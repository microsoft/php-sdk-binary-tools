@echo off

call %~dp0phpsdk-starter.bat -c vs16 -a x64 %*

exit /b %ERRORLEVEL%

