@ECHO OFF

SET OLDPATH=%PATH%
CALL d:\php-sdk\rmtools\bin\snap.bat d:\php-sdk\rmtools\config\php53vc9x86.ini
SET PATH=%OLDPATH%
SET OLDPATH=%PATH%
CALL d:\php-sdk\rmtools\bin\snap.bat d:\php-sdk\rmtools\config\php53ntsvc9x86.ini
SET PATH=%OLDPATH%
SET OLDPATH=%PATH%
CALL d:\php-sdk\rmtools\bin\snap.bat d:\php-sdk\rmtools\config\php53vc6x86.ini
SET PATH=%OLDPATH%
SET OLDPATH=%PATH%
CALL d:\php-sdk\rmtools\bin\snap.bat d:\php-sdk\rmtools\config\php53ntsvc6x86.ini
SET PATH=%OLDPATH%