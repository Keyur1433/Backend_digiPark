@echo off
echo Checking for processes using ports 8000-8010...
echo.

for /L %%i in (8000,1,8010) do (
    echo Checking port %%i:
    netstat -ano | findstr :%%i
    echo.
)

echo.
echo To kill a process, use: taskkill /F /PID [PID]
echo.
pause 