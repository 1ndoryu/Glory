;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};

    function toCssValue(val, defaultUnit) {
        if (val === null || val === undefined || val === '') return '';
        var strVal = String(val).trim();
        // Fix for double unit (e.g. 120pxpx)
        if (strVal.match(/pxpx$/i)) {
            strVal = strVal.replace(/pxpx$/i, 'px');
        }
        if (/^[0-9.]+[a-z%]+$/i.test(strVal)) return strVal;
        if (!isNaN(parseFloat(strVal))) return strVal + (defaultUnit || 'px');
        return strVal;
    }

    function applyPageSettings(settings) {
        // Check if a root is already defined (e.g. by PHP template)
        var existingRoot = document.querySelector('[data-gbn-root]');
        var root = existingRoot;
        
        if (!root) {
            root = document.querySelector('main') || document.body;
            if (root) {
                root.setAttribute('data-gbn-root', 'true');
            }
        }
        
        if (!root) return;
        
        if (settings.background) {
            root.style.backgroundColor = settings.background;
        }
        if (settings.padding) {
            if (typeof settings.padding === 'object') {
                root.style.paddingTop = toCssValue(settings.padding.superior);
                root.style.paddingRight = toCssValue(settings.padding.derecha);
                root.style.paddingBottom = toCssValue(settings.padding.inferior);
                root.style.paddingLeft = toCssValue(settings.padding.izquierda);
            } else {
                root.style.padding = toCssValue(settings.padding);
            }
        }
    }

    function applyThemeSettings(settings) {
        // Use data-gbn-root for scoping, or find main/body if not set yet (consistent with page settings)
        var root = document.querySelector('[data-gbn-root]');
        if (!root) {
            root = document.querySelector('main') || document.body;
            if (root) {
                root.setAttribute('data-gbn-root', 'true');
            }
        }
        // Fallback to documentElement if still nothing (unlikely)
        if (!root) root = document.documentElement;

        if (!settings) return;
        
        // Text Settings
        if (settings.text) {
            var tags = ['p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'];
            tags.forEach(function(tag) {
                if (settings.text[tag]) {
                    var s = settings.text[tag];
                    var prefix = '--gbn-' + (tag === 'p' ? 'text' : tag);
                    
                    if (s.color) root.style.setProperty(prefix + '-color', s.color);
                    if (s.size) root.style.setProperty(prefix + '-size', toCssValue(s.size));
                    if (s.font && s.font !== 'System') root.style.setProperty(prefix + '-font', s.font);
                    if (s.lineHeight) root.style.setProperty(prefix + '-lh', s.lineHeight);
                    if (s.letterSpacing) root.style.setProperty(prefix + '-ls', toCssValue(s.letterSpacing));
                    if (s.transform) root.style.setProperty(prefix + '-transform', s.transform);
                }
            });
        }
        
        // Color Settings
        if (settings.colors) {
            if (settings.colors.primary) root.style.setProperty('--gbn-primary', settings.colors.primary);
            if (settings.colors.secondary) root.style.setProperty('--gbn-secondary', settings.colors.secondary);
            if (settings.colors.accent) root.style.setProperty('--gbn-accent', settings.colors.accent);
            if (settings.colors.background) root.style.setProperty('--gbn-bg', settings.colors.background);
            
            // Custom Colors
            if (settings.colors.custom && Array.isArray(settings.colors.custom)) {
                settings.colors.custom.forEach(function(c, i) {
                    if (c.value) {
                        root.style.setProperty('--gbn-custom-' + i, c.value);
                    }
                });
            }
        }
        
        // Page Defaults
        if (settings.pages) {
            if (settings.pages.background) root.style.setProperty('--gbn-page-bg', settings.pages.background);
        }

        // Component Defaults
        if (settings.components) {
            Object.keys(settings.components).forEach(function(role) {
                var compSettings = settings.components[role];
                if (!compSettings) return;
                
                Object.keys(compSettings).forEach(function(prop) {
                    var val = compSettings[prop];
                    var prefix = '--gbn-' + role + '-' + prop;
                    
                    if (val === null || val === undefined || val === '') return;

                    // Handle complex types
                    if (prop === 'typography') {
                        // Typography object: size, font, lineHeight, etc.
                        // We map to --gbn-{role}-text-{prop} to avoid conflict or just --gbn-{role}-{prop}
                        // Let's use --gbn-{role}-text-size etc. to match text settings pattern if possible,
                        // OR just --gbn-{role}-font-size.
                        // Given the schema uses 'typography' type, val is an object.
                        if (val.size) root.style.setProperty('--gbn-' + role + '-font-size', toCssValue(val.size));
                        if (val.font && val.font !== 'System') root.style.setProperty('--gbn-' + role + '-font-family', val.font);
                        if (val.lineHeight) root.style.setProperty('--gbn-' + role + '-line-height', val.lineHeight);
                        if (val.letterSpacing) root.style.setProperty('--gbn-' + role + '-letter-spacing', toCssValue(val.letterSpacing));
                        if (val.transform) root.style.setProperty('--gbn-' + role + '-text-transform', val.transform);
                        if (val.weight) root.style.setProperty('--gbn-' + role + '-font-weight', val.weight);
                    } else if (prop === 'padding' && typeof val === 'object') {
                        // Spacing object
                        root.style.setProperty(prefix + '-top', toCssValue(val.superior));
                        root.style.setProperty(prefix + '-right', toCssValue(val.derecha));
                        root.style.setProperty(prefix + '-bottom', toCssValue(val.inferior));
                        root.style.setProperty(prefix + '-left', toCssValue(val.izquierda));
                        // Also set shorthand if possible, but vars are usually individual.
                        // Let's set individual vars.
                    } else {
                        // Simple value (color, gap, etc.)
                        root.style.setProperty(prefix, toCssValue(val));
                    }
                });
            });
        }
    }

    // Expose functions
    Gbn.ui.themeApplicator = {
        applyThemeSettings: applyThemeSettings,
        applyPageSettings: applyPageSettings,
        toCssValue: toCssValue
    };

    // Initialize Settings on Load
    document.addEventListener('DOMContentLoaded', function() {
        // 1. Identify Root (if not already done by applyPageSettings logic or PHP)
        // We defer this to applyPageSettings or just check here if needed.
        // But applyPageSettings handles the root check now.
        
        // 2. Load Config
        var config = window.gloryGbnCfg || {};
        if (window.Gbn) {
            if (!window.Gbn.config) window.Gbn.config = {};
            if (config.themeSettings) window.Gbn.config.themeSettings = config.themeSettings;
            if (config.pageSettings) window.Gbn.config.pageSettings = config.pageSettings;
        }
        
        // 3. Apply Settings
        if (config.themeSettings) {
            applyThemeSettings(config.themeSettings);
        }
        if (config.pageSettings) {
            applyPageSettings(config.pageSettings);
        }
    });

})(window);
