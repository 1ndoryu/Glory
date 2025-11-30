<?php

namespace Glory\Gbn\Ajax\Handlers;

use Glory\Gbn\Logger;

class OptionsHandler
{
    public static function saveOptions(): void
    {
        check_ajax_referer('glory_gbn_nonce', 'nonce');
        Logger::log('[saveOptions] INICIO');
        $pageId = isset($_POST['pageId']) ? absint($_POST['pageId']) : 0;
        $gbnId  = isset($_POST['gbnId']) ? sanitize_text_field($_POST['gbnId']) : '';
        $valuesRaw = isset($_POST['values']) ? wp_unslash($_POST['values']) : '{}';
        $values = json_decode((string) $valuesRaw, true);
        if (!$pageId || $gbnId === '' || !is_array($values)) {
            Logger::log('[saveOptions] Datos inválidos');
            wp_send_json_error(['message' => 'Datos inválidos']);
        }
        if (!current_user_can('edit_post', $pageId)) {
            Logger::log('[saveOptions] Sin permisos');
            wp_send_json_error(['message' => 'Sin permisos']);
        }
        $allowed = [
            'post_type', 'number_of_posts', 'query_args', 'include_post_ids', 'show_links', 'show_headers', 'auto_open_first_item', 'height',
            'list_direction', 'list_item_spacing', 'list_panel_width', 'list_scroll_enabled', 'list_padding_top', 'list_padding_bottom', 'list_padding_left', 'list_padding_right',
            'post_title_position', 'post_title_alignment', 'title_color', 'title_font_family', 'title_font_variant', 'title_font_size', 'title_line_height', 'title_letter_spacing', 'title_text_transform',
            'page_title_enabled', 'page_title_text', 'page_title_position', 'page_title_font_family', 'page_title_font_variant', 'page_title_font_size', 'page_title_color', 'page_title_alignment',
            'content_scroll_enabled', 'content_color', 'content_font_family', 'content_font_variant', 'content_font_size', 'content_line_height', 'content_letter_spacing', 'content_text_transform',
            'padding_top', 'padding_bottom', 'padding_left', 'padding_right',
            'content_padding_top', 'content_padding_bottom', 'content_padding_left', 'content_padding_right',
        ];
        $sanitized = [];
        foreach ($allowed as $k) {
            if (!array_key_exists($k, $values)) continue;
            $v = $values[$k];
            switch ($k) {
                case 'post_type':
                    $v = sanitize_key((string) $v);
                    break;
                case 'number_of_posts':
                    $v = absint($v);
                    break;
                case 'query_args':
                    if (json_decode($v, true) === null) $v = '';
                    break;
                case 'include_post_ids':
                    $v = preg_replace('/[^0-9,\s]/', '', (string) $v);
                    break;
                case 'show_links':
                    $v = in_array($v, ['yes','no'], true) ? $v : 'yes';
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
                case 'post_title_position':
                    $v = in_array($v, ['above','below'], true) ? $v : 'above';
                    break;
                case 'post_title_alignment':
                case 'page_title_alignment':
                    $v = in_array($v, ['left','center','right'], true) ? $v : 'left';
                    break;
                case 'page_title_enabled':
                    $v = in_array($v, ['yes','no'], true) ? $v : 'no';
                    break;
                case 'page_title_text':
                    $v = sanitize_text_field((string) $v);
                    break;
                case 'page_title_position':
                    $v = in_array($v, ['top','bottom'], true) ? $v : 'top';
                    break;
                case 'page_title_font_family':
                    $v = sanitize_text_field((string) $v);
                    break;
                case 'page_title_font_variant':
                    $v = sanitize_text_field((string) $v);
                    break;
                case 'page_title_font_size':
                case 'page_title_color':
                    $v = preg_replace('/[^#0-9a-fA-F]/', '', (string) $v);
                    break;
                default:
                    $v = sanitize_text_field((string) $v);
            }
            $sanitized[$k] = $v;
        }
        update_post_meta($pageId, 'gbn_opts_' . $gbnId, $sanitized);
        Logger::log('[saveOptions] Guardado opts para ' . $gbnId . ' en page ' . $pageId);
        try {
            self::syncShortcodeAttributes($pageId, $gbnId, $sanitized);
        } catch (\Throwable $e) {
        }
        Logger::log('[saveOptions] OK');
        wp_send_json_success(['ok' => true]);
    }

    private static function syncShortcodeAttributes(int $pageId, string $gbnId, array $values): void
    {
        $post = get_post($pageId);
        if (!$post || empty($post->post_content)) { return; }
        $content = (string) $post->post_content;
        $shortcode = 'glory_split_content';
        $pattern = '/\[' . $shortcode . '([^\]]*)\]/i';
        if (!preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) { return; }
        $defaults = \FusionSC_GlorySplitContent::get_element_defaults();
        $keysForHash = [
            'post_type', 'query_args', 'include_post_ids', 'include_post_ids_select',
        ];
        $normalize = function(array $attrs) use ($defaults): array {
            $a = $attrs;
            $alias = [
                'publicaciones_por_pagina' => 'number_of_posts',
                'argumentos_json'         => 'query_args',
                'post_ids'                => 'include_post_ids',
                'post_ids_select'         => 'include_post_ids_select',
                'auto_open_first'         => 'auto_open_first_item',
                'container_height'        => 'height',
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
            return $a;
        };
        foreach ($matches[1] as $idx => $match) {
            $attrStr = trim($match[0] ?? '');
            $attrs = shortcode_parse_atts($attrStr) ?: [];
            $attrs = $normalize($attrs);
            $merged = array_merge($defaults, $attrs);
            $postType = isset($merged['post_type']) ? sanitize_key($merged['post_type']) : 'post';
            $normArgs = array_intersect_key($merged, array_flip($keysForHash));
            $calc = substr(md5(wp_json_encode($normArgs) . '|' . $postType . '|' . (int) $pageId), 0, 12);
            if ($calc !== $gbnId) { continue; }
            $newAttrs = array_merge($attrs, $values);
            $newAttrs = $normalize($newAttrs);
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
            $fullMatch = $matches[0][$idx][0];
            $pos       = $matches[0][$idx][1];
            $len       = strlen($fullMatch);
            $content   = substr($content, 0, $pos) . $replacement . substr($content, $pos + $len);
            remove_filter('content_save_pre', 'wp_filter_post_kses');
            wp_update_post([ 'ID' => $pageId, 'post_content' => $content ]);
            return;
        }
    }
}
