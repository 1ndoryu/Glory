;(function (global) {
    'use strict';

    /**
     * IMAGE RENDERER - Refactorizado Fase 11
     * 
     * Este renderer usa los traits centralizados y solo maneja
     * propiedades específicas de imágenes (src, alt, objectFit).
     * 
     * @module Gbn.ui.renderers.image
     */

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.renderers = Gbn.ui.renderers || {};

    // Referencia a traits para funciones compartidas
    var traits = Gbn.ui.renderers.traits;

    /**
     * Genera estilos CSS para una imagen.
     * Usa traits.getCommonStyles() y agrega específicos de imagen.
     */
    function getStyles(config, block) {
        // Obtener estilos comunes (width, height, borderRadius ya están en traits)
        var styles = traits.getCommonStyles(config);
        
        // Propiedades específicas de imagen
        if (config.objectFit) { 
            // Usar variable CSS para que la imagen interna la consuma
            styles['--gbn-img-object-fit'] = config.objectFit; 
        }
        if (config.maxWidth) {
            styles['max-width'] = traits.normalizeSize(config.maxWidth);
        }
        if (config.maxHeight) {
            styles['max-height'] = traits.normalizeSize(config.maxHeight);
        }

        // [FIX] Aplicar overflow:hidden si hay border-radius para recortar la imagen interna
        if (config.borderRadius) {
            styles['overflow'] = 'hidden';
        }
        
        return styles;
    }

    /**
     * Maneja actualizaciones específicas de imagen.
     * Delega estilos comunes a traits.
     */
    function handleUpdate(block, path, value) {
        if (!block || !block.element) return false;
        var el = block.element;
        // Buscar la imagen interna si existe (wrapper structure), sino usar el elemento mismo (fallback)
        var img = el.tagName === 'IMG' ? el : el.querySelector('img');
        
        // === PROPIEDADES ESPECÍFICAS DE IMAGEN (atributos HTML) ===
        
        // Actualizar src de la imagen
        if (path === 'src') {
            if (img) img.src = value;
            return true;
        }
        
        // Actualizar alt text
        if (path === 'alt') {
            if (img) img.alt = value;
            return true;
        }
        
        // Object-fit específico de imagen
        if (path === 'objectFit') {
            // Actualizar variable CSS en el contenedor
            el.style.setProperty('--gbn-img-object-fit', value || 'cover');
            return true;
        }

        if (path === 'maxWidth') {
            el.style.maxWidth = traits.normalizeSize(value) || '';
            return true;
        }

        if (path === 'maxHeight') {
            el.style.maxHeight = traits.normalizeSize(value) || '';
            return true;
        }

        // [FIX] Interceptar borderRadius para aplicar overflow: hidden
        if (path === 'borderRadius') {
            traits.applyBorder(el, path, value);
            el.style.overflow = value ? 'hidden' : '';
            return true;
        }
        
        // === DELEGAR A TRAITS COMUNES ===
        // Width, Height, BorderRadius, etc. se aplican al contenedor
        return traits.handleCommonUpdate(el, path, value);
    }

    // Exportar renderer
    Gbn.ui.renderers.image = {
        getStyles: getStyles,
        handleUpdate: handleUpdate
    };

})(typeof window !== 'undefined' ? window : this);
