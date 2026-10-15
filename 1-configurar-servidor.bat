@echo off
setlocal
cd /d "%~dp0"
if not exist "C:\xampp\php\php.exe" (
    echo No encuentro PHP en C:\xampp\php\php.exe
    pause
    exit /b 1
)
"C:\xampp\php\php.exe" bin\setup.php
echo.
pause
