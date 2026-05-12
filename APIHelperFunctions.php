<?php

use App\Support\Helpers\APIHelper;
use Illuminate\Http\JsonResponse;
use App\Enums\HTTPStatus;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Resources\Json\ResourceCollection;

if (!function_exists('api_response')) {
    /**
     * Helper principal pour les réponses API
     *
     * @param mixed $data
     * @param int|HTTPStatus $status
     * @param string $message
     * @param array $errors
     * @param array $meta
     * @param array $links
     * @param \Throwable|null $exception Exception à logger
     * @return JsonResponse
     */
    function api_response(
        mixed $data = null,
        int|HTTPStatus $status = 200,
        string $message = '',
        array $errors = [],
        array $meta = [],
        array $links = [],
        ?\Throwable $exception = null
    ): JsonResponse {
        return APIHelper::jsonApiResponse($data, $status, $message, $errors, $meta, $links, true, $exception);
    }
}

if (!function_exists('api_success')) {
    /**
     * Réponse de succès
     *
     * @param mixed $data
     * @param string $message
     * @param int|HTTPStatus $status
     * @return JsonResponse
     */
    function api_success(
        mixed $data = null,
        string $message = '',
        int|HTTPStatus $status = 200
    ): JsonResponse {
        return APIHelper::jsonSuccess($data, $message, $status);
    }
}

if (!function_exists('api_error')) {
    /**
     * Réponse d'erreur
     *
     * @param string $message
     * @param int|HTTPStatus $status
     * @param array $errors
     * @param mixed $data
     * @param \Throwable|null $exception Exception à logger
     * @return JsonResponse
     */
    function api_error(
        string $message = '',
        int|HTTPStatus $status = 400,
        array $errors = [],
        mixed $data = null,
        ?\Throwable $exception = null
    ): JsonResponse {
        return APIHelper::jsonError($message, $status, $errors, $data, true, $exception);
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
        return APIHelper::jsonPaginated($data, $message, $extraMeta);
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
        return APIHelper::jsonCreated($data, $message);
    }
}

if (!function_exists('api_unauthorized')) {
    /**
     * Réponse 401 Unauthorized
     *
     * @param string $message
     * @param \Throwable|null $exception Exception à logger
     * @return JsonResponse
     */
    function api_unauthorized(string $message = 'Non authentifié', ?\Throwable $exception = null): JsonResponse
    {
        return APIHelper::jsonUnauthorized($message, true, $exception);
    }
}

if (!function_exists('api_forbidden')) {
    /**
     * Réponse 403 Forbidden
     *
     * @param string $message
     * @param \Throwable|null $exception Exception à logger
     * @return JsonResponse
     */
    function api_forbidden(string $message = 'Accès non autorisé', ?\Throwable $exception = null): JsonResponse
    {
        return APIHelper::jsonForbidden($message, true, $exception);
    }
}

if (!function_exists('api_not_found')) {
    /**
     * Réponse 404 Not Found
     *
     * @param string $message
     * @param \Throwable|null $exception Exception à logger
     * @return JsonResponse
     */
    function api_not_found(string $message = 'Ressource non trouvée', ?\Throwable $exception = null): JsonResponse
    {
        return APIHelper::jsonNotFound($message, true, $exception);
    }
}

if (!function_exists('api_validation_error')) {
    /**
     * Réponse 422 Validation Error
     *
     * @param array $errors
     * @param string $message
     * @param \Throwable|null $exception Exception à logger
     * @return JsonResponse
     */
    function api_validation_error(array $errors = [], string $message = 'Erreur de validation', ?\Throwable $exception = null): JsonResponse
    {
        return APIHelper::jsonValidationError($errors, $message, true, $exception);
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
        return APIHelper::jsonNoContent();
    }
}

if (!function_exists('api_bad_request')) {
    /**
     * Réponse 400 Bad Request
     *
     * @param string $message
     * @param array $errors
     * @param \Throwable|null $exception Exception à logger
     * @return JsonResponse
     */
    function api_bad_request(string $message = 'Requête incorrecte', array $errors = [], ?\Throwable $exception = null): JsonResponse
    {
        return APIHelper::jsonBadRequest($message, $errors, true, $exception);
    }
}

if (!function_exists('api_conflict')) {
    /**
     * Réponse 409 Conflict
     *
     * @param string $message
     * @param mixed $data
     * @param \Throwable|null $exception Exception à logger
     * @return JsonResponse
     */
    function api_conflict(string $message = 'Conflit détecté', mixed $data = null, ?\Throwable $exception = null): JsonResponse
    {
        return APIHelper::jsonConflict($message, $data, true, $exception);
    }
}

if (!function_exists('api_service_unavailable')) {
    /**
     * Réponse 503 Service Unavailable
     *
     * @param string $message
     * @param \Throwable|null $exception Exception à logger
     * @return JsonResponse
     */
    function api_service_unavailable(string $message = 'Service temporairement indisponible', ?\Throwable $exception = null): JsonResponse
    {
        return APIHelper::jsonServiceUnavailable($message, true, $exception);
    }
}

if (!function_exists('api_internal_server_error')) {
    /**
     * Réponse 500 Internal Server Error
     *
     * @param string $message
     * @param array $errors
     * @param \Throwable|null $exception Exception à logger
     * @return JsonResponse
     */
    function api_internal_server_error(string $message = 'Erreur interne du serveur', array $errors = [], ?\Throwable $exception = null): JsonResponse
    {
        return APIHelper::jsonInternalServerError($message, $errors, true, $exception);
    }
}
