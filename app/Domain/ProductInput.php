<?php
declare(strict_types=1);
namespace App\Domain;
final class ProductInput
{
    public static function validate(array $input): array
    {
        $name = trim((string)($input['name'] ?? ''));
        $price = trim((string)($input['price'] ?? ''));
        $stock = filter_var($input['stock'] ?? null, FILTER_VALIDATE_INT);
        $category = filter_var($input['category_id'] ?? null, FILTER_VALIDATE_INT);
        $description = trim((string)($input['description'] ?? ''));
        if (strlen($name) < 3 || strlen($name) > 120) throw new \DomainException('Le nom doit contenir entre 3 et 120 caractères.');
        if (!preg_match('/^\d{1,5}([.,]\d{1,2})?$/', $price)) throw new \DomainException('Le prix doit être un montant positif avec deux décimales maximum.');
        $parts = explode('.', str_replace(',', '.', $price));
        $cents = (int)$parts[0]*100 + (int)str_pad($parts[1] ?? '', 2, '0');
        if ($cents < 100 || $cents > 1000000) throw new \DomainException('Le prix doit être compris entre 1 € et 10 000 €.');
        if ($stock === false || $stock < 0 || $stock > 10000) throw new \DomainException('Le stock doit être compris entre 0 et 10 000.');
        if (!$category || $category < 1) throw new \DomainException('Choisis une catégorie.');
        if (strlen($description) < 10 || strlen($description) > 2000) throw new \DomainException('La description doit contenir entre 10 et 2 000 caractères.');
        $illustration = (string)($input['illustration'] ?? 'notebook');
        if (!in_array($illustration, ['keyboard','lamp','bottle','notebook','headphones','bag','stand','mouse'], true)) $illustration = 'notebook';
        return ['name'=>$name, 'price_cents'=>$cents, 'stock'=>$stock, 'category_id'=>$category, 'description'=>$description, 'illustration'=>$illustration];
    }
}
