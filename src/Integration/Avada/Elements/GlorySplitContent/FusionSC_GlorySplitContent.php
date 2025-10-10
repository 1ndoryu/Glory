<?php

// Clase global sin namespace para ajustarse al patrón de Avada.

if (! class_exists('FusionSC_GlorySplitContent') && class_exists('Fusion_Element')) {

    class FusionSC_GlorySplitContent extends Fusion_Element
    {
        private $counter = 1;

        public function __construct()
        {
            parent::__construct();
            add_shortcode('glory_split_content', [ $this, 'render' ]);
        }

        public static function get_element_defaults()
        {
            return [
                'post_type'                => 'post',
                'publicaciones_por_pagina' => 10,
                'argumentos_json'          => '',
                'post_ids'                 => '',
                'post_ids_select'          => '',
                'auto_open_first'          => 'no',
                // Lista
                'list_direction'           => 'vertical',
                'list_gap'                 => '12px',
                'list_panel_width'         => '30%',
                'titles_color'             => '',
                'titles_font_family'       => '',
                'titles_font_size'         => '',
                // Contenido
                'content_color'            => '',
                'content_font_family'      => '',
                'content_font_size'        => '',
            ];
        }

        public function render($args, $content = '')
        {
            $this->defaults = self::get_element_defaults();
            $this->args     = FusionBuilder::set_shortcode_defaults($this->defaults, $args, 'glory_split_content');

            $instanceClass = 'glory-split-' . substr(md5(uniqid('', true)), 0, 8);
            $postType      = isset($this->args['post_type']) ? sanitize_key($this->args['post_type']) : 'post';
            $ppp           = isset($this->args['publicaciones_por_pagina']) ? (int) $this->args['publicaciones_por_pagina'] : 10;

            // Argumentos de consulta extendidos
            $argumentosConsulta = [];
            if (! empty($this->args['argumentos_json'])) {
                $json = trim((string) $this->args['argumentos_json']);
                $parsed = json_decode($json, true);
                if (is_array($parsed)) {
                    $argumentosConsulta = $parsed;
                }
            }
            // IDs explícitos via CSV o selector múltiple
            $idsCsv = isset($this->args['post_ids']) ? (string) $this->args['post_ids'] : '';
            $idsSelRaw = $this->args['post_ids_select'] ?? '';
            $idsSel = [];
            if (is_array($idsSelRaw)) { $idsSel = array_map('intval', $idsSelRaw); }
            $idsCsvArr = [];
            if ($idsCsv !== '') { $idsCsvArr = array_filter(array_map('absint', array_map('trim', explode(',', $idsCsv)))); }
            $ids = array_values(array_unique(array_filter(array_merge($idsCsvArr, $idsSel))));
            if (! empty($ids)) {
                $argumentosConsulta['post__in'] = $ids;
                $argumentosConsulta['orderby'] = 'post__in';
            }

            // Construir HTML de lista con ContentRender + plantilla de título
            $listContainerClass = $instanceClass . '__list glory-content-list';
            $listItemClass      = $instanceClass . '__list-item glory-content-item';

            ob_start();
            try {
                \Glory\Components\ContentRender::print($postType, [
                    'publicacionesPorPagina' => $ppp,
                    'claseContenedor'        => $listContainerClass,
                    'claseItem'              => $listItemClass,
                    'paginacion'             => false,
                    'plantillaCallback'      => [ '\\Glory\\Integration\\Avada\\Elements\\GlorySplitContent\\GlorySplitContentTemplate', 'titleItem' ],
                    'argumentosConsulta'     => $argumentosConsulta,
                    'forzarSinCache'         => true,
                ]);
            } catch (\Throwable $t) {
            }
            $titlesHtml = ob_get_clean();

            $ajaxUrl = admin_url('admin-ajax.php');

            // Wrapper
            $html  = '<div class="glory-split ' . esc_attr($instanceClass) . '" data-ajax-url="' . esc_url($ajaxUrl) . '" data-post-type="' . esc_attr($postType) . '">';
            $html .= '<div class="glory-split__panel glory-split__titles">' . $titlesHtml . '</div>';
            $html .= '<div class="glory-split__panel glory-split__content" id="' . esc_attr($instanceClass) . '-content"></div>';
            $html .= '</div>';

            // CSS por instancia
            $css = $this->buildInstanceCss($instanceClass, $this->args);
            if ($css !== '') {
                $html .= '<style id="' . esc_attr($instanceClass) . '-css">' . $css . '</style>';
            }

            // JS por instancia (carga diferida)
            $html .= $this->buildInstanceJs($instanceClass, $postType, (string) ($this->args['auto_open_first'] ?? 'no') === 'yes');

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
            $list_gap       = (string) ($a['list_gap'] ?? '12px');
            $panel_width    = (string) ($a['list_panel_width'] ?? '30%');
            $titles_color   = (string) ($a['titles_color'] ?? '');
            $titles_ff      = (string) ($a['titles_font_family'] ?? '');
            $titles_fs      = (string) ($a['titles_font_size'] ?? '');
            $content_color  = (string) ($a['content_color'] ?? '');
            $content_ff     = (string) ($a['content_font_family'] ?? '');
            $content_fs     = (string) ($a['content_font_size'] ?? '');

            $css  = $root . '{display:flex;gap:24px;align-items:stretch;width:100%;}';
            $css .= $root . ' .glory-split__titles{flex:0 0 ' . $panel_width . ';min-width:200px;}';
            $css .= $root . ' .glory-split__content{flex:1 1 auto;min-width:0;}';

            // Lista
            $dir = $list_direction === 'horizontal' ? 'row' : 'column';
            $wrap = $list_direction === 'horizontal' ? 'wrap' : 'nowrap';
            $css .= $list . '{display:flex;flex-direction:' . $dir . ';flex-wrap:' . $wrap . ';gap:' . $list_gap . ';}';
            $css .= $item . '{margin:0;}';
            $css .= $root . ' .glory-split__title{display:block;width:100%;text-align:left;background:transparent;border:0;padding:0;margin:0;cursor:pointer;}';
            $css .= $title . '{display:inline-block;}';
            if ($titles_color !== '') { $css .= $title . '{color:' . $titles_color . ';}'; }
            if ($titles_ff !== '') { $css .= $title . '{font-family:' . $titles_ff . ';}'; }
            if ($titles_fs !== '') { $css .= $title . '{font-size:' . $titles_fs . ';}'; }

            // Contenido
            if ($content_color !== '') { $css .= $content . '{color:' . $content_color . ';}'; }
            if ($content_ff !== '') { $css .= $content . '{font-family:' . $content_ff . ';}'; }
            if ($content_fs !== '') { $css .= $content . '{font-size:' . $content_fs . ';}'; }

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
            $js .= 'var cache={};function load(id, item){if(cache[id]){content.innerHTML=cache[id];setActive(item);return;}';
            $js .= 'var fd=new FormData();fd.append("action","obtenerHtmlPost");fd.append("id",id);fd.append("tipo",tipo);fd.append("plantilla","full");';
            $js .= 'fetch(ajax,{method:"POST",body:fd,credentials:"same-origin"}).then(function(r){return r.json();}).then(function(res){if(!res||!res.success){throw new Error("Error");}cache[id]=res.data||"";content.innerHTML=cache[id];setActive(item);}).catch(function(e){content.innerHTML="<p>Error al cargar contenido.</p>";});}
';
            $js .= 'function setActive(item){root.querySelectorAll(".glory-split__item.is-active").forEach(function(el){el.classList.remove("is-active")});if(item){item.classList.add("is-active");}}';
            $js .= 'root.addEventListener("click",function(e){var btn=e.target.closest(".glory-split__item .glory-split__title");if(!btn||!root.contains(btn))return;e.preventDefault();var item=btn.closest(".glory-split__item");if(!item)return;var id=item.getAttribute("data-post-id");if(!id)return;load(id,item);});';
            if ($autoOpenFirst) {
                $js .= 'var first=root.querySelector(".glory-split__item .glory-split__title");if(first){first.click();}';
            }
            $js .= '})();</script>';
            return $js;
        }
    }

    new FusionSC_GlorySplitContent();
}


