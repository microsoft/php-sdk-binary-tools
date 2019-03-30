@echo off

call %~dp0phpsdk-starter.bat -c vs16 -a x86 %*

exit /b %ERRORLEVEL%

