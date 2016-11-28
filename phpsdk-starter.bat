@echo off

setlocal enableextensions enabledelayedexpansion

set IMHERE=%~dp0

:getopt
if /i "%1" equ "-h" goto help
if /i "%1" equ "-c" set CRT=%2 & shift
if /i "%1" equ "-a" set ARCH=%2 & shift
if /i "%1" equ "-t" set TASK=%2 & shift
if /i "%1" equ "--task-args" set TASK_ARGS=%2 & shift
shift
if not (%1)==() goto getopt

if "%CRT%" equ "" goto help
if "%ARCH%" equ "" goto help
goto skip_help

:help
	echo Usage: phpsdk-starter -c ^<crt^> -a ^<arch^> [-t ^<task_script.bat^>]
	exit /b 0

:skip_help

set CRT=%CRT: =%
set ARCH=%ARCH: =%

set PHP_SDK_RUN_FROM_ROOT=1

title PHP SDK

call %IMHERE%bin\phpsdk_setshell.bat %CRT% %ARCH%

if errorlevel 3 (
	exit /b %errorlevel%
)

if "%TASK%" neq "" (
	if exist "%TASK%" (
		set TASK_ARGS=%TASK_ARGS:"=%

		if exist "%IMHERE%phpsdk-local.bat" (
			cmd /c "!PHP_SDK_VC_SHELL_CMD! && %IMHERE%\bin\phpsdk_setvars.bat && %IMHERE%\phpsdk-local.bat && %TASK% !TASK_ARGS!"
		) else (
			cmd /c "!PHP_SDK_VC_SHELL_CMD! && %IMHERE%\bin\phpsdk_setvars.bat && %TASK% !TASK_ARGS!"
		)
		exit /b
	) else (
		echo could not find the task file
		exit /b 3
	)
)

if exist "%IMHERE%phpsdk-local.bat" (
	cmd /k "!PHP_SDK_VC_SHELL_CMD! && %IMHERE%\bin\phpsdk_setvars.bat && %IMHERE%\bin\phpsdk_dumpenv.bat && %IMHERE%\phpsdk-local.bat && echo. && set prompt=$P$_$+$$$S"
) else (
	cmd /k "!PHP_SDK_VC_SHELL_CMD! && %IMHERE%\bin\phpsdk_setvars.bat && %IMHERE%\bin\phpsdk_dumpenv.bat && set prompt=$P$_$+$$$S"
)

set PHP_SDK_RUN_FROM_ROOT=

exit /b

