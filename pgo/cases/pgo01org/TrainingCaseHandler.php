<?php

namespace pgo01org;

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
	protected $maria;
	protected $php;
	protected $max_runs = 12;

	public function __construct(Config $conf, ?Interfaces\Server $nginx, ?Interfaces\Server\DB $maria)
	{
		if (!$nginx) {
			throw new Exception("Invalid NGINX object");
		}

		$this->conf = $conf;
		$this->base = $this->conf->getCaseWorkDir($this->getName());
		$this->nginx = $nginx;
		$this->maria = $maria;
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

	protected function getToolFn() : string
	{
		return $this->conf->getToolsDir() . DIRECTORY_SEPARATOR . "wp-cli.phar";
	}

	protected function setupDist() : void
	{
		$cmd_path_arg = "--path=" . $this->base;

		if (!is_dir($this->base)) {
			echo "Setting up " . $this->getName() . " in '{$this->base}'\n";
			/* XXX Use host PHP for this. */
			$php = new PHP\CLI($this->conf);
			$php->exec($this->getToolFn() . " core download --force $cmd_path_arg");
			unset($php);
		}
		
		$http_port = $this->getHttpPort();
		$http_host = $this->getHttpHost();
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

//		$this->maria->query("DROP DATABASE IF EXISTS " . $this->getName());
//		$this->maria->query("CREATE DATABASE " . $this->getName());

		$htdocs = $this->conf->getHtdocs($this->getName());
		$fl = $htdocs . DIRECTORY_SEPARATOR . "constants.php";

		$constants = file_get_contents($fl);
		
		$constants = preg_replace(",define\('DB_USER'.+,", "define('DB_USER', '$db_user');", $constants);
		$constants = preg_replace(",define\('DB_PASSWORD'.+,", "define('DB_PASSWORD', '$db_pass');", $constants);
		$constants = preg_replace(",define\('DB_NAME'.+,", "define('DB_NAME', '" . $this->getName() . "');", $constants);
		$constants = preg_replace(",define\('DB_HOST'.+,", "define('DB_HOST', '$db_host:$db_port');", $constants);
		file_put_contents($fl, $constants);

		//$php->exec($cmd, NULL, $env);
		/* TODO check status or switch to cli. */
		$out = file_get_contents("http://$http_host:$http_port/init.php");
		echo $out, PHP_EOL;

		$this->nginx->down(true);
		$this->maria->down(true);

	}

	public function setupUrls()
	{
		$url = "http://" . $this->getHttpHost() . ":" . $this->getHttpPort();

		echo "Generating training urls.\n";

		$fn = $this->getJobFilename();
		if (strlen($url) !== file_put_contents($fn, $url)) {
			throw new Exception("Couldn't write '$fn'.");
		}
	}

	public function prepareInit(Tool\PackageWorkman $pw, bool $force = false) : void
	{
		$url = $this->conf->getSectionItem($this->getName(), "pgo01org_zip_url");
		$pw->fetchAndUnzip($url, "php_pgo_training_scripts_01org.zip", $this->conf->getHtdocs(), "pgo01org", $force);
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
