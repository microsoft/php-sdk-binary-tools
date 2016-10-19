<?php

namespace SDK\Dependency;

use SDK\Config;
use SDK\Exception;

class Series
{
	protected $fetcher;
	protected $stability;
	protected $arch;
	protected $rawData;

	public function __construct(string $stability, string $arch, Fetcher $fetcher = NULL)
	{
		$this->fetcher = $fetcher;
		$this->stability = $stability;
		$this->arch = $arch;
	}

	public function getFetcher() : Fetcher
	{
		return $this->fetcher;
	}

	public function setFetcher(Fetcher $fetcher)
	{
		$this->fetcher = $fetcher;
	}

	public function getArch() : string
	{
		return $this->arch;
	}

	public function setArch(string $arch)
	{
		$this->arch = $arch;
	}

	public function getName() : string
	{
		$base = Config::getDepsBaseUri();
		$branch_data = Config::getCurrentBranchData();

		$file = "packages-" . Config::getCurrentBranchName() . "-{$branch_data['crt']}-{$this->arch}-{$this->stability}.txt";

		return $file;
	}

	protected function getUri() : string
	{
		$base = Config::getDepsBaseUri();
		$file = $this->getName();

		return "$base/series/$file";
	}

	public function getData(bool $raw = false, bool $cache = true)
	{
		if ($cache && $this->rawData) {
			$ret = $this->rawData;
		} else {
			if (!$this->fetcher) {
				throw new Exception("Fetcher is not set");
			}

			$ret = $this->fetcher->getByUri($this->getUri());
		}

		if (!$raw) {
			$ret = explode(" ", preg_replace(",[\r\n ]+,", " ", trim($ret)));
		}

		return $ret;
	}

	public function getCachedPath()
	{
		return Config::getCacheDir() . DIRECTORY_SEPARATOR . $this->getname();
	}

	public function cache(string $path = NULL)
	{
		if (!$path) {
			$path = $this->getCachedPath();
		}

		if (false === file_put_contents($path, $this->getData(true))) {
			throw new Exception("Failed to write series to '$path'");
		}
	}
}

