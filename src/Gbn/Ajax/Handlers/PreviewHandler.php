<?php

namespace Glory\Gbn\Ajax\Handlers;

class PreviewHandler
{
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

    private static function attrsToString(array $attrs): string
    {
        $parts = [];
        foreach ($attrs as $k => $v) {
            $parts[] = $k . '="' . esc_attr((string) $v) . '"';
        }
        return implode(' ', $parts);
    }
}
