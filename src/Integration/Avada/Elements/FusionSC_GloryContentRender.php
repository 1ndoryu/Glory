<?php

// Clase global sin namespace para ajustarse al patrón de Avada.

if ( ! class_exists( 'FusionSC_GloryContentRender' ) && class_exists( 'Fusion_Element' ) ) {

    class FusionSC_GloryContentRender extends Fusion_Element {

        private $counter = 1;

        public function __construct() {
            parent::__construct();
            add_shortcode( 'glory_content_render', [ $this, 'render' ] );
        }

        public static function get_element_defaults() {
            return [
                'post_type'                => 'post',
                'template_id'              => '__default',
                'usar_content_render'      => 'yes',
                'publicaciones_por_pagina' => 10,
                'clase_contenedor'         => 'glory-content-list',
                'clase_item'               => 'glory-content-item',
                'paginacion'               => '',
                'orden'                    => 'fecha',
                'meta_key'                 => '',
                'meta_order'               => 'ASC',
                'min_paginas'              => 1,
                'tiempo_cache'             => 3600,
                'forzar_sin_cache'         => '',
                'acciones'                 => '',
                'submenu'                  => '',
                'evento_accion'            => 'dblclick',
                'selector_item'            => '[id^="post-"]',
                'argumentos_json'          => '',
                'titulo'                   => '',
                'function_name'            => '',
                'shortcode'                => '',
                'raw_content'              => '',
            ];
        }

        public function render( $args, $content = '' ) {
            $this->defaults = self::get_element_defaults();
            $this->args     = FusionBuilder::set_shortcode_defaults( $this->defaults, $args, 'glory_content_render' );

            $this->set_element_id( $this->counter );

            $titulo = isset( $this->args['titulo'] ) ? (string) $this->args['titulo'] : '';
            $html   = '';
            if ( '' !== $titulo ) {
                $html .= '<h3>' . esc_html( $titulo ) . '</h3>';
            }

            $usar_cr  = ( isset( $this->args['usar_content_render'] ) && 'yes' === $this->args['usar_content_render'] );
            $postType = isset( $this->args['post_type'] ) ? sanitize_key( $this->args['post_type'] ) : 'post';

            if ( $usar_cr && class_exists( '\\Glory\\Components\\ContentRender' ) ) {
                $callable    = [ '\\Glory\\Components\\ContentRender', 'defaultTemplate' ];
                $template_id = isset( $this->args['template_id'] ) ? (string) $this->args['template_id'] : '';
                if ( '' !== $template_id && '__default' !== $template_id && class_exists( '\\Glory\\Utility\\TemplateRegistry' ) ) {
                    $applies = call_user_func( [ '\\Glory\\Utility\\TemplateRegistry', 'appliesTo' ], $template_id );
                    if ( ! empty( $applies ) && is_array( $applies ) && ! in_array( $postType, $applies, true ) ) {
                        if ( current_user_can( 'manage_options' ) ) {
                            $html .= '<div class="glory-warning">' . esc_html( sprintf( __( 'La plantilla seleccionada no aplica a “%s”. Se usará la plantilla por defecto.', 'glory-ab' ), $postType ) ) . '</div>';
                        }
                    } else {
                        $c = call_user_func( [ '\\Glory\\Utility\\TemplateRegistry', 'get' ], $template_id );
                        if ( is_callable( $c ) ) {
                            $callable = $c;
                        }
                    }
                }

                $argumentosConsulta = [];
                if ( ! empty( $this->args['argumentos_json'] ) ) {
                    $json   = trim( (string) $this->args['argumentos_json'] );
                    $parsed = json_decode( $json, true );
                    if ( is_array( $parsed ) ) {
                        $argumentosConsulta = $parsed;
                    } else {
                        if ( current_user_can( 'manage_options' ) ) {
                            $html .= '<div class="glory-warning">' . esc_html__( 'JSON inválido en argumentos de consulta. Ignorando.', 'glory-ab' ) . '</div>';
                        }
                    }
                }

                $orden     = isset( $this->args['orden'] ) ? (string) $this->args['orden'] : 'fecha';
                $metaKey   = isset( $this->args['meta_key'] ) ? trim( (string) $this->args['meta_key'] ) : '';
                $metaOrder = isset( $this->args['meta_order'] ) ? strtoupper( (string) $this->args['meta_order'] ) : 'ASC';

                $config = [
                    'publicacionesPorPagina' => isset( $this->args['publicaciones_por_pagina'] ) ? (int) $this->args['publicaciones_por_pagina'] : 10,
                    'claseContenedor'        => $this->args['clase_contenedor'] ?? 'glory-content-list',
                    'claseItem'              => $this->args['clase_item'] ?? 'glory-content-item',
                    'paginacion'             => ( isset( $this->args['paginacion'] ) && 'yes' === $this->args['paginacion'] ),
                    'plantillaCallback'      => $callable,
                    'argumentosConsulta'     => $argumentosConsulta,
                    'orden'                  => ( 'meta' === $orden && '' !== $metaKey ) ? $metaOrder : ( 'random' === $orden ? 'random' : 'fecha' ),
                    'metaKey'                => '' !== $metaKey ? $metaKey : null,
                    'minPaginas'             => isset( $this->args['min_paginas'] ) ? (int) $this->args['min_paginas'] : 1,
                    'tiempoCache'            => isset( $this->args['tiempo_cache'] ) ? (int) $this->args['tiempo_cache'] : 3600,
                    'forzarSinCache'         => ( isset( $this->args['forzar_sin_cache'] ) && 'yes' === $this->args['forzar_sin_cache'] ),
                    'acciones'               => ! empty( $this->args['acciones'] ) ? array_map( 'trim', explode( ',', (string) $this->args['acciones'] ) ) : [],
                    'submenu'                => ( isset( $this->args['submenu'] ) && 'yes' === $this->args['submenu'] ),
                    'eventoAccion'           => $this->args['evento_accion'] ?? 'dblclick',
                    'selectorItem'           => $this->args['selector_item'] ?? '[id^="post-"]',
                ];

                ob_start();
                try {
                    call_user_func( [ '\\Glory\\Components\\ContentRender', 'print' ], $postType, $config );
                } catch ( \Throwable $t ) {
                    if ( current_user_can( 'manage_options' ) ) {
                        $html .= '<div class="glory-widget-error">' . esc_html( $t->getMessage() ) . '</div>';
                    }
                }
                $html .= ob_get_clean();

                $this->counter++;
                $this->on_render();
                return $html;
            }

            $function_name = isset( $this->args['function_name'] ) ? (string) $this->args['function_name'] : '';
            if ( '' !== $function_name && function_exists( $function_name ) ) {
                ob_start();
                try {
                    call_user_func( $function_name );
                } catch ( \Throwable $t ) {
                    if ( current_user_can( 'manage_options' ) ) {
                        echo '<div class="glory-widget-error">' . esc_html( $t->getMessage() ) . '</div>';
                    }
                }
                $html .= ob_get_clean();

                $this->counter++;
                $this->on_render();
                return $html;
            }

            $sc = isset( $this->args['shortcode'] ) ? trim( (string) $this->args['shortcode'] ) : '';
            if ( '' !== $sc ) {
                $html .= do_shortcode( $sc );
                $this->counter++;
                $this->on_render();
                return $html;
            }

            $raw = isset( $this->args['raw_content'] ) ? (string) $this->args['raw_content'] : '';
            $html .= $raw;

            $this->counter++;
            $this->on_render();
            return $html;
        }
    }

    new FusionSC_GloryContentRender();
}


