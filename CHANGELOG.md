# Journal des versions

## 1.0.0 — 10 octobre 2026

Première version du projet portfolio Commerce Pulse, avec un parcours e-commerce de démonstration et une analyse des ventes.

- Catalogue avec recherche, filtres, tri, pagination et fiches produit.
- Inscription et connexion, panier, confirmation de commande et historique client.
- Administration des produits, stocks et commandes ; archivage et annulation avec restitution du stock.
- Transactions, prix en centimes, snapshots historiques et confirmation idempotente.
- Export CSV, contrôles de qualité Python et tableau de bord des ventes.
- Interface éditoriale responsive, photos locales créditées et effets compatibles avec la réduction des mouvements.
- Inscription et commande utilisables sans JavaScript.
- Correction du panier après archivage d’un produit ; refus des quantités décimales ou textuelles.
- Documentation du démarrage, de l’architecture, des données, de la démonstration et des vérifications.

Validation locale sous Windows/PHP/SQLite : 193 contrôles navigateur, 15 scénarios HTTP, 20 assertions PHP et 10 tests Python réussis. Docker/MySQL et le workflow GitHub Actions sont fournis ; leur exécution n’a pas encore été confirmée. Cette version simule les commandes sans paiement ni livraison réels.
