# Vérification

## Résultat local du 10 octobre 2026

Vérifié sur Windows avec PHP 8.5.11, PDO SQLite 3.53.4 et Python 3.13.7 :

- 20 assertions PHP (dont transactions, stock et idempotence) réussies sur une base de test dédiée.
- 10 tests Python de contrôle/agrégation réussis.
- 6 tests HTTP client/admin réussis, avec création/modification/archivage de produit et échappement HTML.
- 137 vérifications Playwright dans Edge réussies : effets au pointeur et au défilement, formulaires, commande, gestion des produits, annulation et restitution du stock, régénération de l’analyse, infobulle du graphique et montants contenus dans leurs cartes.
- Dix routes publiques/admin testées à 320, 390, 768, 1024 et 1440 pixels : aucun débordement horizontal de la page. Les tableaux gardent un défilement interne.
- Mode de réduction des animations et inscription sans JavaScript vérifiés ; aucune erreur de script ou console relevée. Syntaxe des 34 fichiers PHP et des scripts JavaScript vérifiée.

La configuration Compose est valide. Le moteur Docker Desktop n’a pas démarré sur cette machine ; le build Docker et les tests MySQL n’ont donc pas été exécutés localement. Le workflow GitHub Actions est préparé, mais son résultat reste à vérifier après publication.

## Couches testées

- PHP : validation des montants/stock et transitions d’états.
- Base dédiée : prix serveur, décrément du stock, double confirmation, rollback d’un panier incomplet, snapshots historiques, restitution exactement une fois et quantités invalides.
- Python : 10 tests de qualité/agrégation ; les sous-cas couvrent nombres, dates et statuts invalides.
- HTTP : santé, contrat API, accès admin, CSRF, inscription, commande, prix falsifié ignoré, rejeu et accès à une commande d’autrui, CSV et régénération de rapport.
- Navigateur : interactions réelles, parcours client/admin, isolation des commandes, affichage responsive, préférence de réduction des animations et formulaires sans JavaScript.
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

Les fixtures de base sont isolées et supprimées en fin de test ; les données préexistantes ne sont pas tronquées. Les tests HTTP sont réservés à localhost et ajoutent quelques données fictives dans la démo.

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
