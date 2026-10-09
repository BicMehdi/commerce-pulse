# API du catalogue

## `GET /health`

Réponse JSON 200 : `{"status":"ok","database":"mysql"}` (ou `sqlite`). Une indisponibilité de base donne une réponse JSON 500. Ce point vérifie la connexion, pas la validité d’un rapport analytique.

## `GET /api/products`

Catalogue public des produits actifs. Paramètres facultatifs :

- `q` : recherche dans le nom et la description (100 caractères maximum).
- `category` : identifiant entier d’une catégorie.
- `sort` : `newest`, `price_asc`, `price_desc` ou `name`.
- `page` : page à partir de 1, 12 produits par page.

```json
{
  "data": [
    {"id": 1, "name": "Clavier Studio", "description": "…", "category": "Équipement", "price_cents": 8900, "stock": 42}
  ],
  "meta": {"page": 1, "pages": 1, "total": 8, "currency": "EUR"}
}
```

Les montants sont des entiers en centimes d’euro. Le stock est indicatif : le stock définitif est contrôlé dans la transaction de commande. Les prix renvoyés par cette API ne peuvent pas être transmis comme prix faisant autorité au checkout.

## Export et commandes de l’administration

`GET /admin/sales.csv` nécessite une session admin et renvoie un CSV des lignes historiques, tous statuts confondus. La définition des colonnes est dans `SalesExport::HEADERS` et `analytics.pipeline.HEADERS`.

`POST /admin/analytics/refresh` nécessite une session admin et le champ `_csrf`. Les routes de création, modification et archivage de produits et de transition des commandes utilisent la même protection. Ces routes sont des formulaires HTML, pas une API REST d’écriture JSON.

La version actuelle ne fournit pas de jeton d’API externe, webhook ni API d’administration publique.
