<?php

namespace Glory\Admin;

use Glory\Admin\PanelDataProvider;
use Glory\Admin\OpcionPanelSaver;
use Glory\Admin\PanelRenderer;
use Glory\Manager\AssetManager;

class OpcionPanelController
{
    private const MENU_SLUG = 'glory-opciones';
    private string $hookName;

    public function registerHooks(): void
    {
        add_action('admin_menu', [$this, 'agregarPaginaOpciones']);
    }

    public function agregarPaginaOpciones(): void
    {
        $this->hookName = add_menu_page(
            'Theme Options',
            'Theme Options',
            'manage_options',
            self::MENU_SLUG,
            [$this, 'gestionarYRenderizarPagina'],
            'dashicons-admin-generic',
            60
        );

        add_action('load-' . $this->hookName, [$this, 'enqueuePanelAssets']);
    }

    public function enqueuePanelAssets(): void
    {
        // Estilos de WP necesarios para el panel
        wp_enqueue_style('wp-color-picker');

        // Biblioteca de medios
        wp_enqueue_media();

        // Registrar assets del panel mediante AssetManager para que solo se encolen en admin
        AssetManager::define(
            'script',
            'glory-options-panel-js',
            '/Glory/assets/js/admin/options-panel.js',
            [
                'deps' => ['jquery', 'wp-color-picker'],
                'in_footer' => true,
                'area' => 'admin',
                'ver' => filemtime(get_template_directory() . '/Glory/assets/js/admin/options-panel.js'),
            ]
        );

        AssetManager::define(
            'style',
            'glory-admin-panel-css',
            '/Glory/assets/css/admin-panel.css',
            [
                'deps' => [],
                'media' => 'all',
                'area' => 'admin',
                'ver' => filemtime(get_template_directory() . '/Glory/assets/css/admin-panel.css'),
            ]
        );
    }

    public function gestionarYRenderizarPagina(): void
    {
        if ('POST' === $_SERVER['REQUEST_METHOD'] && isset($_POST['glory_opciones_nonce']) && wp_verify_nonce($_POST['glory_opciones_nonce'], 'glory_guardar_opciones')) {
            if (isset($_POST['guardar_opciones'])) {
                $resultado = OpcionPanelSaver::guardarDesdePanel($_POST);
                add_settings_error('glory_opciones', 'guardado_exitoso', "Se guardaron {$resultado['guardadas']} opciones.", 'success');
            } elseif (isset($_POST['resetear_seccion']) && !empty($_POST['seccion_a_resetear'])) {
                $slugSeccion = sanitize_title($_POST['seccion_a_resetear']);
                $resultado = OpcionPanelSaver::resetearSeccion($slugSeccion);
                add_settings_error('glory_opciones', 'reseteo_exitoso', "Se resetearon {$resultado['reseteadas']} opciones en la sección.", 'updated');
            }
        }

        $datosParaVista = PanelDataProvider::obtenerDatosParaPanel();
        settings_errors('glory_opciones');
        PanelRenderer::renderizar($datosParaVista);
    }
}