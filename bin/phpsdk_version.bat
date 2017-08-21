@echo off

cmd /c "exit /b 0"

if "%PHP_SDK_PHP_CMD%"=="" (
	call %~dp0phpsdk_setvars.bat
	if "!PHP_SDK_PHP_CMD!"=="" (
		echo PHP SDK is not setup
		exit /b 3
	)
)

%PHP_SDK_PHP_CMD% -r "echo 'PHP SDK ' . file_get_contents(getenv('PHP_SDK_ROOT_PATH') . '\\VERSION');"

exit /b %errorlevel%

