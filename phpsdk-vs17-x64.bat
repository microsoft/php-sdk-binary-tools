@echo off

call %~dp0phpsdk-starter.bat -c vs17 -a x64 %*

exit /b %ERRORLEVEL%

