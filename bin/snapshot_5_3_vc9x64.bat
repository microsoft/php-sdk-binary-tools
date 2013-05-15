@ECHO OFF
REM %VC9_SHELL%

REM CD "C:\Program Files\Microsoft SDKs\Windows\v6.1\"
REM CALL "C:\Program Files\Microsoft SDKs\Windows\v6.1\Bin\SetEnv.Cmd" /x86 /xp /release

SET PHP_SDK_SCRIPT_PATH=%~dp0

REM change the drive
%~d0

CALL %PHP_SDK_SCRIPT_PATH%\phpsdk_setvars.bat

SET CONFIGURE_ARGS=--enable-snapshot-build --enable-debug-pack --with-pdo-oci=D:\php-sdk\oracle\instantclient10\sdk,shared --with-oci8=D:\php-sdk\oracle\instantclient10\sdk,shared --with-oci8-11g=D:\php-sdk\oracle\instantclient11\sdk,shared --with-enchant=shared
SET PHP_MODULE=php5

CD %PHP_SDK_PATH%\snap_5_3\sources
CALL snapshot_src_download.bat 5.3

REM XP is the minimum version we support
REM setenv /x86 /xp /release

CD %PHP_SDK_PATH%\snap_5_3\vc9\x64
CALL snapshot.bat 9x64 PHP_5_3 snap53_vc9x64 %PHP_SDK_PATH%\snap_5_3\sources\php-5.3-src-latest.zip

