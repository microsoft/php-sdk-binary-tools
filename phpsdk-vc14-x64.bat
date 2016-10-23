@echo off

set PHP_SDK_RUN_FROM_ROOT=1

pushd "%~dp0"

%comspec% /k bin\phpsdk_shell.bat vc14 x64
bin\phpsdk_setvars.bat

set PHP_SDK_RUN_FROM_ROOT=
