<?php
declare(strict_types=1);
namespace App\Core;
final class Auth
{
    public static function user(): ?array
    {
        if (!isset($_SESSION['user_id'])) return null;
        return Database::query('SELECT id, name, email, role FROM users WHERE id = ?', [(int)$_SESSION['user_id']])->fetch() ?: null;
    }
    public static function requireUser(): array
    {
        $user = self::user();
        if (!$user) { Session::flash('Connecte-toi pour continuer.', 'info'); Response::redirect('/login'); }
        return $user;
    }
    public static function requireAdmin(): array
    {
        $user = self::user();
        if (!$user || $user['role'] !== 'admin') throw new HttpException(403, 'Cet espace est réservé à l’administration.');
        return $user;
    }
}
