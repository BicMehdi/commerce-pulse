# Savoir présenter Commerce Pulse

Ce guide sert à comprendre le dépôt avant de le présenter comme une compétence acquise. Pour chaque partie, lire le code, exécuter le parcours et refaire une petite modification soi-même.

## Présentation en une minute

« Commerce Pulse est une boutique PHP avec un espace client et une administration. Le backend utilise PDO et une base relationnelle. Les commandes calculent leurs prix côté serveur et réservent le stock dans une transaction. J’ai également intégré une chaîne Python qui contrôle un export de ventes et produit un tableau de bord. Le projet me permet d’approfondir PHP, SQL, les tests et l’analyse de données. »

Adapter les verbes à ce qui a réellement été compris et réalisé. Le code a été construit avec l’assistance de Codex ; savoir expliquer un choix et reproduire un comportement compte davantage que réciter une présentation.

## Lire dans cet ordre

1. `public/index.php`, `app/Core/Response.php` et une vue : suivre une requête jusqu’à son HTML.
2. `AuthController.php`, `Auth.php`, `Session.php` : comprendre hash, cookie, rôle et CSRF.
3. `Cart.php` et `Orders.php` : comparer les données en session à celles qui font autorité en base.
4. `database/schema.mysql.sql` : dessiner les relations et expliquer chaque clé étrangère.
5. `SalesExport.php` et `analytics/pipeline.py` : retrouver un total à la main depuis le CSV.
6. `tests/` et `.github/workflows/ci.yml` : reproduire un échec avant de le corriger.

## Questions d’entretien à préparer

- Pourquoi stocker un montant en centimes plutôt qu’en float ?
- Que se passe-t-il si deux clients veulent la dernière unité ? Quelle différence entre SQLite et MySQL ici ?
- Que protège un jeton CSRF ? Pourquoi ne suffit-il pas à vérifier le rôle ?
- Pourquoi conserver le prix dans la ligne de commande ?
- Comment éviter deux commandes lors d’un double clic ?
- Pourquoi exclure les commandes annulées de la valeur ? Pourquoi cette valeur n’est-elle pas un paiement encaissé ?
- Comment détecter une ligne CSV en double et préserver le dernier rapport valide ?
- Quels index correspondent aux filtres du catalogue et des commandes ?

## Trois évolutions à réaliser soi-même

1. Ajouter un filtre « en stock » dans le catalogue, avec un test API qui démontre son comportement.
2. Ajouter un indicateur Python du taux d’annulation, avec un cas d’export vide et une définition claire de sa période.
3. Ajouter un journal des changements de statut : table dédiée, transaction, vue admin et test.

Ne pas ajouter une technologie au CV uniquement parce qu’elle apparaît dans ce dépôt. L’ajouter après l’avoir utilisée et expliquée sans assistance.
