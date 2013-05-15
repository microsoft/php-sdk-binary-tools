@ECHO OFF

SET PSDK_200302_DIR=C:\Program Files\Microsoft SDK
SET VC6_DIR=C:\Program Files\Microsoft Visual Studio

SET PSDK_61_DIR=C:\Program Files\Microsoft SDKs\Windows\v6.1
SET VC9_DIR=C:\Program Files\Microsoft Visual Studio 9.0

REM C:\Program Files\Microsoft SDK\Bin;C:\Program Files\Microsoft SDK\Bin\WinNT;C:\PROGRA~1\MICROS~3\Common\msdev98\BIN;C:\PROGRA~1\MICROS~3\VC98\BIN;C:\PROGRA~1\MICROS~3\Common\TOOLS\WINNT;C:\PROGRA~1\MICROS~3\Common\TOOLS;C:\Perl\site\bin;C:\Perl\bin;C:\Program Files\PHP\;C:\WINDOWS\system32;C:\WINDOWS;C:\WINDOWS\System32\Wbem;c:\win2k3cd;C:\Program Files\cvsnt;C:\Program Files\WinSCP\;C:\Program Files\CVSNT\ 
REM C:\Program Files\Microsoft SDK\Lib;C:\PROGRA~1\MICROS~3\VC98\LIB;C:\PROGRA~1\MICROS~3\VC98\MFC\LIB; 
REM C:\Program Files\Microsoft SDK\Include;C:\PROGRA~1\MICROS~3\VC98\ATL\INCLUDE;C:\PROGRA~1\MICROS~3\VC98\INCLUDE;C:\PROGRA~1\MICROS~3\VC98\MFC\INCLUDE; 

SET VC6_INCLUDE=%PSDK_200302_DIR%\Include;%VC6_DIR%\VC98\ATL\Include;%VC6_DIR%\VC98\Include;%VC6_DIR%\VC98\MFC\Include
SET VC6_LIB=%PSDK_200302_DIR%\Lib;%VC6_DIR%\VC98\LIB;%VC6_DIR%\VC98\MFC\LIB
SET VC6_PATH=%PSDK_200302_DIR%\Bin;%PSDK_200302_DIR%\Bin\WinNT;%VC6_DIR%\Common\msdev98\BIN;%VC6_DIR%\VC98\BIN;%VC6_DIR%\Common\TOOLS\WINNT;%VC6_DIR%\Common/TOOLS

SET VC9_SHELL=C:\WINDOWS\system32\cmd.exe /E:ON /V:ON /T:0E /K "C:\Program Files\Microsoft SDKs\Windows\v6.1\Bin\SetEnv.Cmd"
SET VC9_INCLUDE=%PSDK_61_DIR%\include;%VC9_DIR%\VC\ATLMFC\INCLUDE;%VC9_DIR%\VC\INCLUDE
SET VC9_LIB=%PSDK_61_DIR%\lib;%VC9_DIR%\VC\ATLMFC\LIB;%VC9_DIR%\VC\LIB
SET VC9_PATH=%VC9_DIR%\Common7\IDE;%VC9_DIR%\VC\BIN;%VC9_DIR%\Common7\Tools;%VC9_DIR%\VC\VCPackages;%PSDK_61_DIR%\bin;C:\WINDOWS\Microsoft.NET\Framework\v3.5;C:\WINDOWS\Microsoft.NET\Framework\v2.0.50727;C:\WINDOWS\system32;C:\WINDOWS;C:\WINDOWS\System32\Wbem

SET VC9_X64_INCLUDE=%VC9_DIR%\VC\Include;C:\Program Files\Microsoft SDKs\Windows\v6.1\Include;C:\Program Files\Microsoft SDKs\Windows\v6.1\Include\gl;%VC9_DIR%VC\ATLMFC\INCLUDE; 
SET VC9_X64_LIB=%VC9_DIR%\VC\Lib\amd64;C:\Program Files\Microsoft SDKs\Windows\v6.1\Lib\x64;%VC9_DIR%\VC\ATLMFC\LIB\AMD64; 
SET VC9_X64_PATH=%VC9_DIR%\VC\Bin\x86_amd64;%VC9_DIR%\VC\Bin;%VC9_DIR%\VC\vcpackages;%VC9_DIR%\Common7\IDE;C:\Program Files\Microsoft SDKs\Windows\v6.1\Bin;C:\WINDOWS\Microsoft.NET\Framework64\v3.5;C:\WINDOWS\Microsoft.NET\Framework\v3.5;C:\WINDOWS\Microsoft.NET\Framework64\v2.0.50727;C:\WINDOWS\Microsoft.NET\Framework\v2.0.50727;C:\Perl\site\bin;C:\Perl\bin;C:\Program Files\PHP\;C:\WINDOWS\system32;C:\WINDOWS;C:\WINDOWS\System32\Wbem;c:\win2k3cd;C:\Program Files\cvsnt;C:\Program Files\WinSCP\;C:\Program Files\CVSNT\ 

SET PHP_CVSROOT=-d :pserver:cvsread@cvs.php.net:/repository
SET PUTTYBASE=C:\Program Files\PUTTY\
SET SSH_USER=snaps
SET SSH_HOST=windows.php.net
SET SSH_KEY= d:\php-sdk\keys\windows.php.net.ppk
SET REMOTE_PATH=/home/web/windows.php.net/docroot/downloads/snaps
SET HOMEPATH=\Documents and Settings\pierre
SET HOMEDRIVE=C:
