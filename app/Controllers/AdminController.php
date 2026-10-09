<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\{Database as DB,HttpException,Response,Session};
use App\Domain\ProductInput;
use App\Services\{Catalog,Orders,SalesExport};
final class AdminController
{
    public function dashboard(): void
    {
        $stats=['revenue'=>(int)DB::query("SELECT COALESCE(SUM(total_cents),0) FROM orders WHERE status<>'cancelled'")->fetchColumn(),'orders'=>(int)DB::query('SELECT COUNT(*) FROM orders')->fetchColumn(),'pending'=>(int)DB::query("SELECT COUNT(*) FROM orders WHERE status IN ('pending','processing')")->fetchColumn(),'lowStock'=>(int)DB::query('SELECT COUNT(*) FROM products WHERE active=1 AND stock<10')->fetchColumn()];
        $recent=DB::query('SELECT o.*,u.name customer_name FROM orders o JOIN users u ON u.id=o.user_id ORDER BY o.id DESC LIMIT 6')->fetchAll();
        $low=DB::query('SELECT * FROM products WHERE active=1 ORDER BY stock ASC LIMIT 4')->fetchAll();
        Response::view('admin/dashboard',['title'=>'Vue d’ensemble','stats'=>$stats,'recent'=>$recent,'low'=>$low],true);
    }
    public function products(): void { $c=new Catalog(); Response::view('admin/products',$c->search($_GET,true)+['title'=>'Produits','categories'=>$c->categories()],true); }
    public function productForm(int $id=0): void
    {
        $c=new Catalog(); $product=$id ? $c->find($id,true) : null;
        if ($id && !$product) throw new HttpException(404,'Produit introuvable.');
        Response::view('admin/product-form',['title'=>$id?'Modifier le produit':'Nouveau produit','product'=>$product,'categories'=>$c->categories()],true);
    }
    public function saveProduct(int $id=0): void
    {
        $c=new Catalog(); if ($id && !$c->find($id,true)) throw new HttpException(404,'Produit introuvable.');
        try {
            $data=ProductInput::validate($_POST);
            if (!DB::query('SELECT id FROM categories WHERE id=?',[$data['category_id']])->fetchColumn()) throw new \DomainException('Catégorie introuvable.');
            if ($id) DB::query('UPDATE products SET name=?,price_cents=?,stock=?,category_id=?,description=?,illustration=? WHERE id=?',[...array_values($data),$id]);
            else DB::query('INSERT INTO products (name,price_cents,stock,category_id,description,illustration,created_at) VALUES (?,?,?,?,?,?,?)',[...array_values($data),gmdate('Y-m-d H:i:s')]);
            Session::flash('Produit enregistré.'); Response::redirect('/admin/products');
        } catch (\DomainException $e) {
            $product=['id'=>$id,'name'=>$_POST['name'] ?? '', 'description'=>$_POST['description'] ?? '', 'price_raw'=>$_POST['price'] ?? '', 'stock'=>$_POST['stock'] ?? 0, 'category_id'=>$_POST['category_id'] ?? 0,'illustration'=>$_POST['illustration'] ?? 'notebook'];
            Response::view('admin/product-form',['title'=>'Enregistrer le produit','product'=>$product,'categories'=>$c->categories(),'error'=>$e->getMessage()],true);
        }
    }
    public function toggleProduct(int $id): void
    {
        if (!(new Catalog())->find($id,true)) throw new HttpException(404,'Produit introuvable.');
        DB::query('UPDATE products SET active=1-active WHERE id=?',[$id]); Session::flash('Visibilité du produit mise à jour.'); Response::redirect('/admin/products');
    }
    public function orders(): void
    {
        $status=(string)($_GET['status'] ?? ''); $params=[]; $where='';
        if (in_array($status,['pending','processing','shipped','cancelled'],true)) { $where=' WHERE o.status=?'; $params[]=$status; }
        $orders=DB::query('SELECT o.*,u.name customer_name FROM orders o JOIN users u ON u.id=o.user_id'.$where.' ORDER BY o.id DESC LIMIT 100',$params)->fetchAll();
        Response::view('admin/orders',['title'=>'Commandes','orders'=>$orders,'status'=>$status],true);
    }
    public function updateOrder(int $id): void
    {
        try { (new Orders())->transition($id,(string)($_POST['status'] ?? '')); Session::flash('Statut de commande mis à jour.'); }
        catch (\DomainException $e) { Session::flash($e->getMessage(),'error'); }
        Response::redirect('/orders/'.$id);
    }
    public function analytics(): void
    {
        $path=BASE_PATH.'/storage/reports/sales.json'; $report=is_file($path) ? json_decode(file_get_contents($path),true,512,JSON_THROW_ON_ERROR) : null;
        Response::view('admin/analytics',['title'=>'Analyse des ventes','report'=>$report],true);
    }
    public function export(): void
    {
        header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename="pulse-sales.csv"');
        $out=fopen('php://output','wb'); (new SalesExport())->write($out); fclose($out);
    }
    public function refresh(): void
    {
        // Fixed executables and arguments: request parameters are never passed to a shell.
        $commands=[[PHP_BINARY,BASE_PATH.'/bin/export-sales.php'],[env('PYTHON_BINARY','python3'),BASE_PATH.'/analytics/pipeline.py','--input',BASE_PATH.'/storage/exports/sales.csv','--output',BASE_PATH.'/storage/reports/sales.json']];
        foreach ($commands as $command) {
            $p=proc_open($command,[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,BASE_PATH);
            if (!is_resource($p)) throw new HttpException(503,'L’analyse est indisponible.');
            fclose($pipes[0]); $out=stream_get_contents($pipes[1]); $error=stream_get_contents($pipes[2]); fclose($pipes[1]); fclose($pipes[2]);
            if (proc_close($p)!==0) { error_log($error); throw new HttpException(503,'L’analyse a échoué. Le dernier rapport reste disponible.'); }
        }
        Session::flash('Les données de vente et le rapport ont été actualisés.'); Response::redirect('/admin/analytics');
    }
}
