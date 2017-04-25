<?php

include dirname(__FILE__) . "/../lib/php/libsdk/autoload.php";

use SDK\Config;
use SDK\Exception;

$sopt = "s:cuhb:a:d:t:fn";
$lopt = array(
	"branch:",
	"update",
	"check",
	"stability:",
	"arch:",
	"crt:",
	"help",
	"deps:",
	"force",
	"no-backup",
);

$cmd = NULL;
$stability = NULL;
$arch = NULL;
$branch = NULL;
$crt = NULL;
$force = false;
$backup = true;

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
				Config::setCurrentStabilityName($val);
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

			case "t":
			case "crt":
				Config::setCurrentCrtName($val);
				break;

			case "f":
			case "force":
				$force = true;
				break;

			case "n":
			case "no-backup":
				$backup = false;
				break;
		}
	}

	if (NULL === $cmd) {
		usage();
	}

	if (!Config::getDepsLocalPath()) {
		if (file_exists("../deps")) {
			Config::setDepsLocalPath(realpath("../deps"));
		} else if (file_exists("main/php_version.h")) {
			/* Deps dir might not exist. */
			Config::setDepsLocalPath(realpath("..") . DIRECTORY_SEPARATOR . "deps");
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
		$all_branches = Config::getKnownBranches();

		if (!isset($all_branches[Config::getCurrentBranchName()])) {
			throw new Exception("Couldn't find any configuration for branch '" . Config::getCurrentBranchName() . "'");
		}

		$branch = $all_branches[Config::getCurrentBranchName()];
		if (count($branch) > 1) {
			throw new Exception("Multiple CRTs are available for this branch, please choose one from " . implode(",", array_keys($branch)));
		} else {
			Config::setCurrentCrtName(array_keys($branch)[0]);
		}
	}
	/* The current CRT needs to match the config one. */
	$active_crt = getenv("PHP_SDK_VC");
	if (Config::getCurrentCrtName() != $active_crt && !$force) {
		throw new Exception("Active CRT '$active_crt' differs from the branch CRT '" . Config::getCurrentCrtName() . "'.");
	}

	if (NULL === Config::getCurrentStabilityName()) {
		if ("master" == Config::getCurrentBranchName()) {
			Config::setCurrentStabilityName("staging");
		} else {
			Config::setCurrentStabilityName("stable");
		}
	}

	$branch_data = Config::getCurrentBranchData();
	echo "\nConfiguration: " . Config::getCurrentBranchName() . "-$branch_data[crt]-$branch_data[arch]-$branch_data[stability]\n\n";

	/* Let the dep manager to run the command. */
	$dm = new SDK\Dependency\Manager(Config::getDepsLocalPath(), $branch_data["stability"], $branch_data["arch"]);
	switch ($cmd) {
		default:
			throw new Exception("Unknown command '$cmd'");
		break;
		case "check":
			$ret = $dm->updatesAvailable();
			if ($ret) {
				msg("Updates are available.", 7);
			} else {
				msg("No updates are available.");
			}
			break;
		case "update":
			if ($force) {
				print "Replacing the current deps by the force option.\n\n";
			}
			$dm->performUpdate($msg, $force, $backup);
			msg($msg);
			break;
	}

} catch (Throwable $e) {
	//var_dump($e);
	//echo "\nError: ", $e->getMessage(), PHP_EOL;
	throw $e;
	exit(3);
}

function usage(int $code = -1)
{
	echo "PHP SDK dependency handling tool.", PHP_EOL;
	echo "Usage: ", PHP_EOL, PHP_EOL;
	echo "Configuration:", PHP_EOL;
	echo "  -b --branch    Branch name, eg. 7.0, 7.1, etc. If omited, CWD is used to guess.", PHP_EOL;
	echo "  -a --arch      Architecture, x86 or x64. If omited, cl.exe is used to guess.", PHP_EOL;
	echo "  -t --crt       CRT, marked by the corresponding VC++ version, eg. vc11, vc14, etc.", PHP_EOL;
	echo "  -s --stability One of stable or staging.", PHP_EOL, PHP_EOL;
	echo "Commands:", PHP_EOL;
	echo "  -c --check     Check for dependency updates. If updates are available, the exit code is set to 7.", PHP_EOL;
	echo "  -u --update    Update dependencies. If deps directory already exists, backup copy is created automatically.", PHP_EOL, PHP_EOL;
	echo "Misc:", PHP_EOL;
	echo "  -d --deps      Path to the dependencies directory. If omited, CWD is used to guess.", PHP_EOL;
	echo "  -f --force     Force the operation even if there are no upgrades available.", PHP_EOL;
	echo "  -n --no-backup Replace the current dependencies without creating backup.", PHP_EOL;
	echo "  -h --help      Show help message.", PHP_EOL, PHP_EOL;
	echo "Example: ", PHP_EOL;
	echo "  phpsdk_deps -c -b master", PHP_EOL;
	echo "  phpsdk_deps -u -b 7.0 -a x86 -d c:\\path\\to\\deps\\dir", PHP_EOL, PHP_EOL;

	$code = -1 == $code ? 0 : $code;
	exit($code);
}

function msg(string $s, int $code = 0) {
	echo $s, PHP_EOL;
	exit($code);
}

exit(0);

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 * vim600: sw=4 ts=4 fdm=marker
 * vim<600: sw=4 ts=4
 */
