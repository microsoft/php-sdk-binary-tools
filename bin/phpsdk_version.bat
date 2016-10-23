@echo off

%PHP_SDK_PHP_CMD% -r "echo 'PHP-SDK ' . file_get_contents(getenv('PHP_SDK_PATH') . '\\VERSION');"

exit /b

