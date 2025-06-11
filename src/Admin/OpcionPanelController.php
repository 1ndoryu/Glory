<?

namespace Glory\Admin;

use Glory\Admin\PanelDataProvider;
use Glory\Admin\OpcionPanelSaver;
use Glory\Admin\PanelRenderer;

/**
 * Orquesta la página de opciones del tema en el panel de administración.
 *
 * Registra la página en el menú de WP, gestiona el envío de formularios
 * (guardar y resetear) utilizando OpcionPanelSaver, y prepara los datos
 * para la vista utilizando PanelDataProvider.
 */
class OpcionPanelController
{
    /**
     * @var string Slug para la página de opciones.
     */
    private const MENU_SLUG = 'glory-opciones';

    /**
     * Registra los hooks de WordPress necesarios.
     */
    public function registerHooks(): void
    {
        add_action('admin_menu', [$this, 'agregarPaginaOpciones']);
    }

    /**
     * Agrega la página de opciones al menú de administración de WordPress.
     */
    public function agregarPaginaOpciones(): void
    {
        add_menu_page(
            'Opciones del Tema Glory',
            'Glory Opciones',
            'manage_options',
            self::MENU_SLUG,
            [$this, 'gestionarYRenderizarPagina'],
            'dashicons-admin-generic',
            60
        );
    }

    /**
     * Gestiona los envíos de formularios y renderiza la página.
     * Esta función es el "controlador" principal de la página.
     */
    public function gestionarYRenderizarPagina(): void
    {
        // 1. Gestión del envío de formularios (POST request)
        if ('POST' === $_SERVER['REQUEST_METHOD'] && isset($_POST['glory_opciones_nonce']) && wp_verify_nonce($_POST['glory_opciones_nonce'], 'glory_guardar_opciones')) {

            if (isset($_POST['guardar_opciones'])) {
                // Acción de guardar: Llama al saver y muestra notificación.
                $resultado = OpcionPanelSaver::guardarDesdePanel($_POST);
                add_settings_error('glory_opciones', 'guardado_exitoso', "Se guardaron {$resultado['guardadas']} opciones.", 'success');
            } elseif (isset($_POST['resetear_seccion']) && !empty($_POST['seccion_a_resetear'])) {
                // Acción de resetear: Llama al saver y muestra notificación.
                $slugSeccion = sanitize_title($_POST['seccion_a_resetear']);
                $resultado = OpcionPanelSaver::resetearSeccion($slugSeccion);
                add_settings_error('glory_opciones', 'reseteo_exitoso', "Se resetearon {$resultado['reseteadas']} opciones en la sección.", 'updated');
            }
        }

        // 2. Preparación de datos para la vista
        $datosParaVista = PanelDataProvider::obtenerDatosParaPanel();

        // 3. Renderizado de la vista
        settings_errors('glory_opciones');

        // Llama al renderizador con el nombre de clase corregido.
        PanelRenderer::renderizar($datosParaVista);
    }
}
