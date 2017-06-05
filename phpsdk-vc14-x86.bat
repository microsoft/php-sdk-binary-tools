@echo off

call %~dp0phpsdk-starter.bat -c vc14 -a x86 %*

exit /b %ERRORLEVEL%

