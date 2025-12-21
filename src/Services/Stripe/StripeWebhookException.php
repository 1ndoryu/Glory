<?php

/**
 * StripeWebhookException
 *
 * Excepcion personalizada para errores de webhook de Stripe
 *
 * @package Glory\Services\Stripe
 */

namespace Glory\Services\Stripe;

class StripeWebhookException extends \Exception
{
    private string $errorCode;

    public function __construct(string $message, string $errorCode, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->errorCode = $errorCode;
    }

    /**
     * Codigo de error para respuestas API
     */
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * HTTP status code sugerido
     */
    public function getHttpStatus(): int
    {
        return match ($this->errorCode) {
            'config_error' => 500,
            'missing_signature', 'invalid_format', 'signature_mismatch' => 400,
            'timestamp_expired' => 400,
            'invalid_json' => 400,
            default => 400,
        };
    }
}
