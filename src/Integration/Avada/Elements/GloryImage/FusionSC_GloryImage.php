<?php

// Clase global sin namespace para ajustarse al patrón de Avada.

if ( ! class_exists( 'FusionSC_GloryImage' ) && class_exists( 'Fusion_Element' ) ) {

    class FusionSC_GloryImage extends Fusion_Element {

        private $counter = 1;

        public function __construct() {
            parent::__construct();
            add_shortcode( 'glory_image', [ $this, 'render' ] );
        }

        public static function get_element_defaults() {
            return [
                'image'                => '',
                'image_id'             => '',
                'image_size'           => 'full',
                'alt'                  => '',
                // Diseño imagen
                'align'                => 'none', // none|left|center|right
                'aspect_ratio'         => '1 / 1',
                'object_fit'           => 'cover', // cover|contain
                'min_width'            => '',
                'height'               => '',
                'full_width'           => 'no',
                // Título
                'show_title'           => 'no',
                'title_text'           => '',
                'title_font_family'    => '',
                'title_font_size'      => '',
                'title_font_weight'    => '',
                'title_max_width'      => '',
                'title_show_on_hover'  => 'no',
            ];
        }

        public function render( $args, $content = '' ) {
            $this->defaults = self::get_element_defaults();
            $this->args     = FusionBuilder::set_shortcode_defaults( $this->defaults, $args, 'glory_image' );

            $this->set_element_id( $this->counter );

            $instance_class = 'glory-img-' . (string) $this->counter;

            $attachment_id = isset( $this->args['image_id'] ) ? (int) $this->args['image_id'] : 0;
            $image_url     = isset( $this->args['image'] ) ? (string) $this->args['image'] : '';
            $image_size    = isset( $this->args['image_size'] ) ? (string) $this->args['image_size'] : 'full';
            $alt           = isset( $this->args['alt'] ) ? (string) $this->args['alt'] : '';

            $html = '';
            if ( class_exists( '\\Glory\\Components\\GloryImage' ) ) {
                $html .= call_user_func( [ '\\Glory\\Components\\GloryImage', 'render' ], [
                    'attachment_id' => $attachment_id,
                    'image_url'     => $image_url,
                    'image_size'    => $image_size,
                    'alt'           => $alt,
                    'align'         => $this->args['align'] ?? 'none',
                    'aspect_ratio'  => $this->args['aspect_ratio'] ?? '1 / 1',
                    'object_fit'    => $this->args['object_fit'] ?? 'cover',
                    'min_width'     => $this->args['min_width'] ?? '',
                    'height'        => $this->args['height'] ?? '',
                    'full_width'    => $this->args['full_width'] ?? 'no',
                    'show_title'    => $this->args['show_title'] ?? 'no',
                    'title_text'    => $this->args['title_text'] ?? '',
                    'instance_class'=> $instance_class,
                ] );
            }

            // CSS inline por instancia (título y hover)
            $css  = $this->build_instance_css( $instance_class );
            if ( '' !== $css ) {
                $html .= '<style id="' . esc_attr( $instance_class ) . '-css">' . $css . '</style>';
            }

            $this->counter++;
            $this->on_render();
            return $html;
        }

        private function build_instance_css( string $instance_class ): string {
            $containerClass = '.' . $instance_class;
            $css = '';

            // Estilos del título
            $css .= $containerClass . ' .glory-image__title{';
            if ( ! empty( $this->args['title_font_family'] ) ) {
                $css .= 'font-family:' . esc_attr( (string) $this->args['title_font_family'] ) . ';';
            }
            if ( ! empty( $this->args['title_font_size'] ) ) {
                $css .= 'font-size:' . esc_attr( (string) $this->args['title_font_size'] ) . ';';
            }
            if ( ! empty( $this->args['title_font_weight'] ) ) {
                $css .= 'font-weight:' . esc_attr( (string) $this->args['title_font_weight'] ) . ';';
            }
            if ( ! empty( $this->args['title_max_width'] ) ) {
                $css .= 'max-width:' . esc_attr( (string) $this->args['title_max_width'] ) . ';';
            }
            $css .= '}';

            // Mostrar título solo en hover (opcional)
            $title_text = isset( $this->args['title_text'] ) ? (string) $this->args['title_text'] : '';
            $show_title = isset( $this->args['show_title'] ) && 'yes' === (string) $this->args['show_title'];
            $title_on_hover = isset( $this->args['title_show_on_hover'] ) && 'yes' === (string) $this->args['title_show_on_hover'];
            if ( $show_title && $title_on_hover && '' !== $title_text ) {
                $css .= $containerClass . ' .glory-image__title{opacity:0;visibility:hidden;transition:opacity .2s ease;}';
                $css .= $containerClass . ':hover .glory-image__title,';
                $css .= $containerClass . ' .glory-image__image:hover + .glory-image__title{opacity:1;visibility:visible;}';
            }

            // Forzar ancho completo del contenedor
            $full_width = isset( $this->args['full_width'] ) && 'yes' === (string) $this->args['full_width'];
            if ( $full_width ) {
                $css .= $containerClass . ' .glory-image__image{width:100%;}';
            }

            return $css;
        }
    }

    new FusionSC_GloryImage();
}


