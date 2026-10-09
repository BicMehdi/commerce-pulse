<div class="page-heading">
<span class="eyebrow">MON ESPACE</span>
<h1>Bonjour, <?= e($user['name']) ?>.</h1>
<p class="muted">Tes commandes et les essentiels qui t’accompagnent.</p>
</div>
<div class="account-bar">
<span>
<?= e($user['email']) ?>
</span>
<form action="/logout" method="post">
<?= csrf_field() ?>
<button class="text-button">Se déconnecter</button>
</form>
</div>
<section class="panel">
<h2>Mes commandes</h2>
<?php if (!$orders): ?>
<p>Ta première commande se prépare ici.</p>
<a href="/#collection" class="button secondary">Voir la collection</a>
<?php else: ?>
<div class="table-scroll">
<table>
<thead>
<tr>
<th>Commande</th>
<th>Date</th>
<th>Statut</th>
<th>Total</th>
<th>
</th>
</tr>
</thead>
<tbody>
<?php foreach ($orders as $o): ?>
<tr>
<td>#<?= str_pad((string)$o['id'],4,'0',STR_PAD_LEFT) ?>
</td>
<td>
<?= short_date($o['created_at']) ?>
</td>
<td>
<span class="status <?= e($o['status']) ?>">
<?= status_label($o['status']) ?>
</span>
</td>
<td>
<?= euros((int)$o['total_cents']) ?>
</td>
<td>
<a href="/orders/<?= (int)$o['id'] ?>">Voir →</a>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php endif; ?>
</section>
