<?php
// Glory/src/Admin/SyncManager.php

namespace Glory\Admin;

use Glory\Core\GloryLogger;
use Glory\Services\DefaultContentSynchronizer;

class SyncManager
{
    public function registerHooks(): void
    {
        add_action('admin_bar_menu', [$this, 'addSyncButtons'], 999);
        // --- INICIO DE LA SOLUCIÓN ---
        // Se cambia la prioridad del hook de 10 (por defecto) a 20.
        // Esto asegura que se ejecute DESPUÉS de que se registren los tipos de post (que se enganchan en init:10).
        add_action('init', [$this, 'handleSyncActions'], 20);
        // --- FIN DE LA SOLUCIÓN ---
        add_action('admin_notices', [$this, 'showSyncNotice']);
    }

    public function addSyncButtons(\WP_Admin_Bar $wp_admin_bar): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $wp_admin_bar->add_node([
            'id'  => 'glory_sync_group',
            'title' => 'Glory Sync',
            'href' => '#',
        ]);

        $wp_admin_bar->add_node([
            'id'  => 'glory_force_sync',
            'parent' => 'glory_sync_group',
            'title' => 'Sincronizar',
            'href' => add_query_arg('glory_action', 'sync'),
            'meta' => [
                'title' => 'Sincroniza el contenido desde el código, actualizando cambios necesarios sin sobreescribir ediciones manuales.'
            ]
        ]);

        $wp_admin_bar->add_node([
            'id'  => 'glory_reset_default',
            'parent' => 'glory_sync_group',
            'title' => 'Restablecer a Default',
            'href' => add_query_arg('glory_action', 'reset'),
            'meta' => [
                'title' => 'Restablece el contenido modificado manualmente a su estado original definido en el código.'
            ]
        ]);
    }

    public function handleSyncActions(): void
    {
        if (!isset($_GET['glory_action']) || !current_user_can('manage_options')) {
            return;
        }

        $action = sanitize_key($_GET['glory_action']);
        $sync = new DefaultContentSynchronizer();
        $redirect_url = remove_query_arg('glory_action');

        if ($action === 'sync') {
            GloryLogger::info('Sincronización manual forzada por el usuario desde la barra de admin.');
            $sync->sincronizar();
            $redirect_url = add_query_arg('glory_sync_notice', 'sync_success', $redirect_url);
        } elseif ($action === 'reset') {
            GloryLogger::info('Restablecimiento a default forzado por el usuario desde la barra de admin.');
            $sync->restablecer();
            $redirect_url = add_query_arg('glory_sync_notice', 'reset_success', $redirect_url);
        }

        wp_safe_redirect($redirect_url);
        exit;
    }

    public function showSyncNotice(): void
    {
        if (isset($_GET['glory_sync_notice'])) {
            $notice_type = sanitize_key($_GET['glory_sync_notice']);
            $message = '';

            if ($notice_type === 'sync_success') {
                $message = '<strong>Sincronización de Glory completada con éxito.</strong>';
            } elseif ($notice_type === 'reset_success') {
                $message = '<strong>Contenido de Glory restablecido a default con éxito.</strong>';
            }

            if ($message) {
                echo '<div class="notice notice-success is-dismissible"><p>' . $message . '</p></div>';
            }
        }
    }
}
