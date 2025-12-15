<?php

namespace Glory\Plugins\AmazonProduct\Model;

/**
 * Modelo de Licencia para el sistema SaaS.
 * 
 * Representa una licencia de cliente con:
 * - API Key unica
 * - Email del cliente
 * - Estado (active, trial, expired, suspended)
 * - Control de GB usados y limite
 * - Fechas de inicio, expiracion
 * - ID de suscripcion de Stripe
 */
class License
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_TRIAL = 'trial';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_SUSPENDED = 'suspended';

    public const DEFAULT_GB_LIMIT = 4;

    private int $id;
    private string $apiKey;
    private string $email;
    private string $status;
    private float $gbUsed;
    private float $gbLimit;
    private string $stripeCustomerId;
    private string $stripeSubscriptionId;
    private int $createdAt;
    private int $expiresAt;
    private int $lastRequestAt;

    public function __construct(array $data = [])
    {
        $this->id = (int) ($data['id'] ?? 0);
        $this->apiKey = $data['api_key'] ?? '';
        $this->email = $data['email'] ?? '';
        $this->status = $data['status'] ?? self::STATUS_TRIAL;
        $this->gbUsed = (float) ($data['gb_used'] ?? 0);
        $this->gbLimit = (float) ($data['gb_limit'] ?? self::DEFAULT_GB_LIMIT);
        $this->stripeCustomerId = $data['stripe_customer_id'] ?? '';
        $this->stripeSubscriptionId = $data['stripe_subscription_id'] ?? '';
        $this->createdAt = (int) ($data['created_at'] ?? time());
        $this->expiresAt = (int) ($data['expires_at'] ?? 0);
        $this->lastRequestAt = (int) ($data['last_request_at'] ?? 0);
    }

    /*
     * Getters
     */

    public function getId(): int
    {
        return $this->id;
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getGbUsed(): float
    {
        return $this->gbUsed;
    }

    public function getGbLimit(): float
    {
        return $this->gbLimit;
    }

    public function getGbRemaining(): float
    {
        return max(0, $this->gbLimit - $this->gbUsed);
    }

    public function getStripeCustomerId(): string
    {
        return $this->stripeCustomerId;
    }

    public function getStripeSubscriptionId(): string
    {
        return $this->stripeSubscriptionId;
    }

    public function getCreatedAt(): int
    {
        return $this->createdAt;
    }

    public function getExpiresAt(): int
    {
        return $this->expiresAt;
    }

    public function getLastRequestAt(): int
    {
        return $this->lastRequestAt;
    }

    /*
     * Setters
     */

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function setApiKey(string $apiKey): void
    {
        $this->apiKey = $apiKey;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function setGbUsed(float $gbUsed): void
    {
        $this->gbUsed = $gbUsed;
    }

    public function setGbLimit(float $gbLimit): void
    {
        $this->gbLimit = $gbLimit;
    }

    public function setExpiresAt(int $expiresAt): void
    {
        $this->expiresAt = $expiresAt;
    }

    public function setLastRequestAt(int $timestamp): void
    {
        $this->lastRequestAt = $timestamp;
    }

    public function setStripeCustomerId(string $id): void
    {
        $this->stripeCustomerId = $id;
    }

    public function setStripeSubscriptionId(string $id): void
    {
        $this->stripeSubscriptionId = $id;
    }
    
    /*
     * Logica de negocio
     */

    /**
     * Verifica si la licencia esta activa y puede hacer requests.
     */
    public function isValid(): bool
    {
        if ($this->status === self::STATUS_SUSPENDED) {
            return false;
        }

        if ($this->status === self::STATUS_EXPIRED) {
            return false;
        }

        if ($this->expiresAt > 0 && time() > $this->expiresAt) {
            return false;
        }

        return true;
    }

    /**
     * Verifica si tiene GB disponibles.
     */
    public function hasGbAvailable(): bool
    {
        return $this->getGbRemaining() > 0;
    }

    /**
     * Verifica si puede hacer una request (licencia valida + GB disponible).
     */
    public function canMakeRequest(): bool
    {
        return $this->isValid() && $this->hasGbAvailable();
    }

    /**
     * Registra uso de bytes.
     * 
     * @param int $bytes Bytes consumidos
     */
    public function addUsage(int $bytes): void
    {
        $gbConsumed = $bytes / (1024 * 1024 * 1024);
        $this->gbUsed += $gbConsumed;
        $this->lastRequestAt = time();
    }

    /**
     * Reinicia el contador de GB (para nuevo ciclo de facturacion).
     */
    public function resetUsage(): void
    {
        $this->gbUsed = 0;
    }

    /**
     * Convierte a array para guardar en DB.
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'api_key' => $this->apiKey,
            'email' => $this->email,
            'status' => $this->status,
            'gb_used' => $this->gbUsed,
            'gb_limit' => $this->gbLimit,
            'stripe_customer_id' => $this->stripeCustomerId,
            'stripe_subscription_id' => $this->stripeSubscriptionId,
            'created_at' => $this->createdAt,
            'expires_at' => $this->expiresAt,
            'last_request_at' => $this->lastRequestAt,
        ];
    }

    /**
     * Obtiene porcentaje de uso.
     */
    public function getUsagePercentage(): float
    {
        if ($this->gbLimit <= 0) {
            return 100;
        }
        return min(100, ($this->gbUsed / $this->gbLimit) * 100);
    }

    /**
     * Verifica si esta cerca del limite (>80%).
     */
    public function isNearLimit(): bool
    {
        return $this->getUsagePercentage() >= 80;
    }
}
