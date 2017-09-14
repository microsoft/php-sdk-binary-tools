<?php

namespace SDK;

use SDK\{Config, Exception};

class Lock
{
	protected $fd;
	protected $locked = false;
	protected $wouldBlock = false;

	public function __construct(string $tag, bool $auto = true, bool $auto_flags = LOCK_EX | LOCK_NB)
	{/*{{{*/
		$hash = md5($tagConfig::getDepsLocalPath());
		$fd = Config::getTmpDir() . DIRECTORY_SEPARATOR . $hash;

		$this->fd = fopen($fn, "wb");

		if ($auto) {
			$this->locked = $this->doLock($auto_flags);
		}
	}/*}}}*/

	public function shared(bool $block = false) : bool
	{
		$flags = LOCK_SH;
		if (!$block) {
			$flags = LOCK_NB;
		}

		return $this->doLock($flags);
	}

	public function exclusive(bool $block = false) : bool
	{
		$flags = LOCK_EX;
		if (!$block) {
			$flags = LOCK_NB;
		}

		return $this->doLock($flags);
	}

	protected function doLock(int $flags = LOCK_EX) : bool
	{
		$this->locked = flock($this->fd, $flags, $this->wouldBlock);	
		return $this->locked;
	}

	public function unlock() : bool
	{
		if ($this->locked) {
			return $this->doLock(LOCK_UN);
		}
		return $this->locked;
	}

	public function locked() : bool
	{
		return $this->locked;
	}

	public function wouldBlock() : bool
	{
		return 1 === $this->wouldBlock;
	}

	public function __destruct()
	{
		$this->unlock();
		fclose($this->fd);
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
