@echo off

setlocal enableextensions enabledelayedexpansion

if not defined PHP_SDK_RUN_FROM_ROOT (
	echo phpsdk_shell.bat should not be run directly, use starter scripts in the PHP SDK root
	goto out_error
)


if "%1"=="" goto :help
if "%1"=="/?" goto :help
if "%1"=="-h" goto :help
if "%1"=="--help" goto :help
if "%2"=="" goto :help

if /i not "%1"=="vc14" (
		echo Unsupported runtime "%1"
		goto out_error
)

if /i not "%2"=="x64" (
	if /i not "%2"=="x86" (
		echo Unsupported arch "%2"
		goto out_error
	)
)

set PHP_SDK_VC=%1
set PHP_SDK_ARCH=%2

rem check OS arch
set TMPKEY=HKLM\SOFTWARE\Microsoft\Windows\CurrentVersion
reg query "%TMPKEY%" /v "ProgramFilesDir (x86)" >nul 2>nul
if not errorlevel 1 (
	set PHP_SDK_SYS_ARCH=x64
) else (
	if /i "%PHP_SDK_ARCH%"=="x64" (
		echo 32-bit OS detected, native 64-bit toolchain is unavailable.
		goto out_error
	)
	set PHP_SDK_SYS_ARCH=x86
)
set TMPKEY=

rem get vc base dir
if /i "%PHP_SDK_SYS_ARCH%"=="x64" (
	set TMPKEY=HKLM\SOFTWARE\Wow6432Node\Microsoft\VisualStudio\%PHP_SDK_VC:~2%.0\Setup\VC
) else (
	set TMPKEY=HKLM\SOFTWARE\Microsoft\VisualStudio\%PHP_SDK_VC:~2%.0\Setup\VC
)
reg query !TMPKEY! /v ProductDir >nul 2>&1
if errorlevel 1 (
	echo Couldn't determine VC%PHP_SDK_VC:~2% directory
	goto out_error;
)
for /f "tokens=2*" %%a in ('reg query !TMPKEY! /v ProductDir') do set PHP_SDK_VC_DIR=%%b
set TMPKEY=

rem get sdk dir
if /i "%PHP_SDK_SYS_ARCH%"=="x64" (
	set TMPKEY=HKEY_LOCAL_MACHINE\SOFTWARE\Wow6432Node\Microsoft\Microsoft SDKs\Windows\v8.1
) else (
	set TMPKEY=HKEY_LOCAL_MACHINE\SOFTWARE\Microsoft\Microsoft SDKs\Windows\v8.1
)
for /f "tokens=2*" %%a in ('reg query "!TMPKEY!" /v InstallationFolder') do (
	if exist "%%b\Include\um\Windows.h" (
		set PHP_SDK_WIN_SDK_DIR=%%b
	)
)
if not defined PHP_SDK_WIN_SDK_DIR (
	echo Windows SDK not found.
	goto out_error;
)
set TMPKEY=


if /i "%PHP_SDK_ARCH%"=="x64" (
	%comspec% /k "!PHP_SDK_VC_DIR!\vcvarsall.bat" amd64
) else (
	%comspec% /k "!PHP_SDK_VC_DIR!\vcvarsall.bat" x86
)

rem echo Visual Studio path %PHP_SDK_VC_DIR%
rem echo Windows SDK path %PHP_SDK_WIN_SDK_DIR%


goto out

:help
	echo "Start Visual Studio command line for PHP SDK"
	echo "Usage: %0 vc arch" 
	echo nul

:out_error
	exit /b 3

:out
	exit /b 0

