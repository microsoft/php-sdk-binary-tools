<?php

namespace SDK\Dependency;

use SDK\Config;
use SDK\Exception;

class Package
{
	protected $name;
	protected $series;
	protected $fetcher;
	protected $filepath;

	public function __construct(string $name, Series $series, Fetcher $fetcher)
	{
		$this->name = $name;
		$this->series = $series;
		$this->fetcher = $fetcher;
	}

	public function getUri() : string
	{
		$base = Config::getDepsBaseUri();
		$branch_data = Config::getCurrentBranchData();
		$arch = $this->series->getArch();

		return "$base/{$branch_data['crt']}/$arch/{$this->name}";
	}

	public function retrieve(string $path)
	{
		$this->filepath = $path . DIRECTORY_SEPARATOR . $this->name;

		$cont = $this->fetcher->getByUri($this->getUri());

		$fd = fopen($this->filepath, "wb");
		fwrite($fd, $cont);
		fclose($fd);
	}

	public function unpack(string $path)
	{
		if (!$this->filepath || !file_exists($this->filepath)) {
			throw new Exception("Invalid filepath '{$this->filepath}'");
		}
		$zip = new \ZipArchive;

		$ret = $zip->open($this->filepath);
		if (true === $ret) {
			$zip->extractTo($path);
			$zip->close();
		} else {
			throw new Exception("Failed to unpack, error code '$ret'");
		}
	}

	public function cleanup()
	{
		unlink($this->filepath);		
	}
}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 * vim600: sw=4 ts=4 fdm=marker
 * vim<600: sw=4 ts=4
 */
