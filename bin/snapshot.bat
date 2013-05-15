@ECHO off
REM Configuration 
CALL %PHP_SDK_PATH%\script\conf_tools.bat

IF EXIST %PHP_SDK_PATH%\snaps.lock (
	ECHO Snapshot script is already running
	GOTO EXIT_LOCKED
)

SET LOG_DIR=%PHP_SDK_PATH%\log
SET START=%CD%
echo "LOCKED" >  %PHP_SDK_PATH%\snaps.lock

IF "%1"=="" GOTO HELP
IF "%2"=="" GOTO HELP
IF "%3"=="" GOTO HELP
IF "%4"=="" SET USE_CVS=Yes
IF "%5"=="msi" SET USE_INSTALLER=Yes

SET SRC_ARCHIVE=%4
SET VC=%1
SET BRANCH=%2
SET DEST=%3
FOR /F "TOKENS=1* DELIMS= " %%A IN ('DATE/T') DO SET CALL_DATE=%%B
FOR /F "TOKENS=*" %%A IN ('TIME/T') DO SET CALL_TIME=%%A
SET CALL_DATETIME=%CALL_DATE% %CALL_TIME%

IF "%2"=="5.2" (
	SET BRANCH=PHP_5_2
	SET PHP_VERSION=5.2
	GOTO START
)
IF "%2"=="5.3" (
	SET BRANCH=PHP_5_3
	SET PHP_VERSION=5.3
	GOTO START
)
IF "%2"=="6.0" (
	SET BRANCH=HEAD
	SET PHP_VERSION=6.0
	GOTO START
)
echo Invalid branch name
GOTO EXIT

:START
for /F "tokens=1-4 delims=:., " %%a in ('time/T') do set _TIME=%%a%%b%%c
for /F "tokens=2-5 delims=:.,/ " %%a in ('date/T') do set _DATE=%%a%%b%%c
SET SNAPDATETIME=%_DATE%%_TIME%

REM IF EXIST %3 rmdir /s /q %3
SET OLD_INCLUDE=%INCLUDE%
SET OLD_LIB=%LIB%
SET OLD_PATH=%PATH%

IF "%VC%"=="6" GOTO CONFIG_VC6
IF "%VC%"=="9" GOTO CONFIG_VC9
IF "%VC%"=="9x64" GOTO CONFIG_VC9_X64
echo Invalid VC Version
GOTO EXIT

:CONFIG_VC6
ECHO Setting environment for VC6-x86
SET INCLUDE=%VC6_INCLUDE%
SET LIB=%VC6_LIB%
SET PATH=%VC6_PATH%;%PATH%
SET ARCH=x86
SET VC_VERS=VC6
GOTO CVS

:CONFIG_VC9
ECHO Setting environment for VC9-x86
SET INCLUDE=%VC9_INCLUDE%
SET LIB=%VC9_LIB%
SET PATH=%VC9_PATH%;%PATH%
SET VC_VERS=VC9
SET ARCH=x86
GOTO CVS

:CONFIG_VC9_X64
ECHO Setting environment for VC9-x64
SET INCLUDE=%VC9_X64_INCLUDE%
SET LIB=%VC9_LIB%
SET PATH=%VC9_X64_PATH%;%PATH%
SET VC_VERS=VC9
SET ARCH=x64

:CVS
IF NOT "%USE_CVS%"=="Yes" GOTO USE_LAST_ARCHIVE
IF EXIST %DEST% RD /Q /S %DEST%
echo checkout from  cvs %PHP_CVSROOT% -z3 checkout -r %BRANCH% -d %DEST% %PHP_MODULE%
cvs %PHP_CVSROOT% -z3 export -r %BRANCH% -d %DEST% %PHP_MODULE% > %START%\cvs.log 2<&1

GOTO TEST_DEST

:USE_LAST_ARCHIVE
ECHO Using archive %SRC_ARCHIVE% ...
unzip -o -qq %SRC_ARCHIVE%
FOR /D %%A IN (php-?.?-src-*) DO (
	SET DIRNAME=%%A
)
ECHO Using %DIRNAME% ...
IF EXIST %DIRNAME%.last GOTO ALREADY_DONE
IF EXIST %DEST% RD /Q /S %DEST%
REN %DIRNAME% %DEST%

REM Clean old directories and .last file
FOR /D %%A IN (php-?.?-src-*) DO (
	RD /Q /S %%A
)
FOR /F "tokens=4 delims=-" %%A IN ("%DIRNAME%") DO SET SNAPDATETIME=%%A

:TEST_DEST
echo Testing %DEST%...
IF EXIST %DEST% GOTO DEST_EXISTS 
ECHO CVS or Archive ERROR %DEST% cannot be found
GOTO EXIT

:DEST_EXISTS
echo Compiling...
cd %DEST%

echo Buildconf log for %SNAPDATETIME% called at %CALL_DATETIME% >> %START%\buildconf.log
call buildconf.bat > %START%\buildconf.log 2<&1

echo Configure log for %SNAPDATETIME% called at %CALL_DATETIME% > %START%\configure.log 2<&1
cscript /nologo configure.js %CONFIGURE_ARGS% >> %START%\configure.log 2<&1

echo Compile log for %SNAPDATETIME% called at %CALL_DATETIME% > %START%\compile.log
nmake snap >> %START%\compile.log 2<&1

:TRANSFERT
echo Transfert files to %SSH_HOST%
cd %DEST%
IF EXIST Release_TS GOTO RELEASE_TS
IF EXIST Release GOTO RELEASE_NTS

:RELEASE_TS
SET PHP_BUILD_DIR=Release_TS
SET PHP_EXE=Release_TS\php.exe
SET NTS_POSTFIX=
SET NTS=ts
IF NOT EXIST %PHP_EXE% (
	ECHO Build error.
	GOTO EXIT
)
GOTO REMOTE_COPY

:RELEASE_NTS
SET PHP_BUILD_DIR=Release
SET PHP_EXE=Release\php.exe
SET NTS_POSTFIX=-nts
SET NTS=nts
IF NOT EXIST %PHP_EXE% (
	ECHO Build error.
	GOTO EXIT
)
:REMOTE_COPY
FOR /F "tokens=*" %%A IN ('%PHP_EXE% -r "echo substr(phpversion(),0,3);"') DO SET _PHPVERSION_SHORT=%%A
FOR /F "tokens=*" %%A IN ('%PHP_EXE% -r "echo phpversion();"') DO SET _PHPVERSION_STRING=%%A
IF "%USE_CVS%"=="Yes" FOR /F "tokens=*" %%A IN ('%PHP_EXE% -r "echo date('YmdHi');"') DO SET SNAPDATETIME=%%A

SET SSH_URL=%SSH_USER%@%SSH_HOST%
echo %SSH_KEY%
FOR %%A IN (%START%\*.log) DO (
	ECHO copying %LOG_DIR%\%%~nA-%_PHPVERSION_SHORT%-%VC_VERS%-%ARCH%%NTS_POSTFIX%-%SNAPDATETIME%.log ...
	COPY %START%\%%~nA.log %LOG_DIR%\%%~nA-%_PHPVERSION_SHORT%-%VC_VERS%-%ARCH%%NTS_POSTFIX%-%SNAPDATETIME%.log
	"%PUTTYBASE%pscp.exe" -batch -q -i %SSH_KEY% -l %SSH_USER% %LOG_DIR%\%%~nA-%_PHPVERSION_SHORT%-%VC_VERS%-%ARCH%%NTS_POSTFIX%-%SNAPDATETIME%.log %SSH_URL%:%REMOTE_PATH% >> %LOG_DIR%\scp_%PHP_VERSION%%NTS_POSTFIX%-%SNAPDATETIME%.log 2<&1
)

FOR %%A IN (%PHP_BUILD_DIR%\*.zip) DO (
	echo Copying %%~nA-%SNAPDATETIME%.zip ...
	"%PUTTYBASE%pscp.exe" -batch -q -i %SSH_KEY% -l %SSH_USER% %PHP_BUILD_DIR%\%%~nA.zip %SSH_URL%:%REMOTE_PATH%/%%~nA-%SNAPDATETIME%.zip >> %LOG_DIR%\scp_%PHP_VERSION%%NTS_POSTFIX%-%SNAPDATETIME%.log 2<&1
	copy %PHP_BUILD_DIR%\%%~nA.zip %PHP_BUILD_DIR%\%%~nA-%SNAPDATETIME%.zip
)


REM Remove old links and create the "-latest links"
"%PUTTYBASE%plink.exe" -i %SSH_KEY% -l %SSH_USER% %SSH_HOST% "rm -f" %REMOTE_PATH%/php-%PHP_VERSION%%NTS_POSTFIX%-win32-%VC_VERS%-%ARCH%-latest.zip >> %LOG_DIR%\scp_%PHP_VERSION%%NTS_POSTFIX%-%SNAPDATETIME%.log 2<&1
"%PUTTYBASE%plink.exe" -i %SSH_KEY% -l %SSH_USER% %SSH_HOST% "rm -f" %REMOTE_PATH%/php-debug-pack-%PHP_VERSION%%NTS_POSTFIX%-win32-%VC_VERS%-%ARCH%-latest.zip  >> %LOG_DIR%\scp_%PHP_VERSION%%NTS_POSTFIX%-%SNAPDATETIME%.log 2<&1
"%PUTTYBASE%plink.exe" -i %SSH_KEY% -l %SSH_USER% %SSH_HOST% "rm -f" %REMOTE_PATH%/php-test-pack-%_PHPVERSION_SHORT%-latest.zip  >> %LOG_DIR%\scp_%PHP_VERSION%%NTS_POSTFIX%-%SNAPDATETIME%.log 2<&1
"%PUTTYBASE%plink.exe" -i %SSH_KEY% -l %SSH_USER% %SSH_HOST% "rm -f" %REMOTE_PATH%/compile-%PHP_VERSION%%NTS_POSTFIX%-%VC_VERS%-%ARCH%-latest.log  >> %LOG_DIR%\scp_%PHP_VERSION%%NTS_POSTFIX%-%SNAPDATETIME%.log 2<&1
"%PUTTYBASE%plink.exe" -i %SSH_KEY% -l %SSH_USER% %SSH_HOST% "rm -f" %REMOTE_PATH%/buildconf-%PHP_VERSION%%NTS_POSTFIX%-%VC_VERS%-%ARCH%-latest.log  >> %LOG_DIR%\scp_%PHP_VERSION%%NTS_POSTFIX%-%SNAPDATETIME%.log 2<&1
"%PUTTYBASE%plink.exe" -i %SSH_KEY% -l %SSH_USER% %SSH_HOST% "rm -f" %REMOTE_PATH%/configure-%PHP_VERSION%%NTS_POSTFIX%-%VC_VERS%-%ARCH%-latest.log  >> %LOG_DIR%\scp_%PHP_VERSION%%NTS_POSTFIX%-%SNAPDATETIME%.log 2<&1
"%PUTTYBASE%plink.exe" -i %SSH_KEY% -l %SSH_USER% %SSH_HOST% "rm -f"  %REMOTE_PATH%/cache.info  >> %LOG_DIR%\scp_%PHP_VERSION%%NTS_POSTFIX%-%SNAPDATETIME%.log 2<&1

SET VERSION_INFO=%_PHPVERSION_STRING%%NTS_POSTFIX%-Win32

IF "%PHP_VERSION%"=="5.2" GOTO OLD_NAMING

:CLEAN_NAMING
"%PUTTYBASE%plink.exe" -i %SSH_KEY% -l %SSH_USER% %SSH_HOST% ln -s %REMOTE_PATH%/php-%VERSION_INFO%-%VC_VERS%-%ARCH%-%SNAPDATETIME%.zip %REMOTE_PATH%/php-%PHP_VERSION%%NTS_POSTFIX%-win32-%VC_VERS%-%ARCH%-latest.zip >> %LOG_DIR%\scp_%PHP_VERSION%%NTS_POSTFIX%-%SNAPDATETIME%.log 2<&1
"%PUTTYBASE%plink.exe" -i %SSH_KEY% -l %SSH_USER% %SSH_HOST% ln -s %REMOTE_PATH%/php-debug-pack-%VERSION_INFO%-%VC_VERS%-%ARCH%-%SNAPDATETIME%.zip %REMOTE_PATH%/php-debug-pack-%PHP_VERSION%%NTS_POSTFIX%-win32-%VC_VERS%-%ARCH%-latest.zip  >> %LOG_DIR%\scp_%PHP_VERSION%%NTS_POSTFIX%-%SNAPDATETIME%.log 2<&1
"%PUTTYBASE%plink.exe" -i %SSH_KEY% -l %SSH_USER% %SSH_HOST% ln -s %REMOTE_PATH%/php-test-pack-%_PHPVERSION_STRING%-%SNAPDATETIME%.zip %REMOTE_PATH%/php-test-pack-%_PHPVERSION_SHORT%-latest.zip  >> %LOG_DIR%\scp_%PHP_VERSION%%NTS_POSTFIX%-%SNAPDATETIME%.log 2<&1
"%PUTTYBASE%plink.exe" -i %SSH_KEY% -l %SSH_USER% %SSH_HOST% ln -s %REMOTE_PATH%/compile-%PHP_VERSION%-%VC_VERS%-%ARCH%%NTS_POSTFIX%-%SNAPDATETIME%.log %REMOTE_PATH%/compile-%PHP_VERSION%%NTS_POSTFIX%-%VC_VERS%-%ARCH%-latest.log  >> %LOG_DIR%\scp_%PHP_VERSION%%NTS_POSTFIX%-%SNAPDATETIME%.log 2<&1
"%PUTTYBASE%plink.exe" -i %SSH_KEY% -l %SSH_USER% %SSH_HOST% ln -s %REMOTE_PATH%/buildconf-%PHP_VERSION%-%VC_VERS%-%ARCH%%NTS_POSTFIX%-%SNAPDATETIME%.log %REMOTE_PATH%/buildconf-%PHP_VERSION%%NTS_POSTFIX%-%VC_VERS%-%ARCH%-latest.log  >> %LOG_DIR%\scp_%PHP_VERSION%%NTS_POSTFIX%-%SNAPDATETIME%.log 2<&1
"%PUTTYBASE%plink.exe" -i %SSH_KEY% -l %SSH_USER% %SSH_HOST% ln -s %REMOTE_PATH%/configure-%PHP_VERSION%-%VC_VERS%-%ARCH%%NTS_POSTFIX%-%SNAPDATETIME%.log %REMOTE_PATH%/configure-%PHP_VERSION%%NTS_POSTFIX%-%VC_VERS%-%ARCH%-latest.log  >> %LOG_DIR%\scp_%PHP_VERSION%%NTS_POSTFIX%-%SNAPDATETIME%.log 2<&1

SET PHP_ZIP_FILE=%PHP_BUILD_DIR%\php-%VERSION_INFO%-%VC_VERS%-%ARCH%-%SNAPDATETIME%.zip
GOTO INSTALLER

:OLD_NAMING
"%PUTTYBASE%plink.exe" -i %SSH_KEY% -l %SSH_USER% %SSH_HOST% ln -s %REMOTE_PATH%/php-%VERSION_INFO%-%SNAPDATETIME%.zip %REMOTE_PATH%/php-%PHP_VERSION%%NTS_POSTFIX%-win32-%VC_VERS%-%ARCH%-latest.zip >> %LOG_DIR%\scp_%PHP_VERSION%%NTS_POSTFIX%-%SNAPDATETIME%.log 2<&1
"%PUTTYBASE%plink.exe" -i %SSH_KEY% -l %SSH_USER% %SSH_HOST% ln -s %REMOTE_PATH%/php-debug-pack-%VERSION_INFO%-%SNAPDATETIME%.zip %REMOTE_PATH%/php-debug-pack-%PHP_VERSION%%NTS_POSTFIX%-win32-%VC_VERS%-%ARCH%-latest.zip  >> %LOG_DIR%\scp_%PHP_VERSION%%NTS_POSTFIX%-%SNAPDATETIME%.log 2<&1
"%PUTTYBASE%plink.exe" -i %SSH_KEY% -l %SSH_USER% %SSH_HOST% ln -s %REMOTE_PATH%/compile-%PHP_VERSION%-%VC_VERS%-%ARCH%-%SNAPDATETIME%.log %REMOTE_PATH%/compile-%PHP_VERSION%-%VC_VERS%-%ARCH%-latest.log  >> %LOG_DIR%\scp_%PHP_VERSION%%NTS_POSTFIX%-%SNAPDATETIME%.log 2<&1
"%PUTTYBASE%plink.exe" -i %SSH_KEY% -l %SSH_USER% %SSH_HOST% ln -s %REMOTE_PATH%/buildconf-%PHP_VERSION%-%VC_VERS%-%ARCH%-%SNAPDATETIME%.log %REMOTE_PATH%/buildconf-%PHP_VERSION%-%VC_VERS%-%ARCH%-latest.log  >> %LOG_DIR%\scp_%PHP_VERSION%%NTS_POSTFIX%-%SNAPDATETIME%.log 2<&1
"%PUTTYBASE%plink.exe" -i %SSH_KEY% -l %SSH_USER% %SSH_HOST% ln -s %REMOTE_PATH%/configure-%PHP_VERSION%-%VC_VERS%-%ARCH%-%SNAPDATETIME%.log %REMOTE_PATH%/configure-%PHP_VERSION%-%VC_VERS%-%ARCH%-latest.log  >> %LOG_DIR%\scp_%PHP_VERSION%%NTS_POSTFIX%-%SNAPDATETIME%.log 2<&1

SET PHP_ZIP_FILE=%PHP_BUILD_DIR%\php-%VERSION_INFO%-%SNAPDATETIME%.zip
GOTO INSTALLER


:INSTALLER
echo ************************ 3
IF "%USE_INSTALLER%"=="Yes" (
	echo ************************ 3.1 installer
	FOR %%A IN (%PHP_ZIP_FILE%) DO (
		echo snapshot_installer.bat %PHP_VERSION%.0 %NTS% %VC_VERS% %ARCH% %%~fA
		CALL snapshot_installer.bat %PHP_VERSION%.0 %NTS% %VC_VERS% %ARCH% %%~fA >> %LOG_DIR%\msi_%PHP_VERSION%%NTS_POSTFIX%-%VC_VERS%-%ARCH%-%SNAPDATETIME%.log 2<&1
	)
)

FOR %%A IN (%PHP_BUILD_DIR%\*.msi) DO (
	echo Copying %%~nA.msi ...
	"%PUTTYBASE%pscp.exe" -batch -q -i %SSH_KEY% -l %SSH_USER% %PHP_BUILD_DIR%\%%~nA.msi %SSH_URL%:%REMOTE_PATH%/%%~nA.msi >> %LOG_DIR%\scp_%PHP_VERSION%%NTS_POSTFIX%-%VC_VERS%-%ARCH%-%SNAPDATETIME%.log 2<&1
	"%PUTTYBASE%plink.exe" -i %SSH_KEY% -l %SSH_USER% %SSH_HOST% rm %REMOTE_PATH%/php-%PHP_VERSION%%NTS_POSTFIX%-win32-%VC_VERS%-%ARCH%-latest.msi >> %LOG_DIR%\scp_%PHP_VERSION%%NTS_POSTFIX%-%VC_VERS%-%ARCH%-%SNAPDATETIME%.log 2<&1
	"%PUTTYBASE%plink.exe" -i %SSH_KEY% -l %SSH_USER% %SSH_HOST% ln -s %REMOTE_PATH%/%%~nA.msi %REMOTE_PATH%/php-%PHP_VERSION%%NTS_POSTFIX%-win32-%VC_VERS%-%ARCH%-latest.msi >> %LOG_DIR%\scp_%PHP_VERSION%%NTS_POSTFIX%-%VC_VERS%-%ARCH%-%SNAPDATETIME%.log 2<&1
)

"%PUTTYBASE%plink.exe" -i %SSH_KEY% -l %SSH_USER% %SSH_HOST% "sha1sum %REMOTE_PATH%/*-latest.zip %REMOTE_PATH%/*-latest.msi > %REMOTE_PATH%/sha1sum.txt"  >> %LOG_DIR%\scp_%PHP_VERSION%%NTS_POSTFIX%-%SNAPDATETIME%.log 2<&1

echo ************************ 4
GOTO EXIT
	
:HELP
ECHO snapshot ^<VC version^> ^<branch/tag^> ^<destination^>
GOTO EXIT

:ALREADY_DONE
ECHO Snapshot for %DIRNAME% already done


:EXIT
del %PHP_SDK_PATH%\snaps.lock

FOR /D %%A IN (*.last) DO (
	DEL %%A
)

REM Set the last "snap"
echo %DIRNAME% > %DIRNAME%.last

:EXIT_LOCKED
cd %START%

SET LIB=%OLD_LIB%
SET INCLUDE=%OLD_INCLUDE%
SET PATH=%OLD_PATH%

SET BRANCH=
SET DEST=
SET DIRNAME=
SET USE_CVS=
SET LOG_DIR=
SET NTS_POSTFIX=
SET PHP_BUILD_DIR=
SET PHP_EXE=
SET PHP_VERSION=
SET SSH_URL=
SET START=
SET VC=
SET ZIP_PATH=