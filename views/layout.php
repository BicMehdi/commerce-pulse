<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="description" content="Commerce Pulse — boutique et analyse des ventes. Projet de démonstration PHP, SQL et Python.">
<title>
<?= e($title ?? 'Commerce Pulse') ?> · Commerce Pulse</title>
<link rel="icon" href="/assets/favicon.svg" type="image/svg+xml">
<link rel="stylesheet" href="/assets/app.css">
<script src="/assets/app.js" defer>
</script>
</head>
<body class="<?= $admin ? 'admin-shell' : 'shop-shell' ?>">
<a href="#main" class="skip-link">Aller au contenu</a>
<?php if ($admin): ?>
<aside class="sidebar">
<a class="brand" href="/admin">
<span class="brand-mark">cp</span>Commerce Pulse</a>
<span class="eyebrow">ESPACE ADMIN</span>
<nav aria-label="Administration">
<?php foreach ([['/admin','grid','Vue d’ensemble'],['/admin/products','box','Produits'],['/admin/orders','bag','Commandes'],['/admin/analytics','chart','Analyse des ventes']] as [$href,$ico,$label]): ?>
<a class="nav-item <?= ($path ?? '')===$href?'active':'' ?>" href="<?= e($href) ?>">
<?= icon($ico) ?>
<?= e($label) ?>
</a>
<?php endforeach; ?>
</nav>
<div class="sidebar-bottom">
<a href="/" class="nav-item">
<?= icon('arrow') ?>Voir la boutique</a>
<span class="user-chip">
<?= icon('user') ?>
<?= e($user['name'] ?? '') ?>
</span>
<form action="/logout" method="post">
<?= csrf_field() ?>
<button class="text-button" type="submit">Se déconnecter</button>
</form>
</div>
</aside>
<div class="admin-body">
<header class="admin-header">
<span>Workspace / <strong>
<?= e($title ?? '') ?>
</strong>
</span>
<span class="demo-label">Démonstration locale</span>
</header>
<?php else: ?>
<div class="announcement">Un bureau pensé pour les idées qui avancent. <span>Boutique de démonstration · aucun paiement réel</span>
</div>
<header class="shop-header">
<a class="brand" href="/">
<span class="brand-mark">cp</span>Commerce Pulse</a>
<nav aria-label="Navigation principale">
<a href="/#collection">La collection</a>
<?php if ($user && $user['role']==='admin'): ?>
<a href="/admin">Administration</a>
<?php endif; ?>
<a href="<?= $user?'/account':'/login' ?>">
<?= icon('user') ?>
<span>
<?= $user?'Mon espace':'Connexion' ?>
</span>
</a>
<a class="cart-link" href="/cart">
<?= icon('bag') ?>
<span>Panier</span>
<span class="count">
<?= (new App\Services\Cart())->quantity() ?>
</span>
</a>
</nav>
</header>
<?php endif; ?>
<main id="main" class="<?= $admin?'admin-content':'shop-content' ?>">
<?php if ($flash): ?>
<div class="alert <?= e($flash['type']) ?>" role="status">
<?= e($flash['message']) ?>
</div>
<?php endif; ?>
<?= $content ?>
</main>
<?php if ($admin): ?>
</div>
<?php else: ?>
<footer class="shop-footer">
<div>
<a class="brand" href="/">
<span class="brand-mark">cp</span>Commerce Pulse</a>
<p>Des essentiels. Une journée plus simple.</p>
</div>
<div>
<span>Projet portfolio · Mehdi Bictogo</span>
<a href="https://github.com/BicMehdi" target="_blank" rel="noopener noreferrer">GitHub ↗</a>
<span>Produits et commandes fictifs.</span>
</div>
</footer>
<?php endif; ?>
</body>
</html>
