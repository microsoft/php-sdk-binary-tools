@ECHO OFF
SET PHP_SDK_SCRIPT_PATH=%~dp0

REM change the drive
%~d0

CALL %PHP_SDK_SCRIPT_PATH%\phpsdk_setvars.bat

SET PHP_MODULE=HEAD

IF "%1" == "" (
	SET CONFIGURE_ARGS=--enable-snapshot-build --disable-isapi --enable-debug-pack --without-static-icu  --without-sqlite --with-pdo-oci=D:\php-sdk\oracle\instantclient10\sdk,shared --with-oci8=D:\php-sdk\oracle\instantclient10\sdk,shared --with-oci8-11g=D:\php-sdk\oracle\instantclient11\sdk,shared
)

IF "%1" == "ts" (
	SET CONFIGURE_ARGS=--enable-snapshot-build --disable-isapi --enable-debug-pack --without-static-icu  --without-sqlite --with-pdo-oci=D:\php-sdk\oracle\instantclient10\sdk,shared --with-oci8=D:\php-sdk\oracle\instantclient10\sdk,shared --with-oci8-11g=D:\php-sdk\oracle\instantclient11\sdk,shared
)

IF "%1" == "nts" (
 	SET CONFIGURE_ARGS=--enable-snapshot-build --disable-zts --disable-isapi --enable-debug-pack --without-static-icu --without-sqlite --with-pdo-oci=D:\php-sdk\oracle\instantclient10\sdk,shared --with-oci8=D:\php-sdk\oracle\instantclient10\sdk,shared --with-oci8-11g=D:\php-sdk\oracle\instantclient11\sdk,shared
)

SET OLD_PATH=%PATH%
SET PATH=%PATH%;%PHP_SDK_PATH%\snap_6_0\vc9\x86\deps\bin

IF "%2" == "" (
	CD %PHP_SDK_PATH%\snap_6_0\sources
	CALL snapshot_src_download.bat 6.0
)

CD %PHP_SDK_PATH%\snap_6_0\vc9\x86
CALL snapshot.bat 9 6.0 snap60_vc9 %PHP_SDK_PATH%\snap_6_0\sources\php-6.0-src-latest.zip %4
