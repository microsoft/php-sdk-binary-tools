<?php

namespace symfony_demo_pdo_mysql;

use SDK\Build\PGO\Abstracts;
use SDK\Build\PGO\Interfaces;
use SDK\Build\PGO\Config;
use SDK\Build\PGO\PHP;
use SDK\{Config as SDKConfig, Exception, FileOps};
use SDK\Build\PGO\Tool;

class TrainingCaseHandler extends Abstracts\TrainingCase implements Interfaces\TrainingCase
{
	protected $conf;
	protected $base;
	protected $nginx;
	protected $maria;
	protected $php;
	protected $max_runs = 1;

	public function __construct(Config $conf, ?Interfaces\Server $nginx, ?Interfaces\Server\DB $srv_db)
	{
		if (!$nginx) {
			throw new Exception("Invalid NGINX object");
		}

		$this->conf = $conf;
		$this->base = $this->conf->getCaseWorkDir($this->getName());
		$this->nginx = $nginx;
		$this->maria = $srv_db;
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
		return $this->conf->getToolsDir() . DIRECTORY_SEPARATOR . "composer.phar";
	}

	protected function setupDist() : void
	{
		$case_dir = $this->conf->getCasesTplDir($this->getName());

		if (!is_dir($this->conf->getCaseWorkDir($this->getName()))) {
			echo "Setting up in '{$this->base}'\n";

			$ver = $this->conf->getSectionItem($this->getName(), "symfony_demo_version");
			$php = new PHP\CLI($this->conf);
			$php->exec($this->getToolFn() . " create-project symfony/symfony-demo " . $this->base . " " . $ver);

			copy("$case_dir/env.tpl", "{$this->base}/.env");
			copy("$case_dir/doctrine.yaml", "{$this->base}/config/packages/doctrine.yaml");
			unset($php);
		}

		$this->maria->up();

		$this->maria->query("DROP DATABASE IF EXISTS " . $this->getName());
		$this->maria->query("CREATE DATABASE " . $this->getName());

		$this->maria->import("$case_dir/blog.mysql", $this->getName());

		$this->maria->down(true);

		$port = $this->getHttpPort();
		$host = $this->getHttpHost();

		$vars = array(
			$this->conf->buildTplVarName($this->getName(), "docroot") => str_replace("\\", "/", $this->base . DIRECTORY_SEPARATOR . "public"),
		);
		$tpl_fn = $this->conf->getCasesTplDir($this->getName()) . DIRECTORY_SEPARATOR . "nginx.partial.conf";
		$this->nginx->addServer($tpl_fn, $vars);
	}

	public function setupUrls()
	{
		$this->maria->up();
		$this->nginx->up();

		$url = "http://" . $this->getHttpHost() . ":" . $this->getHttpPort() . "/en/blog/";
		$s = file_get_contents($url);

		echo "Generating training urls.\n";

		$lst = array();
		if (preg_match_all(", href=\"([^\"]+)\",", $s, $m)) {
			foreach ($m[1] as $u) {
				if (strlen($u) >= 2 && "/" == $u[0] && "/" != $u[1] && !in_array(substr($u, -3), array("css", "xml", "ico"))) {
					$ur = "http://" . $this->getHttpHost() . ":" . $this->getHttpPort() . $u;
					if (!in_array($ur, $lst) && $this->probeUrl($ur)) {
						$lst[] = $ur;
					}
				}
			}
		}

		if (empty($lst)) {
			printf("\033[31m WARNING: Training URL list is empty, check the regex and the possible previous error messages!\033[0m\n");
		}

		$this->nginx->down(true);
		$this->maria->down(true);

		$fn = $this->getJobFilename();
		$s = implode("\n", $lst);
		if (strlen($s) !== file_put_contents($fn, $s)) {
			throw new Exception("Couldn't write '$fn'.");
		}
	}

	public function prepareInit(Tool\PackageWorkman $pw, bool $force = false) : void
	{
		/*$url = $this->conf->getSectionItem($this->getName(), "symfony_phar_url");
		$pw->fetch($url, $this->getToolFn(), $force);*/
	}

	public function init() : void
	{
		echo "Initializing " . $this->getName() . ".\n";

		$this->setupDist();
		$this->setupUrls();

		echo $this->getName() . " initialization done.\n";
		echo $this->getName() . " site configured to run under " . $this->getHttpHost() . ":" .$this->getHttpPort() . "\n";
	}
}


