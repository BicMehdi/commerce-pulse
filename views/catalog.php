<section class="hero" aria-labelledby="hero-title">
    <div class="hero-topline"><span class="eyebrow">OBJETS DE BUREAU & DE TOUS LES JOURS</span><span class="edition">LA SÉLECTION / 01</span></div>
    <div class="hero-copy">
        <h1 id="hero-title" data-reveal>Le quotidien,<br><em>en mieux.</em><span class="title-dot" aria-hidden="true">*</span></h1>
        <div class="hero-bottom" data-reveal>
            <p>Un bon clavier. Un carnet ouvert.<br>Et de la place pour la prochaine idée.<br>Les essentiels du bureau, à ta façon.</p>
            <a class="button" href="#collection" data-magnetic><span>Explorer les objets</span><?= icon('arrow') ?></a>
        </div>
    </div>
    <div class="hero-scene" data-scene aria-label="Composition de trois objets de bureau : clavier, carnet et gourde">
        <div class="scene-grid" aria-hidden="true"></div>
        <span class="scene-coordinate" aria-hidden="true">CP / ÉTUDE D’OBJETS N° 01</span>
        <div class="scene-object scene-keyboard" data-depth="1.1"><img src="/assets/products/keyboard.svg" alt="Clavier mécanique"><span>01 — POUR CRÉER</span></div>
        <div class="scene-object scene-notebook" data-depth="-0.7"><img src="/assets/products/notebook.svg" alt="Carnet de notes"><span>02 — POUR LES IDÉES</span></div>
        <div class="scene-object scene-bottle" data-depth="0.5"><img src="/assets/products/bottle.svg" alt="Gourde isotherme"><span>03 — POUR BOUGER</span></div>
        <img class="orbit-stamp" src="/assets/stamp.svg" alt="Moins de distractions, plus d’idées">
        <span class="scene-cross cross-one" aria-hidden="true">+</span><span class="scene-cross cross-two" aria-hidden="true">+</span>
        <span class="scene-measure" aria-hidden="true">DES ESSENTIELS. PAS DE SUPERFLU.</span>
    </div>
    <a class="scroll-cue" href="#collection"><span>FAIRE DÉFILER</span><span aria-hidden="true">↓</span></a>
</section>

<div class="ticker" aria-label="Pour travailler, créer, emporter et recommencer">
    <div class="ticker-track"><span>TRAVAILLER <b>✳</b> CRÉER <b>✳</b> EMPORTER <b>✳</b> RECOMMENCER <b>✳</b></span><span aria-hidden="true">TRAVAILLER <b>✳</b> CRÉER <b>✳</b> EMPORTER <b>✳</b> RECOMMENCER <b>✳</b></span></div>
</div>

<section id="collection" class="collection" aria-labelledby="collection-title">
    <div class="section-heading" data-reveal>
        <div class="section-index">01 /</div><div><span class="eyebrow">CHOISIR SES ESSENTIELS</span><h2 id="collection-title">Les objets <em>qui suivent.</em></h2></div>
        <span class="collection-count"><?= str_pad((string)$total, 2, '0', STR_PAD_LEFT) ?><small>OBJETS</small></span>
    </div>
    <form class="catalog-filters" method="get" action="/#collection">
        <div class="search-field"><?= icon('search') ?><input type="search" name="q" value="<?= e($search) ?>" placeholder="Un objet, une idée…" aria-label="Rechercher un produit"></div>
        <select name="category" aria-label="Catégorie"><option value="0">Toutes les catégories</option><?php foreach ($categories as $c): ?><option value="<?= (int)$c['id'] ?>" <?= selected($category,$c['id']) ?>><?= e($c['name']) ?></option><?php endforeach; ?></select>
        <select name="sort" aria-label="Trier les produits"><option value="newest" <?= selected($_GET['sort'] ?? '', 'newest') ?>>Nouveautés</option><option value="price_asc" <?= selected($_GET['sort'] ?? '', 'price_asc') ?>>Prix croissant</option><option value="price_desc" <?= selected($_GET['sort'] ?? '', 'price_desc') ?>>Prix décroissant</option><option value="name" <?= selected($_GET['sort'] ?? '', 'name') ?>>Nom</option></select>
        <button class="button secondary" type="submit"><span>Filtrer</span><?= icon('arrow') ?></button>
    </form>
    <div class="product-grid">
        <?php foreach ($products as $index=>$p): ?>
        <article class="product-card" data-reveal>
            <a class="product-art art-<?= e($p['illustration']) ?>" href="/products/<?= (int)$p['id'] ?>" data-tilt>
                <span class="object-number" aria-hidden="true">OBJ. <?= str_pad((string)(($page-1)*12+$index+1), 2, '0', STR_PAD_LEFT) ?></span>
                <img loading="lazy" src="/assets/products/<?= e($p['illustration']) ?>.svg" alt="<?= e($p['name']) ?>">
                <?php if ((int)$p['stock']===0): ?><span class="badge">Épuisé</span><?php elseif ((int)$p['stock']<10): ?><span class="badge">Dernières pièces</span><?php endif; ?>
                <span class="card-arrow" aria-hidden="true"><?= icon('arrow') ?></span>
            </a>
            <div class="product-meta"><span class="eyebrow"><?= e($p['category_name']) ?></span><div><h3><a href="/products/<?= (int)$p['id'] ?>"><?= e($p['name']) ?></a></h3><strong><?= euros((int)$p['price_cents']) ?></strong></div><a class="product-discover" href="/products/<?= (int)$p['id'] ?>">Découvrir l’objet <span aria-hidden="true">↗</span></a></div>
        </article>
        <?php endforeach; ?>
    </div>
    <?php if (!$products): ?><div class="empty-panel"><span class="empty-symbol" aria-hidden="true">∅</span><h3>Aucun objet trouvé.</h3><p>Essaie un autre mot-clé ou une autre catégorie.</p><a href="/" class="button secondary">Voir toute la collection</a></div><?php endif; ?>
    <?php if ($pages>1): ?><nav class="pagination" aria-label="Pages du catalogue"><?php for ($i=1;$i<=$pages;$i++): ?><a class="<?= $i===$page?'active':'' ?>" href="/?<?= e(http_build_query(array_merge($_GET,['page'=>$i]))) ?>#collection" <?= $i===$page?'aria-current="page"':'' ?>><?= $i ?></a><?php endfor; ?></nav><?php endif; ?>
</section>

<section id="studio" class="studio-note" aria-labelledby="studio-title" data-reveal>
    <div class="studio-copy"><span class="eyebrow">02 / L’ESPRIT COMMERCE PULSE</span><h2 id="studio-title">Faire moins.<br><em>Mais mieux.</em></h2><p>On aime les bureaux qui vivent. Les carnets raturés. Les idées qui partent en balade. Des objets simples, pour faire de la place à ce qui compte.</p><a class="studio-link" href="#collection">Trouver mon prochain essentiel <?= icon('arrow') ?></a></div>
    <div class="studio-art" aria-hidden="true"><span class="studio-large-type">À TA<br>FAÇON.</span><img src="/assets/products/bag.svg" alt=""><span class="studio-sticker">AU BUREAU.<br>ET AILLEURS.</span></div>
</section>
<section class="values-strip" aria-label="L’esprit de la collection"><div><span class="value-number">01</span><h3>Utile avant tout.</h3><p>Un objet, une fonction. Le plaisir en plus.</p></div><div><span class="value-number">02</span><h3>Du bureau à la ville.</h3><p>Des essentiels qui changent de décor.</p></div><div><span class="value-number">03</span><h3>À ton rythme.</h3><p>Choisir, ajuster, passer commande.</p></div></section>
