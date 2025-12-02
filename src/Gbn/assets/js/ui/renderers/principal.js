;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.renderers = Gbn.ui.renderers || {};

    var shared = Gbn.ui.renderers.shared;
    var layoutFlex = Gbn.ui.renderers.layoutFlex;
    var layoutGrid = Gbn.ui.renderers.layoutGrid;

    function get(block, path, bp) {
        return shared.getResponsiveValue(block, path, bp);
    }

    function getStyles(config, block) {
        var bp = (Gbn.responsive && Gbn.responsive.getCurrentBreakpoint) ? Gbn.responsive.getCurrentBreakpoint() : 'desktop';
        
        // Padding
        var paddingConfig = get(block, 'padding', bp);
        // Fallback to config.padding if getResponsiveValue returns undefined (legacy behavior in original code)
        if (paddingConfig === undefined) paddingConfig = config.padding;
        
        var styles = shared.extractSpacingStyles(paddingConfig);
        
        var height = get(block, 'height', bp);
        if (height && height !== 'auto') {
            if (height === 'min-content') {
                styles['height'] = 'min-content';
            } else if (height === '100vh') {
                styles['height'] = '100vh';
            }
        }
        
        var alineacion = get(block, 'alineacion', bp);
        if (alineacion && alineacion !== 'inherit') { styles['text-align'] = alineacion; }
        
        var maxAncho = get(block, 'maxAncho', bp);
        if (maxAncho !== null && maxAncho !== undefined && maxAncho !== '') {
            var val = String(maxAncho).trim();
            if (/^-?\d+(\.\d+)?$/.test(val)) {
                styles['max-width'] = val + 'px';
            } else {
                styles['max-width'] = val;
            }
        }
        
        var fondo = get(block, 'fondo', bp) || get(block, 'background', bp);
        if (fondo) { styles.background = fondo; }
        
        // Layout
        var layout = get(block, 'layout', bp) || 'flex'; // Default to flex
        
        var layoutStyles = {};
        if (layout === 'grid') {
            layoutStyles = layoutGrid(block, bp);
        } else if (layout === 'flex') {
            layoutStyles = layoutFlex(block, bp);
        } else {
            layoutStyles.display = 'block';
        }
        
        // Merge layout styles
        Object.keys(layoutStyles).forEach(function(key) {
            styles[key] = layoutStyles[key];
        });
        
        return styles;
    }

    Gbn.ui.renderers.principal = {
        getStyles: getStyles
    };

})(typeof window !== 'undefined' ? window : this);
