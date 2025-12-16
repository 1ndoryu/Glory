<?php

namespace Glory\Plugins\AmazonProduct\Service;

use Glory\Core\GloryLogger;

/**
 * Configuracion SMTP para emails del plugin.
 * 
 * Usa Brevo (ex-Sendinblue) como proveedor SMTP.
 * 
 * Constantes requeridas en wp-config.php:
 * - GLORY_SMTP_HOST
 * - GLORY_SMTP_PORT
 * - GLORY_SMTP_USER
 * - GLORY_SMTP_PASS
 * - GLORY_SMTP_FROM_EMAIL (opcional)
 * - GLORY_SMTP_FROM_NAME (opcional)
 */
class SmtpConfig
{
    /**
     * Inicializa la configuracion SMTP.
     * Llamar una sola vez durante la carga del plugin.
     */
    public static function init(): void
    {
        if (!self::isConfigured()) {
            return;
        }

        add_action('phpmailer_init', [self::class, 'configurePhpMailer']);
    }

    /**
     * Verifica si SMTP esta configurado.
     */
    public static function isConfigured(): bool
    {
        return defined('GLORY_SMTP_HOST')
            && defined('GLORY_SMTP_USER')
            && defined('GLORY_SMTP_PASS');
    }

    /**
     * Configura PHPMailer para usar SMTP.
     */
    public static function configurePhpMailer($phpmailer): void
    {
        $phpmailer->isSMTP();
        $phpmailer->Host = GLORY_SMTP_HOST;
        $phpmailer->Port = defined('GLORY_SMTP_PORT') ? GLORY_SMTP_PORT : 587;
        $phpmailer->SMTPAuth = true;
        $phpmailer->Username = GLORY_SMTP_USER;
        $phpmailer->Password = GLORY_SMTP_PASS;
        $phpmailer->SMTPSecure = 'tls';

        /*
         * From address (opcional, usa admin_email si no esta definido)
         */
        if (defined('GLORY_SMTP_FROM_EMAIL')) {
            $phpmailer->From = GLORY_SMTP_FROM_EMAIL;
        }

        if (defined('GLORY_SMTP_FROM_NAME')) {
            $phpmailer->FromName = GLORY_SMTP_FROM_NAME;
        }

        /*
         * Debug mode (solo si esta definido)
         */
        if (defined('GLORY_SMTP_DEBUG') && GLORY_SMTP_DEBUG) {
            $phpmailer->SMTPDebug = 2;
            $phpmailer->Debugoutput = function ($str, $level) {
                GloryLogger::info("SMTP Debug [{$level}]: {$str}");
            };
        }

        GloryLogger::info('SMTP: PHPMailer configurado con ' . GLORY_SMTP_HOST);
    }

    /**
     * Obtiene info de configuracion (para diagnostico).
     */
    public static function getConfigInfo(): array
    {
        return [
            'configured' => self::isConfigured(),
            'host' => defined('GLORY_SMTP_HOST') ? GLORY_SMTP_HOST : null,
            'port' => defined('GLORY_SMTP_PORT') ? GLORY_SMTP_PORT : 587,
            'user' => defined('GLORY_SMTP_USER') ? substr(GLORY_SMTP_USER, 0, 5) . '...' : null,
            'from_email' => defined('GLORY_SMTP_FROM_EMAIL') ? GLORY_SMTP_FROM_EMAIL : get_option('admin_email'),
            'from_name' => defined('GLORY_SMTP_FROM_NAME') ? GLORY_SMTP_FROM_NAME : 'Glory Plugin',
        ];
    }
}
