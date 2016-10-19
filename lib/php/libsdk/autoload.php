<?php

function __autoload($name)
{
	require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . $name . ".php";
}

