@echo ofF

%~dp0php.exe -c %~dp0php.ini -d curl.cainfo=%PHP_SDK_ROOT_PATH%\msys2\usr\ssl\cert.pem -d extension_dir=%~dp0ext %*
