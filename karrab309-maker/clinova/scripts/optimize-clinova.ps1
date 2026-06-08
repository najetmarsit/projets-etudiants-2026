# Clinova — optimisation Laravel pour production (Windows / XAMPP)
$ErrorActionPreference = "Stop"
$root = Split-Path -Parent $PSScriptRoot
Set-Location $root

Write-Host ">> Composer autoload optimisé..." -ForegroundColor Cyan
composer dump-autoload -o

Write-Host ">> Cache config / routes / views..." -ForegroundColor Cyan
php artisan config:cache
php artisan route:cache
php artisan view:cache

Write-Host ">> Lien storage public..." -ForegroundColor Cyan
php artisan storage:link 2>$null

Write-Host "OK — Clinova prêt pour la production." -ForegroundColor Green
Write-Host "Astuce: définir CACHE_DRIVER=redis et QUEUE_CONNECTION=database dans .env si disponible."
