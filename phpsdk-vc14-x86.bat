@echo off

setlocal enableextensions enabledelayedexpansion

set PHP_SDK_RUN_FROM_ROOT=1

call %~dp0bin\phpsdk_setshell.bat vc14 x86

title PHP SDK
cmd /k "%PHP_SDK_VC_SHELL_CMD% && %~dp0\bin\phpsdk_setvars.bat && %~dp0\bin\phpsdk_dumpenv.bat && set prompt=$P$_$+$$$S"

set PHP_SDK_RUN_FROM_ROOT=

exit 

