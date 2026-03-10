<?php

use App\Support\Helpers\ApiHelper;
use Illuminate\Http\JsonResponse;
use App\Enums\HttpStatus;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Resources\Json\ResourceCollection;

if (!function_exists('api_response')) {
    /**
     * Helper principal pour les réponses API
     *
     * @param mixed $data
     * @param int|HttpStatus $status
     * @param string $message
     * @param array $errors
     * @param array $meta
     * @param array $links
     * @return JsonResponse
     */
    function api_response(
        mixed $data = null,
        int|HttpStatus $status = 200,
        string $message = '',
        array $errors = [],
        array $meta = [],
        array $links = []
    ): JsonResponse {
        return ApiHelper::jsonApiResponse($data, $status, $message, $errors, $meta, $links);
    }
}

if (!function_exists('api_success')) {
    /**
     * Réponse de succès
     *
     * @param mixed $data
     * @param string $message
     * @param int|HttpStatus $status
     * @return JsonResponse
     */
    function api_success(
        mixed $data = null,
        string $message = '',
        int|HttpStatus $status = 200
    ): JsonResponse {
        return ApiHelper::jsonSuccess($data, $message, $status);
    }
}

if (!function_exists('api_error')) {
    /**
     * Réponse d'erreur
     *
     * @param string $message
     * @param int|HttpStatus $status
     * @param array $errors
     * @param mixed $data
     * @return JsonResponse
     */
    function api_error(
        string $message = '',
        int|HttpStatus $status = 400,
        array $errors = [],
        mixed $data = null
    ): JsonResponse {
        return ApiHelper::jsonError($message, $status, $errors, $data);
    }
}

if (!function_exists('api_paginated')) {
    /**
     * Réponse paginée
     *
     * @param LengthAwarePaginator|ResourceCollection $data
     * @param string $message
     * @param array $extraMeta
     * @return JsonResponse
     */
    function api_paginated(
        LengthAwarePaginator|ResourceCollection $data,
        string $message = '',
        array $extraMeta = []
    ): JsonResponse {
        return ApiHelper::jsonPaginated($data, $message, $extraMeta);
    }
}

if (!function_exists('api_created')) {
    /**
     * Réponse 201 Created
     *
     * @param mixed $data
     * @param string $message
     * @return JsonResponse
     */
    function api_created(mixed $data = null, string $message = 'Ressource créée avec succès'): JsonResponse
    {
        return ApiHelper::jsonCreated($data, $message);
    }
}

if (!function_exists('api_unauthorized')) {
    /**
     * Réponse 401 Unauthorized
     *
     * @param string $message
     * @return JsonResponse
     */
    function api_unauthorized(string $message = 'Non authentifié'): JsonResponse
    {
        return ApiHelper::jsonUnauthorized($message);
    }
}

if (!function_exists('api_forbidden')) {
    /**
     * Réponse 403 Forbidden
     *
     * @param string $message
     * @return JsonResponse
     */
    function api_forbidden(string $message = 'Accès non autorisé'): JsonResponse
    {
        return ApiHelper::jsonForbidden($message);
    }
}

if (!function_exists('api_not_found')) {
    /**
     * Réponse 404 Not Found
     *
     * @param string $message
     * @return JsonResponse
     */
    function api_not_found(string $message = 'Ressource non trouvée'): JsonResponse
    {
        return ApiHelper::jsonNotFound($message);
    }
}

if (!function_exists('api_validation_error')) {
    /**
     * Réponse 422 Validation Error
     *
     * @param array $errors
     * @param string $message
     * @return JsonResponse
     */
    function api_validation_error(array $errors = [], string $message = 'Erreur de validation'): JsonResponse
    {
        return ApiHelper::jsonValidationError($errors, $message);
    }
}

if (!function_exists('api_no_content')) {
    /**
     * Réponse 204 No Content
     *
     * @return JsonResponse
     */
    function api_no_content(): JsonResponse
    {
        return ApiHelper::jsonNoContent();
    }
}

if (!function_exists('api_bad_request')) {
    /**
     * Réponse 400 Bad Request
     *
     * @param string $message
     * @param array $errors
     * @return JsonResponse
     */
    function api_bad_request(string $message = 'Requête incorrecte', array $errors = []): JsonResponse
    {
        return ApiHelper::jsonBadRequest($message, $errors);
    }
}

if (!function_exists('api_conflict')) {
    /**
     * Réponse 409 Conflict
     *
     * @param string $message
     * @param mixed $data
     * @return JsonResponse
     */
    function api_conflict(string $message = 'Conflit détecté', mixed $data = null): JsonResponse
    {
        return ApiHelper::jsonConflict($message, $data);
    }
}

if (!function_exists('api_service_unavailable')) {
    /**
     * Réponse 503 Service Unavailable
     *
     * @param string $message
     * @return JsonResponse
     */
    function api_service_unavailable(string $message = 'Service temporairement indisponible'): JsonResponse
    {
        return ApiHelper::jsonServiceUnavailable($message);
    }
}

if (!function_exists('api_internal_server_error')) {
    /**
     * Réponse 500 Internal Server Error
     *
     * @param string $message
     * @param array $errors
     * @return JsonResponse
     */
    function api_internal_server_error(string $message = 'Erreur interne du serveur', array $errors = []): JsonResponse
    {
        return ApiHelper::jsonInternalServerError($message, $errors);
    }
}
