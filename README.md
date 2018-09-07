# PHP SDK

PHP SDK is a tool kit for Windows PHP builds.

# License

The PHP SDK itself and the SDK own tools and code are licensed under the BSD 2-Clause license. With the usage of the other tools, you accept the respective licenses.

# Overview

The PHP SDK 2.0+ is compatible with PHP 7.0 and above. The compatibility with [older versions](http://windows.php.net/downloads/php-sdk/php-sdk-binary-tools-20110915.zip "php-sdk-binary-tools-20110915.zip") is kept, also available from the [legacy branch](https://github.com/Microsoft/php-sdk-binary-tools/tree/legacy). The toolset was significantly revamped. Newer tools are now available, better workflows are now possible. The toolset consists on a mix of the hand written scripts, selected MSYS2 parts and standalone programs. 

# Requirements

- `Visual C++ 2015` or `Visual C++ 2017` must be installed prior SDK usage (be sure to install both the C++ and the Windows SDK components)
- if `Cygwin`, `MingW` or any other cross solution is installed, please read notes in the pitfalls section
- if a 64-bit build is intended, a 64-bit system is required. Cross compilation of 64-bit on 32-bit system is not supported at the moment
- The PHP SDK was successfully tested on Windows 7 or later, earlier versions might work but are not recommended

# Tools

All the tools included are either scripts or 32-bit binaries. They are therefore runable on any of x86 or x64 supported Windows system.

## SDK

- starter scripts, named phpsdk-&lt;crt&gt;-&lt;arch&gt;.bat
- `phpsdk_buildtree` - initialize the development filesystem structure
- `phpsdk_deps`      - handle dependency libraries
- `phpsdk_dllmap`    - create a JSON listing of DLLs contained in zip files
- `phpsdk_pgo`       - run PGO training 
- `phpsdk_version`   - show SDK version
- `task.exe`         - wrapper to hide the given command line

## Other tools

- `bison` 3.0.5, `re2c` 1.0.3, `lemon`
- `awk`, `gawk`, `sed`, `grep`
- `diff`, `diff3`, `patch`
- `md5sum`, `sha1sum`, `sha224sum`, `sha256sum`, `sha384sum`, `sha512sum`
- `7za`, `zip`, `unzip`, `unzipsfx`
- `wget`, `pwgen`

## Optional, not included

These are not included with the PHP SDK, but might be useful. While Visual C++ is the only required, the others might enable some additional functionality. Care yourself about making them available on your system, if relevant.

- `Git`        - useful for PHP source management
- `Cppcheck`   - used for static analysis
- `clang`      - useful for experimental builds and for static analysis
- `ICC`        - useful for experimental builds
- `ConEmu`     - console emulator with tabs and more

# Usage

The PHP SDK should be unzipped into the shortest possible path, preferrably somewhere near the drive root.

Usually, the first step to start the PHP SDK is by invoking one of the suitable starter scripts. This automatically puts the console on the correct environment relevant for the desired PHP build configuration.

It is not required to hold the source in the PHP SDK directory. It could be useful, for example, to simplify the SDK updates.

## Basic usage example

- `git clone https://github.com/Microsoft/php-sdk-binary-tools.git c:\php-sdk`
- `cd c:\php-sdk`
- `git checkout php-sdk-2.0.12` or later
- invoke `phpsdk-vc15-x64.bat`
- `phpsdk_buildtree phpmaster`
- `git clone https://github.com/php/php-src.git && cd php-src`, or fetch a zipball
- `phpsdk_deps --update --branch master`, use `phpsdk_deps --update --branch X.Y` for a non master branch
- do the build, eg. `buildconf && configure --enable-cli && nmake`

More extensive documentation can be found on the [wiki](https://wiki.php.net/internals/windows/stepbystepbuild_sdk_2 "PHP wiki page").

## The old way

- `git clone https://github.com/Microsoft/php-sdk-binary-tools.git c:\php-sdk`
- follow the instructions on the PHP [wiki page](https://wiki.php.net/internals/windows/stepbystepbuild "PHP wiki page")

# Customizing

## Custom environment setup

A script called phpsdk-local.bat has to be put into the PHP SDK root. If present, it will be automatically picked up by the starter script. A template for such a script is included with the PHP SDK. This allows to automatically meet any required preparations, that are not foreseen by the standard PHP SDK startup. Be careful while creating your own phpsdk-local. It's your responsibility to ensure the regular PHP SDK startup isn't broken after phpsdk-local.bat was injected into the startup sequence.

## Console emulator integration

The starter scripts can be also easy integrated with the consoles other than standard cmd.exe. For the reference, here's an example ConEmu task

`C:\php-sdk\phpsdk-vc15-x64.bat -cur_console:d:C:\php-sdk\php72\vc15\x64\php-src`

## Unattended builds

An elementary functionality to run unattended builds is included. See an example on how to setup a simple unattended build task in the doc directory.

Be aware, that starter scripts always start a new shell. Scripts intended to run as a task need to be passed with `-t` argument to a starter script.

# Upgrading

- backup phpsdk-local.bat
- backup the source trees and any other custom files in the PHP SDK root, if any present
- move the PHP SDK folder into trash
- download, unpack and the new PHP SDK version under the same path
- move the custom files back in their respective places

If the PHP SDK is kept as a git checkout, merely what is needed instead is to git fetch and to checkout an updated git tag.

# Extending

The SDK tools are based on the KISS principle and should be kept so. Basic tools are implemented as simple batch script. The minimalistic `PHP` is available for internal SDK purposes. It can be used, if more complexity is required. A suitable PHP binary is bound with the PHP SDK. If you have an idea for some useful tool or workflow, please open a ticket or PR, so it can be discussed, implemented and added to the SDK. By contributing an implementation, you should also accept the SDK license.

# PGO

As of the version 2.1.0, the SDK includes a tool for the [PGO](https://docs.microsoft.com/en-us/cpp/build/reference/profile-guided-optimizations) optimization. Several training cases are included by default, which are based on the real life opensource applications. The PGO optimization can give an overall speedup up to 30%. The work on adding more training scenarios for the widely used opensource apps is ongoing. If you have a training scenario to share, please create a PR to this repo. Any new training cases are thoroughly validated through the extensive performance tests.

## Preparing PGO training environment
- the pgo folder in the SDK root dir contains templates and scenarios for PGO training
- adjust and execute [doc/phpsdk_pgo_prep_elevated.bat.example](doc/phpsdk_pgo_prep_elevated.bat.example) to open ports required for PHP SDK training servers
- run `phpsdk_pgo --init`

## Creating PGO build
- compile PHP configured using `--enable-pgi`
- run `phpsdk_pgo --train`
- run `nmake clean-pgo`
- rebuild PHP `--with-pgo`

## Adding custom PGO training scenario

A custom scenario can be used to produce a custom PHP binary dedicated to an arbitrary application. 

The existing training cases can be found in [pgo/cases](pgo/cases). Assumed the case would be named `myapp`, the general steps to setup were

- create the case directory under `pgo/cases/myapp`
- create `pgo/cases/myapp/phpsdk_pgo.json` with the necessary definitions 
- create `pgo/cases/myapp/nginx.partial.conf` with a partial NGINX template
- create `pgo/cases/myapp/TrainingCaseHandler.php` with a class as defined in the [interface](lib/php/libsdk/SDK/Build/PGO/Interfaces/TrainingCase.php)

After a training case is implemented and put under `pgo/cases`, the work environment needs to be reinitialized. The tool puts all the training data and necessary applications under `pgo/work`. Rename or remove that directory and rerun `phpsdk_pgo --init`.

To skip a training case, add a file named `inactive` into the case folder.

# Debugging PHP

This part covers debugging possibilities for the builds produced by the native VS compilers.
For the cross compiled builds produced with toolsets other than VC++, please check the
documentation for the corresponding toolsets. In any case, general principles on debugging
native programs apply.

Either a debug build of PHP or enabled debug symbols are required to be able to debug PHP.
A debug build is usually more suitable for the development process and can be produced by
adding `--enable-debug` to the configure options. A release build with debug symbols can
be produced by adding `--enable-debug-pack`. These options are mutually exclusive.

## Debugging with Visual Studio

- Configure with either `--enable-debug` or `--enable-debug-pack`.
- A debug build might bring better experience for dev, but sometimes you want to debug a release build.
- `nmake run ARGS=yourscript.php DEBUGGER=1`, that will open a Visual Studio window.
- Any additional runtime options for PHP or the script executed go to ARGS, too.
- Select `Debug -> New Breakpoint -> Function Breakpoint` and add a function where the debugger should break.
- Click `Start`.

Adding a breakpoint before starting debugging might be not necessary, if a crash is debugged. When such a script runs
under the debugger, the debugger will stop at the crashing point. In that case, a breakpoint can be added
around the crashed code directly.

## Debugging test suite with Visual Studio

The [Microsoft Child Process Debugging Power Tool](https://marketplace.visualstudio.com/items?itemName=vsdbgplat.MicrosoftChildProcessDebuggingPowerTool)
plugin for Visual Studio is required. After installing it, following these steps

- `nmake test TESTS=ext/myext/tests/sometest.phpt DEBUGGER=1`
- Select `Debug -> Other Debug Targets -> Child Process Debugging Settings` and enable child process debugging.
- If necessary, add a breakpoint and start debugging as described in the previous section.

## Debugging with WinDbg

PHP can also be debugged with the tools from the WinDbg package. There is currently no way
implemented in the Makefile to start the WinDbg integrated, so it needs to de done manually.
Either a debug build or a release build with debug symbols is still required, as described
previously.

# Support

- Join `#winphp-dev` on Freenode to discuss any ideas or questions
- File an issue on GitHub

# Pitfalls

- SDK or PHP sources put into paths including spaces might cause issue.
- SDK or PHP sources put into too long paths, will cause an issue.
- If Cygwin, MSYS2 or MinGW flavors are exposed in global PATH, it might cause issues. If it's unavoidable, ensure SDK preceeds it on the PATH.
- When fetching the binary SDK from git, git `core.autocrlf` configuration directive set to `false` is recommended.
- Tools, based on MSYS2, only accept paths with forward slashes.
- Both Visual C++ toolset and the Windows SDK components have to be installed for the PHP SDK to work properly. 
- The VC++ toolset is still requried, even if another compiler, fe. clang, is intended to be used.
- `task.exe` is not a console application, some systems might not propagate exit code except the batch is explicitly run from `cmd /c`, etc.
- `7za` should be prefered over `unzip` and `zip` for compatibility reasons.


