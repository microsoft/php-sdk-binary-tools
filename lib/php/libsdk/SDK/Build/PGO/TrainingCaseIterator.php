<?php

namespace SDK\Build\PGO;

use SDK\{Config as SDKConfig, Exception, FileOps};
use SDK\Build\PGO\Config as PGOConfig;


class TrainingCaseIterator implements \Iterator
{
	protected $conf;
	protected $items = array();
	protected $idx;
	protected $el;

	public function __construct(PGOConfig $conf)
	{
		$this->rewind();

		$this->conf = $conf;

		$items = glob($this->conf->getCasesTplDir() . DIRECTORY_SEPARATOR . "*");
		foreach ($items as $it) {
			if(!is_dir($it)) {
				continue;
			}

			if (!file_exists($this->getHandlerFilename($it))) {
				echo "Test case handler isn't present in '$it'.\n";
				continue;
			}

			if ($this->isInactive($it)) {
				echo "The test case in '$it' is marked inactive.\n";
				continue;
			}

			$this->items[] = $it;
		}


	}

	protected function isInactive(string $base) : bool
	{
		return file_exists($base . DIRECTORY_SEPARATOR . "inactive");
	}

	protected function getHandlerFilename(string $base) : string
	{
		return $base . DIRECTORY_SEPARATOR . "TrainingCaseHandler.php";
	}

	public function current()
	{
		$base =  $this->items[$this->idx];
		$ns = basename($base);

		/* Don't overwrite generated config. */
		$it = $this->conf->getSectionItem($ns);
		if (!$it) {
			$this->conf->importSectionFromDir($ns, $base);
		}

		require_once $this->getHandlerFilename($base);

		$srv_http = $this->conf->getSrv($this->conf->getSectionItem($ns, "srv_http"));
		$srv_db = $this->conf->getSrv($this->conf->getSectionItem($ns, "srv_db"));

		$class = "$ns\\TrainingCaseHandler";

		$this->el = new $class($this->conf, $srv_http, $srv_db);

		return $this->el;
	}

	public function next()
	{
		$this->idx++;
	}

	public function rewind()
	{
		$this->idx = 0;
	}

	public function valid()
	{
		return $this->idx < count($this->items);
	}

	public function key()
	{
		if (!is_object($this->el)) {
			return NULL;
		}

		return $this->el->getName();
	}

}

