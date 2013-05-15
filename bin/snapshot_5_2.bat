@ECHO OFF
SET PHP_SDK_SCRIPT_PATH=%~dp0

REM change the drive
%~d0

CALL %PHP_SDK_SCRIPT_PATH%\phpsdk_setvars.bat


IF "%1" == "" (
	SET CONFIGURE_ARGS=--enable-snapshot-build --enable-debug-pack --with-snapshot-template=%PHP_SDK_PATH%\snap_5_2\vc6\x86\template  --with-php-build=%PHP_SDK_PATH%\snap_5_2\vc6\x86\php_build --with-pdo-oci=D:\php-sdk\oracle\instantclient10\sdk,shared --with-oci8=D:\php-sdk\oracle\instantclient10\sdk,shared 
)

IF "%1" == "ts" (
	SET CONFIGURE_ARGS=--enable-snapshot-build --enable-debug-pack --with-snapshot-template=%PHP_SDK_PATH%\snap_5_2\vc6\x86\template  --with-php-build=%PHP_SDK_PATH%\snap_5_2\vc6\x86\php_build --with-pdo-oci=D:\php-sdk\oracle\instantclient10\sdk,shared --with-oci8=D:\php-sdk\oracle\instantclient10\sdk,shared 
)

IF "%1" == "nts" (
	SET CONFIGURE_ARGS=--enable-snapshot-build --enable-debug-pack --disable-zts --disable-isapi --with-snapshot-template=%PHP_SDK_PATH%\snap_5_2\vc6\x86\template  --with-php-build=%PHP_SDK_PATH%\snap_5_2\vc6\x86\php_build --with-pdo-oci=D:\php-sdk\oracle\instantclient10\sdk,shared --with-oci8=D:\php-sdk\oracle\instantclient10\sdk,shared 
)

SET PHP_MODULE=php5

IF "%2" == "" (
	CD %PHP_SDK_PATH%\snap_5_2\sources
	CALL snapshot_src_download.bat 5.2
)

CD %PHP_SDK_PATH%\snap_5_2\vc6\x86
CALL snapshot.bat 6 5.2 snap52_vc6 %PHP_SDK_PATH%\snap_5_2\sources\php-5.2-src-latest.zip %4
