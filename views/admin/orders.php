<div class="admin-title">
<div>
<span class="eyebrow">ACTIVITÉ</span>
<h1>Commandes</h1>
<p class="muted">Préparer, expédier et suivre les commandes.</p>
</div>
</div>
<form method="get" class="catalog-filters">
<select name="status" aria-label="Statut">
<option value="">Tous les statuts</option>
<?php foreach(array_keys(App\Domain\OrderStatus::NEXT) as $s): ?>
<option value="<?= $s ?>" <?= selected($status,$s) ?>>
<?= status_label($s) ?>
</option>
<?php endforeach; ?>
</select>
<button class="button secondary">Filtrer</button>
</form>
<section class="panel table-scroll">
<table>
<thead>
<tr>
<th>Commande</th>
<th>Client</th>
<th>Date</th>
<th>Statut</th>
<th>Total</th>
<th>
</th>
</tr>
</thead>
<tbody>
<?php foreach($orders as $o): ?>
<tr>
<td>#<?= str_pad((string)$o['id'],4,'0',STR_PAD_LEFT) ?>
</td>
<td>
<?= e($o['customer_name']) ?>
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
<a href="/orders/<?= (int)$o['id'] ?>">Gérer →</a>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</section>
<p class="small muted">Les 100 commandes les plus récentes pour ce filtre.</p>
