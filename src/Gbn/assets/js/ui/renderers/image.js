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
            styles['object-fit'] = config.objectFit; 
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
        
        // === PROPIEDADES ESPECÍFICAS DE IMAGEN (atributos HTML) ===
        
        // Actualizar src de la imagen
        if (path === 'src') {
            el.src = value;
            return true;
        }
        
        // Actualizar alt text
        if (path === 'alt') {
            el.alt = value;
            return true;
        }
        
        // Object-fit específico de imagen
        if (path === 'objectFit') {
            el.style.objectFit = value || '';
            return true;
        }
        
        // === DELEGAR A TRAITS COMUNES ===
        // Width, Height, BorderRadius, etc.
        return traits.handleCommonUpdate(el, path, value);
    }

    // Exportar renderer
    Gbn.ui.renderers.image = {
        getStyles: getStyles,
        handleUpdate: handleUpdate
    };

})(typeof window !== 'undefined' ? window : this);
