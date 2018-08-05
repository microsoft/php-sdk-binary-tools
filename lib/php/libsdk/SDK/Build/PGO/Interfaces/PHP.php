<?php

namespace SDK\Build\PGO\Interfaces;

use SDK\Build\PGO\Tool\PackageWorkman;

interface PHP
{
	/* Prepare anything necessary to start initialization, like fetch required packages, etc. */
	public function prepareInit(PackageWorkman $pw, bool $force = false) : void;
	public function init() : void;
	public function up() : void;
	public function down(bool $force = false) : void;
	public function getVersion(bool $short = false) : string;
	public function getExeFilename() : string;
}
