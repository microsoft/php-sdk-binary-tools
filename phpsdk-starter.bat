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

call %~dp0bin\phpsdk_setshell.bat vc14 x64


if "%3" neq "" (
	if exist "%3" (
		cmd /k "!PHP_SDK_VC_SHELL_CMD! && %~dp0\bin\phpsdk_setvars.bat && %~dp0\bin\phpsdk_dumpenv.bat && %3 && set prompt=$P$_$+$$$S"
	) else (
		echo The appended file %3 doesn't exist
		exit /b 3
	)
) else (
	cmd /k "!PHP_SDK_VC_SHELL_CMD! && %~dp0\bin\phpsdk_setvars.bat && %~dp0\bin\phpsdk_dumpenv.bat && set prompt=$P$_$+$$$S"
)

set PHP_SDK_RUN_FROM_ROOT=

exit /b

