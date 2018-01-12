<?php

namespace SDK\Build\PGO\Abstracts;

use SDK\Build\PGO\Config as PGOConfig;
use SDK\{Config as SDKConfig, Exception, FileOps};
use SDK\Build\PGO\Tool;

class TrainingCase
{
	use FileOps;

	const TYPE_WEB = "web";
	const TYPE_CLI = "cli";

	protected $stat = array();

	public function getType() : string
	{
		$type = $this->conf->getSectionItem($this->getName(), "type");

		if (!$type) {
			$type = "web";
		}

		return $type;
	}

	public function run() : void
	{
		$training = new Tool\Training($this->conf, $this);
		$pgo = new Tool\PGO($this->conf, $this->php);

		echo "Running " . $this->getName() . " training.\n";

		$max_runs = $this->max_runs ?? 1;
		$max_runs = (int)$max_runs > 0 ? $max_runs : 1;
		$training->run($max_runs, $stat);

		if ($this->getType() == "web") {
			echo "HTTP responses:\n";
			foreach ($stat["http_code"] as $code => $num) {
				printf("    %d received %d times\n", $code, $num);
			}
			if (count($stat["not_ok"]) > 0) {
				foreach($stat["not_ok"] as $st) {
					echo "Code: $st[http_code], URL: $st[url]", ($st["redirect_url"] ? ", Redirected to: $st[redirect_url]" : ""), "\n";
				}
				printf("\033[31m WARNING: Not all HTTP responses have indicated success, the PGO data might be unsuitable!\033[0m\n");
			}
		}

		echo $this->getName() . " training complete.\n";

		echo "Dumping PGO data for " . $this->getName() . ".\n";
		$pgo->dump();
		echo "Finished dumping training data for " . $this->getName() . ".\n";
	}

	public function getHttpPort() : string
	{
		$port = $this->conf->getSectionItem($this->getName(), "http_port");
		if (!$port) {
			$port = $this->conf->getNextPort();
			$this->conf->setSectionItem($this->getName(), "http_port", $port);
		}
		
		return $port;
	}

	public function getHttpHost() : string
	{
		$host = $this->conf->getSectionItem($this->getName(), "http_host");
		if (!$host) {
			$srv = $this->conf->getSrv(
				$this->conf->getSectionItem($this->getName(), "srv_http")
			);
			if ($srv) {
				$host = $this->conf->getSectionItem($srv->getName(), "host");
				$this->conf->setSectionItem($this->getName(), "http_host", $host);
			}
		}
		
		return $host;
	}

	protected function getDbConf(string $item) : string
	{
		$val = $this->conf->getSectionItem($this->getName(), "db_$item");
		if (!$val) {
			$srv = $this->conf->getSrv(
				$this->conf->getSectionItem($this->getName(), "srv_db")
			);
			if ($srv) {
				$val = $this->conf->getSectionItem($srv->getName(), $item);
				$this->conf->setSectionItem($this->getName(), "db_$item", $val);
			}
		}
		
		return $val;
	}

	public function getDbPass() : string
	{
		return $this->getDbConf("pass");
	}

	public function getDbUser() : string
	{
		return $this->getDbConf("user");
	}

	public function getDbHost() : string
	{
		return $this->getDbConf("host");
	}

	public function getDbPort() : string
	{
		return $this->getDbConf("port");
	}

	public function httpStatusOk(int $status) : bool
	{
		$ok = array();

		$ok = array_merge($ok, range(200, 206));
		$ok = array_merge($ok, range(300, 307));

		return in_array($status, $ok);
	}

	public function probeUrl(string $url) : bool
	{
		$ret = false;
		$c = curl_init($url);

		curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
		curl_exec($c);

		if (!curl_errno($c)) {
			$st = curl_getinfo($c, CURLINFO_HTTP_CODE);
			$ret = $this->httpStatusOk((int)$st);
		}

		curl_close($c);

		return $ret;
	}
}

