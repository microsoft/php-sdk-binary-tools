@echo off

if "%PHP_SDK_PHP_CMD%"=="" (
	call %~dp0phpsdk_setvars.bat
	if "!PHP_SDK_PHP_CMD!"=="" (
		echo PHP SDK is not setup
		exit /b 3
	)
)

cmd /c %PHP_SDK_PHP_CMD% %PHP_SDK_BIN_PATH%\phpsdk_pgo.php %*

exit /b %errorlevel%

