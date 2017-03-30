@echo ofF

%~dp0php.exe -c %~dp0php.ini -d extension_dir=%~dp0ext %*
