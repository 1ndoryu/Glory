<?php

namespace Glory\Admin;

if (!defined('ABSPATH')) {
    exit;
}

use Glory\Components\formManagerComponent;

class formAdminPanel
{
    public function __construct()
    {
        add_action('admin_menu', [$this, 'agregarPaginaAdmin']);
    }

    public function agregarPaginaAdmin(): void
    {
        add_menu_page(
            esc_html__('Datos de Formularios', 'glory-domain'),
            esc_html__('Formularios', 'glory-domain'),
            'manage_options',
            'glory-form-data',
            [$this, 'renderizarContenidoPanel'],
            'dashicons-feedback',
            26
        );
    }

    public function renderizarContenidoPanel(): void
    {
        echo formManagerComponent::generarHtmlPanelAdmin();
    }
}
