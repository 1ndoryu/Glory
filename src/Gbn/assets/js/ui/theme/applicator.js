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
            root.style.removeProperty('max-width');
            root.style.removeProperty('margin-left');
            root.style.removeProperty('margin-right');
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
        }
        
        
        // Component Defaults (Principal, Secundario, etc)
        if (settings.components) {
             var roleSchemas = (typeof gloryGbnCfg !== 'undefined' && gloryGbnCfg.roleSchemas) ? gloryGbnCfg.roleSchemas : {};

             Object.keys(settings.components).forEach(function(role) {
                 var rawComp = settings.components[role];
                 if (!rawComp) return;

                 // Obtener configuración efectiva para el breakpoint actual
                 var comp = getEffectiveComponentConfig(rawComp, breakpoint);
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
                         // Handle specific overrides if needed (e.g. flex properties mapping)
                         // But if schema id matches css var suffix, it's automatic.
                         // Current schema ids: layout, background, gap, width, height, maxAncho...
                         
                         // Mapping for legacy property names if they differ from schema id
                         // (In new schema, ids should match what we want in CSS roughly)
                         
                         // Default handling
                         // Convert camelCase ID to kebab-case for CSS variable
                         // e.g. flexDirection -> flex-direction
                         var kebabId = field.id.replace(/([a-z0-9]|(?=[A-Z]))([A-Z])/g, '$1-$2').toLowerCase();
                         var varName = prefix + '-' + kebabId;
                         
                         // Special case: maxAncho -> max-width
                         if (field.id === 'maxAncho') varName = prefix + '-max-width';
                         // Special case: fondo -> background
                         if (field.id === 'fondo') varName = prefix + '-background';
                         
                         setOrRemoveValue(varName, value);
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

})(window);
