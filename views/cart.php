<div class="page-heading">
<span class="eyebrow">LES PROCHAINS ESSENTIELS</span>
<h1>Ton panier.</h1>
<p class="muted">Encore un coup d’œil avant de passer commande.</p>
</div>
<?php if (!$items): ?>
<div class="empty-panel">
<?= icon('bag') ?>
<h2>Ton panier attend ses essentiels.</h2>
<a class="button" href="/#collection">Découvrir la collection</a>
</div>
<?php else: ?>
<div class="cart-layout">
<div class="cart-items">
<?php foreach ($items as $item): ?>
<article class="cart-item">
<a class="cart-art art-<?= e($item['illustration']) ?>" href="/products/<?= (int)$item['id'] ?>">
<img src="<?= e(product_photo($item['illustration'], true)) ?>" alt="">
</a>
<div>
<span class="eyebrow">
<?= e($item['category_name']) ?>
</span>
<h3>
<?= e($item['name']) ?>
</h3>
<span class="muted">
<?= euros((int)$item['price_cents']) ?> / unité</span>
<form class="quantity-form" action="/cart/update" method="post">
<?= csrf_field() ?>
<input type="hidden" name="product_id" value="<?= (int)$item['id'] ?>">
<label class="sr-only" for="qty-<?= (int)$item['id'] ?>">Quantité pour <?= e($item['name']) ?>
</label>
<input id="qty-<?= (int)$item['id'] ?>" type="number" name="quantity" min="0" max="10" value="<?= (int)$item['quantity'] ?>">
<button class="text-button">Mettre à jour</button>
<span class="small muted">0 pour retirer</span>
</form>
</div>
<strong>
<?= euros((int)$item['line_total']) ?>
</strong>
</article>
<?php endforeach; ?>
</div>
<aside class="order-summary">
<h2>Le récapitulatif</h2>
<div>
<span>Sous-total</span>
<strong>
<?= euros($total) ?>
</strong>
</div>
<div>
<span>Livraison</span>
<span>Simulation gratuite</span>
</div>
<hr>
<div class="summary-total">
<span>Total</span>
<strong>
<?= euros($total) ?>
</strong>
</div>
<?php if ($user): ?>
<form action="/checkout" method="post">
<?= csrf_field() ?>
<input type="hidden" name="checkout_key" value="<?= e($checkoutKey) ?>">
<button class="button full">Confirmer la commande <?= icon('arrow') ?>
</button>
</form>
<?php else: ?>
<a href="/login" class="button full">Se connecter pour commander</a>
<?php endif; ?>
<p class="small muted">Aucun paiement demandé. Le stock sera réservé après confirmation.</p>
</aside>
</div>
<?php endif; ?>
