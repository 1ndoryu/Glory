;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.renderers = Gbn.ui.renderers || {};

    // Helper to get value using shared getResponsiveValue
    function get(block, path, bp) {
        return Gbn.ui.renderers.shared.getResponsiveValue(block, path, bp);
    }

    /**
     * Renderiza estilos Flexbox.
     * 
     * NOTA SOBRE HERENCIA (V5):
     * El uso de `var(--gbn-role-prop)` como fallback en las propiedades individuales (wrap, direction, etc.)
     * es lo que permite que el Theme Settings funcione correctamente.
     * Al cambiar una opción global, `applicator.js` actualiza la variable CSS, y como este renderer
     * inyecta la variable en el estilo inline (cuando no hay valor local), el navegador
     * repinta inmediatamente sin necesidad de re-renderizar el componente JS.
     */
    function renderFlex(block, bp) {
        var styles = {};
        styles.display = 'flex';
        
        var direction = get(block, 'direction', bp) || get(block, 'flexDirection', bp);
        var wrap = get(block, 'wrap', bp) || get(block, 'flexWrap', bp);
        var justify = get(block, 'justify', bp) || get(block, 'flexJustify', bp);
        var align = get(block, 'align', bp) || get(block, 'flexAlign', bp);
        var gap = get(block, 'gap', bp);
        
        var role = block.role;
        // Prefijo para variables CSS: --gbn-principal- o --gbn-secundario-
        var prefix = role ? '--gbn-' + role + '-' : null;

        // 1. Flex Direction
        if (direction) { 
            styles['flex-direction'] = direction; 
        } else if (prefix) {
            // Fallback robusto: prueba 'direction' (ID corto) y 'flex-direction' (CSS prop)
            styles['flex-direction'] = 'var(' + prefix + 'direction, var(' + prefix + 'flex-direction))';
        }

        // 2. Flex Wrap
        if (wrap) { 
            styles['flex-wrap'] = wrap; 
        } else if (prefix) {
            styles['flex-wrap'] = 'var(' + prefix + 'wrap, var(' + prefix + 'flex-wrap))';
        }

        // 3. Justify Content
        if (justify) { 
            styles['justify-content'] = justify; 
        } else if (prefix) {
            // Bug 31 Fix: Probar 'justify' (ID probable) y 'justify-content'
            styles['justify-content'] = 'var(' + prefix + 'justify, var(' + prefix + 'justify-content))';
        }

        // 4. Align Items
        if (align) { 
            styles['align-items'] = align; 
        } else if (prefix) {
            // Bug 31 Fix: Probar 'align' (ID probable) y 'align-items'
            styles['align-items'] = 'var(' + prefix + 'align, var(' + prefix + 'align-items))';
        }

        // 5. Gap
        if (gap) { 
            styles.gap = gap + 'px'; 
        } else if (prefix) {
            styles.gap = 'var(' + prefix + 'gap)';
        }
        
        return styles;
    }

    Gbn.ui.renderers.layoutFlex = renderFlex;

})(typeof window !== 'undefined' ? window : this);
