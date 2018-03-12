<?php

namespace SDK\Build\Dependency;

use SDK\{Config, Exception, FileOps};

class Fetcher
{
	use FileOps;

	protected $host;
	protected $port;
	protected $stability;
	protected $arch;
	protected $series;


	public function __construct(string $host, int $port, string $scheme = "https", string $arch = NULL, string $stability = NULL, Series $series = NULL)
	{/*{{{*/
		$this->stability = $stability;
		$this->arch = $arch;
		$this->host = $host;
		$this->port = $port;
		$this->scheme = $scheme;
	}/*}}}*/

	public function getSeries() : Series
	{/*{{{*/
		return $this->series;
	}/*}}}*/

	public function setSeries(Series $series) : void
	{/*{{{*/
		$this->series = $series;
	}/*}}}*/

	/* TODO more robust implementation. */
	/* TODO implement indicator. */
	public function getByUri(string $uri, int $retries = 3) : string
	{/*{{{*/
		$url = "{$this->scheme}://{$this->host}:{$this->port}$uri";
		$ret = false;

retry:
		try {
			$ret = $this->download($url);
		} catch (Exception $e) {
			if ($retries > 0) {
				sleep(1);
				$retries--;
				goto retry;
			}
		}

		return $ret;
	}/*}}}*/

	/*protected function fetch($uri) : string
	{
		$fp = @fsockopen($this->host, $this->port);
		if (!$fp) {
			throw new Exception("Couldn't connect to windows.php.net");
		}

		$hdrs = "GET $uri HTTP/1.0\r\nHost: {$this->host}\r\nConnection: close\r\n\r\n";
		$r = fwrite($fp, $hdrs);
		if (false === $r || $r != strlen($hdrs)) {
			fclose($fp);
			throw new Exception("Request to windows.php.net failed");
		}

		$r = '';
		while (!feof($fp)) {
			$r .= fread($fp, 32768);
		}

		if (preg_match(',HTTP/\d\.\d 200 .*,', $r) < 1) {
			var_dump($r);
			fclose($fp);
			throw new Exception("Invalid response from {$this->host}:{$this->port} while fetching '$uri'");
		}

		fclose($fp);

		$ret = substr($r, strpos($r, "\r\n"));

		return trim($ret);
	}*/

}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 * vim600: sw=4 ts=4 fdm=marker
 * vim<600: sw=4 ts=4
 */
