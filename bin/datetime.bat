for /F "tokens=1-4 delims=:., " %%a in ('time/T') do set _TIME=%%a%%b%%c
for /F "tokens=2-5 delims=:.,/ " %%a in ('date/T') do set _DATE=%%a%%b%%c
SET SNAPDATETIME=%_DATE%%_TIME%
