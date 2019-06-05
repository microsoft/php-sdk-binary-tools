@echo off
IF "%1" EQU "" GOTO Help

IF "%2" NEQ ""   SET _=%2\%1
IF "%2" EQU ""   SET _=%CD%\%1

rem if we're in the starter script shell, create the only struct that corresponds to the current env
rem otherwise - retain the old behavior, create structs for all the known build combinations and don't cd

cmd /c "exit /b 0"

if "%PHP_SDK_ARCH%" NEQ "" (
	if "%PHP_SDK_VS%" NEQ "" (
		MD %_%\%PHP_SDK_VS%\%PHP_SDK_ARCH%\deps\bin
		MD %_%\%PHP_SDK_VS%\%PHP_SDK_ARCH%\deps\lib
		MD %_%\%PHP_SDK_VS%\%PHP_SDK_ARCH%\deps\include
		cd %_%\%PHP_SDK_VS%\%PHP_SDK_ARCH%
		goto exit
	)
	goto create_all
) else (
:create_all
	for %%i in (vc14 vc15 vs16) do (
		MD %_%\%%i\x86\deps\bin
		MD %_%\%%i\x86\deps\lib
		MD %_%\%%i\x86\deps\include
		MD %_%\%%i\x64\deps\bin
		MD %_%\%%i\x64\deps\lib
		MD %_%\%%i\x64\deps\include
	)
)

set _=

GOTO EXIT

:help
echo phpsdk_buildtree ^<nameofthetree^> [PATH]
echo  Create the common directory structure used by the PHP SDK

:EXIT
exit /b %errorlevel%

