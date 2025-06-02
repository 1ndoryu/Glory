<?php

namespace Glory\Admin;

if (!defined('ABSPATH')) {
    exit;
}

use Glory\Components\formManagerComponent;
use Glory\Services\FormService; // Necesario para las constantes de acción AJAX

class formAdminPanel
{
    private string $hookSuffix = ''; // Inicializar para evitar errores si no se llama add_menu_page

    public function __construct()
    {
        add_action('admin_menu', [$this, 'agregarPaginaAdmin']);
    }

    public function agregarPaginaAdmin(): void
    {
        $this->hookSuffix = add_menu_page(
            esc_html__('Datos de Formularios', 'glory-domain'),
            esc_html__('Formularios', 'glory-domain'),
            'manage_options',
            'glory-form-data', // Slug de la página
            [$this, 'renderizarContenidoPanel'],
            'dashicons-feedback',
            26
        );

        // Enganchar el encolado de scripts solo para esta página de admin
        // Es importante que $this->hookSuffix se haya establecido antes de que se dispare admin_enqueue_scripts
        if ($this->hookSuffix) {
            add_action('admin_enqueue_scripts', [$this, 'enqueueAdminScripts']);
        }
    }

    public function renderizarContenidoPanel(): void
    {

        echo formManagerComponent::generarHtmlPanelAdmin();
    }

    public function enqueueAdminScripts(string $hook): void
    {
        // Asegurarse de que los scripts solo se carguen en nuestra página de admin
        if ($hook !== $this->hookSuffix) {
            return;
        }

        // Ruta y URL para el script dentro del tema "Glory"
        // Asumimos que el tema se llama "Glory". Si es un tema hijo, get_stylesheet_... es correcto.
        // Si es un tema padre y quieres forzarlo desde el padre, usa get_template_...
        $scriptRelativePath = 'Glory/assets/js/FormPanel.js';
        $scriptPath = get_stylesheet_directory() . '/' . $scriptRelativePath;
        $scriptUrl = get_stylesheet_directory_uri() . '/' . $scriptRelativePath;
        
        $scriptHandle = 'glory-form-panel-admin-js'; // Nuevo handle para el script
        $scriptVersion = file_exists($scriptPath) ? filemtime($scriptPath) : '1.0.0';

        wp_enqueue_script(
            $scriptHandle,
            $scriptUrl,
            [], // Dependencias, ej. ['jquery'] si lo necesitas. Por ahora, ninguno.
            $scriptVersion,
            true // Cargar en el footer
        );

        // Localizar datos para el script, como nonces y URLs de AJAX
        wp_localize_script(
            $scriptHandle, // Usar el nuevo handle del script
            'gloryAdminPanelData', // Objeto global JS para los datos localizados
            [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'deleteSingleAction' => FormService::AJAX_DELETE_SINGLE_SUBMISSION_ACTION,
                'deleteAllAction' => FormService::AJAX_DELETE_ALL_SUBMISSIONS_ACTION,
                'textConfirmDeleteSingle' => __('¿Estás seguro de que quieres borrar este mensaje? Esta acción no se puede deshacer.', 'glory-domain'),
                'textConfirmDeleteAll' => __('¿Estás seguro de que quieres borrar TODOS los mensajes de este formulario? Esta acción no se puede deshacer.', 'glory-domain'),
                'textErrorGeneric' => __('Ocurrió un error. Por favor, inténtalo de nuevo.', 'glory-domain'),
                'textNonceError' => __('Error de seguridad. Por favor, recarga la página e inténtalo de nuevo.', 'glory-domain'),
                'textDeleting' => __('Borrando...', 'glory-domain'),
                'textDeleted' => __('Borrado.', 'glory-domain'),
                'textNoMessages' => __('No hay envíos para este formulario todavía.', 'glory-domain')
            ]
        );
    }
}