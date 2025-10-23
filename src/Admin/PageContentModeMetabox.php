<?php

namespace Glory\Admin;

use Glory\Manager\PageManager;

class PageContentModeMetabox
{
    private const META_MODO = '_glory_content_mode'; // 'code' | 'editor'
    private const META_GESTION = '_page_manager_managed';

    public function registerHooks(): void
    {
        add_action('add_meta_boxes', [$this, 'addMetabox']);
        add_action('save_post_page', [$this, 'save'], 10, 2);
    }

    public function addMetabox(): void
    {
        add_meta_box(
            'glory_content_mode',
            'Modo de contenido (Glory)',
            [$this, 'render'],
            'page',
            'side',
            'high'
        );
    }

    public function render(\WP_Post $post): void
    {
        $esGestionada = (bool) get_post_meta($post->ID, self::META_GESTION, true);
        if (!$esGestionada) {
            echo '<p>Esta página no está gestionada por Glory.</p>';
            return;
        }

        $modo = (string) get_post_meta($post->ID, self::META_MODO, true);
        if ($modo !== 'editor' && $modo !== 'code') {
            $modo = 'code';
        }

        wp_nonce_field('glory_content_mode_save', 'glory_content_mode_nonce');

        echo '<p style="margin:0 0 8px;">Elige cómo renderizar el contenido:</p>';
        echo '<label style="display:block;margin-bottom:6px;"><input type="radio" name="glory_content_mode" value="code"' . checked($modo, 'code', false) . '> Código (función del tema)</label>';
        echo '<label style="display:block;margin-bottom:6px;"><input type="radio" name="glory_content_mode" value="editor"' . checked($modo, 'editor', false) . '> Editor de WordPress</label>';

        echo '<div id="glory_copy_wrap" style="margin-top:8px;' . ($modo === 'editor' ? '' : 'display:none;') . '">';
        echo '<label style="display:block;"><input type="checkbox" name="glory_copy_now" value="1"> Copiar ahora el HTML del código al editor</label>';
        echo '<p style="color:#666;margin-top:6px;">Crea una copia editable del HTML actual. Tras esto, la sincronización con el código se detiene.</p>';
        echo '<button type="submit" class="button" name="glory_resync_now" value="1" style="margin-top:8px;">Volver a sincronizar HTML con el código</button>';
        echo '</div>';

        echo '<script>document.addEventListener("change",function(e){if(e.target && e.target.name==="glory_content_mode"){var w=document.getElementById("glory_copy_wrap");if(!w)return;w.style.display=e.target.value==="editor"?"block":"none";}});</script>';
    }

    public function save(int $postId, \WP_Post $post): void
    {
        if (!isset($_POST['glory_content_mode_nonce']) || !wp_verify_nonce($_POST['glory_content_mode_nonce'], 'glory_content_mode_save')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_page', $postId)) {
            return;
        }

        $esGestionada = (bool) get_post_meta($postId, self::META_GESTION, true);
        if (!$esGestionada) {
            return;
        }

        $modo = isset($_POST['glory_content_mode']) ? sanitize_text_field($_POST['glory_content_mode']) : 'code';
        if ($modo !== 'editor' && $modo !== 'code') {
            $modo = 'code';
        }
        update_post_meta($postId, self::META_MODO, $modo);

        $copiar = isset($_POST['glory_copy_now']) && $_POST['glory_copy_now'] === '1';
        $resync = isset($_POST['glory_resync_now']) && $_POST['glory_resync_now'] === '1';
        if ($modo === 'editor' && ($copiar || $resync)) {
            $slug = get_post_field('post_name', $postId);
            $handler = is_string($slug) ? PageManager::getHandlerPorSlug($slug) : null;
            if ($handler && function_exists($handler)) {
                ob_start();
                try {
                    call_user_func($handler);
                } catch (\Throwable $e) {
                    ob_end_clean();
                    return;
                }
                $html = ob_get_clean();
                if (is_string($html) && $html !== '') {
                    remove_action('save_post_page', [$this, 'save'], 10); // evitar loop
                    wp_update_post([
                        'ID' => $postId,
                        'post_content' => $html,
                    ]);
                    add_action('save_post_page', [$this, 'save'], 10, 2);
                    // Actualizar hash para permitir sincronización inteligente futura
                    $normalized = preg_replace('/\s+/', ' ', trim((string) $html));
                    update_post_meta($postId, '_glory_content_hash', hash('sha256', (string) $normalized));
                }
            }
        }
    }
}


