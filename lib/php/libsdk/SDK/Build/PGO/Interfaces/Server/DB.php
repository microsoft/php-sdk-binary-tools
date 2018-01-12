<?php

namespace SDK\Build\PGO\Interfaces\Server;

use SDK\Build\PGO\Config;
use SDK\Build\PGO\Interfaces;

interface DB extends Interfaces\Server
{
	public function __construct(Config $conf);
	public function query(string $s, string $db = NULL) : void;
}

