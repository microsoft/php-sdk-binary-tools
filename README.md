# PHP SDK

PHP SDK is a tool kit for Windows PHP builds

# License

BSD

# Notes

This reworked SDK is compatible with PHP 7.0 and above. The compatibility with php-sdk-binary-tools-20110915.zip available from windows.php.net is kept, though some irrelevant tools was removed. Though, newer tools and workflows are now possible, and the work is in progress. The toolset consists on a mix of the hand written scripts, selected MSYS2 parts and standalone programs.

## SDK tools

- `phpsdk_buildtree` - initialize the development filesystem structure
- `phpsdk_deps`      - check and handle dependency libraries
- `phpsdk_version`   - show SDK version
- `phpsdk_dllmap`    - create a JSON listing of DLLs contained in zip files

## Other available tools

- `bison` 3.0.2, `re2c` 0.15.3
- `awk`, `gawk`, `sed`, `grep`
- `diff`, `diff3`, `patch`
- `md5sum`, `sha1sum`, `sha224sum`, `sha256sum`, `sha384sum`, `sha512sum`
- `7za`, `zip`, `unzip`
- `wget`

## Not included

These are not included with the PHP SDK, but might be useful for the compilation and other tasks. While Visual C++ is the only required, the others might enable some additional functionality. Care yourself about making them available on your system.

- `Visual C++` - required always
- `clang`      - optional, useful for experimental builds and for static analysis
- `ICC`        - optional, useful for experimental builds
- `Git`        - optional, useful for PHP source management
- `Cppcheck`   - optional, used for static analysis

# Usage

## Basic usage example

- `git clone https://github.com/OSTC/php-sdk-binary-tools.git c:\php-sdk`
- `git checkout new_binary_tools`
- `cd` to c:\php-sdk and click on `phpsdk-vc14-x64.bat` in the SDK root
- `phpsdk_buildtree php70 && git clone https://github.com/php/php-src.git`, or fetch a zipball
- cd into php-src, run `phpsdk_deps --update --branch YOUR_BRANCH_NAME`
- do the build, eg. `buildconf && configure --enable-cli && nmake`

TODO more extensive documentation on the wiki

## Staying compatible with the older version of the SDK

- `git clone https://github.com/OSTC/php-sdk-binary-tools.git c:\php-sdk`
- follow the instructions on the PHP [wiki page](https://wiki.php.net/internals/windows/stepbystepbuild "PHP wiki page")

