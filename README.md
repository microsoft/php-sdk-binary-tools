# PHP SDK

PHP SDK is a tool kit for Windows PHP builds

# License

BSD

# Overview

This reworked SDK is compatible with PHP 7.0 and above. The compatibility with php-sdk-binary-tools-20110915.zip available from windows.php.net is kept. Though, some irrelevant tools was removed. Newer tools and workflows are now possible. The toolset consists on a mix of the hand written scripts, selected MSYS2 parts and standalone programs.

# Tools

## Required

- `Visual C++ 2015` is required and must to be installed prior SDK usage.

## SDK

- `phpsdk_buildtree` - initialize the development filesystem structure
- `phpsdk_deps`      - check and handle dependency libraries
- `phpsdk_version`   - show SDK version
- `phpsdk_dllmap`    - create a JSON listing of DLLs contained in zip files
- minimal `PHP` distribution is available through the PHP_SDK_PHP_CMD variable. 

## MSYS2

- `bison` 3.0.2, `re2c` 0.15.3
- `awk`, `gawk`, `sed`, `grep`
- `diff`, `diff3`, `patch`
- `md5sum`, `sha1sum`, `sha224sum`, `sha256sum`, `sha384sum`, `sha512sum`
- `7za`, `zip`, `unzip`, `unzipsfx`
- `wget`

## Not included

These are not included with the PHP SDK, but might be useful for the compilation and other tasks. While Visual C++ is the only required, the others might enable some additional functionality. Care yourself about making them available on your system.

- `Git`        - optional, useful for PHP source management
- `Cppcheck`   - optional, used for static analysis
- `clang`      - optional, useful for experimental builds and for static analysis
- `ICC`        - optional, useful for experimental builds

# Usage

## Basic usage example

- `git clone https://github.com/OSTC/php-sdk-binary-tools.git c:\php-sdk`
- `cd c:\php-sdk`
- `git checkout new_binary_tools`
- either run or click on `phpsdk-vc14-x64.bat` in the SDK root
- `cd` to c:\php-sdk and click on `phpsdk-vc14-x64.bat` in the SDK root
- `phpsdk_buildtree phpmaster`
- `git clone https://github.com/php/php-src.git && cd php-src`, or fetch a zipball
- `phpsdk_deps --update --branch master`
- do the build, eg. `buildconf && configure --enable-cli && nmake`

TODO more extensive documentation on the wiki

## Staying compatible with the older version of the SDK

- `git clone https://github.com/OSTC/php-sdk-binary-tools.git c:\php-sdk`
- follow the instructions on the PHP [wiki page](https://wiki.php.net/internals/windows/stepbystepbuild "PHP wiki page")

# Pitfalls

- SDK or PHP sources put into paths including spaces might cause issue.
- If Cygwin is installed, it might cause issues. If it's unavoidable, to have Cygwin on the same machine, ensure SDK preceeds it on the PATH.
- Tools, based on MSYS2, only accept paths with forward slashes.

