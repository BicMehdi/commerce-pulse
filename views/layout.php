<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="description" content="Commerce Pulse — les essentiels du bureau à ta façon. Boutique et analyse des ventes, projet PHP, SQL et Python.">
    <meta name="theme-color" content="#eceadf">
    <title><?= e($title ?? 'Commerce Pulse') ?> · Commerce Pulse</title>
    <link rel="icon" href="/assets/favicon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="/assets/app.css?v=2"><script src="/assets/app.js?v=2" defer></script>
</head>
<body class="<?= $admin ? 'admin-shell' : 'shop-shell' ?>">
<a href="#main" class="skip-link">Aller au contenu</a><div class="reading-progress" aria-hidden="true"></div>
<?php if ($admin): ?>
    <aside class="sidebar">
        <a class="brand" href="/admin"><span class="brand-mark">CP</span><span class="brand-copy">COMMERCE<br>PULSE.</span></a><span class="eyebrow">LE STUDIO / ADMINISTRATION</span>
        <nav aria-label="Administration"><?php foreach ([['/admin','grid','Vue d’ensemble'],['/admin/products','box','Produits'],['/admin/orders','bag','Commandes'],['/admin/analytics','chart','Analyse des ventes']] as [$href,$ico,$label]): ?><a class="nav-item <?= $path===$href?'active':'' ?>" href="<?= e($href) ?>" <?= $path===$href?'aria-current="page"':'' ?>><?= icon($ico) ?><?= e($label) ?></a><?php endforeach; ?></nav>
        <div class="sidebar-bottom"><a href="/" class="nav-item"><?= icon('arrow') ?>Voir la boutique</a><span class="user-chip"><?= icon('user') ?><?= e($user['name'] ?? '') ?></span><form action="/logout" method="post"><?= csrf_field() ?><button class="text-button" type="submit">Se déconnecter</button></form></div>
    </aside>
    <div class="admin-body"><header class="admin-header"><span>LE STUDIO / <strong><?= e($title ?? '') ?></strong></span><span class="demo-label">DÉMONSTRATION LOCALE</span></header>
<?php else: ?>
    <div class="announcement"><span>LES BONNES IDÉES MÉRITENT UN BON BUREAU.</span><span class="announcement-note">BOUTIQUE DE DÉMONSTRATION — AUCUN PAIEMENT RÉEL</span></div>
    <header class="shop-header">
        <a class="brand" href="/"><span class="brand-mark">CP</span><span class="brand-copy">COMMERCE<br>PULSE.</span></a>
        <nav aria-label="Navigation principale"><a class="nav-collection" href="/#collection">LES OBJETS <span aria-hidden="true">↗</span></a><a class="nav-studio" href="/#studio">L’ESPRIT</a><?php if ($user && $user['role']==='admin'): ?><a class="nav-admin" href="/admin">ADMIN</a><?php endif; ?><a class="nav-account" aria-label="<?= $user?'Mon espace':'Connexion' ?>" href="<?= $user?'/account':'/login' ?>"><?= icon('user') ?><span><?= $user?'Mon espace':'Connexion' ?></span></a><a class="cart-link" aria-label="Panier : <?= (new App\Services\Cart())->quantity() ?> articles" href="/cart"><?= icon('bag') ?><span>Panier</span><span class="count"><?= (new App\Services\Cart())->quantity() ?></span></a></nav>
    </header>
<?php endif; ?>
<main id="main" class="<?= $admin?'admin-content':'shop-content' ?>"><?php if ($flash): ?><div class="alert <?= e($flash['type']) ?>" role="status"><?= icon('check') ?><span><?= e($flash['message']) ?></span></div><?php endif; ?><?= $content ?></main>
<?php if ($admin): ?></div><?php else: ?>
    <footer class="shop-footer"><div class="footer-top"><a class="footer-wordmark" href="/">COMMERCE<br><em>PULSE.</em><span aria-hidden="true">↗</span></a><div class="footer-note"><span class="eyebrow">BIEN S’ÉQUIPER. PUIS AVANCER.</span><p>Les objets restent simples.<br>Les idées font le reste.</p><a href="/#collection">Retour à la collection <?= icon('arrow') ?></a></div></div><div class="footer-bottom"><span>MEHDI BICTOGO / PROJET PORTFOLIO</span><span>PRODUITS ET COMMANDES FICTIFS</span><a href="https://github.com/BicMehdi" target="_blank" rel="noopener noreferrer">GITHUB ↗</a></div></footer>
<?php endif; ?>
</body>
</html>
