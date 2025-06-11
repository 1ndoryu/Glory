<?
namespace Glory\Admin;

/**
 * Renderiza el panel de opciones de Glory utilizando funciones PHP y output buffering.
 * Reemplaza la necesidad de un archivo de vista de plantilla.
 */
class RenderizadorPanel
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
                            <table class="form-table">
                                <tbody>
                                    <?php foreach ($opciones as $key => $config) {
                                        self::renderizarFilaCampo($key, $config);
                                    } ?>
                                </tbody>
                            </table>
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
     * Renderiza una fila completa de la tabla para un campo de opción.
     */
    private static function renderizarFilaCampo(string $key, array $config): void
    {
        ob_start();
        ?>
        <tr>
            <th scope="row">
                <label for="glory-opcion-<?php echo esc_attr($key); ?>">
                    <?php echo esc_html($config['etiqueta']); ?>
                </label>
            </th>
            <td>
                <?php
                self::renderizarCampo($key, $config);
                if (!empty($config['descripcion'])) {
                    printf('<p class="description">%s</p>', esc_html($config['descripcion']));
                }
                ?>
            </td>
        </tr>
        <?php
        echo ob_get_clean();
    }

    /**
     * Renderiza el control de formulario (input, textarea, etc.) para una opción.
     */
    private static function renderizarCampo(string $key, array $config): void
    {
        $valor = $config['valorActual'];
        $tipo = $config['tipo'] ?? 'text';
        $idCampo = 'glory-opcion-' . esc_attr($key);

        ob_start();
        switch ($tipo) {
            case 'textarea':
                ?>
                <textarea id="<?php echo $idCampo; ?>" name="<?php echo esc_attr($key); ?>" class="large-text" rows="5"><?php echo esc_textarea($valor); ?></textarea>
                <?php
                break;
            case 'richText':
                wp_editor($valor, $idCampo, ['textarea_name' => $key, 'media_buttons' => false, 'textarea_rows' => 7]);
                break;
            case 'text':
            default:
                ?>
                <input type="text" id="<?php echo $idCampo; ?>" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($valor); ?>" class="regular-text" />
                <?php
                break;
        }
        echo ob_get_clean();
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