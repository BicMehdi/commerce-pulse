# Direction visuelle

Commerce Pulse présente des objets de bureau. L’interface reprend les codes d’un atelier et d’un catalogue imprimé : composition asymétrique, grandes lettres, numéros de références, traits fins et matières discrètes. Les produits constituent les visuels principaux.

## Repères

- Papier `#f0eee5`, encre `#262823`, accent jaune `#e1ed53`, bleu cobalt `#3455dc`.
- Sans-serif pour les titres et la navigation ; serif italique pour les accroches ; monospace pour les repères de catalogue.
- Photos réelles de chaque produit, conservées localement en WebP, texture légère et tampon circulaire en SVG. Les auteurs et sources sont dans [les crédits](../CREDITS.md).
- Grille de trois colonnes décalée sur ordinateur, puis deux colonnes sur mobile. Formulaire d’inscription associé à une affiche jaune et bleue.
- Administration dans la même palette, avec priorité à la lisibilité des chiffres et aux tableaux défilants sur petit écran.

## Effets et usages

Le pointeur déplace légèrement les objets du visuel d’accueil. Les cartes produit prennent au maximum 2,5 degrés de relief par axe. Le bouton principal réagit au survol, les boutons affichent une onde au clic, et les sections apparaissent une seule fois quand elles entrent dans la fenêtre.

Les photographies de l’accueil ressemblent à des tirages superposés, avec cadre crème et ombre. Les fiches produit affichent le cliché complet ; les cartes et miniatures utilisent un cadrage propre à l’espace disponible. Les repères de catalogue reposent sur un fond clair pour rester lisibles sur les photos sombres. Le catalogue utilise `srcset` pour choisir entre 640 et 1200 pixels et `loading="lazy"` pour les images situées plus bas.

Le ruban de texte et le tampon sont décoratifs. Les textes dupliqués et les illustrations décoratives sont masqués aux lecteurs d’écran. Les champs conservent des labels, les boutons de visibilité du mot de passe ont un état explicite et les liens du menu mobile gardent leur nom accessible.

La préférence `prefers-reduced-motion` supprime les animations et transitions. Sans JavaScript, les contenus restent visibles et les formulaires utilisent les routes PHP habituelles. Le graphique des ventes dispose d’une alternative tabulaire.

## Vérifier

`npm run test:browser` teste les interactions réelles et les parcours client/admin, puis dix routes à 320, 390, 768, 1024 et 1440 pixels. Il vérifie aussi le mode de réduction des animations et une inscription sans JavaScript. Les captures sont écrites dans `test-results/browser/`.

Les captures sélectionnées pour le README sont dans `docs/screenshots/`. Elles sont prises avec les mouvements réduits pour rendre tous les éléments visibles de manière stable.

Références : [Intersection Observer](https://developer.mozilla.org/en-US/docs/Web/API/Intersection_Observer_API), [préférence de réduction des animations](https://developer.mozilla.org/en-US/docs/Web/CSS/Reference/At-rules/@media/prefers-reduced-motion), [Element.animate](https://developer.mozilla.org/en-US/docs/Web/API/Element/animate).
