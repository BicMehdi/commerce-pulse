# Vérification

## Résultat local du 10 octobre 2026

Vérifié sur Windows avec PHP 8.5.11, PDO SQLite 3.53.4 et Python 3.13.7 :

- 20 assertions PHP (dont transactions, stock et idempotence) réussies sur une base de test dédiée.
- 10 tests Python de contrôle/agrégation réussis.
- 15 tests HTTP client/admin réussis, avec sous-cas de validation, contrôle des sessions et des permissions, limitation des connexions et situations de stock modifié.
- 193 vérifications Playwright dans Edge réussies : chargement des photos locales, effets au pointeur et au défilement, formulaires, commandes, gestion des produits, annulation et restitution du stock, régénération de l’analyse, infobulle du graphique et montants contenus dans leurs cartes.
- Douze routes publiques/client/admin testées à 320, 390, 768, 1024 et 1440 pixels, dont le panier rempli, l’espace client et le suivi de commande : aucun débordement horizontal de la page. Les tableaux gardent un défilement interne.
- Mode de réduction des animations, inscription, ajout au panier et commande sans JavaScript vérifiés ; aucune erreur de script ou console relevée. Syntaxe des 34 fichiers PHP et des scripts JavaScript vérifiée.

Les parcours HTTP et navigateur de cette session ont tourné sur une démo distincte au port 8091 avec une base SQLite dédiée. Le rapport de la démo principale a ensuite été régénéré depuis sa propre base.

Deux problèmes du panier ont été reproduits puis corrigés :

- Un produit archivé disparaissait visuellement mais restait dans la session, le compteur et la commande. Il est maintenant retiré avec un message explicite ; la confirmation précédente est invalidée pour permettre au client de revoir le panier. Le reste de la commande peut ensuite être confirmé.
- Les quantités décimales ou textuelles étaient converties silencieusement en entiers ; elles sont maintenant refusées sans modifier le panier. La suppression avec une quantité de zéro reste possible, même pour un produit retiré du catalogue.

La configuration Compose est valide. Le moteur Docker Desktop n’a pas démarré sur cette machine ; le build Docker et les tests MySQL n’ont donc pas été exécutés localement. Le workflow GitHub Actions est préparé, mais son résultat reste à vérifier après publication.

## Couches testées

- PHP : validation des montants/stock et transitions d’états.
- Base dédiée : prix serveur, décrément du stock, double confirmation, rollback d’un panier incomplet, snapshots historiques, restitution exactement une fois et quantités invalides.
- Python : 10 tests de qualité/agrégation ; les sous-cas couvrent nombres, dates et statuts invalides.
- HTTP : santé, contrat API et filtres, accès admin y compris en POST, CSRF invalide/tableau, fichiers privés inaccessibles, validation d’inscription, email en double, rotation de session et protection du cookie, rôle admin non attribuable à l’inscription, déconnexion et ancien jeton invalidé, limite de connexion, panier périmé, archivage et changement de stock, commande atomique, prix falsifié ignoré, rejeu et accès à une commande d’autrui, validation des produits, transitions jusqu’à l’expédition, CSV et régénération de rapport.
- Navigateur : interactions réelles, parcours client/admin, isolation des commandes, panier nettoyé après archivage avec message et compteur cohérents, retrait par quantité zéro, affichage responsive des pages avec données, préférence de réduction des animations et commande sans JavaScript.
- Visuel : captures de l’accueil, de l’inscription et de l’administration revues sur ordinateur et mobile.

## MySQL dans Docker

```sh
docker compose up -d --build --wait
docker compose exec db sh -c 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" -e "CREATE DATABASE IF NOT EXISTS commerce_pulse_test; GRANT ALL ON commerce_pulse_test.* TO '\''pulse'\''@'\''%'\'';"'
docker compose exec -e APP_ENV=testing -e DB_DATABASE=commerce_pulse_test app php bin/migrate.php
docker compose exec -e APP_ENV=testing -e DB_DATABASE=commerce_pulse_test app php tests/run.php --database
```

Ces mêmes étapes sont automatisées par le workflow GitHub Actions. Il faut consulter le résultat effectif du workflow avant de considérer l’environnement MySQL validé.

## SQLite sous PowerShell

```powershell
$env:APP_ENV='testing'
$env:DB_DRIVER='sqlite'
$env:DB_SQLITE_PATH=Join-Path (Get-Location) 'storage\integration.test.sqlite'
php bin/migrate.php
php tests/run.php --database
```

Les fixtures des tests PHP de base sont isolées et supprimées en fin de test ; les données préexistantes ne sont pas tronquées. Les tests HTTP et navigateur sont réservés à localhost et créent des comptes, produits et commandes fictifs. Utiliser une démo de test dédiée. Les fixtures de produits HTTP sont archivées en fin de scénario ; certaines commandes HTTP restent enregistrées.

Le CSV et le rapport JSON sont actuellement propres au dossier de l’application, pas à la base sélectionnée. Si deux serveurs partagent ce dossier avec des bases différentes, régénérer le rapport depuis la base de la démo principale après les tests, ou utiliser des dossiers distincts.

## Navigateur

Lancer une démo locale, puis :

```sh
npm ci
npx playwright install chromium
npm run test:browser
```

`BASE_URL` permet de choisir un autre port local. Sous PowerShell avec Edge installé, `$env:BROWSER_CHANNEL='msedge'` permet d’utiliser Edge au lieu d’installer Chromium. Les captures se trouvent dans `test-results/browser/`.

Le script crée des comptes fictifs et une commande annulée. Le produit créé pour le test est ensuite archivé ; l’annulation restitue le stock commandé. Il doit être exécuté uniquement sur une démo locale. Le workflow CI utilise Chromium et le port 18090 ; cette configuration reste à confirmer par un premier run GitHub Actions.

## Ce qui reste à tester

Les tests existants ne constituent pas un audit de sécurité complet ni une validation de production. Ajouter un test de concurrence MySQL réel, des tests de charge et les situations de deadlock avant d’élargir le périmètre.
