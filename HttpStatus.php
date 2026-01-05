<?php

namespace App\Enums;

use Symfony\Component\HttpFoundation\Response;

/**
 * Enumération complète des statuts HTTP
 * Compatible PHP 8.4 / Laravel 12
 * Fournit une méthode message() pour récupérer la description du statut
 */
enum HttpStatus: int
{
    // 1xx Informational
    case CONTINUE = Response::HTTP_CONTINUE;
    case SWITCHING_PROTOCOLS = Response::HTTP_SWITCHING_PROTOCOLS;
    case PROCESSING = Response::HTTP_PROCESSING;
    case EARLY_HINTS = Response::HTTP_EARLY_HINTS;

        // 2xx Success
    case OK = Response::HTTP_OK;
    case CREATED = Response::HTTP_CREATED;
    case ACCEPTED = Response::HTTP_ACCEPTED;
    case NON_AUTHORITATIVE_INFORMATION = Response::HTTP_NON_AUTHORITATIVE_INFORMATION;
    case NO_CONTENT = Response::HTTP_NO_CONTENT;
    case RESET_CONTENT = Response::HTTP_RESET_CONTENT;
    case PARTIAL_CONTENT = Response::HTTP_PARTIAL_CONTENT;
    case MULTI_STATUS = Response::HTTP_MULTI_STATUS;
    case ALREADY_REPORTED = Response::HTTP_ALREADY_REPORTED;
    case IM_USED = Response::HTTP_IM_USED;

        // 3xx Redirection
    case MULTIPLE_CHOICES = Response::HTTP_MULTIPLE_CHOICES;
    case MOVED_PERMANENTLY = Response::HTTP_MOVED_PERMANENTLY;
    case FOUND = Response::HTTP_FOUND;
    case SEE_OTHER = Response::HTTP_SEE_OTHER;
    case NOT_MODIFIED = Response::HTTP_NOT_MODIFIED;
    case TEMPORARY_REDIRECT = Response::HTTP_TEMPORARY_REDIRECT;
    case PERMANENT_REDIRECT = Response::HTTP_PERMANENTLY_REDIRECT;

        // 4xx Client errors
    case BAD_REQUEST = Response::HTTP_BAD_REQUEST;
    case UNAUTHORIZED = Response::HTTP_UNAUTHORIZED;
    case PAYMENT_REQUIRED = Response::HTTP_PAYMENT_REQUIRED;
    case FORBIDDEN = Response::HTTP_FORBIDDEN;
    case NOT_FOUND = Response::HTTP_NOT_FOUND;
    case METHOD_NOT_ALLOWED = Response::HTTP_METHOD_NOT_ALLOWED;
    case NOT_ACCEPTABLE = Response::HTTP_NOT_ACCEPTABLE;
    case PROXY_AUTHENTICATION_REQUIRED = Response::HTTP_PROXY_AUTHENTICATION_REQUIRED;
    case REQUEST_TIMEOUT = Response::HTTP_REQUEST_TIMEOUT;
    case CONFLICT = Response::HTTP_CONFLICT;
    case GONE = Response::HTTP_GONE;
    case LENGTH_REQUIRED = Response::HTTP_LENGTH_REQUIRED;
    case PRECONDITION_FAILED = Response::HTTP_PRECONDITION_FAILED;
    case CONTENT_TOO_LARGE = Response::HTTP_REQUEST_ENTITY_TOO_LARGE;
    case URI_TOO_LONG = Response::HTTP_REQUEST_URI_TOO_LONG;
    case UNSUPPORTED_MEDIA_TYPE = Response::HTTP_UNSUPPORTED_MEDIA_TYPE;
    case RANGE_NOT_SATISFIABLE = Response::HTTP_REQUESTED_RANGE_NOT_SATISFIABLE;
    case EXPECTATION_FAILED = Response::HTTP_EXPECTATION_FAILED;
    case I_AM_A_TEAPOT = Response::HTTP_I_AM_A_TEAPOT;
    case MISDIRECTED_REQUEST = Response::HTTP_MISDIRECTED_REQUEST;
    case UNPROCESSABLE_CONTENT = Response::HTTP_UNPROCESSABLE_ENTITY;
    case LOCKED = Response::HTTP_LOCKED;
    case FAILED_DEPENDENCY = Response::HTTP_FAILED_DEPENDENCY;
    case TOO_EARLY = Response::HTTP_TOO_EARLY;
    case UPGRADE_REQUIRED = Response::HTTP_UPGRADE_REQUIRED;
    case PRECONDITION_REQUIRED = Response::HTTP_PRECONDITION_REQUIRED;
    case TOO_MANY_REQUESTS = Response::HTTP_TOO_MANY_REQUESTS;
    case REQUEST_HEADER_FIELDS_TOO_LARGE = Response::HTTP_REQUEST_HEADER_FIELDS_TOO_LARGE;
    case UNAVAILABLE_FOR_LEGAL_REASONS = Response::HTTP_UNAVAILABLE_FOR_LEGAL_REASONS;

        // 5xx Server errors
    case INTERNAL_SERVER_ERROR = Response::HTTP_INTERNAL_SERVER_ERROR;
    case NOT_IMPLEMENTED = Response::HTTP_NOT_IMPLEMENTED;
    case BAD_GATEWAY = Response::HTTP_BAD_GATEWAY;
    case SERVICE_UNAVAILABLE = Response::HTTP_SERVICE_UNAVAILABLE;
    case GATEWAY_TIMEOUT = Response::HTTP_GATEWAY_TIMEOUT;
    case VERSION_NOT_SUPPORTED = Response::HTTP_VERSION_NOT_SUPPORTED;
    case VARIANT_ALSO_NEGOTIATES = Response::HTTP_VARIANT_ALSO_NEGOTIATES_EXPERIMENTAL;
    case INSUFFICIENT_STORAGE = Response::HTTP_INSUFFICIENT_STORAGE;
    case LOOP_DETECTED = Response::HTTP_LOOP_DETECTED;
    case NOT_EXTENDED = Response::HTTP_NOT_EXTENDED;
    case NETWORK_AUTHENTICATION_REQUIRED = Response::HTTP_NETWORK_AUTHENTICATION_REQUIRED;

    /**
     * Retourne la description standard du code HTTP
     */
    public function message(): string
    {
        return match ($this) {
            // 1xx
            self::CONTINUE => 'Le client peut continuer la requête.',
            self::SWITCHING_PROTOCOLS => 'Le serveur change de protocole selon la requête.',
            self::PROCESSING => 'Le serveur traite la requête.',
            self::EARLY_HINTS => 'Indique que des en-têtes préliminaires vont être envoyés.',

            // 2xx
            self::OK => 'Requête traitée avec succès.',
            self::CREATED => 'Ressource créée avec succès.',
            self::ACCEPTED => 'Requête acceptée pour traitement.',
            self::NON_AUTHORITATIVE_INFORMATION => 'Informations renvoyées non autoritatives.',
            self::NO_CONTENT => 'Aucun contenu à renvoyer.',
            self::RESET_CONTENT => 'Réinitialiser le document.',
            self::PARTIAL_CONTENT => 'Contenu partiel renvoyé.',
            self::MULTI_STATUS => 'Résultat multiple pour différentes sous-requêtes.',
            self::ALREADY_REPORTED => 'Les membres ont déjà été signalés.',
            self::IM_USED => 'La réponse a été transformée par un proxy intermédiaire.',

            // 3xx
            self::MULTIPLE_CHOICES => 'Plusieurs choix possibles pour la ressource.',
            self::MOVED_PERMANENTLY => 'La ressource a été déplacée de manière permanente.',
            self::FOUND => 'La ressource a été trouvée à une autre URI.',
            self::SEE_OTHER => 'La réponse est disponible à une autre URI.',
            self::NOT_MODIFIED => 'La ressource n’a pas été modifiée.',
            self::TEMPORARY_REDIRECT => 'La redirection est temporaire.',
            self::PERMANENT_REDIRECT => 'La redirection est permanente.',

            // 4xx
            self::BAD_REQUEST => 'Requête mal formée.',
            self::UNAUTHORIZED => 'Authentification requise.',
            self::PAYMENT_REQUIRED => 'Paiement requis.',
            self::FORBIDDEN => 'Accès refusé.',
            self::NOT_FOUND => 'Ressource introuvable.',
            self::METHOD_NOT_ALLOWED => 'Méthode non autorisée pour cette ressource.',
            self::NOT_ACCEPTABLE => 'Contenu non acceptable selon l’entête Accept.',
            self::PROXY_AUTHENTICATION_REQUIRED => 'Authentification proxy requise.',
            self::REQUEST_TIMEOUT => 'Délai d’attente dépassé.',
            self::CONFLICT => 'Conflit avec l’état actuel de la ressource.',
            self::GONE => 'La ressource demandée n’existe plus.',
            self::LENGTH_REQUIRED => 'L’en-tête Content-Length est requis.',
            self::PRECONDITION_FAILED => 'Précondition échouée.',
            self::CONTENT_TOO_LARGE => 'Contenu trop volumineux.',
            self::URI_TOO_LONG => 'URI trop longue.',
            self::UNSUPPORTED_MEDIA_TYPE => 'Type de contenu non supporté.',
            self::RANGE_NOT_SATISFIABLE => 'Plage de requête invalide.',
            self::EXPECTATION_FAILED => 'Attente du client non satisfaite.',
            self::I_AM_A_TEAPOT => 'Je suis une théière ☕.',
            self::MISDIRECTED_REQUEST => 'Requête dirigée vers un serveur incorrect.',
            self::UNPROCESSABLE_CONTENT => 'Contenu non traitable (erreur de validation).',
            self::LOCKED => 'Ressource verrouillée.',
            self::FAILED_DEPENDENCY => 'Dépendance échouée.',
            self::TOO_EARLY => 'Requête trop précoce.',
            self::UPGRADE_REQUIRED => 'Mise à niveau du protocole requise.',
            self::PRECONDITION_REQUIRED => 'Précondition requise.',
            self::TOO_MANY_REQUESTS => 'Trop de requêtes envoyées.',
            self::REQUEST_HEADER_FIELDS_TOO_LARGE => 'En-têtes de requête trop volumineux.',
            self::UNAVAILABLE_FOR_LEGAL_REASONS => 'Contenu indisponible pour raisons légales.',

            // 5xx
            self::INTERNAL_SERVER_ERROR => 'Erreur interne du serveur.',
            self::NOT_IMPLEMENTED => 'Fonctionnalité non implémentée.',
            self::BAD_GATEWAY => 'Passerelle invalide.',
            self::SERVICE_UNAVAILABLE => 'Service temporairement indisponible.',
            self::GATEWAY_TIMEOUT => 'Délai d’attente de la passerelle dépassé.',
            self::VERSION_NOT_SUPPORTED => 'Version HTTP non supportée.',
            self::VARIANT_ALSO_NEGOTIATES => 'Erreur de négociation de contenu.',
            self::INSUFFICIENT_STORAGE => 'Espace insuffisant sur le serveur.',
            self::LOOP_DETECTED => 'Boucle infinie détectée.',
            self::NOT_EXTENDED => 'Extension de requête requise.',
            self::NETWORK_AUTHENTICATION_REQUIRED => 'Authentification réseau requise.',

            default => 'Statut inconnu',
        };
    }
}