#!/bin/sh
set -eu
php bin/migrate.php
if [ "${APP_DEMO:-0}" = "1" ]; then php bin/seed.php; fi
php bin/export-sales.php
python3 analytics/pipeline.py --input storage/exports/sales.csv --output storage/reports/sales.json
exec php -S 0.0.0.0:8000 -t public public/router.php
