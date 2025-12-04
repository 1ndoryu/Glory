;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.renderers = Gbn.ui.renderers || {};

    /**
     * Helper para normalizar valores de tamaño (agrega 'px' si es solo número)
     */
    function normalizeSize(val) {
        if (!val) return val;
        var strVal = String(val).trim();
        if (strVal === '' || strVal === 'null') return null;
        // Si ya tiene unidad, dejarlo como está
        if (/[a-z%]+$/i.test(strVal)) return strVal;
        // Si es un número puro, agregar px
        if (!isNaN(parseFloat(strVal)) && isFinite(strVal)) {
            return strVal + 'px';
        }
        return strVal;
    }

    /**
     * Genera los estilos inline para un botón basándose en su configuración
     * Sigue el principio de reglas.md: solo emitir estilos si hay configuración explícita
     */
    function getStyles(config, block) {
        var styles = {};
        
        // Display
        if (config.display) {
            styles['display'] = config.display;
        }
        
        // Width
        if (config.width && config.width !== 'auto') {
            styles['width'] = config.width;
            // Si es 100%, también cambiar display a block
            if (config.width === '100%' && !config.display) {
                styles['display'] = 'block';
            }
        }
        
        // Text Align
        if (config.textAlign) {
            styles['text-align'] = config.textAlign;
        }
        
        // Background Color
        if (config.backgroundColor) {
            styles['background-color'] = config.backgroundColor;
        }
        
        // Text Color
        if (config.color) {
            styles['color'] = config.color;
        }
        
        // Cursor
        if (config.cursor && config.cursor !== 'pointer') {
            styles['cursor'] = config.cursor;
        }
        
        // Transition
        if (config.transition) {
            styles['transition'] = config.transition;
        }
        
        // Transform
        if (config.transform) {
            styles['transform'] = config.transform;
        }
        
        // Border
        if (config.borderWidth) {
            styles['border-width'] = config.borderWidth;
        }
        if (config.borderStyle) {
            styles['border-style'] = config.borderStyle;
        }
        if (config.borderColor) {
            styles['border-color'] = config.borderColor;
        }
        if (config.borderRadius) {
            styles['border-radius'] = config.borderRadius;
        }
        
        // Typography overrides
        if (config.typography) {
            var t = config.typography;
            if (t.font && t.font !== 'Default' && t.font !== 'System') {
                styles['font-family'] = t.font + ', sans-serif';
            }
            if (t.size) {
                styles['font-size'] = t.size;
            }
            if (t.weight) {
                styles['font-weight'] = t.weight;
            }
            if (t.lineHeight) {
                styles['line-height'] = t.lineHeight;
            }
            if (t.letterSpacing) {
                styles['letter-spacing'] = t.letterSpacing;
            }
            if (t.transform && t.transform !== 'none') {
                styles['text-transform'] = t.transform;
            }
        }

        // Spacing (Padding)
        if (config.padding) {
            var p = config.padding;
            if (p.superior) styles['padding-top'] = p.superior;
            if (p.derecha) styles['padding-right'] = p.derecha;
            if (p.inferior) styles['padding-bottom'] = p.inferior;
            if (p.izquierda) styles['padding-left'] = p.izquierda;
        }

        // Spacing (Margin)
        if (config.margin) {
            var m = config.margin;
            if (m.superior) styles['margin-top'] = m.superior;
            if (m.derecha) styles['margin-right'] = m.derecha;
            if (m.inferior) styles['margin-bottom'] = m.inferior;
            if (m.izquierda) styles['margin-left'] = m.izquierda;
        }
        
        return styles;
    }

    /**
     * Maneja actualizaciones específicas del botón
     * Retorna true si manejó la actualización, false para delegar al style composer
     * 
     * IMPORTANTE: Esta función maneja paths anidados como 'typography.size' o 'padding.superior'
     */
    function handleUpdate(block, path, value) {
        if (!block || !block.element) return false;
        var el = block.element;
        
        // === CONTENIDO ===
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

        // === TIPOGRAFÍA (campos anidados) ===
        // Usando indexOf en lugar de startsWith para compatibilidad ES5
        if (path.indexOf('typography.') === 0) {
            var typoProp = path.replace('typography.', '');
            switch (typoProp) {
                case 'font':
                    if (value && value !== 'System' && value !== 'Default') {
                        el.style.fontFamily = value + ', sans-serif';
                    } else {
                        el.style.fontFamily = '';
                    }
                    break;
                case 'size':
                    el.style.fontSize = normalizeSize(value) || '';
                    break;
                case 'weight':
                    el.style.fontWeight = value || '';
                    break;
                case 'lineHeight':
                    el.style.lineHeight = value || '';
                    break;
                case 'letterSpacing':
                    el.style.letterSpacing = normalizeSize(value) || '';
                    break;
                case 'transform':
                    el.style.textTransform = (value && value !== 'none') ? value : '';
                    break;
            }
            return true;
        }

        // === SPACING: PADDING (campos anidados) ===
        if (path.indexOf('padding.') === 0) {
            var paddingProp = path.replace('padding.', '');
            var paddingVal = normalizeSize(value) || '';
            switch (paddingProp) {
                case 'superior': el.style.paddingTop = paddingVal; break;
                case 'derecha': el.style.paddingRight = paddingVal; break;
                case 'inferior': el.style.paddingBottom = paddingVal; break;
                case 'izquierda': el.style.paddingLeft = paddingVal; break;
            }
            return true;
        }

        // === SPACING: MARGIN (campos anidados) ===
        if (path.indexOf('margin.') === 0) {
            var marginProp = path.replace('margin.', '');
            var marginVal = normalizeSize(value) || '';
            switch (marginProp) {
                case 'superior': el.style.marginTop = marginVal; break;
                case 'derecha': el.style.marginRight = marginVal; break;
                case 'inferior': el.style.marginBottom = marginVal; break;
                case 'izquierda': el.style.marginLeft = marginVal; break;
            }
            return true;
        }

        // === ESTILOS DIRECTOS ===
        if (path === 'display') {
            el.style.display = value || '';
            return true;
        }

        if (path === 'width') {
            el.style.width = value || '';
            // Si es 100%, cambiar a block
            if (value === '100%') {
                el.style.display = 'block';
            }
            return true;
        }

        if (path === 'textAlign') {
            el.style.textAlign = value || '';
            return true;
        }

        if (path === 'backgroundColor') {
            el.style.backgroundColor = value || '';
            return true;
        }

        if (path === 'color') {
            el.style.color = value || '';
            return true;
        }

        if (path === 'cursor') {
            el.style.cursor = value || '';
            return true;
        }

        if (path === 'transition') {
            el.style.transition = value || '';
            return true;
        }

        if (path === 'transform') {
            el.style.transform = value || '';
            return true;
        }

        // === BORDER ===
        if (path === 'borderWidth') {
            el.style.borderWidth = normalizeSize(value) || '';
            return true;
        }
        if (path === 'borderStyle') {
            el.style.borderStyle = value || '';
            return true;
        }
        if (path === 'borderColor') {
            el.style.borderColor = value || '';
            return true;
        }
        if (path === 'borderRadius') {
            el.style.borderRadius = normalizeSize(value) || '';
            return true;
        }
        
        // Delegar al style composer para propiedades no manejadas aquí
        return false;
    }

    /**
     * Función para leer valores computados del elemento (sincronización bidireccional)
     * Usado por el panel para mostrar valores reales aplicados por CSS
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
