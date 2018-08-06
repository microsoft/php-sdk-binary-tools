<?php

namespace SDK\Build\PGO\Tool;

use SDK\{Exception, FileOps};
use SDK\Build\PGO\Config as PGOConfig;


class PackageWorkman
{
	use FileOps;

	protected $conf;

	public function __construct(PGOConfig $conf)
	{
		$this->conf = $conf;
	}

	public function fetch(string $url, string $tgt_fn, bool $force = false) : void
	{
		$cache_fn = $this->conf->getPkgCacheDir() . DIRECTORY_SEPARATOR . basename($tgt_fn);

		if ($force || !file_exists($cache_fn)) {
			echo "Fetching '$url' into '$tgt_fn'\n";
			$this->download($url, $cache_fn);
		}

		if ($force || !file_exists($tgt_fn)) {
			if ($cache_fn != $tgt_fn && !$this->cp($cache_fn, $tgt_fn)) {
				throw new Exception("Failed to copy '$cache_fn' to '$tgt_fn'.");
			}
		}
	}

	/* Only for zips! */
	public function fetchAndUnzip(string $url, string $zip_bn, string $zip_tgt_dn, string $tgt_bn = NULL, bool $force = false) : void
	{
		$cache_fn = $this->conf->getPkgCacheDir() . DIRECTORY_SEPARATOR . $zip_bn;

		if ($force || !file_exists($cache_fn)) {
			$this->fetch($url, $cache_fn, $force);
		}

		$tgt_name = $zip_tgt_dn . ($tgt_bn ? DIRECTORY_SEPARATOR . $tgt_bn : "");
		if ($force || $tgt_bn && !file_exists($tgt_name) || !$tgt_bn /* This means unzip always if no rename. */) {
			echo "Unpacking '$cache_fn' to '$tgt_name'\n";
			try {
				$this->unzip($cache_fn, $zip_tgt_dn, $tgt_bn);
			} catch (\Throwable $e) {
				$this->rm($cache_fn);
				throw $e;
			}
		}
	}
}
