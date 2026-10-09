<?php
declare(strict_types=1);
namespace App\Services;
use App\Core\Database as DB;
final class Catalog
{
    public function search(array $filters, bool $includeArchived = false): array
    {
        $page = max(1, min(10000, (int)($filters['page'] ?? 1)));
        $search = substr(trim((string)($filters['q'] ?? '')), 0, 100);
        $category = (int)($filters['category'] ?? 0);
        $sort = ['newest'=>'p.id DESC','price_asc'=>'p.price_cents ASC','price_desc'=>'p.price_cents DESC','name'=>'p.name ASC'][(string)($filters['sort'] ?? 'newest')] ?? 'p.id DESC';
        $clauses = [$includeArchived ? '1=1' : 'p.active = 1']; $params = [];
        if ($search !== '') { $clauses[] = '(p.name LIKE ? OR p.description LIKE ?)'; $params[]='%'.$search.'%'; $params[]='%'.$search.'%'; }
        if ($category > 0) { $clauses[]='p.category_id = ?'; $params[]=$category; }
        $where = implode(' AND ', $clauses);
        $total = (int)DB::query('SELECT COUNT(*) FROM products p WHERE '.$where, $params)->fetchColumn();
        $products = DB::query('SELECT p.*, c.name category_name FROM products p JOIN categories c ON c.id=p.category_id WHERE '.$where.' ORDER BY '.$sort.' LIMIT ? OFFSET ?', [...$params,12,($page-1)*12])->fetchAll();
        return compact('products','total','page','search','category') + ['pages'=>(int)ceil($total/12)];
    }
    public function find(int $id, bool $includeArchived = false): ?array
    {
        return DB::query('SELECT p.*, c.name category_name FROM products p JOIN categories c ON c.id=p.category_id WHERE p.id=?'.($includeArchived ? '' : ' AND p.active=1'), [$id])->fetch() ?: null;
    }
    public function categories(): array { return DB::query('SELECT * FROM categories ORDER BY name')->fetchAll(); }
}
