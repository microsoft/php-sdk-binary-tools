<?php

spl_autoload_register(function($name) {
	$fl = dirname(__FILE__) . DIRECTORY_SEPARATOR . "libsdk" . DIRECTORY_SEPARATOR . $name . ".php";

	if (file_exists($fl)) {
		require_once $fl;
	}
});

spl_autoload_register(function($name) {
	$fl = getenv("PHP_SDK_ROOT_PATH") . DIRECTORY_SEPARATOR . "pgo" . DIRECTORY_SEPARATOR . "cases" . DIRECTORY_SEPARATOR . $name . ".php";

	if (file_exists($fl)) {
		require_once $fl;
	}
});

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 * vim600: sw=4 ts=4 fdm=marker
 * vim<600: sw=4 ts=4
 */
