## Authentification API

L’API utilise **Laravel Sanctum** pour gérer l’authentification par token.

Les routes ci-dessous permettent de créer un token d’accès, de supprimer le token actuellement utilisé ou de supprimer tous les tokens d’un utilisateur connecté.

---

## Créer un token

### Endpoint

```http
POST /api/token/create
```

### Description

Cette route permet à un utilisateur de générer un token d’accès API à partir de son adresse email, de son mot de passe et du nom de l’application utilisée.

Si les identifiants sont corrects, un token Sanctum est créé et retourné en réponse.

### Paramètres attendus

| Champ      | Type   | Obligatoire | Description                                       |
| ---------- | ------ | ----------- | ------------------------------------------------- |
| `email`    | string | Oui         | Adresse email de l’utilisateur                    |
| `password` | string | Oui         | Mot de passe de l’utilisateur                     |
| `app_name` | string | Oui         | Nom de l’application ou du client utilisant l’API |

### Exemple de requête

```json
{
  "email": "user@example.com",
  "password": "password",
  "app_name": "MonApplication"
}
```

### Exemple de réponse

```text
1|abcdef123456789...
```

Le token retourné doit ensuite être utilisé dans les requêtes protégées avec l’en-tête suivant :

```http
Authorization: Bearer 1|abcdef123456789...
```

### Erreurs possibles

Si les identifiants sont incorrects, l’API retourne une erreur de validation :

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

---

## Supprimer le token actuel

### Endpoint

```http
DELETE /api/token/destroy
```

### Description

Cette route permet de supprimer uniquement le token actuellement utilisé pour authentifier la requête.

Elle est utile pour déconnecter un utilisateur d’un appareil ou d’une application spécifique sans supprimer ses autres tokens actifs.

### Authentification requise

Oui.

La requête doit contenir un token valide dans l’en-tête :

```http
Authorization: Bearer {token}
```

### Exemple de requête

```http
DELETE /api/token/destroy
Authorization: Bearer 1|abcdef123456789...
```

### Exemple de réponse

Cette route ne retourne pas de contenu particulier si la suppression réussit.

---

## Supprimer tous les tokens

### Endpoint

```http
DELETE /api/token/destroy/all
```

### Description

Cette route permet de supprimer tous les tokens associés à l’utilisateur connecté.

Elle est utile pour déconnecter l’utilisateur de tous ses appareils ou révoquer tous ses accès API.

### Authentification requise

Oui.

La requête doit contenir un token valide dans l’en-tête :

```http
Authorization: Bearer {token}
```

### Exemple de requête

```http
DELETE /api/token/destroy/all
Authorization: Bearer 1|abcdef123456789...
```

### Exemple de réponse

Cette route ne retourne pas de contenu particulier si la suppression réussit.

---

## Créer une plaque d’immatriculation

### Endpoint

```http
POST /api/plate/create
```

### Description

Cette route permet de créer une nouvelle plaque d’immatriculation pour l’utilisateur authentifié.

La route est protégée par **Laravel Sanctum**, ce qui signifie que l’utilisateur doit envoyer un token valide dans l’en-tête `Authorization`.

Lors de la création, le numéro de plaque est généré automatiquement côté serveur, et la plaque est associée à l’utilisateur connecté grâce à son token d’authentification.

### Authentification requise

Oui.

La requête doit contenir un token valide dans l’en-tête :

```http
Authorization: Bearer {token}
```

### Paramètres attendus

Aucun paramètre n’est nécessaire dans le body de la requête.

### Exemple de requête

```http
POST /api/plate/create
Authorization: Bearer 1|abcdef123456789...
```

### Exemple de réponse

```json
{
  "id": 1,
  "license_plate_number": "CA-812-AA",
  "user_id": 1,
  "created_at": "2026-06-10T12:00:00.000000Z",
  "updated_at": "2026-06-10T12:00:00.000000Z"
}
```

### Erreurs possibles

Si le token est absent, invalide ou expiré, l’API retourne une erreur d’authentification :

```json
{
  "message": "Unauthenticated."
}
```

---


## Journalisation des appels API

À chaque appel sur ces routes, une entrée est ajoutée dans les logs API grâce à la méthode :

```php
ApiLogsController::addLog($request, $user->email);
```

Cela permet de garder une trace des actions effectuées sur les tokens, comme la création ou la suppression d’un accès API.

---

## Résumé des routes

| Méthode  | Endpoint                 | Authentification | Description                                        |
| -------- | ------------------------ | ---------------- | -------------------------------------------------- |
| `POST`   | `/api/token/create`      | Non              | Crée un token d’accès API                          |
| `DELETE` | `/api/token/destroy`     | Oui              | Supprime le token actuellement utilisé             |
| `DELETE` | `/api/token/destroy/all` | Oui              | Supprime tous les tokens de l’utilisateur connecté |
