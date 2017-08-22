@echo off

cmd /c "exit /b 0"

rem Add necessary dirs to the path 

set PHP_SDK_BIN_PATH=%~dp0
rem remove trailing slash
set PHP_SDK_BIN_PATH=%PHP_SDK_BIN_PATH:~0,-1%

for %%a in ("%PHP_SDK_BIN_PATH%") do set PHP_SDK_ROOT_PATH=%%~dpa
rem remove trailing slash
set PHP_SDK_ROOT_PATH=%PHP_SDK_ROOT_PATH:~0,-1%

set PHP_SDK_MSYS2_PATH=%PHP_SDK_ROOT_PATH%\msys2\usr\bin
set PHP_SDK_PHP_CMD=%PHP_SDK_BIN_PATH%\php\do_php.bat

set PATH=%PHP_SDK_BIN_PATH%;%PHP_SDK_MSYS2_PATH%;%PATH%

if 11 equ %PHP_SDK_VC_NUM% (
	set "PATH=%PHP_SDK_ROOT_PATH%\compat5\bin;%PATH%"
)

exit /b %errorlevel%

