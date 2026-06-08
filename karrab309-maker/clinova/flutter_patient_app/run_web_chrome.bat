@echo off
chcp 65001 >nul
cd /d "%~dp0"
echo Clinova Flutter — Chrome (DDS désactivé)
echo Si "flutter run -d chrome" plante avec DartDevelopmentServiceException, ce script utilise --no-dds.
echo.
flutter run -d chrome --no-dds
