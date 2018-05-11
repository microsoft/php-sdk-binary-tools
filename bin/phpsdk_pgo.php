<?php

include dirname(__FILE__) . "/../lib/php/autoload.php";

use SDK\Config;
use SDK\Exception;
use SDK\Build\PGO\Controller;

$sopt = "itudhs:frc:";
$lopt = array("init", "train", "up", "down", "help", "scenario:", "force", "ready", "cases:");

$cmd = NULL;
/* TODO For now we simply check the current php build, this could be extended to take arbitrary binaries. */
$deps_root = NULL;
$php_root = NULL;
$scenario = NULL;
$force = false;
$cases = NULL;

try {
	$opt = getopt($sopt, $lopt);
	foreach ($opt as $name => $val) {
		switch ($name) {
		case "i":
		case "init":
			$cmd = "init";
			break;
		case "ready":
			$cmd = "check_init";
			break;
		case "t":
		case "train":
			$cmd = "train";
			break;
		case "u":
		case "up":
			$cmd = "up";
			break;
		case "d":
		case "down":
			$cmd = "down";
			break;
		case "s":
		case "scenario":
			$scenario = $val;
			break;
		case "f":
		case "force":
			$force = true;
			break;
		case "c":
		case "cases":
			$cases = explode(",", $val);
			break;
		case "h": case "help":
			usage(0);
			break;

		}
	}

	if (NULL === $cmd) {
		usage();
	}

	$deps_root = Config::getDepsLocalPath();

	if ("check_init" != $cmd) {
		/* XXX Need these checks for more safety, as long as the dist zipballs are not supported. */
		if (!file_exists("Makefile")) {
			throw new Exception("Makefile not found. Arbitrary php snapshots are not supported yet, switch to the php source dir.");
		}
		if (preg_match(",BUILD_DIR=(.+),", file_get_contents("Makefile"), $m)) {
			$php_root = trim($m[1]);
		}
		if (!$php_root || !file_exists($php_root)) {
			throw new Exception("Invalid php root dir encountered '$php_root'.");
		}
	}

	$controller = new Controller($cmd, $scenario, $cases);
	$controller->handle($force);

	if ("check_init" == $cmd) {
		/* 0 for success, fail otherwise. */
		$ret = ($controller->isInitialized() === false);
		exit((int)$ret);
	}

	/*$env = getenv();
	$env["PATH"] = $deps_root . DIRECTORY_SEPARATOR . "bin;" . $env["PATH"];

	$php = $php_root . DIRECTORY_SEPARATOR . "php.exe";
	$php = $php_root . DIRECTORY_SEPARATOR . "php.exe";*/

} catch (Throwable $e) {
	throw $e;
	exit(3);
}


function usage(int $code = -1)
{
	echo "PHP SDK PGO training tool.", PHP_EOL;
	echo "Usage: ", PHP_EOL, PHP_EOL;
	echo "Commands:", PHP_EOL;
	echo "  -i --init     Initialize training environment.", PHP_EOL;
	echo "  -t --train    Run training. This involves startup, training and shutdown.", PHP_EOL;
	echo "  -u --up       Startup training environment.", PHP_EOL;
	echo "  -d --down     Shutdown training environment.", PHP_EOL;
	echo "  -f --force    Force requested operation. Not every option can be forced.", PHP_EOL;
	echo "  -s --scenario Run training with a specified scenario.", PHP_EOL;
	echo "  -c --cases    Run training with a specified cases only.", PHP_EOL;

	/*echo "  -p --php-root  PHP binary to train.", PHP_EOL;*/

	$code = -1 == $code ? 0 : $code;
	exit($code);
}

function msg(string $s, int $code = 0) {
	echo $s, PHP_EOL;
	exit($code);
}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 * vim600: sw=4 ts=4 fdm=marker
 * vim<600: sw=4 ts=4
 */
