;(function (global) {
    'use strict';

    /**
     * TEXT RENDERER - Refactorizado Fase 11
     * 
     * Este renderer ahora usa los traits centralizados para tipografía, spacing, etc.
     * Solo maneja lógica específica del componente Text.
     * 
     * @module Gbn.ui.renderers.text
     */

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.renderers = Gbn.ui.renderers || {};

    // Referencia a traits para funciones compartidas
    var traits = Gbn.ui.renderers.traits;

    /**
     * Genera los estilos CSS para un bloque de texto basado en su configuración.
     * Usa traits.getCommonStyles() para propiedades compartidas y agrega específicas de texto.
     */
    function getStyles(config, block) {
        // Obtener estilos comunes via traits
        var styles = traits.getCommonStyles(config);
        
        // Propiedades específicas de texto que no están en traits
        if (config.alineacion) { 
            styles['text-align'] = config.alineacion; 
        }
        
        // Text Shadow (para efectos como .textGlow)
        if (config.textShadow) { 
            styles['text-shadow'] = config.textShadow; 
        }
        
        // Tamaño legacy (mantener compatibilidad - prioriza sobre typography.size)
        if (config.size) { 
            styles['font-size'] = traits.normalizeSize(config.size); 
        }
        
        return styles;
    }

    /**
     * Maneja actualizaciones de configuración en tiempo real.
     * Aplica cambios directamente al DOM para feedback instantáneo.
     * 
     * Fase 11: Delega propiedades comunes a traits.handleCommonUpdate()
     */
    function handleUpdate(block, path, value) {
        if (!block || !block.element) return false;
        var el = block.element;

        // === PROPIEDADES ESPECÍFICAS DE TEXTO ===
        // Estas propiedades son únicas del componente Text y no están en traits
        
        // Cambio de etiqueta HTML (tag)
        if (path === 'tag') {
            var oldEl = el;
            var newTag = value || 'p';
            if (oldEl.tagName.toLowerCase() !== newTag.toLowerCase()) {
                var newEl = document.createElement(newTag);
                // Copiar todos los atributos
                Array.from(oldEl.attributes).forEach(function(attr) {
                    newEl.setAttribute(attr.name, attr.value);
                });
                newEl.innerHTML = oldEl.innerHTML;
                if (oldEl.parentNode) {
                    oldEl.parentNode.replaceChild(newEl, oldEl);
                    block.element = newEl;
                }
            }
            return true;
        }
        
        // Actualización de contenido HTML
        if (path === 'texto') {
            // Preservar controles GBN si existen
            var controls = el.querySelector('.gbn-controls-group');
            el.innerHTML = value;
            if (controls) el.appendChild(controls);
            return true;
        }

        // Alineación (usa nombre personalizado 'alineacion')
        if (path === 'alineacion') {
            el.style.textAlign = value || '';
            return true;
        }

        // Text Shadow específico
        if (path === 'textShadow') {
            el.style.textShadow = value || '';
            return true;
        }

        // Tamaño legacy
        if (path === 'size') {
            el.style.fontSize = traits.normalizeSize(value) || '';
            return true;
        }

        // === DELEGAR A TRAITS COMUNES ===
        // Typography, Spacing, Border, Background - manejados por traits
        return traits.handleCommonUpdate(el, path, value);
    }

    // Exportar renderer
    Gbn.ui.renderers.text = {
        getStyles: getStyles,
        handleUpdate: handleUpdate
    };

})(typeof window !== 'undefined' ? window : this);
