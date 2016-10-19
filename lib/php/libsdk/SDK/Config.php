<?php

namespace SDK;

use SDK\Exception;

class Config
{
	/* Config variables. */
	protected static $depsHost = 'windows.php.net';
	protected static $depsPort = 80;
	protected static $depsBaseUri = "/downloads/php-sdk/deps";

	/* protected static $sdkNugetFeedUrl = "http://127.0.0.1/sdk/nuget"; */

	protected static $supportedBranches = array (
		'master' => array(
			"crt" => "vc14",
		),
		'7.1' => array(
			"crt" => "vc14",
		),
		'7.0' => array(
			"crt" => "vc14",
		),
		/*'5.6' => array(
			"crt" => "vc11",
		),*/
	);

	/* Helper props and methods. */
	protected static $currentBranchName = NULL;

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

	public static function getSupportedBranches() : array
	{
		return self::$supportedBranches;
	}

	public static function setCurrentBranchName(string $name)
	{
		if (!array_key_exists($name, self::$supportedBranches)) {
			throw new Exception("Unsupported branch '$name'");
		}

		self::$currentBranchName = $name;
	}

	public static function getCurrentBranchName()
	{
		return self::$currentBranchName;
	}

	public static function getCurrentBranchData() : array
	{
		return self::$supportedBranches[self::$currentBranchName];
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

