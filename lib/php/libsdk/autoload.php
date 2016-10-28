<?php

function __autoload($name)
{
	require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . $name . ".php";
}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 * vim600: sw=4 ts=4 fdm=marker
 * vim<600: sw=4 ts=4
 */
