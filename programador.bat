@echo off
REM Ejecuta el programador de Laravel (descarga de checadas y calculo de asistencia).
REM Funciona desde cualquier carpeta: usa la ubicacion de este mismo archivo.
REM Registrar en el Programador de tareas de Windows para que corra cada minuto:
REM   schtasks /create /sc minute /mo 1 /tn "ControlAsistencia" /tr "C:\xampp\htdocs\controlasistencia\programador.bat" /ru SYSTEM /f
cd /d "%~dp0"
C:\xampp\php\php.exe artisan schedule:run >> storage\logs\programador.log 2>&1
