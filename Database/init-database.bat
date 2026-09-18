@echo off

REM ==================================================
REM Se placer dans le dossier Database
REM ==================================================
cd /d "%~dp0"


REM ==================================================
REM Rechercher automatiquement mysql.exe dans Laragon
REM ==================================================
set "MYSQL="

for /d %%D in ("C:\laragon\bin\mysql\*") do (
    if exist "%%D\bin\mysql.exe" (
        set "MYSQL=%%D\bin\mysql.exe"
    )
)

REM Vérifie si MySQL a été trouvé
if not defined MYSQL (
    echo.
    echo ERREUR : mysql.exe est introuvable dans Laragon.
    echo Chemin recherche : C:\laragon\bin\mysql
    echo.
    pause
    exit /b 1
)


echo ========================================
echo Initialisation de la base Compte
echo ========================================

echo.
echo MySQL utilise :
echo %MYSQL%
echo.


REM ==================================================
REM 1 - Creation de la base
REM ==================================================
echo [1/2] Creation de la base...

"%MYSQL%" -u root < database.sql

IF ERRORLEVEL 1 (
    echo.
    echo ERREUR pendant la creation de la base.
    pause
    exit /b 1
)


REM ==================================================
REM 2 - Creation des tables
REM ==================================================
echo.
echo [2/2] Creation des tables...

"%MYSQL%" -u root compte < schema.sql

IF ERRORLEVEL 1 (
    echo.
    echo ERREUR pendant la creation des tables.
    pause
    exit /b 1
)


REM ==================================================
REM Termine
REM ==================================================
echo.
echo ========================================
echo Base de donnees initialisee avec succes !
echo ========================================
echo.

pause