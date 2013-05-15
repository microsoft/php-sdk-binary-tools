@ECHO OFF

SET OLDPATH=%PATH%
CALL d:\php-sdk\rmtools\bin\snap.bat d:\php-sdk\rmtools\config\phptrunkvc9x86.ini
SET PATH=%OLDPATH%
SET OLDPATH=%PATH%
CALL d:\php-sdk\rmtools\bin\snap.bat d:\php-sdk\rmtools\config\phptrunkntsvc9x86.ini
SET PATH=%OLDPATH%
