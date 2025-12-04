;(function (global) {
    'use strict';

    /**
     * BUTTON RENDERER - Refactorizado Fase 11
     * 
     * Este renderer ahora usa los traits centralizados para tipografía, spacing, etc.
     * Solo maneja lógica específica del componente Button (URL, target, etc.)
     * 
     * @module Gbn.ui.renderers.button
     */

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.renderers = Gbn.ui.renderers || {};

    // Referencia a traits para funciones compartidas
    var traits = Gbn.ui.renderers.traits;

    /**
     * Genera los estilos inline para un botón basándose en su configuración.
     * Usa traits.getCommonStyles() y agrega específicos de botón.
     */
    function getStyles(config, block) {
        // Obtener estilos comunes via traits
        var styles = traits.getCommonStyles(config);
        
        // === PROPIEDADES ESPECÍFICAS DE BUTTON ===
        
        // Text Align para contenido del botón
        if (config.textAlign) {
            styles['text-align'] = config.textAlign;
        }
        
        // Cursor específico de botón (default 'pointer')
        if (config.cursor && config.cursor !== 'pointer') {
            styles['cursor'] = config.cursor;
        }
        
        // Transition para efectos hover
        if (config.transition) {
            styles['transition'] = config.transition;
        }
        
        // Transform para efectos visuales
        if (config.transform) {
            styles['transform'] = config.transform;
        }
        
        // Width con lógica especial de display
        if (config.width && config.width !== 'auto') {
            styles['width'] = config.width;
            // Si es 100%, también cambiar display a block
            if (config.width === '100%' && !config.display) {
                styles['display'] = 'block';
            }
        }
        
        return styles;
    }

    /**
     * Maneja actualizaciones específicas del botón.
     * Retorna true si manejó la actualización, false para delegar a traits.
     * 
     * Fase 11: Delega propiedades comunes a traits.handleCommonUpdate()
     */
    function handleUpdate(block, path, value) {
        if (!block || !block.element) return false;
        var el = block.element;
        
        // === PROPIEDADES ESPECÍFICAS DE BUTTON (contenido/atributos) ===
        
        // Actualizar texto del botón
        if (path === 'texto') {
            // Preservar controles si existen
            var controls = el.querySelector('.gbn-controls-group');
            el.innerHTML = value;
            if (controls) {
                el.appendChild(controls);
            }
            return true;
        }
        
        // Actualizar URL (atributo href nativo)
        if (path === 'url') {
            el.setAttribute('href', value || '#');
            return true;
        }
        
        // Actualizar target (atributo nativo)
        if (path === 'target') {
            if (value && value !== '_self') {
                el.setAttribute('target', value);
            } else {
                el.removeAttribute('target');
            }
            return true;
        }

        // === PROPIEDADES DE ESTILO ESPECÍFICAS DE BUTTON ===
        
        // Width con lógica especial de display
        if (path === 'width') {
            el.style.width = value || '';
            // Si es 100%, cambiar a block
            if (value === '100%') {
                el.style.display = 'block';
            }
            return true;
        }

        // TextAlign (botones lo manejan directamente)
        if (path === 'textAlign') {
            el.style.textAlign = value || '';
            return true;
        }

        // Cursor
        if (path === 'cursor') {
            el.style.cursor = value || '';
            return true;
        }

        // Transition
        if (path === 'transition') {
            el.style.transition = value || '';
            return true;
        }

        // Transform (diferente de typography.transform que es text-transform)
        if (path === 'transform') {
            el.style.transform = value || '';
            return true;
        }

        // === DELEGAR A TRAITS COMUNES ===
        // Typography, Spacing, Border, Background, Color, Display
        return traits.handleCommonUpdate(el, path, value);
    }

    /**
     * Función para leer valores computados del elemento (sincronización bidireccional).
     * Usado por el panel para mostrar valores reales aplicados por CSS.
     */
    function getComputedValues(block) {
        if (!block || !block.element) return {};
        
        try {
            var computed = window.getComputedStyle(block.element);
            return {
                display: computed.display,
                width: computed.width,
                textAlign: computed.textAlign,
                backgroundColor: computed.backgroundColor,
                color: computed.color,
                fontFamily: computed.fontFamily,
                fontSize: computed.fontSize,
                fontWeight: computed.fontWeight,
                lineHeight: computed.lineHeight,
                letterSpacing: computed.letterSpacing,
                textTransform: computed.textTransform,
                borderWidth: computed.borderWidth,
                borderStyle: computed.borderStyle,
                borderColor: computed.borderColor,
                borderRadius: computed.borderRadius,
                cursor: computed.cursor,
                transition: computed.transition,
                transform: computed.transform,
                paddingTop: computed.paddingTop,
                paddingRight: computed.paddingRight,
                paddingBottom: computed.paddingBottom,
                paddingLeft: computed.paddingLeft,
                marginTop: computed.marginTop,
                marginRight: computed.marginRight,
                marginBottom: computed.marginBottom,
                marginLeft: computed.marginLeft
            };
        } catch (e) {
            return {};
        }
    }

    // Exportar el renderer
    Gbn.ui.renderers.button = {
        getStyles: getStyles,
        handleUpdate: handleUpdate,
        getComputedValues: getComputedValues
    };

})(typeof window !== 'undefined' ? window : this);
