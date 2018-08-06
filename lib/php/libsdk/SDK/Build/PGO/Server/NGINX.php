<?php

namespace SDK\Build\PGO\Server;

use SDK\Build\PGO\Interfaces;
use SDK\Build\PGO\Abstracts;
use SDK\Build\PGO\{Config as PGOConfig};
use SDK\{Exception, FileOps};
use SDK\Build\PGO\Tool\PackageWorkman;

class NGINX extends Abstracts\Server implements Interfaces\Server\HTTP
{
	use FileOps;

	protected $name = "NGINX";
	protected $conf;
	protected $base;
	protected $php;

	public function __construct(PGOConfig $conf, Interfaces\PHP $php)
	{
		$this->conf = $conf;
		$this->base = $conf->getSrvDir(strtolower($this->name));
		$this->php = $php;
	}

	protected function setupDist() : void
	{
		$nginx_conf_in = $this->conf->getTplDir($this->name) . DIRECTORY_SEPARATOR . "nginx.conf";
		$conf_fn = $this->base . DIRECTORY_SEPARATOR . "conf" . DIRECTORY_SEPARATOR . "nginx.conf";

		$port = $this->conf->getSectionItem($this->name, "port");
		if (!$port) {
			$port = $this->conf->getNextPort();
			$this->conf->setSectionItem($this->name, "port", $port);
		}

		$vars = array(
			$this->conf->buildTplVarName($this->name, "docroot") => str_replace("\\", "/", $this->base . DIRECTORY_SEPARATOR . "html"),
		);

		$this->conf->processTplFile(
			$nginx_conf_in,
			$conf_fn,
			$vars
		);
	}

	public function prepareInit(PackageWorkman $pw, bool $force = false) : void
	{
		$url = $this->conf->getSectionItem($this->name, "pkg_url");
		$pw->fetchAndUnzip($url, "nginx.zip", $this->conf->getSrvDir(), "nginx", $force);
	}

	public function init() : void
	{
		echo "Initializing " . $this->name . ".\n";

		$this->setupDist();

		$this->upMe();
		$this->downMe(true);


		echo $this->name . " initialization done.\n";
	}

	protected function upMe() : void
	{
		echo "Starting " . $this->name . ".\n";

		$cwd = getcwd();

		chdir($this->base);

		$h = popen("start /b .\\nginx.exe 2>&1", "r");
		if (!is_resource($h)) {
			chdir($cwd);
			throw new Exception("Failed to start MariaDB.");
		}
		sleep(3);

/*		while (!feof($h)) {
			echo fread($h, 1024);
		}*/
	
		pclose($h);

		chdir($cwd);

		echo $this->name . " started.\n";
	}

	public function up() : void
	{

		$this->php->up();
		$this->upMe();
	}

	public function downMe(bool $force = false) : void
	{
		echo "Stopping " . $this->name . ".\n";

		$cwd = getcwd();

		chdir($this->base);

		exec(".\\nginx.exe -s quit");

		if ($force) {
			sleep(1);
			exec("taskkill /f /im nginx.exe >nul 2>&1");
		}

		chdir($cwd);

		echo $this->name . " stopped.\n";
	}

	public function down(bool $force = false) : void
	{
		$this->php->down();
		$this->downMe($force);
	}

	/* Use only for init phase! */
	public function addServer(string $part_tpl_fn, array $tpl_vars = array())
	{
		if (!file_exists($part_tpl_fn)) {
			throw new Exception("Template file '$part_tpl_fn' doesn't exist.");
		}

		/* We've already did a fresh (re)config, so use the work file now. */
		$nginx_conf_in = $this->base . DIRECTORY_SEPARATOR . "conf" . DIRECTORY_SEPARATOR . "nginx.conf";
		$cur_conf = file_get_contents($nginx_conf_in);

		$in = file_get_contents($part_tpl_fn);
		$out = $this->conf->processTpl($in, $tpl_vars);

		$tpl = "    # PHP_SDK_PGO_NGINX_SERVERS_INC_TPL";
		$new_conf = str_replace($tpl, "$out\n$tpl", $cur_conf);

		$conf_fn = $this->base . DIRECTORY_SEPARATOR . "conf" . DIRECTORY_SEPARATOR . "nginx.conf";
		if (!file_put_contents($conf_fn, $new_conf)) {
			throw new Exception("Couldn't write '$conf_fn'.");
		}
	}
}
