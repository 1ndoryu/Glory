;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.renderers = Gbn.ui.renderers || {};

    function extractSpacingStyles(spacingConfig) {
        var styles = {};
        if (spacingConfig === null || spacingConfig === undefined || spacingConfig === '') { return styles; }
        
        // Handle single value (string or number)
        if (typeof spacingConfig !== 'object') {
            var val = typeof spacingConfig === 'number' ? spacingConfig + 'px' : spacingConfig;
            styles['padding-top'] = val;
            styles['padding-right'] = val;
            styles['padding-bottom'] = val;
            styles['padding-left'] = val;
            return styles;
        }
        
        var map = { superior: 'padding-top', derecha: 'padding-right', inferior: 'padding-bottom', izquierda: 'padding-left' };
        Object.keys(map).forEach(function (key) {
            var raw = spacingConfig[key];
            if (raw === null || raw === undefined || raw === '') { return; }
            if (typeof raw === 'number') { styles[map[key]] = raw + 'px'; }
            else { styles[map[key]] = raw; }
        });
        return styles;
    }

    function parseFraction(fraction) {
        if (!fraction || typeof fraction !== 'string') return null;
        var parts = fraction.split('/');
        if (parts.length !== 2) return null;
        var num = parseFloat(parts[0]);
        var den = parseFloat(parts[1]);
        if (isNaN(num) || isNaN(den) || den === 0) return null;
        return (num / den * 100).toFixed(4) + '%';
    }

    function getResponsiveValue(block, path, bp) {
        if (Gbn.responsive && Gbn.responsive.getBlockResponsiveValue) {
             return Gbn.responsive.getBlockResponsiveValue(block, path, bp);
        }
        // Fallback legacy: solo config del bloque
        var segments = path.split('.');
        var cursor = block.config || {};
        for (var i = 0; i < segments.length; i++) {
            if (cursor === null || cursor === undefined) break;
            cursor = cursor[segments[i]];
        }
        return (cursor !== undefined && cursor !== null && cursor !== '') ? cursor : undefined;
    }

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

    /**
     * Obtiene el valor de Theme Settings para un rol y propiedad específicos
     */
    function getThemeSettingsValue(role, path) {
        if (!role || !path) return undefined;
        
        // Intentar desde Gbn.config.themeSettings (estado local)
        var themeSettings = Gbn.config && Gbn.config.themeSettings;
        
        // Si no hay estado local, intentar desde gloryGbnCfg (cargado del servidor)
        if (!themeSettings && typeof gloryGbnCfg !== 'undefined') {
            themeSettings = gloryGbnCfg.themeSettings;
        }
        
        if (!themeSettings || !themeSettings.components || !themeSettings.components[role]) {
            return undefined;
        }
        
        var comp = themeSettings.components[role];
        var segments = path.split('.');
        var cursor = comp;
        
        for (var i = 0; i < segments.length; i++) {
            if (cursor === null || cursor === undefined) return undefined;
            cursor = cursor[segments[i]];
        }
        
        // Solo retornar si hay valor válido
        if (cursor !== undefined && cursor !== null && cursor !== '') {
            return cursor;
        }
        
        return undefined;
    }
    
    /**
     * Obtiene el valor efectivo de configuración con fallback a Theme Settings
     */
    function getConfigWithThemeFallback(config, role, path) {
        // 1. Buscar en config del bloque
        var segments = path.split('.');
        var cursor = config || {};
        
        for (var i = 0; i < segments.length; i++) {
            if (cursor === null || cursor === undefined) break;
            cursor = cursor[segments[i]];
        }
        
        // Si el valor existe en config, usarlo
        if (cursor !== undefined && cursor !== null && cursor !== '') {
            return cursor;
        }
        
        // 2. Fallback a Theme Settings
        return getThemeSettingsValue(role, path);
    }

    Gbn.ui.renderers.shared = {
        extractSpacingStyles: extractSpacingStyles,
        parseFraction: parseFraction,
        getResponsiveValue: getResponsiveValue,
        cloneConfig: cloneConfig,
        getThemeSettingsValue: getThemeSettingsValue,
        getConfigWithThemeFallback: getConfigWithThemeFallback
    };

})(typeof window !== 'undefined' ? window : this);
