<?php
declare(strict_types=1);
require dirname(__DIR__) . '/bootstrap.php';
use App\Core\Database as DB;
if (env('APP_ENV','local') !== 'local' || env('APP_DEMO','0') !== '1') throw new RuntimeException('Demo seeding requires APP_ENV=local and APP_DEMO=1.');
if ((int)DB::query('SELECT COUNT(*) FROM products')->fetchColumn()>0) { echo "Demo already present; no existing data modified.\n"; exit; }
$pdo=DB::connect(); $pdo->beginTransaction();
try {
    $password=env('DEMO_PASSWORD','Demo-Pulse-2026!'); $hash=password_hash($password,PASSWORD_DEFAULT);
    $users=[];
    foreach ([['Admin Demo','admin@commercepulse.test','admin'],['Alex Demo','alex@commercepulse.test','customer'],['Lou Demo','lou@commercepulse.test','customer'],['Sam Demo','sam@commercepulse.test','customer'],['Camille Demo','camille@commercepulse.test','customer']] as [$name,$email,$role]) {
        DB::query('INSERT INTO users (name,email,password_hash,role,created_at) VALUES (?,?,?,?,?)',[$name,$email,$hash,$role,gmdate('Y-m-d H:i:s')]); $users[]=(int)$pdo->lastInsertId();
    }
    $categories=[];
    foreach (['Équipement','Organisation','Lifestyle'] as $name) { DB::query('INSERT INTO categories (name) VALUES (?)',[$name]); $categories[]=(int)$pdo->lastInsertId(); }
    $products=[];
    $catalog=[
        [0,'Clavier Studio','Un clavier mécanique compact au toucher précis. Connexion USB-C et disposition AZERTY pour un bureau bien organisé.',8900,42,'keyboard'],
        [0,'Lampe Horizon','Éclairage LED réglable avec pied en aluminium. Une lumière chaleureuse pour tes sessions de travail du soir.',4900,28,'lamp'],
        [2,'Gourde Nomade','Gourde isotherme 600 ml en acier inoxydable. Garde ta boisson à portée de main au bureau comme en déplacement.',2400,64,'bottle'],
        [1,'Carnet Focus','Carnet A5 à couverture souple, 192 pages pointillées. Pour tes idées, tes schémas et tes prochaines étapes.',1400,76,'notebook'],
        [0,'Casque Session','Casque filaire confortable avec microphone intégré. Pensé pour les réunions et les journées de concentration.',6900,19,'headphones'],
        [2,'Sac Commuter','Sac de ville avec compartiment ordinateur 15 pouces et poches intérieures. Toile résistante et design sobre.',7900,22,'bag'],
        [1,'Support Lift','Support ordinateur en aluminium pliable. Surélève ton écran et libère de l’espace sur ton bureau.',3500,35,'stand'],
        [0,'Souris Motion','Souris sans fil ergonomique et silencieuse. Prise en main agréable, capteur réglable et recharge USB-C.',2900,48,'mouse'],
    ];
    foreach ($catalog as [$c,$n,$d,$p,$s,$i]) {
        DB::query('INSERT INTO products (category_id,name,description,price_cents,stock,illustration,created_at) VALUES (?,?,?,?,?,?,?)',[$categories[$c],$n,$d,$p,$s,$i,gmdate('Y-m-d H:i:s')]);
        $products[]=['id'=>(int)$pdo->lastInsertId(),'name'=>$n,'category'=>['Équipement','Organisation','Lifestyle'][$c],'price'=>$p];
    }
    mt_srand(42);
    for ($i=0;$i<90;$i++) {
        $date=(new DateTimeImmutable('today',new DateTimeZone('UTC')))->modify('-'.mt_rand(0,89).' days')->setTime(mt_rand(8,20),mt_rand(0,59))->format('Y-m-d H:i:s');
        $status=['shipped','shipped','shipped','processing','pending','cancelled'][$i%6];
        $chosen=array_rand($products,2); $items=[]; $total=0;
        foreach ($chosen as $index) { $q=mt_rand(1,2); $p=$products[$index]; $items[]=[$p,$q]; $total+=$p['price']*$q; }
        DB::query('INSERT INTO orders (user_id,status,total_cents,idempotency_key,created_at,updated_at) VALUES (?,?,?,?,?,?)',[$users[1+$i%4],$status,$total,hash('sha256','demo-'.$i),$date,$date]);
        $orderId=(int)$pdo->lastInsertId();
        foreach ($items as [$p,$q]) DB::query('INSERT INTO order_items (order_id,product_id,product_name,category_name,quantity,unit_price_cents) VALUES (?,?,?,?,?,?)',[$orderId,$p['id'],$p['name'],$p['category'],$q,$p['price']]);
    }
    $pdo->commit(); echo "Demo seeded: 8 products, 5 fictitious users, 90 historical orders. Current stocks are the post-history opening balance.\n";
} catch (Throwable $e) { $pdo->rollBack(); throw $e; }
