<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use App\Enums\HttpStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

abstract class ApiController extends Controller
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
    protected function jsonApiResponse(
        mixed $data = null,
        int|HttpStatus $status = 200,
        string $message = '',
        array $errors = [],
        array $meta = [],
        array $links = []
    ): JsonResponse {
        try {
            if (!$this->isValidHttpStatus($status)) {
                throw new \InvalidArgumentException("Code HTTP invalide : $status");
            }

            $success = $status >= 200 && $status < 300;
            $message = $message ?: $this->getDefaultMessageForStatus($status);

            $response = array_filter([
                'success' => $success,
                'data' => $data,
                'message' => $message,
                'meta' => !empty($meta) ? $meta : null,
                'links' => !empty($links) ? $links : null,
                'errors' => !empty($errors) ? $errors : null,
            ]);

            $context = $this->getRequestContext();
            $this->logApiResponse($status, $message, $data, $errors, $context);

            return Response::json($response, $status);
        } catch (\Throwable $th) {
            $this->logApiError('RESPONSE_GENERATION', $th->getMessage(), [
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
    protected function getRequestContext(): array
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
    protected function logApiResponse(
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
    protected function logApiError(string $type, string $message, array $context = []): void
    {
        try {
            $context = array_merge($this->getRequestContext(), $context, [
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
    protected function isValidHttpStatus(int|HttpStatus $status): bool
    {
        return $status >= 100 && $status <= 599;
    }

    /**
     * Retourne la description associée à un code HTTP.
     *
     * @param int|HttpStatus $status
     * @return string
     */
    protected function getDefaultMessageForStatus(int|HttpStatus $status): string
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
    protected function jsonSuccess(
        mixed $data = null,
        string $message = '',
        int|HttpStatus $status = 200
    ): JsonResponse {
        return $this->jsonApiResponse($data, $status, $message);
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
    protected function jsonError(
        string $message = '',
        int|HttpStatus $status = 400,
        array $errors = [],
        mixed $data = null
    ): JsonResponse {
        return $this->jsonApiResponse($data, $status, $message, $errors);
    }

    /**
     * Réponse standardisée pour les données paginées.
     *
     * @param LengthAwarePaginator $data
     * @param string $message
     * @param array $extraMeta
     * @return JsonResponse
     */
    protected function jsonPaginated(
        LengthAwarePaginator $data,
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

            return $this->jsonApiResponse($data->items(), HttpStatus::OK->value, $message, [], $meta, $links);
        } catch (\Throwable $th) {
            $this->logApiError('PAGINATION_ERROR', $th->getMessage());
            return $this->jsonError('Erreur lors de la pagination des données', HttpStatus::INTERNAL_SERVER_ERROR->value);
        }
    }

    /**
     * Réponse 201 Created
     *
     * @param mixed $data
     * @param string $message
     * @return JsonResponse
     */
    protected function jsonCreated(mixed $data = null, string $message = 'Ressource créée avec succès'): JsonResponse
    {
        return $this->jsonApiResponse($data, HttpStatus::CREATED->value, $message);
    }

    /**
     * Réponse 401 Unauthorized
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function jsonUnauthorized(string $message = 'Non authentifié'): JsonResponse
    {
        return $this->jsonError($message, HttpStatus::UNAUTHORIZED->value);
    }

    /**
     * Réponse 403 Forbidden
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function jsonForbidden(string $message = 'Accès non autorisé'): JsonResponse
    {
        return $this->jsonError($message, HttpStatus::FORBIDDEN->value);
    }

    /**
     * Réponse 404 Not Found
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function jsonNotFound(string $message = 'Ressource non trouvée'): JsonResponse
    {
        return $this->jsonError($message, HttpStatus::NOT_FOUND->value);
    }

    /**
     * Réponse 422 Validation Error
     *
     * @param array $errors
     * @param string $message
     * @return JsonResponse
     */
    protected function jsonValidationError(array $errors = [], string $message = 'Erreur de validation'): JsonResponse
    {
        return $this->jsonError($message, HttpStatus::UNPROCESSABLE_CONTENT->value, $errors);
    }

    /**
     * Réponse 204 No Content
     *
     * @return JsonResponse
     */
    protected function jsonNoContent(): JsonResponse
    {
        return $this->jsonApiResponse(null, HttpStatus::NO_CONTENT->value, 'Aucun contenu');
    }

    /**
     * Réponse 400 Bad Request
     *
     * @param string $message
     * @param array $errors
     * @return JsonResponse
     */
    protected function jsonBadRequest(string $message = 'Requête incorrecte', array $errors = []): JsonResponse
    {
        return $this->jsonError($message, HttpStatus::BAD_REQUEST->value, $errors);
    }

    /**
     * Réponse 409 Conflict
     *
     * @param string $message
     * @param mixed $data
     * @return JsonResponse
     */
    protected function jsonConflict(string $message = 'Conflit détecté', mixed $data = null): JsonResponse
    {
        return $this->jsonApiResponse($data, HttpStatus::CONFLICT->value, $message);
    }

    /**
     * Réponse 503 Service Unavailable
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function jsonServiceUnavailable(string $message = 'Service temporairement indisponible'): JsonResponse
    {
        return $this->jsonError($message, HttpStatus::SERVICE_UNAVAILABLE->value);
    }

    /**
     * Réponse 500 Internal Server Error
     *
     * @param string $message
     * @param array $errors
     * @return JsonResponse
     */
    protected function jsonInternalServerError(string $message = 'Erreur interne du serveur', array $errors = []): JsonResponse
    {
        return $this->jsonError($message, HttpStatus::INTERNAL_SERVER_ERROR->value, $errors);
    }
}
