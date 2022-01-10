@echo off

call %~dp0phpsdk-starter.bat -c vs17 -a x86 %*

exit /b %ERRORLEVEL%

