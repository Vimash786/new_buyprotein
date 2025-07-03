Write-Host "WARNING: This will DELETE ALL DATA in your server database!" -ForegroundColor Red
Write-Host "Database: u820220146_buy_protein" -ForegroundColor Yellow
Write-Host "Host: sql917.main-hosting.eu" -ForegroundColor Yellow
Write-Host ""
Write-Host "This action cannot be undone!" -ForegroundColor Red
Write-Host ""

$confirm = Read-Host "Are you sure you want to proceed? (type 'YES' to confirm)"

if ($confirm -ne "YES") {
    Write-Host "Operation cancelled." -ForegroundColor Green
    Read-Host "Press Enter to exit..."
    exit 1
}

Write-Host ""
Write-Host "Step 1: Dropping all tables and recreating database structure..." -ForegroundColor Yellow
php artisan migrate:fresh --force

if ($LASTEXITCODE -ne 0) {
    Write-Host "Error occurred during migration. Stopping." -ForegroundColor Red
    Read-Host "Press Enter to exit..."
    exit 1
}

Write-Host ""
Write-Host "Step 2: Seeding fresh data with images..." -ForegroundColor Yellow
php artisan db:seed --force

if ($LASTEXITCODE -ne 0) {
    Write-Host "Error occurred during seeding. Database structure is ready but seeding failed." -ForegroundColor Red
    Read-Host "Press Enter to exit..."
    exit 1
}

Write-Host ""
Write-Host "Database has been completely reset and reseeded!" -ForegroundColor Green
Write-Host ""
Write-Host "New data includes:" -ForegroundColor Cyan
Write-Host "- Categories with images from storage/categories/" -ForegroundColor Cyan
Write-Host "- Products with thumbnails from storage/products/thumbnails/" -ForegroundColor Cyan
Write-Host "- Product images from storage/products/images/" -ForegroundColor Cyan
Write-Host "- Sellers and orders data" -ForegroundColor Cyan
Write-Host "- Fresh users data" -ForegroundColor Cyan
Write-Host ""
Read-Host "Press Enter to continue..."
