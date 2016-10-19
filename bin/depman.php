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

	if (NULL === $cmd) {
		usage();
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
				usage();
			}
		} else {
			usage();
		}
	}

	if (NULL === $arch) {
		/* XXX this might be not true for other compilers! */
		passthru("where cl.exe >nul", $status);
		if (!$status) {
			exec("cl.exe /? 2>&1", $a, $status);
			if (!$status) {
				if (preg_match(",x64,", $a[0])) {
					$arch = "x64";
				} else {
					$arch = "x86";
				}
			} else {
				usage();
			}
		} else {
			usage();
		}
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
	$dm = new SDK\Dependency\Manager($stability, $arch);
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
			$dm->performUpdate();
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

function msg($s) {
	echo $s, PHP_EOL;
}

exit(0);

