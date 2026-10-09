<div class="admin-title">
<div>
<span class="eyebrow">CATALOGUE</span>
<h1>Produits</h1>
<p class="muted">Prix, disponibilité et visibilité de la collection.</p>
</div>
<a class="button" href="/admin/products/new">Ajouter un produit +</a>
</div>
<form class="catalog-filters" method="get">
<div class="search-field">
<?= icon('search') ?>
<input name="q" type="search" value="<?= e($search) ?>" placeholder="Rechercher" aria-label="Rechercher les produits">
</div>
<button class="button secondary">Rechercher</button>
</form>
<section class="panel table-scroll">
<table>
<thead>
<tr>
<th>Produit</th>
<th>Catégorie</th>
<th>Prix</th>
<th>Stock</th>
<th>Visibilité</th>
<th>Actions</th>
</tr>
</thead>
<tbody>
<?php foreach ($products as $p): ?>
<tr>
<td>
<div class="table-product">
<img src="<?= e(product_photo($p['illustration'], true)) ?>" alt="">
<strong>
<?= e($p['name']) ?>
</strong>
</div>
</td>
<td>
<?= e($p['category_name']) ?>
</td>
<td>
<?= euros((int)$p['price_cents']) ?>
</td>
<td>
<?= (int)$p['stock'] ?>
</td>
<td>
<span class="status <?= $p['active']?'shipped':'cancelled' ?>">
<?= $p['active']?'Publié':'Archivé' ?>
</span>
</td>
<td>
<div class="inline-form">
<a href="/admin/products/<?= (int)$p['id'] ?>/edit">Modifier</a>
<form action="/admin/products/<?= (int)$p['id'] ?>/toggle" method="post">
<?= csrf_field() ?>
<button class="text-button" data-confirm="<?= $p['active']?'Archiver ce produit ? Il sera retiré de la boutique.':'Publier ce produit ?' ?>">
<?= $p['active']?'Archiver':'Publier' ?>
</button>
</form>
</div>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</section>
<?php if ($pages>1): ?>
<nav class="pagination" aria-label="Pages des produits">
<?php for($i=1;$i<=$pages;$i++): ?>
<a href="?<?= e(http_build_query(array_merge($_GET,['page'=>$i]))) ?>" class="<?= $i===$page?'active':'' ?>">
<?= $i ?>
</a>
<?php endfor; ?>
</nav>
<?php endif; ?>
