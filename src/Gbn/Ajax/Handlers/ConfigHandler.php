<?php

namespace Glory\Gbn\Ajax\Handlers;

use Glory\Manager\PageManager;
use Glory\Gbn\Logger;
use Glory\Gbn\Ajax\Services\DomProcessor;

class ConfigHandler
{
    public static function saveConfig(): void
    {
        check_ajax_referer('glory_gbn_nonce', 'nonce');
        $pageId = isset($_POST['pageId']) ? absint($_POST['pageId']) : 0;
        $blocksRaw = isset($_POST['blocks']) ? wp_unslash($_POST['blocks']) : '[]';
        $blocks = json_decode((string) $blocksRaw, true);
        if (!$pageId || !is_array($blocks)) {
            wp_send_json_error(['message' => 'Datos inválidos']);
        }
        if (!current_user_can('edit_post', $pageId)) {
            wp_send_json_error(['message' => 'Sin permisos']);
        }

        $configById = [];
        $stylesById = [];

        foreach ($blocks as $b) {
            if (!is_array($b)) {
                continue;
            }
            $id = isset($b['id']) ? sanitize_text_field((string) $b['id']) : '';
            if ($id === '') {
                continue;
            }
            $role = isset($b['role']) ? sanitize_key((string) $b['role']) : 'block';
            $order = isset($b['order']) ? absint($b['order']) : 0;
            $children = isset($b['children']) && is_array($b['children']) ? $b['children'] : [];

            $config = [];
            if (isset($b['config']) && is_array($b['config'])) {
                $config = self::sanitizeMixedArray($b['config']);
            }

            $styles = [];
            if (isset($b['styles']) && is_array($b['styles'])) {
                $styles = self::sanitizeCssMap($b['styles']);
            }

            $configById[$id] = [
                'role' => $role,
                'order' => $order,
                'config' => $config,
                'children' => $children,
                'clientPath' => isset($b['domPath']) ? $b['domPath'] : '',
            ];

            if (!empty($styles)) {
                $stylesById[$id] = $styles;
            }
        }

        update_post_meta($pageId, 'gbn_config', $configById);
        update_post_meta($pageId, 'gbn_styles', $stylesById);

        // Guardar CSS Responsive generado por el frontend
        $responsiveCss = isset($_POST['responsiveCss']) ? wp_unslash($_POST['responsiveCss']) : '';
        // Sanitize CSS (básico, permitir media queries y selectores)
        // wp_strip_all_tags es demasiado agresivo para CSS, pero wp_kses no permite style tags.
        // Como es CSS puro, lo guardamos tal cual pero asegurando que venga de un usuario con permisos (ya verificado arriba).
        update_post_meta($pageId, 'gbn_responsive_css', $responsiveCss);

        $mode = method_exists(PageManager::class, 'getModoContenidoParaPagina')
            ? PageManager::getModoContenidoParaPagina($pageId)
            : 'code';

        if ($mode === 'code') {
            update_post_meta($pageId, '_glory_content_mode', 'editor');
            $mode = 'editor';
            Logger::log('[saveConfig] Mode switched from code to editor');
        }

        Logger::log('[saveConfig] PageID: ' . $pageId . ' Mode: ' . $mode);

        $manualEditDetected = false;
        $contentUpdated = false;

        if ($mode === 'editor') {
            $currentContent = (string) get_post_field('post_content', $pageId);
            $savedHash = (string) get_post_meta($pageId, '_glory_content_hash', true);
            $currentHash = $currentContent !== '' ? DomProcessor::hashContenidoLocal($currentContent) : '';

            Logger::log("Hash Check - Saved: '$savedHash', Current: '$currentHash'");

            // Si el usuario editó manualmente, no sobreescribir y notificar
            if ($savedHash !== '' && $savedHash !== $currentHash) {
                $manualEditDetected = true;
                Logger::log("Manual edit detected! Overwriting enabled for GBN.");
            }

            // Volcar HTML baseline renderizado por el handler cuando exista
            // Volcar HTML
            $html = '';

            // 1. Preferir HTML enviado por el cliente (WYSIWYG real)
            if (isset($_POST['htmlContent'])) {
                $html = wp_unslash($_POST['htmlContent']);
                // Limpieza básica de seguridad (pero permitiendo estructura)
                // DomProcessor::processHtmlForPersistence se encargará de sanitizar atributos y normalizar
            }
            // 2. Fallback: Regenerar desde código (Solo si no hay input del cliente)
            else {
                $slug = (string) get_post_field('post_name', $pageId);
                $def  = method_exists(PageManager::class, 'getDefinicionPorSlug') ? PageManager::getDefinicionPorSlug($slug) : null;
                if (is_array($def) && !empty($def['funcion']) && method_exists(PageManager::class, 'renderHandlerParaCopiar')) {
                    $html = PageManager::renderHandlerParaCopiar((string) $def['funcion']);
                }
            }

            if ($html !== '') {
                // Procesar HTML para inyectar IDs, eliminar bloques borrados y reordenar
                Logger::log('Config items count: ' . count($configById));

                // Si viene del cliente, ya tiene IDs y estructura, pero necesitamos limpiar basura del editor
                // y asegurar integridad con la config.
                $html = DomProcessor::processHtmlForPersistence($html, $configById);

                remove_filter('content_save_pre', 'wp_filter_post_kses');
                $updateResult = wp_update_post(['ID' => $pageId, 'post_content' => $html]);

                // IMPORTANTE: No actualizar el hash (o borrarlo) para que PageManager
                // detecte que el contenido ha sido "editado manualmente" (por GBN) y no
                // lo sobrescriba automáticamente con el contenido del código.
                delete_post_meta($pageId, '_glory_content_hash');

                $contentUpdated = true;
            }
        }

        wp_send_json_success([
            'ok' => true,
            'saved' => [
                'config' => count($configById),
                'styles' => count($stylesById),
            ],
            'mode' => $mode,
            'manualEditDetected' => $manualEditDetected,
            'contentUpdated' => $contentUpdated,
        ]);
    }

    public static function restorePage(): void
    {
        check_ajax_referer('glory_gbn_nonce', 'nonce');
        $pageId = isset($_POST['pageId']) ? absint($_POST['pageId']) : 0;
        if (!$pageId) {
            wp_send_json_error(['message' => 'Datos inválidos']);
        }
        if (!current_user_can('edit_post', $pageId)) {
            wp_send_json_error(['message' => 'Sin permisos']);
        }

        // 1. Limpiar metadatos de GBN
        delete_post_meta($pageId, 'gbn_config');
        delete_post_meta($pageId, 'gbn_styles');

        // 2. Resetear modo a 'code'
        update_post_meta($pageId, '_glory_content_mode', 'code');

        $updatedContent = false;

        // 3. Regenerar contenido desde el código base si es posible
        if (method_exists(PageManager::class, 'getDefinicionPorSlug')) {
            $slug = (string) get_post_field('post_name', $pageId);
            $def  = PageManager::getDefinicionPorSlug($slug);
            if (is_array($def) && !empty($def['funcion']) && method_exists(PageManager::class, 'renderHandlerParaCopiar')) {
                $html = PageManager::renderHandlerParaCopiar((string) $def['funcion']);

                // Aunque esté vacío, si es lo que devuelve el código, eso es lo que debe haber.
                // Pero generalmente evitamos borrar contenido si algo falla, así que validamos no vacío o explícito.
                // En este caso, "Restaurar" implica volver a lo que diga el código.

                remove_filter('content_save_pre', 'wp_filter_post_kses');
                wp_update_post(['ID' => $pageId, 'post_content' => $html]);
                update_post_meta($pageId, '_glory_content_hash', DomProcessor::hashContenidoLocal($html));
                $updatedContent = true;
                Logger::log('[restorePage] Contenido restaurado desde código para page ' . $pageId);
            }
        }

        wp_send_json_success(['ok' => true, 'mode' => 'code', 'contentUpdated' => $updatedContent]);
    }

    private static function sanitizeMixedArray(array $input): array
    {
        $out = [];
        foreach ($input as $k => $v) {
            $key = is_string($k) ? sanitize_key($k) : $k;
            if (is_array($v)) {
                $out[$key] = self::sanitizeMixedArray($v);
            } elseif (is_bool($v) || is_int($v) || is_float($v)) {
                $out[$key] = $v;
            } elseif ($v === null) {
                $out[$key] = null;
            } else {
                $out[$key] = sanitize_text_field((string) $v);
            }
        }
        return $out;
    }

    private static function sanitizeCssMap(array $styles): array
    {
        $out = [];
        foreach ($styles as $prop => $val) {
            if (!is_string($prop)) {
                continue;
            }
            $propKey = preg_replace('/[^a-zA-Z0-9\-]/', '', (string) $prop);
            if ($propKey === '') {
                continue;
            }
            if ($val === null || $val === '') {
                continue;
            }
            $out[$propKey] = is_string($val) ? wp_kses_post($val) : sanitize_text_field((string) $val);
        }
        return $out;
    }
}
