@echo off

setlocal enableextensions enabledelayedexpansion

set PHP_SDK_RUN_FROM_ROOT=1

call %~dp0bin\phpsdk_shell.bat vc14 x86

cmd /k %PHP_SDK_SHELL_CMD% && %~dp0\bin\phpsdk_setvars.bat

set PHP_SDK_RUN_FROM_ROOT=

exit 

