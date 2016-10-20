<?php

namespace SDK;

use SDK\Dependency\Fetcher;
use SDK\Dependency\Cache;
use SDK\Exception;

class Config
{
	/* Config variables. */
	protected static $depsHost = 'windows.php.net';
	protected static $depsPort = 80;
	protected static $depsBaseUri = "/downloads/php-sdk/deps";

	/* protected static $sdkNugetFeedUrl = "http://127.0.0.1/sdk/nuget"; */

	protected static $knownBranches = array ();

	/* Helper props and methods. */
	protected static $currentBranchName = NULL;
	protected static $currentArchName = NULL;
	protected static $currentCrtName = NULL;
	protected static $depsLocalPath = NULL;

	public static function getDepsHost() : string
	{
		return self::$depsHost;
	}
	public static function getDepsPort() : string
	{
		return self::$depsPort;
	}
	public static function getDepsBaseUri() : string
	{
		return self::$depsBaseUri;
	}

	public static function setCurrentArchName(string $arch)
	{
		self::$currentArchName = $arch;
	}	

	public static function getCurrentArchName() 
	{
		return self::$currentArchName;
	}	

	public static function setCurrentCrtName(string $crt)
	{
		self::$currentCrtName = $crt;
	}	

	public static function getCurrentCrtName()
	{
		return self::$currentCrtName;
	}	

	public static function getKnownBranches() : array
	{
		if (empty(self::$knownBranches)) {
			$cache_file = "supported_branches.txt";
			$cache = new Cache(self::getDepsLocalPath());
			$fetcher = new Fetcher(self::$depsHost, self::$depsPort);

			$tmp = $fetcher->getByUri(self::$depsBaseUri . "/series/supported_branches.txt");
			if (false !== $tmp) {
				$cache->cachecontent($cache_file, $tmp, true);
			} else {
				$data = $cache->getCachedContent($cache_file, true);
			}

			$data = json_decode($tmp, true);
			if (!is_array($data)) {
				throw new Exception("Failed to fetch supported branches");
			}
			self::$knownBranches = $data;
		}

		return self::$knownBranches;
	}

	public static function setCurrentBranchName(string $name)
	{
		if (!array_key_exists($name, self::getKnownBranches())) {
		//	throw new Exception("Unsupported branch '$name'");
		}

		self::$currentBranchName = $name;
	}

	public static function getCurrentBranchName()
	{
		return self::$currentBranchName;
	}

	public static function getCurrentBranchData() : array
	{
		if (array_key_exists(self::$currentBranchName, self::getKnownBranches())) {
			return self::getKnownBranches()[self::$currentBranchName];
		}

		$arch = Config::getCurrentArchName();
		$crt = Config::getCurrentCrtName();
		if (NULL !== $arch && NULL !== $crt) {
			$ret = array();
			$ret["crt"] = $crt;

			return $ret;
		}

		throw new Exception("Not enough data to handle branch '" . self::$currentBranchName . "'");
	}

	public static function getSdkNugetFeedUrl() : string
	{
		return self::$sdkNugetFeedUrl;
	}

	public static function getSdkPath()
	{
		$path = getenv("PHP_SDK_PATH");

		if (!$path) {
			throw new Exception("PHP_SDK_PATH isn't set!");
		}

		$path = realpath($path);
		if (!file_exists($path)) {
			throw new Exception("The path '$path' is non existent.");
		}

		return $path;
	}

	public static function getSdkVersion() : string
	{
		$path = self::getSdkPath() . DIRECTORY_SEPARATOR . "VERSION";

		if (!file_exists($path)) {
			throw new Exception("Couldn't find the SDK version file.");
		}

		return file_get_contents($path);
	}

	public static function getDepsLocalPath()
	{
		return self::$depsLocalPath;
	}

	public static function setDepsLocalPath(string $path)
	{
		self::$depsLocalPath = $path;
	}

	public static function getCacheDir() : string
	{
		$path = self::getSdkPath() . DIRECTORY_SEPARATOR . ".cache";

		if (!file_exists($path)) {
			if (!mkdir($path)) {
				throw new Exception("Failed to create '$path'");
			}
		}

		return $path;
	}

	public static function getTmpDir() : string
	{
		$path = self::getSdkPath() . DIRECTORY_SEPARATOR . ".tmp";

		if (!file_exists($path)) {
			if (!mkdir($path)) {
				throw new Exception("Failed to create '$path'");
			}
		}

		return $path;
	}
}

