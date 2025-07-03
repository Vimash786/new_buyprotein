@echo off
echo WARNING: This will DELETE ALL DATA in your server database!
echo Database: u820220146_buy_protein
echo Host: sql917.main-hosting.eu
echo.
echo This action cannot be undone!
echo.
set /p confirm="Are you sure you want to proceed? (type 'YES' to confirm): "

if not "%confirm%"=="YES" (
    echo Operation cancelled.
    pause
    exit /b 1
)

echo.
echo Step 1: Dropping all tables and recreating database structure...
php artisan migrate:fresh --force

echo.
echo Step 2: Seeding fresh data with images...
php artisan db:seed --force

echo.
echo Database has been completely reset and reseeded!
echo.
echo New data includes:
echo - Categories with images from storage/categories/
echo - Products with thumbnails from storage/products/thumbnails/
echo - Product images from storage/products/images/
echo - Sellers and orders data
echo - Fresh users data
echo.
pause
