<?php
declare(strict_types=1);
namespace App\Services;
use App\Core\Session;
final class Cart
{
    public function items(): array
    {
        $items = []; $catalog = new Catalog(); $removed = 0;
        foreach ($_SESSION['cart'] ?? [] as $id=>$quantity) {
            $product = $catalog->find((int)$id);
            if ($product) {
                $items[] = $product + ['quantity'=>(int)$quantity,'line_total'=>(int)$product['price_cents']*(int)$quantity];
            } else {
                unset($_SESSION['cart'][$id]); $removed++;
            }
        }
        if ($removed) {
            // A stale confirmation must not silently buy a smaller basket.
            $_SESSION['checkout_key'] = bin2hex(random_bytes(32));
            Session::flash(($removed===1 ? 'Un article indisponible a été retiré' : 'Des articles indisponibles ont été retirés').' de ton panier. Vérifie le récapitulatif avant de commander.','info');
        }
        return $items;
    }
    public function total(): int { return array_sum(array_column($this->items(), 'line_total')); }
    public function quantity(): int { return array_sum(array_column($this->items(), 'quantity')); }
    public function put(int $id, int $quantity, bool $add = false): void
    {
        if ($quantity < 0 || $quantity > 10) throw new \DomainException('Choisis une quantité comprise entre 0 et 10.');
        $new = $quantity + ($add ? (int)($_SESSION['cart'][$id] ?? 0) : 0);
        if ($new === 0) {
            unset($_SESSION['cart'][$id]);
            $_SESSION['checkout_key'] = bin2hex(random_bytes(32));
            return;
        }
        $product = (new Catalog())->find($id);
        if (!$product) throw new \DomainException('Ce produit n’est plus disponible.');
        if ($new > 10 || $new > (int)$product['stock']) throw new \DomainException('La quantité demandée dépasse le stock disponible (ou 10 unités).');
        $_SESSION['cart'][$id] = $new;
        // Any basket mutation starts a new checkout intent.
        $_SESSION['checkout_key'] = bin2hex(random_bytes(32));
    }
    public function key(): string { return $_SESSION['checkout_key'] ??= bin2hex(random_bytes(32)); }
    public function clear(): void { unset($_SESSION['cart']); /* Keep intent for harmless retries after a successful checkout. */ }
}
