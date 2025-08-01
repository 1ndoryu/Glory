<?php

namespace Glory\Admin;

use Glory\Components\FormBuilder;
use Glory\Manager\OpcionManager;

class PanelRenderer
{
    private static array $opcionesAgrupadas = [];

    public static function renderizar(array $datosParaVista): void
    {
        if (!OpcionManager::get('glory_ejemplos_activo')) {
            $datosParaVista = array_filter($datosParaVista, function ($config, $key) {
                if ($key === 'glory_ejemplos_activo') {
                    return true;
                }
                return ($config['seccion'] ?? 'general') !== 'ejemplos';
            }, ARRAY_FILTER_USE_BOTH);
        }

        self::agruparOpciones($datosParaVista);

        ob_start();
        ?>
        <div class="wrap glory-options-panel">
            <h1 style="display: none;"><?php echo esc_html(get_admin_page_title()); ?></h1>

            <form method="post" action="">
                <?php wp_nonce_field('glory_guardar_opciones', 'glory_opciones_nonce'); ?>

                <div class="glory-panel-container">
                    <?php self::renderizarNavegacion(); ?>
                    <div class="glory-panel-content">
                        <?php self::renderizarContenidoPestanas(); ?>
                    </div>
                </div>

            </form>
        </div>
        <?php
        echo ob_get_clean();
    }

    private static function agruparOpciones(array $datosParaVista): void
    {
        self::$opcionesAgrupadas = [];
        if (empty($datosParaVista)) return;

        foreach ($datosParaVista as $key => $config) {
            $seccion = $config['seccion'] ?? 'general';
            $etiquetaSeccion = $config['etiquetaSeccion'] ?? ucfirst($seccion);
            $subSeccion = $config['subSeccion'] ?? 'general';

            self::$opcionesAgrupadas[$seccion]['etiqueta'] = $etiquetaSeccion;
            self::$opcionesAgrupadas[$seccion]['subsecciones'][$subSeccion][$key] = $config;
        }
    }

    private static function renderizarNavegacion(): void
    {
        if (empty(self::$opcionesAgrupadas)) return;
        ?>
        <div class="glory-panel-nav">
            <h2 class="nav-tab-wrapper">
                <?php foreach (self::$opcionesAgrupadas as $slugSeccion => $datosSeccion) : ?>
                    <a href="#tab-<?php echo esc_attr($slugSeccion); ?>" class="nav-tab">
                        <?php echo esc_html($datosSeccion['etiqueta']); ?>
                    </a>
                <?php endforeach; ?>
            </h2>
        </div>
        <?php
    }

    private static function renderizarContenidoPestanas(): void
    {
        if (empty(self::$opcionesAgrupadas)) return;
        ?>
        <?php foreach (self::$opcionesAgrupadas as $slugSeccion => $datosSeccion) : ?>
            <div id="tab-<?php echo esc_attr($slugSeccion); ?>" class="glory-panel-tab">
                <?php foreach ($datosSeccion['subsecciones'] as $slugSubSeccion => $opciones) : ?>
                    <div class="postbox">
                        <h2 class="titleSubSection"><span><?php echo esc_html(ucfirst(str_replace(['_', '-'], ' ', $slugSubSeccion))); ?></span></h2>
                        <div class="inside">
                            <?php foreach ($opciones as $key => $config) {
                                self::renderizarCampo($key, $config);
                            } ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="glory-panel-actions">
                    <?php submit_button('Save Options', 'primary', 'guardar_opciones', false); ?>
                    <button type="submit" name="resetear_seccion" value="<?php echo esc_attr($slugSeccion); ?>" class="button button-secondary" onclick="return confirm('Are you sure you want to restore the default values for this section?');">
                        Restore Section
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
        <?php
    }

    private static function renderizarCampo(string $key, array $config): void
    {
        $tipo = $config['tipo'] ?? 'text';

        $wrapperClasses = 'form-field-wrapper';
        $wrapperAttributes = '';

        // Lógica condicional para campos de logo
        if ($key === 'glory_logo_text') {
            $wrapperClasses .= ' glory-conditional-field';
            $wrapperAttributes = ' data-condition-field="glory_logo_mode" data-condition-value="text"';
        } elseif ($key === 'glory_logo_image') {
            $wrapperClasses .= ' glory-conditional-field';
            $wrapperAttributes = ' data-condition-field="glory_logo_mode" data-condition-value="image"';
        }

        echo '<div class="' . esc_attr($wrapperClasses) . '"' . $wrapperAttributes . '>';

        $opcionesCampo = [
            'nombre'      => $key,
            'label'       => $config['etiqueta'],
            'valor'       => $config['valorActual'],
            'descripcion' => $config['descripcion'] ?? '',
            'opciones'    => $config['opciones'] ?? [],
        ];

        switch ($tipo) {
            case 'textarea':
                echo FormBuilder::campoTextarea($opcionesCampo);
                break;
            case 'richText':
                echo '<label>' . esc_html($config['etiqueta']) . '</label>';
                wp_editor($config['valorActual'], 'glory-opcion-' . esc_attr($key), ['textarea_name' => $key, 'media_buttons' => false, 'textarea_rows' => 7]);
                if (!empty($config['descripcion'])) {
                    printf('<p class="description">%s</p>', wp_kses_post($config['descripcion']));
                }
                break;
            case 'checkbox':
                $opcionesCampo['checked'] = !empty($config['valorActual']);
                echo FormBuilder::campoCheckbox($opcionesCampo);
                break;
            case 'select':
                echo FormBuilder::campoSelect($opcionesCampo);
                break;
            case 'radio':
                echo FormBuilder::campoRadio($opcionesCampo);
                break;
            case 'imagen':
                echo FormBuilder::campoImagen($opcionesCampo);
                break;
            case 'color':
                echo FormBuilder::campoColor($opcionesCampo);
                break;
            case 'numero':
                echo FormBuilder::campoNumero($opcionesCampo);
                break;
            case 'text':
            default:
                echo FormBuilder::campoTexto($opcionesCampo);
                break;
        }
        echo '</div>';
    }
}