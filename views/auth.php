<section class="auth-layout">
    <div class="auth-intro" data-scene>
        <span class="eyebrow">CP / L’ESPACE PERSONNEL</span>
        <h1 data-reveal><?= $register?'Les bonnes idées<br>commencent<br><em>ici.</em>':'On reprend<br>là où tu<br><em>en étais.</em>' ?></h1>
        <p>Un espace pour tes objets, tes commandes<br>et la suite de ta journée.</p>
        <div class="auth-object" data-depth="0.7"><img src="/assets/products/photos/notebook-small.webp" alt="Carnet et stylo sur fond jaune"></div>
        <span class="auth-poster-foot">COMMERCE PULSE <span>FAIS DE LA PLACE.</span></span>
    </div>
    <div class="auth-form" data-reveal>
        <div class="form-kicker"><span class="eyebrow"><?= $register?'PREMIÈRE VISITE':'CONTENT DE TE RETROUVER' ?></span><span aria-hidden="true">↗</span></div>
        <h2><?= $register?'Créer mon compte':'Me connecter' ?></h2>
        <p class="muted form-intro"><?= $register?'Quelques infos. Et c’est parti.':'Tes essentiels sont toujours là.' ?></p>
        <?php if (isset($error)): ?><div class="alert error" role="alert"><?= e($error) ?></div><?php endif; ?>
        <form method="post" action="<?= $register?'/register':'/login' ?>">
            <?= csrf_field() ?>
            <?php if ($register): ?><label for="name">Ton nom</label><input id="name" name="name" autocomplete="name" value="<?= e($name ?? '') ?>" minlength="2" maxlength="100" placeholder="Comment t’appelles-tu ?" required><?php endif; ?>
            <label for="email">Adresse email</label><input id="email" type="email" name="email" autocomplete="email" value="<?= e($email ?? '') ?>" maxlength="190" placeholder="toi@exemple.fr" required>
            <label for="password">Mot de passe</label>
            <div class="password-field"><input id="password" type="password" name="password" autocomplete="<?= $register?'new-password':'current-password' ?>" <?= $register?'minlength="12"':'' ?> maxlength="72" required><button type="button" class="password-toggle" data-password-target="password" aria-controls="password" aria-pressed="false" aria-label="Afficher le mot de passe">VOIR</button></div>
            <?php if ($register): ?><p class="small muted password-help">12 caractères minimum.</p><label for="confirm">Confirmer le mot de passe</label><div class="password-field"><input id="confirm" type="password" name="password_confirmation" autocomplete="new-password" required><button type="button" class="password-toggle" data-password-target="confirm" aria-controls="confirm" aria-pressed="false" aria-label="Afficher la confirmation du mot de passe">VOIR</button></div><?php endif; ?>
            <button class="button full" type="submit"><span><?= $register?'Créer mon compte':'Me connecter' ?></span><?= icon('arrow') ?></button>
        </form>
        <p class="auth-switch"><?= $register?'Déjà un compte ?':'Première visite ?' ?> <a href="<?= $register?'/login':'/register' ?>"><?= $register?'Me connecter':'Créer un compte' ?> ↗</a></p>
        <?php if (env('APP_DEMO')==='1'): ?><details class="demo-accounts"><summary>Essayer les comptes de démonstration</summary><p>Client : <strong>alex@commercepulse.test</strong><br>Admin : <strong>admin@commercepulse.test</strong><br>Mot de passe : <strong><?= e(env('DEMO_PASSWORD','Demo-Pulse-2026!')) ?></strong></p></details><?php endif; ?>
    </div>
</section>
