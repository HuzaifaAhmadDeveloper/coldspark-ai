@echo off
setlocal

set PROJECT_DIR=C:\laragon\www\coldspark
set PHP_EXE=C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe
set LOG_FILE=%PROJECT_DIR%\storage\logs\windows-worker.log

cd /d "%PROJECT_DIR%"

if not exist "%PHP_EXE%" (
    echo [%date% %time%] ERROR: PHP not found at %PHP_EXE% — update PHP_EXE in this script if you changed Laragon's active PHP version. >> "%LOG_FILE%"
    exit /b 1
)

REM Fires campaigns:dispatch-due if a minute has actually elapsed since it last ran
REM (Schedule::command(...)->everyMinute() in routes/console.php decides that,
REM not this script — schedule:run is safe to call more often than needed).
"%PHP_EXE%" artisan schedule:run >> "%LOG_FILE%" 2>&1

REM Drains whatever dispatch-due just queued (plus anything left from a slow
REM previous run) and exits cleanly — no long-lived process to keep alive,
REM Task Scheduler just calls this again next minute.
"%PHP_EXE%" artisan queue:work --stop-when-empty --tries=3 --max-time=55 >> "%LOG_FILE%" 2>&1

endlocal
