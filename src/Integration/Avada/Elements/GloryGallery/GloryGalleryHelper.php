<?php

namespace Glory\Integration\Avada\Elements\GloryGallery;

use Glory\Support\ContentRender\Args;
use Glory\Support\ContentRender\QueryArgs;
use Glory\Support\CSS\ContentRenderCss;
use Glory\Support\Scripts\Carousel;
use Glory\Support\Scripts\HorizontalDrag;
use Glory\Support\WP\PostsDedup;

class GloryGalleryHelper
{
    /**
     * Procesa los IDs de imágenes desde diferentes formatos de entrada
     * 
     * @param mixed $image_ids IDs en formato CSV, array o string
     * @return array IDs procesados
     */
    public static function procesarIdsImagenes($image_ids): array
    {
        $ids = [];
        
        if (empty($image_ids) || $image_ids === 'Select Images') {
            return $ids;
        }
        
        // Si es un string, convertir a array
        if (is_string($image_ids)) {
            $raw_ids = trim($image_ids);
            $ids = array_filter(array_map('absint', array_map('trim', explode(',', $raw_ids))));
        } 
        // Si ya es un array, procesarlo directamente
        elseif (is_array($image_ids)) {
            $ids = array_filter(array_map('absint', $image_ids));
        }
        
        return array_values(array_unique($ids));
    }
    
    /**
     * Genera una clase única para la instancia
     * 
     * @return string Clase única
     */
    public static function generarInstanciaClass(): string
    {
        return 'glory-gr-' . substr(md5(uniqid('', true)), 0, 8);
    }
    
    /**
     * Prepara los argumentos de consulta para WordPress
     * 
     * @param array $ids IDs de imágenes
     * @return array Argumentos de consulta
     */
    public static function prepararArgumentosConsulta(array $ids): array
    {
        return [
            'post_type'      => 'attachment',
            'post_mime_type' => 'image',
            'post_status'    => 'inherit',
            'post__in'       => $ids,
            'orderby'        => 'post__in',
        ];
    }
    
    /**
     * Prepara la configuración para ContentRender
     * 
     * @param array $args Argumentos del shortcode
     * @param array $ids IDs de imágenes
     * @param string $instanceClass Clase de instancia
     * @return array Configuración para ContentRender
     */
    public static function prepararConfiguracion(array $args, array $ids, string $instanceClass): array
    {
        $is_manual = isset($args['carousel_manual']) && 'yes' === (string)$args['carousel_manual'];
        
        return [
            'publicacionesPorPagina' => count($ids),
            'claseContenedor'        => trim(($args['clase_contenedor'] ?? 'glory-gallery-list') . ' ' . $instanceClass . ($is_manual ? ' glory-manual-grid' : '')),
            'claseItem'              => trim(($args['clase_item'] ?? 'glory-gallery-item') . ' ' . $instanceClass . '__item'),
            'paginacion'             => (isset($args['paginacion']) && 'yes' === $args['paginacion']),
            'plantillaCallback'      => [\Glory\Integration\Avada\Elements\GloryGallery\GloryGalleryTemplate::class, 'galleryItemTemplate'],
            'argumentosConsulta'     => self::prepararArgumentosConsulta($ids),
            'tiempoCache'            => isset($args['tiempo_cache']) ? (int)$args['tiempo_cache'] : 3600,
            'forzarSinCache'         => (isset($args['forzar_sin_cache']) && 'yes' === $args['forzar_sin_cache']),
            'linkEnabled'            => (!isset($args['link_enabled']) || 'yes' === (string)$args['link_enabled']),
            'imgOptimize'            => (!isset($args['img_optimize']) || 'yes' === (string)$args['img_optimize']),
            'imgQuality'             => isset($args['img_quality']) ? (int)$args['img_quality'] : 60,
            'imgSize'                => isset($args['img_size']) ? (string)$args['img_size'] : 'medium',
            'imgShow'                => (!isset($args['img_show']) || 'yes' === (string)$args['img_show']),
            'titleShow'              => (!isset($args['title_show']) || 'yes' === (string)$args['title_show']),
            'titlePosition'          => isset($args['title_position']) ? (string)$args['title_position'] : 'bottom',
        ];
    }
    
    /**
     * Genera el CSS para la instancia usando ContentRenderCss
     * 
     * @param string $instanceClass Clase de instancia
     * @param array $args Argumentos del shortcode
     * @return string CSS generado
     */
    public static function generarCSS(string $instanceClass, array $args): string
    {
        // Determinar el modo de interacción
        $modo_interaccion = 'normal';
        if (isset($args['carousel']) && 'yes' === (string)$args['carousel']) {
            $modo_interaccion = 'carousel';
        } elseif (isset($args['carousel_manual']) && 'yes' === (string)$args['carousel_manual']) {
            $modo_interaccion = 'manual';
        }
        
        // Usar ContentRenderCss para generar el CSS (GloryGallery no usa tipografías de Fusion)
        $css = ContentRenderCss::build($instanceClass, $args, null, $modo_interaccion, false);
        
        // CSS adicional para carrusel manual
        if ($modo_interaccion === 'manual') {
            $css .= '.' . $instanceClass . '{overflow-x:auto;scrollbar-width:none;-ms-overflow-style:none;}';
            $css .= '.' . $instanceClass . '::-webkit-scrollbar{display:none;}';
            $css .= '.' . $instanceClass . '-wrap{position:relative;}';
            $css .= '.' . $instanceClass . '-wrap .glory-manual-btn{position:absolute;top:50%;transform:translateY(-50%);z-index:2;border:none;background:transparent;color:#fff;font-size:28px;line-height:1;cursor:pointer;padding:8px;}';
            $css .= '.' . $instanceClass . '-wrap .glory-manual-prev{left:8px;}';
            $css .= '.' . $instanceClass . '-wrap .glory-manual-next{right:8px;}';
        }
        
        return $css;
    }
    
    /**
     * Genera el JavaScript para la instancia
     * 
     * @param string $instanceClass Clase de instancia
     * @param array $args Argumentos del shortcode
     * @return string JavaScript generado
     */
    public static function generarJS(string $instanceClass, array $args): string
    {
        $js = '';
        $selector = '.' . $instanceClass;
        
        // Desactivar enlaces si corresponde
        $is_link_enabled = (isset($args['link_enabled']) && 'yes' === (string)$args['link_enabled']);
        if (!$is_link_enabled) {
            $js .= '<script>(function(){var s=' . wp_json_encode($selector) . ';function prevent(e){var a=e.target.closest("a");if(a&&a.closest(s)){e.preventDefault();e.stopPropagation();}}document.addEventListener("click",prevent,true);document.addEventListener("keydown",function(e){if((e.key||e.keyCode)==="Enter"||e.keyCode===13){prevent(e);}},true);})();</script>';
        }
        
        // JS carrusel automático
        $is_carousel = (isset($args['carousel']) && 'yes' === (string)$args['carousel']);
        if ($is_carousel) {
            $speed = isset($args['carousel_speed']) ? (float)$args['carousel_speed'] : 20.0;
            $js .= Carousel::buildInitOrQueue($selector, $speed);
        } else {
            $js .= Carousel::buildStop($selector);
        }
        
        // JS carrusel manual
        $is_manual = (isset($args['carousel_manual']) && 'yes' === (string)$args['carousel_manual']);
        if ($is_manual) {
            $wrapSel = '.' . $instanceClass . '-wrap';
            $gridSel = '.' . $instanceClass;
            $js .= '<script>(function(){var wrapSel=' . wp_json_encode($wrapSel) . ',gridSel=' . wp_json_encode($gridSel) . ';document.querySelectorAll(wrapSel).forEach(function(wrap){if(wrap.dataset.galeriaInit==="true")return;wrap.dataset.galeriaInit="true";var grid=wrap.querySelector(gridSel);if(!grid)return;var prev=wrap.querySelector(".glory-manual-prev"),next=wrap.querySelector(".glory-manual-next");var scrollDist=function(){return grid.clientWidth||0;};prev&&prev.addEventListener("click",function(){grid.scrollBy({left:-scrollDist(),behavior:"smooth"});});next&&next.addEventListener("click",function(){grid.scrollBy({left:scrollDist(),behavior:"smooth"});});var isDragging=false,startX=0,scrollLeftStart=0,hasMoved=false;var startDrag=function(e){if(e.pointerType==="touch"){e.preventDefault();}isDragging=true;hasMoved=false;startX=e.clientX;scrollLeftStart=grid.scrollLeft;if(e.pointerType!=="mouse"&&grid.setPointerCapture){grid.setPointerCapture(e.pointerId);}grid.classList.add("is-dragging");};var duringDrag=function(e){if(!isDragging)return;var dx=e.clientX-startX;if(Math.abs(dx)>5){hasMoved=true;}grid.scrollLeft=scrollLeftStart-dx;};var stopDrag=function(e){if(!isDragging)return;isDragging=false;if(e.pointerType!=="mouse"&&grid.releasePointerCapture){grid.releasePointerCapture(e.pointerId);}grid.classList.remove("is-dragging");};grid.addEventListener("pointerdown",startDrag);grid.addEventListener("pointermove",duringDrag);grid.addEventListener("pointerup",stopDrag);grid.addEventListener("pointercancel",stopDrag);grid.addEventListener("pointerleave",stopDrag);grid.addEventListener("dragstart",function(e){e.preventDefault();});grid.addEventListener("click",function(e){if(!hasMoved)return;var a=e.target.closest("a");if(a&&a.closest(gridSel)){e.preventDefault();e.stopPropagation();}},true);});})();</script>';
        }
        
        return $js;
    }
    
    /**
     * Envuelve el contenido con el wrapper para carrusel manual si es necesario
     * 
     * @param string $contenido Contenido a envolver
     * @param string $instanceClass Clase de instancia
     * @param array $args Argumentos del shortcode
     * @return string Contenido envuelto
     */
    public static function envolverParaCarruselManual(string $contenido, string $instanceClass, array $args): string
    {
        $is_manual = (isset($args['carousel_manual']) && 'yes' === (string)$args['carousel_manual']);
        
        if ($is_manual) {
            $wrap_start = '<div class="glory-manual-wrapper ' . esc_attr($instanceClass) . '-wrap">' .
                '<button class="glory-manual-btn glory-manual-prev" aria-label="Anterior">&#10094;</button>';
            $wrap_end = '<button class="glory-manual-btn glory-manual-next" aria-label="Siguiente">&#10095;</button>' .
                '</div>';
            return $wrap_start . $contenido . $wrap_end;
        }
        
        return $contenido;
    }
    
    
    /**
     * Activa los filtros de deduplicación de posts
     */
    public static function enableDedupFilters(): void
    {
        PostsDedup::enable();
    }
    
    /**
     * Desactiva los filtros de deduplicación de posts
     */
    public static function disableDedupFilters(): void
    {
        PostsDedup::disable();
    }
}
