@ECHO OFF
SET PHP_SDK_SCRIPT_PATH=%~dp0

REM change the drive
%~d0

CALL %PHP_SDK_SCRIPT_PATH%\phpsdk_setvars.bat

CD %PHP_SDK_PATH%\snap_6_0\sources
CALL snapshot_src_download.bat 6.0
 
CMD /C snapshot_6_0_vc9.bat ts nodownload no msi
CMD /C snapshot_6_0_vc9.bat nts nodownload no msi
REM CMD /C snapshot_6_0.bat ts nodownload no msi
REM CMD /C snapshot_6_0.bat nts nodownload no msi