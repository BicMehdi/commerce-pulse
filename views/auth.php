<section class="auth-layout">
<div class="auth-intro">
<span class="eyebrow">BIENVENUE CHEZ COMMERCE PULSE</span>
<h1>
<?= $register?'Un espace pour<br>tes prochains essentiels.':'Content de<br>te retrouver.' ?>
</h1>
<p>Retrouve tes commandes et prépare ta prochaine journée.</p>
<img src="/assets/products/notebook.svg" alt="Illustration d’un carnet">
</div>
<div class="auth-form">
<h2>
<?= $register?'Créer mon compte':'Me connecter' ?>
</h2>
<?php if (isset($error)): ?>
<div class="alert error" role="alert">
<?= e($error) ?>
</div>
<?php endif; ?>
<form method="post" action="<?= $register?'/register':'/login' ?>">
<?= csrf_field() ?>
<?php if ($register): ?>
<label for="name">Ton nom</label>
<input id="name" name="name" autocomplete="name" value="<?= e($name ?? '') ?>" minlength="2" maxlength="100" required>
<?php endif; ?>
<label for="email">Adresse email</label>
<input id="email" type="email" name="email" autocomplete="email" value="<?= e($email ?? '') ?>" maxlength="190" required>
<label for="password">Mot de passe</label>
<input id="password" type="password" name="password" autocomplete="<?= $register?'new-password':'current-password' ?>" <?= $register?'minlength="12"':'' ?> maxlength="72" required>
<?php if ($register): ?>
<p class="small muted">12 caractères minimum.</p>
<label for="confirm">Confirmer le mot de passe</label>
<input id="confirm" type="password" name="password_confirmation" autocomplete="new-password" required>
<?php endif; ?>
<button class="button full">
<?= $register?'Créer mon compte':'Me connecter' ?>
<?= icon('arrow') ?>
</button>
</form>
<p>
<?= $register?'Déjà un compte ?':'Première visite ?' ?>
<a href="<?= $register?'/login':'/register' ?>">
<?= $register?'Me connecter':'Créer un compte' ?>
</a>
</p>
<?php if (env('APP_DEMO')==='1'): ?>
<details class="demo-accounts">
<summary>Essayer les comptes de démonstration</summary>
<p>Client : <strong>alex@commercepulse.test</strong>
<br>Admin : <strong>admin@commercepulse.test</strong>
<br>Mot de passe : <strong>
<?= e(env('DEMO_PASSWORD','Demo-Pulse-2026!')) ?>
</strong>
</p>
</details>
<?php endif; ?>
</div>
</section>
