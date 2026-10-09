# Vérification

## Couches testées

- PHP : validation des montants/stock et transitions d’états.
- Base dédiée : prix serveur, décrément du stock, double confirmation, rollback d’un panier incomplet, snapshots historiques, restitution exactement une fois et quantités invalides.
- Python : 10 tests de qualité/agrégation ; les sous-cas couvrent nombres, dates et statuts invalides.
- HTTP : santé, contrat API, accès admin, CSRF, inscription, commande, prix falsifié ignoré, rejeu et accès à une commande d’autrui, CSV et régénération de rapport.
- JavaScript : contrôle de syntaxe ; revue visuelle sur desktop et mobile.

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

## Ce qui reste à tester

Les tests existants ne constituent pas un audit de sécurité complet ni une validation de production. Ajouter un test de concurrence MySQL réel, des tests de charge, des tests navigateur automatisés dans la CI et les situations de deadlock avant d’élargir le périmètre.
