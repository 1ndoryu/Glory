<?php

namespace Glory\Plugins\AmazonProduct\Model;

/**
 * PostType para registrar transacciones/eventos de Stripe.
 * 
 * Permite tener un historial visible en WordPress de:
 * - Nuevas suscripciones
 * - Renovaciones
 * - Cancelaciones
 * - Pagos fallidos
 */
class TransactionLog
{
    public const POST_TYPE = 'glory_transaction';

    /* Tipos de transaccion */
    public const TYPE_SUBSCRIPTION_CREATED = 'subscription_created';
    public const TYPE_SUBSCRIPTION_RENEWED = 'subscription_renewed';
    public const TYPE_SUBSCRIPTION_CANCELED = 'subscription_canceled';
    public const TYPE_PAYMENT_FAILED = 'payment_failed';
    public const TYPE_TRIAL_STARTED = 'trial_started';

    /**
     * Registra el PostType en WordPress.
     */
    public static function register(): void
    {
        register_post_type(self::POST_TYPE, [
            'labels' => [
                'name' => 'Transacciones',
                'singular_name' => 'Transaccion',
                'menu_name' => 'Transacciones',
                'all_items' => 'Todas las Transacciones',
                'view_item' => 'Ver Transaccion',
                'search_items' => 'Buscar Transacciones',
                'not_found' => 'No se encontraron transacciones',
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'edit.php?post_type=amazon_product',
            'capability_type' => 'post',
            'capabilities' => [
                'create_posts' => 'do_not_allow',
            ],
            'map_meta_cap' => true,
            'supports' => ['title', 'editor', 'custom-fields'],
            'menu_icon' => 'dashicons-money-alt',
        ]);
    }

    /**
     * Crea un nuevo registro de transaccion.
     */
    public static function create(array $data): int
    {
        $type = $data['type'] ?? 'unknown';
        $email = $data['email'] ?? 'desconocido';
        $subscriptionId = $data['subscription_id'] ?? '';
        $customerId = $data['customer_id'] ?? '';
        $amount = $data['amount'] ?? 0;
        $currency = $data['currency'] ?? 'usd';
        $details = $data['details'] ?? [];
        $eventId = $data['event_id'] ?? '';

        $typeLabels = [
            self::TYPE_SUBSCRIPTION_CREATED => 'Nueva Suscripcion',
            self::TYPE_SUBSCRIPTION_RENEWED => 'Renovacion',
            self::TYPE_SUBSCRIPTION_CANCELED => 'Cancelacion',
            self::TYPE_PAYMENT_FAILED => 'Pago Fallido',
            self::TYPE_TRIAL_STARTED => 'Inicio de Prueba',
        ];

        $title = sprintf(
            '[%s] %s - %s',
            date('Y-m-d H:i'),
            $typeLabels[$type] ?? $type,
            $email
        );

        $content = self::buildContent($data);

        $postId = wp_insert_post([
            'post_type' => self::POST_TYPE,
            'post_title' => $title,
            'post_content' => $content,
            'post_status' => 'publish',
            'meta_input' => [
                '_transaction_type' => $type,
                '_customer_email' => $email,
                '_subscription_id' => $subscriptionId,
                '_customer_id' => $customerId,
                '_amount' => $amount,
                '_currency' => $currency,
                '_event_id' => $eventId,
                '_raw_data' => wp_json_encode($details),
                '_timestamp' => time(),
            ],
        ]);

        return $postId ?: 0;
    }

    /**
     * Construye el contenido HTML del registro.
     */
    private static function buildContent(array $data): string
    {
        $type = $data['type'] ?? 'unknown';
        $email = $data['email'] ?? 'desconocido';
        $subscriptionId = $data['subscription_id'] ?? '-';
        $customerId = $data['customer_id'] ?? '-';
        $amount = $data['amount'] ?? 0;
        $currency = strtoupper($data['currency'] ?? 'USD');
        $details = $data['details'] ?? [];
        $eventId = $data['event_id'] ?? '-';
        $apiKey = $data['api_key'] ?? null;

        $amountFormatted = number_format($amount / 100, 2);

        $html = "<h3>Detalles de la Transaccion</h3>\n";
        $html .= "<table style='width:100%; border-collapse:collapse;'>\n";
        $html .= "<tr><td><strong>Tipo:</strong></td><td>{$type}</td></tr>\n";
        $html .= "<tr><td><strong>Email:</strong></td><td>{$email}</td></tr>\n";
        $html .= "<tr><td><strong>Monto:</strong></td><td>{$amountFormatted} {$currency}</td></tr>\n";
        $html .= "<tr><td><strong>Customer ID:</strong></td><td>{$customerId}</td></tr>\n";
        $html .= "<tr><td><strong>Subscription ID:</strong></td><td>{$subscriptionId}</td></tr>\n";
        $html .= "<tr><td><strong>Event ID:</strong></td><td>{$eventId}</td></tr>\n";

        if ($apiKey) {
            $html .= "<tr><td><strong>API Key generada:</strong></td><td><code>{$apiKey}</code></td></tr>\n";
        }

        $html .= "<tr><td><strong>Fecha:</strong></td><td>" . date('Y-m-d H:i:s') . "</td></tr>\n";
        $html .= "</table>\n";

        if (!empty($details)) {
            $html .= "\n<h4>Datos adicionales</h4>\n";
            $html .= "<pre>" . esc_html(wp_json_encode($details, JSON_PRETTY_PRINT)) . "</pre>\n";
        }

        return $html;
    }

    /**
     * Obtiene las ultimas transacciones.
     */
    public static function getRecent(int $limit = 20): array
    {
        $posts = get_posts([
            'post_type' => self::POST_TYPE,
            'posts_per_page' => $limit,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        $transactions = [];
        foreach ($posts as $post) {
            $transactions[] = [
                'id' => $post->ID,
                'title' => $post->post_title,
                'type' => get_post_meta($post->ID, '_transaction_type', true),
                'email' => get_post_meta($post->ID, '_customer_email', true),
                'amount' => get_post_meta($post->ID, '_amount', true),
                'currency' => get_post_meta($post->ID, '_currency', true),
                'date' => $post->post_date,
            ];
        }

        return $transactions;
    }
}
