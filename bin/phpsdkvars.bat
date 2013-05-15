::phpsdk.bat
@ECHO OFF
:: Add skd\bin directory to the path

SET PHP_SDK_BIN_PATH=%~dp0

SET PATH=%PATH%;%PHP_SDK_BIN_PATH%

:: Set BISON_SIMPLE
SET BISON_SIMPLE=%PHP_SDK_BIN_PATH%bison.simple
