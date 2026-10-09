<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\{Auth,Database as DB,HttpException,Response,Session};
use App\Services\{Catalog,Cart,Orders};
final class ShopController
{
    public function index(): void { $c=new Catalog(); Response::view('catalog',$c->search($_GET)+['categories'=>$c->categories(),'title'=>'Les essentiels du quotidien']); }
    public function product(int $id): void { $product=(new Catalog())->find($id); if (!$product) throw new HttpException(404,'Produit introuvable.'); Response::view('product',['title'=>$product['name'],'product'=>$product]); }
    public function cart(): void { $cart=new Cart(); Response::view('cart',['title'=>'Ton panier','items'=>$cart->items(),'total'=>$cart->total(),'checkoutKey'=>$cart->key()]); }
    public function add(): void
    {
        try { (new Cart())->put((int)($_POST['product_id'] ?? 0),(int)($_POST['quantity'] ?? 1),true); Session::flash('Le produit a été ajouté à ton panier.'); }
        catch (\DomainException $e) { Session::flash($e->getMessage(),'error'); }
        Response::redirect('/cart');
    }
    public function update(): void
    {
        try { (new Cart())->put((int)($_POST['product_id'] ?? 0),(int)($_POST['quantity'] ?? 0)); }
        catch (\DomainException $e) { Session::flash($e->getMessage(),'error'); }
        Response::redirect('/cart');
    }
    public function checkout(): void
    {
        $user=Auth::requireUser(); $cart=new Cart(); $key=(string)($_POST['checkout_key'] ?? '');
        if (!hash_equals($cart->key(),$key)) throw new HttpException(409,'Le panier a changé. Recharge-le avant de commander.');
        try { $id=(new Orders())->place((int)$user['id'],$_SESSION['cart'] ?? [],$key); $cart->clear(); Session::flash('Commande enregistrée. Aucun paiement n’a été effectué.'); Response::redirect('/orders/'.$id); }
        catch (\DomainException $e) { Session::flash($e->getMessage(),'error'); Response::redirect('/cart'); }
    }
    public function account(): void
    {
        $user=Auth::requireUser(); $orders=DB::query('SELECT * FROM orders WHERE user_id=? ORDER BY id DESC',[(int)$user['id']])->fetchAll();
        Response::view('account',['title'=>'Mon espace','orders'=>$orders]);
    }
    public function order(int $id): void
    {
        $user=Auth::requireUser(); $order=(new Orders())->find($id);
        if (!$order || ((int)$order['user_id']!==(int)$user['id'] && $user['role']!=='admin')) throw new HttpException(404,'Commande introuvable.');
        Response::view('order',['title'=>'Commande #'.str_pad((string)$id,4,'0',STR_PAD_LEFT),'order'=>$order],$user['role']==='admin');
    }
    public function productsApi(): void
    {
        $result=(new Catalog())->search($_GET);
        Response::json(['data'=>array_map(static fn($p)=>['id'=>(int)$p['id'],'name'=>$p['name'],'description'=>$p['description'],'category'=>$p['category_name'],'price_cents'=>(int)$p['price_cents'],'stock'=>(int)$p['stock']],$result['products']),'meta'=>['page'=>$result['page'],'pages'=>$result['pages'],'total'=>$result['total'],'currency'=>'EUR']]);
    }
}
