(function (global) {
    'use strict';

    var Gbn = (global.Gbn = global.Gbn || {});
    Gbn.responsive = Gbn.responsive || {};

    // Constantes
    var BREAKPOINTS = {
        desktop: {min: 1025, label: 'Desktop', icon: 'desktop'},
        tablet: {min: 769, max: 1024, label: 'Tablet', icon: 'tablet'},
        mobile: {max: 768, label: 'Móvil', icon: 'mobile'}
    };

    var currentBreakpoint = 'desktop'; // Estado global

    // API Pública
    function getCurrentBreakpoint() {
        return currentBreakpoint;
    }

    function setBreakpoint(bp) {
        if (!BREAKPOINTS[bp]) return;
        var prev = currentBreakpoint;
        currentBreakpoint = bp;

        // Aplicar simulación de viewport
        applyViewportSimulation(bp);

        // Disparar evento
        window.dispatchEvent(
            new CustomEvent('gbn:breakpointChanged', {
                detail: {previous: prev, current: bp}
            })
        );
    }

    function getResponsiveValue(block, path, breakpoint) {
        breakpoint = breakpoint || currentBreakpoint;
        var utils = Gbn.ui.fieldUtils;
        if (!utils || !utils.getDeepValue) return undefined;

        // Log retrieval attempt for critical properties
        // if (Gbn.log && (path.indexOf('padding') !== -1 || path.indexOf('layout') !== -1 || path.indexOf('flex') !== -1)) {
        //      Gbn.log.debug('getResponsiveValue', { blockId: block.id, path: path, bp: breakpoint });
        // }

        // 0. Manejo especial para Theme Settings (block.role === 'theme')
        if (block.role === 'theme' && path.indexOf('components.') === 0) {
            var parts = path.split('.');
            if (parts.length >= 3) {
                var role = parts[1];
                var subPath = parts.slice(2).join('.');

                if (block.config && block.config.components && block.config.components[role]) {
                    var compConfig = block.config.components[role];

                    // 1. Buscar override en breakpoint actual
                    if (compConfig._responsive && compConfig._responsive[breakpoint]) {
                        var val = utils.getDeepValue(compConfig._responsive[breakpoint], subPath);
                        if (val !== undefined) return val;
                    }

                    // 2. Heredar de breakpoint superior
                    if (breakpoint === 'mobile' && compConfig._responsive && compConfig._responsive.tablet) {
                        var tabletVal = utils.getDeepValue(compConfig._responsive.tablet, subPath);
                        if (tabletVal !== undefined) return tabletVal;
                    }

                    // 3. Heredar de desktop
                    var desktopVal = utils.getDeepValue(compConfig, subPath);
                    if (desktopVal !== undefined) return desktopVal;
                }
                return undefined;
            }
        }

        // 1. Buscar en config del bloque para breakpoint específico
        if (block.config._responsive && block.config._responsive[breakpoint]) {
            var val = utils.getDeepValue(block.config._responsive[breakpoint], path);
            if (val !== undefined) return val;
        }

        // 2. Heredar de breakpoint superior (solo mobile hereda de tablet)
        if (breakpoint === 'mobile' && block.config._responsive && block.config._responsive.tablet) {
            var tabletVal = utils.getDeepValue(block.config._responsive.tablet, path);
            if (tabletVal !== undefined) return tabletVal;
        }

        // 3. Heredar de desktop (base del bloque)
        var desktopVal = utils.getDeepValue(block.config, path);
        if (desktopVal !== undefined) return desktopVal;

        // 4-7. Buscar en themeSettings con misma lógica
        return getThemeResponsiveValue(block.role, path, breakpoint);
    }

    function getBlockResponsiveValue(block, path, breakpoint) {
        breakpoint = breakpoint || currentBreakpoint;
        var utils = Gbn.ui.fieldUtils;
        if (!utils || !utils.getDeepValue) return undefined;

        // 1. Buscar en config del bloque para breakpoint específico
        if (block.config._responsive && block.config._responsive[breakpoint]) {
            var val = utils.getDeepValue(block.config._responsive[breakpoint], path);
            if (val !== undefined) return val;
        }

        // 2. Heredar de breakpoint superior (solo mobile hereda de tablet)
        if (breakpoint === 'mobile' && block.config._responsive && block.config._responsive.tablet) {
            var tabletVal = utils.getDeepValue(block.config._responsive.tablet, path);
            if (tabletVal !== undefined) return tabletVal;
        }

        // 3. Heredar de desktop (base del bloque)
        var desktopVal = utils.getDeepValue(block.config, path);
        if (desktopVal !== undefined) return desktopVal;

        return undefined;
    }

    function getThemeResponsiveValue(role, path, breakpoint) {
        var themeSettings = (Gbn.config && Gbn.config.themeSettings) || (gloryGbnCfg && gloryGbnCfg.themeSettings) || {};

        if (!themeSettings.components || !themeSettings.components[role]) {
            return undefined;
        }

        var roleConfig = themeSettings.components[role];
        var utils = Gbn.ui.fieldUtils;

        // 4. Theme responsive override para breakpoint actual
        if (roleConfig._responsive && roleConfig._responsive[breakpoint]) {
            var val = utils.getDeepValue(roleConfig._responsive[breakpoint], path);
            if (val !== undefined) return val;
        }

        // 5. Theme responsive override para breakpoint superior
        if (breakpoint === 'mobile' && roleConfig._responsive && roleConfig._responsive.tablet) {
            var tabletVal = utils.getDeepValue(roleConfig._responsive.tablet, path);
            if (tabletVal !== undefined) return tabletVal;
        }

        // 6. Theme desktop (base)
        var desktopVal = utils.getDeepValue(roleConfig, path);
        if (desktopVal !== undefined) return desktopVal;

        // 7. CSS defaults (delegado a cssSync)
        if (Gbn.cssSync && Gbn.cssSync.readDefaults) {
            var cssDefaults = Gbn.cssSync.readDefaults(role);
            return utils.getDeepValue(cssDefaults, path);
        }

        return undefined;
    }

    function setResponsiveValue(block, path, value, breakpoint) {
        breakpoint = breakpoint || currentBreakpoint;

        // Manejo especial para Theme Settings
        if (block.role === 'theme' && path.indexOf('components.') === 0) {
            var parts = path.split('.');
            if (parts.length >= 3) {
                var role = parts[1];
                var subPath = parts.slice(2).join('.');

                if (!block.config.components) block.config.components = {};
                if (!block.config.components[role]) block.config.components[role] = {};

                var targetConfig = block.config.components[role];

                if (breakpoint === 'desktop') {
                    setDeepValue(targetConfig, subPath, value);
                } else {
                    if (!targetConfig._responsive) targetConfig._responsive = {};
                    if (!targetConfig._responsive[breakpoint]) targetConfig._responsive[breakpoint] = {};
                    setDeepValue(targetConfig._responsive[breakpoint], subPath, value);
                }
                return;
            }
        }

        if (breakpoint === 'desktop') {
            // Desktop es la base, guardar directamente en config
            setDeepValue(block.config, path, value);
        } else {
            // Crear estructura _responsive si no existe
            if (!block.config._responsive) block.config._responsive = {};
            if (!block.config._responsive[breakpoint]) block.config._responsive[breakpoint] = {};

            setDeepValue(block.config._responsive[breakpoint], path, value);
        }
    }

    function clearResponsiveOverride(block, path, breakpoint) {
        breakpoint = breakpoint || currentBreakpoint;
        if (breakpoint === 'desktop') return; // No se puede limpiar el base

        // Manejo especial para Theme Settings
        if (block.role === 'theme' && path.indexOf('components.') === 0) {
            var parts = path.split('.');
            if (parts.length >= 3) {
                var role = parts[1];
                var subPath = parts.slice(2).join('.');

                if (block.config.components && block.config.components[role]) {
                    var targetConfig = block.config.components[role];

                    if (targetConfig._responsive && targetConfig._responsive[breakpoint]) {
                        deleteDeepValue(targetConfig._responsive[breakpoint], subPath);

                        // Limpiar estructura vacía
                        if (Object.keys(targetConfig._responsive[breakpoint]).length === 0) {
                            delete targetConfig._responsive[breakpoint];
                        }
                    }
                }
                return;
            }
        }

        if (block.config._responsive && block.config._responsive[breakpoint]) {
            deleteDeepValue(block.config._responsive[breakpoint], path);

            // Limpiar estructura vacía
            if (Object.keys(block.config._responsive[breakpoint]).length === 0) {
                delete block.config._responsive[breakpoint];
            }
        }
    }

    // Almacenar el max-width original del root para restaurarlo en Desktop
    var originalMaxWidth = null;

    function captureOriginalMaxWidth() {
        var root = document.querySelector('[data-gbn-root]');
        if (!root) return;

        // Capturar el max-width original solo la primera vez
        if (originalMaxWidth === null) {
            // Leer de los estilos computados o del atributo style
            var computedStyle = window.getComputedStyle(root);
            var inlineMaxWidth = root.style.maxWidth;

            // Priorizar inline style, luego computed style
            originalMaxWidth = inlineMaxWidth || computedStyle.maxWidth || 'none';

            // Si es 'none', ver si hay configuración en pageSettings
            if (originalMaxWidth === 'none' && Gbn.config && Gbn.config.pageSettings && Gbn.config.pageSettings.maxAncho) {
                originalMaxWidth = Gbn.config.pageSettings.maxAncho;
            }
        }
    }

    function applyViewportSimulation(breakpoint) {
        var root = document.querySelector('[data-gbn-root]');
        if (!root) return;

        // Capturar el max-width original si aun no lo hemos hecho
        captureOriginalMaxWidth();

        var widths = {
            desktop: originalMaxWidth || 'none', // Restaurar el original en desktop
            tablet: '768px',
            mobile: '375px'
        };

        var simulatedWidth = widths[breakpoint] || originalMaxWidth || 'none';
        root.style.maxWidth = simulatedWidth;

        // Aplicar margin segun el contexto
        if (breakpoint === 'desktop') {
            // En desktop, si hay max-width configurado, mantener centrado con margin auto
            if (originalMaxWidth && originalMaxWidth !== 'none') {
                root.style.marginLeft = 'auto';
                root.style.marginRight = 'auto';
            } else {
                // Sin max-width, limpiar margins
                root.style.marginLeft = '';
                root.style.marginRight = '';
            }
        } else {
            // En tablet/mobile, siempre centrar
            root.style.marginLeft = 'auto';
            root.style.marginRight = 'auto';
        }

        root.style.transition = 'max-width 0.3s ease';

        // BUG-010 FIX: Aplica simulacion a Header y Footer
        // El Header es position:fixed con width:100%, por lo que ignora el root.
        // Para simular el viewport correctamente, aplicamos max-width al header/footer.
        applyViewportToLayoutComponents(breakpoint, simulatedWidth);
    }

    /**
     * BUG-010 FIX: Aplica simulacion de viewport a componentes de layout (Header/Footer)
     * Estos componentes tienen position:fixed o width:100% y no heredan del root.
     *
     * @param {string} breakpoint - Breakpoint actual (desktop, tablet, mobile)
     * @param {string} simulatedWidth - Ancho simulado (ej: '375px', '768px', 'none')
     */
    function applyViewportToLayoutComponents(breakpoint, simulatedWidth) {
        // BUG-010: Usar querySelectorAll para encontrar TODOS los headers/footers
        var headers = document.querySelectorAll('[gloryHeader]');
        var footers = document.querySelectorAll('[gloryFooter]');

        var isSimulating = breakpoint !== 'desktop';

        // Debug: log para verificar que se encuentran los elementos
        if (Gbn.log) {
            Gbn.log.debug('applyViewportToLayoutComponents', {
                breakpoint: breakpoint,
                simulatedWidth: simulatedWidth,
                headersFound: headers.length,
                footersFound: footers.length
            });
        }

        // Aplicar estilos a TODOS los Headers
        headers.forEach(function (header) {
            if (isSimulating) {
                // En simulacion: limitar ancho y centrar
                // El header tiene position:fixed, asi que usamos left:50% + translateX(-50%)
                header.style.maxWidth = simulatedWidth;
                header.style.left = '50%';
                header.style.transform = 'translateX(-50%)';
                header.style.width = simulatedWidth; // Forzar width tambien
                header.style.transition = 'max-width 0.3s ease, transform 0.3s ease, width 0.3s ease';
            } else {
                // Desktop: restaurar estilos originales
                header.style.maxWidth = '';
                header.style.left = '';
                header.style.transform = '';
                header.style.width = '';
                header.style.transition = '';
            }
        });

        // Aplicar estilos a TODOS los Footers
        footers.forEach(function (footer) {
            if (isSimulating) {
                // En simulacion: limitar ancho y centrar
                footer.style.maxWidth = simulatedWidth;
                footer.style.width = simulatedWidth; // Forzar width tambien
                footer.style.marginLeft = 'auto';
                footer.style.marginRight = 'auto';
                footer.style.transition = 'max-width 0.3s ease, width 0.3s ease';
            } else {
                // Desktop: restaurar estilos originales
                footer.style.maxWidth = '';
                footer.style.width = '';
                footer.style.marginLeft = '';
                footer.style.marginRight = '';
                footer.style.transition = '';
            }
        });
    }

    // Helpers internos
    function setDeepValue(obj, path, value) {
        var parts = path.split('.');
        var current = obj;
        for (var i = 0; i < parts.length - 1; i++) {
            if (!current[parts[i]]) current[parts[i]] = {};
            current = current[parts[i]];
        }
        current[parts[parts.length - 1]] = value;
    }

    function deleteDeepValue(obj, path) {
        var parts = path.split('.');
        var current = obj;
        for (var i = 0; i < parts.length - 1; i++) {
            if (!current[parts[i]]) return;
            current = current[parts[i]];
        }
        delete current[parts[parts.length - 1]];
    }

    // API Pública
    Gbn.responsive = {
        BREAKPOINTS: BREAKPOINTS,
        getCurrentBreakpoint: getCurrentBreakpoint,
        setBreakpoint: setBreakpoint,
        getResponsiveValue: getResponsiveValue,
        getBlockResponsiveValue: getBlockResponsiveValue,
        getThemeResponsiveValue: getThemeResponsiveValue,
        setResponsiveValue: setResponsiveValue,
        clearResponsiveOverride: clearResponsiveOverride
    };
})(window);
