<div class="admin-title">
<div>
<span class="eyebrow">DATA & ACTIVITÉ</span>
<h1>Analyse des ventes</h1>
<p class="muted">Comprendre les commandes, les produits et les habitudes d’achat.</p>
</div>
<div class="inline-form">
<a class="button secondary" href="/admin/sales.csv">Exporter le CSV</a>
<form action="/admin/analytics/refresh" method="post">
<?= csrf_field() ?>
<button class="button">Actualiser l’analyse</button>
</form>
</div>
</div>
<?php if (!$report): ?>
<section class="panel">
<h2>La première analyse est prête à être lancée.</h2>
<p>Actualise l’analyse pour générer le rapport à partir des commandes.</p>
</section>
<?php else: $k=$report['kpis']; ?>
<p class="report-caption">Rapport au <?= short_date($report['as_of']) ?> · <?= (int)$report['quality']['orders_checked'] ?> commandes et <?= (int)$report['quality']['rows_checked'] ?> lignes contrôlées · horaires UTC</p>
<div class="kpi-grid">
<article>
<span>Valeur sur 30 jours</span>
<strong>
<?= euros((int)$k['last_30_days_value_cents']) ?>
</strong>
<small>
<?= $k['change_percent']===null?'Aucune base de comparaison':e($k['change_percent']).' % par rapport aux 30 jours précédents' ?>
</small>
</article>
<article>
<span>Commandes sur 30 jours</span>
<strong>
<?= (int)$k['last_30_days_orders'] ?>
</strong>
<small>Hors commandes annulées</small>
</article>
<article>
<span>Panier moyen historique</span>
<strong>
<?= euros((int)$k['average_order_cents']) ?>
</strong>
<small>Commandes non annulées · arrondi au centime</small>
</article>
<article>
<span>Clients récurrents</span>
<strong>
<?= (int)$k['repeat_customers'] ?> / <?= (int)$k['active_customers'] ?>
</strong>
<small>Au moins deux commandes non annulées</small>
</article>
</div>
<section class="panel chart-panel">
<div class="section-heading">
<div>
<h2>Valeur commandée par jour</h2>
<p class="small muted">30 jours · euros · commandes non annulées</p>
</div>
<span class="chart-legend">Valeur commandée</span>
</div>
<canvas data-sales-chart="<?= e(json_encode($report['daily'],JSON_THROW_ON_ERROR)) ?>" role="img" aria-label="Valeur commandée quotidienne en euros, sur les trente derniers jours">
</canvas>
<details>
<summary>Voir les données du graphique</summary>
<div class="table-scroll">
<table>
<thead>
<tr>
<th>Jour UTC</th>
<th>Commandes</th>
<th>Valeur (€)</th>
</tr>
</thead>
<tbody>
<?php foreach($report['daily'] as $day): ?>
<tr>
<td>
<?= short_date($day['date']) ?>
</td>
<td>
<?= (int)$day['orders'] ?>
</td>
<td>
<?= euros((int)$day['value_cents']) ?>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</details>
<p class="small muted">Source : export des lignes de commande → contrôle et agrégation Python. Les jours sans commande apparaissent à zéro.</p>
</section>
<div class="dashboard-grid">
<section class="panel">
<h2>Produits les plus commandés</h2>
<p class="small muted">Classement par valeur · tout l’historique non annulé</p>
<div class="table-scroll">
<table>
<thead>
<tr>
<th>Produit</th>
<th>Unités</th>
<th>Valeur</th>
</tr>
</thead>
<tbody>
<?php foreach($report['products'] as $p): ?>
<tr>
<td>
<?= e($p['name']) ?>
</td>
<td>
<?= (int)$p['quantity'] ?>
</td>
<td>
<?= euros((int)$p['value_cents']) ?>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</section>
<section class="panel">
<h2>Répartition par catégorie</h2>
<p class="small muted">Valeur des commandes non annulées · tout l’historique</p>
<?php foreach($report['categories'] as $c): ?>
<div class="category-row">
<span>
<?= e($c['name']) ?>
</span>
<strong>
<?= euros((int)$c['value_cents']) ?>
</strong>
<span class="small muted">
<?= (int)$c['quantity'] ?> unités</span>
</div>
<?php endforeach; ?>
<p class="small muted">
<?= (int)$k['cancelled_orders'] ?> commandes annulées exclues des indicateurs de valeur.</p>
</section>
</div>
<section class="data-note">
<?= icon('check') ?>
<div>
<strong>Des chiffres contrôlés avant d’être affichés.</strong>
<p>Chaque montant est calculé en centimes. Doublons, données invalides ou totaux incohérents empêchent la publication du rapport. La valeur commandée ne représente pas un paiement encaissé.</p>
</div>
</section>
<?php endif; ?>
