<?php

namespace SDK;

use SDK\Build\Dependency\Fetcher;

class Config
{
	/* Config variables. */
	protected static $depsHost = 'windows.php.net';
	protected static $depsPort = 443;
	protected static $depsUriScheme = "https";
	protected static $depsBaseUri = "/downloads/php-sdk/deps";

	/* protected static $sdkNugetFeedUrl = "http://127.0.0.1/sdk/nuget"; */

	protected static $knownBranches = array ();

	/* Helper props and methods. */
	protected static $currentBranchName = NULL;
	protected static $currentArchName = NULL;
	protected static $currentCrtName = NULL;
	protected static $currentStabilityName = NULL;
	protected static $depsLocalPath = NULL;

	public static function getDepsHost() : string
	{/*{{{*/
		return self::$depsHost;
	}/*}}}*/

	public static function getDepsPort() : string
	{/*{{{*/
		return self::$depsPort;
	}/*}}}*/

	public static function getDepsUriScheme() : string
	{/*{{{*/
		return self::$depsUriScheme;
	}/*}}}*/

	public static function getDepsBaseUri() : string
	{/*{{{*/
		return self::$depsBaseUri;
	}/*}}}*/

	public static function setCurrentArchName(string $arch) : void
	{/*{{{*/
		$arch = strtolower($arch);

		if ("x64" != $arch && "x86" != $arch) {
			throw new Exception("Unknown arch keyword, either x86 or x64 is accepted");
		}

		self::$currentArchName = $arch;
	}	/*}}}*/

	public static function getCurrentArchName() : string
	{/*{{{*/
		if (NULL === self::$currentArchName) {
			if (FALSE !== ($env = getenv('PHP_SDK_ARCH'))) {
				self::setCurrentArchName($env);
			} else {
				/* XXX this might be not true for other compilers! */
				passthru("where cl.exe >nul", $status);
				if ($status) {
					throw new Exception("Couldn't execute cl.exe.");
				}

				exec("cl.exe /? 2>&1", $out);

				if (preg_match(",x64,", $out[0])) {
					self::setCurrentArchName("x64");
				} elseif (preg_match(",x86,", $out[0])) {
					self::setCurrentArchName("x86");
				} else {
					throw new Exception("Couldn't determine Arch.");
				}
			}
		}

		return self::$currentArchName;
	}	/*}}}*/

	public static function setCurrentCrtName(string $crt) : void
	{/*{{{*/
		self::$currentCrtName = $crt;
	}	/*}}}*/

	public static function getCurrentCrtName() : ?string
	{/*{{{*/
		if (NULL === self::$currentCrtName) {
			if (FALSE !== ($env = getenv('PHP_SDK_VS'))) {
				self::setCurrentCrtName($env);
			} else {
				$all_branches = Config::getKnownBranches();

				if (!isset($all_branches[Config::getCurrentBranchName()])) {
					throw new Exception("Couldn't find any configuration for branch '" . Config::getCurrentBranchName() . "'");
				}

				$branch = $all_branches[Config::getCurrentBranchName()];
				if (count($branch) > 1) {
					throw new Exception("Multiple CRTs are available for this branch, please choose one from " . implode(",", array_keys($branch)));
				}

				self::setCurrentCrtName(array_keys($branch)[0]);
			}
		}

		return self::$currentCrtName;
	}	/*}}}*/

	public static function setCurrentStabilityName(string $stability) : void
	{/*{{{*/
		if ("stable" != $stability && "staging" != $stability) {
			throw new Exception("Unknown stability keyword, either stable or staging is accepted");
		}

		self::$currentStabilityName = $stability;
	}	/*}}}*/

	public static function getCurrentStabilityName() : ?string
	{/*{{{*/
		if (NULL === self::$currentStabilityName) {
			if ("master" == Config::getCurrentBranchName()) {
				Config::setCurrentStabilityName("staging");
			} else {
				Config::setCurrentStabilityName("stable");
			}
		}

		return self::$currentStabilityName;
	}	/*}}}*/

	public static function getKnownBranches() : array
	{/*{{{*/
		if (empty(self::$knownBranches)) {
			$cache_file = "known_branches.txt";
			$deps_path = self::getDepsLocalPath();
			if (!$deps_path) {
					throw new Exception("Couldn't determine dependencies path. Please either switch to the PHP source root or use -d option.");
			}
			$cache = new Cache($deps_path);
			$fetcher = new Fetcher(self::$depsHost, self::$depsPort, self::$depsUriScheme);

			$tmp = $fetcher->getByUri(self::$depsBaseUri . "/series/");
			if (false !== $tmp) {
				$data = array();
				if (preg_match_all(",/packages-(.+)-(v[cs]\d+)-(x86|x64)-(stable|staging)\.txt,U", $tmp, $m, PREG_SET_ORDER)) {
					foreach ($m as $b) {
						if (!isset($data[$b[1]])) {
							$data[$b[1]] = array();
						}

						$data[$b[1]][$b[2]][] = array("arch" => $b[3], "stability" => $b[4]);
					}

					$cache->cachecontent($cache_file, json_encode($data, JSON_PRETTY_PRINT), true);
				}
			} else {
				/* It might be ok to use cached branches list, if a fetch failed. */
				$tmp = $cache->getCachedContent($cache_file, true);
				if (NULL == $tmp) {
					throw new Exception("No cached branches list found");
				}
				$data = json_decode($tmp, true);
			}

			if (!is_array($data) || empty($data)) {
				throw new Exception("Failed to fetch supported branches");
			}
			self::$knownBranches = $data;
		}

		return self::$knownBranches;
	}/*}}}*/

	public static function setCurrentBranchName(string $name) : void
	{/*{{{*/
		if (!array_key_exists($name, self::getKnownBranches())) {
			throw new Exception("Unsupported branch '$name'");
		}

		self::$currentBranchName = $name;
	}/*}}}*/

	public static function guessCurrentBranchName() : ?string
	{/*{{{*/
		$branch = NULL;
		$found = false;

		$rmtools_branch = getenv("PHP_RMTOOLS_PHP_BUILD_BRANCH");
		if ("master" == $rmtools_branch) {
			return "master";
		}

		/* Try to figure out the branch. The worky scenarios are
			- CWD is in php-src 
			- phpize is on the path
			FIXME for the dev package, there should be a php-config utility
		 */
		$fl = "main/php_version.h";
		$found = file_exists($fl);

		if (!$found) {
			exec("where phpize", $out, $status);
			if (!$status) {
				$fl = dirname($out[0]) . DIRECTORY_SEPARATOR . "include" . DIRECTORY_SEPARATOR . $fl;
				$found = file_exists($fl);
			}
		}

		if ($found) {
			$s = file_get_contents($fl);
			$major = $minor = NULL;

			if (preg_match(",PHP_MAJOR_VERSION (\d+),", $s, $m)) {
				$major = $m[1];
			}
			if (preg_match(",PHP_MINOR_VERSION (\d+),", $s, $m)) {
				$minor = $m[1];
			}

			if (is_numeric($major) && is_numeric($minor)) {
				$branch = "$major.$minor";
			}

			/* Verify that we use an available branch name. Master has some
				version, but no dedicated series. For master, it rather
				makes sense to use master as branch name. */
			$git = trim(shell_exec("where git.exe"));
			if ($git && is_dir(".git")) {
				$cmd = "\"$git\" branch";

				$ret = trim(shell_exec($cmd));
				if (preg_match_all(",\*\s+master,", $ret) > 0) {	
					$branch = "master";
				}
			}
		}

		return $branch;
	}/*}}}*/

	public static function getCurrentBranchName() : string
	{/*{{{*/
		if (NULL == self::$currentBranchName) {
			$branch = self::guessCurrentBranchName();
			self::setCurrentBranchName($branch);
		}
	
		return self::$currentBranchName;
	}/*}}}*/

	public static function getCurrentBranchData() : array
	{/*{{{*/
		$ret = array();
		$branches = self::getKnownBranches();

		$current_branch_name = self::getCurrentBranchName();
		if (!array_key_exists($current_branch_name, $branches)) {
			throw new Exception("Unknown branch '$current_branch_name'");
		}

		$cur_crt = Config::getCurrentCrtName();
		if (count($branches[$current_branch_name]) > 1) {
			if (NULL === $cur_crt) {
				throw new Exception("More than one CRT is available for branch '$current_branch_name', pass one explicitly.");
			}

			$cur_crt_usable = false;
			foreach (array_keys($branches[$current_branch_name]) as $crt) {
				if ($cur_crt == $crt) {
					$cur_crt_usable = true;
					break;
				}
			}
			if (!$cur_crt_usable) {
				throw new Exception("The passed CRT '$cur_crt' doesn't match any available for branch '$current_branch_name'");
			}
			$data = $branches[$current_branch_name][$cur_crt];
		} else {
			/* Evaluate CRTs, to avoid ambiquity. */
			$crt = key($branches[$current_branch_name]);
			$data = $branches[$current_branch_name][$crt];
			if ($crt != $cur_crt) {
				throw new Exception("The passed CRT '$cur_crt' doesn't match any available for branch '$current_branch_name'");
			}
		}

		$ret["name"] = $current_branch_name;
		$ret["crt"] = $crt;

		/* Last step, filter by arch and stability. */
		foreach ($data as $d) {
			if (self::getCurrentArchName() == $d["arch"]) {
				if (self::getCurrentStabilityName() == $d["stability"]) {
					$ret["arch"] = $d["arch"];
					$ret["stability"] = $d["stability"];
				}
			}
		}

		if (!isset($ret["arch"]) || !$ret["arch"]) {
			throw new Exception("Failed to find config with arch '" . self::getCurrentArchName() . "'");
		}
		if (!isset($ret["stability"]) || !$ret["stability"]) {
			throw new Exception("Failed to find config with stability '" . self::getCurrentStabilityName() . "'");
		}
		if (!isset($ret["crt"]) || !$ret["crt"]) {
			throw new Exception("Failed to find config with arch '" . self::getCurrentArchName() . "'");
		}

		return $ret; 
	}/*}}}*/

	public static function getSdkNugetFeedUrl() : string
	{/*{{{*/
		return self::$sdkNugetFeedUrl;
	}/*}}}*/

	public static function getSdkPath() : string
	{/*{{{*/
		$path = getenv("PHP_SDK_ROOT_PATH");

		if (!$path) {
			throw new Exception("PHP_SDK_ROOT_PATH isn't set!");
		}

		$path = realpath($path);
		if (!file_exists($path)) {
			throw new Exception("The path '$path' is non existent.");
		}

		return $path;
	}/*}}}*/

	public static function getSdkVersion() : string
	{/*{{{*/
		$path = self::getSdkPath() . DIRECTORY_SEPARATOR . "VERSION";

		if (!file_exists($path)) {
			throw new Exception("Couldn't find the SDK version file.");
		}

		return file_get_contents($path);
	}/*}}}*/

	public static function getDepsLocalPath() : ?string
	{/*{{{*/
		if (NULL == self::$depsLocalPath) {
			if (file_exists("Makefile")) {
				$s = file_get_contents("Makefile");

				if (preg_match(",PHP_BUILD=(.+),", $s, $m)) {
					if (isset($m[1])) {
						self::setDepsLocalPath(trim($m[1]));
					}
				}
			}
		}

		if (NULL == self::$depsLocalPath) {
			$tmp = dirname(getcwd()) . DIRECTORY_SEPARATOR . "deps";
			if (is_dir($tmp)) {
				self::setDepsLocalPath($tmp);
			}
		}
		
		if (NULL == self::$depsLocalPath) {
			$tmp = realpath("../deps");
			if (is_dir($tmp)) {
				self::setDepsLocalPath($tmp);
			}
		}

		if (NULL == self::$depsLocalPath) {
			if (file_exists("main/php_version.h")) {
				/* Deps dir might not exist. */
				self::setDepsLocalPath(realpath("..") . DIRECTORY_SEPARATOR . "deps");
			}
		}

		return self::$depsLocalPath;
	}/*}}}*/

	public static function setDepsLocalPath(string $path) : void
	{/*{{{*/
		self::$depsLocalPath = $path;
	}/*}}}*/

	public static function getCacheDir() : string
	{/*{{{*/
		$path = self::getSdkPath() . DIRECTORY_SEPARATOR . ".cache";

		if (!file_exists($path)) {
			if (!mkdir($path)) {
				throw new Exception("Failed to create '$path'");
			}
		}

		return $path;
	}/*}}}*/

	public static function getTmpDir() : string
	{/*{{{*/
		$path = self::getSdkPath() . DIRECTORY_SEPARATOR . ".tmp";

		if (!file_exists($path)) {
			if (!mkdir($path)) {
				throw new Exception("Failed to create '$path'");
			}
		}

		return $path;
	}/*}}}*/

	public static function getSdkUserAgentName() : string
	{/*{{{*/
		return "PHP-SDK-BINARY-TOOLS/" . self::getSdkVersion();
	}/*}}}*/
}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 * vim600: sw=4 ts=4 fdm=marker
 * vim<600: sw=4 ts=4
 */
