<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
use App\Core\{Session,Auth,Response,HttpException,Database};
use App\Controllers\{AuthController,ShopController,AdminController};
Session::start();
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:; base-uri 'self'; frame-ancestors 'none'; form-action 'self'");
header('X-Content-Type-Options: nosniff'); header('Referrer-Policy: strict-origin-when-cross-origin');
header('Cache-Control: no-store');
$path=rtrim(rawurldecode(parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH)),'/') ?: '/';
$method=$_SERVER['REQUEST_METHOD'];
$routes=[
 ['GET','/',ShopController::class,'index'],['GET','/products/(\d+)',ShopController::class,'product'],
 ['GET','/cart',ShopController::class,'cart'],['POST','/cart/add',ShopController::class,'add'],['POST','/cart/update',ShopController::class,'update'],['POST','/checkout',ShopController::class,'checkout'],
 ['GET','/login',AuthController::class,'loginForm'],['POST','/login',AuthController::class,'login'],['GET','/register',AuthController::class,'registerForm'],['POST','/register',AuthController::class,'register'],['POST','/logout',AuthController::class,'logout'],
 ['GET','/account',ShopController::class,'account'],['GET','/orders/(\d+)',ShopController::class,'order'],['GET','/api/products',ShopController::class,'productsApi'],
 ['GET','/admin',AdminController::class,'dashboard'],['GET','/admin/products',AdminController::class,'products'],['GET','/admin/products/new',AdminController::class,'productForm'],['POST','/admin/products',AdminController::class,'saveProduct'],
 ['GET','/admin/products/(\d+)/edit',AdminController::class,'productForm'],['POST','/admin/products/(\d+)',AdminController::class,'saveProduct'],['POST','/admin/products/(\d+)/toggle',AdminController::class,'toggleProduct'],
 ['GET','/admin/orders',AdminController::class,'orders'],['POST','/admin/orders/(\d+)/status',AdminController::class,'updateOrder'],
 ['GET','/admin/analytics',AdminController::class,'analytics'],['POST','/admin/analytics/refresh',AdminController::class,'refresh'],['GET','/admin/sales.csv',AdminController::class,'export'],
];
try {
    if ($path==='/health' && $method==='GET') { Database::query('SELECT 1'); Response::json(['status'=>'ok','database'=>env('DB_DRIVER','mysql')]); exit; }
    $pathFound=false;
    foreach ($routes as [$verb,$pattern,$controller,$action]) {
        if (!preg_match('#^'.$pattern.'$#',$path,$matches)) continue;
        $pathFound=true; if ($verb!==$method) continue;
        if (str_starts_with($path,'/admin')) Auth::requireAdmin();
        if ($method==='POST') Session::verify();
        (new $controller())->$action(...array_map('intval',array_slice($matches,1))); exit;
    }
    throw new HttpException($pathFound ? 405 : 404,$pathFound ? 'Méthode non autorisée.' : 'Cette page n’existe pas.');
} catch (Throwable $error) {
    $status=$error instanceof HttpException ? $error->status : 500;
    if ($status===500) error_log((string)$error);
    $message=$status===500 ? 'Le service est momentanément indisponible. Réessaie dans un instant.' : $error->getMessage();
    if (str_starts_with($path,'/api/') || $path==='/health') Response::json(['error'=>$message],$status);
    else { http_response_code($status); Response::view('error',['title'=>'Une petite pause','status'=>$status,'message'=>$message]); }
}
