@echo off
chcp 65001 >nul
echo ========================================
echo   Medical Hub - Demarrage
echo ========================================
echo.

echo [1] Verification MySQL...
echo     Ouvrez XAMPP et demarrez MySQL si necessaire.
echo     Appuyez sur une touche quand MySQL est demarre...
pause >nul

echo.
echo [2] Demarrage de l'API Laravel (port 8000)...
start "API Laravel" cmd /k "cd /d %~dp0 && php artisan route:clear >nul 2>&1 && php artisan serve --host=0.0.0.0 --port=8000"

timeout /t 3 /nobreak >nul

echo.
echo [3] Demarrage d'Angular (port 4200)...
start "Angular" cmd /k "cd /d %~dp0angular-app && npm start"

echo.
echo ========================================
echo   Serveurs demarres !
echo   - API : http://127.0.0.1:8000
echo   - Site : http://localhost:4200
echo ========================================
echo.
echo Fermez les fenetres pour arreter.
pause
