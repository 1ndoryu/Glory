;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.renderers = Gbn.ui.renderers || {};

    var shared = Gbn.ui.renderers.shared;
    var layoutFlex = Gbn.ui.renderers.layoutFlex;
    var layoutGrid = Gbn.ui.renderers.layoutGrid;

    /**
     * Composes styles for a block based on its schema traits.
     * @param {Object} block - The block data.
     * @param {Object} schema - The component schema (from roleSchemas).
     * @param {string} bp - Current breakpoint.
     * @returns {Object} - Computed CSS styles.
     */
    function compose(block, schema, bp) {
        var config = block.config || {};
        var styles = {};
        var traits = schema.traits || [];

        // 1. Spacing (Padding/Margin)
        // Checks for 'HasSpacing' trait or explicit 'padding' field in schema
        if (traits.indexOf('HasSpacing') !== -1 || schema.fields && schema.fields.padding) {
            var paddingConfig = shared.getResponsiveValue(block, 'padding', bp);
            if (paddingConfig === undefined) paddingConfig = config.padding; // Legacy fallback
            
            var spacingStyles = shared.extractSpacingStyles(paddingConfig);
            Object.assign(styles, spacingStyles);
        }

        // 2. Dimensions (Height/Width)
        // Height
        var height = shared.getResponsiveValue(block, 'height', bp);
        if (height && height !== 'auto') {
            if (height === 'min-content') {
                styles['height'] = 'min-content';
            } else if (height === '100vh') {
                styles['height'] = '100vh';
            } else {
                 // Future: handle explicit height values if needed
            }
        }

        // Width (Specific to Secundario/Flex items usually, but good to have generic)
        var width = shared.getResponsiveValue(block, 'width', bp);
        if (width) {
             var pct = shared.parseFraction(width);
             if (pct) {
                 styles.width = pct;
                 styles['flex-basis'] = pct;
                 styles['flex-shrink'] = '0';
                 styles['flex-grow'] = '0';
             }
        }
        
        // Max Width (Principal usually)
        var maxAncho = shared.getResponsiveValue(block, 'maxAncho', bp);
        if (maxAncho !== null && maxAncho !== undefined && maxAncho !== '') {
            var val = String(maxAncho).trim();
            if (/^-?\d+(\.\d+)?$/.test(val)) {
                styles['max-width'] = val + 'px';
            } else {
                styles['max-width'] = val;
            }
        }

        // 3. Typography (Alignment)
        var alineacion = shared.getResponsiveValue(block, 'alineacion', bp);
        if (alineacion && alineacion !== 'inherit') { styles['text-align'] = alineacion; }

        // 4. Background
        var fondo = shared.getResponsiveValue(block, 'fondo', bp) || shared.getResponsiveValue(block, 'background', bp);
        if (fondo) { styles.background = fondo; }

        // 5. Gap
        var gap = shared.getResponsiveValue(block, 'gap', bp);
        if (gap !== null && gap !== undefined && gap !== '') {
            var gapVal = parseFloat(gap);
            if (!isNaN(gapVal)) { styles.gap = gapVal + 'px'; }
        }

        // 6. Layout (Flex/Grid)
        // Checks for 'HasFlexbox' or 'HasGrid' traits, or just checks the 'layout' value
        var layout = shared.getResponsiveValue(block, 'layout', bp);
        
        // Default layout logic based on component type/traits could go here, 
        // but for now we rely on the value being present or defaulting in the specific renderer if needed.
        // However, to be fully generic:
        if (!layout && traits.indexOf('HasFlexbox') !== -1) layout = 'flex';
        
        if (layout) {
            var layoutStyles = {};
            if (layout === 'grid') {
                layoutStyles = layoutGrid(block, bp);
            } else if (layout === 'flex') {
                layoutStyles = layoutFlex(block, bp);
            } else if (layout === 'block') {
                layoutStyles.display = 'block';
            }
            
            Object.assign(styles, layoutStyles);
        }

        return styles;
    }

    Gbn.ui.renderers.styleComposer = {
        compose: compose
    };

})(typeof window !== 'undefined' ? window : this);
