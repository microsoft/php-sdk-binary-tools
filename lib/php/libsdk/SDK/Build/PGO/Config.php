<?php

namespace SDK\Build\PGO;

use SDK\Exception;

class Config
{
	const MODE_INIT = 0;
	const MODE_RUN = 1;
	const MODE_REINIT = 2; /* currently unused */
	const MODE_CHECK_INIT = 3;

	protected $mode;
	protected $last_port = 8081;
	protected $sections = array();
	protected $scenario = "default";
	protected $tpl_vars = array();
	protected $srv = array();

	public function __construct(int $mode = self::MODE_RUN)
	{
		if (self::MODE_CHECK_INIT == $mode) {
			// XXX The check is simple right now, so this is sufficient. 
			return;
		}

		if (!$this->isInitialized()) {
			$this->initWorkDir();
		}

		if (self::MODE_REINIT == $mode) {
			$fn = $this->getWorkSectionsFilename();
			if (file_exists($fn)) {
				unlink($fn);
			}
			$mode = self::MODE_INIT;
		}

		$this->mode = $mode;


		if (self::MODE_INIT == $mode) {
			foreach (array("nginx", "mariadb", "postgresql", "php") as $i) {
				$this->importSectionFromDir($i, $this->getTplDir() . DIRECTORY_SEPARATOR . $i);
			}
		} else if (self::MODE_RUN == $mode) {
			$fn = $this->getWorkSectionsFilename();
			if (!file_exists($fn)) {
				throw new Exception("Required config doesn't exist under '$fn'.");
			}
			$s = file_get_contents($fn);
			$this->sections = json_decode($s, true);
			foreach($this->sections as $k => $v) {
				$this->importTplVars($k, $v);
			}

		} else {
			throw new Exception("Unknown config mode '$mode'.");
		}
	}

	protected function initWorkDir() : void
	{
		if (!mkdir($this->getWorkDir())) {
			throw new Exception("Failed to create " . $this->getWorkDir());
		}
	}

	public function isInitialized()
	{
		/* XXX Could be some better check. */
		return is_dir($this->getWorkDir());
	}


	public function getToolsDir() : string
	{
		$base = $this->getWorkDir();

		return $base . DIRECTORY_SEPARATOR . "tools";
	}

	public function getWorkDir() : string
	{
		$base = getenv("PHP_SDK_ROOT_PATH");

		return $base . DIRECTORY_SEPARATOR . "pgo" . DIRECTORY_SEPARATOR . "work";
	}

	public function getPkgCacheDir() : string
	{
		$base = $this->getWorkDir();

		return $base . DIRECTORY_SEPARATOR . "package_cache";
	}

	public function getJobDir(string $name = NULL) : string
	{
		$ret = $this->getWorkDir() . DIRECTORY_SEPARATOR . "job";

		if ($name) {
			$ret .= DIRECTORY_SEPARATOR . $name;
		}

		return $ret;
	}

	public function getSrvDir(string $name = NULL) : string
	{
		$ret = $this->getWorkDir() . DIRECTORY_SEPARATOR . "server";

		if ($name) {
			$ret .= DIRECTORY_SEPARATOR . $name;
		}

		return $ret;
	}

	public function getHtdocs(string $name = NULL) : string
	{
		$ret = $this->getWorkDir() . DIRECTORY_SEPARATOR . "htdocs";

		if ($name) {
			$ret .= DIRECTORY_SEPARATOR . $name;
		}

		return $ret;
	}

	public function getTplDir(string $name = NULL) : string
	{
		$ret = getenv("PHP_SDK_ROOT_PATH") . DIRECTORY_SEPARATOR . "pgo" . DIRECTORY_SEPARATOR . "tpl";

		if ($name) {
			$ret .= DIRECTORY_SEPARATOR . $name;
		}

		return $ret;
	}

	public function getCaseWorkDir(string $name = NULL) : string
	{
		$ret = $this->getWorkDir() . DIRECTORY_SEPARATOR . "htdocs";

		if ($name) {
			$ret .= DIRECTORY_SEPARATOR . $name;
		}

		return $ret;
	}

	public function getCasesTplDir(string $name = NULL) : string
	{
		$ret = getenv("PHP_SDK_ROOT_PATH") . DIRECTORY_SEPARATOR . "pgo" . DIRECTORY_SEPARATOR . "cases";

		if ($name) {
			$ret .= DIRECTORY_SEPARATOR . $name;
		}

		return $ret;
	}

	public function sectionItemExists(...$args) : bool
	{
		$i = 0;
		$k = strtolower($args[$i]);
		$it = $this->sections;

		while (array_key_exists($k, $it)) {
			$it = $it[$k];

			if (++$i >= count($args)) break;

			$k = strtolower($args[$i]);
		}

		return $i == count($args);
	}

	public function getSectionItem(...$args)
	{
		$i = 0;
		$k = strtolower($args[$i]);
		$it = $this->sections;

		while (array_key_exists($k, $it)) {
			$it = $it[$k];

			if (++$i >= count($args)) break;

			$k = strtolower($args[$i]);
		}

		if ($i != count($args)) {
			return NULL;
		}

		return $it;
	}

	public function setSectionItem(...$args) : void
	{
		$val = array_pop($args);

		$i = 0;
		$k = strtolower($args[$i]);
		$it = &$this->sections;

		while (true) {
			$it = &$it[$k];
			if (++$i >= count($args)) break;
			$k = strtolower($args[$i]);
		}

		$it = $val;

		$this->syncTplVars();
		$this->dump();
	}

	public function importSectionFromDir(string $name, string $dir) : void
	{
		$fn = $dir . DIRECTORY_SEPARATOR . "phpsdk_pgo.json";
		if (!file_exists($fn)) {
			throw new Exception("Couldn't import section, file '$fn' doesn't exist.");
		}

		$s = file_get_contents($fn);
		$this->setSectionItem($name, json_decode($s, true));
	}

	protected function syncTplVars() : void
	{
		$this->tpl_vars = array();
		foreach ($this->sections as $k => $v) {
			$this->importTplVars($k, $v);	
		}
	}

	public function buildTplVarName(...$args) : string
	{
		$tpl_k = array("PHP_SDK_PGO");
		
	 	foreach ($args as $a) {
			$tpl_k[] = strtoupper($a);
		}

		return implode("_", $tpl_k);
	}

	protected function importTplVars(string $section_name, array $section) : void
	{
		foreach($section as $k0 => $v0) {

			if (is_array($v0)) {
				if (substr($k0, -4) == ":env") {
					/* Don't put env vars as tpl vars for now. */
					continue;	
				}
				$this->importTplVars($section_name . "_" . $k0, $v0);
			} else {
				$tpl_k = $this->buildTplVarName($section_name, $k0);
				$this->tpl_vars[$tpl_k] = $v0;
			}
		}	
	}

	public function processTpl(string $s, array $additional_vars = array()) : string
	{
		$vars = array_merge($this->tpl_vars, $additional_vars);

		$s = str_replace(array_keys($vars), array_values($vars), $s);

		return $s;
	}

	public function processTplFile(string $tpl_fn, string $dst_fn, array $additional_vars = array()) : void
	{
		if (!file_exists($tpl_fn)) {
			throw new Exception("Template file '$tpl_fn' doesn't exist.");
		}

		$s = file_get_contents($tpl_fn);	
		if (false === $s) {
			throw new Exception("Couldn't read '$tpl_fn'.");
		}

		$s = $this->processTpl($s, $additional_vars);

		if (false === file_put_contents($dst_fn, $s)) {
			throw new Exception("Failed to write '$dst_fn'.");
		}
	}

	public function getWorkSectionsFilename()
	{
		return $this->getWorkDir() . DIRECTORY_SEPARATOR . "phpsdk_pgo.json";
	}

	public function dump(string $fn = NULL) : void
	{
		$fn = $fn ? $fn : $this->getWorkSectionsFilename();

		$s = json_encode($this->sections, JSON_PRETTY_PRINT);

		$ret = file_put_contents($fn, $s);
		if (false === $ret || strlen($s) !== $ret) {
			throw new Exception("Errors with writing to '$fn'.");
		}
	}

	public function setScenario(string $scenario) : void
	{
		if (!in_array($scenario, array("default", "cache"), true)) {
			throw new Exception("Unknown scenario '$scenario'.");
		}
		$this->scenario = $scenario;
	}

	public function getScenario() : string
	{
		return $this->scenario;
	}

	public function getNextPort() : int
	{
		return ++$this->last_port;
	}

	public function setLastPort(int $port) : void
	{
		$this->last_port = $port;
	}

	public function getSdkPhpCmd() : string
	{
		return getenv("PHP_SDK_PHP_CMD");
	}

	public function addSrv($item) : void
	{
		$name = strtolower($item->getName());

		if (isset($this->srv[$name])) {
			throw new Exception("Server '$name' already exists.");
		}

		/* XXX Additional checks could not harm. */
		$this->srv[$name] = $item;
	}

	public function getSrv(?string $name = NULL)
	{
		$ret = NULL;

		$name = strtolower($name);

		if (!$name) {
			return NULL;
		} else if ("all" == $name) {
			return $this->srv;
		} else if (isset($this->srv[$name])) {
			return $this->srv[$name];
		}

		return $ret;
	}
}
