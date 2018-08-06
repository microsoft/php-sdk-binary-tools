<?php

namespace joomla;

use SDK\Build\PGO\Abstracts;
use SDK\Build\PGO\Interfaces;
use SDK\Build\PGO\Config;
use SDK\Build\PGO\PHP;
use SDK\Exception;
use SDK\Build\PGO\Tool;

class TrainingCaseHandler extends Abstracts\TrainingCase implements Interfaces\TrainingCase
{
	protected $base;
	protected $nginx;
	protected $php;
	protected $maria;
	protected $max_runs = 4;

	public function __construct(Config $conf, ?Interfaces\Server $nginx, ?Interfaces\Server\DB $maria)
	{
		if (!$nginx) {
			throw new Exception("Invalid NGINX object");
		}

		$this->conf = $conf;
		$this->base = $this->conf->getCaseWorkDir($this->getName());
		$this->nginx = $nginx;
		$this->php = $nginx->getPhp();
		$this->maria = $maria;
	}

	public function getName() : string
	{
		return __NAMESPACE__;
	}

	public function getJobFilename() : string
	{
		return $this->conf->getJobDir() . DIRECTORY_SEPARATOR . $this->getName() . ".txt";
	}

	protected function getToolFn() : string
	{
		return $this->conf->getToolsDir() . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, array("joomla", "vendor", "joomlatools", "console", "bin", "joomla"));
	}

	protected function setupDist() : void
	{
		$port = $this->getHttpPort();
		$host = $this->getHttpHost();
		$db_port = $this->getDbPort();
		$db_host = $this->getDbHost();
		$db_user = $this->getDbUser();
		$db_pass = $this->getDbPass();

		$vars = array(
			$this->conf->buildTplVarName($this->getName(), "docroot") => str_replace("\\", "/", $this->base),
		);
		$tpl_fn = $this->conf->getCasesTplDir($this->getName()) . DIRECTORY_SEPARATOR . "nginx.partial.conf";
		$this->nginx->addServer($tpl_fn, $vars);

		$php = new PHP\CLI($this->conf);

		$this->maria->up();
		$this->nginx->up();

		$this->maria->query("DROP DATABASE IF EXISTS " . $this->getName());
		$this->maria->query("CREATE DATABASE " . $this->getName() . " CHARACTER SET utf8");

		$htdocs = $this->conf->getCaseWorkDir($this->getName());
		if (is_dir($htdocs . DIRECTORY_SEPARATOR . "_installation")) {
			rename($htdocs . DIRECTORY_SEPARATOR . "_installation", $htdocs . DIRECTORY_SEPARATOR . "installation");
		}

		$env = array(
			"PATH" => $this->conf->getSrvDir(strtolower($this->maria->getName())) . DIRECTORY_SEPARATOR . "bin",
		);

		$www = $this->conf->getCaseWorkDir();
		$login = $db_pass ? "$db_user:$db_pass" : $db_user;
		$cmd = $this->getToolFn() . " site:install --overwrite --sample-data=learn --mysql-database=" . $this->getName() . " --mysql-login=$login --mysql-host=$db_host --mysql-port=$db_port --www=$www " . $this->getName();
		//$cmd = $this->getToolFn() . " site:install --drop --overwrite --sample-data=default --mysql-database=" . $this->getName() . " --mysql-login=$login --mysql-host=$db_host --mysql-port=$db_port --www=$www " . $this->getName();
		//$cmd = $this->getToolFn() . " site:create --clear-cache --disable-ssl --release=3.7 --http-port=$port --sample-data=testing --mysql-database=" . $this->getName() . " --mysql-login=$login --mysql-host=$db_host --mysql-port=$db_port --www=$www " . $this->getName();
		$php->exec($cmd, NULL, $env);

		if (is_dir($htdocs . DIRECTORY_SEPARATOR . "installation")) {
			rename($htdocs . DIRECTORY_SEPARATOR . "installation", $htdocs . DIRECTORY_SEPARATOR . "_installation");
		}

		$fn = $htdocs . DIRECTORY_SEPARATOR . "configuration.php";
		$s = file_get_contents($fn);
		$s = str_replace("public \$debug = '1';", "public \$debug = '0';", $s);
		if (strlen($s) !== file_put_contents($fn, $s)) {
			throw new Exception("Couldn't write '$fn'.");
		}

		$this->nginx->down(true);
		$this->maria->down(true);
	}

	public function setupUrls()
	{
		$this->maria->up();
		$this->nginx->up();

		$url = "http://" . $this->getHttpHost() . ":" . $this->getHttpPort();
		$s = file_get_contents($url);

		$this->nginx->down(true);
		$this->maria->down(true);

		echo "Generating training urls.\n";

		$lst = array();
		if (preg_match_all(", href=\"([^\"]+)\",", $s, $m)) {
			foreach ($m[1] as $u) {
				$h = parse_url($u, PHP_URL_HOST);
				$s = parse_url($u, PHP_URL_SCHEME);
				if ($h && $s) {
					if ($this->getHttpHost() != $h) {
						continue;
					}
					if (!in_array($u, $lst)) {
						$lst[] = $u;
					}
					continue;
				}
				$p = parse_url($u, PHP_URL_PATH);
				if (strlen($p) >= 2 && "/" == $p[0] && "/" != $p[1] && !in_array(substr($p, -3), array("css", "xml", "ico")) &&
					"/using-joomla/extensions/components/news-feeds-component/single-news-feed" != $p) {
					$ur = "http://" . $this->getHttpHost() . ":" . $this->getHttpPort() . $u;
					if (!in_array($ur, $lst)) {
						$lst[] = $ur;
					}
				}
			}
		}

		if (empty($lst)) {
			printf("\033[31m WARNING: Training URL list is empty, check the regex and the possible previous error messages!\033[0m\n");
		}

		$fn = $this->getJobFilename();
		$s = implode("\n", $lst);
		if (strlen($s) !== file_put_contents($fn, $s)) {
			throw new Exception("Couldn't write '$fn'.");
		}
	}

	public function prepareInit(Tool\PackageWorkman $pw, bool $force = false) : void
	{
		$php = new PHP\CLI($this->conf);

		$composer = $this->conf->getToolsDir() . DIRECTORY_SEPARATOR . "composer.phar";
		$joomla_cli_base = $this->conf->getToolsDir() . DIRECTORY_SEPARATOR . "joomla";
		if (!file_exists($this->getToolFn()) || $force) {
			if (!is_dir($joomla_cli_base)) {
				mkdir($joomla_cli_base);
			}
			$cmd = $this->conf->getToolsDir() . DIRECTORY_SEPARATOR . "composer.phar require joomlatools/console --working-dir=" . $joomla_cli_base;
			$php->exec($cmd);
		}

		$url = $this->conf->getSectionItem($this->getName(), "joomla_zip_url");
		$pw->fetchAndUnzip($url, "joomla.zip", $this->conf->getCaseWorkDir($this->getName()), $this->getName(), $force);
	}

	public function init() : void
	{
		echo "Initializing " . $this->getName() . ".\n";
		echo "It is OK to see some warnings here, because the joomla tools are not fully Windows compatible.\n";

		$this->setupDist();
		$this->setupUrls();

		echo $this->getName() . " initialization done.\n";
		echo $this->getName() . " site configured to run under " . $this->getHttpHost() . ":" . $this->getHttpPort() . "\n";
	}
}
