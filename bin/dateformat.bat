@ECHO OFF
IF NOT "%OS%"=="Windows_NT" GOTO Syntax
IF      "%?1"==""           GOTO Syntax
IF NOT  "%?6"==""           GOTO Syntax
ECHO .%* | FIND "?" > NUL && GOTO Syntax

SETLOCAL ENABLEDELAYEDEXPANSION
FOR /F "tokens=2-4 delims=(/-)" %%A IN ('VER ?| DATE') DO (
	SET Var1=%%A
	SET Var2=%%B
	SET Var3=%%C
)
FOR /F "tokens=2 delims=:" %%A IN ('VER ?| DATE ?| FIND /V "("') DO (
	FOR /F "tokens=1-3 delims=/- " %%B IN ("%%A") DO (
		SET %Var1%=%%B
		SET %Var2%=%%C
		SET %Var3%=%%D
	)
)
IF /I NOT "%?1"=="%Var1%" IF /I NOT "%?1"=="%Var2%" IF /I NOT "%?1"=="%Var3%" (
	ENDLOCAL
	GOTO Syntax
)
IF /I "%?4"=="/LZ" (SET Delim=) ELSE (SET Delim=%4)
IF /I NOT "%?3"=="%Var1%" IF /I NOT "%?3"=="%Var2%" IF /I NOT "%?3"=="%Var3%" IF /I NOT "%?3"=="/LZ" (SET Delim=%?3)
IF /I NOT "%?2"=="%Var1%" IF /I NOT "%?2"=="%Var2%" IF /I NOT "%?2"=="%Var3%" IF /I NOT "%?2"=="/LZ" (SET Delim=%?2)
ECHO.%* | FIND /I "/LZ" >NUL
IF NOT ERRORLEVEL 1 CALL :AddLeadingZero
SET DateFmt=!%1!
IF /I NOT "%?2"=="%Delim%" IF /I NOT "%?2"=="/LZ" (SET DateFmt=%DateFmt%%Delim%!%2!)
IF /I NOT "%?3"=="%Delim%" IF /I NOT "%?3"=="/LZ" (SET DateFmt=%DateFmt%%Delim%!%3!)
ENDLOCAL & SET DateFmt=%DateFmt%

SET DateFmt

GOTO:EOF


:AddLeadingZero
CALL SET Char1=%%%Var1%:?0,1%%
IF NOT "%Char1%"=="0" (
	IF !%Var1%! LSS 10 SET %Var1%=0!%Var1%!
)
CALL SET Char1=%%%Var2%:?0,1%%
IF NOT "%Char1%"=="0" (
	IF !%Var2%! LSS 10 SET %Var2%=0!%Var2%!
)
CALL SET Char1=%%%Var3%:?0,1%%
IF NOT "%Char1%"=="0" (
	IF !%Var3%! LSS 10 SET %Var3%=0!%Var3%!
)
GOTO:EOF


:Syntax
ECHO DateFmt.bat,  Version 0.52 BETA for Windows NT 4 and later
ECHO Display the current date in the specified format
ECHO.
ECHO Usage:  DATEFMT  date_format  [ delimiter ]  [ /LZ ]
ECHO.
IF     "%OS%"=="Windows_NT" FOR /F "tokens=2-4 delims=()/-" %%A IN ('VER ?| DATE ?| FIND "("') DO ECHO Where:  date_format is any combination of %%A, %%B and/or %%C
IF NOT "%OS%"=="Windows_NT" ECHO Where:  date_format is any combination of dd, mm and/or yy
ECHO                     (these date_format options are always in the computer's
IF NOT "%OS%"=="Windows_NT" ECHO                     local language; to look them up, type VER ¦ DATE)
IF NOT "%OS%"=="Windows_NT" GOTO Skip
ECHO                     local language; to look them up, type VER ?| DATE)
:Skip
ECHO         delimiter   is the delimiter to be used in the end result
ECHO         /LZ         use leading zeroes in the end result
ECHO.
ECHO Examples (for English Windows versions):
ECHO DATEFMT yy mm dd        ---  2007115    (January 15 or November 5, 2007)
ECHO DATEFMT yy mm dd -      ---  2007-11-5  (November 5, 2007)
ECHO DATEFMT yy mm dd - /LZ  ---  2007-11-05 (November 5, 2007)
ECHO DATEFMT mm /LZ          ---  01         (January)
ECHO DATEFMT yy mm - /LZ     ---  2007-06    (June 2007)
ECHO DATEFMT dd mm dd * /LZ  ---  11*03*11   (March 11)
ECHO.
ECHO Inspired by Simon Sheppard's GetDate.bat
ECHO http://www.ss64.com/ntsyntax/getdate.html
ECHO Written by Rob van der Woude
ECHO http://www.robvanderwoude.com
