@ECHO OFF
SET PHP_SDK_SCRIPT_PATH=%~dp0
CALL %PHP_SDK_SCRIPT_PATH%\phpsdk_setvars.bat
ECHO %PHP_SDK_SCRIPT_PATH%

REM change the drive
%~d0

IF "%1"=="5.2" GOTO DOWNLOAD
IF "%1"=="5.3" GOTO DOWNLOAD
IF "%1"=="6.0" GOTO DOWNLOAD

GOTO USAGE

:DOWNLOAD
SET BRANCH=%1
SET PHP_ARCHIVE_FILENAME=php-%BRANCH%-src-latest.zip

IF EXIST %PHP_ARCHIVE_FILENAME% DEL %PHP_ARCHIVE_FILENAME%
wget http://windows.php.net/downloads/snaps/php-%BRANCH%/%PHP_ARCHIVE_FILENAME%
REM unzip -o -qq %PHP_ARCHIVE_FILENAME%

REM Take the last one
FOR /D %%A IN (php-%BRANCH%-src-*) DO (
	SET DIRNAME=%%A
)
ECHO Downloaded: %DIRNAME%

:DONE
GOTO EXIT

:USAGE
echo Usage %~n0 ^<branch name^> (5.2 5.3 or 6.0)

:EXIT
SET A=
SET N=
SET BRANCH=
SET PHP_ARCHIVE_FILENAME=
SET DIRNAME=
SET cnt=