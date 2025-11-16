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
        $is_featured = isset($args['featured_mode']) && 'yes' === (string)$args['featured_mode'];

        $extraContainerClass = $is_manual ? ' glory-manual-grid' : '';
        if ($is_featured) {
            $extraContainerClass .= ' glory-featured-gallery';
        }
        
        return [
            'publicacionesPorPagina' => count($ids),
            'claseContenedor'        => trim(($args['clase_contenedor'] ?? 'glory-gallery-list') . ' ' . $instanceClass . $extraContainerClass),
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
            'titleShow'              => (isset($args['title_show']) && 'yes' === (string)$args['title_show']),
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
        $is_featured = isset($args['featured_mode']) && 'yes' === (string)$args['featured_mode'];
        if (!$is_featured && isset($args['carousel']) && 'yes' === (string)$args['carousel']) {
            $modo_interaccion = 'carousel';
        } elseif (!$is_featured && isset($args['carousel_manual']) && 'yes' === (string)$args['carousel_manual']) {
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

        // CSS adicional para modo destacado (imagen principal + miniaturas)
        if ($is_featured) {
            $thumbHeight = isset($args['featured_thumb_height']) ? trim((string) $args['featured_thumb_height']) : '';
            $thumbAspect = isset($args['featured_thumb_aspect_ratio']) ? trim((string) $args['featured_thumb_aspect_ratio']) : '';
            $mainObjectFit = isset($args['featured_main_object_fit']) && in_array((string) $args['featured_main_object_fit'], ['cover', 'contain'], true)
                ? (string) $args['featured_main_object_fit']
                : (isset($args['img_object_fit']) ? (string) $args['img_object_fit'] : 'cover');
            $mainBgColor = isset($args['featured_main_bg_color']) ? trim((string) $args['featured_main_bg_color']) : '';

            // Contenedor principal en bloque para evitar layouts raros con flex globales
            $css .= '.' . $instanceClass . '{display:block;}';

            // Imagen principal: centrada, con object-fit configurable
            $css .= '.' . $instanceClass . ' .glory-featured-main{position:relative;text-align:center;margin-bottom:10px;}';
            $css .= '.' . $instanceClass . ' .glory-featured-main .glory-cr__image{display:inline-block;max-width:100%;height:auto;object-fit:' . $mainObjectFit . ';';
            if ('contain' === $mainObjectFit && '' !== $mainBgColor) {
                $css .= 'background-color:' . $mainBgColor . ';';
            }
            $css .= '}';

            // Thumbnails: debajo o encima según DOM, con margen y centrados
            $css .= '.' . $instanceClass . ' .glory-featured-thumbs{display:flex;flex-wrap:wrap;gap:10px;}';
            $css .= '.' . $instanceClass . ' .glory-featured-thumbs--top{margin-bottom:10px;margin-top:0;}';
            $css .= '.' . $instanceClass . ' .glory-featured-thumbs--bottom{margin-top:10px;margin-bottom:0;}';

            $css .= '.' . $instanceClass . ' .glory-featured-thumb{cursor:pointer;}';
            $css .= '.' . $instanceClass . ' .glory-featured-btn{position:absolute;top:50%;transform:translateY(-50%);z-index:2;border:none;background:transparent;color:#fff;font-size:28px;line-height:1;cursor:pointer;padding:8px;}';
            $css .= '.' . $instanceClass . ' .glory-featured-prev{left:8px;}';
            $css .= '.' . $instanceClass . ' .glory-featured-next{right:8px;}';

            if ('' !== $thumbHeight || '' !== $thumbAspect) {
                $css .= '.' . $instanceClass . ' .glory-featured-thumbs .glory-cr__image{';
                if ('' !== $thumbHeight) {
                    $css .= 'height:' . $thumbHeight . ' !important;max-height:' . $thumbHeight . ' !important;width:auto;max-width:100%;';
                }
                if ('' !== $thumbAspect) {
                    $css .= 'aspect-ratio:' . $thumbAspect . ';object-fit:cover;';
                }
                $css .= '}';
            }
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
        $is_featured = isset($args['featured_mode']) && 'yes' === (string)$args['featured_mode'];
        
        // Desactivar enlaces si corresponde
        $is_link_enabled = (isset($args['link_enabled']) && 'yes' === (string)$args['link_enabled']);
        if (!$is_link_enabled) {
            $js .= '<script>(function(){var s=' . wp_json_encode($selector) . ';function prevent(e){var a=e.target.closest("a");if(a&&a.closest(s)){e.preventDefault();e.stopPropagation();}}document.addEventListener("click",prevent,true);document.addEventListener("keydown",function(e){if((e.key||e.keyCode)==="Enter"||e.keyCode===13){prevent(e);}},true);})();</script>';
        }
        
        // JS carrusel automático (desactivado en modo destacado)
        $is_carousel = (!$is_featured && isset($args['carousel']) && 'yes' === (string)$args['carousel']);
        if ($is_carousel) {
            $speed = isset($args['carousel_speed']) ? (float)$args['carousel_speed'] : 20.0;
            $js .= Carousel::buildInitOrQueue($selector, $speed);
        } else {
            $js .= Carousel::buildStop($selector);
        }
        
        // JS carrusel manual (desactivado en modo destacado)
        $is_manual = (!$is_featured && isset($args['carousel_manual']) && 'yes' === (string)$args['carousel_manual']);
        if ($is_manual) {
            $wrapSel = '.' . $instanceClass . '-wrap';
            $gridSel = '.' . $instanceClass;
            $js .= '<script>(function(){var wrapSel=' . wp_json_encode($wrapSel) . ',gridSel=' . wp_json_encode($gridSel) . ';document.querySelectorAll(wrapSel).forEach(function(wrap){if(wrap.dataset.galeriaInit==="true")return;wrap.dataset.galeriaInit="true";var grid=wrap.querySelector(gridSel);if(!grid)return;var prev=wrap.querySelector(".glory-manual-prev"),next=wrap.querySelector(".glory-manual-next");var scrollDist=function(){return grid.clientWidth||0;};prev&&prev.addEventListener("click",function(){grid.scrollBy({left:-scrollDist(),behavior:"smooth"});});next&&next.addEventListener("click",function(){grid.scrollBy({left:scrollDist(),behavior:"smooth"});});var isDragging=false,startX=0,scrollLeftStart=0,hasMoved=false;var startDrag=function(e){if(e.pointerType==="touch"){e.preventDefault();}isDragging=true;hasMoved=false;startX=e.clientX;scrollLeftStart=grid.scrollLeft;if(e.pointerType!=="mouse"&&grid.setPointerCapture){grid.setPointerCapture(e.pointerId);}grid.classList.add("is-dragging");};var duringDrag=function(e){if(!isDragging)return;var dx=e.clientX-startX;if(Math.abs(dx)>5){hasMoved=true;}grid.scrollLeft=scrollLeftStart-dx;};var stopDrag=function(e){if(!isDragging)return;isDragging=false;if(e.pointerType!=="mouse"&&grid.releasePointerCapture){grid.releasePointerCapture(e.pointerId);}grid.classList.remove("is-dragging");};grid.addEventListener("pointerdown",startDrag);grid.addEventListener("pointermove",duringDrag);grid.addEventListener("pointerup",stopDrag);grid.addEventListener("pointercancel",stopDrag);grid.addEventListener("pointerleave",stopDrag);grid.addEventListener("dragstart",function(e){e.preventDefault();});grid.addEventListener("click",function(e){if(!hasMoved)return;var a=e.target.closest("a");if(a&&a.closest(gridSel)){e.preventDefault();e.stopPropagation();}},true);});})();</script>';
        }

        // JS modo destacado (imagen principal + miniaturas)
        if ($is_featured) {
            $position = isset($args['featured_thumbs_position']) ? (string) $args['featured_thumbs_position'] : 'bottom';
            $js .= '<script>(function(){var selector=' . wp_json_encode($selector) . ';var pos=' . wp_json_encode($position) . ';document.querySelectorAll(selector).forEach(function(container){if(!container||container.dataset.gloryFeaturedInit==="true")return;container.dataset.gloryFeaturedInit="true";var items=container.querySelectorAll(".' . $instanceClass . '__item");if(!items.length||items.length===1)return;var itemsArr=Array.prototype.slice.call(items);var mainWrap=document.createElement("div");mainWrap.className="glory-featured-main";var thumbsWrap=document.createElement("div");thumbsWrap.className="glory-featured-thumbs glory-featured-thumbs--"+pos;var parent=itemsArr[0].parentNode;if(pos==="top"){parent.insertBefore(thumbsWrap,itemsArr[0]);parent.insertBefore(mainWrap,thumbsWrap.nextSibling);}else{parent.insertBefore(mainWrap,itemsArr[0]);parent.insertBefore(thumbsWrap,mainWrap.nextSibling);}mainWrap.appendChild(itemsArr[0]);itemsArr[0].classList.add("glory-featured-main-item");for(var i=1;i<itemsArr.length;i++){thumbsWrap.appendChild(itemsArr[i]);itemsArr[i].classList.add("glory-featured-thumb");}var prevBtn=document.createElement("button");prevBtn.className="glory-featured-btn glory-featured-prev";prevBtn.type="button";prevBtn.setAttribute("aria-label","Previous image");prevBtn.innerHTML="&#10094;";var nextBtn=document.createElement("button");nextBtn.className="glory-featured-btn glory-featured-next";nextBtn.type="button";nextBtn.setAttribute("aria-label","Next image");nextBtn.innerHTML="&#10095;";mainWrap.appendChild(prevBtn);mainWrap.appendChild(nextBtn);var currentIndex=0;function showIndex(newIndex){if(newIndex<0){newIndex=itemsArr.length-1;}if(newIndex>=itemsArr.length){newIndex=0;}if(newIndex===currentIndex)return;var currentItem=itemsArr[currentIndex];var nextItem=itemsArr[newIndex];if(!currentItem||!nextItem)return;currentItem.classList.remove("glory-featured-main-item");currentItem.classList.add("glory-featured-thumb");nextItem.classList.add("glory-featured-main-item");nextItem.classList.remove("glory-featured-thumb");if(currentItem.parentNode===mainWrap){thumbsWrap.appendChild(currentItem);}if(nextItem.parentNode===thumbsWrap){mainWrap.insertBefore(nextItem,prevBtn);}currentIndex=newIndex;}thumbsWrap.addEventListener("click",function(e){var thumb=e.target.closest(".' . $instanceClass . '__item");if(!thumb)return;var idx=itemsArr.indexOf(thumb);if(idx===-1)return;e.preventDefault();e.stopPropagation();showIndex(idx);});prevBtn.addEventListener("click",function(e){e.preventDefault();showIndex(currentIndex-1);});nextBtn.addEventListener("click",function(e){e.preventDefault();showIndex(currentIndex+1);});});})();</script>';
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
