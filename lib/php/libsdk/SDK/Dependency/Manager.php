<?php

namespace SDK\Dependency;

use SDK\Config;
use SDK\Exception;

class Manager
{
	protected $stability;
	protected $arch;
	protected $path;
	protected $series;
	protected $fetcher;

	public function __construct(string $path, string $stability, string $arch)
	{
		$this->stability = $stability;
		$this->arch = $arch;
		$this->path = $path;

		$host = Config::getDepsHost();
		$port = Config::getDepsPort();
		$fetcher = new Fetcher($host, $port, $this->arch, $this->stability);
		$series = new Series($this->stability, $this->arch, NULL);
		$fetcher->setSeries($series);
		$series->setFetcher($fetcher);

		$this->fetcher = $fetcher;
		$this->series = $series;
	}

	protected function getTmpSeriesPath()
	{
		return Config::getTmpDir() . DIRECTORY_SEPARATOR . $this->series->getname();
	}

	protected function getCachedSeriesPath()
	{
		return $this->series->getCachedPath();
	}

	public function updatesAvailable() : bool
	{
		$series_data = $this->series->getData(true);
		$cached_series_file = $this->getCachedSeriesPath();

		if (!file_exists($cached_series_file)) {
			return true;
		}

		$old_sum = md5_file($cached_series_file);
		$new_sum = md5($series_data);

		return $old_sum != $new_sum;
	}


	/* TODO check if there are actually some updates available, and implement --force. */
	public function performUpdate(string &$msg = NULL)
	{
		if (!$this->updatesAvailable()) {
			$msg = "No updates are available";
			return;
		}

		$series_data = $this->series->getData();

		$tmp_dir = Config::getTmpDir() . DIRECTORY_SEPARATOR . md5(uniqid());
		$tmp_dir_packs = $tmp_dir . DIRECTORY_SEPARATOR . "packs";
		$tmp_dir_deps = $tmp_dir . DIRECTORY_SEPARATOR . "deps";
		mkdir($tmp_dir);
		mkdir($tmp_dir_packs);
		mkdir($tmp_dir_deps);

		foreach ($series_data as $item) {
			echo "Processing package $item", PHP_EOL;
			$pkg = new Package($item, $this->series, $this->fetcher);

			$pkg->retrieve($tmp_dir_packs);
			$pkg->unpack($tmp_dir_deps);

			unset($pkg);
		}

		if (file_exists($this->path)) {
			$suffix = date("YmdHi");
			$new_path = "{$this->path}.$suffix";
			if (!rename($this->path, $new_path)) {
				throw new Exception("Unable to rename '{$this->path}' to '$new_path'");
			}
		} else {
			$up = dirname($this->path);
			if (!file_exists($up)) {
				if (!mkdir($up, 0755, true)) {
					throw new Exception("Unable to create '{$this->path}'");
				}
			}
		}

		if (!rename($tmp_dir_deps, $this->path)) {
			throw new Exception("Unable to rename '$tmp_dir_deps' to '{$this->path}'");
		}

		$this->series->cache();

		/* TODO cleanup tmp files. */

		/* save new series file, move the updated deps and backup the old ones, cleanup.*/
		$msg = "Updates performed successfully";
	}
}

