@echo off

call %~dp0phpsdk-starter.bat -c vc16 -a x64 %*

exit /b %ERRORLEVEL%

