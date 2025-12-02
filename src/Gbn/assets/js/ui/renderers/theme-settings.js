;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.renderers = Gbn.ui.renderers || {};

    var utils = Gbn.utils;

    function cloneConfig(config) {
        var output = utils.assign({}, config || {});
        Object.keys(output).forEach(function (key) {
            var item = output[key];
            if (item && typeof item === 'object' && !Array.isArray(item)) {
                output[key] = utils.assign({}, item);
            }
        });
        return output;
    }

    function handleUpdate(block, path, value) {
        var current = cloneConfig(block.config);
        var segments = path.split('.');
        
        // NUEVO: Detectar breakpoint activo
        var breakpoint = (Gbn.responsive && Gbn.responsive.getCurrentBreakpoint) ? Gbn.responsive.getCurrentBreakpoint() : 'desktop';
        
        // Si estamos en Mobile/Tablet, escribir en _responsive[bp] en lugar de raíz
        var cursor;
        if (breakpoint !== 'desktop' && path.startsWith('components.')) {
            // Path ejemplo: "components.principal.padding.superior"
            var pathParts = path.split('.');
            var role = pathParts[1]; // "principal"
            
            // Asegurar estructura _responsive en el componente
            if (!current.components) current.components = {};
            if (!current.components[role]) current.components[role] = {};
            if (!current.components[role]._responsive) current.components[role]._responsive = {};
            if (!current.components[role]._responsive[breakpoint]) current.components[role]._responsive[breakpoint] = {};
            
            // Navegar dentro de _responsive para escribir el valor
            var relativePath = pathParts.slice(2); // ['padding', 'superior']
            cursor = current.components[role]._responsive[breakpoint];
            
            for (var i = 0; i < relativePath.length - 1; i++) {
                var key = relativePath[i];
                if (!cursor[key] || typeof cursor[key] !== 'object') {
                    cursor[key] = {};
                }
                cursor = cursor[key];
            }
            cursor[relativePath[relativePath.length - 1]] = value;
        } else {
            // Desktop o paths no-components: escribir en raíz como siempre
            cursor = current;
            for (var i = 0; i < segments.length - 1; i++) {
                var key = segments[i];
                if (!cursor[key] || typeof cursor[key] !== 'object') {
                    cursor[key] = {};
                }
                cursor = cursor[key];
            }
            cursor[segments[segments.length - 1]] = value;
        }
        
        // DETECTAR CAMBIOS MANUALES: Marcar como 'manual' en __sync
        // Solo para desktop (los overrides responsive ya son "manuales" por definición)
        if (breakpoint === 'desktop' && path.startsWith('components.')) {
            // Ejemplo path: "components.principal.padding.superior"
            var pathParts = path.split('.');
            if (pathParts.length >= 3) {
                var role = pathParts[1];      // "principal" o "secundario"
                var prop = pathParts[2];      // "padding", "background", etc.
                
                // Asegurar que existe el objeto __sync
                if (!current.components[role].__sync) {
                    current.components[role].__sync = {};
                }
                
                // Marcar como modificado manualmente
                current.components[role].__sync[prop] = 'manual';
            }
        }
        
        // Update block config reference
        block.config = current;
        
        // Sync to global config
        if (!Gbn.config) Gbn.config = {};
        Gbn.config.themeSettings = current;
        
        if (Gbn.ui.panelTheme && Gbn.ui.panelTheme.applyThemeSettings) {
            Gbn.ui.panelTheme.applyThemeSettings(current);
        }
        
        // Dispatch event (incluir path y breakpoint)
        var event;
        if (typeof global.CustomEvent === 'function') {
            event = new CustomEvent('gbn:configChanged', { detail: { id: 'theme-settings', path: path, breakpoint: breakpoint } });
        } else {
            event = document.createEvent('CustomEvent');
            event.initCustomEvent('gbn:configChanged', false, false, { id: 'theme-settings', path: path, breakpoint: breakpoint });
        }
        global.dispatchEvent(event);
        
        // NUEVO: Disparar evento específico para actualización en tiempo real de defaults
        // Solo para desktop (los overrides responsive no afectan a otros bloques como "defaults")
        if (breakpoint === 'desktop' && path.startsWith('components.')) {
            var pathParts = path.split('.');
            if (pathParts.length >= 3) {
                var role = pathParts[1];
                // Reconstruir la propiedad relativa al componente (ej: 'padding.superior')
                var property = pathParts.slice(2).join('.');
                
                var defaultsEvent;
                var detail = { role: role, property: property, value: value };
                
                if (typeof global.CustomEvent === 'function') {
                    defaultsEvent = new CustomEvent('gbn:themeDefaultsChanged', { detail: detail });
                } else {
                    defaultsEvent = document.createEvent('CustomEvent');
                    defaultsEvent.initCustomEvent('gbn:themeDefaultsChanged', false, false, detail);
                }
                global.dispatchEvent(defaultsEvent);
            }
        }
        
        return current;
    }

    Gbn.ui.renderers.themeSettings = {
        handleUpdate: handleUpdate
    };

})(typeof window !== 'undefined' ? window : this);
