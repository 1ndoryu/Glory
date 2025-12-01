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
    }

    function applyThemeSettings(settings) {
        // Use data-gbn-root for scoping, fallback to documentElement if not found (but prefer root)
        var root = document.querySelector('[data-gbn-root]') || document.documentElement;
        if (!settings) return;
        
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
             
             Object.keys(settings.components).forEach(function(role) {
                 var comp = settings.components[role];
                 if (!comp) return;
                 
                 // Map specific known properties to CSS variables
                 if (role === 'principal') {
                     applyPadding('--gbn-principal', comp.padding);
                     setOrRemove('--gbn-principal-background', comp.background);
                     setOrRemoveValue('--gbn-principal-gap', comp.gap);
                     // Layout defaults could be vars too if we updated CSS
                 } else if (role === 'secundario') {
                     applyPadding('--gbn-secundario', comp.padding);
                     setOrRemove('--gbn-secundario-background', comp.background);
                     setOrRemoveValue('--gbn-secundario-width', comp.width);
                 }
             });
        }
    }

    Gbn.ui.theme.applicator = {
        toCssValue: toCssValue,
        applyPageSettings: applyPageSettings,
        applyThemeSettings: applyThemeSettings
    };

})(window);
