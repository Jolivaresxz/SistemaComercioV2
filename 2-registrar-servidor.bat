@echo off
setlocal
cd /d "%~dp0"
if not exist "config.local.php" (
    echo Primero ejecuta 1-configurar-servidor.bat
    pause
    exit /b 1
)
if not exist "C:\xampp\php\php.exe" (
    echo No encuentro PHP en C:\xampp\php\php.exe
    pause
    exit /b 1
)
echo Registrando este PC. Deja esta ventana abierta.
"C:\xampp\php\php.exe" bin\instance.php watch
echo.
echo El registro se detuvo. Revisa el error mostrado arriba.
pause
