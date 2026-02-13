@echo off
echo Running Purchase Request Migrations...
echo.

REM Try different common PHP paths
if exist "C:\laragon\bin\php\php.exe" (
    C:\laragon\bin\php\php.exe artisan migrate
    goto :end
)

if exist "C:\xampp\php\php.exe" (
    C:\xampp\php\php.exe artisan migrate
    goto :end
)

REM Try Herd
if exist "%LOCALAPPDATA%\herd\bin\php.exe" (
    "%LOCALAPPDATA%\herd\bin\php.exe" artisan migrate
    goto :end
)

REM Try system PHP
php artisan migrate 2>nul
if %errorlevel% equ 0 goto :end

echo.
echo ERROR: Could not find PHP. Please run the migration manually:
echo   php artisan migrate
echo.

:end
echo.
echo Press any key to exit...
pause >nul
