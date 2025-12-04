;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.renderers = Gbn.ui.renderers || {};

    /**
     * Genera los estilos CSS para un bloque de texto basado en su configuración.
     * Esta función es llamada por el styleManager para obtener los estilos inline.
     */
    function getStyles(config, block) {
        var styles = {};
        
        // Alineación de texto
        if (config.alineacion) { styles['text-align'] = config.alineacion; }
        
        // Color de texto
        if (config.color) { styles['color'] = config.color; }
        
        // Tamaño legacy (mantener compatibilidad)
        if (config.size) { styles['font-size'] = config.size; }
        
        // Tipografía completa
        if (config.typography) {
            var t = config.typography;
            if (t.font && t.font !== 'System' && t.font !== 'Default') { 
                styles['font-family'] = t.font + ', sans-serif'; 
            }
            if (t.size) { styles['font-size'] = t.size; }
            if (t.lineHeight) { styles['line-height'] = t.lineHeight; }
            if (t.letterSpacing) { styles['letter-spacing'] = t.letterSpacing; }
            if (t.transform && t.transform !== 'none') { styles['text-transform'] = t.transform; }
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

        // Background
        if (config.background) { styles['background'] = config.background; }
        if (config.backgroundColor) { styles['background-color'] = config.backgroundColor; }

        // Border
        if (config.borderWidth) { styles['border-width'] = config.borderWidth; }
        if (config.borderStyle) { styles['border-style'] = config.borderStyle; }
        if (config.borderColor) { styles['border-color'] = config.borderColor; }
        if (config.borderRadius) { styles['border-radius'] = config.borderRadius; }

        return styles;
    }

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
     * Maneja actualizaciones de configuración en tiempo real.
     * Aplica cambios directamente al DOM para feedback instantáneo.
     * 
     * IMPORTANTE: Esta función maneja paths anidados como 'typography.size' o 'padding.superior'
     */
    function handleUpdate(block, path, value) {
        if (!block || !block.element) return false;
        var el = block.element;

        // === CONTENIDO ===
        if (path === 'tag') {
            var oldEl = el;
            var newTag = value || 'p';
            if (oldEl.tagName.toLowerCase() !== newTag.toLowerCase()) {
                var newEl = document.createElement(newTag);
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
        
        if (path === 'texto') {
            var controls = el.querySelector('.gbn-controls-group');
            el.innerHTML = value;
            if (controls) el.appendChild(controls);
            return true;
        }

        // === ALINEACIÓN ===
        if (path === 'alineacion') {
            el.style.textAlign = value || '';
            return true;
        }

        // === COLOR ===
        if (path === 'color') {
            el.style.color = value || '';
            return true;
        }

        // === TAMAÑO LEGACY ===
        if (path === 'size') {
            el.style.fontSize = normalizeSize(value) || '';
            return true;
        }

        // === TIPOGRAFÍA (campos anidados) ===
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

        // === BACKGROUND ===
        if (path === 'background' || path === 'backgroundColor') {
            el.style.background = value || '';
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

        // No se manejó, dejar que el sistema genérico lo maneje
        return false;
    }

    Gbn.ui.renderers.text = {
        getStyles: getStyles,
        handleUpdate: handleUpdate
    };

})(typeof window !== 'undefined' ? window : this);
