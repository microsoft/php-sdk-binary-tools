@echo off

setlocal enableextensions enabledelayedexpansion

set PHPSDK_MSYS2_BASE_ADDR=0x70000000
set PHPSDK_MSYS2_BASE_DYNAMIC=0

:getopt
if /i "%1" equ "--help" goto help
if /i "%1" equ "--addr" (
	set PHPSDK_MSYS2_BASE_ADDR=%2 & shift
	for /l %%a in (1,1,100) do if "!PHPSDK_MSYS2_BASE_ADDR:~-1!"==" " set PHPSDK_MSYS2_BASE_ADDR=!PHPSDK_MSYS2_BASE_ADDR:~0,-1!
)
shift
if /i "%1" equ "--dynamic" (
	set PHPSDK_MSYS2_BASE_DYNAMIC=1
	shift
)
if not (%1)==() goto getopt

IF "1" EQU "%PHPSDK_MSYS2_BASE_DYNAMIC%" (
	echo Rebasing MSYS2 DLLs to load at a dynamic address
	editbin /NOLOGO /DYNAMICBASE %PHP_SDK_ROOT_PATH%\msys2\usr\bin\*.dll
) else (
	echo Rebasing MSYS2 DLLs to load at %PHPSDK_MSYS2_BASE_ADDR%
	editbin /NOLOGO /REBASE:BASE=%PHPSDK_MSYS2_BASE_ADDR%,DOWN %PHP_SDK_ROOT_PATH%\msys2\usr\bin\*.dll
)

set PHPSDK_MSYS2_BASE_ADDR=
set PHPSDK_MSYS2_BASE_DYNAMIC=

GOTO EXIT

:help
echo phpsdk_rebase_msys2 ^<address^>
echo Rebase MSYS2 DLLs to the given address. If ommited, default is 0x70000000.

:EXIT
exit /b %errorlevel%

