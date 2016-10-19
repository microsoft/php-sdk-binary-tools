<?php

namespace SDK\Dependency;

use SDK\Config;
use SDK\Exception;

class Manager
{
	protected $stability;
	protected $arch;

	public function __construct(string $stability, string $arch)
	{
		$this->stability = $stability;
		$this->arch = $arch;

		$host = Config::getDepsHost();
		$port = Config::getDepsPort();
		$fetcher = new Fetcher($this->stability, $this->arch, $host, $port, NULL);
		$series = new Series($this->stability, $this->arch, NULL);
		$fetcher->setSeries($series);
		$series->setFetcher($fetcher);

		$this->fetcher = $fetcher;
		$this->series = $series;
	}

	public function updatesAvailable() : bool
	{
		$series = $this->series;

		$series_file_base = $series->getName();
		$series_data = $series->getData(true);

		$tmp = Config::getTmpDir() . DIRECTORY_SEPARATOR . $series_file_base;
		$cached_series_file = Config::getCacheDir() . DIRECTORY_SEPARATOR . $series_file_base;

		if (!file_exists($cached_series_file)) {
			return true;
		}

		$old_sum = md5_file($cached_series_file);
		$new_sum = md5($series_data);

		return $old_sum != $new_sum;
	}


	public function performUpdate()
	{

	}
}

