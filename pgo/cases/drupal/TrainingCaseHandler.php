<?php

namespace drupal;

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
	protected $php;
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
		return $this->base . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, array("vendor", "drush", "drush", "drush"));
	}

	protected function setupDist() : void
	{
		$vars = array(
			$this->conf->buildTplVarName($this->getName(), "docroot") => str_replace("\\", "/", $this->base . DIRECTORY_SEPARATOR . "web"),
		);
		$tpl_fn = $this->conf->getCasesTplDir($this->getName()) . DIRECTORY_SEPARATOR . "nginx.partial.conf";
		$this->nginx->addServer($tpl_fn, $vars);

		$php = new PHP\CLI($this->conf);

		$this->nginx->up();

		$cmd = $this->getToolFn() . " site-install demo_umami --db-url=sqlite://{$this->base}/drupal.sqlite --account-mail=\"admin@example.com\" --account-name=admin --account-pass=adminpass --site-mail=\"admin@example.com\" --site-name=\"Site-Install\" --yes";

		$php->exec($cmd, NULL, $env);

		$this->nginx->down(true);
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
				$p = parse_url($u, PHP_URL_PATH);
				if (strlen($p) >= 2 && "/" == $p[0] && "/" != $p[1] && !in_array(substr($p, -3), array("css", "xml", "ico"))) {
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

		$cmd = $this->conf->getToolsDir() . DIRECTORY_SEPARATOR . "composer.phar create-project drupal-composer/drupal-project:8.x-dev {$this->base} --stability dev --no-interaction"; 
		$php->exec($cmd);
	}

	public function init() : void
	{
		echo "Initializing " . $this->getName() . ".\n";
		echo "It is OK to see some warnings here, because the joomla tools are not fully Windows compatible.\n";

		$this->setupDist();
		$this->setupUrls();

		echo $this->getName() . " initialization done.\n";
		echo $this->getName() . " site configured to run under " . $this->getHttpHost() . ":" .$this->getHttpPort() . "\n";
	}
}


