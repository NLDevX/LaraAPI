<?php

namespace App\Support\Helpers;

use App\Enums\HTTPStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;

class APIHelper
{
    /**
     * Génère une réponse JSON API standardisée.
     *
     * @param mixed               $data      Données de la réponse
     * @param int|HTTPStatus      $status    Code HTTP (200 par défaut)
     * @param string              $message   Message descriptif
     * @param array               $errors    Liste des erreurs
     * @param array               $meta      Métadonnées additionnelles
     * @param array               $links     Liens de pagination/relation
     * @param bool                $shouldLog Journaliser ou non cette réponse (true par défaut)
     * @param \Throwable|null     $exception Exception associée (loguée si présente)
     * @return JsonResponse
     */
    public static function jsonApiResponse(
        mixed $data = null,
        int|HTTPStatus $status = 200,
        string $message = '',
        array $errors = [],
        array $meta = [],
        array $links = [],
        bool $shouldLog = true,
        ?\Throwable $exception = null
    ): JsonResponse {
        try {
            if (!self::isValidHTTPStatus($status)) {
                throw new \InvalidArgumentException("Code HTTP invalide : $status");
            }

            $statusValue = $status instanceof HTTPStatus ? $status->value : $status;
            $success = $statusValue >= 200 && $statusValue < 300;
            $message = $message ?: self::getDefaultMessageForStatus($statusValue);

            $response = [
                'success' => $success,
                'data'    => $data,
                'message' => $message,
                'meta'    => !empty($meta) ? $meta : null,
                'links'   => !empty($links) ? $links : null,
                'errors'  => !empty($errors) ? $errors : null,
            ];

            // Toujours conserver 'data', même si tableau vide ; pour le reste on retire null
            $response = array_filter($response, function ($value, $key) {
                if ($key === 'data') {
                    return true;
                }
                return $value !== null;
            }, ARRAY_FILTER_USE_BOTH);

            if ($shouldLog) {
                $context = self::getRequestContext();
                if ($exception) {
                    $context['exception'] = [
                        'message' => $exception->getMessage(),
                        'trace'   => $exception->getTraceAsString(),
                    ];
                }
                self::logApiResponse($statusValue, $message, $data, $errors, $context);
            }

            return Response::json($response, $statusValue);
        } catch (\Throwable $th) {
            self::logApiError('RESPONSE_GENERATION', $th->getMessage(), [
                'original_status' => $status,
                'data_type'       => gettype($data),
            ]);

            return Response::json([
                'success' => false,
                'data'    => null,
                'message' => 'Erreur interne du serveur',
                'errors'  => config('app.debug') ? ['exception' => $th->getMessage()] : [],
            ], HTTPStatus::INTERNAL_SERVER_ERROR->value);
        }
    }

    /**
     * Récupère le contexte minimal et sécurisé de la requête courante.
     *
     * @return array
     */
    protected static function getRequestContext(): array
    {
        try {
            $request = app(Request::class);

            return [
                'endpoint'      => $request->getRequestUri(),
                'method'        => $request->method(),
                'ip'            => $request->ip(),
                'user_agent'    => $request->userAgent(),
                'user_id'       => $request->user()?->id,
                'content_type'  => $request->header('Content-Type'),
                'referer'       => $request->header('Referer'),
                'input_keys'    => array_keys(
                    $request->except(['password', 'password_confirmation', 'token', 'api_key'])
                ),
            ];
        } catch (\Throwable) {
            return ['error' => 'Impossible de récupérer le contexte de la requête'];
        }
    }

    /**
     * Journalise une réponse API.
     *
     * @param int    $status  Code HTTP de la réponse
     * @param string $message Message descriptif
     * @param mixed  $data    Données associées
     * @param array  $errors  Erreurs éventuelles
     * @param array  $context Contexte supplémentaire de la requête
     * @return void
     */
    protected static function logApiResponse(
        int $status,
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
                'status'      => $status,
                'message'     => $message,
                'data_type'   => gettype($data),
                'error_count' => count($errors),
                'timestamp'   => now()->toISOString(),
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

            Log::channel('api')->{$level}($logMessage, $context);
        } catch (\Throwable $th) {
            error_log(sprintf("[%s] [LOG_ERROR] %s", now()->toDateTimeString(), $th->getMessage()));
        }
    }

    /**
     * Journalise une erreur critique du système API.
     *
     * @param string $type    Type d'erreur
     * @param string $message Message d'erreur
     * @param array  $context Contexte additionnel
     * @return void
     */
    protected static function logApiError(string $type, string $message, array $context = []): void
    {
        try {
            $context = array_merge(self::getRequestContext(), $context, [
                'timestamp'  => now()->toISOString(),
                'error_type' => $type,
            ]);

            $logMessage = sprintf(
                "[API_ERROR] [%s] %s %s - %s",
                $type,
                $context['method'] ?? 'UNKNOWN',
                $context['endpoint'] ?? 'UNKNOWN',
                $message
            );

            Log::channel('api')->error($logMessage, $context);
        } catch (\Throwable $th) {
            error_log(sprintf("[%s] [LOG_CRITICAL] %s", now()->toDateTimeString(), $th->getMessage()));
        }
    }

    /**
     * Vérifie la validité d'un code HTTP.
     *
     * @param int|HTTPStatus $status
     * @return bool
     */
    protected static function isValidHTTPStatus(int|HTTPStatus $status): bool
    {
        $value = $status instanceof HTTPStatus ? $status->value : $status;
        return $value >= 100 && $value <= 599;
    }

    /**
     * Retourne le message par défaut associé à un code HTTP.
     *
     * @param int|HTTPStatus $status
     * @return string
     */
    protected static function getDefaultMessageForStatus(int|HTTPStatus $status): string
    {
        $value = $status instanceof HTTPStatus ? $status->value : $status;
        $enum = HTTPStatus::tryFrom($value);
        return $enum?->message() ?? 'Statut HTTP inconnu';
    }

    // -----------------------------------------------------------------
    // Méthodes de réponse spécialisées
    // -----------------------------------------------------------------

    /**
     * Réponse de succès (200 par défaut).
     *
     * @param mixed          $data      Données de la réponse
     * @param string         $message   Message descriptif
     * @param int|HTTPStatus $status    Code HTTP (200 par défaut)
     * @param bool           $shouldLog Journaliser ou non
     * @return JsonResponse
     */
    public static function jsonSuccess(
        mixed $data = null,
        string $message = '',
        int|HTTPStatus $status = 200,
        bool $shouldLog = true
    ): JsonResponse {
        return self::jsonApiResponse($data, $status, $message, [], [], [], $shouldLog);
    }

    /**
     * Réponse d'erreur standardisée.
     *
     * @param string         $message   Message d'erreur
     * @param int|HTTPStatus $status    Code HTTP (400 par défaut)
     * @param array          $errors    Détails des erreurs
     * @param mixed          $data      Données supplémentaires
     * @param bool           $shouldLog Journaliser ou non
     * @param \Throwable|null $exception Exception associée (loguée si présente)
     * @return JsonResponse
     */
    public static function jsonError(
        string $message = '',
        int|HTTPStatus $status = 400,
        array $errors = [],
        mixed $data = null,
        bool $shouldLog = true,
        ?\Throwable $exception = null
    ): JsonResponse {
        return self::jsonApiResponse($data, $status, $message, $errors, [], [], $shouldLog, $exception);
    }

    /**
     * Réponse standardisée pour les données paginées.
     * Supporte à la fois LengthAwarePaginator et ResourceCollection.
     *
     * @param LengthAwarePaginator|ResourceCollection $data      Données paginées
     * @param string                                  $message   Message descriptif
     * @param array                                   $extraMeta Métadonnées supplémentaires
     * @param bool                                    $shouldLog Journaliser ou non
     * @return JsonResponse
     */
    public static function jsonPaginated(
        LengthAwarePaginator|ResourceCollection $data,
        string $message = '',
        array $extraMeta = [],
        bool $shouldLog = true
    ): JsonResponse {
        try {
            // Extraire les éléments selon le type
            $items = $data instanceof LengthAwarePaginator ? $data->items() : $data->collection;

            $meta = array_merge([
                'pagination' => [
                    'total'        => $data->total(),
                    'per_page'     => $data->perPage(),
                    'current_page' => $data->currentPage(),
                    'last_page'    => $data->lastPage(),
                    'from'         => $data->firstItem(),
                    'to'           => $data->lastItem(),
                ]
            ], $extraMeta);

            $links = [
                'first' => $data->url(1),
                'last'  => $data->url($data->lastPage()),
                'prev'  => $data->previousPageUrl(),
                'next'  => $data->nextPageUrl(),
            ];

            return self::jsonApiResponse($items, HTTPStatus::OK->value, $message, [], $meta, $links, $shouldLog);
        } catch (\Throwable $th) {
            self::logApiError('PAGINATION_ERROR', $th->getMessage());
            return self::jsonError('Erreur lors de la pagination des données', HTTPStatus::INTERNAL_SERVER_ERROR->value, [], null, $shouldLog, $th);
        }
    }

    /**
     * Réponse 201 Created.
     *
     * @param mixed  $data      Données de la ressource créée
     * @param string $message   Message descriptif
     * @param bool   $shouldLog Journaliser ou non
     * @return JsonResponse
     */
    public static function jsonCreated(mixed $data = null, string $message = 'Ressource créée avec succès', bool $shouldLog = true): JsonResponse
    {
        return self::jsonApiResponse($data, HTTPStatus::CREATED->value, $message, [], [], [], $shouldLog);
    }

    /**
     * Réponse 401 Unauthorized.
     *
     * @param string $message   Message descriptif
     * @param bool   $shouldLog Journaliser ou non
     * @param \Throwable|null $exception Exception à logger
     * @return JsonResponse
     */
    public static function jsonUnauthorized(string $message = 'Non authentifié', bool $shouldLog = true, ?\Throwable $exception = null): JsonResponse
    {
        return self::jsonError($message, HTTPStatus::UNAUTHORIZED->value, [], null, $shouldLog, $exception);
    }

    /**
     * Réponse 403 Forbidden.
     *
     * @param string $message   Message descriptif
     * @param bool   $shouldLog Journaliser ou non
     * @param \Throwable|null $exception Exception à logger
     * @return JsonResponse
     */
    public static function jsonForbidden(string $message = 'Accès non autorisé', bool $shouldLog = true, ?\Throwable $exception = null): JsonResponse
    {
        return self::jsonError($message, HTTPStatus::FORBIDDEN->value, [], null, $shouldLog, $exception);
    }

    /**
     * Réponse 404 Not Found.
     *
     * @param string $message   Message descriptif
     * @param bool   $shouldLog Journaliser ou non
     * @param \Throwable|null $exception Exception à logger
     * @return JsonResponse
     */
    public static function jsonNotFound(string $message = 'Ressource non trouvée', bool $shouldLog = true, ?\Throwable $exception = null): JsonResponse
    {
        return self::jsonError($message, HTTPStatus::NOT_FOUND->value, [], null, $shouldLog, $exception);
    }

    /**
     * Réponse 422 Validation Error.
     *
     * @param array  $errors    Détails des erreurs de validation
     * @param string $message   Message descriptif
     * @param bool   $shouldLog Journaliser ou non
     * @param \Throwable|null $exception Exception à logger
     * @return JsonResponse
     */
    public static function jsonValidationError(array $errors = [], string $message = 'Erreur de validation', bool $shouldLog = true, ?\Throwable $exception = null): JsonResponse
    {
        return self::jsonError($message, HTTPStatus::UNPROCESSABLE_CONTENT->value, $errors, null, $shouldLog, $exception);
    }

    /**
     * Réponse 204 No Content.
     *
     * @return JsonResponse
     */
    public static function jsonNoContent(): JsonResponse
    {
        return self::jsonApiResponse(null, HTTPStatus::NO_CONTENT->value, 'Aucun contenu', [], [], [], false);
    }

    /**
     * Réponse 400 Bad Request.
     *
     * @param string $message   Message descriptif
     * @param array  $errors    Détails des erreurs
     * @param bool   $shouldLog Journaliser ou non
     * @param \Throwable|null $exception Exception à logger
     * @return JsonResponse
     */
    public static function jsonBadRequest(string $message = 'Requête incorrecte', array $errors = [], bool $shouldLog = true, ?\Throwable $exception = null): JsonResponse
    {
        return self::jsonError($message, HTTPStatus::BAD_REQUEST->value, $errors, null, $shouldLog, $exception);
    }

    /**
     * Réponse 409 Conflict.
     *
     * @param string $message   Message descriptif
     * @param mixed  $data      Données supplémentaires
     * @param bool   $shouldLog Journaliser ou non
     * @param \Throwable|null $exception Exception à logger
     * @return JsonResponse
     */
    public static function jsonConflict(string $message = 'Conflit détecté', mixed $data = null, bool $shouldLog = true, ?\Throwable $exception = null): JsonResponse
    {
        return self::jsonError($message, HTTPStatus::CONFLICT->value, [], $data, $shouldLog, $exception);
    }

    /**
     * Réponse 503 Service Unavailable.
     *
     * @param string $message   Message descriptif
     * @param bool   $shouldLog Journaliser ou non
     * @param \Throwable|null $exception Exception à logger
     * @return JsonResponse
     */
    public static function jsonServiceUnavailable(string $message = 'Service temporairement indisponible', bool $shouldLog = true, ?\Throwable $exception = null): JsonResponse
    {
        return self::jsonError($message, HTTPStatus::SERVICE_UNAVAILABLE->value, [], null, $shouldLog, $exception);
    }

    /**
     * Réponse 500 Internal Server Error.
     *
     * @param string $message   Message descriptif
     * @param array  $errors    Détails supplémentaires
     * @param bool   $shouldLog Journaliser ou non
     * @param \Throwable|null $exception Exception à logger
     * @return JsonResponse
     */
    public static function jsonInternalServerError(string $message = 'Erreur interne du serveur', array $errors = [], bool $shouldLog = true, ?\Throwable $exception = null): JsonResponse
    {
        return self::jsonError($message, HTTPStatus::INTERNAL_SERVER_ERROR->value, $errors, null, $shouldLog, $exception);
    }
}
