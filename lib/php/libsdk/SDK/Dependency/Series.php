<?php

namespace SDK\Dependency;

use SDK\Config;
use SDK\Exception;

class Series
{
	protected $fetcher;
	protected $stability;
	protected $arch;

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

	public function getData(bool $raw = false)
	{
		if (!$this->fetcher) {
			throw new Exception("Fetcher is not set");
		}

		$ret = $this->fetcher->getByUri($this->getUri());

		if (!$raw) {
			$ret = explode(" ", preg_replace(",[\r\n ]+,", " ", trim($ret)));
		}

		return $ret;
	}
}

