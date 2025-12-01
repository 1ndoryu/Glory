;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    var utils = Gbn.utils;

    /**
     * Lee valores CSS de elementos temporales para sincronizar con Panel de Tema
     * Solo se ejecuta en modo dev
     */
    function readCssDefaults() {
        var defaults = {
            components: {}
        };

        // Definir los roles y sus atributos
        var roles = [
            { name: 'principal', attr: 'gloryDiv' },
            { name: 'secundario', attr: 'gloryDivSecundario' }
        ];

        roles.forEach(function(role) {
            // Crear elemento temporal invisible
            var temp = document.createElement('div');
            temp.setAttribute(role.attr, '');
            temp.style.position = 'absolute';
            temp.style.visibility = 'hidden';
            temp.style.pointerEvents = 'none';
            temp.style.left = '-9999px';
            document.body.appendChild(temp);

            try {
                var computed = window.getComputedStyle(temp);
                
                // Leer valores de padding
                var padding = {
                    superior: computed.paddingTop,
                    derecha: computed.paddingRight,
                    inferior: computed.paddingBottom,
                    izquierda: computed.paddingLeft
                };

                // Leer otros valores relevantes
                var background = computed.backgroundColor;
                var gap = computed.gap || computed.rowGap || '0px';

                defaults.components[role.name] = {
                    padding: padding,
                    background: background !== 'rgba(0, 0, 0, 0)' && background !== 'transparent' ? background : null,
                    gap: gap !== '0px' && gap !== 'normal' ? gap : null
                };

            } catch (e) {
                utils.warn('Error leyendo CSS defaults para', role.name, e);
            } finally {
                document.body.removeChild(temp);
            }
        });

        return defaults;
    }

    /**
     * Sincroniza la configuración del tema con valores CSS
     * Respeta el estado de sincronización (__sync)
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

            // Sincronizar cada propiedad
            Object.keys(cssComp).forEach(function(prop) {
                // Solo sincronizar si:
                // 1. No tiene marca de sincronización (primera vez)
                // 2. O está marcado como 'css' (sincronizado)
                var syncState = themeComp.__sync[prop];
                
                if (!syncState || syncState === 'css') {
                    themeComp[prop] = cssComp[prop];
                    themeComp.__sync[prop] = 'css';
                }
            });
        });

        return themeSettings;
    }

    /**
     * Restaura la sincronización con CSS para un componente específico o todos
     */
    function restoreCssSync(themeSettings, componentRole) {
        if (!themeSettings || !themeSettings.components) return themeSettings;

        var cssDefaults = readCssDefaults();
        
        if (componentRole) {
            // Restaurar un componente específico
            if (cssDefaults.components[componentRole] && themeSettings.components[componentRole]) {
                var cssComp = cssDefaults.components[componentRole];
                var themeComp = themeSettings.components[componentRole];
                
                Object.keys(cssComp).forEach(function(prop) {
                    themeComp[prop] = cssComp[prop];
                    if (!themeComp.__sync) themeComp.__sync = {};
                    themeComp.__sync[prop] = 'css';
                });
            }
        } else {
            // Restaurar todos los componentes
            Object.keys(cssDefaults.components).forEach(function(role) {
                var cssComp = cssDefaults.components[role];
                
                if (!themeSettings.components[role]) {
                    themeSettings.components[role] = {};
                }
                
                var themeComp = themeSettings.components[role];
                
                Object.keys(cssComp).forEach(function(prop) {
                    themeComp[prop] = cssComp[prop];
                    if (!themeComp.__sync) themeComp.__sync = {};
                    themeComp.__sync[prop] = 'css';
                });
            });
        }

        return themeSettings;
    }

    // Exponer API pública
    Gbn.cssSync = {
        readDefaults: readCssDefaults,
        syncTheme: syncThemeWithCss,
        restore: restoreCssSync
    };

})(window);
