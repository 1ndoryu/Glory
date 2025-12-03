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
     * 
     * ARQUITECTURA DE HERENCIA Y ESPECIFICIDAD (CRÍTICO):
     * Este compositor sigue una jerarquía estricta para resolver el "Zombie Bug" de herencia:
     * 1. Valores Explícitos (Panel): Tienen la máxima prioridad. Se inyectan como estilos inline.
     * 2. Valores Responsive: Se resuelven mediante `getValue` (Mobile -> Tablet -> Desktop).
     * 3. Fallback a Variables CSS (Theme Settings): Si no hay valor explícito, se inyecta `var(--gbn-role-prop)`.
     *    - Esto permite que el componente "escuche" los cambios globales del tema inmediatamente.
     *    - Se evita emitir valores "default" hardcoded (ej: '10px') que bloquearían la herencia.
     * 
     * @param {Object} block - The block data.
     * @param {Object} roleSchema - The component schema payload (contains .schema array and .config).
     * @param {string} bp - Current breakpoint.
     * @returns {Object} - Computed CSS styles.
     */
    function compose(block, roleSchema, bp) {
        try {
            var config = block.config || {};
            
            // if (Gbn.log) Gbn.log.debug('Style Composer Start', { blockId: block.id, bp: bp, config: config });

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
                
                // Fallback to CSS vars if no explicit padding
                if (Object.keys(spacingStyles).length === 0) {
                    // No explicit padding, do nothing.
                }

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
            if (alineacion && alineacion !== 'inherit') { 
                styles['text-align'] = alineacion; 
            } else if (block.role) {
                // Bug 31 Fix V6: Corregido nombre de variable a 'alineacion' (ID del campo)
                // Se usa var(--gbn-role-alineacion) para coincidir con lo que genera applicator.js
                styles['text-align'] = 'var(--gbn-' + block.role + '-alineacion)';
            }

            // 4. Background
            var fondo = getValue('fondo') || getValue('background');
            if (fondo) { 
                styles.background = fondo; 
            }

            // 5. Gap
            var gap = getValue('gap');
            if (gap !== null && gap !== undefined && gap !== '') {
                var gapVal = parseFloat(gap);
                if (!isNaN(gapVal)) { styles.gap = gapVal + 'px'; }
            }

            // 6. Layout (Flex/Grid)
            var layout = getValue('layout');
            
            // Definir themeSettings aquí para que esté disponible para el fallback
            var themeSettings = (Gbn.config && Gbn.config.themeSettings) || (global.gloryGbnCfg && global.gloryGbnCfg.themeSettings);

            // Fix Flex Click Bug: If layout is not explicitly set on the block, check if the theme defines a default layout.
            // [GBN-ARCH-FIX V10] HYBRID LAYOUT STRATEGY (The "Implicit" Mode)
            // Resolves conflict between Bug 27 (Flex options need processing) and Bug 32 (Grid needs CSS var control).
            // Logic:
            // 1. If no local layout, check Theme Settings.
            // 2. If Theme has layout, use it to calculate child props (justify, align, gap).
            // 3. BUT mark it as 'isImplicit'.
            // 4. If 'isImplicit', DELETE 'display' property from result. Let CSS Base handle 'display'.
            
            var isImplicit = false;
            
            // [GBN-FIX V11] Smart Layout Detection
            // If the user has customized specific layout properties (like wrap or columns),
            // we must infer the layout type and FORCE it explicitly (isImplicit = false).
            // This ensures that local customizations always work, even if they contradict the theme default.
            
            // Check for local Flex overrides
            var hasFlexOverride = config.direction || config.wrap || config.justify || config.align || 
                                  config.flexDirection || config.flexWrap || config.flexJustify || config.flexAlign;
                                  
            // Check for local Grid overrides
            var hasGridOverride = config.gridColumns || config.gridRows || config.gridGap;

            if (!layout && block.role) {
                if (hasFlexOverride) {
                    layout = 'flex';
                    isImplicit = false; // Force explicit display:flex
                    // console.log('[GBN-DEBUG] Composer: Auto-detected Flex from overrides');
                } else if (hasGridOverride) {
                    layout = 'grid';
                    isImplicit = false; // Force explicit display:grid
                    // console.log('[GBN-DEBUG] Composer: Auto-detected Grid from overrides');
                } else {
                    // No local overrides, fallback to Theme
                    var themeLayout = null;
                    if (themeSettings && themeSettings.components && themeSettings.components[block.role]) {
                        themeLayout = themeSettings.components[block.role].layout;
                    }

                    if (themeLayout) {
                        layout = themeLayout;
                        isImplicit = true; // Pure theme inheritance, let CSS vars handle display
                    }
                }
            }

            if (layout) {
                var layoutStyles = {};
                if (layout === 'grid') {
                    layoutStyles = layoutGrid(block, bp);
                } else if (layout === 'flex') {
                    // console.log('[GBN-DEBUG] Composer: Applying Flex Layout', { blockId: block.id, bp: bp });
                    layoutStyles = layoutFlex(block, bp);
                } else if (layout === 'block') {
                    layoutStyles.display = 'block';
                }
                
                // [GBN-FIX V10] Critical: If layout comes from theme (Implicit), DO NOT inline 'display'.
                // This lets CSS variables control the display mode (switching Flex/Grid instantly),
                // while still applying the calculated child properties (justify, gap, etc.) inline.
                if (isImplicit && layoutStyles.display) {
                    delete layoutStyles.display;
                }
                
                Object.assign(styles, layoutStyles);
            }

            // 7. Custom CSS
            var customCss = getValue('custom_css');

            // if (Gbn.log) Gbn.log.debug('Style Composer Result', { blockId: block.id, styles: styles });
            
            // Return object with styles and customCss
            // We attach customCss as a non-enumerable property or just a property if consumers handle it.
            // But to avoid breaking consumers expecting just styles, we can attach it to the styles object 
            // but that's messy.
            // Better to return a structured object: { inline: styles, custom: customCss }
            // BUT this breaks signature.
            // Let's attach it to the styles object as a special property that styleManager will strip.
            if (customCss) {
                styles.__custom_css = customCss;
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
