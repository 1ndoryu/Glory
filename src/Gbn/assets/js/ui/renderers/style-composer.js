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
        try {
            var config = block.config || {};
            var styles = {};
            
            // roleSchema comes from ContainerRegistry::rolePayload() -> { config: {}, schema: [] }
            var schemaFields = roleSchema.schema || [];
            var traits = roleSchema.traits || []; // Currently not populated by PHP, but kept for future

            // Helper to get value with fallback to component defaults
            function getValue(path) {
                var val = shared.getResponsiveValue(block, path, bp);
                if (val !== undefined) return val;
                return undefined;
            }

            // 1. Spacing (Padding/Margin)
            var paddingField = findField(schemaFields, 'padding');
            if (traits.indexOf('HasSpacing') !== -1 || paddingField) {
                var paddingConfig = getValue('padding');
                if (paddingConfig === undefined) paddingConfig = config.padding; 
                var spacingStyles = shared.extractSpacingStyles(paddingConfig);
                Object.assign(styles, spacingStyles);
            }

            // 2. Dimensions (Height/Width)
            var height = getValue('height');
            if (height && height !== 'auto') {
                if (height === 'min-content') {
                    styles['height'] = 'min-content';
                } else if (height === '100vh') {
                    styles['height'] = '100vh';
                }
            }

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
            var layout = getValue('layout');
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

        } catch (err) {
            // Error Boundary: Fail gracefully
            console.error('[GBN Style Composer] Error composing styles for block ' + block.id, err);
            if (Gbn.log) Gbn.log.error('Style Composition Failed', { blockId: block.id, error: err.message });
            
            // Return emergency styles to make the error visible but safe
            return {
                outline: '2px dashed red',
                position: 'relative',
                'min-height': '50px'
            };
        }
    }

    Gbn.ui.renderers.styleComposer = {
        compose: compose
    };

})(typeof window !== 'undefined' ? window : this);
