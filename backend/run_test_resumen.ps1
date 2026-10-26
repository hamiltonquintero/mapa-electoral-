<# PowerShell script to run ImportCaldasResumTest with proper php.ini #>
$env:PHPRC = "$(Resolve-Path .\php.ini)"
php artisan test --filter=ImportCaldasResumTest
