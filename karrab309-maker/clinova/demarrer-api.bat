@echo off
title API Medical - Laravel
cd /d "%~dp0"
echo Demarrage de l'API Laravel sur http://127.0.0.1:8000 (et reseau local : http://VOTRE_IP:8000)
echo.
echo Flutter telephone : php artisan serve ecoute 0.0.0.0 pour que le Wi-Fi puisse joindre l'API.
echo L'app Chrome / Angular : http://localhost:4200 en proxy vers l'API.
echo.
echo [Cache routes] Evite les 404 sur de nouvelles routes (ex. lab analytics^)...
php artisan route:clear >nul 2>&1
php artisan serve --host=0.0.0.0 --port=8000
pause
