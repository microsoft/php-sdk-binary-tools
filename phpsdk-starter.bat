@echo off

setlocal enableextensions enabledelayedexpansion

if "%1" equ "" goto help
if "%2" equ "" goto help
goto skip_help

:help
	echo Usage: phpsdk-starter ^<crt^> ^<arch^> [^<append_script.bat^>]
	exit /b 0

:skip_help

set PHP_SDK_RUN_FROM_ROOT=1

title PHP SDK

call %~dp0bin\phpsdk_setshell.bat %1 %2

if errorlevel 3 (
	exit /b %errorlevel%
)

if exist "%~dp0phpsdk-local.bat" (
	cmd /k "!PHP_SDK_VC_SHELL_CMD! && %~dp0\bin\phpsdk_setvars.bat && %~dp0\bin\phpsdk_dumpenv.bat && %~dp0\phpsdk-local.bat && set prompt=$P$_$+$$$S"
) else (
	cmd /k "!PHP_SDK_VC_SHELL_CMD! && %~dp0\bin\phpsdk_setvars.bat && %~dp0\bin\phpsdk_dumpenv.bat && set prompt=$P$_$+$$$S"
)

set PHP_SDK_RUN_FROM_ROOT=

exit /b

