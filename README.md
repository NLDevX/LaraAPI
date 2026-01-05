# ApiController - Documentation Complète

## 📋 Vue d'ensemble

`ApiController` est une classe abstraite servant de base pour tous les contrôleurs API de l'application. Elle fournit une gestion standardisée des réponses JSON, du logging et de la gestion des erreurs.

**Localisation** : `app/Http/Controllers/Api/ApiController.php`

---

## 🎯 Objectifs Principaux

1. **Normalisation des réponses API** : Format JSON cohérent pour toutes les réponses
2. **Logging intelligent** : Traçabilité automatique de chaque requête et réponse
3. **Gestion des erreurs** : Centralisation de la gestion des codes HTTP et erreurs
4. **Sécurité** : Protection des données sensibles dans les logs
5. **Maintenabilité** : Réduction de la duplication de code dans les contrôleurs

---

## 📦 Structure de Réponse JSON

### Format Standard

```json
{
  "success": true,
  "data": { /* données */ },
  "message": "Description de la réponse",
  "meta": { /* métadonnées optionnelles */ },
  "links": { /* liens optionnels */ },
  "errors": null
}
```

### Exemple Succès (200)

```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com"
  },
  "message": "Utilisateur récupéré avec succès"
}
```

### Exemple Erreur (400)

```json
{
  "success": false,
  "data": null,
  "message": "Erreur de validation",
  "errors": {
    "email": ["L'email est requis"],
    "password": ["Le mot de passe doit contenir au moins 8 caractères"]
  }
}
```

### Exemple Pagination (200)

```json
{
  "success": true,
  "data": [ /* items */ ],
  "message": "Utilisateurs récupérés",
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
    "first": "/api/users?page=1",
    "last": "/api/users?page=7",
    "prev": null,
    "next": "/api/users?page=2"
  }
}
```

---

## 🔧 Méthodes Disponibles

### 1. Réponses Génériques

#### `jsonApiResponse()` - Réponse Personnalisée Complète

```php
protected function jsonApiResponse(
    mixed $data = null,
    int|HttpStatus $status = 200,
    string $message = '',
    array $errors = [],
    array $meta = [],
    array $links = []
): JsonResponse
```

**Utilisation** :
```php
return $this->jsonApiResponse(
    data: $user,
    status: 201,
    message: 'Utilisateur créé',
    meta: ['created_at' => now()]
);
```

---

### 2. Réponses Courtes (Raccourcis)

#### `jsonSuccess()` - Réponse 200

```php
protected function jsonSuccess(
    mixed $data = null,
    string $message = '',
    int|HttpStatus $status = 200
): JsonResponse
```

**Utilisation** :
```php
public function show(User $user)
{
    return $this->jsonSuccess(
        data: $user,
        message: 'Utilisateur trouvé'
    );
}
```

---

#### `jsonError()` - Réponse d'Erreur Générique

```php
protected function jsonError(
    string $message = '',
    int|HttpStatus $status = 400,
    array $errors = [],
    mixed $data = null
): JsonResponse
```

**Utilisation** :
```php
return $this->jsonError(
    message: 'Données invalides',
    status: 422,
    errors: $validator->errors()->toArray()
);
```

---

### 3. Réponses Spécifiques aux Codes HTTP

#### `jsonCreated()` - Réponse 201

```php
public function store(StoreUserRequest $request, UserService $service)
{
    $user = $service->createUser($request->validated());
    return $this->jsonCreated($user, 'Utilisateur créé avec succès');
}
```

---

#### `jsonPaginated()` - Réponse Paginée

```php
public function index(UserService $service)
{
    $users = $service->getAllUsers(
        request()->query(),
        per_page: 15
    );
    
    return $this->jsonPaginated(
        $users,
        message: 'Utilisateurs récupérés',
        extraMeta: ['filters' => request()->query()]
    );
}
```

---

#### `jsonValidationError()` - Réponse 422

```php
protected function jsonValidationError(
    array $errors = [],
    string $message = 'Erreur de validation'
): JsonResponse
```

**Utilisation** :
```php
return $this->jsonValidationError(
    errors: $request->validate([...]),
    message: 'Données invalides'
);
```

---

#### `jsonNoContent()` - Réponse 204

```php
public function destroy(User $user, UserService $service)
{
    $service->deleteUser($user);
    return $this->jsonNoContent();
}
```

---

#### Codes HTTP Spécifiques

| Méthode | Code | Utilisation |
|---------|------|------------|
| `jsonUnauthorized()` | 401 | Utilisateur non authentifié |
| `jsonForbidden()` | 403 | Accès refusé / Permission insuffisante |
| `jsonNotFound()` | 404 | Ressource introuvable |
| `jsonBadRequest()` | 400 | Requête malformée |
| `jsonConflict()` | 409 | Conflit de données |
| `jsonInternalServerError()` | 500 | Erreur serveur |
| `jsonServiceUnavailable()` | 503 | Service indisponible |

---

## 💡 Exemples Complets

### Exemple 1 : Créer un Utilisateur

```php
namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreUserRequest;
use App\Services\UserService;

class UserController extends ApiController
{
    public function __construct(
        private UserService $userService
    ) {}

    public function store(StoreUserRequest $request)
    {
        try {
            $user = $this->userService->createUser($request->validated());
            
            return $this->jsonCreated(
                data: $user,
                message: 'Utilisateur créé avec succès'
            );
        } catch (\Exception $e) {
            return $this->jsonInternalServerError(
                message: 'Erreur lors de la création',
                errors: config('app.debug') ? [$e->getMessage()] : []
            );
        }
    }
}
```

---

### Exemple 2 : Récupérer une Liste Paginée

```php
public function index(UserService $userService)
{
    try {
        $filters = $this->request->only(['search', 'is_active', 'sort_field', 'sort_direction']);
        $users = $userService->getAllUsers($filters, perPage: 15);
        
        return $this->jsonPaginated(
            data: $users,
            message: 'Utilisateurs récupérés',
            extraMeta: [
                'filters_applied' => array_filter($filters)
            ]
        );
    } catch (\Exception $e) {
        return $this->jsonInternalServerError();
    }
}
```

---

### Exemple 3 : Supprimer une Ressource

```php
public function destroy(User $user, UserService $userService)
{
    try {
        $userService->deleteUser($user);
        return $this->jsonNoContent();
    } catch (\Exception $e) {
        return $this->jsonInternalServerError(
            message: 'Impossible de supprimer l\'utilisateur'
        );
    }
}
```

---

### Exemple 4 : Gestion des Erreurs 404

```php
public function show(User $user)
{
    if (!$user) {
        return $this->jsonNotFound('Utilisateur non trouvé');
    }
    
    return $this->jsonSuccess(
        data: $user,
        message: 'Utilisateur trouvé'
    );
}
```

---

### Exemple 5 : Gestion des Permissions

```php
public function update(Request $request, User $user)
{
    if (!$request->user()->can('update', $user)) {
        return $this->jsonForbidden('Vous n\'avez pas accès à cet utilisateur');
    }
    
    // Mise à jour...
    return $this->jsonSuccess($user, 'Utilisateur mis à jour');
}
```

---

## 📊 Logging Automatique

### Logs Générés Automatiquement

Chaque appel à `jsonApiResponse()` génère un log structuré :

```
[INFO] GET /api/users/1 - 200 Utilisateur trouvé
```

**Contexte loggé** :
- `method` : Méthode HTTP (GET, POST, etc.)
- `endpoint` : URI de la requête
- `status` : Code HTTP
- `message` : Message de réponse
- `user_id` : ID de l'utilisateur authentifié
- `ip` : Adresse IP du client
- `user_agent` : User-Agent du navigateur
- `data_type` : Type de données (array, object, etc.)
- `data_count` : Nombre d'items (si applicable)
- `error_count` : Nombre d'erreurs (si applicable)

### Niveaux de Log

| Code HTTP | Niveau | Couleur |
|-----------|--------|--------|
| 200-299 | `info` | ℹ️ |
| 300-399 | `info` | ℹ️ |
| 400-499 | `warning` | ⚠️ |
| 500-599 | `error` | ❌ |

### Protection des Données Sensibles

Les données suivantes sont automatiquement exclues des logs :

- `password`
- `password_confirmation`
- `token`
- `api_key`

---

## 🔐 Sécurité

### Filtrage Automatique

```php
// Ces champs ne sont jamais loggés
$context['input_keys'] = array_keys(
    $request->except(['password', 'password_confirmation', 'token', 'api_key'])
);
```

### Gestion des Erreurs en Produit

```php
// Si app.debug = false, les exceptions ne sont pas retournées
'errors' => config('app.debug') ? ['exception' => $th->getMessage()] : [],
```

---

## 🧪 Intégration avec HttpStatus Enum

L'application utilise un enum `HttpStatus` pour typage fort :

```php
namespace App\Enums;

enum HttpStatus: int {
    case OK = 200;
    case CREATED = 201;
    case BAD_REQUEST = 400;
    case UNAUTHORIZED = 401;
    case FORBIDDEN = 403;
    case NOT_FOUND = 404;
    case CONFLICT = 409;
    case UNPROCESSABLE_CONTENT = 422;
    case INTERNAL_SERVER_ERROR = 500;
    case SERVICE_UNAVAILABLE = 503;
    
    public function message(): string { /* ... */ }
}
```

**Utilisation** :
```php
return $this->jsonApiResponse(
    data: $user,
    status: HttpStatus::CREATED  // ✅ Type-safe
);
```

---

## 📝 Bonnes Pratiques

### ✅ À Faire

```php
// 1. Utiliser les raccourcis appropriés
return $this->jsonCreated($user);

// 2. Toujours fournir un message explicite
return $this->jsonSuccess(
    data: $users,
    message: 'Utilisateurs récupérés avec succès'
);

// 3. Inclure les erreurs de validation
return $this->jsonValidationError(
    errors: $validator->errors()->toArray()
);

// 4. Utiliser HttpStatus enum
return $this->jsonApiResponse($user, HttpStatus::CREATED->value);
```

### ❌ À Éviter

```php
// ❌ Codes HTTP bruts sans message
return response()->json(['data' => $user], 200);

// ❌ Logs sensibles non filtrés
Log::info('User created', ['password' => $password]);

// ❌ Pas de gestion d'erreurs
return $this->jsonSuccess($data, 200);  // Sans try-catch

// ❌ Messages génériques
return $this->jsonError('Erreur');
```

---

## 🔄 Flux Complet d'une Requête

```
1. Requête HTTP
    ↓
2. ApiController reçoit la requête
    ↓
3. Récupère le contexte (IP, user_agent, etc.)
    ↓
4. Appelle la méthode de contrôleur
    ↓
5. Utilise un raccourci (jsonSuccess, jsonError, etc.)
    ↓
6. Génère la réponse JSON
    ↓
7. Logs automatiques (niveau, message, contexte)
    ↓
8. Retourne la réponse au client
```

---

## 📞 Support & Extensions

### Créer un Nouveau Raccourci

```php
// Ajouter dans ApiController
protected function jsonTooManyRequests(string $message = 'Trop de requêtes'): JsonResponse
{
    return $this->jsonError($message, HttpStatus::TOO_MANY_REQUESTS->value);
}
```

### Personnaliser la Réponse Globale

```php
// Surcharger dans votre contrôleur
protected function jsonApiResponse(...) {
    $response = parent::jsonApiResponse(...);
    // Modifications personnalisées
    return $response;
}
```

---

## 📚 Résumé des Méthodes

| Méthode | Paramètres | Retour | Cas d'Usage |
|---------|-----------|--------|-----------|
| `jsonSuccess()` | data, message, status | JSON 200 | Succès généraux |
| `jsonCreated()` | data, message | JSON 201 | Création de ressource |
| `jsonPaginated()` | data, message, meta | JSON 200 | Listes paginées |
| `jsonError()` | message, status, errors | JSON 4xx/5xx | Erreurs générales |
| `jsonValidationError()` | errors, message | JSON 422 | Erreurs de validation |
| `jsonNotFound()` | message | JSON 404 | Ressource introuvable |
| `jsonUnauthorized()` | message | JSON 401 | Non authentifié |
| `jsonForbidden()` | message | JSON 403 | Accès refusé |
| `jsonNoContent()` | - | JSON 204 | Suppression réussie |
| `jsonConflict()` | message, data | JSON 409 | Conflit de données |
| `jsonBadRequest()` | message, errors | JSON 400 | Requête malformée |
| `jsonInternalServerError()` | message, errors | JSON 500 | Erreur serveur |
| `jsonServiceUnavailable()` | message | JSON 503 | Service indisponible |