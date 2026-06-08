@echo off
chcp 65001 >nul
cd /d "%~dp0"
echo.
echo === Clinova — Android (telephone / tablette sur le Wi-Fi) ===
echo 1. Remplacez 192.168.x.x par l'IPv4 de votre PC (ipconfig).
echo 2. Lancez l'API : php artisan serve --host=0.0.0.0 --port=8000
echo.
set /p LANIP="Adresse IP du PC (ex. 192.168.1.20) : "
if "%LANIP%"=="" (
  echo Annulé.
  exit /b 1
)
flutter run -d android --dart-define=API_LAN_HOST=%LANIP%
