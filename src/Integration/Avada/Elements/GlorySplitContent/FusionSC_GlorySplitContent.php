<?php

// Clase global sin namespace para ajustarse al patrón de Avada.

if (! class_exists('FusionSC_GlorySplitContent') && class_exists('Fusion_Element')) {

    /**
     * 
     */


    class FusionSC_GlorySplitContent extends Fusion_Element
    {
        private $counter = 1;

        public function __construct()
        {
            parent::__construct();
            add_shortcode('glory_split_content', [$this, 'render']);
        }

        public static function get_element_defaults()
        {
            return [
                'post_type'                => 'post',
                'number_of_posts'          => 10,
                'query_args'               => '',
                'include_post_ids'         => '',
                'include_post_ids_select'  => '',
                'auto_open_first_item'     => 'no',
                // Lista
                'list_direction'           => 'vertical',
                'list_item_spacing'        => '12px',
                'list_panel_width'         => '30%',
                'list_scroll_enabled'      => 'yes',
                // Padding lista
                'list_padding_top'         => '0px',
                'list_padding_bottom'      => '0px',
                'list_padding_left'        => '0px',
                'list_padding_right'       => '0px',
                'title_color'              => '',
                'title_font_family'        => '',
                'title_font_variant'       => '',
                'title_font_size'          => '',
                'title_line_height'        => '',
                'title_letter_spacing'     => '',
                'title_text_transform'     => '',
                // Contenido
                'content_scroll_enabled'   => 'yes',
                'content_color'            => '',
                'content_font_family'      => '',
                'content_font_variant'     => '',
                'content_font_size'        => '',
                'content_line_height'      => '',
                'content_letter_spacing'   => '',
                'content_text_transform'   => '',
                // Contenedor
                'height'                   => '100%',
                // Padding contenedor
                'padding_top'              => '30px',
                'padding_bottom'           => '30px',
                'padding_left'             => '20px',
                'padding_right'            => '20px',
                // Padding contenido
                'content_padding_top'     => '10px',
                'content_padding_bottom'  => '10px',
                'content_padding_left'    => '10px',
                'content_padding_right'   => '10px',
            ];
        }

        public function render($args, $content = '')
        {
            $this->defaults = self::get_element_defaults();
            $this->args     = FusionBuilder::set_shortcode_defaults($this->defaults, $args, 'glory_split_content');
            // Normalizar alias provenientes de versiones anteriores del mapeo de Avada
            $alias = [
                'publicaciones_por_pagina' => 'number_of_posts',
                'argumentos_json'         => 'query_args',
                'post_ids'                => 'include_post_ids',
                'post_ids_select'         => 'include_post_ids_select',
                'auto_open_first'         => 'auto_open_first_item',
                'container_height'        => 'height',
                'list_gap'                => 'list_item_spacing',
                'titles_color'            => 'title_color',
                'titles_font_family'      => 'title_font_family',
                'titles_font_size'        => 'title_font_size',
            ];
            foreach ($alias as $old => $new) {
                if (isset($this->args[$old]) && !isset($this->args[$new])) {
                    $this->args[$new] = $this->args[$old];
                }
                unset($this->args[$old]);
            }
            $pageIdForRender = isset($_REQUEST['__gbn_page_id']) ? absint($_REQUEST['__gbn_page_id']) : get_queried_object_id();
            $rawArgs        = $this->args; // conservar args originales para modo preview

            // Clase de instancia estable: basada en el gbnId calculado (definida tras getGbnData)
            $instanceClass = null; // se definirá luego de obtener $gbnData
            $postType      = isset($this->args['post_type']) ? sanitize_key($this->args['post_type']) : 'post';
            $ppp           = isset($this->args['number_of_posts']) ? (int) $this->args['number_of_posts'] : 10;

            // Argumentos de consulta extendidos
            $argumentosConsulta = [];
            if (! empty($this->args['query_args'])) {
                $json = trim((string) $this->args['query_args']);
                $parsed = json_decode($json, true);
                if (is_array($parsed)) {
                    $argumentosConsulta = $parsed;
                }
            }
            // IDs explícitos via CSV o selector múltiple
            $idsCsv = isset($this->args['include_post_ids']) ? (string) $this->args['include_post_ids'] : '';
            $idsSelRaw = $this->args['include_post_ids_select'] ?? '';
            $idsSel = [];
            if (is_array($idsSelRaw)) {
                $idsSel = array_map('intval', $idsSelRaw);
            }
            $idsCsvArr = [];
            if ($idsCsv !== '') {
                $idsCsvArr = array_filter(array_map('absint', array_map('trim', explode(',', $idsCsv))));
            }
            $ids = array_values(array_unique(array_filter(array_merge($idsCsvArr, $idsSel))));
            if (! empty($ids)) {
                $argumentosConsulta['post__in'] = $ids;
                $argumentosConsulta['orderby'] = 'post__in';
            }

            // Overrides GBN por instancia
            $gbnData = $this->getGbnData($this->args, $postType, $pageIdForRender);
            // Definir clase de instancia estable en base al ID de GBN
            $stableSuffix = isset($gbnData['id']) ? substr((string) $gbnData['id'], 0, 8) : substr(md5('glory|'.$postType.'|'.$pageIdForRender), 0, 8);
            $instanceClass = 'glory-split-' . $stableSuffix;
            if (!empty($gbnData['opts'])) {
                // fusionar opciones (override no destructivo)
                $this->args = array_merge($this->args, $gbnData['opts']);
            }
            // En modo preview, los valores entrantes deben tener prioridad sobre los guardados
            if (!empty($rawArgs['__gbn_preview']) && $rawArgs['__gbn_preview'] === 'yes') {
                $onlyKnown = array_intersect_key($rawArgs, $this->defaults);
                $this->args = array_merge($this->args, $onlyKnown);
            }

            // Recalcular valores clave con overrides aplicados
            $postType = isset($this->args['post_type']) ? sanitize_key($this->args['post_type']) : $postType;
            // Corregir: usar number_of_posts (no publicaciones_por_pagina)
            $ppp      = isset($this->args['number_of_posts']) ? (int) $this->args['number_of_posts'] : $ppp;

            // Construir HTML de lista con ContentRender + plantilla de título
            $listContainerClass = $instanceClass . '__list glory-content-list';
            $listItemClass      = $instanceClass . '__list-item glory-content-item';

            ob_start();
            try {
                $listScroll = (string) ($this->args['list_scroll_enabled'] ?? 'yes') === 'yes';
                // Aplicar orden guardado por GBN si existe
                if (!empty($gbnData['order']) && is_array($gbnData['order'])) {
                    $argumentosConsulta['post__in'] = array_map('absint', $gbnData['order']);
                    $argumentosConsulta['orderby'] = 'post__in';
                }

                \Glory\Components\ContentRender::print($postType, [
                    // Respetar publicaciones_por_pagina si es > 0, incluso con scroll habilitado
                    'publicacionesPorPagina' => ($ppp > 0 ? $ppp : ($listScroll ? -1 : $ppp)),
                    'claseContenedor'        => $listContainerClass,
                    'claseItem'              => $listItemClass,
                    'paginacion'             => false,
                    'plantillaCallback'      => ['\\Glory\\Integration\\Avada\\Elements\\GlorySplitContent\\GlorySplitContentTemplate', 'titleItem'],
                    'argumentosConsulta'     => $argumentosConsulta,
                    'forzarSinCache'         => true,
                ]);
            } catch (\Throwable $t) {
            }
            $titlesHtml = ob_get_clean();

            $ajaxUrl = admin_url('admin-ajax.php');
            $pageId = $pageIdForRender;

            // Wrapper
            $html  = '<div class="glory-split ' . esc_attr($instanceClass) . '" data-ajax-url="' . esc_url($ajaxUrl) . '" data-post-type="' . esc_attr($postType) . '" data-gbn-page-id="' . esc_attr((string) $pageId) . '"'
                . (!empty($gbnData['id']) ? ' data-gbn-id="' . esc_attr($gbnData['id']) . '"' : '')
                . (!empty($gbnData['schema']) ? ' data-gbn-schema="' . esc_attr(wp_json_encode($gbnData['schema'])) . '"' : '')
                . (!empty($gbnData['config']) ? ' data-gbn-config="' . esc_attr(wp_json_encode($gbnData['config'])) . '"' : '')
                . '>';
            $html .= '<div class="glory-split__panel glory-split__titles">' . $titlesHtml . '</div>';
            // Precarga de contenido del primer ítem si auto_open_first_item = yes
            $preloadContent = '';
            $autoOpen = (string) ($this->args['auto_open_first_item'] ?? 'no') === 'yes';
            if ($autoOpen) {
                // Repetir consulta mínima para obtener el primer post mostrado
                try {
                    $argsConsulta = $argumentosConsulta;
                    // Respetar el order si viene por GBN
                    $argsConsulta['posts_per_page'] = 1;
                    $argsConsulta['ignore_sticky_posts'] = true;
                    $argsConsulta = array_merge([
                        'post_type' => $postType,
                    ], $argsConsulta);
                    $q = new \WP_Query($argsConsulta);
                    if ($q && $q->have_posts()) {
                        $q->the_post();
                        ob_start();
                        // Usar plantilla de contenido completo
                        \Glory\Components\ContentRender::fullContentTemplate(get_post(), 'glory-split__content-item');
                        $preloadContent = ob_get_clean();
                    }
                    wp_reset_postdata();
                } catch (\Throwable $e) {
                    $preloadContent = '';
                }
            }
            $html .= '<div class="glory-split__panel glory-split__content" id="' . esc_attr($instanceClass) . '-content">' . $preloadContent . '</div>';
            $html .= '</div>';

            // CSS por instancia
            $css = $this->buildInstanceCss($instanceClass, $this->args);
            if ($css !== '') {
                $html .= '<style id="' . esc_attr($instanceClass) . '-css">' . $css . '</style>';
            }

            // JS por instancia (carga diferida)
            $html .= $this->buildInstanceJs($instanceClass, $postType, (string) ($this->args['auto_open_first_item'] ?? 'no') === 'yes');

            $this->counter++;
            $this->on_render();
            return $html;
        }

        private function buildInstanceCss(string $instanceClass, array $a): string
        {
            $root = '.' . $instanceClass;
            $list = $root . ' .glory-split__titles .glory-content-list';
            $item = $root . ' .glory-split__titles .glory-content-item';
            $title = $root . ' .glory-split__title-text';
            $content = $root . ' .glory-split__content';

            $list_direction = (string) ($a['list_direction'] ?? 'vertical');
            $list_gap       = (string) ($a['list_item_spacing'] ?? '12px');
            $panel_width    = (string) ($a['list_panel_width'] ?? '30%');
            $list_padding_top    = (string) ($a['list_padding_top'] ?? '0px');
            $list_padding_bottom = (string) ($a['list_padding_bottom'] ?? '0px');
            $list_padding_left   = (string) ($a['list_padding_left'] ?? '0px');
            $list_padding_right  = (string) ($a['list_padding_right'] ?? '0px');
            $titles_color   = (string) ($a['title_color'] ?? '');
            $titles_ff      = (string) ($a['title_font_family'] ?? '');
            $titles_variant = (string) ($a['title_font_variant'] ?? '');
            $titles_fs      = (string) ($a['title_font_size'] ?? '');
            $titles_lh      = (string) ($a['title_line_height'] ?? '');
            $titles_ls      = (string) ($a['title_letter_spacing'] ?? '');
            $titles_tt      = (string) ($a['title_text_transform'] ?? '');
            $list_scroll_enabled = (string) ($a['list_scroll_enabled'] ?? 'yes') === 'yes';
            $content_scroll_enabled = (string) ($a['content_scroll_enabled'] ?? 'yes') === 'yes';
            $content_color  = (string) ($a['content_color'] ?? '');
            $content_ff     = (string) ($a['content_font_family'] ?? '');
            $content_variant= (string) ($a['content_font_variant'] ?? '');
            $content_fs     = (string) ($a['content_font_size'] ?? '');
            $content_lh     = (string) ($a['content_line_height'] ?? '');
            $content_ls     = (string) ($a['content_letter_spacing'] ?? '');
            $content_tt     = (string) ($a['content_text_transform'] ?? '');
            $container_height = (string) ($a['height'] ?? '100%');
            $padding_top    = (string) ($a['padding_top'] ?? '30px');
            $padding_bottom = (string) ($a['padding_bottom'] ?? '30px');
            $padding_left   = (string) ($a['padding_left'] ?? '20px');
            $padding_right  = (string) ($a['padding_right'] ?? '20px');
            $content_padding_top    = (string) ($a['content_padding_top'] ?? '10px');
            $content_padding_bottom = (string) ($a['content_padding_bottom'] ?? '10px');
            $content_padding_left   = (string) ($a['content_padding_left'] ?? '10px');
            $content_padding_right  = (string) ($a['content_padding_right'] ?? '10px');

            $css  = $root . '{display:flex;gap:24px;align-items:stretch;width:100%;height:' . $container_height . ';padding:' . $padding_top . ' ' . $padding_right . ' ' . $padding_bottom . ' ' . $padding_left . ';}';
            $css .= $root . ' .glory-split__titles{flex:0 0 ' . $panel_width . ';min-width:200px;}';
            $css .= $root . ' .glory-split__content{flex:1 1 auto;min-width:0;}';
            $css .= $root . ' .glory-split__titles, ' . $root . ' .glory-split__content{height:100%;}';

            // Lista
            $dir = $list_direction === 'horizontal' ? 'row' : 'column';
            $wrap = $list_direction === 'horizontal' ? 'wrap' : 'nowrap';
            $css .= $list . '{display:flex;flex-direction:' . $dir . ';flex-wrap:' . $wrap . ';gap:' . $list_gap . ';padding:' . $list_padding_top . ' ' . $list_padding_right . ' ' . $list_padding_bottom . ' ' . $list_padding_left . ';}';
            if ($list_scroll_enabled) {
                $css .= $list . '{overflow-y:auto;max-height:100%;scroll-behavior:smooth;}';
                $css .= $list . '::-webkit-scrollbar{width:6px;}';
                $css .= $list . '::-webkit-scrollbar-track{background:#f1f1f1;border-radius:3px;}';
                $css .= $list . '::-webkit-scrollbar-thumb{background:#c1c1c1;border-radius:3px;}';
                $css .= $list . '::-webkit-scrollbar-thumb:hover{background:#a8a8a8;}';
            }
            $css .= $item . '{margin:0;}';
            $css .= $root . ' .glory-split__title{display:block;width:100%;text-align:left;background:transparent;border:0;padding:0;margin:0;cursor:pointer;}';
            $css .= $title . '{display:inline-block;}';
            if ($titles_color !== '') {
                $css .= $title . '{color:' . $titles_color . ';}';
            }
            if ($titles_ff !== '') {
                $css .= $title . '{font-family:' . (false !== strpos($titles_ff, ' ') ? '"' . $titles_ff . '"' : $titles_ff) . ';}';
            }
            if ($titles_variant !== '') { $css .= $title . '{' . \Glory\Support\CSS\Typography::variantToCss($titles_variant) . '}'; }
            if ($titles_fs !== '') { $css .= $title . '{font-size:' . $titles_fs . ';}'; }
            if ($titles_lh !== '') { $css .= $title . '{line-height:' . $titles_lh . ';}'; }
            if ($titles_ls !== '') { $css .= $title . '{letter-spacing:' . $titles_ls . ';}'; }
            if ($titles_tt !== '') { $css .= $title . '{text-transform:' . $titles_tt . ';}'; }

            // Contenido
            $css .= $content . '{padding:' . $content_padding_top . ' ' . $content_padding_right . ' ' . $content_padding_bottom . ' ' . $content_padding_left . ';}';
            if ($content_scroll_enabled) {
                $css .= $content . '{overflow-y:auto;scroll-behavior:smooth;}';
                $css .= $content . '::-webkit-scrollbar{width:6px;}';
                $css .= $content . '::-webkit-scrollbar-track{background:#f1f1f1;border-radius:3px;}';
                $css .= $content . '::-webkit-scrollbar-thumb{background:#c1c1c1;border-radius:3px;}';
                $css .= $content . '::-webkit-scrollbar-thumb:hover{background:#a8a8a8;}';
            }
            if ($content_color !== '') {
                $css .= $content . '{color:' . $content_color . ';}';
            }
            if ($content_ff !== '') {
                $css .= $content . '{font-family:' . (false !== strpos($content_ff, ' ') ? '"' . $content_ff . '"' : $content_ff) . ';}';
            }
            if ($content_variant !== '') { $css .= $content . '{' . \Glory\Support\CSS\Typography::variantToCss($content_variant) . '}'; }
            if ($content_fs !== '') { $css .= $content . '{font-size:' . $content_fs . ';}'; }
            if ($content_lh !== '') { $css .= $content . '{line-height:' . $content_lh . ';}'; }
            if ($content_ls !== '') { $css .= $content . '{letter-spacing:' . $content_ls . ';}'; }
            if ($content_tt !== '') { $css .= $content . '{text-transform:' . $content_tt . ';}'; }

            // Estado activo
            $css .= $root . ' .glory-split__item.is-active ' . $title . '{text-decoration:underline;}';

            return $css;
        }

        private function buildInstanceJs(string $instanceClass, string $postType, bool $autoOpenFirst): string
        {
            $sel = '.' . $instanceClass;
            $js  = '<script>(function(){var root=document.querySelector(' . wp_json_encode($sel) . ');if(!root)return;';
            $js .= 'if(root.dataset.glorySplitInit==="true")return;root.dataset.glorySplitInit="true";';
            $js .= 'var ajax=root.getAttribute("data-ajax-url");var tipo=' . wp_json_encode($postType) . ';var content=root.querySelector(' . wp_json_encode('#' . $instanceClass . '-content') . ');';
            $js .= 'var cache={};function updateHash(slug){if(!slug)return;var base=location.origin+location.pathname;var nuevo=base+"#"+slug;if(location.href!==nuevo){history.replaceState(null, "", nuevo);}}';
            $js .= 'function load(id, item){if(cache[id]){content.innerHTML=cache[id];setActive(item);var s=item?item.getAttribute("data-post-slug"):null;updateHash(s);return;}';
            $js .= 'var fd=new FormData();fd.append("action","obtenerHtmlPost");fd.append("id",id);fd.append("tipo",tipo);fd.append("plantilla","full");';
            $js .= 'fetch(ajax,{method:"POST",body:fd,credentials:"same-origin"}).then(function(r){return r.json();}).then(function(res){if(!res||!res.success){throw new Error("Error");}cache[id]=res.data||"";content.innerHTML=cache[id];setActive(item);var s=item?item.getAttribute("data-post-slug"):null;updateHash(s);}).catch(function(e){content.innerHTML="<p>Error al cargar contenido.</p>";});}
';
            $js .= 'function setActive(item){root.querySelectorAll(".glory-split__item.is-active").forEach(function(el){el.classList.remove("is-active")});if(item){item.classList.add("is-active");}}';
            $js .= 'root.addEventListener("click",function(e){var btn=e.target.closest(".glory-split__item .glory-split__title");if(!btn||!root.contains(btn))return;var item=btn.closest(".glory-split__item");if(!item)return;var id=item.getAttribute("data-post-id");if(!id){return;}e.preventDefault();load(id,item);});';
            if ($autoOpenFirst) {
                $js .= 'var first=root.querySelector(".glory-split__item .glory-split__title");var content=root.querySelector(' . wp_json_encode('#' . $instanceClass . '-content') . ');if(first&&content&&!content.hasChildNodes()){first.click();}';
            }
            // Apertura automática por hash (#slug)
            $js .= 'var hashSlug=(location.hash||"").replace(/^#/,"");if(hashSlug){var target=root.querySelector(".glory-split__item[data-post-slug=' . '"+CSS.escape(hashSlug)+"' . ']");if(target){var btn=target.querySelector(".glory-split__title");var id=target.getAttribute("data-post-id");if(btn&&id){btn.click();}}}';
            $js .= '})();</script>';
            return $js;
        }

        private function getGbnData(array $args, string $postType, ?int $pageIdOverride = null): array
        {
            $pageId = ($pageIdOverride !== null && $pageIdOverride > 0)
                ? $pageIdOverride
                : (function_exists('fusion_library') && method_exists('Fusion_Settings', 'get_option_name') ? get_queried_object_id() : get_queried_object_id());
            // Hash estable solo por identidad de contenido (no estilos ni pagination)
            $normArgs = array_intersect_key($args, array_flip([
                'post_type',
                'query_args',
                'include_post_ids',
                'include_post_ids_select',
            ]));
            $hash = md5(wp_json_encode($normArgs) . '|' . $postType . '|' . (int) $pageId);
            $gbnId = substr($hash, 0, 12);

            // Leer overrides
            $opts = [];
            $order = [];
            if ($pageId) {
                $o = get_post_meta($pageId, 'gbn_opts_' . $gbnId, true);
                if (is_array($o)) {
                    $opts = $o;
                }
                $ord = get_post_meta($pageId, 'gbn_order_' . $gbnId, true);
                if (is_array($ord)) {
                    $order = array_map('absint', $ord);
                }
            }

            // Generar opciones dinámicas de post types
            $postTypeOptions = [];
            $publicPostTypes = get_post_types(['public' => true], 'objects');
            foreach ($publicPostTypes as $pt) {
                $postTypeOptions[$pt->name] = $pt->label;
            }
            // Agregar glory_link si existe
            if (post_type_exists('glory_link')) {
                $postTypeOptions['glory_link'] = 'Glory Link';
            }

            // Opciones de tipografía (fuentes: Avada Google/Adobe/Custom + personalizadas Glory)
            $fontFamilies = [];
            try {
                // Google fonts desde el paquete de Avada
                if (defined('FUSION_LIBRARY_PATH')) {
                    $gfPath = trailingslashit(FUSION_LIBRARY_PATH) . 'inc/googlefonts-array.php';
                    if (is_readable($gfPath)) {
                        $gf = include $gfPath; // array con key 'items'
                        if (is_array($gf) && isset($gf['items']) && is_array($gf['items'])) {
                            foreach ($gf['items'] as $font) {
                                if (!is_array($font)) { continue; }
                                $family = isset($font['family']) ? (string) $font['family'] : '';
                                if ($family !== '') { $fontFamilies[$family] = $family; }
                            }
                        }
                    }
                }
                // Custom fonts (Avada options)
                if (function_exists('awb_get_fusion_settings')) {
                    $fusion_settings = awb_get_fusion_settings();
                    if ($fusion_settings && is_object($fusion_settings)) {
                        $saved_custom_fonts = $fusion_settings->get('custom_fonts');
                        if (!empty($saved_custom_fonts) && is_array($saved_custom_fonts) && isset($saved_custom_fonts['name']) && is_array($saved_custom_fonts['name'])) {
                            foreach ($saved_custom_fonts['name'] as $fontName) {
                                $fontName = (string) $fontName;
                                if ($fontName !== '') { $fontFamilies[$fontName] = $fontName; }
                            }
                        }
                    }
                }
                // Adobe fonts (si existen)
                $adobe_fonts = get_option('avada_adobe_fonts', []);
                if (is_array($adobe_fonts)) {
                    foreach ($adobe_fonts as $af) {
                        if (!is_array($af)) { continue; }
                        $slug = isset($af['font_slug']) ? (string) $af['font_slug'] : '';
                        $label = isset($af['label']) ? (string) $af['label'] : $slug;
                        if ($slug !== '') { $fontFamilies[$slug] = $label !== '' ? $label : $slug; }
                    }
                }
            } catch (\Throwable $e) {}
            // Agregar familias detectadas por Glory si no están
            if (class_exists('\\Glory\\Integration\\Avada\\AvadaFontsUtils')) {
                try {
                    $familias = \Glory\Integration\Avada\AvadaFontsUtils::discoverFontFamilies();
                    foreach ((array) $familias as $fam) {
                        $fam = (string) $fam;
                        if ($fam !== '' && !isset($fontFamilies[$fam])) { $fontFamilies[$fam] = $fam; }
                    }
                } catch (\Throwable $e) {}
            }
            if (empty($fontFamilies)) {
                $fontFamilies = [ '' => __('Default', 'glory-ab') ];
            } else {
                // Orden natural, agregar 'Default' primero
                asort($fontFamilies, SORT_NATURAL | SORT_FLAG_CASE);
                $fontFamilies = array_merge([ '' => __('Default', 'glory-ab') ], $fontFamilies);
            }
            $variantOptions = [
                '' => __('Default', 'glory-ab'),
                'normal' => 'normal',
                'italic' => 'italic',
                '100' => '100','200' => '200','300' => '300','400' => '400','500' => '500','600' => '600','700' => '700','800' => '800','900' => '900',
                '100italic' => '100 italic','200italic' => '200 italic','300italic' => '300 italic','400italic' => '400 italic','500italic' => '500 italic','600italic' => '600 italic','700italic' => '700 italic','800italic' => '800 italic','900italic' => '900 italic',
            ];
            $transformOptions = [
                '' => __('None', 'glory-ab'),
                'uppercase' => 'uppercase',
                'lowercase' => 'lowercase',
                'capitalize' => 'capitalize',
            ];

            // Config base con valores por defecto
            $defaults = [
                'post_type' => $args['post_type'] ?? 'post',
                'number_of_posts' => $args['number_of_posts'] ?? 10,
                'query_args' => $args['query_args'] ?? '',
                'include_post_ids' => $args['include_post_ids'] ?? '',
                'auto_open_first_item' => $args['auto_open_first_item'] ?? 'no',
                'list_direction' => $args['list_direction'] ?? 'vertical',
                'list_item_spacing' => $args['list_item_spacing'] ?? '12px',
                'list_panel_width' => $args['list_panel_width'] ?? '30%',
                'list_scroll_enabled' => $args['list_scroll_enabled'] ?? 'yes',
                'list_padding_top' => $args['list_padding_top'] ?? '0px',
                'list_padding_bottom' => $args['list_padding_bottom'] ?? '0px',
                'list_padding_left' => $args['list_padding_left'] ?? '0px',
                'list_padding_right' => $args['list_padding_right'] ?? '0px',
                'title_color' => $args['title_color'] ?? '',
                'title_font_family' => $args['title_font_family'] ?? '',
                'title_font_variant' => $args['title_font_variant'] ?? '',
                'title_font_size' => $args['title_font_size'] ?? '',
                'title_line_height' => $args['title_line_height'] ?? '',
                'title_letter_spacing' => $args['title_letter_spacing'] ?? '',
                'title_text_transform' => $args['title_text_transform'] ?? '',
                'content_scroll_enabled' => $args['content_scroll_enabled'] ?? 'yes',
                'content_color' => $args['content_color'] ?? '',
                'content_font_family' => $args['content_font_family'] ?? '',
                'content_font_variant' => $args['content_font_variant'] ?? '',
                'content_font_size' => $args['content_font_size'] ?? '',
                'content_line_height' => $args['content_line_height'] ?? '',
                'content_letter_spacing' => $args['content_letter_spacing'] ?? '',
                'content_text_transform' => $args['content_text_transform'] ?? '',
                'height' => $args['height'] ?? '100%',
                'padding_top' => $args['padding_top'] ?? '30px',
                'padding_bottom' => $args['padding_bottom'] ?? '30px',
                'padding_left' => $args['padding_left'] ?? '20px',
                'padding_right' => $args['padding_right'] ?? '20px',
                'content_padding_top' => $args['content_padding_top'] ?? '10px',
                'content_padding_bottom' => $args['content_padding_bottom'] ?? '10px',
                'content_padding_left' => $args['content_padding_left'] ?? '10px',
                'content_padding_right' => $args['content_padding_right'] ?? '10px',
            ];

            // Fusionar con overrides guardados (los guardados tienen prioridad)
            $config = array_merge($defaults, $opts);
            $schema = [
                [
                    'tab' => 'General',
                    'controls' => [
                        ['key' => 'post_type', 'type' => 'select', 'options' => $postTypeOptions, 'label' => 'Post Type'],
                        ['key' => 'number_of_posts', 'type' => 'text', 'label' => 'Number of Posts'],
                        ['key' => 'query_args', 'type' => 'textarea', 'label' => 'Query Arguments (JSON)'],
                        ['key' => 'include_post_ids', 'type' => 'text', 'label' => 'Include Post IDs (CSV)'],
                        ['key' => 'auto_open_first_item', 'type' => 'toggle', 'label' => 'Auto Open First Item'],
                        ['key' => 'height', 'type' => 'text', 'unit' => '%', 'label' => 'Height'],
                        ['key' => 'padding_top', 'type' => 'text', 'unit' => 'px', 'label' => 'Padding Top'],
                        ['key' => 'padding_bottom', 'type' => 'text', 'unit' => 'px', 'label' => 'Padding Bottom'],
                        ['key' => 'padding_left', 'type' => 'text', 'unit' => 'px', 'label' => 'Padding Left'],
                        ['key' => 'padding_right', 'type' => 'text', 'unit' => 'px', 'label' => 'Padding Right'],
                    ]
                ],
                [
                    'tab' => 'List',
                    'controls' => [
                        ['key' => 'list_direction', 'type' => 'select', 'options' => ['vertical' => 'Vertical', 'horizontal' => 'Horizontal'], 'label' => 'List Direction'],
                        ['key' => 'list_item_spacing', 'type' => 'text', 'unit' => 'px', 'label' => 'List Item Spacing'],
                        ['key' => 'list_panel_width', 'type' => 'range', 'min' => 10, 'max' => 70, 'step' => 1, 'unit' => '%', 'label' => 'List Panel Width'],
                        ['key' => 'list_scroll_enabled', 'type' => 'toggle', 'label' => 'List Scroll Enabled'],
                        ['key' => 'list_padding_top', 'type' => 'text', 'unit' => 'px', 'label' => 'List Padding Top'],
                        ['key' => 'list_padding_bottom', 'type' => 'text', 'unit' => 'px', 'label' => 'List Padding Bottom'],
                        ['key' => 'list_padding_left', 'type' => 'text', 'unit' => 'px', 'label' => 'List Padding Left'],
                        ['key' => 'list_padding_right', 'type' => 'text', 'unit' => 'px', 'label' => 'List Padding Right'],
                    ]
                ],
                [
                    'tab' => 'Titles',
                    'controls' => [
                        ['key' => 'title_font_family', 'type' => 'select', 'options' => $fontFamilies, 'label' => 'Title Font Family', 'search' => true],
                        ['key' => 'title_font_variant', 'type' => 'select', 'options' => $variantOptions, 'label' => 'Title Font Variant'],
                        ['key' => 'title_font_size', 'type' => 'text', 'unit' => 'px', 'label' => 'Title Font Size'],
                        ['key' => 'title_line_height', 'type' => 'text', 'unit' => 'px', 'label' => 'Title Line Height'],
                        ['key' => 'title_letter_spacing', 'type' => 'text', 'unit' => 'px', 'label' => 'Title Letter Spacing'],
                        ['key' => 'title_text_transform', 'type' => 'select', 'options' => $transformOptions, 'label' => 'Title Text Transform'],
                        ['key' => 'title_color', 'type' => 'color', 'label' => 'Title Color'],
                    ]
                ],
                [
                    'tab' => 'Content',
                    'controls' => [
                        ['key' => 'content_scroll_enabled', 'type' => 'toggle', 'label' => 'Content Scroll Enabled'],
                        ['key' => 'content_font_family', 'type' => 'select', 'options' => $fontFamilies, 'label' => 'Content Font Family', 'search' => true],
                        ['key' => 'content_font_variant', 'type' => 'select', 'options' => $variantOptions, 'label' => 'Content Font Variant'],
                        ['key' => 'content_font_size', 'type' => 'text', 'unit' => 'px', 'label' => 'Content Font Size'],
                        ['key' => 'content_line_height', 'type' => 'text', 'unit' => 'px', 'label' => 'Content Line Height'],
                        ['key' => 'content_letter_spacing', 'type' => 'text', 'unit' => 'px', 'label' => 'Content Letter Spacing'],
                        ['key' => 'content_text_transform', 'type' => 'select', 'options' => $transformOptions, 'label' => 'Content Text Transform'],
                        ['key' => 'content_color', 'type' => 'color', 'label' => 'Content Color'],
                        ['key' => 'content_padding_top', 'type' => 'text', 'unit' => 'px', 'label' => 'Content Padding Top'],
                        ['key' => 'content_padding_bottom', 'type' => 'text', 'unit' => 'px', 'label' => 'Content Padding Bottom'],
                        ['key' => 'content_padding_left', 'type' => 'text', 'unit' => 'px', 'label' => 'Content Padding Left'],
                        ['key' => 'content_padding_right', 'type' => 'text', 'unit' => 'px', 'label' => 'Content Padding Right'],
                    ]
                ],
            ];

            return ['id' => $gbnId, 'opts' => $opts, 'order' => $order, 'config' => $config, 'schema' => $schema];
        }
    }
    new FusionSC_GlorySplitContent();
}
