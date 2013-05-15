@ECHO OFF
REM Run 5.3 Snapshots builds with VC9/VC6

SET PHP_SDK_SCRIPT_PATH=%~dp0
CALL %PHP_SDK_SCRIPT_PATH%\phpsdk_setvars.bat

%~d0

CD %PHP_SDK_PATH%\php_5_3\sources


REM unzip -o -qq %PHP_SDK_PATH%\php_5_3\sources\php-5.3-src-latest.zip

FOR /D %%A IN (php-?.?-src-*) DO (
	SET DIRNAME=%%A
)
ECHO Using %DIRNAME% ...

FOR /D %%A IN (php-?.?-src-*) DO (
	SET DIRNAME=%%A
)
FOR /D %%A IN (php-?.?-src-*) DO (
	SET DIRNAME=%%A
)

FOR /F "tokens=4 delims=-" %%A IN ("%DIRNAME%") DO ECHO %%A
REM snapshot_src_download.bat 5.3