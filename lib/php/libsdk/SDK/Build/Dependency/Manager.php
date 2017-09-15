<?php

namespace SDK\Build\Dependency;

use SDK\{Config, Cache, Exception, FileOps, Lock};

class Manager
{
	use FileOps;

	protected $stability;
	protected $arch;
	protected $path;
	protected $series;
	protected $fetcher;

	public function __construct(string $path, string $stability, string $arch)
	{/*{{{*/
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
	}/*}}}*/

	protected function getTmpSeriesPath() : string
	{/*{{{*/
		return Config::getTmpDir() . DIRECTORY_SEPARATOR . $this->series->getname();
	}/*}}}*/

	public function updatesAvailable() : bool
	{/*{{{*/
		return $this->series->updatesAvailable() || !file_exists(Config::getDepsLocalPath());
	}/*}}}*/

	/* FIXME implement rollback */
	public function performUpdate(string &$msg = NULL, bool $force = false, bool $backup = true) : void
	{/*{{{*/
		if (!$force) {
			if (!$this->updatesAvailable()) {
				$msg .= "No updates are available";
				return;
			}

			$lock = new Lock(Config::getDepsLocalPath());
			if (!$lock->locked()) {
				$msg .= "Dependencies was updated by another process.";
				echo "Another process is updating same dependency path. I'm just going to wait for it to finish and then exit.", PHP_EOL;
				$lock->exclusive(true);
				unset($lock);
				return;
			}
		}

		$series_data = $this->series->getData();

		$tmp_dir = $this->md("", true);
		$tmp_dir_packs = $this->md($tmp_dir . DIRECTORY_SEPARATOR . "packs");
		$tmp_dir_deps = $this->md($tmp_dir . DIRECTORY_SEPARATOR . "deps");

		foreach ($series_data as $item) {
			echo "Processing package $item", PHP_EOL;
			$pkg = new Package($item, $this->series, $this->fetcher);

			$pkg->retrieve($tmp_dir_packs);
			$pkg->unpack($tmp_dir_deps);
			$pkg->cleanup();

			unset($pkg);
		}

		/* Clear, it is an extra handling. So far it's the only case, doing it this way. And, we have
			no package definitions ATM to handle it otherwise. */
		$extra = $tmp_dir_deps . DIRECTORY_SEPARATOR . "openssl.cnf";
		if (file_exists($extra)) {
			$tdir = $tmp_dir_deps . DIRECTORY_SEPARATOR . "template" . DIRECTORY_SEPARATOR . "ssl";

			$this->md($tdir);
			$this->mv($extra, $tdir . DIRECTORY_SEPARATOR . "openssl.cnf");
		}

		if (file_exists($this->path)) {
			if ($backup) {
				$suffix = date("YmdHi");
				$new_path = "{$this->path}.$suffix";

				/* This is fine, it's gonna be on the same drive. */
				if (!$this->mv($this->path, $new_path)) {
					if (!$force) {
						unset($lock);
					}
					throw new Exception("Unable to rename '{$this->path}' to '$new_path'");
				}
			} else {
				if (!$this->rm($this->path)) {
					if (!$force) {
						unset($lock);
					}
					throw new Exception("Unable to remove the current dependency dir at '{$this->path}'");
				}
			}
		} else {
			$up = dirname($this->path);
			if (!file_exists($up)) {
				if (!$this->md($up)) {
					if (!$force) {
						unset($lock);
					}
					throw new Exception("Unable to create '{$this->path}'");
				}
			}
		}

		$this->mv($tmp_dir_deps, $this->path);

		$this->rm($tmp_dir_packs);
		$this->rm($tmp_dir);

		$this->series->cache();

		/* save new series file, move the updated deps and backup the old ones, cleanup.*/
		$msg .= "Updates performed successfully. " . PHP_EOL;
		if ($backup) {
			if (isset($new_path)) {
				$msg .= "Old dependencies backed up into '$new_path'.";
			}
		} else {
			$msg .= "No backup was created.";
		}

		if (!$force) {
			unset($lock);
		}
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
