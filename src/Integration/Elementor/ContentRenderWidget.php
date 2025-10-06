<?php

namespace Glory\Integration\Elementor;

use Glory\Components\ContentRender;
use Glory\Utility\TemplateRegistry;

if (!class_exists('Elementor\\Widget_Base')) {
    return;
}

class ContentRenderWidget extends \Elementor\Widget_Base
{
    public function get_name()
    {
        return 'glory_content_render';
    }

    public function get_title()
    {
        return 'Glory Content Render';
    }

    public function get_icon()
    {
        return 'eicon-code';
    }

    public function get_categories()
    {
        return ['glory'];
    }

    protected function register_controls()
    {
        $this->{"start_controls_section"}('section_content', [
            'label' => 'Contenido',
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ]);

        $postTypeOptions = [];
        $pts = get_post_types(['public' => true], 'objects');
        if (is_array($pts)) {
            foreach ($pts as $pt) {
                $postTypeOptions[$pt->name] = $pt->labels->singular_name ?: $pt->label ?: $pt->name;
            }
        }

        $this->{"add_control"}('post_type', [
            'label'   => 'Tipo de contenido',
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => $postTypeOptions,
            'default' => 'post',
        ]);

        $plantillas = [];
        if (class_exists(TemplateRegistry::class)) {
            $plantillas = TemplateRegistry::options(null);
        }
        if (!is_array($plantillas)) {
            $plantillas = [];
        }
        $plantillas = array_merge(['__default' => 'Plantilla por defecto (genérica)'], $plantillas);

        $this->{"add_control"}('template_id', [
            'label'       => 'Plantilla de contenido',
            'type'        => \Elementor\Controls_Manager::SELECT,
            'options'     => $plantillas,
            'default'     => '__default',
            'description' => 'Elige una plantilla registrada o la plantilla por defecto (genérica).',
        ]);

        $this->{"add_control"}('usar_content_render', [
            'label'        => 'Usar motor ContentRender',
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'label_on'     => 'Sí',
            'label_off'    => 'No',
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->{"end_controls_section"}();

        $this->{"start_controls_section"}('section_cr', [
            'label' => 'Opciones ContentRender',
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ]);

        $this->{"add_control"}('publicaciones_por_pagina', [
            'label'   => 'Publicaciones por página',
            'type'    => \Elementor\Controls_Manager::NUMBER,
            'min'     => 1,
            'max'     => 100,
            'step'    => 1,
            'default' => 10,
        ]);

        $this->{"add_control"}('clase_contenedor', [
            'label'       => 'Clase contenedor',
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => 'glory-content-list',
            'placeholder' => 'glory-content-list',
        ]);

        $this->{"add_control"}('clase_item', [
            'label'       => 'Clase de item',
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => 'glory-content-item',
            'placeholder' => 'glory-content-item',
        ]);

        $this->{"add_control"}('paginacion', [
            'label'        => 'Paginación AJAX',
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'label_on'     => 'Sí',
            'label_off'    => 'No',
            'return_value' => 'yes',
            'default'      => '',
        ]);

        $this->{"add_control"}('orden', [
            'label'   => 'Orden',
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => [
                'fecha'   => 'Fecha',
                'random'  => 'Aleatorio',
                'meta'    => 'Por meta (requiere metaKey)',
            ],
            'default' => 'fecha',
        ]);

        $this->{"add_control"}('meta_key', [
            'label'       => 'Meta key (para orden por meta)',
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => '',
            'placeholder' => 'mi_meta_key',
        ]);

        $this->{"add_control"}('meta_order', [
            'label'   => 'Dirección (para meta)',
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => [
                'ASC'  => 'Ascendente',
                'DESC' => 'Descendente',
            ],
            'default' => 'ASC',
        ]);

        $this->{"add_control"}('min_paginas', [
            'label'   => 'Mínimo de páginas',
            'type'    => \Elementor\Controls_Manager::NUMBER,
            'min'     => 1,
            'max'     => 50,
            'step'    => 1,
            'default' => 1,
        ]);

        $this->{"add_control"}('tiempo_cache', [
            'label'       => 'Tiempo de caché (segundos)',
            'type'        => \Elementor\Controls_Manager::NUMBER,
            'min'         => 0,
            'step'        => 60,
            'default'     => 3600,
            'description' => '0 para desactivar caché (usa forzar sin caché).',
        ]);

        $this->{"add_control"}('forzar_sin_cache', [
            'label'        => 'Forzar sin caché',
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'label_on'     => 'Sí',
            'label_off'    => 'No',
            'return_value' => 'yes',
            'default'      => '',
        ]);

        $this->{"add_control"}('acciones', [
            'label'       => 'Acciones (CSV)',
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => '',
            'placeholder' => 'eliminar,editar',
        ]);

        $this->{"add_control"}('submenu', [
            'label'        => 'Submenú habilitado',
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'label_on'     => 'Sí',
            'label_off'    => 'No',
            'return_value' => 'yes',
            'default'      => '',
        ]);

        $this->{"add_control"}('evento_accion', [
            'label'   => 'Evento de acción',
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => [
                'click'     => 'click',
                'dblclick'  => 'dblclick',
                'longpress' => 'longpress',
            ],
            'default' => 'dblclick',
        ]);

        $this->{"add_control"}('selector_item', [
            'label'       => 'Selector CSS del item',
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => '[id^="post-"]',
            'placeholder' => '[id^="post-"]',
        ]);

        $this->{"add_control"}('argumentos_json', [
            'label'       => 'Argumentos de consulta avanzados (JSON)',
            'type'        => \Elementor\Controls_Manager::TEXTAREA,
            'default'     => '',
            'placeholder' => '{"post__in":[1,2,3],"tax_query":[...]}'
        ]);

        $this->{"end_controls_section"}();

        $this->{"start_controls_section"}('section_alt', [
            'label' => 'Alternativas (si no usas ContentRender)',
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ]);

        $this->{"add_control"}('titulo', [
            'label'       => 'Título (opcional)',
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => '',
            'placeholder' => 'Título del bloque',
        ]);

        $this->{"add_control"}('function_name', [
            'label'       => 'Función PHP a invocar (opcional)',
            'type'        => \Elementor\Controls_Manager::TEXT,
            'placeholder' => 'ej: home',
            'description' => 'Si la función existe y es callable, se usará su salida.',
        ]);

        $this->{"add_control"}('shortcode', [
            'label'       => 'Shortcode (opcional)',
            'type'        => \Elementor\Controls_Manager::TEXT,
            'placeholder' => '[mi_shortcode]'
        ]);

        $this->{"add_control"}('raw_content', [
            'label'       => 'Contenido HTML (fallback)',
            'type'        => \Elementor\Controls_Manager::WYSIWYG,
            'default'     => '',
        ]);

        $this->{"end_controls_section"}();
    }

    protected function render()
    {
        $settings = $this->{"get_settings_for_display"}();

        if (!empty($settings['titulo'])) {
            echo '<h3>' . esc_html($settings['titulo']) . '</h3>';
        }

        $usarCR = isset($settings['usar_content_render']) && $settings['usar_content_render'] === 'yes';
        $postType = isset($settings['post_type']) ? $settings['post_type'] : 'post';

        if ($usarCR && class_exists(ContentRender::class)) {
            $callable = [ContentRender::class, 'defaultTemplate'];
            $templateId = isset($settings['template_id']) ? $settings['template_id'] : '';
            if ($templateId !== '' && $templateId !== '__default' && class_exists(TemplateRegistry::class)) {
                $applies = TemplateRegistry::appliesTo($templateId);
                if (!empty($applies) && !in_array($postType, $applies, true)) {
                    if (current_user_can('manage_options')) {
                        echo '<div class="glory-warning">La plantilla seleccionada no aplica a “' . esc_html($postType) . '”. Se usará la plantilla por defecto.</div>';
                    }
                } else {
                    $c = TemplateRegistry::get($templateId);
                    if (is_callable($c)) {
                        $callable = $c;
                    }
                }
            }

            $argumentosConsulta = [];
            if (!empty($settings['argumentos_json'])) {
                $json = trim(strval($settings['argumentos_json']));
                $parsed = json_decode($json, true);
                if (is_array($parsed)) {
                    $argumentosConsulta = $parsed;
                } else {
                    if (current_user_can('manage_options')) {
                        echo '<div class="glory-warning">JSON inválido en argumentos de consulta. Ignorando.</div>';
                    }
                }
            }

            $orden = isset($settings['orden']) ? $settings['orden'] : 'fecha';
            $metaKey = isset($settings['meta_key']) ? trim(strval($settings['meta_key'])) : '';
            $metaOrder = isset($settings['meta_order']) ? strtoupper($settings['meta_order']) : 'ASC';

            $config = [
                'publicacionesPorPagina' => isset($settings['publicaciones_por_pagina']) ? (int) $settings['publicaciones_por_pagina'] : 10,
                'claseContenedor'        => isset($settings['clase_contenedor']) ? $settings['clase_contenedor'] : 'glory-content-list',
                'claseItem'              => isset($settings['clase_item']) ? $settings['clase_item'] : 'glory-content-item',
                'paginacion'             => isset($settings['paginacion']) && $settings['paginacion'] === 'yes',
                'plantillaCallback'      => $callable,
                'argumentosConsulta'     => $argumentosConsulta,
                'orden'                  => ($orden === 'meta' && $metaKey !== '') ? $metaOrder : ($orden === 'random' ? 'random' : 'fecha'),
                'metaKey'                => $metaKey !== '' ? $metaKey : null,
                'minPaginas'             => isset($settings['min_paginas']) ? (int) $settings['min_paginas'] : 1,
                'tiempoCache'            => isset($settings['tiempo_cache']) ? (int) $settings['tiempo_cache'] : 3600,
                'forzarSinCache'         => isset($settings['forzar_sin_cache']) && $settings['forzar_sin_cache'] === 'yes',
                'acciones'               => !empty($settings['acciones']) ? array_map('trim', explode(',', strval($settings['acciones']))) : [],
                'submenu'                => isset($settings['submenu']) && $settings['submenu'] === 'yes',
                'eventoAccion'           => isset($settings['evento_accion']) ? $settings['evento_accion'] : 'dblclick',
                'selectorItem'           => isset($settings['selector_item']) ? $settings['selector_item'] : '[id^="post-"]',
            ];

            ContentRender::print($postType, $config);
            return;
        }

        $functionName = isset($settings['function_name']) ? $settings['function_name'] : '';
        if (is_string($functionName) && $functionName !== '' && function_exists($functionName)) {
            ob_start();
            try {
                call_user_func($functionName);
            } catch (\Throwable $t) {
                if (current_user_can('manage_options')) {
                    echo '<div class="glory-widget-error">Error en función: ' . esc_html($t->getMessage()) . '</div>';
                }
            }
            echo ob_get_clean();
            return;
        }

        $shortcode = isset($settings['shortcode']) ? trim($settings['shortcode']) : '';
        if ($shortcode !== '') {
            echo do_shortcode($shortcode);
            return;
        }

        $raw = isset($settings['raw_content']) ? $settings['raw_content'] : '';
        echo $raw;
    }
}


