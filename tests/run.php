<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
use App\Core\Database as DB;
use App\Domain\{ProductInput,OrderStatus};
use App\Services\Orders;
$checks=0;
function check(bool $value,string $label): void { global $checks; if (!$value) throw new RuntimeException('FAIL: '.$label); $checks++; echo 'PASS: '.$label."\n"; }
function rejects(callable $fn,string $label): void { try { $fn(); } catch (DomainException $e) { check(true,$label); return; } throw new RuntimeException('FAIL: '.$label); }
$input=['name'=>'Test product','description'=>'A meaningful description','price'=>'19,95','stock'=>'3','category_id'=>'1'];
check(ProductInput::validate($input)['price_cents']===1995,'decimal euro input produces exact integer cents');
rejects(fn()=>ProductInput::validate(array_merge($input,['price'=>'1e3'])),'scientific notation rejected');
rejects(fn()=>ProductInput::validate(array_merge($input,['price'=>'19.999'])),'fractional cent rejected');
rejects(fn()=>ProductInput::validate(array_merge($input,['stock'=>'-1'])),'negative stock rejected');
rejects(fn()=>ProductInput::validate(array_merge($input,['category_id'=>'0'])),'invalid category rejected');
check(OrderStatus::allows('pending','cancelled'),'pending order can be cancelled');
check(!OrderStatus::allows('shipped','cancelled'),'shipped order cannot be cancelled');
check(!OrderStatus::allows('cancelled','processing'),'cancelled order cannot resume');
if (in_array('--database',$argv,true)) {
    $driver=DB::connect()->getAttribute(PDO::ATTR_DRIVER_NAME);
    $safe=$driver==='sqlite' ? str_ends_with(env('DB_SQLITE_PATH'),'.test.sqlite') : str_ends_with(env('DB_DATABASE'),'_test');
    if (env('APP_ENV')!=='testing' || !$safe) throw new RuntimeException('Database tests require APP_ENV=testing and a dedicated *_test / *.test.sqlite database.');
    $stamp=bin2hex(random_bytes(5)); $userIds=[]; $productIds=[]; $categoryId=0;
    try {
        DB::query('INSERT INTO users (name,email,password_hash,role,created_at) VALUES (?,?,?,?,?)',['Test','test-'.$stamp.'@commercepulse.test',password_hash('Test-Only-2026!',PASSWORD_DEFAULT),'customer',gmdate('Y-m-d H:i:s')]);
        $userId=(int)DB::connect()->lastInsertId(); $userIds[]=$userId;
        DB::query('INSERT INTO categories (name) VALUES (?)',['Test-'.$stamp]); $categoryId=(int)DB::connect()->lastInsertId();
        foreach ([['Original product',3],['Scarce product',1]] as [$name,$stock]) {
            DB::query('INSERT INTO products (name,category_id,description,price_cents,stock,illustration,created_at) VALUES (?,?,?,?,?,?,?)',[$name,$categoryId,'A product for integration tests',1995,$stock,'notebook',gmdate('Y-m-d H:i:s')]); $productIds[]=(int)DB::connect()->lastInsertId();
        }
        [$p,$scarce]=$productIds; $service=new Orders(); $key=hash('sha256','first-'.$stamp);
        $orderId=$service->place($userId,[$p=>2],$key);
        check((int)$service->find($orderId)['total_cents']===3990,'order prices come from database');
        check((int)DB::query('SELECT stock FROM products WHERE id=?',[$p])->fetchColumn()===1,'checkout decrements stock');
        check($service->place($userId,[],$key)===$orderId,'duplicate confirmation returns the existing order');
        check((int)DB::query('SELECT stock FROM products WHERE id=?',[$p])->fetchColumn()===1,'duplicate confirmation does not consume stock');
        rejects(fn()=>$service->place($userId,[$p=>1,$scarce=>2],hash('sha256','fail-'.$stamp)),'one unavailable item rejects the entire basket');
        check((int)DB::query('SELECT stock FROM products WHERE id=?',[$p])->fetchColumn()===1,'failed basket leaves all stock unchanged');
        DB::query('UPDATE products SET name=?,price_cents=? WHERE id=?',['Renamed',2995,$p]);
        check($service->find($orderId)['items'][0]['product_name']==='Original product','historical order retains its product name');
        check((int)$service->find($orderId)['items'][0]['unit_price_cents']===1995,'historical order retains its original price');
        $service->transition($orderId,'cancelled');
        check((int)DB::query('SELECT stock FROM products WHERE id=?',[$p])->fetchColumn()===3,'cancellation restores stock');
        rejects(fn()=>$service->transition($orderId,'cancelled'),'second cancellation rejected');
        check((int)DB::query('SELECT stock FROM products WHERE id=?',[$p])->fetchColumn()===3,'stock restoration occurs exactly once');
        rejects(fn()=>$service->place($userId,[$p=>-1],hash('sha256','negative-'.$stamp)),'negative checkout quantity rejected');
    } finally {
        foreach ($userIds as $id) { DB::query('DELETE FROM order_items WHERE order_id IN (SELECT id FROM orders WHERE user_id=?)',[$id]); DB::query('DELETE FROM orders WHERE user_id=?',[$id]); DB::query('DELETE FROM users WHERE id=?',[$id]); }
        foreach ($productIds as $id) DB::query('DELETE FROM products WHERE id=?',[$id]);
        if ($categoryId) DB::query('DELETE FROM categories WHERE id=?',[$categoryId]);
    }
}
echo "$checks assertions passed.\n";
