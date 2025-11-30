<?php

namespace Glory\Gbn\Ajax;

use Glory\Manager\PageManager;

class ContentHandler
{
    public static function saveOptions(): void
    {
        check_ajax_referer('glory_gbn_nonce', 'nonce');
        self::log('[saveOptions] INICIO');
        $pageId = isset($_POST['pageId']) ? absint($_POST['pageId']) : 0;
        $gbnId  = isset($_POST['gbnId']) ? sanitize_text_field($_POST['gbnId']) : '';
        $valuesRaw = isset($_POST['values']) ? wp_unslash($_POST['values']) : '{}';
        $values = json_decode((string) $valuesRaw, true);
        if (!$pageId || $gbnId === '' || !is_array($values)) {
            self::log('[saveOptions] Datos inválidos');
            wp_send_json_error(['message' => 'Datos inválidos']);
        }
        if (!current_user_can('edit_post', $pageId)) {
            self::log('[saveOptions] Sin permisos');
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
        self::log('[saveOptions] Guardado opts para ' . $gbnId . ' en page ' . $pageId);
        try {
            self::syncShortcodeAttributes($pageId, $gbnId, $sanitized);
        } catch (\Throwable $e) {
        }
        self::log('[saveOptions] OK');
        wp_send_json_success(['ok' => true]);
    }

    public static function previewBlock(): void
    {
        check_ajax_referer('glory_gbn_nonce', 'nonce');
        $pageId = isset($_POST['pageId']) ? absint($_POST['pageId']) : 0;
        $gbnId  = isset($_POST['gbnId']) ? sanitize_text_field($_POST['gbnId']) : '';
        $valuesRaw = isset($_POST['values']) ? wp_unslash($_POST['values']) : '{}';
        $values = json_decode((string) $valuesRaw, true);
        if (!$pageId || $gbnId === '' || !is_array($values)) {
            wp_send_json_error(['message' => 'Datos inválidos']);
        }
        if (!current_user_can('edit_post', $pageId)) {
            wp_send_json_error(['message' => 'Sin permisos']);
        }
        $post = get_post($pageId);
        if (!$post || empty($post->post_content)) {
            wp_send_json_error(['message' => 'Post vacío']);
        }
        $content = (string) $post->post_content;
        $shortcode = 'glory_split_content';
        $pattern = '/\[' . $shortcode . '([^\]]*)\]/i';
        if (!preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            wp_send_json_error(['message' => 'Shortcode no encontrado']);
        }
        $defaults = \FusionSC_GlorySplitContent::get_element_defaults();
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
            $args = array_merge($merged, $values);
            $args['__gbn_preview'] = 'yes';
            $_REQUEST['__gbn_page_id'] = $pageId;
            $html = do_shortcode('[' . $shortcode . ' ' . self::attrsToString($args) . ']');
            wp_send_json_success(['html' => $html, 'gbnId' => $gbnId]);
        }
        wp_send_json_error(['message' => 'Instancia no encontrada']);
    }

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
            if (!is_array($b)) { continue; }
            $id = isset($b['id']) ? sanitize_text_field((string) $b['id']) : '';
            if ($id === '') { continue; }
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

        $mode = method_exists(PageManager::class, 'getModoContenidoParaPagina')
            ? PageManager::getModoContenidoParaPagina($pageId)
            : 'code';

        if ($mode === 'code') {
            update_post_meta($pageId, '_glory_content_mode', 'editor');
            $mode = 'editor';
            self::log('[saveConfig] Mode switched from code to editor');
        }
        
        self::log('[saveConfig] PageID: ' . $pageId . ' Mode: ' . $mode);

        $manualEditDetected = false;
        $contentUpdated = false;

        if ($mode === 'editor') {
            $currentContent = (string) get_post_field('post_content', $pageId);
            $savedHash = (string) get_post_meta($pageId, '_glory_content_hash', true);
            $currentHash = $currentContent !== '' ? self::hashContenidoLocal($currentContent) : '';
            
            self::log("Hash Check - Saved: '$savedHash', Current: '$currentHash'");

            // Si el usuario editó manualmente, no sobreescribir y notificar
            if ($savedHash !== '' && $savedHash !== $currentHash) {
                $manualEditDetected = true;
                self::log("Manual edit detected! Overwriting enabled for GBN.");
            }
            
            // Volcar HTML baseline renderizado por el handler cuando exista
            $slug = (string) get_post_field('post_name', $pageId);
            $def  = method_exists(PageManager::class, 'getDefinicionPorSlug') ? PageManager::getDefinicionPorSlug($slug) : null;
            if (is_array($def) && !empty($def['funcion']) && method_exists(PageManager::class, 'renderHandlerParaCopiar')) {
                $html = PageManager::renderHandlerParaCopiar((string) $def['funcion']);
                if ($html !== '') {
                    // Procesar HTML para inyectar IDs y eliminar bloques borrados
                    $validIds = array_keys($configById);
                    self::log('Valid IDs count: ' . count($validIds));
                    self::log('Valid IDs sample: ' . print_r(array_slice($validIds, 0, 5), true));
                    
                    // Log client paths for debugging
                    $clientPaths = [];
                    foreach (array_slice($blocks, 0, 5) as $b) {
                        if (isset($b['clientPath'])) {
                            $clientPaths[] = $b['id'] . ': ' . $b['clientPath'];
                        }
                    }
                    self::log('Client Paths Sample: ' . print_r($clientPaths, true));

                    $html = self::processHtmlForPersistence($html, $validIds);

                    remove_filter('content_save_pre', 'wp_filter_post_kses');
                    wp_update_post(['ID' => $pageId, 'post_content' => $html]);
                    update_post_meta($pageId, '_glory_content_hash', self::hashContenidoLocal($html));
                    $contentUpdated = true;
                }
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
        if (!$pageId) { wp_send_json_error(['message' => 'Datos inválidos']); }
        if (!current_user_can('edit_post', $pageId)) { wp_send_json_error(['message' => 'Sin permisos']); }

        delete_post_meta($pageId, 'gbn_config');
        delete_post_meta($pageId, 'gbn_styles');

        $updatedContent = false;
        $mode = method_exists(PageManager::class, 'getModoContenidoParaPagina')
            ? PageManager::getModoContenidoParaPagina($pageId)
            : 'code';

        if ($mode === 'editor' && method_exists(PageManager::class, 'getDefinicionPorSlug')) {
            $slug = (string) get_post_field('post_name', $pageId);
            $def  = PageManager::getDefinicionPorSlug($slug);
            if (is_array($def) && !empty($def['funcion']) && method_exists(PageManager::class, 'renderHandlerParaCopiar')) {
                $html = PageManager::renderHandlerParaCopiar((string) $def['funcion']);
                if ($html !== '') {
                    remove_filter('content_save_pre', 'wp_filter_post_kses');
                    wp_update_post(['ID' => $pageId, 'post_content' => $html]);
                    update_post_meta($pageId, '_glory_content_hash', self::hashContenidoLocal($html));
                    $updatedContent = true;
                }
            }
        }

        wp_send_json_success(['ok' => true, 'mode' => $mode, 'contentUpdated' => $updatedContent]);
    }

    private static function hashContenidoLocal(string $content): string
    {
        $normalized = preg_replace('/\s+/', ' ', trim($content));
        return hash('sha256', (string) $normalized);
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
            if (!is_string($prop)) { continue; }
            $propKey = preg_replace('/[^a-zA-Z0-9\-]/', '', (string) $prop);
            if ($propKey === '') { continue; }
            if ($val === null || $val === '') { continue; }
            $out[$propKey] = is_string($val) ? wp_kses_post($val) : sanitize_text_field((string) $val);
        }
        return $out;
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

    private static function processHtmlForPersistence(string $html, array $validIds): string
    {
        if (trim($html) === '') {
            return '';
        }

        $dom = new \DOMDocument();
        // Suppress warnings for malformed HTML
        libxml_use_internal_errors(true);
        // Hack for UTF-8. Wrap in data-gbn-root to ensure consistent ID generation.
        $dom->loadHTML('<?xml encoding="utf-8" ?><body><div data-gbn-root>' . $html . '</div></body>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        
        // self::log('HTML Start: ' . substr($html, 0, 100));

        $xpath = new \DOMXPath($dom);
        // Find all elements that might be blocks.
        // Note: loadHTML lowercases attributes, so we must query for lowercase versions.
        $query = "//*[@glorydiv] | //*[@glorydivsecundario] | //*[@glorycontentrender] | //*[@glorytermrender] | //*[@gloryimage] | //*[@data-gbnprincipal] | //*[@data-gbnsecundario] | //*[@data-gbncontent] | //*[@data-gbn-term-list] | //*[@data-gbn-image]";

        $nodes = $xpath->query($query);
        $toRemove = [];
        
        // self::log('Nodes found: ' . $nodes->length);

        foreach ($nodes as $node) {
            // Generate ID
            $id = $node->getAttribute('data-gbn-id');
            if (!$id) {
                $path = self::computeDomPath($node);
                $id = self::generateId($node);
                $node->setAttribute('data-gbn-id', $id);
                // self::log("Generated ID: $id | Path: $path");
            }
            
            // self::log('Node ID: ' . $id . ' | Valid: ' . (in_array($id, $validIds) ? 'YES' : 'NO'));

            // Check if valid
            if (!in_array($id, $validIds)) {
                $toRemove[] = $node;
            }
        }
        
        // self::log('Nodes to remove: ' . count($toRemove));

        foreach ($toRemove as $node) {
            $node->parentNode->removeChild($node);
        }

        // Return HTML of body content
        $body = $dom->getElementsByTagName('body')->item(0);
        $output = '';
        foreach ($body->childNodes as $child) {
            $output .= $dom->saveHTML($child);
        }

        return $output;
    }

    private static function generateId(\DOMElement $node): string
    {
        $path = self::computeDomPath($node);
        $hash = self::hashString($path);
        return 'gbn-v3-' . base_convert((string)$hash, 10, 36);
    }

    private static function computeDomPath(\DOMElement $node): string
    {
        $segments = [];
        $curr = $node;
        while ($curr && $curr->nodeType === XML_ELEMENT_NODE && $curr->nodeName !== 'body') {
            // Stop at data-gbn-root
            if ($curr->hasAttribute('data-gbn-root')) {
                break;
            }
            $tag = strtolower($curr->nodeName);
            
            // Ignore 'main' tag to fix inconsistency between client (with main) and server (without main)
            if ($tag === 'main') {
                $curr = $curr->parentNode;
                continue;
            }

            $index = 0;
            $sibling = $curr;
            while ($sibling = $sibling->previousSibling) {
                if ($sibling->nodeType === XML_ELEMENT_NODE && $sibling->nodeName === $curr->nodeName) {
                    $index++;
                }
            }
            array_unshift($segments, $tag . ':' . $index);
            $curr = $curr->parentNode;
        }
        return implode('>', $segments);
    }

    private static function hashString(string $str): int
    {
        $hash = 0;
        $len = strlen($str);
        for ($i = 0; $i < $len; $i++) {
            $char = ord($str[$i]);
            $hash = (($hash << 5) - $hash) + $char;
            $hash = $hash & 0xFFFFFFFF; // Keep 32 bits (unsigned)
        }
        
        // Convert unsigned 32-bit to signed 32-bit to match JS bitwise operations
        if ($hash & 0x80000000) {
            $hash = -((~$hash & 0xFFFFFFFF) + 1);
        }
        
        return abs($hash);
    }

    private static function log(string $msg, $context = []): void
    {
        $file = dirname(__DIR__) . '/gbn.log';
        $date = date('Y-m-d H:i:s.u');
        $contextStr = !empty($context) ? ' | Contexto: ' . print_r($context, true) : '';
        $line = "[$date] [INFO] [ContentHandler] $msg$contextStr\n";
        file_put_contents($file, $line, FILE_APPEND);
    }
}


