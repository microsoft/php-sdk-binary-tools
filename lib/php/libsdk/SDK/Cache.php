<?php

namespace SDK;

class Cache
{
	protected $id;
	protected $hash;

	public function __construct(string $id)
	{/*{{{*/
		$this->id = $id;
		$this->hash = md5($id);
		/* XXX pass as arg, fine for now. */
	}/*}}}*/

	protected function getCacheablePath(string $path, bool $relative = false) : string
	{/*{{{*/
		if ($relative) {
			$dir = Config::getCacheDir();
			$name = $path;
		} else {
			$dir = dirname($path);
			$name = basename($path);
		}

		return $dir . DIRECTORY_SEPARATOR . $this->hash . "." . $name;
	}/*}}}*/

	public function fileIsCached(string $path, bool $relative = false) : bool
	{/*{{{*/
		return file_exists($this->getCacheablePath($path, $relative));
	}/*}}}*/

	/* TODO Sometimes a timestamp comparison might make sense. */
	public function cachedContentDiffers(string $path, string $content, bool $relative = false) : bool
	{/*{{{*/
		$p = $this->getCacheablePath($path, $relative);
		
		if (!file_exists($p)) {
			return true;
		}

		$old_sum = md5_file($p);
		$new_sum = md5($content);

		return $old_sum !== $new_sum;
	}/*}}}*/

	public function cacheContent(string $path, string $content, bool $relative = false) : void
	{/*{{{*/
		$p = $this->getCacheablePath($path, $relative);

		$to_write = strlen($content);
		$wrote = 0;

		$fd = fopen($p, "wb");

		flock($fd, LOCK_EX);

		do {
			$got = fwrite($fd, substr($content, $wrote));
			if (false === $got) {
				break;
			}
			$wrote += $got;
		} while ($wrote < $to_write);

		flock($fd, LOCK_UN);

		fclose($fd);

		if ($to_write !== $wrote) {
			throw new Exception("Couldn't cache '$p'");
		}
	}/*}}}*/

	public function getCachedContent(string $path, bool $relative = false) : ?string
	{/*{{{*/
		$p = $this->getCacheablePath($path, $relative);

		if ($this->isFileCached($p)) {
			return file_get_contents($p);
		}

		return NULL;
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
