<?php

namespace Glory\Integration\Avada;

use Glory\Utility\TemplateRegistry;

class AvadaElementRegistrar
{
    public static function register(): void
    {
        add_action('fusion_builder_before_init', [self::class, 'registerElement']);
        if ( function_exists('did_action') && did_action('fusion_builder_before_init') > 0 ) {
            self::registerElement();
        }
        add_action('init', [self::class, 'ensureShortcode']);
    }

    public static function registerElement(): void
    {
        if (!function_exists('fusion_builder_map') || !class_exists('Fusion_Element')) {
            return;
        }

        if (!class_exists('FusionSC_GloryContentRender')) {
            $elementPath = get_template_directory() . '/Glory/src/Integration/Avada/Elements/FusionSC_GloryContentRender.php';
            $childPath   = get_stylesheet_directory() . '/Glory/src/Integration/Avada/Elements/FusionSC_GloryContentRender.php';
            if (file_exists($childPath)) {
                require_once $childPath;
            } elseif (file_exists($elementPath)) {
                require_once $elementPath;
            } else {
                return;
            }
        }

        fusion_builder_map(
            fusion_builder_frontend_data(
                'FusionSC_GloryContentRender',
                [
                    'name'            => esc_html__('Glory Content Render', 'glory-ab'),
                    'shortcode'       => 'glory_content_render',
                    'icon'            => 'fusiona-code',
                    'allow_generator' => true,
                    'inline_editor'   => false,
                    'help_url'        => 'https://example.com/docs/glory-content-render',
                    'params'          => [
                        [
                            'type'        => 'select',
                            'heading'     => esc_html__('Tipo de contenido', 'glory-ab'),
                            'param_name'  => 'post_type',
                            'value'       => self::getPublicPostTypesForSelect(),
                            'default'     => 'post',
                            'description' => esc_html__('Selecciona el tipo de contenido público.', 'glory-ab'),
                        ],
                        [
                            'type'        => 'select',
                            'heading'     => esc_html__('Plantilla de contenido', 'glory-ab'),
                            'param_name'  => 'template_id',
                            'value'       => self::getTemplatesForSelect(),
                            'default'     => '__default',
                            'description' => esc_html__('Plantilla registrada en TemplateRegistry.', 'glory-ab'),
                        ],
                        [
                            'type'       => 'radio_button_set',
                            'heading'    => esc_html__('Usar ContentRender', 'glory-ab'),
                            'param_name' => 'usar_content_render',
                            'default'    => 'yes',
                            'value'      => [ 'yes' => esc_html__('Sí', 'glory-ab'), 'no' => esc_html__('No', 'glory-ab') ],
                        ],
                        [ 'type' => 'range', 'heading' => esc_html__('Publicaciones por página', 'glory-ab'), 'param_name' => 'publicaciones_por_pagina', 'default' => 10, 'min' => 1, 'max' => 100, 'step' => 1 ],
                        [ 'type' => 'textfield', 'heading' => esc_html__('Clase contenedor', 'glory-ab'), 'param_name' => 'clase_contenedor', 'default' => 'glory-content-list' ],
                        [ 'type' => 'textfield', 'heading' => esc_html__('Clase de item', 'glory-ab'), 'param_name' => 'clase_item', 'default' => 'glory-content-item' ],
                        [ 'type' => 'radio_button_set', 'heading' => esc_html__('Paginación AJAX', 'glory-ab'), 'param_name' => 'paginacion', 'default' => '', 'value' => [ 'yes' => esc_html__('Sí', 'glory-ab'), 'no' => esc_html__('No', 'glory-ab') ] ],
                        [ 'type' => 'select', 'heading' => esc_html__('Orden', 'glory-ab'), 'param_name' => 'orden', 'default' => 'fecha', 'value' => [ 'fecha' => esc_html__('Fecha', 'glory-ab'), 'random' => esc_html__('Aleatorio', 'glory-ab'), 'meta' => esc_html__('Por meta', 'glory-ab') ] ],
                        [ 'type' => 'textfield', 'heading' => esc_html__('Meta key (para orden por meta)', 'glory-ab'), 'param_name' => 'meta_key', 'default' => '', 'dependency' => [ [ 'element' => 'orden', 'value' => 'meta', 'operator' => '==' ] ] ],
                        [ 'type' => 'select', 'heading' => esc_html__('Dirección (para meta)', 'glory-ab'), 'param_name' => 'meta_order', 'default' => 'ASC', 'value' => [ 'ASC' => 'ASC', 'DESC' => 'DESC' ], 'dependency' => [ [ 'element' => 'orden', 'value' => 'meta', 'operator' => '==' ] ] ],
                        [ 'type' => 'range', 'heading' => esc_html__('Mínimo de páginas', 'glory-ab'), 'param_name' => 'min_paginas', 'default' => 1, 'min' => 1, 'max' => 50, 'step' => 1 ],
                        [ 'type' => 'range', 'heading' => esc_html__('Tiempo de caché (segundos)', 'glory-ab'), 'param_name' => 'tiempo_cache', 'default' => 3600, 'min' => 0, 'max' => 86400, 'step' => 60, 'description' => esc_html__('0 para desactivar caché.', 'glory-ab') ],
                        [ 'type' => 'radio_button_set', 'heading' => esc_html__('Forzar sin caché', 'glory-ab'), 'param_name' => 'forzar_sin_cache', 'default' => '', 'value' => [ 'yes' => esc_html__('Sí', 'glory-ab'), 'no' => esc_html__('No', 'glory-ab') ] ],
                        [ 'type' => 'textfield', 'heading' => esc_html__('Acciones (CSV)', 'glory-ab'), 'param_name' => 'acciones', 'default' => '' ],
                        [ 'type' => 'radio_button_set', 'heading' => esc_html__('Submenú habilitado', 'glory-ab'), 'param_name' => 'submenu', 'default' => '', 'value' => [ 'yes' => esc_html__('Sí', 'glory-ab'), 'no' => esc_html__('No', 'glory-ab') ] ],
                        [ 'type' => 'select', 'heading' => esc_html__('Evento de acción', 'glory-ab'), 'param_name' => 'evento_accion', 'default' => 'dblclick', 'value' => [ 'click' => 'click', 'dblclick' => 'dblclick', 'longpress' => 'longpress' ] ],
                        [ 'type' => 'textfield', 'heading' => esc_html__('Selector CSS del item', 'glory-ab'), 'param_name' => 'selector_item', 'default' => '[id^="post-"]' ],
                        [ 'type' => 'textarea', 'heading' => esc_html__('Argumentos de consulta avanzados (JSON)', 'glory-ab'), 'param_name' => 'argumentos_json', 'default' => '' ],
                        [ 'type' => 'textfield', 'heading' => esc_html__('Título (opcional)', 'glory-ab'), 'param_name' => 'titulo', 'default' => '' ],
                        [ 'type' => 'textfield', 'heading' => esc_html__('Función PHP a invocar (opcional)', 'glory-ab'), 'param_name' => 'function_name', 'default' => '' ],
                        [ 'type' => 'textfield', 'heading' => esc_html__('Shortcode (opcional)', 'glory-ab'), 'param_name' => 'shortcode', 'default' => '' ],
                        [ 'type' => 'tinymce', 'heading' => esc_html__('Contenido HTML (fallback)', 'glory-ab'), 'param_name' => 'raw_content', 'default' => '' ],
                    ],
                ]
            )
        );
    }

    public static function ensureShortcode(): void
    {
        if ( shortcode_exists('glory_content_render') ) {
            return;
        }

        if ( class_exists('Fusion_Element') ) {
            $elementPath = get_template_directory() . '/Glory/src/Integration/Avada/Elements/FusionSC_GloryContentRender.php';
            $childPath   = get_stylesheet_directory() . '/Glory/src/Integration/Avada/Elements/FusionSC_GloryContentRender.php';
            if (file_exists($childPath)) {
                require_once $childPath;
            } elseif (file_exists($elementPath)) {
                require_once $elementPath;
            }
            if ( shortcode_exists('glory_content_render') ) {
                return;
            }
        }

        add_shortcode('glory_content_render', function($atts, $content = '') {
            $defaults = [
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
            $a = shortcode_atts($defaults, $atts, 'glory_content_render');

            $html = '';
            if (!empty($a['titulo'])) {
                $html .= '<h3>' . esc_html($a['titulo']) . '</h3>';
            }

            $usarCR = isset($a['usar_content_render']) && 'yes' === $a['usar_content_render'];
            $postType = sanitize_key($a['post_type']);

            if ($usarCR && class_exists('\\Glory\\Components\\ContentRender')) {
                $callable = ['\\Glory\\Components\\ContentRender', 'defaultTemplate'];
                $templateId = (string) $a['template_id'];
                if ($templateId !== '' && $templateId !== '__default' && class_exists('\\Glory\\Utility\\TemplateRegistry')) {
                    $applies = call_user_func(['\\Glory\\Utility\\TemplateRegistry', 'appliesTo'], $templateId);
                    if (empty($applies) || in_array($postType, (array) $applies, true)) {
                        $c = call_user_func(['\\Glory\\Utility\\TemplateRegistry', 'get'], $templateId);
                        if (is_callable($c)) {
                            $callable = $c;
                        }
                    }
                }

                $argumentosConsulta = [];
                if (!empty($a['argumentos_json'])) {
                    $parsed = json_decode((string) $a['argumentos_json'], true);
                    if (is_array($parsed)) {
                        $argumentosConsulta = $parsed;
                    }
                }

                $orden = (string) $a['orden'];
                $metaKey = trim((string) $a['meta_key']);
                $metaOrder = strtoupper((string) $a['meta_order']);

                $config = [
                    'publicacionesPorPagina' => (int) $a['publicaciones_por_pagina'],
                    'claseContenedor'        => (string) $a['clase_contenedor'],
                    'claseItem'              => (string) $a['clase_item'],
                    'paginacion'             => ('yes' === ($a['paginacion'] ?? '')),
                    'plantillaCallback'      => $callable,
                    'argumentosConsulta'     => $argumentosConsulta,
                    'orden'                  => ('meta' === $orden && '' !== $metaKey) ? $metaOrder : ( 'random' === $orden ? 'random' : 'fecha' ),
                    'metaKey'                => '' !== $metaKey ? $metaKey : null,
                    'minPaginas'             => (int) $a['min_paginas'],
                    'tiempoCache'            => (int) $a['tiempo_cache'],
                    'forzarSinCache'         => ('yes' === ($a['forzar_sin_cache'] ?? '')),
                    'acciones'               => !empty($a['acciones']) ? array_map('trim', explode(',', (string) $a['acciones'])) : [],
                    'submenu'                => ('yes' === ($a['submenu'] ?? '')),
                    'eventoAccion'           => (string) $a['evento_accion'],
                    'selectorItem'           => (string) $a['selector_item'],
                ];

                ob_start();
                try {
                    call_user_func(['\\Glory\\Components\\ContentRender', 'print'], $postType, $config);
                } catch (\Throwable $t) {
                    if ( current_user_can('manage_options') ) {
                        $html .= '<div class="glory-widget-error">' . esc_html($t->getMessage()) . '</div>';
                    }
                }
                $html .= ob_get_clean();
                return $html;
            }

            if (!empty($a['function_name']) && function_exists($a['function_name'])) {
                ob_start();
                call_user_func($a['function_name']);
                return $html . ob_get_clean();
            }
            if (!empty($a['shortcode'])) {
                return $html . do_shortcode($a['shortcode']);
            }
            return $html . (string) ($a['raw_content'] ?? '');
        });
    }

    private static function getPublicPostTypesForSelect(): array
    {
        $options = [];
        $pts = get_post_types([ 'public' => true ], 'objects');
        if (is_array($pts)) {
            foreach ($pts as $pt) {
                $label = isset($pt->labels->singular_name) && $pt->labels->singular_name ? $pt->labels->singular_name : ($pt->label ?? $pt->name);
                $options[$pt->name] = $label;
            }
        }
        return $options;
    }

    private static function getTemplatesForSelect(): array
    {
        $templates = [ '__default' => esc_html__('Plantilla por defecto (genérica)', 'glory-ab') ];
        if (class_exists(TemplateRegistry::class)) {
            $opts = TemplateRegistry::options(null);
            if (is_array($opts)) {
                $templates = array_merge($templates, $opts);
            }
        }
        return $templates;
    }
}


