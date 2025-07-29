<?php

namespace Glory\Handler;

use Glory\Components\ContentRender;
use Glory\Core\GloryLogger;

class PaginationAjaxHandler
{
    public function __construct()
    {
        add_action('wp_ajax_glory_pagination', [$this, 'handle_request']);
        add_action('wp_ajax_nopriv_glory_pagination', [$this, 'handle_request']);
    }

    public function handle_request(): void
    {
        check_ajax_referer('glory_pagination_nonce', 'nonce');

        $post_type = sanitize_text_field($_POST['post_type'] ?? '');
        $posts_per_page = absint($_POST['posts_per_page'] ?? 10);
        $paged = absint($_POST['paged'] ?? 1);
        $template_callback = sanitize_text_field($_POST['template_callback'] ?? '');
        $container_class = sanitize_text_field($_POST['container_class'] ?? 'glory-content-list');
        $item_class = sanitize_text_field($_POST['item_class'] ?? 'glory-content-item');

        if (empty($post_type) || empty($template_callback) || !is_callable($template_callback)) {
            wp_send_json_error(['message' => 'Parámetros inválidos.']);
            return;
        }

        set_query_var('paged', $paged);

        ob_start();
        ContentRender::print($post_type, [
            'publicacionesPorPagina' => $posts_per_page,
            'paginacion' => true,
            'plantillaCallback' => $template_callback,
            'claseContenedor' => $container_class,
            'claseItem' => $item_class,
            'argumentosConsulta' => [
                'paged' => $paged
            ]
        ]);
        $html = ob_get_clean();

        wp_send_json_success(['html' => $html]);
    }
}
