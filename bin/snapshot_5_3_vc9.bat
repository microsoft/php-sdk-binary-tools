@ECHO OFF
SET PHP_SDK_SCRIPT_PATH=%~dp0

REM change the drive
%~d0

CALL %PHP_SDK_SCRIPT_PATH%\phpsdk_setvars.bat

CD %PHP_SDK_PATH%\php_5_3\vc9\x86

IF "%1" == "" (
	SET CONFIGURE_ARGS=--enable-snapshot-build --disable-isapi --enable-debug-pack --disable-isapi  --with-pdo-oci=D:\php-sdk\oracle\instantclient10\sdk,shared --with-oci8=D:\php-sdk\oracle\instantclient10\sdk,shared --with-oci8-11g=D:\php-sdk\oracle\instantclient11\sdk,shared --with-enchant=shared
)
IF "%1" == "ts" (
	SET CONFIGURE_ARGS=--enable-snapshot-build --disable-isapi --enable-debug-pack --disable-isapi  --with-pdo-oci=D:\php-sdk\oracle\instantclient10\sdk,shared --with-oci8=D:\php-sdk\oracle\instantclient10\sdk,shared --with-oci8-11g=D:\php-sdk\oracle\instantclient11\sdk,shared --with-enchant=shared
)

IF "%1" == "nts" (
 	SET CONFIGURE_ARGS=--enable-snapshot-build --enable-debug-pack --disable-zts --disable-isapi  --disable-nsapi --with-pdo-oci=D:\php-sdk\oracle\instantclient10\sdk,shared --with-oci8=D:\php-sdk\oracle\instantclient10\sdk,shared --with-oci8-11g=D:\php-sdk\oracle\instantclient11\sdk,shared --with-enchant=shared
)

SET PHP_MODULE=php5

IF "%2" == "" (
	CD %PHP_SDK_PATH%\snap_5_3\sources
	CALL snapshot_src_download.bat 5.3
)

CD %PHP_SDK_PATH%\snap_5_3\vc9\x86
CALL snapshot.bat 9 5.3 snap53_vc9 %PHP_SDK_PATH%\snap_5_3\sources\php-5.3-src-latest.zip  %4
