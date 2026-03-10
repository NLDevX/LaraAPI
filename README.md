# Laravel API Response Helper

Ensemble de classes utilitaires pour standardiser les réponses JSON d'une API Laravel. Fournit un helper statique, des fonctions globales et un contrôleur de base prêt à l'emploi.

---

## Fonctionnalités

- Réponses JSON structurées et uniformes sur tout le projet
- Support complet des codes HTTP via l'enum `HttpStatus`
- Journalisation automatique de chaque réponse (niveau adapté au code HTTP)
- Gestion des réponses paginées avec métadonnées et liens
- Fonctions globales `api_*` utilisables partout dans l'application
- Contrôleur de base `ApiController` à étendre dans vos controllers

---

## Fichiers

| Fichier | Emplacement |
|---------|-------------|
| Enum HTTP | `app/Enums/HttpStatus.php` |
| Helper statique | `app/Support/Helpers/ApiHelper.php` |
| Fonctions globales | `app/Support/Helpers/ApiHelperFunctions.php` |
| Contrôleur de base | `app/Core/Controllers/ApiController.php` |

---

## Installation

**1. Copier les fichiers dans votre projet**

**2. Enregistrer les fonctions globales dans `composer.json` :**

```json
"autoload": {
    "files": [
        "app/Support/Helpers/ApiHelperFunctions.php"
    ]
}
```

**3. Mettre à jour l'autoloader :**

```bash
composer dump-autoload
```

**4. Ajouter le channel de log dans `config/logging.php` :**

```php
'channels' => [
    'api' => [
        'driver'     => 'daily',
        'path'       => storage_path('logs/api.log'),
        'level'      => 'info',
        'days'       => 30,
        'permission' => 0664,
    ],
],
```

---

## Utilisation

### Via le contrôleur de base

La façon recommandée. Étendez `ApiController` dans vos controllers :

```php
use App\Core\Controllers\ApiController;

class UserController extends ApiController
{
    public function index()
    {
        $users = User::paginate(15);
        return $this->jsonPaginated($users, 'Liste des utilisateurs');
    }

    public function show(User $user)
    {
        return $this->jsonSuccess($user, 'Utilisateur trouvé');
    }

    public function store(StoreUserRequest $request)
    {
        $user = User::create($request->validated());
        return $this->jsonCreated($user);
    }

    public function destroy(User $user)
    {
        $user->delete();
        return $this->jsonNoContent();
    }
}
```

### Via les fonctions globales

Utilisables directement dans n'importe quel fichier sans injection de dépendance :

```php
return api_success($data, 'Opération réussie');
return api_error('Ressource introuvable', 404);
return api_created($user);
return api_paginated($users->paginate(15));
return api_no_content();
```

### Via le helper statique

```php
use App\Support\Helpers\ApiHelper;

return ApiHelper::jsonSuccess($data, 'Opération réussie');
return ApiHelper::jsonError('Accès refusé', HttpStatus::FORBIDDEN);
return ApiHelper::jsonPaginated($paginator, 'Résultats');
```

---

## Format de réponse

Toutes les réponses suivent la même structure :

```json
{
    "success": true,
    "data": {},
    "message": "Opération réussie",
    "meta": null,
    "links": null,
    "errors": null
}
```

Les clés `meta`, `links` et `errors` sont omises de la réponse si elles sont vides.

### Réponse paginée

```json
{
    "success": true,
    "data": [...],
    "message": "Liste des utilisateurs",
    "meta": {
        "pagination": {
            "total": 100,
            "per_page": 15,
            "current_page": 1,
            "last_page": 7,
            "from": 1,
            "to": 15
        }
    },
    "links": {
        "first": "https://api.example.com/users?page=1",
        "last": "https://api.example.com/users?page=7",
        "prev": null,
        "next": "https://api.example.com/users?page=2"
    }
}
```

---

## Méthodes disponibles

### Succès

| Méthode | Code | Description |
|---------|------|-------------|
| `jsonSuccess($data, $message, $status)` | 200 | Réponse générique de succès |
| `jsonCreated($data, $message)` | 201 | Ressource créée |
| `jsonNoContent()` | 204 | Pas de contenu à retourner |
| `jsonPaginated($paginator, $message)` | 200 | Données paginées avec métadonnées |

### Erreurs client

| Méthode | Code | Description |
|---------|------|-------------|
| `jsonBadRequest($message, $errors)` | 400 | Requête mal formée |
| `jsonUnauthorized($message)` | 401 | Non authentifié |
| `jsonForbidden($message)` | 403 | Accès refusé |
| `jsonNotFound($message)` | 404 | Ressource introuvable |
| `jsonConflict($message, $data)` | 409 | Conflit d'état |
| `jsonValidationError($errors, $message)` | 422 | Erreur de validation |

### Erreurs serveur

| Méthode | Code | Description |
|---------|------|-------------|
| `jsonInternalServerError($message, $errors)` | 500 | Erreur interne |
| `jsonServiceUnavailable($message)` | 503 | Service indisponible |

### Générique

| Méthode | Description |
|---------|-------------|
| `jsonError($message, $status, $errors, $data)` | Erreur avec code personnalisé |
| `jsonApiResponse($data, $status, $message, $errors, $meta, $links)` | Réponse complète personnalisable |

---

## Fonctions globales disponibles

| Fonction | Équivalent |
|----------|-----------|
| `api_response(...)` | `ApiHelper::jsonApiResponse(...)` |
| `api_success(...)` | `ApiHelper::jsonSuccess(...)` |
| `api_error(...)` | `ApiHelper::jsonError(...)` |
| `api_paginated(...)` | `ApiHelper::jsonPaginated(...)` |
| `api_created(...)` | `ApiHelper::jsonCreated(...)` |
| `api_unauthorized(...)` | `ApiHelper::jsonUnauthorized(...)` |
| `api_forbidden(...)` | `ApiHelper::jsonForbidden(...)` |
| `api_not_found(...)` | `ApiHelper::jsonNotFound(...)` |
| `api_validation_error(...)` | `ApiHelper::jsonValidationError(...)` |
| `api_no_content()` | `ApiHelper::jsonNoContent()` |
| `api_bad_request(...)` | `ApiHelper::jsonBadRequest(...)` |
| `api_conflict(...)` | `ApiHelper::jsonConflict(...)` |
| `api_service_unavailable(...)` | `ApiHelper::jsonServiceUnavailable(...)` |
| `api_internal_server_error(...)` | `ApiHelper::jsonInternalServerError(...)` |

---

## Enum HttpStatus

L'enum `HttpStatus` couvre l'intégralité des codes HTTP (1xx à 5xx) et expose une méthode `message()` qui retourne la description en français du code.

```php
use App\Enums\HttpStatus;

HttpStatus::OK->value;        // 200
HttpStatus::NOT_FOUND->value; // 404
HttpStatus::OK->message();    // 'Requête traitée avec succès.'

// Utilisable directement dans les méthodes helper
return $this->jsonError('Introuvable', HttpStatus::NOT_FOUND);
```

---

## Journalisation

Chaque réponse est automatiquement journalisée dans le channel `api` avec un niveau adapté au code HTTP :

| Plage | Niveau |
|-------|--------|
| 2xx / 3xx | `info` |
| 4xx | `warning` |
| 5xx | `error` |

Format de log :

```
[2026-02-19 13:45:12] local.INFO: [API] [INFO] GET /api/users - 200 Liste des utilisateurs
{"endpoint":"/api/users","method":"GET","ip":"127.0.0.1","user_id":1,"status":200,"data_count":15}
```

> Les codes `204 No Content` et `304 Not Modified` ne sont pas journalisés par défaut depuis `ApiController`.

---

## Prérequis

- PHP 8.1+
- Laravel 10+
