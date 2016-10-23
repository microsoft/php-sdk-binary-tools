@echo off

if "%PHP_SDK_PHP_CMD%"=="" (
	echo PHP SDK is not setup
	exit 3
)

call %PHP_SDK_PHP_CMD% %PHP_SDK_BIN_PATH%\phpsdk_deps.php %*

exit /b

