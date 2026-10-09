<a class="back-link" href="/#collection">← Retour à la collection</a>
<section class="product-detail">
<div class="product-art art-<?= e($product['illustration']) ?>">
<img src="<?= e(product_photo($product['illustration'])) ?>" srcset="<?= e(product_photo($product['illustration'], true)) ?> 640w, <?= e(product_photo($product['illustration'])) ?> 1200w" sizes="(max-width: 650px) 90vw, 55vw" alt="<?= e($product['name']) ?>">
</div>
<div class="product-detail-copy">
<span class="eyebrow">
<?= e($product['category_name']) ?>
</span>
<h1>
<?= e($product['name']) ?>
</h1>
<p class="detail-price">
<?= euros((int)$product['price_cents']) ?>
</p>
<p>
<?= e($product['description']) ?>
</p>
<p class="stock-label">
<?= (int)$product['stock']>0 ? (int)$product['stock'].' pièces disponibles' : 'Ce produit est actuellement épuisé.' ?>
</p>
<form action="/cart/add" method="post">
<?= csrf_field() ?>
<input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
<label for="quantity">Quantité</label>
<div class="inline-form">
<input id="quantity" name="quantity" type="number" min="1" max="<?= min(10,(int)$product['stock']) ?>" value="1" required>
<button class="button" <?= (int)$product['stock']===0?'disabled':'' ?>>Ajouter au panier <?= icon('bag') ?>
</button>
</div>
</form>
<p class="small muted">Commande de démonstration. Aucun paiement ni livraison réelle.</p>
</div>
</section>
