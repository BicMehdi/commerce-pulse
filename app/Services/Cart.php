<?php
declare(strict_types=1);
namespace App\Services;
final class Cart
{
    public function items(): array
    {
        $items = []; $catalog = new Catalog();
        foreach ($_SESSION['cart'] ?? [] as $id=>$quantity) {
            $product = $catalog->find((int)$id);
            if ($product) $items[] = $product + ['quantity'=>(int)$quantity,'line_total'=>(int)$product['price_cents']*(int)$quantity];
        }
        return $items;
    }
    public function total(): int { return array_sum(array_column($this->items(), 'line_total')); }
    public function quantity(): int { return array_sum($_SESSION['cart'] ?? []); }
    public function put(int $id, int $quantity, bool $add = false): void
    {
        $product = (new Catalog())->find($id);
        if (!$product) throw new \DomainException('Ce produit n’est plus disponible.');
        if ($quantity < 0 || $quantity > 10) throw new \DomainException('Choisis une quantité comprise entre 0 et 10.');
        $new = $quantity + ($add ? (int)($_SESSION['cart'][$id] ?? 0) : 0);
        if ($new > 10 || $new > (int)$product['stock']) throw new \DomainException('La quantité demandée dépasse le stock disponible (ou 10 unités).');
        if ($new === 0) unset($_SESSION['cart'][$id]); else $_SESSION['cart'][$id] = $new;
        // Any basket mutation starts a new checkout intent.
        $_SESSION['checkout_key'] = bin2hex(random_bytes(32));
    }
    public function key(): string { return $_SESSION['checkout_key'] ??= bin2hex(random_bytes(32)); }
    public function clear(): void { unset($_SESSION['cart']); /* Keep intent for harmless retries after a successful checkout. */ }
}
