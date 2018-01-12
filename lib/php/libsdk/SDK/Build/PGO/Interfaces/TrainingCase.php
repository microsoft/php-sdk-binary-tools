<?php

namespace SDK\Build\PGO\Interfaces;

use SDK\Build\PGO\Config as PGOConfig;
use SDK\Build\PGO\Tool\PackageWorkman;


interface TrainingCase
{
	public function __construct(PGOConfig $conf, ?Server $srv_http, ?Server\DB $srv_db);

	/* Name of the training case, usually should be same as dirname and namespace. */
	public function getName() : string;

	/* Prepare anything necessary to start initialization, like fetch required packages, etc. */
	public function prepareInit(PackageWorkman $pw, bool $force = false) : void;

	/* Initialize the case, run only once on a new checkout. */
	public function init() : void;

	/* Run training. */
	public function run() : void;

	/* Get training type, it's like "web", "cli", etc.*/
	public function getType() : string;
}

