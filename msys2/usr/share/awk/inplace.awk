# inplace --- load and invoke the inplace extension.

@load "inplace"

# Please set INPLACE_SUFFIX to make a backup copy.  For example, you may
# want to set INPLACE_SUFFIX to .bak on the command line or in a BEGIN rule.

# By default, each filename on the command line will be edited inplace.
# But you can selectively disable this by adding an inplace=0 argument
# prior to files that you do not want to process this way.  You can then
# reenable it later on the commandline by putting inplace=1 before files
# that you wish to be subject to inplace editing.

# N.B. We call inplace_end() in the BEGINFILE and END rules so that any
# actions in an ENDFILE rule will be redirected as expected.

BEGIN {
    inplace = 1		# enabled by default
}

BEGINFILE {
    if (_inplace_filename != "")
        inplace_end(_inplace_filename, INPLACE_SUFFIX)
    if (inplace)
        inplace_begin(_inplace_filename = FILENAME, INPLACE_SUFFIX)
    else
        _inplace_filename = ""
}

END {
    if (_inplace_filename != "")
        inplace_end(_inplace_filename, INPLACE_SUFFIX)
}
