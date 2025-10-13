<?php

namespace Glory\Gbn;

class GbnAjaxHandler
{
    public static function register(): void
    {
        add_action('wp_ajax_gbn_save_order', [self::class, 'saveOrder']);
        add_action('wp_ajax_gbn_save_options', [self::class, 'saveOptions']);
        add_action('wp_ajax_gbn_preview_block', [self::class, 'previewBlock']);
    }

    public static function saveOrder(): void
    {
        check_ajax_referer('glory_gbn_nonce', 'nonce');
        $pageId = isset($_POST['pageId']) ? absint($_POST['pageId']) : 0;
        $gbnId  = isset($_POST['gbnId']) ? sanitize_text_field($_POST['gbnId']) : '';
        $idsRaw = isset($_POST['postIds']) ? wp_unslash($_POST['postIds']) : '[]';
        $ids = json_decode((string) $idsRaw, true);
        if (!$pageId || $gbnId === '' || !is_array($ids)) {
            wp_send_json_error(['message' => 'Datos inválidos']);
        }
        if (!current_user_can('edit_post', $pageId)) {
            wp_send_json_error(['message' => 'Sin permisos']);
        }
        $ids = array_values(array_filter(array_map('absint', $ids)));
        update_post_meta($pageId, 'gbn_order_' . $gbnId, $ids);
        wp_send_json_success(['ok' => true]);
    }

    public static function saveOptions(): void
    {
        check_ajax_referer('glory_gbn_nonce', 'nonce');
        error_log('[GBN][saveOptions] INICIO');
        $pageId = isset($_POST['pageId']) ? absint($_POST['pageId']) : 0;
        $gbnId  = isset($_POST['gbnId']) ? sanitize_text_field($_POST['gbnId']) : '';
        $valuesRaw = isset($_POST['values']) ? wp_unslash($_POST['values']) : '{}';
        $values = json_decode((string) $valuesRaw, true);
        if (!$pageId || $gbnId === '' || !is_array($values)) {
            error_log('[GBN][saveOptions] Datos inválidos');
            wp_send_json_error(['message' => 'Datos inválidos']);
        }
        if (!current_user_can('edit_post', $pageId)) {
            error_log('[GBN][saveOptions] Sin permisos');
            wp_send_json_error(['message' => 'Sin permisos']);
        }
        // Whitelist completo de opciones permitidas
        $allowed = [
            // General
            'post_type', 'number_of_posts', 'query_args', 'include_post_ids', 'auto_open_first_item', 'height',
            // Lista
            'list_direction', 'list_item_spacing', 'list_panel_width', 'list_scroll_enabled', 'list_padding_top', 'list_padding_bottom', 'list_padding_left', 'list_padding_right',
            // Títulos
            'title_color', 'title_font_family', 'title_font_variant', 'title_font_size', 'title_line_height', 'title_letter_spacing', 'title_text_transform',
            // Contenido
            'content_scroll_enabled', 'content_color', 'content_font_family', 'content_font_variant', 'content_font_size', 'content_line_height', 'content_letter_spacing', 'content_text_transform',
            // Padding contenedor
            'padding_top', 'padding_bottom', 'padding_left', 'padding_right',
            // Padding contenido
            'content_padding_top', 'content_padding_bottom', 'content_padding_left', 'content_padding_right',
        ];
        $sanitized = [];
        foreach ($allowed as $k) {
            if (!array_key_exists($k, $values)) continue;
            $v = $values[$k];
            // Sanitización específica por campo
            switch ($k) {
                case 'post_type':
                    $v = sanitize_key((string) $v);
                    break;
                case 'number_of_posts':
                    $v = absint($v);
                    break;
                case 'query_args':
                    // Validar que sea JSON válido
                    if (json_decode($v, true) === null) $v = '';
                    break;
                case 'include_post_ids':
                    $v = preg_replace('/[^0-9,\s]/', '', (string) $v);
                    break;
                case 'auto_open_first_item':
                case 'list_scroll_enabled':
                case 'content_scroll_enabled':
                    $v = in_array($v, ['yes','no'], true) ? $v : 'no';
                    break;
                case 'list_direction':
                    $v = in_array($v, ['vertical','horizontal'], true) ? $v : 'vertical';
                    break;
                case 'list_panel_width':
                    $v = preg_replace('/[^0-9%\.]/', '', (string) $v);
                    break;
                case 'list_item_spacing':
                    $v = preg_replace('/[^0-9px\s]/', '', (string) $v);
                    break;
                case 'title_color':
                case 'content_color':
                    $v = preg_replace('/[^#0-9a-fA-F]/', '', (string) $v);
                    break;
                case 'title_font_family':
                case 'content_font_family':
                    $v = sanitize_text_field((string) $v);
                    break;
                case 'title_font_size':
                case 'title_line_height':
                case 'title_letter_spacing':
                case 'content_font_size':
                case 'content_line_height':
                case 'content_letter_spacing':
                    $v = preg_replace('/[^0-9px\s]/', '', (string) $v);
                    break;
                case 'title_font_variant':
                case 'content_font_variant':
                case 'title_text_transform':
                case 'content_text_transform':
                    $v = sanitize_text_field((string) $v);
                    break;
                case 'height':
                    $v = preg_replace('/[^0-9%\s]/', '', (string) $v);
                    break;
                case 'padding_top':
                case 'padding_bottom':
                case 'padding_left':
                case 'padding_right':
                case 'content_padding_top':
                case 'content_padding_bottom':
                case 'content_padding_left':
                case 'content_padding_right':
                case 'list_padding_top':
                case 'list_padding_bottom':
                case 'list_padding_left':
                case 'list_padding_right':
                    $v = preg_replace('/[^0-9px\s]/', '', (string) $v);
                    break;
                default:
                    $v = sanitize_text_field((string) $v);
            }
            $sanitized[$k] = $v;
        }
        update_post_meta($pageId, 'gbn_opts_' . $gbnId, $sanitized);
        error_log('[GBN][saveOptions] Guardado opts para ' . $gbnId . ' en page ' . $pageId);

        // Sincronizar atributos del shortcode de Avada en el contenido del post
        try {
            self::syncShortcodeAttributes($pageId, $gbnId, $sanitized);
        } catch (\Throwable $e) {
            // Silencioso: no romper guardado si la sync falla
        }
        error_log('[GBN][saveOptions] OK');
        wp_send_json_success(['ok' => true]);
    }

    /**
     * Renderiza el bloque completo glory_split_content con overrides sin persistir (vista previa).
     */
    public static function previewBlock(): void
    {
        check_ajax_referer('glory_gbn_nonce', 'nonce');
        error_log('[GBN][previewBlock] INICIO');
        $pageId = isset($_POST['pageId']) ? absint($_POST['pageId']) : 0;
        $gbnId  = isset($_POST['gbnId']) ? sanitize_text_field($_POST['gbnId']) : '';
        $valuesRaw = isset($_POST['values']) ? wp_unslash($_POST['values']) : '{}';
        $values = json_decode((string) $valuesRaw, true);
        if (!$pageId || $gbnId === '' || !is_array($values)) {
            error_log('[GBN][previewBlock] Datos inválidos');
            wp_send_json_error(['message' => 'Datos inválidos']);
        }
        if (!current_user_can('edit_post', $pageId)) {
            error_log('[GBN][previewBlock] Sin permisos');
            wp_send_json_error(['message' => 'Sin permisos']);
        }

        // Tomar el contenido del post y reemplazar solo el shortcode objetivo con los overrides recibidos
        $post = get_post($pageId);
        if (!$post || empty($post->post_content)) {
            wp_send_json_error(['message' => 'Post vacío']);
        }

        $content = (string) $post->post_content;
        $shortcode = 'glory_split_content';
        $pattern = '/\[' . $shortcode . '([^\]]*)\]/i';
        if (!preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            error_log('[GBN][previewBlock] Shortcode no encontrado');
            wp_send_json_error(['message' => 'Shortcode no encontrado']);
        }

        $defaults = \FusionSC_GlorySplitContent::get_element_defaults();
        // Alinear con getGbnData(): usar solo identidad de contenido
        $keysForHash = [
            'post_type', 'query_args', 'include_post_ids', 'include_post_ids_select',
        ];

        foreach ($matches[1] as $idx => $match) {
            $attrStr = trim($match[0] ?? '');
            $attrs = shortcode_parse_atts($attrStr) ?: [];
            $merged = array_merge($defaults, $attrs);
            $postType = isset($merged['post_type']) ? sanitize_key((string) $merged['post_type']) : 'post';
            $normArgs = array_intersect_key($merged, array_flip($keysForHash));
            $calc = substr(md5(wp_json_encode($normArgs) . '|' . $postType . '|' . (int) $pageId), 0, 12);
            if ($calc !== $gbnId) { continue; }

            // Preparar HTML renderizado con overrides: invocar directamente el render con args fusionados
            $args = array_merge($merged, $values);
            $args['__gbn_preview'] = 'yes';
            // Render directo del shortcode
            // Forzar pageId correcto durante el render para consistencia de IDs/estilos
            $_REQUEST['__gbn_page_id'] = $pageId;
            $html = do_shortcode('[' . $shortcode . ' ' . self::attrsToString($args) . ']');
            error_log('[GBN][previewBlock] OK para ' . $gbnId);
            wp_send_json_success(['html' => $html, 'gbnId' => $gbnId]);
        }
        error_log('[GBN][previewBlock] Instancia no encontrada');
        wp_send_json_error(['message' => 'Instancia no encontrada']);
    }

    private static function attrsToString(array $attrs): string
    {
        $parts = [];
        foreach ($attrs as $k => $v) {
            $parts[] = $k . '="' . esc_attr((string) $v) . '"';
        }
        return implode(' ', $parts);
    }

    private static function syncShortcodeAttributes(int $pageId, string $gbnId, array $values): void
    {
        $post = get_post($pageId);
        if (!$post || empty($post->post_content)) { return; }

        $content = (string) $post->post_content;
        $shortcode = 'glory_split_content';

        // Buscar todas las ocurrencias del shortcode
        $pattern = '/\[' . $shortcode . '([^\]]*)\]/i';
        if (!preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) { return; }

        $defaults = \FusionSC_GlorySplitContent::get_element_defaults();
        // Debe coincidir EXACTAMENTE con el cálculo usado para el ID en getGbnData()/previewBlock
        $keysForHash = [
            'post_type', 'query_args', 'include_post_ids', 'include_post_ids_select',
        ];

        // Normalizador de atributos: convierte alias previos (Avada inicial) a claves canónicas del shortcode
        $normalize = function(array $attrs) use ($defaults): array {
            $a = $attrs;
            $alias = [
                // General
                'publicaciones_por_pagina' => 'number_of_posts',
                'argumentos_json'         => 'query_args',
                'post_ids'                => 'include_post_ids',
                'post_ids_select'         => 'include_post_ids_select',
                'auto_open_first'         => 'auto_open_first_item',
                'container_height'        => 'height',
                // List/Titles
                'list_gap'                => 'list_item_spacing',
                'titles_color'            => 'title_color',
                'titles_font_family'      => 'title_font_family',
                'titles_font_size'        => 'title_font_size',
            ];
            foreach ($alias as $old => $new) {
                if (array_key_exists($old, $a) && !array_key_exists($new, $a)) {
                    $a[$new] = $a[$old];
                }
                unset($a[$old]);
            }
            // Filtrar a sólo claves conocidas + conservar otras para no romper, pero priorizar defaults
            return $a;
        };

        foreach ($matches[1] as $idx => $match) {
            $attrStr = trim($match[0] ?? '');
            $attrs = shortcode_parse_atts($attrStr) ?: [];
            // Normalizar alias a canónicos
            $attrs = $normalize($attrs);
            // Fusionar con defaults para calcular el mismo hash
            $merged = array_merge($defaults, $attrs);
            $postType = isset($merged['post_type']) ? sanitize_key($merged['post_type']) : 'post';
            $normArgs = array_intersect_key($merged, array_flip($keysForHash));
            $calc = substr(md5(wp_json_encode($normArgs) . '|' . $postType . '|' . (int) $pageId), 0, 12);
            if ($calc !== $gbnId) { continue; }

            // Encontramos el shortcode correcto: reconstruir atributos con los values
            $newAttrs = array_merge($attrs, $values);
            // Quitar cualquier alias obsoleto por si viene en $values
            $newAttrs = $normalize($newAttrs);
            // Construir string de atributos (orden estable: keys de $attrs primero, luego el resto)
            $seen = [];
            $parts = [];
            foreach ($attrs as $k => $v) {
                $seen[$k] = true;
                $parts[] = $k . '="' . esc_attr((string) ($newAttrs[$k] ?? '')) . '"';
            }
            foreach ($newAttrs as $k => $v) {
                if (isset($seen[$k])) continue;
                $parts[] = $k . '="' . esc_attr((string) $v) . '"';
            }
            $replacement = '[' . $shortcode . (count($parts) ? ' ' . implode(' ', $parts) : '') . ']';

            // Reemplazar solo esta ocurrencia
            $fullMatch = $matches[0][$idx][0];
            $pos       = $matches[0][$idx][1];
            $len       = strlen($fullMatch);
            $content   = substr($content, 0, $pos) . $replacement . substr($content, $pos + $len);
            // Guardar y salir (una sola instancia)
            remove_filter('content_save_pre', 'wp_filter_post_kses'); // evitar filtrado excesivo
            wp_update_post([ 'ID' => $pageId, 'post_content' => $content ]);
            return;
        }
    }
}
