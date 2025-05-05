@echo off
echo Checking for processes using port 8000...

:: Check if port 8000 is in use and kill the process
for /f "tokens=5" %%a in ('netstat -ano ^| findstr :8000') do (
    if not "%%a" == "" (
        echo Found process using port 8000 with PID: %%a
        echo Attempting to kill process...
        taskkill /F /PID %%a
        echo Process terminated.
    )
)

:: Allow user to select port or use default
set PORT=8000
set /p PORT_INPUT="Enter port number (default: 8000): "
if not "%PORT_INPUT%" == "" set PORT=%PORT_INPUT%

echo Starting Laravel development server with custom PHP configuration on port %PORT%...
php -c custom-php.ini artisan serve --port=%PORT%

:: If server fails to start, offer alternative port
if %ERRORLEVEL% neq 0 (
    echo Failed to start server on port %PORT%. 
    echo Trying alternative port 9000...
    php -c custom-php.ini artisan serve --port=9000
)