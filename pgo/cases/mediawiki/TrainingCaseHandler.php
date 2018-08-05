<?php

namespace mediawiki;

use SDK\Build\PGO\Abstracts;
use SDK\Build\PGO\Interfaces;
use SDK\Build\PGO\Config;
use SDK\Build\PGO\PHP;
use SDK\Exception;
use SDK\Build\PGO\Tool;

class TrainingCaseHandler extends Abstracts\TrainingCase implements Interfaces\TrainingCase
{
	protected $conf;
	protected $base;
	protected $nginx;
	protected $php;
	protected $max_runs = 4;

	public function __construct(Config $conf, ?Interfaces\Server $nginx, ?Interfaces\Server\DB $srv_db)
	{
		if (!$nginx) {
			throw new Exception("Invalid NGINX object");
		}

		$this->conf = $conf;
		$this->base = $this->conf->getCaseWorkDir($this->getName());
		$this->nginx = $nginx;
		$this->php = $nginx->getPhp();
	}

	public function getName() : string
	{
		return __NAMESPACE__;
	}

	public function getJobFilename() : string
	{
		return $this->conf->getJobDir() . DIRECTORY_SEPARATOR . $this->getName() . ".txt";
	}

	protected function getToolFn(string $what) : string
	{
		$ret = NULL;
		if ("install" == $what) {
			$ret = $this->conf->getCaseWorkDir($this->getName()) . DIRECTORY_SEPARATOR . "maintenance" . DIRECTORY_SEPARATOR . "install.php";
		}

		return $ret;
	}

	protected function setupDist() : void
	{
		$php = new PHP\CLI($this->conf);

		$port = $this->getHttpPort();
		$host = $this->getHttpHost();

		$vars = array(
			$this->conf->buildTplVarName($this->getName(), "docroot") => str_replace("\\", "/", $this->base),
		);
		$tpl_fn = $this->conf->getCasesTplDir($this->getName()) . DIRECTORY_SEPARATOR . "nginx.partial.conf";
		$this->nginx->addServer($tpl_fn, $vars);

		$settings = $this->conf->getCaseWorkDir($this->getName()) . DIRECTORY_SEPARATOR . "LocalSettings.php";
		if (is_file($settings)) {
			unlink($settings);
		}

		$site_adm = trim(shell_exec("pwgen -1 -s 8"));
		$this->conf->setSectionItem($this->getName(), "site_admin_user", $site_adm);
		$site_pw = trim(shell_exec("pwgen -1 -s 8"));
		$this->conf->setSectionItem($this->getName(), "site_admin_pass", $site_pw);

		$db_dir = $this->conf->getCaseWorkDir($this->getName()) . DIRECTORY_SEPARATOR . "database";
		$nom = $this->getName();
		$db_dir = $this->conf->getCaseWorkDir($this->getName()) . DIRECTORY_SEPARATOR . "database";
		if (!is_dir($db_dir)) {
			mkdir($db_dir);
		}
		$cmd = $this->getToolFn("install") . " --dbtype=sqlite --dbpath=$db_dir --pass=$site_pw --server=http://$host:$port/ $nom $site_adm";
		echo "$cmd\n";
		$php->exec($cmd);
	}

	public function setupUrls()
	{
		$this->nginx->up();

		$url = "http://" . $this->getHttpHost() . ":" . $this->getHttpPort();
		$s = file_get_contents($url);

		echo "Generating training urls.\n";

		$lst = array();
		if (preg_match_all(", href=\"([^\"]+)\",", $s, $m)) {
			foreach ($m[1] as $u) {
				if ("/" == $u[0] && "/" != $u[1] && !in_array(substr($u, -3), array("css", "xml", "ico"))) {
					$u = str_replace(
						array("&amp;"),
						array("&"),
						$u
					);
					$ur = "http://" . $this->getHttpHost() . ":" . $this->getHttpPort() . $u;
					if (!in_array($ur, $lst) && $this->probeUrl($ur)) {
						$lst[] = $ur;
					}
				}
			}
		}

		$this->nginx->down(true);

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
		$url = $this->conf->getSectionItem($this->getName(), "mediawiki_zip_url");
		$pw->fetchAndUnzip($url, "mediawiki.zip", $this->conf->getCaseWorkDir(), $this->getName(), $force);

		$php = new PHP\CLI($this->conf);

		$lock = $this->conf->getCaseWorkDir($this->getName()) . DIRECTORY_SEPARATOR . "composer.lock";
		if (!file_exists($lock) || $force) {
			$composer = $this->conf->getToolsDir() . DIRECTORY_SEPARATOR . "composer.phar";
			$composer_cmd = file_exists($lock) ? "update" : "install";
			$cmd = $composer . " $composer_cmd --no-dev --working-dir=" . $this->conf->getCaseWorkDir($this->getName());
			$php->exec($cmd);
		}

		$skin_url = "https://github.com/wikimedia/mediawiki-skins-Vector/archive/master.zip";
		$skin_path = $this->conf->getCaseWorkDir($this->getName()) . DIRECTORY_SEPARATOR . "skins" . DIRECTORY_SEPARATOR . "Vector";
		if (!file_exists($skin_path . DIRECTORY_SEPARATOR . "SkinVector.php")) {
			if (is_dir($skin_path)) {
				rmdir($skin_path);
			}
			$pw->fetchAndUnzip($skin_url, "mediawiki_skin.zip", dirname($skin_path), "Vector", $force);
		}
	}

	public function init() : void
	{
		echo "Initializing " . $this->getName() . ".\n";

		$this->setupDist();
		$this->setupUrls();

		echo $this->getName() . " initialization done.\n";
		echo $this->getName() . " site configured to run under " . $this->getHttpHost() . ":" . $this->getHttpPort() . "\n";
	}
}
