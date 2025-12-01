;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    var utils = Gbn.utils;
    
    Gbn.content = Gbn.content || {};

    function deepCloneConfig(obj) {
        if (!obj || typeof obj !== 'object') {
            return {};
        }
        try {
            return JSON.parse(JSON.stringify(obj));
        } catch (_) {
            var copy = Array.isArray(obj) ? [] : {};
            Object.keys(obj).forEach(function (key) {
                var value = obj[key];
                if (value && typeof value === 'object') {
                    copy[key] = deepCloneConfig(value);
                } else {
                    copy[key] = value;
                }
            });
            return copy;
        }
    }

    function mergeConfigIfEmpty(target, source) {
        if (!target || typeof target !== 'object') {
            target = {};
        }
        Object.keys(source || {}).forEach(function (key) {
            var value = source[key];
            if (value && typeof value === 'object' && !Array.isArray(value)) {
                if (!target[key] || typeof target[key] !== 'object' || Array.isArray(target[key])) {
                    target[key] = {};
                }
                mergeConfigIfEmpty(target[key], value);
            } else if (value !== undefined && value !== null && value !== '') {
                var current = target[key];
                if (current === undefined || current === null || current === '') {
                    target[key] = value;
                }
            }
        });
        return target;
    }

    function syncInlineStylesWithConfig(inlineStyles, schema, defaults) {
        if (!inlineStyles || !schema || !Array.isArray(schema)) {
            return deepCloneConfig(defaults || {});
        }

        var config = deepCloneConfig(defaults || {});
        var spacingMap = {
            'padding-top': 'padding.superior',
            'padding-right': 'padding.derecha',
            'padding-bottom': 'padding.inferior',
            'padding-left': 'padding.izquierda'
        };

        // Procesar estilos inline y mapearlos a la configuración
        Object.keys(inlineStyles).forEach(function(cssProp) {
            var cssValue = inlineStyles[cssProp];

            // Manejar propiedades de espaciado
            if (spacingMap[cssProp]) {
                var configPath = spacingMap[cssProp];
                var segments = configPath.split('.');
                var cursor = config;
                for (var i = 0; i < segments.length - 1; i++) {
                    if (!cursor[segments[i]]) {
                        cursor[segments[i]] = {};
                    }
                    // Si encontramos un string donde esperamos un objeto (ej: padding: '20px'), lo convertimos
                    if (typeof cursor[segments[i]] !== 'object') {
                         cursor[segments[i]] = {};
                    }
                    cursor = cursor[segments[i]];
                }
                cursor[segments[segments.length - 1]] = cssValue;
                return;
            }

            // Manejar otras propiedades comunes
            if (cssProp === 'display') {
                if (cssValue === 'flex' || cssValue === 'grid' || cssValue === 'block') {
                    config.display_mode = cssValue;
                }
                return;
            }

            if (cssProp === 'gap') {
                config.gap = cssValue;
                return;
            }

            if (cssProp === 'flex-direction') {
                config.flex_direction = cssValue;
                return;
            }

            if (cssProp === 'flex-wrap') {
                config.flex_wrap = cssValue;
                return;
            }

            if (cssProp === 'justify-content') {
                config.justify_content = cssValue;
                return;
            }

            if (cssProp === 'align-items') {
                config.align_items = cssValue;
                return;
            }

            if (cssProp === 'grid-template-columns') {
                // Intentar inferir el número de columnas si es un repeat simple
                // Ej: repeat(4, 1fr)
                var match = cssValue.match(/repeat\((\d+)/);
                if (match) {
                    config.grid_columns = parseInt(match[1], 10);
                }
                return;
            }

            if (cssProp === 'height') {
                if (cssValue === 'min-content') {
                    config.height = 'min-content';
                } else if (cssValue === '100vh') {
                    config.height = '100vh';
                }
                return;
            }

            if (cssProp === 'text-align') {
                config.alineacion = cssValue;
                return;
            }

            if (cssProp === 'max-width') {
                // Extraer el valor numérico si es posible
                var match = cssValue.match(/^(\d+(?:\.\d+)?)(px|%|rem|em)?$/);
                if (match) {
                    config.maxAncho = match[1] + (match[2] || 'px');
                }
                return;
            }

            if (cssProp === 'background') {
                config.fondo = cssValue;
                return;
            }
        });

        return config;
    }

    function parseOptionsString(str) {
        var output = {};
        if (!str || typeof str !== 'string') {
            return output;
        }
        var buffer = '';
        var inSingle = false;
        var inDouble = false;
        var parts = [];
        for (var i = 0; i < str.length; i += 1) {
            var char = str[i];
            if (char === "'" && !inDouble) {
                inSingle = !inSingle;
            } else if (char === '"' && !inSingle) {
                inDouble = !inDouble;
            }
            if (char === ',' && !inSingle && !inDouble) {
                parts.push(buffer.trim());
                buffer = '';
            } else {
                buffer += char;
            }
        }
        if (buffer.trim()) {
            parts.push(buffer.trim());
        }

        parts.forEach(function (chunk) {
            if (!chunk) {
                return;
            }
            var idx = chunk.indexOf(':');
            if (idx === -1) {
                return;
            }
            var key = chunk.slice(0, idx).trim();
            var value = chunk.slice(idx + 1).trim();
            if (!key) {
                return;
            }
            if ((value[0] === '"' && value[value.length - 1] === '"') || (value[0] === "'" && value[value.length - 1] === "'")) {
                value = value.slice(1, -1);
            }
            if ((value[0] === '{' && value[value.length - 1] === '}') || (value[0] === '[' && value[value.length - 1] === ']')) {
                try {
                    var parsedJson = JSON.parse(value);
                    output[key] = parsedJson;
                    return;
                } catch (_) {}
            }
            var lower = value.toLowerCase();
            if (lower === 'true' || lower === 'false') {
                output[key] = lower === 'true';
                return;
            }
            if (value && !isNaN(value)) {
                output[key] = Number(value);
                return;
            }
            output[key] = value;
        });
        return output;
    }

    function extractInlineArguments(raw) {
        if (raw && typeof raw === 'string' && raw.trim().charAt(0) === '{') {
            try {
                var parsed = JSON.parse(raw);
                if (parsed && typeof parsed === 'object') {
                    return parsed;
                }
            } catch (_) {}
        }
        return {};
    }

    function readJsonAttribute(el, attr) {
        if (!el || !attr) {
            return null;
        }
        var raw = el.getAttribute(attr);
        if (!raw) {
            return null;
        }
        try {
            return JSON.parse(raw);
        } catch (error) {
            utils.warn('No se pudo parsear', attr, 'para', el, error);
            return null;
        }
    }

    Gbn.content.config = {
        deepClone: deepCloneConfig,
        mergeIfEmpty: mergeConfigIfEmpty,
        syncInlineStyles: syncInlineStylesWithConfig,
        parseOptions: parseOptionsString,
        extractInlineArgs: extractInlineArguments,
        readJsonAttr: readJsonAttribute
    };

})(window);
