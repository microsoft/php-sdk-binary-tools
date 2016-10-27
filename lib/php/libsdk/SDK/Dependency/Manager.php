<?php

namespace SDK\Dependency;

use SDK\Config;
use SDK\Cache;
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
		$this->cache = new Cache($path);

		$host = Config::getDepsHost();
		$port = Config::getDepsPort();
		$fetcher = new Fetcher($host, $port, $this->arch, $this->stability);
		$series = new Series($this->stability, $this->arch, $this->cache, NULL);
		$fetcher->setSeries($series);
		$series->setFetcher($fetcher);

		$this->fetcher = $fetcher;
		$this->series = $series;
	}

	protected function getTmpSeriesPath()
	{
		return Config::getTmpDir() . DIRECTORY_SEPARATOR . $this->series->getname();
	}

	public function updatesAvailable() : bool
	{
		return $this->series->updatesAvailable();
	}

	/* TODO and implement --force. */
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
			$pkg->cleanup();

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

		/* Clear, it is an extra handling. So far it's the only case, doing it this way. And, we have
			no package definitions ATM to handle it otherwise. */
		$extra = $tmp_dir_deps . DIRECTORY_SEPARATOR . "openssl.cnf";
		if (file_exists($extra)) {
			$tdir = $tmp_dir_deps . DIRECTORY_SEPARATOR . "template" . DIRECTORY_SEPARATOR . "ssl";

			mkdir($tdir, 0755, true);
			rename($extra, $tdir . DIRECTORY_SEPARATOR . "openssl.cnf");
		}

		if (!rename($tmp_dir_deps, $this->path)) {
			throw new Exception("Unable to rename '$tmp_dir_deps' to '{$this->path}'");
		}

		rmdir($tmp_dir_packs);
		rmdir($tmp_dir);

		$this->series->cache();

		/* save new series file, move the updated deps and backup the old ones, cleanup.*/
		$msg = "Updates performed successfully. ";
		$msg .= "Updated dependencies was saved to '{$this->path}'. ";
		if (isset($new_path)) {
			$msg .= "Old dependencies dir is moved to '$new_path'.";
		}
	}
}

