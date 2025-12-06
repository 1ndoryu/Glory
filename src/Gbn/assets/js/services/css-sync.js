;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};

    /**
     * CSS Sync Module - Sincronización CSS <-> Panel
     * 
     * [BUG-003 FIX] Refactorizado Dic 2025
     * Ahora lee dinámicamente TODOS los roles registrados en gloryGbnCfg.roleSchemas
     * y TODAS las propiedades CSS relevantes desde CONFIG_TO_CSS_MAP.
     * 
     * Antes: Solo leía 2 roles (principal, secundario) y 3 propiedades (padding, background, gap)
     * Ahora: Lee todos los roles y ~40+ propiedades CSS
     */

    /**
     * Obtiene todos los roles y sus selectores desde la configuración global
     * @returns {Array<{name: string, selector: string}>}
     */
    function getAllRoles() {
        var roles = [];
        
        if (typeof gloryGbnCfg !== 'undefined' && gloryGbnCfg.roleSchemas) {
            Object.keys(gloryGbnCfg.roleSchemas).forEach(function(role) {
                var roleData = gloryGbnCfg.roleSchemas[role];
                if (roleData && roleData.selector) {
                    roles.push({
                        name: role,
                        selector: roleData.selector
                    });
                }
            });
        }

        // Fallback si no hay config (mantener compatibilidad)
        if (roles.length === 0) {
            roles = [
                { name: 'principal', selector: '[gloryDiv]' },
                { name: 'secundario', selector: '[gloryDivSecundario]' },
                { name: 'text', selector: '[gloryTexto]' },
                { name: 'button', selector: '[gloryButton]' },
                { name: 'image', selector: '[gloryImagen]' }
            ];
        }

        return roles;
    }

    /**
     * Obtiene el mapeo de propiedades config -> CSS
     * @returns {Object}
     */
    function getCssMap() {
        // Usar el CONFIG_TO_CSS_MAP centralizado si está disponible
        if (Gbn.ui && Gbn.ui.fieldUtils && Gbn.ui.fieldUtils.CONFIG_TO_CSS_MAP) {
            return Gbn.ui.fieldUtils.CONFIG_TO_CSS_MAP;
        }
        
        // Fallback con propiedades básicas
        return {
            // Spacing
            'padding.superior': 'paddingTop',
            'padding.derecha': 'paddingRight',
            'padding.inferior': 'paddingBottom',
            'padding.izquierda': 'paddingLeft',
            'margin.superior': 'marginTop',
            'margin.derecha': 'marginRight',
            'margin.inferior': 'marginBottom',
            'margin.izquierda': 'marginLeft',
            // Background
            'fondo': 'backgroundColor',
            'backgroundColor': 'backgroundColor',
            // Layout
            'gap': 'gap',
            'display': 'display',
            // Dimensions
            'width': 'width',
            'height': 'height',
            // Typography
            'color': 'color',
            // Border
            'borderRadius': 'borderRadius',
            'borderWidth': 'borderWidth',
            'borderColor': 'borderColor'
        };
    }

    /**
     * Verifica si un valor es un browser default que debe ignorarse
     * @param {string} cssProperty - Propiedad CSS
     * @param {string} value - Valor computado
     * @returns {boolean}
     */
    function isBrowserDefault(cssProperty, value) {
        // Usar la función centralizada si está disponible
        if (Gbn.ui && Gbn.ui.fieldUtils && Gbn.ui.fieldUtils.isBrowserDefault) {
            return Gbn.ui.fieldUtils.isBrowserDefault(cssProperty, value);
        }
        
        // Fallback: detección básica de defaults
        var defaults = {
            'paddingTop': ['0px', '0'],
            'paddingRight': ['0px', '0'],
            'paddingBottom': ['0px', '0'],
            'paddingLeft': ['0px', '0'],
            'marginTop': ['0px', '0'],
            'marginRight': ['0px', '0'],
            'marginBottom': ['0px', '0'],
            'marginLeft': ['0px', '0'],
            'backgroundColor': ['rgba(0, 0, 0, 0)', 'transparent'],
            'gap': ['0px', 'normal'],
            'width': ['auto'],
            'height': ['auto'],
            'borderRadius': ['0px', '0'],
            'borderWidth': ['0px', '0'],
            'display': ['block', 'inline']
        };

        var defaultVals = defaults[cssProperty];
        if (!defaultVals) return false;
        return defaultVals.indexOf(value) !== -1;
    }

    /**
     * Lee valores CSS de elementos temporales para TODOS los roles registrados
     * Lee TODAS las propiedades del CONFIG_TO_CSS_MAP
     * 
     * @returns {Object} { components: { [role]: { [configPath]: value, ... } } }
     */
    function readCssDefaults() {
        var defaults = {
            components: {}
        };

        var roles = getAllRoles();
        var cssMap = getCssMap();

        roles.forEach(function(role) {
            // Crear elemento temporal con el selector del rol
            var temp = document.createElement('div');
            
            // Aplicar el selector (ej: [gloryDiv] -> setAttribute('gloryDiv', ''))
            var selectorMatch = role.selector.match(/\[([^\]]+)\]/);
            if (selectorMatch && selectorMatch[1]) {
                temp.setAttribute(selectorMatch[1], '');
            }
            
            // Ocultar elemento para no afectar layout
            temp.style.cssText = 'position:absolute;visibility:hidden;pointer-events:none;left:-9999px;top:-9999px;';
            document.body.appendChild(temp);

            try {
                var computed = window.getComputedStyle(temp);
                var roleDefaults = {};

                // Leer todas las propiedades del CSS MAP
                Object.keys(cssMap).forEach(function(configPath) {
                    var cssProperty = cssMap[configPath];
                    var value = computed[cssProperty];

                    // Solo incluir si no es un browser default
                    if (value && !isBrowserDefault(cssProperty, value)) {
                        // Para propiedades anidadas (ej: padding.superior), crear estructura
                        if (configPath.indexOf('.') !== -1) {
                            var parts = configPath.split('.');
                            var parent = parts[0];
                            var child = parts[1];
                            
                            if (!roleDefaults[parent]) {
                                roleDefaults[parent] = {};
                            }
                            roleDefaults[parent][child] = value;
                        } else {
                            roleDefaults[configPath] = value;
                        }
                    }
                });

                // Solo agregar el rol si tiene al menos un valor no-default
                if (Object.keys(roleDefaults).length > 0) {
                    defaults.components[role.name] = roleDefaults;
                }

            } catch (e) {
                if (Gbn.utils && Gbn.utils.warn) {
                    Gbn.utils.warn('[cssSync] Error leyendo CSS defaults para', role.name, e);
                }
            } finally {
                if (temp.parentNode) {
                    document.body.removeChild(temp);
                }
            }
        });

        return defaults;
    }

    /**
     * Sincroniza la configuración del tema con valores CSS
     * Respeta el estado de sincronización (__sync)
     * 
     * @param {Object} themeSettings - Configuración actual del tema
     * @returns {Object} themeSettings actualizado
     */
    function syncThemeWithCss(themeSettings) {
        if (!themeSettings) return themeSettings;

        var cssDefaults = readCssDefaults();
        
        if (!themeSettings.components) {
            themeSettings.components = {};
        }

        // Sincronizar cada componente
        Object.keys(cssDefaults.components).forEach(function(role) {
            var cssComp = cssDefaults.components[role];
            
            if (!themeSettings.components[role]) {
                themeSettings.components[role] = {};
            }
            
            var themeComp = themeSettings.components[role];
            
            // Inicializar estado de sincronización si no existe
            if (!themeComp.__sync) {
                themeComp.__sync = {};
            }

            // Sincronizar cada propiedad (incluyendo nested como padding.superior)
            syncProperties(cssComp, themeComp, themeComp.__sync, '');
        });

        return themeSettings;
    }

    /**
     * Sincroniza propiedades recursivamente (soporta objetos anidados)
     */
    function syncProperties(source, target, syncState, prefix) {
        Object.keys(source).forEach(function(key) {
            var fullKey = prefix ? prefix + '.' + key : key;
            var value = source[key];

            if (value && typeof value === 'object' && !Array.isArray(value)) {
                // Es un objeto anidado (ej: padding)
                if (!target[key]) target[key] = {};
                if (!syncState[key]) syncState[key] = {};
                syncProperties(value, target[key], syncState[key], fullKey);
            } else {
                // Valor simple
                // Solo sincronizar si no tiene marca o está marcado como 'css'
                var currentSync = syncState[key];
                if (!currentSync || currentSync === 'css') {
                    target[key] = value;
                    syncState[key] = 'css';
                }
            }
        });
    }

    /**
     * Restaura la sincronización con CSS para un componente específico o todos
     * 
     * @param {Object} themeSettings - Configuración del tema
     * @param {string} [componentRole] - Rol específico a restaurar (opcional, todos si no se especifica)
     * @returns {Object} themeSettings actualizado
     */
    function restoreCssSync(themeSettings, componentRole) {
        if (!themeSettings || !themeSettings.components) return themeSettings;

        var cssDefaults = readCssDefaults();
        
        var rolesToRestore = componentRole 
            ? [componentRole]
            : Object.keys(cssDefaults.components);

        rolesToRestore.forEach(function(role) {
            if (!cssDefaults.components[role]) return;
            
            var cssComp = cssDefaults.components[role];
            
            if (!themeSettings.components[role]) {
                themeSettings.components[role] = {};
            }
            
            var themeComp = themeSettings.components[role];
            if (!themeComp.__sync) themeComp.__sync = {};
            
            // Restaurar todas las propiedades del CSS
            restoreProperties(cssComp, themeComp, themeComp.__sync);
        });

        return themeSettings;
    }

    /**
     * Restaura propiedades recursivamente marcando como 'css'
     */
    function restoreProperties(source, target, syncState) {
        Object.keys(source).forEach(function(key) {
            var value = source[key];

            if (value && typeof value === 'object' && !Array.isArray(value)) {
                if (!target[key]) target[key] = {};
                if (!syncState[key]) syncState[key] = {};
                restoreProperties(value, target[key], syncState[key]);
            } else {
                target[key] = value;
                syncState[key] = 'css';
            }
        });
    }

    // Exponer API pública
    Gbn.cssSync = {
        readDefaults: readCssDefaults,
        syncTheme: syncThemeWithCss,
        restore: restoreCssSync,
        // Utilidades expuestas para debugging
        getAllRoles: getAllRoles,
        getCssMap: getCssMap
    };

})(window);
