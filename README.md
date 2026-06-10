# PlateAPI

PlateAPI est un projet développé avec Laravel dont l’objectif est de me permettre de m’exercer à la création et à la gestion d’API.

Ce projet me sert de support d’apprentissage pour mieux comprendre le fonctionnement de Laravel, la structuration d’une API, les routes, les contrôleurs, les modèles, ainsi que les bonnes pratiques de développement backend.

---

## Fonctionnalités

L’API permet de gérer :

* l’authentification par token avec Laravel Sanctum ;
* les sessions actives d’un utilisateur ;
* le profil utilisateur ;
* l’historique des actions effectuées sur l’API ;
* la création et la consultation de plaques d’immatriculation ;
* le transfert de plaques entre utilisateurs ;
* l’historique des transferts d’une plaque.

---

## Installation et lancement du projet

### Cloner le projet

```bash
git clone https://github.com/ArthurCOTTEY/PlateAPI.git
```

### Accéder au dossier du projet

```bash
cd PlateAPI
```

### Installer les dépendances PHP

```bash
composer install
```

### Créer le fichier d’environnement

Sous Windows, utiliser la commande suivante :

```cmd
copy .env.example .env
```

Sous Linux ou macOS :

```bash
cp .env.example .env
```

### Générer la clé de l’application

```bash
php artisan key:generate
```

### Exécuter les migrations et les seeders

```bash
php artisan migrate --seed
```

Cette commande permet de créer les tables en base de données et d’insérer les données par défaut.

### Lancer le serveur Laravel

```bash
php artisan serve
```

L’application sera ensuite disponible à l’adresse indiquée dans le terminal, généralement :

```text
http://127.0.0.1:8000
```

---

## Compte administrateur par défaut

Après l’exécution des seeders, un compte administrateur est créé par défaut.

| Champ        | Valeur              |
| ------------ | ------------------- |
| Email        | `admin@example.com` |
| Mot de passe | `password`          |

Exemple de connexion API :

```json
{
  "email": "admin@example.com",
  "password": "password",
  "app_name": "MonApplication"
}
```

---

## Documentation API générée

Une documentation générée de l’API est disponible à l’adresse suivante :

```text
{{APP_URL}}/docs
```

Exemple en local :

```text
http://127.0.0.1:8000/docs
```

---


## Authentification API

L’API utilise **Laravel Sanctum** pour gérer l’authentification par token.

Pour accéder aux routes protégées, l’utilisateur doit d’abord créer un token d’accès à partir de son email, de son mot de passe et du nom de l’application utilisée.

Le token reçu doit ensuite être envoyé dans l’en-tête `Authorization` :

```http
Authorization: Bearer {token}
```

---

## Créer un token

### Endpoint

```http
POST /api/tokens/create
```

### Description

Cette route permet à un utilisateur de générer un token d’accès API.

Si les identifiants sont corrects, un token Sanctum est créé et retourné dans une réponse JSON.

### Authentification requise

Non.

### Paramètres attendus

| Champ      | Type   | Obligatoire | Description                                       |
| ---------- | ------ | ----------: | ------------------------------------------------- |
| `email`    | string |         Oui | Adresse email de l’utilisateur                    |
| `password` | string |         Oui | Mot de passe de l’utilisateur                     |
| `app_name` | string |         Oui | Nom de l’application ou du client utilisant l’API |

### Exemple de requête

```json
{
  "email": "user@example.com",
  "password": "password",
  "app_name": "MonApplication"
}
```

### Exemple de réponse

```json
{
  "token": "1|abcdef123456789..."
}
```

### Erreurs possibles

#### Identifiants incorrects

```json
{
  "message": "The provided credentials are incorrect.",
  "errors": {
    "email": [
      "The provided credentials are incorrect."
    ]
  }
}
```

#### Champs manquants ou invalides

```json
{
  "message": "The email field is required.",
  "errors": {
    "email": [
      "The email field is required."
    ]
  }
}
```

---

## Sessions actives

### Endpoint

```http
GET /api/tokens/sessions
```

### Description

Cette route permet de récupérer la liste des tokens actifs de l’utilisateur connecté.

### Authentification requise

Oui.

### Exemple de requête

```http
GET /api/tokens/sessions
Authorization: Bearer 1|abcdef123456789...
```

### Exemple de réponse

```json
{
  "data": [
    {
      "id": 1,
      "name": "MonApplication",
      "created_at": "2026-06-10T12:00:00.000000Z"
    }
  ]
}
```

---

## Supprimer le token actuel

### Endpoint

```http
DELETE /api/tokens/destroy
```

### Description

Cette route permet de supprimer uniquement le token actuellement utilisé pour authentifier la requête.

Elle est utile pour déconnecter l’utilisateur de l’appareil ou de l’application actuellement utilisé.

### Authentification requise

Oui.

### Exemple de requête

```http
DELETE /api/tokens/destroy
Authorization: Bearer 1|abcdef123456789...
```

### Exemple de réponse

Cette route retourne une réponse vide avec le code HTTP `204 No Content`.

---

## Supprimer tous les tokens

### Endpoint

```http
DELETE /api/tokens/destroy/all
```

### Description

Cette route permet de supprimer tous les tokens associés à l’utilisateur connecté.

Elle est utile pour déconnecter l’utilisateur de tous ses appareils ou révoquer tous ses accès API.

### Authentification requise

Oui.

### Exemple de requête

```http
DELETE /api/tokens/destroy/all
Authorization: Bearer 1|abcdef123456789...
```

### Exemple de réponse

Cette route retourne une réponse vide avec le code HTTP `204 No Content`.

---

## Afficher le compte utilisateur

### Endpoint

```http
GET /api/account
```

### Description

Cette route permet de récupérer les informations de l’utilisateur connecté.

### Authentification requise

Oui.

### Exemple de réponse

```json
{
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "user@example.com"
  }
}
```

---

## Afficher l’historique des actions

### Endpoint

```http
GET /api/account/actions
```

### Description

Cette route retourne l’historique des actions API effectuées par l’utilisateur connecté.

Les résultats sont paginés par groupes de 10 éléments.

### Authentification requise

Oui.

### Exemple de réponse

```json
{
  "data": [
    {
      "id": 1,
      "method": "GET",
      "ip": "127.0.0.1",
      "email": "user@example.com",
      "at": "2026-06-10T12:00:00.000000Z"
    }
  ],
  "links": {
    "first": "http://localhost/api/account/actions?page=1",
    "last": "http://localhost/api/account/actions?page=1",
    "prev": null,
    "next": null
  },
  "meta": {
    "current_page": 1,
    "per_page": 10,
    "total": 1
  }
}
```

---

## Mettre à jour le compte utilisateur

### Endpoint

```http
PATCH /api/account/update
```

### Description

Cette route permet de modifier les informations du compte utilisateur connecté.

Les champs sont optionnels : seuls les champs envoyés dans la requête seront mis à jour.

Si l’email est modifié, les anciens logs API liés à l’utilisateur sont également mis à jour avec la nouvelle adresse email.

### Authentification requise

Oui.

### Paramètres acceptés

| Champ      | Type   | Obligatoire | Description                                |
| ---------- | ------ | ----------: | ------------------------------------------ |
| `name`     | string |         Non | Nouveau nom de l’utilisateur               |
| `email`    | string |         Non | Nouvelle adresse email                     |
| `password` | string |         Non | Nouveau mot de passe, minimum 8 caractères |

### Exemple de requête

```json
{
  "name": "Jane Doe",
  "email": "jane@example.com",
  "password": "newpassword123"
}
```

### Exemple de réponse

```json
{
  "data": {
    "id": 1,
    "name": "Jane Doe",
    "email": "jane@example.com"
  }
}
```

---

# Plaques d’immatriculation

## Lister les plaques

### Endpoint

```http
GET /api/plates/all
```

### Description

Cette route permet de récupérer les plaques d’immatriculation appartenant à l’utilisateur connecté.

Les résultats sont paginés par groupes de 10 éléments.

### Authentification requise

Oui.

### Exemple de requête

```http
GET /api/plates/all
Authorization: Bearer 1|abcdef123456789...
```

### Exemple de réponse

```json
{
  "data": [
    {
      "id": 1,
      "license_plate_number": "AA-001-AA",
      "created_at": "2026-06-10T12:00:00.000000Z",
      "updated_at": "2026-06-10T12:00:00.000000Z"
    }
  ],
  "links": {
    "first": "http://localhost/api/plates/all?page=1",
    "last": "http://localhost/api/plates/all?page=1",
    "prev": null,
    "next": null
  },
  "meta": {
    "current_page": 1,
    "per_page": 10,
    "total": 1
  }
}
```

---

## Créer une plaque d’immatriculation

### Endpoint

```http
POST /api/plates/create
```

### Description

Cette route permet de créer une nouvelle plaque d’immatriculation pour l’utilisateur authentifié.

Aucun paramètre n’est nécessaire dans le body de la requête.
Le numéro de plaque est généré automatiquement côté serveur.

Le format généré est de type :

```text
AA-001-AA
```

Lorsqu’une plaque est créée, elle est automatiquement associée à l’utilisateur connecté.

### Authentification requise

Oui.

### Paramètres attendus

Aucun.

### Exemple de requête

```http
POST /api/plates/create
Authorization: Bearer 1|abcdef123456789...
```

### Exemple de réponse

```json
{
  "data": {
    "id": 1,
    "license_plate_number": "AA-001-AA",
    "created_at": "2026-06-10T12:00:00.000000Z",
    "updated_at": "2026-06-10T12:00:00.000000Z"
  }
}
```

---

## Afficher une plaque

### Endpoint

```http
GET /api/plates/{plate}
```

### Description

Cette route permet de récupérer les informations d’une plaque appartenant à l’utilisateur connecté.

L’utilisateur ne peut consulter que ses propres plaques.

### Authentification requise

Oui.

### Paramètres d’URL

| Paramètre | Type    | Description              |
| --------- | ------- | ------------------------ |
| `plate`   | integer | Identifiant de la plaque |

### Exemple de requête

```http
GET /api/plates/1
Authorization: Bearer 1|abcdef123456789...
```

### Exemple de réponse

```json
{
  "data": {
    "id": 1,
    "license_plate_number": "AA-001-AA",
    "created_at": "2026-06-10T12:00:00.000000Z",
    "updated_at": "2026-06-10T12:00:00.000000Z"
  }
}
```

### Erreur possible

```json
{
  "message": "Plate not found"
}
```

---

## Transférer une plaque

### Endpoint

```http
PATCH /api/plates/transfer
```

### Description

Cette route permet de transférer une plaque de l’utilisateur connecté vers un autre utilisateur.

L’utilisateur connecté doit être propriétaire de la plaque pour pouvoir la transférer.

Lors du transfert, une entrée est ajoutée dans l’historique des transferts.

### Authentification requise

Oui.

### Paramètres attendus

| Champ        | Type    | Obligatoire | Description                                        |
| ------------ | ------- | ----------: | -------------------------------------------------- |
| `plate_id`   | integer |         Oui | Identifiant de la plaque à transférer              |
| `to_user_id` | integer |         Oui | Identifiant de l’utilisateur qui recevra la plaque |

### Exemple de requête

```json
{
  "plate_id": 1,
  "to_user_id": 2
}
```

### Exemple de réponse

```json
{
  "message": "Plate transferred successfully",
  "plate_id": 1,
  "license_plate_number": "AA-001-AA"
}
```

### Erreurs possibles

#### Plaque introuvable ou non possédée par l’utilisateur connecté

```json
{
  "message": "Plate not found"
}
```

#### Champs invalides

```json
{
  "message": "The plate id field is required.",
  "errors": {
    "plate_id": [
      "The plate id field is required."
    ]
  }
}
```

---

## Historique des transferts d’une plaque

### Endpoint

```http
GET /api/plates/transfer/{plate}/history
```

### Description

Cette route permet de récupérer l’historique des transferts d’une plaque appartenant à l’utilisateur connecté.

L’historique est trié du plus récent au plus ancien.

### Authentification requise

Oui.

### Paramètres d’URL

| Paramètre | Type    | Description              |
| --------- | ------- | ------------------------ |
| `plate`   | integer | Identifiant de la plaque |

### Exemple de requête

```http
GET /api/plates/transfer/1/history
Authorization: Bearer 1|abcdef123456789...
```

### Exemple de réponse

```json
{
  "data": [
    {
      "id": 1,
      "plate": {
        "data": {
          "id": 1,
          "license_plate_number": "AA-001-AA",
          "created_at": "2026-06-10T12:00:00.000000Z",
          "updated_at": "2026-06-10T12:00:00.000000Z"
        }
      },
      "from_user": {
        "data": {
          "id": 1,
          "name": "John Doe",
          "email": "user@example.com"
        }
      },
      "to_user": {
        "data": {
          "id": 2,
          "name": "Jane Doe",
          "email": "jane@example.com"
        }
      },
      "transferred_at": "2026-06-10T12:00:00.000000Z"
    }
  ]
}
```

### Erreur possible

```json
{
  "message": "Plate not found"
}
```

---

# Journalisation des appels API

À chaque appel important sur l’API, une entrée est ajoutée dans les logs API.

Les informations enregistrées sont :

| Champ     | Description                  |
| --------- | ---------------------------- |
| `method`  | Méthode HTTP utilisée        |
| `route`   | Route appelée                |
| `ip`      | Adresse IP du client         |
| `email`   | Email de l’utilisateur       |
| `user_id` | Identifiant de l’utilisateur |

Ces logs permettent de suivre les actions effectuées par chaque utilisateur sur l’API.

---

# Modèles principaux

## User

Le modèle `User` représente un utilisateur de l’application.

Il possède plusieurs relations :

* un utilisateur peut avoir plusieurs plaques ;
* un utilisateur peut avoir plusieurs logs API ;
* un utilisateur peut avoir plusieurs tokens Sanctum.

## Plate

Le modèle `Plate` représente une plaque d’immatriculation.

Lors de sa création :

* un numéro de plaque est généré automatiquement ;
* la plaque est associée à l’utilisateur connecté.

## PlateTransfersHistory

Le modèle `PlateTransfersHistory` représente l’historique des transferts de plaques.

Lors de sa création :

* l’utilisateur connecté est enregistré comme ancien propriétaire ;
* la date du transfert est automatiquement renseignée.

## ApiLogs

Le modèle `ApiLogs` permet d’enregistrer les appels effectués sur l’API.

---

# Résumé des routes

| Méthode  | Endpoint                               | Authentification | Description                                      |
| -------- | -------------------------------------- | ---------------- | ------------------------------------------------ |
| `POST`   | `/api/tokens/create`                   | Non              | Crée un token d’accès API                        |
| `GET`    | `/api/tokens/sessions`                 | Oui              | Liste les tokens actifs de l’utilisateur         |
| `DELETE` | `/api/tokens/destroy`                  | Oui              | Supprime le token actuellement utilisé           |
| `DELETE` | `/api/tokens/destroy/all`              | Oui              | Supprime tous les tokens de l’utilisateur        |
| `GET`    | `/api/account`                         | Oui              | Affiche le compte utilisateur                    |
| `GET`    | `/api/account/actions`                 | Oui              | Affiche l’historique des actions API             |
| `PATCH`  | `/api/account/update`                  | Oui              | Met à jour le compte utilisateur                 |
| `GET`    | `/api/plates/all`                      | Oui              | Liste les plaques de l’utilisateur               |
| `POST`   | `/api/plates/create`                   | Oui              | Crée une nouvelle plaque                         |
| `PATCH`  | `/api/plates/transfer`                 | Oui              | Transfère une plaque à un autre utilisateur      |
| `GET`    | `/api/plates/transfer/{plate}/history` | Oui              | Affiche l’historique des transferts d’une plaque |
| `GET`    | `/api/plates/{plate}`                  | Oui              | Affiche une plaque précise                       |

---

# Codes HTTP utilisés

| Code                        | Signification                         |
| --------------------------- | ------------------------------------- |
| `200 OK`                    | Requête réussie                       |
| `201 Created`               | Ressource créée avec succès           |
| `204 No Content`            | Requête réussie sans contenu retourné |
| `401 Unauthorized`          | Token absent, invalide ou expiré      |
| `404 Not Found`             | Ressource introuvable                 |
| `422 Unprocessable Content` | Données de validation invalides       |

---

# Notes

Cette API est un projet d’apprentissage.
Elle a pour but de pratiquer la création d’API avec Laravel, l’utilisation de Laravel Sanctum, les relations entre modèles, les resources JSON, la validation des requêtes et la journalisation des appels API.
