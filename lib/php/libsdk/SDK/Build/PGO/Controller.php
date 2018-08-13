<?php

namespace SDK\Build\PGO;

use SDK\{Exception, Lock};
use SDK\Build\PGO\Config as PGOConfig;
use SDK\Build\PGO\Server\{MariaDB, NGINX, PostgreSQL};
use SDK\Build\PGO\PHP;
use SDK\Build\PGO\Tool\{PGO, PackageWorkman};


/* TODO add bench action */

class Controller
{
	protected $cmd;
	protected $scenario;
	protected $conf;
	protected $cases;

	public function __construct(string $cmd, ?string $scenario, ?array $cases)
	{
		$this->cmd = $cmd;

		if (NULL == $scenario) {
			$scenario = "default";
		}
		$this->scenario = $scenario;
		$this->cases = $cases;
	}

	protected function vitalizeSrv()
	{
		$all = $this->conf->getSrv("all");

		if (empty($all)) {
			$php_fcgi_tcp = new PHP\FCGI($this->conf, true);
			$this->conf->addSrv(new NGINX($this->conf, $php_fcgi_tcp));

			$this->conf->addSrv(new MariaDB($this->conf));
			/* Uncomment to enable PostgreSQL*/
			/* $this->conf->addSrv(new PostgreSQL($this->conf));*/

			$all = $this->conf->getSrv("all");
		}

		return $all;
	}

	protected function setupConfig($cmd)
	{
		switch ($cmd) {
			default:
				throw new Exception("Unknown action '{$cmd}'.");
				break;
			case "check_init":
				$cnf = new PGOConfig(PGOConfig::MODE_CHECK_INIT);
				break;
			case "init":
				$cnf = new PGOConfig(PGOConfig::MODE_INIT);
				break;
			case "train":
			case "up":
			case "down":
				$cnf = new PGOConfig(PGOConfig::MODE_RUN);
		}
		$cnf->setScenario($this->scenario);

		return $cnf;
	}

	public function handle($force)
	{
		/*$mode = (int)("init" !== $this->cmd);
		$mode = (PGOConfig::MODE_INIT == $mode && $force) ? PGOConfig::MODE_REINIT : $mode;
		$this->conf = new PGOConfig("init" !== $this->cmd);
		$this->conf->setScenario($this->scenario);*/
		$this->conf = $this->setupConfig($this->cmd);

		switch ($this->cmd) {
			default:
				throw new Exception("Unknown action '{$this->cmd}'.");
				break;
			case "init":
				$lk = new Lock("pgo_init");
				if (!$lk->locked()) {
					echo "Another process runs initialization right now, waiting.", PHP_EOL;
					$lk->exclusive(true);
					echo "Another process finished running initialization, I quit as well.", PHP_EOL;
					return;
				}
				$this->init($force);
				break;
			case "train":
				$lk = new Lock("pgo_train");
				if (!$lk->locked()) {
					echo "Another process runs training right now, I have to wait.", PHP_EOL;
					$lk->exclusive(true);
					echo "Another process finished training, I may continue.", PHP_EOL;
				}
				$this->train();
				break;
			case "up":
				$this->up();
				break;

			case "down":
				$this->down($force);
				break;
			case "check_init":
				// pass
				break;
		}
	}

	protected function initWorkDirs() : void
	{
		$dirs = array(
			$this->conf->getSrvDir(),
			$this->conf->getToolsDir(),
			$this->conf->getHtdocs(),
			$this->conf->getJobDir(),
			$this->conf->getPkgCacheDir(),
		);

		foreach ($dirs as $dir) {
			if (!is_dir($dir)) {
				if (!mkdir($dir)) {
					throw new Exception("Failed to create '$dir'.");
				}
			}
		}
	}

	protected function prepareStandaloneTools(PackageWorkman $pw, bool $force = false) : void
	{
		$php = new PHP\CLI($this->conf);

		$composer = $this->conf->getToolsDir() . DIRECTORY_SEPARATOR . "composer.phar";
		if (!file_exists($composer) || $force) {
			/* XXX this needs to go into the config, specifically for composer maybe even separate class. */
			$url = "https://getcomposer.org/installer";
			/* XXX remove the explicit version option when symfony demo is fixed. */
			$tool = $this->conf->getToolsDir() . DIRECTORY_SEPARATOR . "composer-setup.php";
			$pw->fetch($url, $tool, $force);
			$php->exec("$tool --install-dir=" . $this->conf->getToolsDir());
			unlink($tool);
		}
	}

	public function init(bool $force = false)
	{
		echo "\nInitializing PGO training environment.\n\n";

		$this->initWorkDirs();

		$pw = new PackageWorkman($this->conf);

		$this->prepareStandaloneTools($pw, $force);

		$srvs = $this->vitalizeSrv();
		foreach ($srvs as $srv) {
			$srv->prepareInit($pw, $force);
		}

		foreach (new TrainingCaseIterator($this->conf) as $handler) {
			$handler->prepareInit($pw, $force);
		}

		foreach ($srvs as $srv) {
			$srv->init();
			echo "\n";
		}

		echo "\n";
		foreach (new TrainingCaseIterator($this->conf) as $handler) {
			$handler->init();
			echo "\n";
		}

		echo "PGO training environment Initialization complete.\n";
	}

	public function isInitialized()
	{
		return $this->conf->isinitialized();
	}

	public function train()
	{
		if (!$this->isInitialized()) {
			throw new Exception("PGO training environment is not initialized.");
		}

		echo "\nStarting PGO training using scenario '{$this->scenario}'.\n\n";
		$this->up();

		/* Clean the PGO db files, only needed once.
			Imply also, that any data created during init or
			startup is wasted. It is done by dumpbing the data
		 	from the current running processes and subsequently
		 	removing the files. */
		$php = $this->conf->getSrv("nginx")->getPhp();
		$pgo = new PGO($this->conf, $php);
		$pgo->waste();
		$pgo->clean();
		unset($pgo);

		$cases = $this->cases;
		foreach (new TrainingCaseIterator($this->conf) as $handler) {
			$name = $handler->getName();
			/* Just a white list handling for now. */
			if (is_array($cases)) {
				if (!in_array($name, $cases)) {
					continue;
				}
				$key = array_search($name, $cases);
				unset($cases[$key]);
			}

			echo "\n";
			$handler->run();
		}
		if (is_array($cases) && !empty($cases)) {
			echo "\n\033[31m WARNING: The cases " . implode(",", $cases) . " don't exist and was ignored!\033[0m\n\n";
		}

		/* All the PGC files are merged, simply clean them out. */
		$pgo = new PGO($this->conf, $php);
		$pgo->clean(true, false);
		unset($pgo);

		$this->down();
		echo "PGO training complete.\n";
	}

	public function up()
	{

		if (!$this->isInitialized()) {
			throw new Exception("PGO training environment is not initialized.");
		}
		echo "\nStarting up PGO environment.\n\n";

		foreach ($this->vitalizeSrv("all") as $srv) {
			$srv->up();
			echo "\n";
		}

		sleep(1);

		echo "The PGO environment is up.\n";
	}

	public function down(bool $force = false)
	{
		if (!$this->isInitialized()) {
			throw new Exception("PGO training environment is not initialized.");
		}
		/* XXX check it was started of course. */
		echo "\nShutting down PGO environment.\n\n";

		foreach ($this->vitalizeSrv("all") as $srv) {
			$srv->down($force);
			echo "\n";
		}

		sleep(1);

		echo "The PGO environment has been shut down.\n";
	}
}
