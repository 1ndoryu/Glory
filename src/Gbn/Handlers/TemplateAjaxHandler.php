<?php

namespace Glory\Gbn\Handlers;

use Glory\Gbn\Services\TemplateService;

/**
 * TemplateAjaxHandler - Maneja las peticiones AJAX para templates Header/Footer.
 * 
 * @package Glory\Gbn\Handlers
 */
class TemplateAjaxHandler
{
    /**
     * Registra los handlers AJAX.
     */
    public static function register(): void
    {
        // Header
        add_action('wp_ajax_gbn_save_header_template', [self::class, 'saveHeader']);
        add_action('wp_ajax_gbn_delete_header_template', [self::class, 'deleteHeader']);
        add_action('wp_ajax_gbn_get_header_template', [self::class, 'getHeader']);

        // Footer
        add_action('wp_ajax_gbn_save_footer_template', [self::class, 'saveFooter']);
        add_action('wp_ajax_gbn_delete_footer_template', [self::class, 'deleteFooter']);
        add_action('wp_ajax_gbn_get_footer_template', [self::class, 'getFooter']);
    }

    /**
     * Verifica el nonce de seguridad.
     * 
     * @return bool
     */
    private static function verifyNonce(): bool
    {
        $nonce = $_POST['nonce'] ?? '';
        return wp_verify_nonce($nonce, 'gbn_template_nonce');
    }

    /**
     * Verifica que el usuario tenga permisos.
     * 
     * @return bool
     */
    private static function userCanEdit(): bool
    {
        return current_user_can('edit_theme_options');
    }

    /**
     * Guarda el template del header.
     */
    public static function saveHeader(): void
    {
        if (!self::verifyNonce()) {
            wp_send_json_error('Invalid nonce', 403);
            return;
        }

        if (!self::userCanEdit()) {
            wp_send_json_error('Permission denied', 403);
            return;
        }

        $html = isset($_POST['html']) ? wp_kses_post($_POST['html']) : '';
        $configJson = isset($_POST['config']) ? $_POST['config'] : '{}';
        $config = json_decode(stripslashes($configJson), true) ?: [];

        // Sanitizar HTML pero preservar atributos de GBN
        $html = self::sanitizeTemplateHtml($html);

        $saved = TemplateService::saveHeader($html, $config);

        if ($saved) {
            wp_send_json_success(['message' => 'Header saved successfully']);
        } else {
            wp_send_json_error('Failed to save header');
        }
    }

    /**
     * Elimina el template del header.
     */
    public static function deleteHeader(): void
    {
        if (!self::verifyNonce()) {
            wp_send_json_error('Invalid nonce', 403);
            return;
        }

        if (!self::userCanEdit()) {
            wp_send_json_error('Permission denied', 403);
            return;
        }

        $deleted = TemplateService::deleteHeader();

        if ($deleted) {
            wp_send_json_success(['message' => 'Header deleted successfully']);
        } else {
            wp_send_json_error('Failed to delete header');
        }
    }

    /**
     * Obtiene el template del header.
     */
    public static function getHeader(): void
    {
        if (!self::userCanEdit()) {
            wp_send_json_error('Permission denied', 403);
            return;
        }

        $header = TemplateService::getHeader();
        
        if (empty($header['html'])) {
            $header['html'] = TemplateService::getDefaultHeaderTemplate();
            $header['isDefault'] = true;
        } else {
            $header['isDefault'] = false;
        }

        wp_send_json_success($header);
    }

    /**
     * Guarda el template del footer.
     */
    public static function saveFooter(): void
    {
        if (!self::verifyNonce()) {
            wp_send_json_error('Invalid nonce', 403);
            return;
        }

        if (!self::userCanEdit()) {
            wp_send_json_error('Permission denied', 403);
            return;
        }

        $html = isset($_POST['html']) ? wp_kses_post($_POST['html']) : '';
        $configJson = isset($_POST['config']) ? $_POST['config'] : '{}';
        $config = json_decode(stripslashes($configJson), true) ?: [];

        $html = self::sanitizeTemplateHtml($html);

        $saved = TemplateService::saveFooter($html, $config);

        if ($saved) {
            wp_send_json_success(['message' => 'Footer saved successfully']);
        } else {
            wp_send_json_error('Failed to save footer');
        }
    }

    /**
     * Elimina el template del footer.
     */
    public static function deleteFooter(): void
    {
        if (!self::verifyNonce()) {
            wp_send_json_error('Invalid nonce', 403);
            return;
        }

        if (!self::userCanEdit()) {
            wp_send_json_error('Permission denied', 403);
            return;
        }

        $deleted = TemplateService::deleteFooter();

        if ($deleted) {
            wp_send_json_success(['message' => 'Footer deleted successfully']);
        } else {
            wp_send_json_error('Failed to delete footer');
        }
    }

    /**
     * Obtiene el template del footer.
     */
    public static function getFooter(): void
    {
        if (!self::userCanEdit()) {
            wp_send_json_error('Permission denied', 403);
            return;
        }

        $footer = TemplateService::getFooter();
        
        if (empty($footer['html'])) {
            $footer['html'] = TemplateService::getDefaultFooterTemplate();
            $footer['isDefault'] = true;
        } else {
            $footer['isDefault'] = false;
        }

        wp_send_json_success($footer);
    }

    /**
     * Sanitiza el HTML del template preservando atributos GBN.
     * 
     * @param string $html
     * @return string
     */
    private static function sanitizeTemplateHtml(string $html): string
    {
        // Permitir atributos GBN personalizados
        add_filter('wp_kses_allowed_html', function($tags) {
            $gbnAttributes = [
                'gloryheader' => true,
                'gloryfooter' => true,
                'glorylogo' => true,
                'glorymenu' => true,
                'glorymenuitem' => true,
                'data-gbn-id' => true,
                'data-gbn-role' => true,
                'data-gbn-config' => true,
            ];

            foreach ($tags as $tag => $attrs) {
                if (is_array($attrs)) {
                    $tags[$tag] = array_merge($attrs, $gbnAttributes);
                }
            }

            return $tags;
        }, 10, 1);

        // Limpiar atributos de edición que no deben persistir
        $html = preg_replace('/\s*data-gbn-schema="[^"]*"/i', '', $html);
        $html = preg_replace('/\s*contenteditable="[^"]*"/i', '', $html);
        $html = preg_replace('/\s*class="([^"]*\s)?gbn-block-active(\s[^"]*)?"/', ' class="$1$2"', $html);

        return trim($html);
    }
}
