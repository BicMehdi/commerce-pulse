<a class="back-link" href="<?= $admin?'/admin/orders':'/account' ?>">← Toutes les commandes</a>
<div class="page-heading">
<span class="eyebrow">COMMANDE #<?= str_pad((string)$order['id'],4,'0',STR_PAD_LEFT) ?>
</span>
<h1>Le suivi de ta commande.</h1>
<div class="inline-form">
<span class="status <?= e($order['status']) ?>">
<?= status_label($order['status']) ?>
</span>
<span class="muted">Créée le <?= short_date($order['created_at']) ?>
</span>
</div>
</div>
<div class="cart-layout">
<section class="panel">
<h2>Le détail</h2>
<?php foreach ($order['items'] as $item): ?>
<div class="order-line">
<div>
<strong>
<?= e($item['product_name']) ?>
</strong>
<p class="small muted">
<?= (int)$item['quantity'] ?> × <?= euros((int)$item['unit_price_cents']) ?>
</p>
</div>
<strong>
<?= euros((int)$item['quantity']*(int)$item['unit_price_cents']) ?>
</strong>
</div>
<?php endforeach; ?>
<div class="order-line total">
<strong>Total</strong>
<strong>
<?= euros((int)$order['total_cents']) ?>
</strong>
</div>
</section>
<aside class="panel">
<h2>
<?= $admin?'Gestion de la commande':'Et maintenant ?' ?>
</h2>
<p>
<?= e($order['customer_name']) ?>
<br>
<span class="muted">
<?= e($order['customer_email']) ?>
</span>
</p>
<?php if ($admin && App\Domain\OrderStatus::NEXT[$order['status']]): ?>
<form action="/admin/orders/<?= (int)$order['id'] ?>/status" method="post">
<?= csrf_field() ?>
<label for="status">Prochain statut</label>
<select id="status" name="status">
<?php foreach (App\Domain\OrderStatus::NEXT[$order['status']] as $s): ?>
<option value="<?= e($s) ?>">
<?= status_label($s) ?>
</option>
<?php endforeach; ?>
</select>
<button class="button full">Mettre à jour</button>
</form>
<?php else: ?>
<p class="muted">Tu peux suivre le statut depuis ton espace.</p>
<?php endif; ?>
<p class="small muted">Cette commande est une simulation. Aucun paiement ni livraison réelle.</p>
</aside>
</div>
