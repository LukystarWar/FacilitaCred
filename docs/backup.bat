@echo off
REM Script de Backup para Windows - FacilitaCred

set BACKUP_DIR=C:\backups\facilita_cred
set DATE=%date:~-4,4%%date:~-7,2%%date:~-10,2%_%time:~0,2%%time:~3,2%%time:~6,2%
set DATE=%DATE: =0%
set MYSQL_PATH=C:\xampp\mysql\bin
set DB_NAME=facilita_cred

if not exist "%BACKUP_DIR%" mkdir "%BACKUP_DIR%"

echo Iniciando backup do banco de dados...
"%MYSQL_PATH%\mysqldump.exe" -u root %DB_NAME% > "%BACKUP_DIR%\facilita_cred_%DATE%.sql"

if %errorlevel% equ 0 (
    echo Backup concluido com sucesso: facilita_cred_%DATE%.sql
) else (
    echo ERRO: Falha no backup do banco de dados
    exit /b 1
)

echo.
echo Backup completo!
pause
