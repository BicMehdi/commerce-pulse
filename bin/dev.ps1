param([string]$Php = 'php', [string]$Python = 'python', [int]$Port = 8090)
$ErrorActionPreference = 'Stop'
$taskRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
Set-Location -LiteralPath $taskRoot
$env:DB_DRIVER = 'sqlite'
$env:DB_SQLITE_PATH = Join-Path $taskRoot 'storage\local.sqlite'
$env:APP_ENV = 'local'
$env:APP_DEMO = '1'
$env:PYTHON_BINARY = (Get-Command $Python -ErrorAction Stop).Source
& $Php bin/migrate.php
if ($LASTEXITCODE) { throw 'Migration failed. Check that PHP has PDO SQLite enabled.' }
& $Php bin/seed.php
if ($LASTEXITCODE) { throw 'Seed failed' }
& $Php bin/export-sales.php
if ($LASTEXITCODE) { throw 'Export failed' }
& $Python analytics/pipeline.py --input storage/exports/sales.csv --output storage/reports/sales.json
if ($LASTEXITCODE) { throw 'Analysis failed' }
Write-Host "Commerce Pulse: http://127.0.0.1:$Port"
& $Php -S "127.0.0.1:$Port" -t public public/router.php
