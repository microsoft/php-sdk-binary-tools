@ECHO OFF
SET PHP_SDK_SCRIPT_PATH=%~dp0

REM change the drive
%~d0

CALL %PHP_SDK_SCRIPT_PATH%\phpsdk_setvars.bat

CD %PHP_SDK_PATH%\snap_5_2\sources
CALL snapshot_src_download.bat 5.2

CMD /C snapshot_5_2.bat ts nodownload no msi
CMD /C snapshot_5_2.bat nts nodownload no msi
