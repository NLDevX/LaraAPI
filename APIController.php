<?php

namespace App\Http\Controllers;

use App\Enums\HttpStatus;
use App\Support\Helpers\APIHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

abstract class APIController extends Controller
{
    /**
     * Statuts HTTP qui ne doivent pas être journalisés.
     *
     * @var array<int>
     */
    protected array $excludedLogStatuses = [
        HttpStatus::NO_CONTENT->value,
        HttpStatus::NOT_MODIFIED->value,
    ];

    /**
     * Constructeur.
     * Active le log des requêtes SQL en environnement de développement.
     */
    public function __construct()
    {
        if (config('app.debug')) {
            DB::enableQueryLog();
        }
    }

    /**
     * Détermine si la réponse doit être journalisée en fonction du statut.
     *
     * @param int|HttpStatus $status Code HTTP
     * @return bool
     */
    protected function shouldLog(int|HttpStatus $status): bool
    {
        $statusValue = $status instanceof HttpStatus ? $status->value : $status;
        return !in_array($statusValue, $this->excludedLogStatuses, true);
    }

    /**
     * Réponse API générique.
     *
     * @param mixed          $data      Données de la réponse
     * @param int|HttpStatus $status    Code HTTP (200 par défaut)
     * @param string         $message   Message descriptif
     * @param array          $errors    Liste des erreurs
     * @param array          $meta      Métadonnées additionnelles
     * @param array          $links     Liens de pagination/relation
     * @param bool           $shouldLog Journaliser ou non (true par défaut)
     * @param \Throwable|null $exception Exception associée à loguer
     * @return JsonResponse
     */
    protected function jsonApiResponse(
        mixed $data = null,
        int|HttpStatus $status = 200,
        string $message = '',
        array $errors = [],
        array $meta = [],
        array $links = [],
        bool $shouldLog = true,
        ?\Throwable $exception = null
    ): JsonResponse {
        $log = $shouldLog && $this->shouldLog($status);
        return APIHelper::jsonApiResponse($data, $status, $message, $errors, $meta, $links, $log, $exception);
    }

    /**
     * Réponse de succès (200 par défaut).
     *
     * @param mixed          $data      Données de la réponse
     * @param string         $message   Message descriptif
     * @param int|HttpStatus $status    Code HTTP (200 par défaut)
     * @param bool           $shouldLog Journaliser ou non
     * @return JsonResponse
     */
    protected function jsonSuccess(
        mixed $data = null,
        string $message = '',
        int|HttpStatus $status = 200,
        bool $shouldLog = true
    ): JsonResponse {
        return APIHelper::jsonSuccess($data, $message, $status, $shouldLog && $this->shouldLog($status));
    }

    /**
     * Réponse d'erreur standardisée.
     *
     * @param string         $message   Message d'erreur
     * @param int|HttpStatus $status    Code HTTP (400 par défaut)
     * @param array          $errors    Détails des erreurs
     * @param mixed          $data      Données supplémentaires
     * @param bool           $shouldLog Journaliser ou non
     * @param \Throwable|null $exception Exception à logger
     * @return JsonResponse
     */
    protected function jsonError(
        string $message = '',
        int|HttpStatus $status = 400,
        array $errors = [],
        mixed $data = null,
        bool $shouldLog = true,
        ?\Throwable $exception = null
    ): JsonResponse {
        return APIHelper::jsonError($message, $status, $errors, $data, $shouldLog && $this->shouldLog($status), $exception);
    }

    /**
     * Réponse pour données paginées.
     *
     * @param LengthAwarePaginator|ResourceCollection $data      Données paginées
     * @param string                                  $message   Message descriptif
     * @param array                                   $extraMeta Métadonnées supplémentaires
     * @param bool                                    $shouldLog Journaliser ou non
     * @return JsonResponse
     */
    protected function jsonPaginated(
        LengthAwarePaginator|ResourceCollection $data,
        string $message = '',
        array $extraMeta = [],
        bool $shouldLog = true
    ): JsonResponse {
        return APIHelper::jsonPaginated($data, $message, $extraMeta, $shouldLog && $this->shouldLog(HttpStatus::OK));
    }

    /**
     * Réponse 201 Created.
     *
     * @param mixed  $data      Données de la ressource créée
     * @param string $message   Message descriptif
     * @param bool   $shouldLog Journaliser ou non
     * @return JsonResponse
     */
    protected function jsonCreated(
        mixed $data = null,
        string $message = 'Ressource créée avec succès',
        bool $shouldLog = true
    ): JsonResponse {
        return APIHelper::jsonCreated($data, $message, $shouldLog && $this->shouldLog(HttpStatus::CREATED));
    }

    /**
     * Réponse 401 Unauthorized.
     *
     * @param string $message   Message descriptif
     * @param bool   $shouldLog Journaliser ou non
     * @param \Throwable|null $exception Exception à logger
     * @return JsonResponse
     */
    protected function jsonUnauthorized(string $message = 'Non authentifié', bool $shouldLog = true, ?\Throwable $exception = null): JsonResponse
    {
        return APIHelper::jsonUnauthorized($message, $shouldLog && $this->shouldLog(HttpStatus::UNAUTHORIZED), $exception);
    }

    /**
     * Réponse 403 Forbidden.
     *
     * @param string $message   Message descriptif
     * @param bool   $shouldLog Journaliser ou non
     * @param \Throwable|null $exception Exception à logger
     * @return JsonResponse
     */
    protected function jsonForbidden(string $message = 'Accès non autorisé', bool $shouldLog = true, ?\Throwable $exception = null): JsonResponse
    {
        return APIHelper::jsonForbidden($message, $shouldLog && $this->shouldLog(HttpStatus::FORBIDDEN), $exception);
    }

    /**
     * Réponse 404 Not Found.
     *
     * @param string $message   Message descriptif
     * @param bool   $shouldLog Journaliser ou non
     * @param \Throwable|null $exception Exception à logger
     * @return JsonResponse
     */
    protected function jsonNotFound(string $message = 'Ressource non trouvée', bool $shouldLog = true, ?\Throwable $exception = null): JsonResponse
    {
        return APIHelper::jsonNotFound($message, $shouldLog && $this->shouldLog(HttpStatus::NOT_FOUND), $exception);
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
    protected function jsonValidationError(array $errors = [], string $message = 'Erreur de validation', bool $shouldLog = true, ?\Throwable $exception = null): JsonResponse
    {
        return APIHelper::jsonValidationError($errors, $message, $shouldLog && $this->shouldLog(HttpStatus::UNPROCESSABLE_CONTENT), $exception);
    }

    /**
     * Réponse 204 No Content.
     *
     * @return JsonResponse
     */
    protected function jsonNoContent(): JsonResponse
    {
        return APIHelper::jsonNoContent();
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
    protected function jsonBadRequest(string $message = 'Requête incorrecte', array $errors = [], bool $shouldLog = true, ?\Throwable $exception = null): JsonResponse
    {
        return APIHelper::jsonBadRequest($message, $errors, $shouldLog && $this->shouldLog(HttpStatus::BAD_REQUEST), $exception);
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
    protected function jsonConflict(string $message = 'Conflit détecté', mixed $data = null, bool $shouldLog = true, ?\Throwable $exception = null): JsonResponse
    {
        return APIHelper::jsonConflict($message, $data, $shouldLog && $this->shouldLog(HttpStatus::CONFLICT), $exception);
    }

    /**
     * Réponse 503 Service Unavailable.
     *
     * @param string $message   Message descriptif
     * @param bool   $shouldLog Journaliser ou non
     * @param \Throwable|null $exception Exception à logger
     * @return JsonResponse
     */
    protected function jsonServiceUnavailable(string $message = 'Service temporairement indisponible', bool $shouldLog = true, ?\Throwable $exception = null): JsonResponse
    {
        return APIHelper::jsonServiceUnavailable($message, $shouldLog && $this->shouldLog(HttpStatus::SERVICE_UNAVAILABLE), $exception);
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
    protected function jsonInternalServerError(string $message = 'Erreur interne du serveur', array $errors = [], bool $shouldLog = true, ?\Throwable $exception = null): JsonResponse
    {
        return APIHelper::jsonInternalServerError($message, $errors, $shouldLog && $this->shouldLog(HttpStatus::INTERNAL_SERVER_ERROR), $exception);
    }
}
