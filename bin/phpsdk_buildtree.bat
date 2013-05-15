@echo off
IF "%1" EQU "" GOTO Help

IF "%2" NEQ ""   SET _=%2\%1
IF "%2" EQU ""   SET _=%CD%\%1

MD %_%\vc6\x86\deps\bin
MD %_%\vc6\x86\deps\lib
MD %_%\vc6\x86\deps\include

MD %_%\vc8\x86\deps\bin
MD %_%\vc8\x86\deps\lib
MD %_%\vc8\x86\deps\include
MD %_%\vc8\x64\deps\bin
MD %_%\vc8\x64\deps\lib
MD %_%\vc8\x64\deps\include

MD %_%\vc9\x86\deps\bin
MD %_%\vc9\x86\deps\lib
MD %_%\vc9\x86\deps\include
MD %_%\vc9\x64\deps\bin
MD %_%\vc9\x64\deps\lib
MD %_%\vc9\x64\deps\include

GOTO EXIT

:help
echo createbuildtree ^<nameofthetree^> [PATH]
echo  Create the common directory structure used by the PHP SDK

:EXIT
