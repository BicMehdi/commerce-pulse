# Démontrer Commerce Pulse en cinq minutes

Ce parcours présente la version 1.0 sur une instance locale. Le démarrage est décrit dans le [README](../README.md). Les données sont fictives ; aucune commande ne déclenche un paiement ou une livraison.

## 1. Explorer les objets — une minute

Ouvrir `http://127.0.0.1:8090/`. Essayer une catégorie, le tri par prix, puis rechercher « carnet ». Ouvrir une fiche et montrer son prix, sa photo et son stock disponible.

Le catalogue provient de la base SQL. Les photos sont stockées dans le projet : elles restent disponibles sans service d’images externe. Les effets sont progressifs et respectent la préférence de réduction des mouvements.

## 2. Passer une commande — une minute

Créer un compte fictif ou se connecter avec `alex@commercepulse.test` et le mot de passe initial `Demo-Pulse-2026!`. Ajouter un produit, modifier sa quantité, puis confirmer la commande.

Montrer le numéro, le statut « À préparer » et les lignes de commande. Retrouver ensuite cette commande dans « Mon espace ». Une quantité de zéro permet de retirer un article du panier.

Le serveur relit les prix et les stocks ; le navigateur ne fixe pas le montant. Les lignes gardent une copie du nom et du prix au moment de la commande. Une deuxième soumission de la même confirmation retrouve la commande existante.

## 3. Administrer les commandes — une minute

Se déconnecter, puis utiliser `admin@commercepulse.test` avec le même mot de passe initial. Dans « Commandes », ouvrir la commande que l’on vient de créer. La faire passer en préparation ou l’annuler pour montrer la restitution du stock.

Dans « Produits », modifier un produit fictif ou en créer un nouveau. L’archivage retire le produit du catalogue public sans effacer les commandes historiques. Un article archivé est retiré du panier avec un message demandant au client de revoir son récapitulatif.

Ces opérations modifient les données de cette démo ; utiliser une instance distincte si l’on souhaite conserver une présentation identique.

## 4. Relier le backend aux données — une minute

Ouvrir « Analyse des ventes », puis « Actualiser l’analyse ». Montrer les indicateurs, le graphique quotidien, son alternative tabulaire et le classement des produits. Télécharger le CSV si l’on veut examiner ses colonnes.

Le parcours est : commandes SQL → export CSV → validation Python → agrégation → rapport JSON → tableau de bord. Les commandes annulées sont exclues de la valeur commandée. Cette valeur ne représente pas un paiement encaissé.

Le pipeline refuse les doublons, les données invalides et les totaux incohérents. Le dernier rapport n’est remplacé qu’après validation. L’export contient des identifiants clients, sans noms ni emails ; il reste pseudonyme.

## 5. Montrer le dépôt — une minute

Dans le dépôt, ouvrir les [décisions d’architecture](ARCHITECTURE.md), le [contrat API](API.md) et les [résultats des tests](TESTING.md). Pour expliquer le code en entretien, suivre le [guide d’apprentissage](LEARNING.md).

Les tests locaux couvrent les comptes, les permissions, les commandes, les stocks, les cas limites du panier et douze routes sur cinq tailles d’écran. Le parcours de commande fonctionne aussi sans JavaScript.

L’environnement MySQL/Docker et les vérifications GitHub Actions sont préparés. Tant qu’ils n’ont pas été exécutés avec succès, présenter les résultats effectivement obtenus avec SQLite, sans revendiquer une validation de concurrence MySQL.

## Retrouver les écrans

- Boutique : `/`
- Connexion et inscription : `/login`, `/register`
- Panier et espace client : `/cart`, `/account`
- Administration : `/admin`
- Produits et commandes : `/admin/products`, `/admin/orders`
- Analyse : `/admin/analytics`
- Catalogue JSON et santé : `/api/products`, `/health`

Les identifiants ci-dessus sont ceux du seed initial. Si `DEMO_PASSWORD` a été changé avant la création des données, utiliser sa valeur. La démo écoute uniquement sur la machine locale et emploie le serveur intégré PHP.
