;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.renderers = Gbn.ui.renderers || {};

    var shared = Gbn.ui.renderers.shared;
    var layoutFlex = Gbn.ui.renderers.layoutFlex;
    var layoutGrid = Gbn.ui.renderers.layoutGrid;

    /**
     * Helper to find a field in the schema array by ID
     */
    function findField(schemaArray, id) {
        if (!Array.isArray(schemaArray)) return null;
        for (var i = 0; i < schemaArray.length; i++) {
            if (schemaArray[i].id === id) return schemaArray[i];
        }
        return null;
    }

    /**
     * Composes styles for a block based on its schema traits.
     * @param {Object} block - The block data.
     * @param {Object} roleSchema - The component schema payload (contains .schema array and .config).
     * @param {string} bp - Current breakpoint.
     * @returns {Object} - Computed CSS styles.
     */
    function compose(block, roleSchema, bp) {
        var config = block.config || {};
        var styles = {};
        
        // roleSchema comes from ContainerRegistry::rolePayload() -> { config: {}, schema: [] }
        var schemaFields = roleSchema.schema || [];
        var traits = roleSchema.traits || []; // Currently not populated by PHP, but kept for future

        // Helper to get value with fallback to component defaults
        function getValue(path) {
            var val = shared.getResponsiveValue(block, path, bp);
            if (val !== undefined) return val;
            
            // Fallback to role defaults
            if (roleSchema.config && roleSchema.config[path] !== undefined) {
                return roleSchema.config[path];
            }
            return undefined;
        }

        // 1. Spacing (Padding/Margin)
        // Checks for 'HasSpacing' trait or explicit 'padding' field in schema
        var paddingField = findField(schemaFields, 'padding');
        if (traits.indexOf('HasSpacing') !== -1 || paddingField) {
            var paddingConfig = getValue('padding');
            // Fallback for legacy or if getResponsiveValue returns undefined
            if (paddingConfig === undefined) paddingConfig = config.padding; 
            
            var spacingStyles = shared.extractSpacingStyles(paddingConfig);
            Object.assign(styles, spacingStyles);
        }

        // 2. Dimensions (Height/Width)
        // Height
        var height = getValue('height');
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
        var width = getValue('width');
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
        var maxAncho = getValue('maxAncho');
        if (maxAncho !== null && maxAncho !== undefined && maxAncho !== '') {
            var val = String(maxAncho).trim();
            if (/^-?\d+(\.\d+)?$/.test(val)) {
                styles['max-width'] = val + 'px';
            } else {
                styles['max-width'] = val;
            }
        }

        // 3. Typography (Alignment)
        var alineacion = getValue('alineacion');
        if (alineacion && alineacion !== 'inherit') { styles['text-align'] = alineacion; }

        // 4. Background
        var fondo = getValue('fondo') || getValue('background');
        if (fondo) { styles.background = fondo; }

        // 5. Gap
        var gap = getValue('gap');
        if (gap !== null && gap !== undefined && gap !== '') {
            var gapVal = parseFloat(gap);
            if (!isNaN(gapVal)) { styles.gap = gapVal + 'px'; }
        }

        // 6. Layout (Flex/Grid)
        // Checks for 'HasFlexbox' or 'HasGrid' traits, or just checks the 'layout' value
        var layout = getValue('layout');
        
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
