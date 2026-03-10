<?php

namespace App\Support\Helpers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use App\Enums\HttpStatus;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ApiHelper
{
    /**
     * Génère une réponse JSON API standardisée.
     *
     * @param mixed $data Données de la réponse
     * @param int|HttpStatus $status Code HTTP
     * @param string $message Message descriptif
     * @param array $errors Liste des erreurs
     * @param array $meta Métadonnées additionnelles
     * @param array $links Liens de pagination/relation
     * @return JsonResponse
     */
    // public static function jsonApiResponse(
    //     mixed $data = null,
    //     int|HttpStatus $status = 200,
    //     string $message = '',
    //     array $errors = [],
    //     array $meta = [],
    //     array $links = []
    // ): JsonResponse {
    //     try {
    //         if (!self::isValidHttpStatus($status)) {
    //             throw new \InvalidArgumentException("Code HTTP invalide : $status");
    //         }

    //         $success = $status >= 200 && $status < 300;
    //         $message = $message ?: self::getDefaultMessageForStatus($status);

    //         $response = array_filter([
    //             'success' => $success,
    //             'data' => $data,
    //             'message' => $message,
    //             'meta' => !empty($meta) ? $meta : null,
    //             'links' => !empty($links) ? $links : null,
    //             'errors' => !empty($errors) ? $errors : null,
    //         ]);

    //         $context = self::getRequestContext();
    //         self::logApiResponse($status, $message, $data, $errors, $context);

    //         return Response::json($response, $status);
    //     } catch (\Throwable $th) {
    //         self::logApiError('RESPONSE_GENERATION', $th->getMessage(), [
    //             'original_status' => $status,
    //             'data_type' => gettype($data),
    //         ]);

    //         return Response::json([
    //             'success' => false,
    //             'data' => null,
    //             'message' => 'Erreur interne du serveur',
    //             'errors' => config('app.debug') ? ['exception' => $th->getMessage()] : [],
    //         ], HttpStatus::INTERNAL_SERVER_ERROR->value);
    //     }
    // }
    public static function jsonApiResponse(
        mixed $data = null,
        int|HttpStatus $status = 200,
        string $message = '',
        array $errors = [],
        array $meta = [],
        array $links = []
    ): JsonResponse {
        try {
            if (!self::isValidHttpStatus($status)) {
                throw new \InvalidArgumentException("Code HTTP invalide : $status");
            }

            $success = $status >= 200 && $status < 300;
            $message = $message ?: self::getDefaultMessageForStatus($status);

            $response = [
                'success' => $success,
                'data' => $data,
                'message' => $message,
                'meta' => !empty($meta) ? $meta : null,
                'links' => !empty($links) ? $links : null,
                'errors' => !empty($errors) ? $errors : null,
            ];

            // Utiliser array_filter avec ARRAY_FILTER_USE_BOTH pour conserver les tableaux vides
            $response = array_filter($response, function ($value, $key) {
                // Toujours garder 'data', même si c'est un tableau vide
                if ($key === 'data') {
                    return true;
                }
                // Pour les autres clés, on retire seulement si null
                return $value !== null;
            }, ARRAY_FILTER_USE_BOTH);

            $context = self::getRequestContext();
            self::logApiResponse($status, $message, $data, $errors, $context);

            return Response::json($response, $status);
        } catch (\Throwable $th) {
            self::logApiError('RESPONSE_GENERATION', $th->getMessage(), [
                'original_status' => $status,
                'data_type' => gettype($data),
            ]);

            return Response::json([
                'success' => false,
                'data' => null,
                'message' => 'Erreur interne du serveur',
                'errors' => config('app.debug') ? ['exception' => $th->getMessage()] : [],
            ], HttpStatus::INTERNAL_SERVER_ERROR->value);
        }
    }

    /**
     * Récupère le contexte minimal et sécurisé de la requête.
     *
     * @return array
     */
    protected static function getRequestContext(): array
    {
        try {
            $request = app(Request::class);

            return [
                'endpoint' => $request->getRequestUri(),
                'method' => $request->method(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'user_id' => $request->user()?->id,
                'content_type' => $request->header('Content-Type'),
                'referer' => $request->header('Referer'),
                'input_keys' => array_keys(
                    $request->except(['password', 'password_confirmation', 'token', 'api_key'])
                ),
            ];
        } catch (\Throwable) {
            return ['error' => 'Impossible de récupérer le contexte de la requête'];
        }
    }

    /**
     * Journalise automatiquement chaque réponse API.
     *
     * @param int|HttpStatus $status
     * @param string $message
     * @param mixed $data
     * @param array $errors
     * @param array $context
     * @return void
     */
    protected static function logApiResponse(
        int|HttpStatus $status,
        string $message,
        mixed $data = null,
        array $errors = [],
        array $context = []
    ): void {
        try {
            $level = match (true) {
                $status >= 500 => 'error',
                $status >= 400 => 'warning',
                default => 'info',
            };

            $context = array_merge($context, [
                'status' => $status,
                'message' => $message,
                'data_type' => gettype($data),
                'error_count' => count($errors),
                'timestamp' => now()->toISOString(),
            ]);

            if (is_countable($data)) {
                $context['data_count'] = count($data);
            }

            if (!empty($errors)) {
                $context['error_keys'] = array_keys($errors);
            }

            $logMessage = sprintf(
                "[API] [%s] %s %s - %d %s",
                strtoupper($level),
                $context['method'] ?? 'UNKNOWN',
                $context['endpoint'] ?? 'UNKNOWN',
                $status,
                $message
            );

            Log::{$level}($logMessage, $context);
        } catch (\Throwable $th) {
            error_log(sprintf("[%s] [LOG_ERROR] %s", now()->toDateTimeString(), $th->getMessage()));
        }
    }

    /**
     * Journalise une erreur critique du système API.
     *
     * @param string $type
     * @param string $message
     * @param array $context
     * @return void
     */
    protected static function logApiError(string $type, string $message, array $context = []): void
    {
        try {
            $context = array_merge(self::getRequestContext(), $context, [
                'timestamp' => now()->toISOString(),
                'error_type' => $type,
            ]);

            $logMessage = sprintf(
                "[API_ERROR] [%s] %s %s - %s",
                $type,
                $context['method'] ?? 'UNKNOWN',
                $context['endpoint'] ?? 'UNKNOWN',
                $message
            );

            Log::error($logMessage, $context);
        } catch (\Throwable $th) {
            error_log(sprintf("[%s] [LOG_CRITICAL] %s", now()->toDateTimeString(), $th->getMessage()));
        }
    }

    /**
     * Vérifie si un code HTTP est valide.
     *
     * @param int|HttpStatus $status
     * @return bool
     */
    protected static function isValidHttpStatus(int|HttpStatus $status): bool
    {
        return $status >= 100 && $status <= 599;
    }

    /**
     * Retourne la description associée à un code HTTP.
     *
     * @param int|HttpStatus $status
     * @return string
     */
    protected static function getDefaultMessageForStatus(int|HttpStatus $status): string
    {
        $enum = HttpStatus::tryFrom($status);
        return $enum?->message() ?? 'Statut HTTP inconnu';
    }

    /**
     * Réponse de succès (200 par défaut)
     *
     * @param mixed $data
     * @param string $message
     * @param int|HttpStatus $status
     * @return JsonResponse
     */
    public static function jsonSuccess(
        mixed $data = null,
        string $message = '',
        int|HttpStatus $status = 200
    ): JsonResponse {
        return self::jsonApiResponse($data, $status, $message);
    }

    /**
     * Réponse d'erreur standardisée
     *
     * @param string $message
     * @param int|HttpStatus $status
     * @param array $errors
     * @param mixed $data
     * @return JsonResponse
     */
    public static function jsonError(
        string $message = '',
        int|HttpStatus $status = 400,
        array $errors = [],
        mixed $data = null
    ): JsonResponse {
        return self::jsonApiResponse($data, $status, $message, $errors);
    }

    /**
     * Réponse standardisée pour les données paginées.
     *
     * @param LengthAwarePaginator|ResourceCollection $data
     * @param string $message
     * @param array $extraMeta
     * @return JsonResponse
     */
    public static function jsonPaginated(
        LengthAwarePaginator|ResourceCollection $data,
        string $message = '',
        array $extraMeta = []
    ): JsonResponse {
        try {
            $meta = array_merge([
                'pagination' => [
                    'total' => $data->total(),
                    'per_page' => $data->perPage(),
                    'current_page' => $data->currentPage(),
                    'last_page' => $data->lastPage(),
                    'from' => $data->firstItem(),
                    'to' => $data->lastItem(),
                ]
            ], $extraMeta);

            $links = [
                'first' => $data->url(1),
                'last' => $data->url($data->lastPage()),
                'prev' => $data->previousPageUrl(),
                'next' => $data->nextPageUrl(),
            ];

            return self::jsonApiResponse($data->items(), HttpStatus::OK->value, $message, [], $meta, $links);
        } catch (\Throwable $th) {
            self::logApiError('PAGINATION_ERROR', $th->getMessage());
            return self::jsonError('Erreur lors de la pagination des données', HttpStatus::INTERNAL_SERVER_ERROR->value);
        }
    }

    /**
     * Réponse 201 Created
     *
     * @param mixed $data
     * @param string $message
     * @return JsonResponse
     */
    public static function jsonCreated(mixed $data = null, string $message = 'Ressource créée avec succès'): JsonResponse
    {
        return self::jsonApiResponse($data, HttpStatus::CREATED->value, $message);
    }

    /**
     * Réponse 401 Unauthorized
     *
     * @param string $message
     * @return JsonResponse
     */
    public static function jsonUnauthorized(string $message = 'Non authentifié'): JsonResponse
    {
        return self::jsonError($message, HttpStatus::UNAUTHORIZED->value);
    }

    /**
     * Réponse 403 Forbidden
     *
     * @param string $message
     * @return JsonResponse
     */
    public static function jsonForbidden(string $message = 'Accès non autorisé'): JsonResponse
    {
        return self::jsonError($message, HttpStatus::FORBIDDEN->value);
    }

    /**
     * Réponse 404 Not Found
     *
     * @param string $message
     * @return JsonResponse
     */
    public static function jsonNotFound(string $message = 'Ressource non trouvée'): JsonResponse
    {
        return self::jsonError($message, HttpStatus::NOT_FOUND->value);
    }

    /**
     * Réponse 422 Validation Error
     *
     * @param array $errors
     * @param string $message
     * @return JsonResponse
     */
    public static function jsonValidationError(array $errors = [], string $message = 'Erreur de validation'): JsonResponse
    {
        return self::jsonError($message, HttpStatus::UNPROCESSABLE_CONTENT->value, $errors);
    }

    /**
     * Réponse 204 No Content
     *
     * @return JsonResponse
     */
    public static function jsonNoContent(): JsonResponse
    {
        return self::jsonApiResponse(null, HttpStatus::NO_CONTENT->value, 'Aucun contenu');
    }

    /**
     * Réponse 400 Bad Request
     *
     * @param string $message
     * @param array $errors
     * @return JsonResponse
     */
    public static function jsonBadRequest(string $message = 'Requête incorrecte', array $errors = []): JsonResponse
    {
        return self::jsonError($message, HttpStatus::BAD_REQUEST->value, $errors);
    }

    /**
     * Réponse 409 Conflict
     *
     * @param string $message
     * @param mixed $data
     * @return JsonResponse
     */
    public static function jsonConflict(string $message = 'Conflit détecté', mixed $data = null): JsonResponse
    {
        return self::jsonApiResponse($data, HttpStatus::CONFLICT->value, $message);
    }

    /**
     * Réponse 503 Service Unavailable
     *
     * @param string $message
     * @return JsonResponse
     */
    public static function jsonServiceUnavailable(string $message = 'Service temporairement indisponible'): JsonResponse
    {
        return self::jsonError($message, HttpStatus::SERVICE_UNAVAILABLE->value);
    }

    /**
     * Réponse 500 Internal Server Error
     *
     * @param string $message
     * @param array $errors
     * @return JsonResponse
     */
    public static function jsonInternalServerError(string $message = 'Erreur interne du serveur', array $errors = []): JsonResponse
    {
        return self::jsonError($message, HttpStatus::INTERNAL_SERVER_ERROR->value, $errors);
    }
}
