<?php

use \Glory\Integration\Avada\Elements\GloryGallery\GloryGalleryHelper;

if (!class_exists('FusionSC_GloryGallery') && class_exists('Fusion_Element')) {

    class FusionSC_GloryGallery extends Fusion_Element
    {

        private $counter = 1;

        public function __construct()
        {
            parent::__construct();
            add_shortcode('glory_gallery', [$this, 'render']);
        }

        public static function get_element_defaults()
        {
            return [
                'image_ids'                => '', // Array de IDs
                'publicaciones_por_pagina' => 10,
                'clase_contenedor'         => 'glory-gallery-list',
                'clase_item'               => 'glory-gallery-item',
                'paginacion'               => 'no',
                'tiempo_cache'             => 3600,
                'forzar_sin_cache'         => 'no',
                'titulo'                   => '',
                // Carrusel
                'carousel'                 => 'no',
                'carousel_speed'           => 20,
                'carousel_manual'          => 'no',
                // Layout (por instancia)
                'display_mode'             => 'flex', // flex | grid | block
                'flex_direction'           => 'row',
                'flex_wrap'                => 'wrap',
                'gap'                      => '20px',
                'align_items'              => 'stretch',
                'justify_content'          => 'flex-start',
                'grid_min_width'           => '250px',
                'grid_auto_fit'            => 'yes', // yes => auto-fit, no => auto-fill
                'grid_columns_mode'        => 'fixed',
                'grid_columns'             => 4,
                'grid_columns_medium'      => '',
                'grid_columns_small'       => '',
                'grid_min_columns'         => 1,
                'grid_min_columns_medium'  => '',
                'grid_min_columns_small'   => '',
                'grid_max_columns'         => 12,
                'grid_max_columns_medium'  => '',
                'grid_max_columns_small'   => '',
                // Modo destacado (imagen principal + miniaturas)
                'featured_mode'            => 'no',
                'featured_thumbs_position' => 'bottom', // top | bottom
                'featured_thumb_height'    => '',
                'featured_thumb_aspect_ratio' => '',
                'featured_main_object_fit' => 'cover',
                'featured_main_bg_color'   => '',
                // Imagen (por instancia)
                'img_show'                 => 'yes',
                'img_aspect_ratio'         => '1 / 1',
                'img_object_fit'           => 'cover',
                'img_size'                 => 'medium',
                'img_min_width'            => '',
                'img_width'                => '',
                'img_max_width'            => '',
                'img_height'               => '',
                'img_min_height'           => '',
                'img_max_height'           => '',
                'img_optimize'             => 'yes',
                'img_quality'              => 60,
                // Título (por instancia)
                'title_show'               => 'no',
                'title_font_family'        => '',
                'title_font_size'          => '',
                'title_font_weight'        => '',
                'title_text_transform'     => '',
                'title_max_width'          => '',
                'title_show_on_hover'      => 'no',
                'title_position'           => 'bottom', // top | bottom
                // Enlace
                'link_enabled'             => 'yes',
            ];
        }

        public function render($args, $content = '')
        {
            // Cargar el helper si no está disponible
            if (!class_exists('\\Glory\\Integration\\Avada\\Elements\\GloryGallery\\GloryGalleryHelper')) {
                $helperPath = get_template_directory() . '/Glory/src/Integration/Avada/Elements/GloryGallery/GloryGalleryHelper.php';
                $childPath   = get_stylesheet_directory() . '/Glory/src/Integration/Avada/Elements/GloryGallery/GloryGalleryHelper.php';
                if (is_readable($childPath)) {
                    require_once $childPath;
                } elseif (is_readable($helperPath)) {
                    require_once $helperPath;
                }
            }

            $this->defaults = self::get_element_defaults();
            $this->args     = FusionBuilder::set_shortcode_defaults($this->defaults, $args, 'glory_gallery');

            $this->set_element_id($this->counter);

            $titulo = isset($this->args['titulo']) ? (string)$this->args['titulo'] : '';
            $html   = '';
            if ('' !== $titulo) {
                $html .= '<h3>' . esc_html($titulo) . '</h3>';
            }
            // Procesar IDs usando el helper
            $ids = GloryGalleryHelper::procesarIdsImagenes($this->args['image_ids']);

            // Solo renderizar si hay IDs seleccionados
            if (!empty($ids)) {
                // Generar clase única para la instancia
                $instanceClass = GloryGalleryHelper::generarInstanciaClass();

                // Preparar configuración para ContentRender
                $config = GloryGalleryHelper::prepararConfiguracion($this->args, $ids, $instanceClass);

                ob_start();
                try {
                    GloryGalleryHelper::enableDedupFilters();
                    call_user_func(['\\Glory\\Components\\ContentRender', 'print'], 'attachment', $config);
                } catch (\Throwable $t) {
                    if (current_user_can('manage_options')) {
                        echo '<div class="glory-widget-error">Error al cargar galería: ' . esc_html($t->getMessage()) . '</div>';
                    }
                } finally {
                    GloryGalleryHelper::disableDedupFilters();
                }
                $buffer = ob_get_clean();

                // Envolver con wrapper y flechas si carrusel manual
                $buffer = GloryGalleryHelper::envolverParaCarruselManual($buffer, $instanceClass, $this->args);

                $html .= $buffer;

                // CSS inline por instancia usando el helper
                $css = GloryGalleryHelper::generarCSS($instanceClass, $this->args);
                if ('' !== $css) {
                    $html .= '<style id="' . esc_attr($instanceClass) . '-css">' . $css . '</style>';
                }

                // JavaScript por instancia usando el helper
                $html .= GloryGalleryHelper::generarJS($instanceClass, $this->args);

                $this->counter++;
                $this->on_render();
                return $html;
            } else {
                // Sin selección: contenedor vacío
                $instanceClass = GloryGalleryHelper::generarInstanciaClass();
                $contenedorClass = trim((string)($this->args['clase_contenedor'] ?? 'glory-gallery-list') . ' ' . $instanceClass);
                return $html . '<div class="' . esc_attr($contenedorClass) . ' glory-empty-gallery"><p>Selecciona imágenes para ver la galería.</p></div>';
            }
        }
    }

    new FusionSC_GloryGallery();
}
