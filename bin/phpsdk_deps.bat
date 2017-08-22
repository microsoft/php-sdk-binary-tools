@echo off

cmd /c "exit /b 0"

if "%PHP_SDK_PHP_CMD%"=="" (
	call %~dp0phpsdk_setvars.bat
	if "!PHP_SDK_PHP_CMD!"=="" (
		echo PHP SDK is not setup
		exit /b 3
	)
)

call %PHP_SDK_PHP_CMD% %PHP_SDK_BIN_PATH%\phpsdk_deps.php %*

exit /b %errorlevel%

