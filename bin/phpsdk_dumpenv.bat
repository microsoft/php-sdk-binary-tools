@echo off

if "%PHP_SDK_OS_ARCH%"=="" (
	echo PHP SDK is not setup
	exit /b 3
)

cmd /c "exit /b 0"

echo.

call %PHP_SDK_BIN_PATH%\phpsdk_version.bat
echo.

echo OS architecture:    %PHP_SDK_OS_ARCH% 
echo Build architecture: %PHP_SDK_ARCH% 
echo Visual C++:         %PHP_SDK_VC_TOOLSET_VER%
echo PHP-SDK path:       %PHP_SDK_ROOT_PATH%


exit /b %errorlevel%

