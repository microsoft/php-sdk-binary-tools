<?php

namespace SDK;

class Lock
{
	protected $fd;
	protected $fn;
	protected $locked = false;
	protected $wouldBlock = false;
	protected $shared = false;

	public function __construct(string $tag, bool $auto = true, bool $autoShared = false)
	{/*{{{*/
		$hash = hash("sha256", $tag);
		$this->fn = Config::getTmpDir() . DIRECTORY_SEPARATOR . $hash . ".lock";

		if ($auto) {
			if ($autoShared) {
				$this->shared();
			} else {
				$this->exclusive();
			}
		}
	}/*}}}*/

	public function __destruct()
	{/*{{{*/
		$this->unlock();
		/* We don't really know no one else waits on the same lock yet.*/
		/*if (file_exists($this->fn) && !$this->shared) {
			@unlink($this->fn);
		}*/
	}/*}}}*/

	public function shared(bool $block = false) : bool
	{/*{{{*/
		$flags = LOCK_SH;
		if (!$block) {
			$flags |= LOCK_NB;
		}

		return $this->doLock($flags);
	}/*}}}*/

	public function exclusive(bool $block = false) : bool
	{/*{{{*/
		$flags = LOCK_EX;
		if (!$block) {
			$flags |= LOCK_NB;
		}

		return $this->doLock($flags);
	}/*}}}*/

	protected function doLock(int $flags = LOCK_EX) : bool
	{/*{{{*/
		if ($this->locked) {
			/* Or throw an exception, as we don't know which lock type the outta world expected. */
			return true;
		}

		$this->shared = $flags & LOCK_SH;
		if ($this->shared) {
			$this->fd = fopen($this->fn, "rb");
		} else {
			$this->fd = fopen($this->fn, "wb");
		}
		if (false === $this->fd) {
			throw new Exception("Failed to open lock under '$this->fn'");
		}
		$this->locked = flock($this->fd, $flags, $this->wouldBlock);	
		return $this->locked;
	}/*}}}*/

	public function unlock() : bool
	{/*{{{*/
		if (!$this->locked) {
			return true;
		}

		$this->doLock(LOCK_UN);

		fclose($this->fd);
		$this->fd = NULL;

		return $this->locked;
	}/*}}}*/

	public function locked() : bool
	{/*{{{*/
		return $this->locked;
	}/*}}}*/

	public function wouldBlock() : bool
	{/*{{{*/
		return 1 === $this->wouldBlock;
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
