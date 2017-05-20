<?php

namespace SDK\Build\Dependency;

use SDK\Config;
use SDK\Exception;

class Fetcher
{
	protected $host;
	protected $port;
	protected $stability;
	protected $arch;
	protected $series;


	public function __construct(string $host, int $port, string $arch = NULL, string $stability = NULL, Series $series = NULL)
	{/*{{{*/
		$this->stability = $stability;
		$this->arch = $arch;
		$this->host = $host;
		$this->port = $port;
	}/*}}}*/

	public function getSeries() : Series
	{/*{{{*/
		return $this->series;
	}/*}}}*/

	public function setSeries(Series $series)
	{/*{{{*/
		$this->series = $series;
	}/*}}}*/

	/* TODO more robust implementation. */
	/* TODO implement indicator. */
	public function getByUri($uri) : string
	{/*{{{*/
		$url = "http://{$this->host}:{$this->port}$uri";
		$s = file_get_contents($url);

		if (false === $s) {
			throw new Exception("failed to fetch $url");
		}

		return $s;
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
