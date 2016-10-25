@echo off
IF "%1" EQU "" GOTO Help

IF "%2" NEQ ""   SET _=%2\%1
IF "%2" EQU ""   SET _=%CD%\%1

rem TODO rewrite this script to handle more intelligently, creating dirs for only the branch matching current env

MD %_%\vc14\x86\deps\bin
MD %_%\vc14\x86\deps\lib
MD %_%\vc14\x86\deps\include
MD %_%\vc14\x64\deps\bin
MD %_%\vc14\x64\deps\lib
MD %_%\vc14\x64\deps\include

GOTO EXIT

:help
echo phpsdk_buildtree ^<nameofthetree^> [PATH]
echo  Create the common directory structure used by the PHP SDK

:EXIT
