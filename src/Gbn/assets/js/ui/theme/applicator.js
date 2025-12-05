;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.theme = Gbn.ui.theme || {};

    function toCssValue(val, defaultUnit) {
        if (val === null || val === undefined || val === '') return '';
        if (typeof val === 'number') return val + (defaultUnit || 'px');
        if (/^\d+(\.\d+)?$/.test(val)) return val + (defaultUnit || 'px');
        return val;
    }

    function applyPageSettings(settings) {
        var root = document.querySelector('[data-gbn-root]');
        if (!root) return;
        
        if (settings.background) {
            root.style.backgroundColor = settings.background;
        } else {
            root.style.removeProperty('background-color');
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
        } else {
            root.style.removeProperty('padding');
        }
        
        // Ancho Máximo
        if (settings.maxAncho !== undefined && settings.maxAncho !== null && settings.maxAncho !== '') {
            root.style.maxWidth = toCssValue(settings.maxAncho);
            root.style.marginLeft = 'auto';
            root.style.marginRight = 'auto';
        } else {
            root.style.removeProperty('margin-right');
        }

        // Custom CSS
        if (settings.custom_css) {
            var styleId = 'gbn-page-custom-css';
            var styleEl = document.getElementById(styleId);
            if (!styleEl) {
                styleEl = document.createElement('style');
                styleEl.id = styleId;
                document.head.appendChild(styleEl);
            }
            var css = settings.custom_css.replace(/selector/g, 'body');
            styleEl.textContent = css;
        } else {
            var styleEl = document.getElementById('gbn-page-custom-css');
            if (styleEl) styleEl.textContent = '';
        }
    }

    function getEffectiveComponentConfig(comp, breakpoint) {
        if (!comp) return {};
        // Copia superficial inicial (Desktop/Base)
        var effective = {};
        Object.keys(comp).forEach(function(key) {
            if (key !== '_responsive') {
                effective[key] = comp[key];
            }
        });

        if (!breakpoint || breakpoint === 'desktop') return effective;

        var overrides = [];
        if (comp._responsive) {
            // En Mobile, heredar primero de Tablet
            if (breakpoint === 'mobile' && comp._responsive.tablet) {
                overrides.push(comp._responsive.tablet);
            }
            // Luego aplicar el breakpoint actual
            if (comp._responsive[breakpoint]) {
                overrides.push(comp._responsive[breakpoint]);
            }
        }

        overrides.forEach(function(override) {
            Object.keys(override).forEach(function(key) {
                // Manejo especial para objetos (como padding) para hacer merge y no reemplazo total
                if (key === 'padding' && typeof override[key] === 'object' && effective[key] && typeof effective[key] === 'object') {
                    // Merge de propiedades de padding
                    var pOverride = override[key];
                    var pEffective = effective[key];
                    Object.keys(pOverride).forEach(function(pKey) {
                        pEffective[pKey] = pOverride[pKey];
                    });
                } else {
                    // Para otros tipos (o si cambia de objeto a valor simple), reemplazar
                    effective[key] = override[key];
                }
            });
        });

        return effective;
    }

    function applyThemeSettings(settings, breakpoint) {
        // Use data-gbn-root for scoping, fallback to documentElement if not found (but prefer root)
        var root = document.querySelector('[data-gbn-root]') || document.documentElement;
        if (!settings) return;
        
        // Default breakpoint to global state if not provided
        if (!breakpoint && Gbn.responsive && Gbn.responsive.getCurrentBreakpoint) {
            breakpoint = Gbn.responsive.getCurrentBreakpoint();
        }
        breakpoint = breakpoint || 'desktop';

        // if (Gbn.log) Gbn.log.debug('Applying Theme Settings', { breakpoint: breakpoint, settings: settings });
        if (Gbn.log) Gbn.log.info('Theme Applicator Run', { breakpoint: breakpoint });

        // Helper to set or remove property
        function setOrRemove(prop, val) {
            if (val !== null && val !== undefined && val !== '') {
                root.style.setProperty(prop, val);
            } else {
                root.style.removeProperty(prop);
            }
        }

        // Helper to set or remove property with unit conversion
        function setOrRemoveValue(prop, val) {
            if (val !== null && val !== undefined && val !== '') {
                root.style.setProperty(prop, toCssValue(val));
            } else {
                root.style.removeProperty(prop);
            }
        }
        
        // Text Settings
        if (settings.text) {
            var tags = ['p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'];
            tags.forEach(function(tag) {
                if (settings.text[tag]) {
                    var s = settings.text[tag];
                    var prefix = '--gbn-' + (tag === 'p' ? 'text' : tag);
                    
                    setOrRemove(prefix + '-color', s.color);
                    setOrRemoveValue(prefix + '-size', s.size);
                    
                    if (s.font && s.font !== 'System') {
                        root.style.setProperty(prefix + '-font', s.font);
                    } else {
                        root.style.removeProperty(prefix + '-font');
                    }
                    
                    setOrRemove(prefix + '-lh', s.lineHeight);
                    setOrRemoveValue(prefix + '-ls', s.letterSpacing);
                    setOrRemove(prefix + '-transform', s.transform);
                }
            });
        }
        
        // Color Settings
        if (settings.colors) {
            setOrRemove('--gbn-primary', settings.colors.primary);
            setOrRemove('--gbn-secondary', settings.colors.secondary);
            setOrRemove('--gbn-accent', settings.colors.accent);
            setOrRemove('--gbn-bg', settings.colors.background);
            
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
            setOrRemove('--gbn-page-bg', settings.pages.background);
            setOrRemoveValue('--gbn-page-max-width', settings.pages.maxAncho);
            
            if (settings.pages.padding) {
                var p = settings.pages.padding;
                if (typeof p === 'object') {
                    setOrRemoveValue('--gbn-page-pt', p.superior);
                    setOrRemoveValue('--gbn-page-pr', p.derecha);
                    setOrRemoveValue('--gbn-page-pb', p.inferior);
                    setOrRemoveValue('--gbn-page-pl', p.izquierda);
                } else {
                    setOrRemoveValue('--gbn-page-pt', p);
                    setOrRemoveValue('--gbn-page-pr', p);
                    setOrRemoveValue('--gbn-page-pb', p);
                    setOrRemoveValue('--gbn-page-pl', p);
                }
            } else {
                 root.style.removeProperty('--gbn-page-pt');
                 root.style.removeProperty('--gbn-page-pr');
                 root.style.removeProperty('--gbn-page-pb');
                 root.style.removeProperty('--gbn-page-pl');
            }
        }
        
        
        // Component Defaults (Principal, Secundario, etc)
        if (settings.components) {
             var roleSchemas = (typeof gloryGbnCfg !== 'undefined' && gloryGbnCfg.roleSchemas) ? gloryGbnCfg.roleSchemas : {};

             Object.keys(settings.components).forEach(function(role) {
                 var rawComp = settings.components[role];
                 if (!rawComp) return;

                 // Obtener configuración efectiva para el breakpoint actual
                 var comp = getEffectiveComponentConfig(rawComp, breakpoint);
                 
                 // if (Gbn.log) Gbn.log.debug('Applying Component Theme', { role: role, effectiveConfig: comp });

                 var prefix = '--gbn-' + role;
                 var rolePayload = roleSchemas[role];
                 // rolePayload is { config: ..., schema: [...] }
                 
                 var fields = rolePayload ? rolePayload.schema : null;

                 if (!fields || !Array.isArray(fields)) {
                     // Fallback legacy behavior if no schema found
                     return;
                 }

                 fields.forEach(function(field) {
                     var value = comp[field.id];
                     
                     // Special handling based on field type
                     if (field.tipo === 'spacing') {
                         if (value && typeof value === 'object') {
                             var map = { superior: 'top', derecha: 'right', inferior: 'bottom', izquierda: 'left' };
                             Object.keys(map).forEach(function(key) {
                                 var cssName = map[key];
                                 var varName = prefix + '-' + field.id + '-' + cssName;
                                 setOrRemoveValue(varName, value[key]);
                             });
                         } else if (value) {
                             ['top', 'right', 'bottom', 'left'].forEach(function(dir) {
                                 setOrRemoveValue(prefix + '-' + field.id + '-' + dir, value);
                             });
                         } else {
                             ['top', 'right', 'bottom', 'left'].forEach(function(dir) {
                                 root.style.removeProperty(prefix + '-' + field.id + '-' + dir);
                             });
                         }
                     } else {
                         // Default handling
                         // Convert camelCase ID to kebab-case for CSS variable
                         // e.g. flexDirection -> flex-direction
                         var kebabId = field.id.replace(/([a-z0-9]|(?=[A-Z]))([A-Z])/g, '$1-$2').toLowerCase();
                         var varName = prefix + '-' + kebabId;
                         
                         // Special case: maxAncho -> max-width
                         if (field.id === 'maxAncho') varName = prefix + '-max-width';
                         // Special case: fondo -> background
                         if (field.id === 'fondo') varName = prefix + '-background';
                         // Special case: layout -> display
                         if (field.id === 'layout') varName = prefix + '-display';
                         // Special case: gridColumns -> grid-columns
                         if (field.id === 'gridColumns') varName = prefix + '-grid-columns';
                         
                         // Fix Bug 32: Grid Columns Unit Issue
                         // `gridColumns` must be a unitless integer to work correctly in `repeat(N, 1fr)`.
                         // The standard `setOrRemoveValue` helper (and `toCssValue`) automatically appends 'px' 
                         // to numbers, which results in invalid CSS like `repeat(2px, 1fr)`.
                         // Therefore, we bypass the unit conversion for this specific field.
                         if (field.id === 'gridColumns') {
                             setOrRemove(varName, value);
                         } else if (field.id === 'custom_css') {
                             // Handle Global Custom CSS
                             var styleId = 'gbn-theme-custom-' + role;
                             var styleEl = document.getElementById(styleId);
                             if (!styleEl) {
                                 styleEl = document.createElement('style');
                                 styleEl.id = styleId;
                                 document.head.appendChild(styleEl);
                             }
                             
                             if (value) {
                                 // Replace & with role selector
                                 // We need to know the selector for the role.
                                 // Usually it's [data-gbnRole] or .class
                                 // We can infer it from the prefix or hardcode/map it.
                                 // Principal -> [data-gbnPrincipal], Secundario -> [data-gbnSecundario], Text -> [data-gbn-text]
                                 
                                 var selector = '';
                                 if (role === 'principal') selector = '[data-gbnPrincipal]';
                                 else if (role === 'secundario') selector = '[data-gbnSecundario]';
                                 else if (role === 'text') selector = '[data-gbn-text]';
                                 else selector = '[data-gbn-' + role + ']'; // Fallback
                                 
                                 var processed = value.replace(/&/g, selector);
                                 if (processed.indexOf(selector) === -1) {
                                     processed = selector + ' { ' + processed + ' }';
                                 }
                                 styleEl.textContent = processed;
                             } else {
                                 styleEl.textContent = '';
                             }
                         } else {
                             setOrRemoveValue(varName, value);
                         }
                     }
                 });
                 
                 // Handle legacy/extra properties that might not be in schema fields directly or need special mapping
                 // e.g. Flex properties might be individual fields in schema or part of a 'layout' group?
                 // In the new architecture, traits like 'HasFlexbox' add fields like 'direction', 'wrap', etc.
                 // So if they are in schema.fields, they are handled above.
                 // We just need to ensure schema.fields includes everything.
             });
        }
    }

    // Expose functions
    Gbn.ui.theme.applicator = {
        toCssValue: toCssValue,
        applyPageSettings: applyPageSettings,
        applyThemeSettings: applyThemeSettings
    };

    // Alias for backward compatibility and frontend usage
    Gbn.ui.themeApplicator = Gbn.ui.theme.applicator;

    // Initialize Settings on Load
    document.addEventListener('DOMContentLoaded', function() {
        // 1. Load Config
        var config = window.gloryGbnCfg || {};
        if (window.Gbn) {
            if (!window.Gbn.config) window.Gbn.config = {};
            if (config.themeSettings) window.Gbn.config.themeSettings = config.themeSettings;
            if (config.pageSettings) window.Gbn.config.pageSettings = config.pageSettings;
        }
        
        // 2. Apply Settings
        if (config.themeSettings) {
            applyThemeSettings(config.themeSettings);
        }
        if (config.pageSettings) {
            applyPageSettings(config.pageSettings);
        }
    });

    // Re-apply theme settings when breakpoint changes to handle responsive overrides
    window.addEventListener('gbn:breakpointChanged', function(event) {
        var settings = (Gbn.config && Gbn.config.themeSettings) || (window.gloryGbnCfg && window.gloryGbnCfg.themeSettings);
        var breakpoint = event.detail ? event.detail.current : null;
        
        if (settings) {
            if (Gbn.log) Gbn.log.info('Re-applying Theme Settings due to breakpoint change', { breakpoint: breakpoint });
            applyThemeSettings(settings, breakpoint);
        }
    });

})(window);
