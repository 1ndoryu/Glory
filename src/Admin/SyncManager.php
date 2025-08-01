<?php

namespace Glory\Admin;

use Glory\Core\GloryLogger;
use Glory\Services\DefaultContentSynchronizer;
use Glory\Manager\OpcionManager;
use Glory\Core\PageManager;

class SyncManager
{
    /** Controla la visibilidad del grupo "Glory Sync" en la barra de administración. */
    private static bool $showAdminBar = true;

    /** Controla la visibilidad del botón "Restablecer a Default". */
    private static bool $showResetButton = true;

    public static function setAdminBarVisible(bool $visible): void
    {
        self::$showAdminBar = $visible;
    }

    public static function setResetButtonVisible(bool $visible): void
    {
        self::$showResetButton = $visible;
    }

    public function registerHooks(): void
    {
        add_action('admin_bar_menu', [$this, 'addSyncButtons'], 999);
        add_action('init', [$this, 'handleSyncActions'], 20);
        add_action('admin_notices', [$this, 'showSyncNotice']);
    }

    public function addSyncButtons(\WP_Admin_Bar $wp_admin_bar): void
    {
        if (!current_user_can('manage_options') || !self::$showAdminBar) {
            return;
        }

        $wp_admin_bar->add_node([
            'id'    => 'glory_sync_group',
            'title' => 'Glory Sync',
            'href'  => '#'
        ]);

        $wp_admin_bar->add_node([
            'id'     => 'glory_force_sync',
            'parent' => 'glory_sync_group',
            'title'  => 'Sincronizar Todo',
            'href'   => add_query_arg('glory_action', 'sync'),
            'meta'   => [
                'title' => 'Sincroniza Opciones, Páginas y Contenido por Defecto desde el código a la base de datos.',
            ],
        ]);

        if (self::$showResetButton) {
            $wp_admin_bar->add_node([
                'id'     => 'glory_reset_default',
                'parent' => 'glory_sync_group',
                'title'  => 'Restablecer a Default',
                'href'   => add_query_arg('glory_action', 'reset'),
                'meta'   => [
                    'title' => 'Restablece el contenido modificado manualmente a su estado original definido en el código.',
                ],
            ]);
        }

        // <-- BOTÓN NUEVO AÑADIDO AQUÍ -->
        $wp_admin_bar->add_node([
            'id'     => 'glory_clear_cache',
            'parent' => 'glory_sync_group',
            'title'  => 'Borrar Caché de Glory',
            'href'   => add_query_arg('glory_action', 'clear_cache'),
            'meta'   => [
                'title' => 'Elimina toda la caché de contenido (transients) generada por Glory.',
            ],
        ]);
    }

    public function handleSyncActions(): void
    {
        if (!isset($_GET['glory_action']) || !current_user_can('manage_options')) {
            return;
        }

        $action = sanitize_key($_GET['glory_action']);
        $redirect_url = remove_query_arg('glory_action');

        if ($action === 'sync') {
            GloryLogger::info('Sincronización manual forzada por el usuario desde la barra de admin.');
            OpcionManager::sincronizarTodasLasOpciones();
            PageManager::procesarPaginasDefinidas();
            PageManager::reconciliarPaginasGestionadas();
            $sync = new DefaultContentSynchronizer();
            $sync->sincronizar();
            $redirect_url = add_query_arg('glory_sync_notice', 'sync_success', $redirect_url);

        } elseif ($action === 'reset') {
            GloryLogger::info('Restablecimiento a default forzado por el usuario desde la barra de admin.');
            $sync = new DefaultContentSynchronizer();
            $sync->restablecer();
            $redirect_url = add_query_arg('glory_sync_notice', 'reset_success', $redirect_url);
        
        // <-- LÓGICA NUEVA AÑADIDA AQUÍ -->
        } elseif ($action === 'clear_cache') {
            global $wpdb;
            $prefix = '_transient_glory_content_';
            $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                    $wpdb->esc_like($prefix) . '%'
                )
            );
            GloryLogger::info('Caché de Glory borrada manualmente por el usuario.');
            $redirect_url = add_query_arg('glory_sync_notice', 'cache_cleared', $redirect_url);
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
            // <-- MENSAJE NUEVO AÑADIDO AQUÍ -->
            } elseif ($notice_type === 'cache_cleared') {
                $message = '<strong>Caché de Glory borrada con éxito.</strong>';
            }

            if ($message) {
                echo '<div class="notice notice-success is-dismissible"><p>' . $message . '</p></div>';
            }
        }
    }
}