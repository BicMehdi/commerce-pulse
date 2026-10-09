<?php
declare(strict_types=1);
namespace App\Domain;
final class OrderStatus
{
    public const NEXT = ['pending'=>['processing','cancelled'], 'processing'=>['shipped','cancelled'], 'shipped'=>[], 'cancelled'=>[]];
    public static function allows(string $from, string $to): bool { return in_array($to, self::NEXT[$from] ?? [], true); }
}
