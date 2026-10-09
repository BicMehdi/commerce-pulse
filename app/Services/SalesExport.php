<?php
declare(strict_types=1);
namespace App\Services;
use App\Core\Database as DB;
final class SalesExport
{
    public const HEADERS=['order_id','customer_id','status','created_at','order_total_cents','line_id','product_id','product_name','category_name','quantity','unit_price_cents'];
    public function write($stream): int
    {
        fputcsv($stream,self::HEADERS,',','"',''); $count=0;
        $pdo=DB::connect(); $pdo->beginTransaction();
        try {
            $stmt=DB::query('SELECT o.id order_id,o.user_id customer_id,o.status,o.created_at,o.total_cents order_total_cents,i.id line_id,i.product_id,i.product_name,i.category_name,i.quantity,i.unit_price_cents FROM orders o JOIN order_items i ON i.order_id=o.id ORDER BY o.id,i.id');
            while ($row=$stmt->fetch()) { fputcsv($stream,array_values($row),',','"',''); $count++; }
            $pdo->commit(); return $count;
        } catch (\Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
    }
}
