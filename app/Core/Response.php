<?php
declare(strict_types=1);
namespace App\Core;
final class Response
{
    public static function redirect(string $path): never { header('Location: ' . $path, true, 303); exit; }
    public static function json(array $data, int $status = 200): void { http_response_code($status); header('Content-Type: application/json; charset=utf-8'); echo json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE); }
    public static function view(string $view, array $data = [], bool $admin = false): void
    {
        extract($data, EXTR_SKIP);
        $user = Auth::user();
        $path = rtrim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/') ?: '/';
        $flash = Session::takeFlash();
        ob_start(); require BASE_PATH . '/views/' . $view . '.php'; $content = ob_get_clean();
        require BASE_PATH . '/views/layout.php';
    }
}
