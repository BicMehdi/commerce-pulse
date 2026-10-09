<?php
declare(strict_types=1);
namespace App\Services;
use App\Core\Database as DB;
use App\Domain\OrderStatus;
final class Orders
{
    public function place(int $userId, array $cart, string $key): int
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $key)) throw new \DomainException('Commande invalide.');
        $pdo = DB::connect();
        $pdo->beginTransaction();
        try {
            // Lock the customer to serialize retries for the same account.
            DB::query('SELECT id FROM users WHERE id=?'.DB::lock(), [$userId])->fetch();
            $existing = DB::query('SELECT id FROM orders WHERE user_id=? AND idempotency_key=?',[$userId,$key])->fetchColumn();
            if ($existing) { $pdo->commit(); return (int)$existing; }
            if (!$cart) throw new \DomainException('Ton panier est vide.');
            ksort($cart, SORT_NUMERIC); // Stable lock order reduces deadlocks across overlapping baskets.
            $items=[]; $total=0;
            foreach ($cart as $id=>$quantity) {
                if (!is_int($quantity) || $quantity < 1 || $quantity > 10) throw new \DomainException('Quantité invalide.');
                $product = DB::query('SELECT * FROM products WHERE id=?'.DB::lock(),[(int)$id])->fetch();
                if (!$product || !$product['active'] || (int)$product['stock'] < $quantity) throw new \DomainException('Stock insuffisant : actualise ton panier avant de commander.');
                $items[] = [$product,$quantity]; $total += (int)$product['price_cents']*$quantity;
            }
            DB::query('INSERT INTO orders (user_id,status,total_cents,idempotency_key,created_at,updated_at) VALUES (?,?,?,?,?,?)',[$userId,'pending',$total,$key,gmdate('Y-m-d H:i:s'),gmdate('Y-m-d H:i:s')]);
            $orderId = (int)$pdo->lastInsertId();
            foreach ($items as [$p,$q]) {
                DB::query('INSERT INTO order_items (order_id,product_id,product_name,category_name,quantity,unit_price_cents) VALUES (?,?,?,?,?,?)',[$orderId,(int)$p['id'],$p['name'],(string)DB::query('SELECT name FROM categories WHERE id=?',[(int)$p['category_id']])->fetchColumn(),$q,(int)$p['price_cents']]);
                DB::query('UPDATE products SET stock=stock-? WHERE id=?',[$q,(int)$p['id']]);
            }
            $pdo->commit(); return $orderId;
        } catch (\Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
    }
    public function transition(int $id, string $status): void
    {
        $pdo=DB::connect(); $pdo->beginTransaction();
        try {
            $order=DB::query('SELECT * FROM orders WHERE id=?'.DB::lock(),[$id])->fetch();
            if (!$order || !OrderStatus::allows($order['status'],$status)) throw new \DomainException('Ce changement de statut n’est pas autorisé.');
            if ($status==='cancelled') {
                $items=DB::query('SELECT product_id,quantity FROM order_items WHERE order_id=? ORDER BY product_id',[$id])->fetchAll();
                foreach ($items as $item) DB::query('UPDATE products SET stock=stock+? WHERE id=?',[(int)$item['quantity'],(int)$item['product_id']]);
            }
            DB::query('UPDATE orders SET status=?,updated_at=? WHERE id=?',[$status,gmdate('Y-m-d H:i:s'),$id]);
            $pdo->commit();
        } catch (\Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
    }
    public function find(int $id): ?array
    {
        $order=DB::query('SELECT o.*,u.name customer_name,u.email customer_email FROM orders o JOIN users u ON u.id=o.user_id WHERE o.id=?',[$id])->fetch();
        if (!$order) return null;
        return $order + ['items'=>DB::query('SELECT * FROM order_items WHERE order_id=? ORDER BY id',[$id])->fetchAll()];
    }
}
