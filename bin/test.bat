@echo off
set MY=Release\php-5.3.0alpha3-dev-nts-Win32-VC9-x86.zip

FOR %%A IN (%MY%) DO echo %%~dpA
set USE_INSTALLER=Yes
IF %USE_INSTALLER%==Yes (
	FOR  %%A IN (%MY%) DO (
		echo snapshot_installer.bat %PHP_VERSION%.0 %NTS% %VC_VERS% %ARCH% %%~fA
		REM CALL snapshot_installer.bat %PHP_VERSION%.0 %NTS% %VC_VERS% %ARCH% %%~dpA
	)
)