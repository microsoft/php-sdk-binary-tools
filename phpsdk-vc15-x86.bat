@echo off

call %~dp0phpsdk-starter.bat -c vc15 -a x86 %*

exit /b %ERRORLEVEL%

