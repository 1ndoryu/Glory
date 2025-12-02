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
             // Helper para aplicar padding descompuesto
             function applyPadding(prefix, paddingObj) {
                 if (paddingObj && typeof paddingObj === 'object') {
                     // Mapeo de nombres en español a nombres CSS
                     var map = {
                         superior: 'top',
                         derecha: 'right',
                         inferior: 'bottom',
                         izquierda: 'left'
                     };
                     
                     Object.keys(map).forEach(function(key) {
                         var cssName = map[key];
                         var varName = prefix + '-padding-' + cssName;
                         setOrRemoveValue(varName, paddingObj[key]);
                     });
                 } else if (paddingObj) {
                     // Si es un valor único, aplicarlo a todas las direcciones
                     ['top', 'right', 'bottom', 'left'].forEach(function(dir) {
                         setOrRemoveValue(prefix + '-padding-' + dir, paddingObj);
                     });
                 } else {
                     // Remover todas las direcciones
                     ['top', 'right', 'bottom', 'left'].forEach(function(dir) {
                         root.style.removeProperty(prefix + '-padding-' + dir);
                     });
                 }
             }
             
             // Helper para aplicar propiedades de layout
             function applyLayoutProperties(prefix, comp) {
                 // Layout type
                 setOrRemove(prefix + '-layout', comp.layout);
                 
                 // Flex properties
                 setOrRemove(prefix + '-direction', comp.direction || comp.flexDirection);
                 setOrRemove(prefix + '-wrap', comp.wrap || comp.flexWrap);
                 setOrRemove(prefix + '-justify', comp.justify || comp.flexJustify);
                 setOrRemove(prefix + '-align', comp.align || comp.flexAlign);
                 
                 // Grid properties
                 if (comp.gridColumns) {
                     setOrRemove(prefix + '-grid-columns', comp.gridColumns);
                 }
                 if (comp.gridGap !== undefined) {
                     setOrRemoveValue(prefix + '-grid-gap', comp.gridGap);
                 }
                 
                 // Max width
                 if (comp.maxAncho !== undefined && comp.maxAncho !== null && comp.maxAncho !== '') {
                     setOrRemoveValue(prefix + '-max-width', comp.maxAncho);
                 } else {
                     root.style.removeProperty(prefix + '-max-width');
                 }
                 
                 // Height
                 setOrRemove(prefix + '-height', comp.height);
             }
             
             Object.keys(settings.components).forEach(function(role) {
                 var rawComp = settings.components[role];
                 if (!rawComp) return;

                 // Obtener configuración efectiva para el breakpoint actual
                 var comp = getEffectiveComponentConfig(rawComp, breakpoint);
                 
                 var prefix = '--gbn-' + role;
                 
                 // Aplicar padding
                 applyPadding(prefix, comp.padding);
                 
                 // Aplicar background
                 setOrRemove(prefix + '-background', comp.background);
                 
                 // Aplicar gap
                 setOrRemoveValue(prefix + '-gap', comp.gap);
                 
                 // Aplicar propiedades de layout (layout, direction, wrap, justify, align, grid, maxAncho, height)
                 applyLayoutProperties(prefix, comp);
                 
                 // Propiedades específicas por rol
                 if (role === 'secundario') {
                     setOrRemoveValue(prefix + '-width', comp.width);
                 }
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
