<?php
declare(strict_types=1);
namespace App\Core;

final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) return;
        session_name('commerce_pulse');
        session_set_cookie_params(['httponly'=>true, 'secure'=>isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off', 'samesite'=>'Lax', 'path'=>'/']);
        ini_set('session.use_strict_mode', '1');
        session_start();
    }
    public static function csrf(): string { return $_SESSION['_csrf'] ??= bin2hex(random_bytes(32)); }
    public static function verify(): void
    {
        $token = $_POST['_csrf'] ?? '';
        if (!is_string($token) || !hash_equals(self::csrf(), $token)) throw new HttpException(419, 'La session a expiré. Recharge la page puis réessaie.');
    }
    public static function flash(string $message, string $type = 'success'): void { $_SESSION['flash'] = compact('message','type'); }
    public static function takeFlash(): ?array { $flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $flash; }
    public static function login(int $id): void { session_regenerate_id(true); $_SESSION['user_id'] = $id; $_SESSION['_csrf'] = bin2hex(random_bytes(32)); }
    public static function logout(): void { $_SESSION = []; session_regenerate_id(true); }
}
