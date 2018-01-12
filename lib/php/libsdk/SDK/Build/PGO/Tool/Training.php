<?php

namespace SDK\Build\PGO\Tool;

use SDK\{Config as SDKConfig, Exception};
use SDK\Build\PGO\Config as PGOConfig;
use SDK\Build\PGO\Interfaces\{TrainingCase, Server, Server\DB, PHP};

class Training
{
	protected $conf;
	protected $t_case;

	public function __construct(PGOConfig $conf, TrainingCase $t_case)
	{
		$this->conf = $conf;
		$this->t_case = $t_case;
		
		if (!in_array($this->t_case->getType(), array("web", "cli"))) {
			throw new Exception("Unknown training type '$type'.");
		}
	}

	public function getCase() : TrainingCase
	{
		return $this->t_case;
	}

	public function runWeb(int $max_runs, ?array &$stat = array()) : void
	{
		$url_list_fn = $this->t_case->getJobFilename();

		if (!file_exists($url_list_fn)) {
			printf("\033[31m WARNING: Job file '$url_list_fn' not found!\033[0m\n");
		}

		$a = file($url_list_fn, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

		$stat = array("http_code" => array(), "not_ok" => array());

		for ($k = 0; $k < $max_runs; $k++) {
			echo ".";

			$ch = array();

			$mh = curl_multi_init();

			foreach ($a as $i => $u) {

				$ch[$i] = curl_init($u);

				curl_setopt($ch[$i], CURLOPT_CONNECTTIMEOUT_MS, 500000);
				curl_setopt($ch[$i], CURLOPT_TIMEOUT_MS, 500000);
				curl_setopt($ch[$i], CURLOPT_RETURNTRANSFER, true);
				/* ??? */
				/*curl_setopt($ch[$i], CURLOPT_FOLLOWLOCATION, true);*/

				curl_multi_add_handle($mh, $ch[$i]);
			}

			$active = NULL;

			do {
				$mrc = curl_multi_exec($mh, $active);
			} while ($mrc == CURLM_CALL_MULTI_PERFORM);

			while ($active && $mrc == CURLM_OK) {
				if (curl_multi_select($mh, 42.0) != -1) {
					do {
						$mrc = curl_multi_exec($mh, $active);
					} while ($mrc == CURLM_CALL_MULTI_PERFORM);
				}
			}

			foreach ($ch as $h) {
				curl_multi_remove_handle($mh, $h);

				/* Gather some stats */
				$info = curl_getinfo($h);
				$http_code = $info["http_code"];

				if (isset($stat["http_code"][$http_code])) {
					$stat["http_code"][$http_code]++;
				} else {
					$stat["http_code"][$http_code] = 1;
				}

				if (!$this->t_case->httpStatusOk((int)$http_code)) {
					$stat["not_ok"][] = $info;

					//echo curl_multi_getcontent($h) ;
				}

				curl_close($h);
			}

			curl_multi_close($mh);

		}

		echo "\n";

	}

	/* TODO Extend with number runs. */
	public function run(int $max_runs = 1, ?array &$stat = array()) : void
	{
		$type = $this->t_case->getType();
		switch ($type)
		{
			case "web":
				$this->runWeb($max_runs, $stat);
				break;

			case "cli":
			default:
				throw new Exception("Unknown training type '$type'.");
		}
	}
}
