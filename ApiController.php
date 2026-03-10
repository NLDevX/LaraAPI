<?php

namespace App\Core\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Enums\HttpStatus;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Log;

abstract class ApiController extends Controller
{
    /**
     * Statuts HTTP qui ne doivent pas être journalisés
     */
    protected array $excludedLogStatuses = [
        HttpStatus::NO_CONTENT->value,
        HttpStatus::NOT_MODIFIED->value,
    ];

    /**
     * Génère une réponse JSON API standardisée
     */
    protected function jsonApiResponse(
        mixed $data = null,
        int|HttpStatus $status = 200,
        string $message = '',
        array $errors = [],
        array $meta = [],
        array $links = [],
        bool $shouldLog = true
    ): JsonResponse {
        $response = api_response($data, $status, $message, $errors, $meta, $links);

        if ($shouldLog && !in_array($status, $this->excludedLogStatuses)) {
            $this->logCustomResponse($status, $message, $data, $errors);
        }

        return $response;
    }

    /**
     * Journalise les réponses API
     */
    protected function logCustomResponse(
        int|HttpStatus $status,
        string $message,
        mixed $data = null,
        array $errors = []
    ): void {
        try {
            $request = app(Request::class);
            $userId = $request->user()?->id;
            $endpoint = $request->getRequestUri();
            $method = $request->method();

            $logContext = [
                'user_id' => $userId,
                'endpoint' => $endpoint,
                'method' => $method,
                'status' => $status,
                'message' => $message,
                'data_type' => gettype($data),
                'error_count' => count($errors),
                'timestamp' => now()->toISOString(),
            ];

            if (is_countable($data)) {
                $logContext['data_count'] = count($data);
            }

            Log::channel('api')->info('API Response', $logContext);
        } catch (\Throwable $th) {
            // Ignorer les erreurs de journalisation
        }
    }

    /**
     * Réponse de succès (200)
     */
    protected function jsonSuccess(
        mixed $data = null,
        string $message = '',
        int|HttpStatus $status = 200,
        bool $shouldLog = true
    ): JsonResponse {
        return $this->jsonApiResponse($data, $status, $message, [], [], [], $shouldLog);
    }

    /**
     * Réponse d'erreur standardisée
     */
    protected function jsonError(
        string $message = '',
        int|HttpStatus $status = 400,
        array $errors = [],
        mixed $data = null,
        bool $shouldLog = true
    ): JsonResponse {
        return $this->jsonApiResponse($data, $status, $message, $errors, [], [], $shouldLog);
    }

    /**
     * Réponse pour données paginées
     */
    protected function jsonPaginated(
        LengthAwarePaginator|ResourceCollection $data,
        string $message = '',
        array $extraMeta = [],
        bool $shouldLog = true
    ): JsonResponse {
        $response = api_paginated($data, $message, $extraMeta);

        if ($shouldLog) {
            $this->logCustomResponse(HttpStatus::OK->value, $message, $data->items());
        }

        return $response;
    }

    /**
     * Réponse 201 Created
     */
    protected function jsonCreated(
        mixed $data = null,
        string $message = 'Ressource créée avec succès',
        bool $shouldLog = true
    ): JsonResponse {
        return $this->jsonApiResponse($data, HttpStatus::CREATED->value, $message, [], [], [], $shouldLog);
    }

    /**
     * Réponse 401 Unauthorized
     */
    protected function jsonUnauthorized(string $message = 'Non authentifié', bool $shouldLog = true): JsonResponse
    {
        return $this->jsonError($message, HttpStatus::UNAUTHORIZED->value, [], null, $shouldLog);
    }

    /**
     * Réponse 403 Forbidden
     */
    protected function jsonForbidden(string $message = 'Accès non autorisé', bool $shouldLog = true): JsonResponse
    {
        return $this->jsonError($message, HttpStatus::FORBIDDEN->value, [], null, $shouldLog);
    }

    /**
     * Réponse 404 Not Found
     */
    protected function jsonNotFound(string $message = 'Ressource non trouvée', bool $shouldLog = true): JsonResponse
    {
        return $this->jsonError($message, HttpStatus::NOT_FOUND->value, [], null, $shouldLog);
    }

    /**
     * Réponse 422 Validation Error
     */
    protected function jsonValidationError(
        array $errors = [],
        string $message = 'Erreur de validation',
        bool $shouldLog = true
    ): JsonResponse {
        return $this->jsonError($message, HttpStatus::UNPROCESSABLE_CONTENT->value, $errors, null, $shouldLog);
    }

    /**
     * Réponse 204 No Content
     */
    protected function jsonNoContent(): JsonResponse
    {
        return api_no_content();
    }

    /**
     * Réponse 400 Bad Request
     */
    protected function jsonBadRequest(
        string $message = 'Requête incorrecte',
        array $errors = [],
        bool $shouldLog = true
    ): JsonResponse {
        return $this->jsonError($message, HttpStatus::BAD_REQUEST->value, $errors, null, $shouldLog);
    }

    /**
     * Réponse 409 Conflict
     */
    protected function jsonConflict(
        string $message = 'Conflit détecté',
        mixed $data = null,
        bool $shouldLog = true
    ): JsonResponse {
        return $this->jsonApiResponse($data, HttpStatus::CONFLICT->value, $message, [], [], [], $shouldLog);
    }

    /**
     * Réponse 503 Service Unavailable
     */
    protected function jsonServiceUnavailable(
        string $message = 'Service temporairement indisponible',
        bool $shouldLog = true
    ): JsonResponse {
        return $this->jsonError($message, HttpStatus::SERVICE_UNAVAILABLE->value, [], null, $shouldLog);
    }

    /**
     * Réponse 500 Internal Server Error
     */
    protected function jsonInternalServerError(
        string $message = 'Erreur interne du serveur',
        array $errors = [],
        bool $shouldLog = true
    ): JsonResponse {
        return $this->jsonError($message, HttpStatus::INTERNAL_SERVER_ERROR->value, $errors, null, $shouldLog);
    }
}
