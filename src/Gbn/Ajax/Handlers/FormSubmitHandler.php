<?php

/**
 * FormSubmitHandler - Endpoint AJAX para envío de formularios GBN
 * 
 * Maneja el envío de formularios creados con GBN:
 * - Valida honeypot anti-spam
 * - Sanitiza datos del formulario
 * - Formatea contenido en HTML
 * - Envía correo al administrador usando EmailUtility
 * 
 * @package Glory\Gbn\Ajax\Handlers
 * @since Fase 14.5
 */

namespace Glory\Gbn\Ajax\Handlers;

use Glory\Utility\EmailUtility;
use Glory\Core\GloryLogger;

class FormSubmitHandler
{
    /**
     * Procesa el envío de un formulario GBN.
     * 
     * Endpoint: wp_ajax_gbn_form_submit
     *           wp_ajax_nopriv_gbn_form_submit (disponible para usuarios no logueados)
     * 
     * @return void Envía respuesta JSON y termina la ejecución
     */
    public static function handle(): void
    {
        // Verificar nonce para seguridad básica (si está disponible)
        // Para formularios públicos, el nonce puede no estar presente
        $nonceValid = isset($_POST['nonce']) 
            ? wp_verify_nonce($_POST['nonce'], 'glory_gbn_nonce') 
            : true;

        // Rate limiting básico por IP (prevención de spam adicional)
        $clientIp = self::getClientIp();
        $rateLimitKey = 'gbn_form_rate_' . md5($clientIp);
        $lastSubmit = get_transient($rateLimitKey);
        
        if ($lastSubmit && (time() - $lastSubmit) < 5) {
            wp_send_json_error([
                'message' => 'Por favor espera unos segundos antes de enviar otro formulario.',
                'code' => 'rate_limited'
            ]);
            return;
        }

        // Validar honeypot anti-spam
        if (!self::validateHoneypot()) {
            // Log intento de spam pero responder con éxito para no dar pistas al bot
            if (class_exists(GloryLogger::class)) {
                GloryLogger::warning('Formulario GBN bloqueado por honeypot', [
                    'ip' => $clientIp,
                    'formId' => $_POST['formId'] ?? 'unknown'
                ]);
            }
            wp_send_json_success([
                'message' => '¡Formulario enviado con éxito!'
            ]);
            return;
        }

        // Extraer y sanitizar datos del formulario
        $formId = isset($_POST['formId']) ? sanitize_text_field($_POST['formId']) : 'sin-id';
        $formData = self::extractFormData($_POST);

        if (empty($formData)) {
            wp_send_json_error([
                'message' => 'No se recibieron datos del formulario.',
                'code' => 'empty_data'
            ]);
            return;
        }

        // Obtener configuración de email (puede venir del formulario o usar defaults)
        $emailConfig = self::getEmailConfig($_POST);

        // Formatear el contenido del email en HTML
        $htmlBody = self::formatEmailHtml($formId, $formData, $emailConfig);
        $subject = self::formatSubject($emailConfig['subject'], $formId, $formData);

        // Enviar email al administrador
        try {
            $sent = EmailUtility::sendToAdmins($subject, $htmlBody);

            if ($sent) {
                // Guardar rate limit
                set_transient($rateLimitKey, time(), 60);

                // Log éxito
                if (class_exists(GloryLogger::class)) {
                    GloryLogger::info('Formulario GBN enviado exitosamente', [
                        'formId' => $formId,
                        'fieldsCount' => count($formData)
                    ]);
                }

                wp_send_json_success([
                    'message' => $emailConfig['successMessage']
                ]);
            } else {
                if (class_exists(GloryLogger::class)) {
                    GloryLogger::error('Fallo al enviar formulario GBN por email', [
                        'formId' => $formId
                    ]);
                }

                wp_send_json_error([
                    'message' => $emailConfig['errorMessage'],
                    'code' => 'email_failed'
                ]);
            }
        } catch (\Exception $e) {
            if (class_exists(GloryLogger::class)) {
                GloryLogger::error('Excepción al procesar formulario GBN: ' . $e->getMessage(), [
                    'formId' => $formId,
                    'exception' => $e->getMessage()
                ]);
            }

            wp_send_json_error([
                'message' => $emailConfig['errorMessage'],
                'code' => 'exception',
                'debug' => defined('WP_DEBUG') && WP_DEBUG ? $e->getMessage() : null
            ]);
        }
    }

    /**
     * Valida el campo honeypot para detectar bots.
     * 
     * El honeypot es un campo oculto que los usuarios humanos nunca llenan,
     * pero los bots tienden a completar automáticamente.
     * 
     * @return bool True si es un envío legítimo (honeypot vacío), false si es bot
     */
    private static function validateHoneypot(): bool
    {
        // Nombres comunes de campos honeypot que usamos
        $honeypotFields = ['gbn_website', 'gbn_hp', 'website'];

        foreach ($honeypotFields as $field) {
            if (isset($_POST[$field]) && !empty(trim($_POST[$field]))) {
                return false; // Bot detectado
            }
        }

        return true;
    }

    /**
     * Extrae y sanitiza los datos del formulario.
     * 
     * Filtra campos internos (nonce, action, honeypot, etc.) y sanitiza
     * el resto de los datos enviados.
     * 
     * @param array $postData Datos $_POST completos
     * @return array Datos del formulario sanitizados
     */
    private static function extractFormData(array $postData): array
    {
        // Campos a ignorar (internos de WordPress/GBN o honeypot)
        $ignoredFields = [
            'action', 'nonce', 'formId', 'formConfig',
            'gbn_website', 'gbn_hp', 'website',
            '_emailSubject', '_successMessage', '_errorMessage', '_emailTo'
        ];

        $formData = [];

        foreach ($postData as $key => $value) {
            // Saltar campos internos
            if (in_array($key, $ignoredFields, true)) {
                continue;
            }

            // Saltar campos que empiezan con underscore (internos)
            if (strpos($key, '_') === 0) {
                continue;
            }

            // Sanitizar según el tipo de dato
            $sanitizedKey = sanitize_text_field($key);
            
            if (is_array($value)) {
                // Para arrays (ej: checkboxes múltiples)
                $formData[$sanitizedKey] = array_map('sanitize_text_field', $value);
            } else {
                // Usar wp_kses_post para permitir HTML básico en textareas
                $formData[$sanitizedKey] = wp_kses_post($value);
            }
        }

        return $formData;
    }

    /**
     * Obtiene la configuración de email desde el formulario o usa defaults.
     * 
     * @param array $postData Datos $_POST
     * @return array Configuración de email
     */
    private static function getEmailConfig(array $postData): array
    {
        return [
            'subject' => isset($postData['_emailSubject']) 
                ? sanitize_text_field($postData['_emailSubject']) 
                : 'Nuevo mensaje de formulario: {{formId}}',
            'successMessage' => isset($postData['_successMessage']) 
                ? sanitize_text_field($postData['_successMessage']) 
                : '¡Formulario enviado con éxito!',
            'errorMessage' => isset($postData['_errorMessage']) 
                ? sanitize_text_field($postData['_errorMessage']) 
                : 'Hubo un error al enviar el formulario. Por favor, inténtalo de nuevo.',
            'emailTo' => isset($postData['_emailTo']) 
                ? sanitize_email($postData['_emailTo']) 
                : get_option('admin_email')
        ];
    }

    /**
     * Formatea el contenido del email en HTML.
     * 
     * @param string $formId ID del formulario
     * @param array $formData Datos del formulario
     * @param array $emailConfig Configuración de email
     * @return string HTML del email
     */
    private static function formatEmailHtml(string $formId, array $formData, array $emailConfig): string
    {
        $siteName = get_bloginfo('name');
        $siteUrl = home_url();
        $date = wp_date('d/m/Y H:i:s');

        $html = '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif; background-color: #f5f5f5;">
    <table width="100%" cellpadding="0" cellspacing="0" style="max-width: 600px; margin: 20px auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <!-- Header -->
        <tr>
            <td style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px 40px; text-align: center;">
                <h1 style="margin: 0; color: #ffffff; font-size: 24px; font-weight: 600;">📬 Nuevo Mensaje de Formulario</h1>
                <p style="margin: 10px 0 0 0; color: rgba(255,255,255,0.9); font-size: 14px;">Formulario: <strong>' . esc_html($formId) . '</strong></p>
            </td>
        </tr>
        
        <!-- Metadata -->
        <tr>
            <td style="padding: 20px 40px; background-color: #f8f9fa; border-bottom: 1px solid #e9ecef;">
                <table width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="color: #6c757d; font-size: 13px;">
                            📅 <strong>Fecha:</strong> ' . esc_html($date) . '
                        </td>
                        <td style="color: #6c757d; font-size: 13px; text-align: right;">
                            🌐 <strong>Sitio:</strong> <a href="' . esc_url($siteUrl) . '" style="color: #667eea;">' . esc_html($siteName) . '</a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        
        <!-- Form Data -->
        <tr>
            <td style="padding: 30px 40px;">';

        foreach ($formData as $fieldName => $fieldValue) {
            // Convertir nombre del campo a formato legible
            $label = ucfirst(str_replace(['_', '-'], ' ', $fieldName));
            
            // Formatear valor (arrays se unen con comas)
            if (is_array($fieldValue)) {
                $displayValue = implode(', ', $fieldValue);
            } else {
                $displayValue = nl2br(esc_html($fieldValue));
            }

            $html .= '
                <div style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #e9ecef;">
                    <p style="margin: 0 0 5px 0; color: #6c757d; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">' . esc_html($label) . '</p>
                    <p style="margin: 0; color: #212529; font-size: 15px; line-height: 1.6;">' . $displayValue . '</p>
                </div>';
        }

        $html .= '
            </td>
        </tr>
        
        <!-- Footer -->
        <tr>
            <td style="padding: 20px 40px; background-color: #f8f9fa; text-align: center; border-top: 1px solid #e9ecef;">
                <p style="margin: 0; color: #6c757d; font-size: 12px;">
                    Este mensaje fue enviado desde el formulario de contacto de <a href="' . esc_url($siteUrl) . '" style="color: #667eea;">' . esc_html($siteName) . '</a>
                </p>
                <p style="margin: 10px 0 0 0; color: #adb5bd; font-size: 11px;">
                    Generado automáticamente por GBN Forms
                </p>
            </td>
        </tr>
    </table>
</body>
</html>';

        return $html;
    }

    /**
     * Formatea el asunto del email reemplazando placeholders.
     * 
     * Placeholders soportados:
     * - {{formId}}: ID del formulario
     * - {{siteName}}: Nombre del sitio
     * - {{date}}: Fecha actual
     * - {{fieldName}}: Valor de cualquier campo del formulario
     * 
     * @param string $subject Template del asunto
     * @param string $formId ID del formulario
     * @param array $formData Datos del formulario
     * @return string Asunto formateado
     */
    private static function formatSubject(string $subject, string $formId, array $formData): string
    {
        // Reemplazar placeholders básicos
        $subject = str_replace('{{formId}}', $formId, $subject);
        $subject = str_replace('{{siteName}}', get_bloginfo('name'), $subject);
        $subject = str_replace('{{date}}', wp_date('d/m/Y'), $subject);

        // Reemplazar placeholders de campos del formulario
        foreach ($formData as $key => $value) {
            if (!is_array($value)) {
                $subject = str_replace('{{' . $key . '}}', $value, $subject);
            }
        }

        return $subject;
    }

    /**
     * Obtiene la IP del cliente de forma segura.
     * 
     * @return string IP del cliente
     */
    private static function getClientIp(): string
    {
        $headers = [
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                // X-Forwarded-For puede contener múltiples IPs
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
    }
}
