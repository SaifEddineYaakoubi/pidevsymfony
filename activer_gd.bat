@echo off
chcp 65001 >nul
echo ========================================
echo   Activation de l'extension GD pour PHP
echo ========================================
echo.

set PHP_INI=C:\xampp\php\php.ini

if not exist "%PHP_INI%" (
    echo ❌ ERREUR: Le fichier php.ini n'existe pas
    echo    Chemin: %PHP_INI%
    pause
    exit /b 1
)

echo ✓ Fichier php.ini trouvé
echo.

echo Création d'une sauvegarde...
copy "%PHP_INI%" "%PHP_INI%.backup_%date:~-4%%date:~3,2%%date:~0,2%_%time:~0,2%%time:~3,2%%time:~6,2%" >nul
echo ✓ Sauvegarde créée
echo.

echo Activation de l'extension GD...
powershell -Command "(Get-Content '%PHP_INI%') -replace '^\s*;extension=gd\s*$', 'extension=gd' | Set-Content '%PHP_INI%' -Encoding UTF8"
echo ✓ Modification effectuée
echo.

echo ========================================
echo   IMPORTANT: REDÉMARRER APACHE
echo ========================================
echo.
echo 1. Ouvrez le Panneau de Contrôle XAMPP
echo 2. Cliquez sur "Stop" pour Apache
echo 3. Cliquez sur "Start" pour Apache
echo.
echo Appuyez sur une touche pour vérifier l'état de GD...
pause >nul

echo.
echo Vérification de GD...
php -m | findstr gd
if %errorlevel% equ 0 (
    echo.
    echo ✅ GD est ACTIVÉ!
) else (
    echo.
    echo ❌ GD n'est PAS encore activé
    echo    → Redémarrez Apache dans XAMPP
)

echo.
pause
