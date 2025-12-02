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
        
        // Width logic
        var width = get(block, 'width', bp);
        if (width) {
             var pct = shared.parseFraction(width);
             if (pct) {
                 styles.width = pct;
                 styles['flex-basis'] = pct;
                 styles['flex-shrink'] = '0';
                 styles['flex-grow'] = '0';
             }
        }

        var gap = get(block, 'gap', bp);
        if (gap !== null && gap !== undefined && gap !== '') {
            var gapVal = parseFloat(gap);
            if (!isNaN(gapVal)) { styles.gap = gapVal + 'px'; }
        }
        
        var layout = get(block, 'layout', bp) || 'block'; // Default to block
        
        var layoutStyles = {};
        if (layout === 'grid') {
            layoutStyles = layoutGrid(block, bp);
        } else if (layout === 'flex') {
            layoutStyles = layoutFlex(block, bp);
        } else {
            layoutStyles.display = 'block';
        }
        
        Object.keys(layoutStyles).forEach(function(key) {
            styles[key] = layoutStyles[key];
        });
        
        var fondo = get(block, 'fondo', bp) || get(block, 'background', bp);
        if (fondo) { styles.background = fondo; }
        
        return styles;
    }

    Gbn.ui.renderers.secundario = {
        getStyles: getStyles
    };

})(typeof window !== 'undefined' ? window : this);
