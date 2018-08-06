<?php

namespace SDK\Build\PGO\PHP;

use SDK\Build\PGO\Interfaces;
use SDK\Build\PGO\Abstracts;
use SDK\Build\PGO\Config as PGOConfig;
use SDK\{Exception};
use SDK\Build\PGO\Tool\PackageWorkman;

class CLI extends Abstracts\PHP implements Interfaces\PHP
{
	public function __construct(PGOConfig $conf)
	{
		$this->conf = $conf;
		$this->scenario = $conf->getScenario();
		// Don't do that, it'll be a recursive dependency. 
		// Once we need to train CLI, we'll need to split
		// a new class, this one is a utility class.
		//$this->id = $this->getIdString();

		$this->setupPaths();
	}

	public function prepareInit(PackageWorkman $pw, bool $force = false) : void
	{
		/* pass */
	}

	public function init() : void
	{
		/* pass */
	}

	public function up() : void
	{
		/* pass */
	}

	public function down(bool $force = false) : void
	{
		/* pass */
	}

	public function getExeFilename() : string
	{
		$exe = $this->getRootDir() . DIRECTORY_SEPARATOR . "php.exe";

		if (!file_exists($exe)) {
			throw new Exception("Path '$exe' doesn't exist.");
		}

		return $exe;
	}
}
