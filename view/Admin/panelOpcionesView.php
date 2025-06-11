<?
//nota: me gustaría usar clases camelCase, cortas y en español, no me gusta plantillas, me gusta agrupar en funciones el codigo y luego usarlo cuando quiera. 
/**
 * Vista para el panel de opciones de Glory.
 *
 * Este archivo recibe la variable $datosParaVista desde OpcionPanelController
 * y la utiliza para renderizar el formulario HTML.
 *
 * @var array $datosParaVista Datos completos de las opciones a renderizar.
 */

/*
$opcionesAgrupadas = [];
if (!empty($datosParaVista)) {
    foreach ($datosParaVista as $key => $config) {
        $seccion = $config['seccion'] ?? 'general';
        $etiquetaSeccion = $config['etiquetaSeccion'] ?? ucfirst($seccion);
        $subSeccion = $config['subSeccion'] ?? 'general';
        $opcionesAgrupadas[$seccion]['etiqueta'] = $etiquetaSeccion;
        $opcionesAgrupadas[$seccion]['subsecciones'][$subSeccion][$key] = $config;
    }
}
?>

<div class="wrap glory-content-panel">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div class="glory-tabs-container-two">
        <div class="glory-tabs-nav-container">
            <h2 class="nav-tab-wrapper">
                <?php foreach ($opcionesAgrupadas as $slugSeccion => $datosSeccion) : ?>
                    <a href="#glory-seccion-<?php echo esc_attr($slugSeccion); ?>" class="nav-tab">
                        <?php echo esc_html($datosSeccion['etiqueta']); ?>
                    </a>
                <?php endforeach; ?>
            </h2>
        </div>

        <div class="glory-tabs-content-container">
            <form method="post" action="">
                <?php
                // Nonce de seguridad para el guardado general
                wp_nonce_field('glory_guardar_opciones', 'glory_opciones_nonce');
                ?>

                <?php foreach ($opcionesAgrupadas as $slugSeccion => $datosSeccion) : ?>
                    <div id="glory-seccion-<?php echo esc_attr($slugSeccion); ?>" class="glory-tab-content">
                        <?php foreach ($datosSeccion['subsecciones'] as $slugSubSeccion => $opciones) : ?>
                            <div class="postbox">
                                <h2 class="hndle"><span><?php echo esc_html(ucfirst(str_replace(['_', '-'], ' ', $slugSubSeccion))); ?></span></h2>
                                <div class="inside">
                                    <table class="form-table">
                                        <tbody>
                                            <?php foreach ($opciones as $key => $config) :
                                                $valor = $config['valorActual'];
                                                $tipo = $config['tipo'] ?? 'text';
                                            ?>
                                                <tr>
                                                    <th scope="row">
                                                        <label for="glory-opcion-<?php echo esc_attr($key); ?>">
                                                            <?php echo esc_html($config['etiqueta']); ?>
                                                        </label>
                                                    </th>
                                                    <td>
                                                        <?php // Switch para renderizar el tipo de campo adecuado ?>
                                                        <?php switch ($tipo):
                                                            case 'textarea': ?>
                                                                <textarea id="glory-opcion-<?php echo esc_attr($key); ?>" name="<?php echo esc_attr($key); ?>" class="large-text" rows="5"><?php echo esc_textarea($valor); ?></textarea>
                                                                <?php break; ?>

                                                            <?php case 'richText': ?>
                                                                <?php wp_editor($valor, 'glory-opcion-' . esc_attr($key), ['textarea_name' => $key, 'media_buttons' => false, 'textarea_rows' => 7]); ?>
                                                                <?php break; ?>
                                                                
                                                            <?php // Añadir más casos para 'image', 'color', 'select', etc. si es necesario. ?>

                                                            <?php case 'text': ?>
                                                            <?php default: ?>
                                                                <input type="text" id="glory-opcion-<?php echo esc_attr($key); ?>" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($valor); ?>" class="regular-text" />
                                                                <?php break; ?>
                                                        <?php endswitch; ?>

                                                        <?php if (!empty($config['descripcion'])) : ?>
                                                            <p class="description"><?php echo esc_html($config['descripcion']); ?></p>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <div style="margin-top: 15px; text-align: right;">
                            <input type="hidden" name="seccion_a_resetear" value="<?php echo esc_attr($slugSeccion); ?>" />
                            <?php submit_button('Resetear Sección', 'secondary', 'resetear_seccion', false); ?>
                        </div>

                    </div>
                <?php endforeach; ?>

                <?php
                // Botón de guardado principal
                submit_button('Guardar Cambios', 'primary', 'guardar_opciones');
                ?>
            </form>
        </div>
    </div>
</div>
*/