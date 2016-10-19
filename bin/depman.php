<?php

include dirname(__FILE__) . "/../lib/php/libsdk/autoload.php";

use SDK\Config;
use SDK\Exception;

$sopt = "s:cuhb:a:";
$lopt = array(
	"branch:",
	"update",
	"check",
	"stability:",
	"arch:",
	"help",
);

$cmd = NULL;
$stability = NULL;
$arch = NULL;

try {

	$opt = getopt($sopt, $lopt);
	foreach ($opt as $name => $val) {
		switch ($name) {
			default:
				throw new Exception("Unknown switch '$name'");
			break;

			case "h":
			case "help":
				usage(0);
				break;

			case "b":
			case "branch":
				Config::setCurrentBranchName($val);
				break;

			case "s":
			case "stability":
				if ("stable" != $val && "staging" != $val) {
					throw new Exception("Unknown stability keyword, either stable or staging is accepted");
				}
				$stability = $val;
				break;

			case "a":
			case "arch":
				if ("x64" != $val && "x86" != $val) {
					throw new Exception("Unknown arch keyword, either x86 or x64 is accepted");
				}
				$arch = $val;
				break;

			case "c":
			case "check":
				$cmd = "check";
				break;
			case "u":
			case "update":
				$cmd = "update";
				break;
		}
	}

	if (NULL === $arch) {
		usage();
	}

	if (NULL === $cmd) {
		usage();
	}

	if (NULL === $stability) {
		if ("master" == Config::getCurrentBranchName()) {
			$stability = "staging";
		} else {
			$stability = "stable";
		}
	}

	$branch_data = Config::getCurrentBranchData();
	echo "\nConfigured for " . Config::getCurrentBranchName() . "-$branch_data[crt]-$arch-$stability\n\n";

	$dm = new SDK\Dependency\Manager($stability, $arch);
	switch ($cmd) {
		default:
			throw new Exception("Unknown command '$cmd'");
		break;
		case "check":
			$ret = $dm->runCheckCmd();
			if ($ret) {
				echo "Updates are available.";
			} else {
				echo "No updates are available.";
			}
			break;
		case "update":
			break;
	}

} catch (Throwable $e) {
	echo "\nError: ", $e->getMessage(), PHP_EOL;
	exit(3);
}

function usage(int $code = -1)
{
	echo "PHP SDK dependency handling tool.", PHP_EOL;
	echo "Usage: ", PHP_EOL;
	echo "  -a --arch      Architecture.", PHP_EOL;
	echo "  -b --branch    Use dependencies for a specific branch.", PHP_EOL;
	echo "  -c --check     Check for dependency updates.", PHP_EOL;
	echo "  -h --help      Show help message.", PHP_EOL;
	echo "  -s --stability One of stable or staging.", PHP_EOL;
	echo "  -u --update    Update dependencies.", PHP_EOL;
	echo "", PHP_EOL;

	$code = -1 == $code ? 0 : $code;
	exit($code);
}

exit(0);

