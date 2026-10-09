<?php
declare(strict_types=1);
require dirname(__DIR__) . '/bootstrap.php';
$path=BASE_PATH.'/storage/exports/sales.csv';
if (!is_dir(dirname($path))) mkdir(dirname($path),0770,true);
$tmp=tempnam(dirname($path),'export-'); $stream=fopen($tmp,'wb');
try { $count=(new App\Services\SalesExport())->write($stream); fclose($stream); if (!rename($tmp,$path)) throw new RuntimeException('Cannot publish CSV'); }
catch (Throwable $e) { if (is_resource($stream)) fclose($stream); @unlink($tmp); throw $e; }
echo "Exported $count lines to storage/exports/sales.csv (no name/email).\n";
