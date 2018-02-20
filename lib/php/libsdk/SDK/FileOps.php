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

		$ret = true;

		$iterator = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator(
				$path,
				\FilesystemIterator::SKIP_DOTS
			),
			\RecursiveIteratorIterator::CHILD_FIRST
		);
		foreach ($iterator as $item) {
			if ($item->isDir()) {
				$ret = $ret && rmdir($item->getPathname());
			} else {
				$ret = $ret && unlink($item->getPathname());
			}
		}
		return $ret && rmdir($path);
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

	protected function download(string $url, string $dest_fn = NULL) : ?string
	{/*{{{*/
		$fd = NULL;
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url);

		if ($dest_fn) {
			$fd = fopen($dest_fn, "w+");
			curl_setopt($ch, CURLOPT_FILE, $fd); 
		} else {
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		}

		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_USERAGENT, Config::getSdkUserAgentName());
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

		$ret = curl_exec($ch);

		$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if (false === $ret || 200 !== $code) {
			$err = curl_error($ch);
			curl_close($ch);
			if ($dest_fn) {
				fclose($fd);
			}
			throw new Exception($err);
		}

		curl_close($ch);

		if ($dest_fn) {
			fclose($fd);
			return NULL;
		}

		return $ret;
	}/*}}}*/

	/* TODO More detailed zip errors. */
	protected function unzip(string $zip_fn, string $dest_fn, string $dest_dn = NULL) : void
	{/*{{{*/
		$zip = new \ZipArchive;

		$res = $zip->open($zip_fn);
		if (true !== $res) {
			throw new Exception("Failed to open '$zip_fn'.");
		}

		$res = $zip->extractTo($dest_fn);
		if (true !== $res) {
			$zip->close();
			throw new Exception("Failed to unzip '$zip_fn'.");
		}

		/* Not robust, useful for zips containing one dir sibling only in the root. */
		if ($dest_dn) {
			$stat = $zip->statIndex(0);
			if (false === $stat) {
				$zip->close();
				throw new Exception("Failed to stat first index in '$zip_fn'.");
			}

			$zip->close();

			/* Index of zero might be not the zipped folder, unusual but true. */
			/*$name = $stat["name"];
			if ("/" != substr($name, -1)) {
				throw new Exception("'$name' is not a directory.");
			}
			$name = substr($name, 0, -1);*/

			$name = rtrim($stat["name"], "/");
			while (strchr($name, '/') !== false) {
				$name = dirname($name);
			}

			$old_dir = $dest_fn . DIRECTORY_SEPARATOR . $name;
			$new_dir = $dest_fn . DIRECTORY_SEPARATOR . $dest_dn;
			if (file_exists($new_dir)) {
				if (!$this->rm($new_dir)) {
					throw new Exception("Failed to remove '$new_dir'.");
				}
			}
			/* if (!$this->mv($old_dir, $new_dir)) { */
			if (!rename($old_dir, $new_dir)) {
				throw new Exception("Failed to rename '$old_dir' to '$new_dir'.");
			}
		} else {
			$zip->close();
		}
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
