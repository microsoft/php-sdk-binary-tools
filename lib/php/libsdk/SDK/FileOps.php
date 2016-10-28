<?php

namespace SDK;

use SDK\Config;
use SDK\Exception;

trait FileOps
{
	protected function md(string $name = "", bool $tmp = false) : string
	{/*{{{*/
		$ret = $name;

		if (!$name) {
			if ($tmp) {
				$pre = Config::getTmpDir();
				$ret = $pre . DIRECTORY_SEPARATOR . md5(uniqid());
			} else {
				throw new Exception("Dir name is empty");
			}
		}


		if (!is_dir($ret)) {
			if (!mkdir($ret, 0755, true)) {
				throw new Exception("Unable to create '$ret'");
			}
		}

		return $ret;
	}/*}}}*/

	/* TODO is link and more checks. */
	protected function rm(string $path) : bool
	{/*{{{*/
		if (!file_exists($path)) {
			return false;
		} else if (is_file($path)) {
			return unlink($path);
		}

		$iterator = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator(
				$path,
				\FilesystemIterator::SKIP_DOTS
			),
			\RecursiveIteratorIterator::CHILD_FIRST
		);
		foreach ($iterator as $item) {
			if ($item->isDir()) {
				rmdir($item->getPathname());
			} else {
				unlink($item->getPathname());
			}
		}
		return rmdir($path);
	}/*}}}*/

	/* TODO islink and more checks */
	protected function cp_or_mv(string $src, string $dst, callable $cb) : bool
	{/*{{{*/
		if (!file_exists($src)) {
			return false;
		} else if (is_file($src)) {
			return call_user_func($cb, $src, $dst);
		}

		if (!file_exists($dst)) {
			$this->md($dst);
		}

		$iterator = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator(
				$src,
				\FilesystemIterator::SKIP_DOTS
			),
			\RecursiveIteratorIterator::CHILD_FIRST
		);
		$cut_len = strlen($src)+1;
		foreach ($iterator as $item) {
			$src_path = $item->getPathname();
			$sub = substr($src_path, $cut_len);
			$dst_path = $dst . DIRECTORY_SEPARATOR . $sub;
			$dst_parent = dirname($dst_path);

			if (!is_dir($dst_parent)) {
				if (!$this->md($dst_parent)) {
					throw new Exception("Unable to create '$dst_parent'");
				}
			}

			if ($item->isFile()) {
				if (!call_user_func($cb, $src_path, $dst_path)) {
					throw new Exception("Unable to $cb '$src_path' to '$dst_path'");
				}
			}
			
		}

		return true;
	}/*}}}*/

	protected function cp(string $src, string $dst) : bool
	{/*{{{*/
		return $this->cp_or_mv($src, $dst, "copy");
	}/*}}}*/

	protected function mv(string $src, string $dst) : bool
	{/*{{{*/
		$ret = $this->cp_or_mv($src, $dst, "rename");

		$ret = $ret && $this->rm($src);

		return $ret;
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
