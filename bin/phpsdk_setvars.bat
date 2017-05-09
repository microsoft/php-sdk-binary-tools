@echo off

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

rem setup pgo tools
if "%PHP_SDK_PGO_TOOLS_ROOT_PATH%"=="" (
	if exist "%PHP_SDK_ROOT_PATH%\pgo" (
		set PHP_SDK_PGO_TOOLS_ROOT_PATH=%PHP_SDK_ROOT_PATH%\pgo
	)
)
set TMP_PATH=%PHP_SDK_PGO_TOOLS_ROOT_PATH%\bin;
if "%TMP_PATH%"=="\bin;" (
	set TMP_PATH=
)
set PATH=%TMP_PATH%%PATH%
set TMP_PATH=
rem end setup pgo tools

exit /b

