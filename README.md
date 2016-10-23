# PHP SDK

PHP SDK is a toolset for building PHP under windows

# License

BSD

# Notes

This reworked SDK is compatible with PHP 7.0 and above. The compatibility with php-sdk-binary-tools-20110915.zip available from windows.php.net is kept as well. Though, newer tools and workflows are now possible, and the work is in progress. The toolset consists on a mix of the hand written scripts, selected MSYS2 parts and standalone programs.

## SDK tools

- phpsdk_buildtree - initialize the development filesystem structure
- phpsdk_deps      - check and handle dependency libraries
- phpsdk_version   - show SDK version
- phpsdk_dllmap    - create a JSON listing of DLLs contained in zip files

## Other tools

- bison, re2c
- awk, gawk, sed, grep
- diff, diff3, patch
- md5sum, sha1sum, sha224sum, sha256sum, sha384sum, sha512sum
- 7za, zip, unzip
- wget

Bison version: 3.0.2
re2c version:  0.15.3


# Usage

## Basic usage 

- git checkout https://github.com/OSTC/php-sdk-binary-tools.git c:\php-sdk
- chdir to c:\php-sdk and click one of the phpsdk-\*.bat files in the SDK root, corresponding to the desired build parameters
- either move the sources from your old branch, or - phpsdk_buildtree, switch into the source dir and git clone
- phpsdk_deps --update --branch YOUR_BRANCH_NAME
- do the build, eg. buildconf && configure --enable-cli && nmake

TODO more extensive documentation on the wiki

## The way compatible with the older version of the SDK

- git checkout https://github.com/OSTC/php-sdk-binary-tools.git c:\php-sdk
- follow the instructions on the PHP [wiki page](https://wiki.php.net/internals/windows/stepbystepbuild "PHP wiki page")

