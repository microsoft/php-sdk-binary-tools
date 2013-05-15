@echo off
goto old
IF "%1"=="" GOTO HELP
IF "%2"=="" GOTO HELP

IF NOT EXIST %1% (
	echo ^<%1^> does not exist
	GOTO EXIT
)

IF NOT EXIST %2% (
	echo ^<%2^> does not exist
	GOTO EXIT
)

IF "%3%"=="VC9" (
	if "%4%"== "x64"  set includevc9msm="x86_x64"
	if "%4%"=="x86" set includevc9msm="x86"
	if "%4%"=="" set includevc9msm="x86"
)
:old
SET PHP_SDK_SCRIPT_PATH=%~dp0
SET START=%CD%
REM change the drive
%~d0
echo %PHP_SDK_SCRIPT_PATH%
CALL %PHP_SDK_SCRIPT_PATH%\phpsdk_setvars.bat

SET PHP_INSTALLER_PATH=%PHP_SDK_SCRIPT_PATH%..\win-installer
SET A=%~n5
SET MSI_PATH=%A%.msi
cd %PHP_INSTALLER_PATH%
IF EXIST Files RD /Q /S Files
echo %5
unzip -o -qq -d Files %5

SET php_exe=Files\php.exe

copy %PHP_SDK_SCRIPT_PATH%..\template\php_manual_en.chm Files\

echo generating ... %MSI_PATH%

set phpver=%1
set phpver=%phpver:~0,3%
set phpver=%phpver:.=%

echo Building ExtensionsFeatures.wxs
copy ExtensionsFeatures%phpver%.wxs ExtensionsFeatures.wxs

set suffix=
set extrants=
set extrasnaps=
set buildtype="VC6-x86"
set includevc9msm=

if (%2)==() goto build
if %2==nts set extrants="nts-"
if %2==nts set suffix=NTS
if %2==VC9 set buildtype="VC9-x86"
if %2==VC9 set includeVC9msm="x86"
if %2==x64 set buildtype="VC9-x64"
if %2==x64 set includeVC9msm="x86_x64"
if %2==snapshot set extrasnaps="-latest"

if (%3)==() goto build
if %3==nts set extrants="nts-"
if %3==nts set suffix=NTS
if %3==VC9 set buildtype="VC9-x86"
if %3==VC9 set includeVC9msm="x86"
if %3==x64 set buildtype="VC9-x64"
if %3==x64 set includeVC9msm="x86_x64"
if %3==snapshot set extrasnaps="-latest"

if (%4)==() goto build
if %4==nts set extrants="nts-"
if %4==nts set suffix="NTS"
if %4==VC9 set buildtype="VC9-x86"
if %4==VC9 set includeVC9msm="x86"
if %4==x64 set buildtype="VC9-x64"
if %4==x64 set includeVC9msm="x86_x64"
if %4==snapshot set extrasnaps="-latest"
echo WebServerConfig%phpver%%suffix%.wxs ****
echo %phpver% %suffix%

:build
set msiname="%MSI_PATH%"

echo Building ExtensionsFeatures.wxs
copy ExtensionsFeatures%phpver%.wxs ExtensionsFeatures.wxs

echo Building ExtensionsComponents.wxs
%php_exe% GenExtensionsComponents.wxs.php "%phpver%"

echo Building PHPInstaller%1.wxs
%php_exe% GenPHPInstaller.wxs.php "PHPInstallerBase%phpver%%suffix%.wxs" "%1" "%includevc9msm%"

echo Building WebServerConfig%1.wxs
copy WebServerConfig%phpver%%suffix%.wxs WebServerConfig%1.wxs

echo Compiling UI....
Wix\candle.exe -out PHPInstallerCommon.wixobj PHPInstallerCommon%suffix%%phpver%.wxs

echo Building UI....
Wix\lit.exe -out PHPInstallerCommon.wixlib PHPInstallerCommon.wixobj 

echo Compiling Installer....
Wix\candle.exe ExtensionsComponents.wxs ExtensionsFeaturesBuild.wxs WebServerConfig%1.wxs PHPInstaller%1.wxs 

echo Linking Installer....
Wix\light.exe -out "%msiname%" ExtensionsComponents.wixobj ExtensionsFeaturesBuild.wixobj WebServerConfig%1.wixobj PHPInstaller%1.wixobj PHPInstallerCommon.wixlib -loc WixUI_en-us.wxl



copy %msiname% %~dp5
del %msiname%

GOTO EXIT
:help
ECHO snapshot_installer ^<php dist files^> ^<destination directory^>
echo                    create the MSI file using the php version, architecture and compiler information
GOTO EXIT

:EXIT
CD %START%