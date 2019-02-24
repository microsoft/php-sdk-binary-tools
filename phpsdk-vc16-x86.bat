@echo off

call %~dp0phpsdk-starter.bat -c vc16 -a x86 %*

exit /b %ERRORLEVEL%

