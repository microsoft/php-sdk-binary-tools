<?php

namespace SDK\Build\Dependency;

use SDK\Config;
use SDK\Cache;
use SDK\Exception;

class Series
{
	protected $fetcher;
	protected $stability;
	protected $arch;
	protected $rawData;
	protected $cache;

	public function __construct(string $stability, string $arch, Cache $cache, Fetcher $fetcher = NULL)
	{/*{{{*/
		$this->fetcher = $fetcher;
		$this->stability = $stability;
		$this->arch = $arch;
		$this->cache = $cache;
	}/*}}}*/

	public function getFetcher() : Fetcher
	{/*{{{*/
		return $this->fetcher;
	}/*}}}*/

	public function setFetcher(Fetcher $fetcher) : void
	{/*{{{*/
		$this->fetcher = $fetcher;
	}/*}}}*/

	public function getArch() : string
	{/*{{{*/
		return $this->arch;
	}/*}}}*/

	public function setArch(string $arch) : void
	{/*{{{*/
		$this->arch = $arch;
	}/*}}}*/

	public function getName() : string
	{/*{{{*/
		$base = Config::getDepsBaseUri();
		$branch_data = Config::getCurrentBranchData();

		$file = "packages-" . Config::getCurrentBranchName() . "-{$branch_data['crt']}-{$this->arch}-{$this->stability}.txt";

		return $file;
	}/*}}}*/

	protected function getUri() : string 
	{/*{{{*/
		$base = Config::getDepsBaseUri();
		$file = $this->getName();

		return "$base/series/$file";
	}/*}}}*/

	public function getData(bool $raw = false, bool $cache = true)
	{/*{{{*/
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
	}/*}}}*/

	public function getSavePath() : string
	{/*{{{*/
		return Config::getCacheDir() . DIRECTORY_SEPARATOR . $this->getname();
	}/*}}}*/

	public function updatesAvailable() : bool
	{/*{{{*/
		$series_data = $this->getData(true);
		$series_file = $this->getSavePath();
		
		return $this->cache->cachedContentDiffers($series_file, $series_data);
	}/*}}}*/

	public function cache(string $path = NULL) : void
	{/*{{{*/
		if (!$path) {
			$path = $this->getSavePath();
		}

		$this->cache->cacheContent($path, $this->getData(true));
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
