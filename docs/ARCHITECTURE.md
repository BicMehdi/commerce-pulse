# Décisions et parcours d’une commande

## MVC simple

`public/index.php` associe une méthode et une URL à un contrôleur. Avant toute route admin, le rôle est vérifié ; avant toute requête POST, le jeton CSRF est vérifié. Le contrôleur coordonne les services et choisit une vue. Les templates échappent les valeurs dynamiques avec `e()`.

La session contient l’identifiant du compte et des couples identifiant-produit/quantité. Elle ne fait pas autorité sur les prix. Le service catalogue et les commandes utilisent des requêtes PDO préparées ; les fragments de tri sont choisis dans une liste fermée.

## Transaction de commande

1. Démarrer une transaction et verrouiller le compte sous MySQL, pour sérialiser les confirmations d’un même client.
2. Rechercher une commande existante pour la clé d’intention : une nouvelle soumission retourne la même commande.
3. Lire les produits avec `SELECT … FOR UPDATE` dans l’ordre de leurs identifiants. Vérifier la visibilité, les quantités et les stocks.
4. Calculer le total en centimes depuis les prix en base.
5. Insérer commande et lignes. Chaque ligne conserve le nom, la catégorie et le prix au moment de l’achat.
6. Décrémenter les stocks et valider la transaction. Une erreur annule l’ensemble.

La contrainte unique `(user_id, idempotency_key)` protège le dernier niveau. Tout changement de panier crée une nouvelle clé. Un nouvel onglet avec une ancienne clé reçoit une erreur 409.

SQLite sert d’alternative locale ; il ne possède pas `FOR UPDATE`. Les parcours fonctionnels sont partagés, mais une campagne de concurrence doit être faite sur MySQL. Les erreurs de verrou/deadlock ne sont pas réessayées automatiquement dans cette version.

## Cycle et stock

`pending → processing → shipped` ; `pending` ou `processing` peuvent devenir `cancelled`. Les états finaux ne repartent pas vers un état actif. Une annulation rend le stock dans la même transaction que le changement de statut. Une deuxième annulation est refusée. Les produits référencés sont archivés plutôt que supprimés : les commandes gardent leur historique.

## Authentification

Les mots de passe sont hachés avec l’algorithme PHP par défaut. Leur longueur maximale est limitée à 72 octets pour éviter une troncature silencieuse avec bcrypt. Les sessions sont renouvelées à la connexion et à la déconnexion. Les cookies sont HttpOnly, SameSite=Lax et Secure lorsque HTTPS est détecté. Un utilisateur accédant à la commande d’un autre reçoit 404. Huit tentatives de connexion infructueuses sur un couple IP/email en quinze minutes déclenchent un blocage temporaire.

Ce mécanisme de limitation est pédagogique : il ne remplace pas une protection distribuée contre une attaque multi-IP. Un déploiement derrière un proxy demande une configuration HTTPS fiable et explicite.

## Analyse de données

L’export lit les lignes de commandes dans une transaction cohérente, sans noms ni emails. Le pipeline Python contrôle le schéma, les nombres positifs, les dates, les statuts, l’unicité des lignes et la cohérence des métadonnées et des totaux. Le rapport n’est remplacé qu’après validation complète ; il reste dans `storage/`, hors du répertoire public.

L’admin peut déclencher l’actualisation. Les commandes de sous-processus sont fixes et passées sous forme de tableaux : aucune valeur issue de la requête n’est interpolée dans un shell. Cette version exécute le traitement de façon synchrone et conserve l’export en mémoire côté PDO ; un volume important demanderait une tâche asynchrone, un export en streaming et une limite de durée.
