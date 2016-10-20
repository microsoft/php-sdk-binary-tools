<?php

include dirname(__FILE__) . "/../lib/php/libsdk/autoload.php";

use SDK\Config;
use SDK\Exception;

$sopt = "s:cuhb:a:d:";
$lopt = array(
	"branch:",
	"update",
	"check",
	"stability:",
	"arch:",
	"crt:",
	"help",
	"deps:",
);

$cmd = NULL;
$stability = NULL;
$arch = NULL;
$branch = NULL;
$crt = NULL;

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
				$branch = $val;
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
				Config::setCurrentArchName($val);
				break;

			case "d":
			case "deps":
				Config::setDepsLocalPath($val);
				break;

			case "c":
			case "check":
				$cmd = "check";
				break;
			case "u":
			case "update":
				$cmd = "update";
				break;

			case "crt":
				Config::setCurrentCrtName($val);
				break;
		}
	}

	if (NULL === $cmd) {
		usage();
	}

	if (!Config::getDepsLocalPath()) {
		if (file_exists("../deps")) {
			Config::setDepsLocalPath(realpath("../deps"));
		} else {
			usage(3);
		}
	}

	if ($branch) {
		Config::setCurrentBranchName($branch);
	}
	if (!Config::getCurrentBranchName()) {
		/* Try to figure out the branch. For now it only works if CWD is in php-src. */
		$fl = "main/php_version.h";
		if (file_exists($fl)) {
			$s = file_get_contents($fl);
			$major = $minor = NULL;

			if (preg_match(",PHP_MAJOR_VERSION (\d+),", $s, $m)) {
				$major = $m[1];
			}
			if (preg_match(",PHP_MINOR_VERSION (\d+),", $s, $m)) {
				$minor = $m[1];
			}

			if (is_numeric($major) && is_numeric($minor)) {
				Config::setCurrentBranchName("$major.$minor");
			} else {
				usage(3);
			}
		} else {
			usage(3);
		}
	}

	if (NULL === Config::getCurrentArchName()) {
		/* XXX this might be not true for other compilers! */
		passthru("where cl.exe >nul", $status);
		if (!$status) {
			exec("cl.exe /? 2>&1", $a, $status);
			if (!$status) {
				if (preg_match(",x64,", $a[0])) {
					Config::setCurrentArchName("x64");
				} else {
					Config::setCurrentArchName("x86");
				}
			} else {
				usage(3);
			}
		} else {
			usage(3);
		}
		$arch = Config::getCurrentArchName();
	}

	if (NULL === Config::getCurrentCrtName()) {
		Config::setCurrentCrtName(Config::getCurrentBranchData()["crt"]);
	}

	if (NULL === $stability) {
		if ("master" == Config::getCurrentBranchName()) {
			$stability = "staging";
		} else {
			$stability = "stable";
		}
	}

	$branch_data = Config::getCurrentBranchData();
	echo "\nConfiguration: " . Config::getCurrentBranchName() . "-$branch_data[crt]-$arch-$stability\n\n";

	/* Let the dep manager to run the command. */
	$dm = new SDK\Dependency\Manager(Config::getDepsLocalPath(), $stability, $arch);
	switch ($cmd) {
		default:
			throw new Exception("Unknown command '$cmd'");
		break;
		case "check":
			$ret = $dm->updatesAvailable();
			if ($ret) {
				msg("Updates are available.");
			} else {
				msg("No updates are available.");
			}
			break;
		case "update":
			$dm->performUpdate($msg);
			msg($msg);
			break;
	}

} catch (Throwable $e) {
//	var_dump($e);
	echo "\nError: ", $e->getMessage(), PHP_EOL;
	exit(3);
}

function usage(int $code = -1)
{
	echo "PHP SDK dependency handling tool.", PHP_EOL;
	echo "Usage: ", PHP_EOL;
	echo "  -a --arch      Architecture, x86 or x64.", PHP_EOL;
	echo "  -b --branch    Use dependencies for a specific branch. If omited, CWD is used to guess.", PHP_EOL;
	echo "  -c --check     Check for dependency updates.", PHP_EOL;
	echo "     --crt       Usually not needed! Pass a string like vc11, vc14, etc. and hope for best.", PHP_EOL;
	echo "  -d --deps      Path to the dependencies directory. If omited, CWD is used to guess.", PHP_EOL;
	echo "  -h --help      Show help message.", PHP_EOL;
	echo "  -s --stability One of stable or staging.", PHP_EOL;
	echo "  -u --update    Update dependencies. If deps directory already exists, backup copy is created automatically.", PHP_EOL;
	echo "", PHP_EOL;

	$code = -1 == $code ? 0 : $code;
	exit($code);
}

function msg($s) {
	echo $s, PHP_EOL;
}

exit(0);

