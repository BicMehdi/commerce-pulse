<?php
declare(strict_types=1);
require dirname(__DIR__) . '/bootstrap.php';
use App\Core\Database as DB;
$driver=DB::connect()->getAttribute(PDO::ATTR_DRIVER_NAME);
DB::connect()->exec('CREATE TABLE IF NOT EXISTS migrations (version VARCHAR(80) PRIMARY KEY, checksum VARCHAR(64) NOT NULL, applied_at VARCHAR(30) NOT NULL)');
$version='001_initial_'.$driver;
$sql=file_get_contents(BASE_PATH.'/database/schema.'.$driver.'.sql');
$checksum=hash('sha256',$sql);
$previous=DB::query('SELECT checksum FROM migrations WHERE version=?',[$version])->fetchColumn();
if ($previous) {
    if ($previous!==$checksum) throw new RuntimeException('Applied schema changed: add a new migration instead.');
    echo "Schema already current.\n"; exit;
}
// DDL is restart-safe (IF NOT EXISTS); MySQL implicitly commits DDL.
DB::connect()->exec($sql);
DB::query('INSERT INTO migrations VALUES (?,?,?)',[$version,$checksum,gmdate('c')]);
echo "Applied $version.\n";
