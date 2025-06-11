<?

namespace Glory\Admin;

use Glory\Component\FormBuilder;

/**
 * Renderiza el panel de opciones de Glory utilizando el componente FormBuilder.
 * Reemplaza la necesidad de un archivo de vista de plantilla y la lógica de renderizado manual de campos.
 */
class PanelRenderer
{
    private static array $opcionesAgrupadas = [];

    /**
     * Renderiza el panel de opciones completo.
     */
    public static function renderizar(array $datosParaVista): void
    {
        self::agruparOpciones($datosParaVista);

        ob_start();
?>
        <div class="wrap glory-content-panel">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <div class="glory-tabs-container-two">

                <?php self::renderizarNavegacion(); ?>

                <div class="glory-tabs-content-container">
                    <form method="post" action="">
                        <?php
                        wp_nonce_field('glory_guardar_opciones', 'glory_opciones_nonce');
                        self::renderizarContenidoPestanas();
                        submit_button('Guardar Cambios', 'primary', 'guardar_opciones');
                        ?>
                    </form>
                </div>
            </div>
        </div>
    <?php
        echo ob_get_clean();
    }

    /**
     * Agrupa las opciones por sección y subsección para facilitar el renderizado.
     */
    private static function agruparOpciones(array $datosParaVista): void
    {
        if (empty($datosParaVista)) {
            return;
        }

        foreach ($datosParaVista as $key => $config) {
            $seccion = $config['seccion'] ?? 'general';
            $etiquetaSeccion = $config['etiquetaSeccion'] ?? ucfirst($seccion);
            $subSeccion = $config['subSeccion'] ?? 'general';

            self::$opcionesAgrupadas[$seccion]['etiqueta'] = $etiquetaSeccion;
            self::$opcionesAgrupadas[$seccion]['subsecciones'][$subSeccion][$key] = $config;
        }
    }

    /**
     * Renderiza la barra de navegación de pestañas.
     */
    private static function renderizarNavegacion(): void
    {
        ob_start();
    ?>
        <div class="glory-tabs-nav-container">
            <h2 class="nav-tab-wrapper">
                <?php foreach (self::$opcionesAgrupadas as $slugSeccion => $datosSeccion) : ?>
                    <a href="#glory-seccion-<?php echo esc_attr($slugSeccion); ?>" class="nav-tab">
                        <?php echo esc_html($datosSeccion['etiqueta']); ?>
                    </a>
                <?php endforeach; ?>
            </h2>
        </div>
    <?php
        echo ob_get_clean();
    }

    /**
     * Renderiza el contenido de todas las pestañas y sus secciones.
     */
    private static function renderizarContenidoPestanas(): void
    {
        ob_start();
    ?>
        <?php foreach (self::$opcionesAgrupadas as $slugSeccion => $datosSeccion) : ?>
            <div id="glory-seccion-<?php echo esc_attr($slugSeccion); ?>" class="glory-tab-content">
                <?php foreach ($datosSeccion['subsecciones'] as $slugSubSeccion => $opciones) : ?>
                    <div class="postbox">
                        <h2 class="hndle"><span><?php echo esc_html(ucfirst(str_replace(['_', '-'], ' ', $slugSubSeccion))); ?></span></h2>
                        <div class="inside">
                            <div class="form-fields-container">
                                <?php foreach ($opciones as $key => $config) {
                                    self::renderizarCampo($key, $config);
                                } ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php self::renderizarBotonReset($slugSeccion); ?>
            </div>
        <?php endforeach; ?>
    <?php
        echo ob_get_clean();
    }

    /**
     * Renderiza un control de formulario individual, delegando en FormBuilder o gestionando casos especiales.
     */
    private static function renderizarCampo(string $key, array $config): void
    {
        $tipo = $config['tipo'] ?? 'text';
        $idCampo = 'glory-opcion-' . esc_attr($key);

        // El FormBuilder crea un div y una etiqueta, por lo que lo envolvemos para mantener la estructura.
        // La tabla <table> ha sido eliminada en favor de divs para mayor flexibilidad.
        echo '<div class="form-field-wrapper" style="margin-bottom: 20px;">';

        if ($tipo === 'richText') {
            // wp_editor es un caso especial que no está en FormBuilder.
            echo '<label for="' . $idCampo . '">' . esc_html($config['etiqueta']) . '</label>';
            wp_editor(
                $config['valorActual'],
                $idCampo,
                ['textarea_name' => $key, 'media_buttons' => false, 'textarea_rows' => 7]
            );
        } else {
            // Para los demás campos, preparamos las opciones para FormBuilder.
            $opcionesCampo = [
                'nombre' => $key,
                'label' => $config['etiqueta'],
                'valor' => $config['valorActual'],
                'classContainer' => 'campo-panel-opciones' // Clase para posible CSS específico.
            ];

            // Delegamos la renderización a FormBuilder.
            switch ($tipo) {
                case 'textarea':
                    echo FormBuilder::campoTextarea($opcionesCampo);
                    break;
                case 'text':
                default:
                    echo FormBuilder::campoTexto($opcionesCampo);
                    break;
            }
        }

        // Renderizamos la descripción del campo si existe.
        if (!empty($config['descripcion'])) {
            printf('<p class="description">%s</p>', esc_html($config['descripcion']));
        }

        echo '</div>'; // Cierre de .form-field-wrapper
    }

    /**
     * Renderiza el botón para resetear una sección.
     */
    private static function renderizarBotonReset(string $slugSeccion): void
    {
        ob_start();
    ?>
        <div style="margin-top: 15px; text-align: right;">
            <input type="hidden" name="seccion_a_resetear" value="<?php echo esc_attr($slugSeccion); ?>" />
            <?php submit_button('Resetear Sección', 'secondary', 'resetear_seccion', false); ?>
        </div>
<?php
        echo ob_get_clean();
    }
}
