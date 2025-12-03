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
        if (Gbn.log) Gbn.log.info('Theme Settings Update Start', { path: path, value: value });

        var current = cloneConfig(block.config);
        var segments = path.split('.');
        
        // 1. Detectar breakpoint activo
        var breakpoint = (Gbn.responsive && Gbn.responsive.getCurrentBreakpoint) ? Gbn.responsive.getCurrentBreakpoint() : 'desktop';
        
        // 2. Lógica Responsive: Escribir en _responsive[bp] si no es desktop
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
            // Desktop o paths no-components: escribir en raíz
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
        
        // 3. Marcar cambios manuales (__sync) solo en desktop
        if (breakpoint === 'desktop' && path.startsWith('components.')) {
            var pathParts = path.split('.');
            if (pathParts.length >= 3) {
                var role = pathParts[1];
                var prop = pathParts[2];
                if (!current.components[role].__sync) {
                    current.components[role].__sync = {};
                }
                current.components[role].__sync[prop] = 'manual';
            }
        }
        
        // 4. Persistir en Store (CRÍTICO: Usar state.updateConfig)
        if (Gbn.state && Gbn.state.updateConfig) {
            Gbn.state.updateConfig(block.id, current, breakpoint);
        }
        
        // 5. Sincronizar config global
        if (!Gbn.config) Gbn.config = {};
        Gbn.config.themeSettings = current;
        
        // 6. Aplicar cambios visuales inmediatos (Applicator)
        if (Gbn.ui.panelTheme && Gbn.ui.panelTheme.applyThemeSettings) {
            Gbn.ui.panelTheme.applyThemeSettings(current); // Esto dispara el estilo inline en el root
        } else if (Gbn.ui.theme && Gbn.ui.theme.applicator && Gbn.ui.theme.applicator.applyThemeSettings) {
             Gbn.ui.theme.applicator.applyThemeSettings(current);
        }
        
        // 7. Disparar evento de cambio de configuración
        var event;
        if (typeof global.CustomEvent === 'function') {
            event = new CustomEvent('gbn:configChanged', { detail: { id: 'theme-settings', path: path, breakpoint: breakpoint } });
        } else {
            event = document.createEvent('CustomEvent');
            event.initCustomEvent('gbn:configChanged', false, false, { id: 'theme-settings', path: path, breakpoint: breakpoint });
        }
        global.dispatchEvent(event);
        
        // 8. Disparar evento específico para defaults (Bug 27/28 Fix)
        if (path.startsWith('components.')) {
            var pathParts = path.split('.');
            if (pathParts.length >= 3) {
                var role = pathParts[1];
                var property = pathParts.slice(2).join('.');
                
                var defaultsEvent;
                var detail = { role: role, property: property, value: value, breakpoint: breakpoint };
                
                if (typeof global.CustomEvent === 'function') {
                    defaultsEvent = new CustomEvent('gbn:themeDefaultsChanged', { detail: detail });
                } else {
                    defaultsEvent = document.createEvent('CustomEvent');
                    defaultsEvent.initCustomEvent('gbn:themeDefaultsChanged', false, false, detail);
                }
                global.dispatchEvent(defaultsEvent);
                
                if (Gbn.log) Gbn.log.info('Theme Defaults Changed Event Dispatched', detail);
            }
        }
        
        // Retornar TRUE para indicar que ya manejamos todo y panel-render no debe hacer nada más
        return true;
    }

    Gbn.ui.renderers.themeSettings = {
        handleUpdate: handleUpdate
    };

})(typeof window !== 'undefined' ? window : this);
