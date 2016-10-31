/**
 * Run the passed command line hidden, suitable for a scheduled task.
 * Author: Anatol Belski <ab@php.net>
 * License: BSD 2-Clause
 */

#include <windows.h>
#include <stdio.h>
#include <stdlib.h>

#define CMD_PRE "cmd.exe /c "
#define CMD_PRE_LEN (sizeof(CMD_PRE)-1)

#ifdef DEBUG
int
main(int argc, char **argv)
#else
int
APIENTRY WinMain(HINSTANCE inst, HINSTANCE prev_inst, LPTSTR in_cmd, int show)
#endif
{
	STARTUPINFO si;
	PROCESS_INFORMATION pi;
	DWORD exit_code;

	char *cmd = NULL;
	size_t cmd_len = 0, arg_len = 0;
	char *arg = strchr(GetCommandLine(), ' ');

	if (!arg) {
		return 3;
	}
#ifdef DEBUG
	printf("passed cmd: '%s'\n", arg);
#endif

	arg_len = strlen(arg);
	cmd_len = CMD_PRE_LEN + arg_len + 1;

	cmd = malloc(cmd_len * sizeof(char));
	memmove(cmd, CMD_PRE, CMD_PRE_LEN);
	memmove(cmd + CMD_PRE_LEN, arg, arg_len);
	cmd[cmd_len-1] = '\0';
#ifdef DEBUG
	printf("constructed cmd: '%s'\n", cmd);
#endif

	ZeroMemory( &si, sizeof(si) );
	si.cb = sizeof(si);
	si.dwFlags = STARTF_USESHOWWINDOW;
	si.wShowWindow = SW_HIDE;
	ZeroMemory( &pi, sizeof(pi) );

	if (CreateProcess(NULL, cmd, NULL, NULL, FALSE, CREATE_NO_WINDOW, NULL, NULL, &si, &pi)) {
		CloseHandle( pi.hThread );
	} else {
		free(cmd);
		printf( "Error: CreatePracess 0x%08lx \n", GetLastError() );
		return 3;
	}

	WaitForSingleObject( pi.hProcess, INFINITE );

	GetExitCodeProcess(pi.hProcess, &exit_code);

	CloseHandle( pi.hProcess );

	free(cmd);

	return exit_code;
}

