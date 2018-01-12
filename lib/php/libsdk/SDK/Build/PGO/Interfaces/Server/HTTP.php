<?php

namespace SDK\Build\PGO\Interfaces\Server;

use SDK\Build\PGO\Config;
use SDK\Build\PGO\Interfaces;

interface HTTP extends Interfaces\Server
{
	public function __construct(Config $conf, Interfaces\PHP $php);
	public function getPhp() : Interfaces\PHP;
	public function addServer(string $part_tpl_fn, array $tpl_vars = array());
}

