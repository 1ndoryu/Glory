;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    var utils = Gbn.utils;
    var state = Gbn.state;
    var styleManager = Gbn.styleManager;
    var ROLE_DEFAULTS = {};
    var ROLE_MAP = {};

    var FALLBACK_SELECTORS = {
        principal: { attribute: 'gloryDiv', dataAttribute: 'data-gbnPrincipal' },
        secundario: { attribute: 'gloryDivSecundario', dataAttribute: 'data-gbnSecundario' },
        content: { attribute: 'gloryContentRender', dataAttribute: 'data-gbnContent' },
        term_list: { attribute: 'gloryTermRender', dataAttribute: 'data-gbn-term-list' },
        image: { attribute: 'gloryImage', dataAttribute: 'data-gbn-image' },
        text: { attribute: 'gloryTexto', dataAttribute: 'data-gbn-text' }
    };

    function ensureSelector(role, selector) {
        var existing = ROLE_MAP[role] || {};
        var fallback = FALLBACK_SELECTORS[role] || {};
        var attr = existing.attr
            || (selector && (selector.attribute || selector.attr))
            || fallback.attribute
            || fallback.attr
            || null;
        var dataAttr = existing.dataAttr
            || (selector && (selector.dataAttribute || selector.dataAttr))
            || fallback.dataAttribute
            || fallback.dataAttr
            || null;
        ROLE_MAP[role] = {
            attr: attr,
            dataAttr: dataAttr,
        };
    }

    var containerDefs = utils.getConfig().containers || {};
    // Definición de defaults para roles principales si no existen
    if (!ROLE_DEFAULTS.principal) {
        ROLE_DEFAULTS.principal = {
            config: {
                layout: 'flex',
                direction: 'row',
                wrap: 'wrap',
                justify: 'flex-start',
                align: 'stretch',
                padding: '20px'
            },
            schema: [

                { 
                    id: 'layout', 
                    tipo: 'icon_group', 
                    etiqueta: 'Layout', 
                    opciones: [
                        {valor: 'block', icon: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>', etiqueta: 'Bloque'},
                        {valor: 'flex', icon: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M12 3v18"/></svg>', etiqueta: 'Flexbox'},
                        {valor: 'grid', icon: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 3v18"/><path d="M15 3v18"/><path d="M3 9h18"/><path d="M3 15h18"/></svg>', etiqueta: 'Grid'}
                    ] 
                },
                { 
                    id: 'gap', 
                    tipo: 'slider', 
                    etiqueta: 'Separación (Gap)', 
                    unidad: 'px', 
                    min: 0, 
                    max: 120, 
                    paso: 2,
                    condicion: ['layout', 'flex'] // Also for grid, logic handled in panel-render
                },
                { 
                    id: 'direction', // Note: PHP uses flexDirection, JS defaults used direction. Let's align to PHP if possible, but panel-render handles both.
                    tipo: 'icon_group', 
                    etiqueta: 'Dirección', 
                    condicion: ['layout', 'flex'],
                    opciones: [
                        { valor: 'row', icon: '<svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none"><path d="M4 12h16m-4-4l4 4-4 4"/></svg>', etiqueta: 'Fila' },
                        { valor: 'column', icon: '<svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none"><path d="M12 4v16m-4-4l4 4 4-4"/></svg>', etiqueta: 'Columna' }
                    ]
                },
                { 
                    id: 'wrap', 
                    tipo: 'icon_group', 
                    etiqueta: 'Wrap', 
                    condicion: ['layout', 'flex'],
                    opciones: [
                        { valor: 'nowrap', icon: '<svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M8 12h8"/></svg>', etiqueta: 'No Wrap' },
                        { valor: 'wrap', icon: '<svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none"><path d="M4 8h16M4 16h10"/></svg>', etiqueta: 'Wrap' }
                    ]
                },
                { 
                    id: 'justify', 
                    tipo: 'icon_group', 
                    etiqueta: 'Justify Content', 
                    condicion: ['layout', 'flex'],
                    opciones: [
                        { valor: 'flex-start', icon: '<svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none"><path d="M4 6h4M4 12h4M4 18h4"/></svg>', etiqueta: 'Start' },
                        { valor: 'center', icon: '<svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none"><path d="M10 6h4M10 12h4M10 18h4"/></svg>', etiqueta: 'Center' },
                        { valor: 'flex-end', icon: '<svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none"><path d="M16 6h4M16 12h4M16 18h4"/></svg>', etiqueta: 'End' },
                        { valor: 'space-between', icon: '<svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none"><path d="M4 6h2m14 0h2M4 12h2m14 0h2M4 18h2m14 0h2"/></svg>', etiqueta: 'Space Between' }
                    ]
                },
                { 
                    id: 'align', 
                    tipo: 'icon_group', 
                    etiqueta: 'Align Items', 
                    condicion: ['layout', 'flex'],
                    opciones: [
                        { valor: 'flex-start', icon: '<svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none"><path d="M4 6h16M4 10h16"/></svg>', etiqueta: 'Start' },
                        { valor: 'center', icon: '<svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none"><path d="M4 11h16M4 13h16"/></svg>', etiqueta: 'Center' },
                        { valor: 'flex-end', icon: '<svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none"><path d="M4 14h16M4 18h16"/></svg>', etiqueta: 'End' },
                        { valor: 'stretch', icon: '<svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none"><rect x="4" y="4" width="16" height="16" rx="2"/></svg>', etiqueta: 'Stretch' }
                    ]
                },
                {
                    id: 'gridColumns',
                    tipo: 'slider',
                    etiqueta: 'Columnas Grid',
                    min: 1,
                    max: 12,
                    paso: 1,
                    condicion: ['layout', 'grid']
                },
                {
                    id: 'gridGap',
                    tipo: 'slider',
                    etiqueta: 'Separación Grid',
                    unidad: 'px',
                    min: 0,
                    max: 120,
                    paso: 2,
                    condicion: ['layout', 'grid']
                },
                { id: 'padding', tipo: 'spacing', etiqueta: 'Padding' },
                { id: 'background', tipo: 'color', etiqueta: 'Fondo' }
            ]
        };
    }

    if (!ROLE_DEFAULTS.secundario) {
        ROLE_DEFAULTS.secundario = {
            config: {
                width: '1/1',
                padding: '20px'
            },
            schema: [
                { id: 'width', tipo: 'fraction', etiqueta: 'Ancho' },
                { id: 'padding', tipo: 'spacing', etiqueta: 'Padding' },
                { id: 'background', tipo: 'color', etiqueta: 'Fondo' }
            ]
        };
    }

    // Defaults hardcoded para gloryTexto si no vienen de containerDefs
    if (!ROLE_DEFAULTS.text) {
        ROLE_DEFAULTS.text = {
            config: {
                tag: 'p',
                texto: 'Nuevo texto',
                alineacion: 'left',
                color: '#333333',
                size: '16px'
            },
            schema: [
                { id: 'tag', tipo: 'select', etiqueta: 'Etiqueta HTML', opciones: [
                    { valor: 'p', etiqueta: 'Párrafo (p)' },
                    { valor: 'h1', etiqueta: 'Encabezado 1 (h1)' },
                    { valor: 'h2', etiqueta: 'Encabezado 2 (h2)' },
                    { valor: 'h3', etiqueta: 'Encabezado 3 (h3)' },
                    { valor: 'h4', etiqueta: 'Encabezado 4 (h4)' },
                    { valor: 'h5', etiqueta: 'Encabezado 5 (h5)' },
                    { valor: 'h6', etiqueta: 'Encabezado 6 (h6)' },
                    { valor: 'span', etiqueta: 'Span' },
                    { valor: 'div', etiqueta: 'Div' }
                ]},
                { id: 'texto', tipo: 'rich_text', etiqueta: 'Contenido' },
                { id: 'typography', tipo: 'typography', etiqueta: 'Tipografía' },
                { id: 'alineacion', tipo: 'icon_group', etiqueta: 'Alineación', opciones: [
                    { valor: 'left', icon: '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><path d="M17 9.5H3M21 4.5H3M21 14.5H3M17 19.5H3"/></svg>', etiqueta: 'Izquierda' },
                    { valor: 'center', icon: '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><path d="M19 9.5H5M21 4.5H3M21 14.5H3M19 19.5H5"/></svg>', etiqueta: 'Centro' },
                    { valor: 'right', icon: '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><path d="M21 9.5H7M21 4.5H3M21 14.5H3M21 19.5H7"/></svg>', etiqueta: 'Derecha' },
                    { valor: 'justify', icon: '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><path d="M21 9.5H3M21 4.5H3M21 14.5H3M21 19.5H3"/></svg>', etiqueta: 'Justificado' }
                ]},
                { id: 'color', tipo: 'color', etiqueta: 'Color' }
            ]
        };
    }

    if (!Object.keys(ROLE_DEFAULTS).length) {
        var legacyRoles = utils.getConfig().roles || {};
        Object.keys(legacyRoles).forEach(function (role) {
            var data = legacyRoles[role] || {};
            ROLE_DEFAULTS[role] = {
                config: utils.assign({}, data.config || {}),
                schema: Array.isArray(data.schema) ? data.schema.slice() : [],
            };
            ensureSelector(role, {});
        });
    }

    Object.keys(FALLBACK_SELECTORS).forEach(function (role) {
        ensureSelector(role, FALLBACK_SELECTORS[role]);
    });

    var ROLE_PRIORITY = [];
    ['content', 'secundario', 'principal'].forEach(function (role) {
        if (ROLE_MAP[role] && ROLE_PRIORITY.indexOf(role) === -1) {
            ROLE_PRIORITY.push(role);
        }
    });
    Object.keys(ROLE_MAP).forEach(function (role) {
        if (ROLE_PRIORITY.indexOf(role) === -1) {
            ROLE_PRIORITY.push(role);
        }
    });

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

    function detectRole(el) {
        for (var i = 0; i < ROLE_PRIORITY.length; i += 1) {
            var role = ROLE_PRIORITY[i];
            var meta = ROLE_MAP[role];
            if (!meta) {
                continue;
            }
            if ((meta.attr && el.hasAttribute(meta.attr)) || (meta.dataAttr && el.hasAttribute(meta.dataAttr))) {
                return role;
            }
        }
        return null;
    }

    function getRoleDefaults(role) {
        var defaults = ROLE_DEFAULTS[role] || {};
        return {
            config: utils.assign({}, defaults.config || {}),
            schema: Array.isArray(defaults.schema) ? defaults.schema.slice() : [],
        };
    }

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

    function normalizeAttributes(el, role) {
        if (!role) {
            return;
        }
        var meta = ROLE_MAP[role];
        if (!meta) {
            return;
        }
        if (meta.attr && !el.hasAttribute(meta.attr)) {
            el.setAttribute(meta.attr, role);
        }
        if (meta.dataAttr && !el.hasAttribute(meta.dataAttr)) {
            el.setAttribute(meta.dataAttr, '1');
        }
        if (!el.hasAttribute('data-gbn-role')) {
            el.setAttribute('data-gbn-role', role);
        }

        // Inyección automática de clases por defecto
        if (role === 'principal') {
            if (!el.classList.contains('primario')) {
                el.classList.add('primario');
            }
        } else if (role === 'secundario') {
            if (!el.classList.contains('secundario')) {
                el.classList.add('secundario');
            }
        }

        var defaults = getRoleDefaults(role);
        var existingConfig = readJsonAttribute(el, 'data-gbn-config');
        var inlineStyles = utils.parseStyleString(el.getAttribute('style') || '');

        // Inyección de estilos por defecto si no hay inline styles ni config previa
        // Esto asegura que los nuevos elementos (o los que no tienen estilos) tengan los defaults requeridos
        // 1. Principal: padding 20px, display flex
        // 2. Secundario: padding 20px
        var hasInlinePadding = inlineStyles['padding'] || inlineStyles['padding-top']; // Chequeo básico
        var hasInlineDisplay = inlineStyles['display'];

        if (role === 'principal') {
            if (!hasInlinePadding) {
                inlineStyles['padding'] = '20px';
            }
            if (!hasInlineDisplay) {
                inlineStyles['display'] = 'flex';
                // Defaults adicionales para flex si se desea
                if (!inlineStyles['flex-wrap']) inlineStyles['flex-wrap'] = 'wrap';
            }
        } else if (role === 'secundario') {
            if (!hasInlinePadding) {
                inlineStyles['padding'] = '20px';
            }
        }

        if (!existingConfig || Object.keys(existingConfig).length === 0) {
            // Si no hay configuración existente, sincronizar estilos inline (incluyendo los defaults inyectados) con defaults
            var initialConfig = syncInlineStylesWithConfig(inlineStyles, defaults.schema, defaults.config);
            
            // Asegurar que los defaults inyectados se reflejen en la config inicial si syncInlineStylesWithConfig no los capturó
            // (syncInlineStylesWithConfig mapea padding-top etc, pero 'padding' shorthand puede necesitar manejo especial en utils o aquí)
            // Por simplicidad, si inyectamos 'padding: 20px', deberíamos asegurarnos que la config lo tenga.
            // syncInlineStylesWithConfig maneja padding-top/right/bottom/left.
            // Vamos a expandir el shorthand 'padding' para que sync lo agarre si es necesario, 
            // o mejor, dejar que el styleManager aplique los estilos y la config se derive.
            
            // NOTA: syncInlineStylesWithConfig actualmente solo mira padding-top, etc.
            // Si inlineStyles tiene 'padding', necesitamos expandirlo para que sync lo vea.
            if (inlineStyles['padding']) {
                var pVal = inlineStyles['padding'];
                // Asumimos 1 valor para simplificar por ahora (20px)
                if (!inlineStyles['padding-top']) inlineStyles['padding-top'] = pVal;
                if (!inlineStyles['padding-right']) inlineStyles['padding-right'] = pVal;
                if (!inlineStyles['padding-bottom']) inlineStyles['padding-bottom'] = pVal;
                if (!inlineStyles['padding-left']) inlineStyles['padding-left'] = pVal;
            }

            // Re-sincronizar con los valores expandidos
            initialConfig = syncInlineStylesWithConfig(inlineStyles, defaults.schema, defaults.config);
            
            // Forzar layout flex en config para principal si se inyectó
            if (role === 'principal' && inlineStyles['display'] === 'flex') {
                // Asumiendo que hay una propiedad 'layout' en el schema/config
                // Si no existe en defaults.config, se agregará.
                if (!initialConfig.layout) initialConfig.layout = 'flex';
            }

            el.setAttribute('data-gbn-config', JSON.stringify(initialConfig));
        } else {
            var mergedConfig = utils.assign({}, defaults.config || {}, existingConfig || {});
            el.setAttribute('data-gbn-config', JSON.stringify(mergedConfig));
        }
        var existingSchema = readJsonAttribute(el, 'data-gbn-schema');
        if (!Array.isArray(existingSchema) || existingSchema.length === 0) {
            el.setAttribute('data-gbn-schema', JSON.stringify(defaults.schema || []));
        }
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

    function buildBlock(el) {
        var role = detectRole(el);
        if (!role) {
            return null;
        }
        normalizeAttributes(el, role);
        var meta = {};
        if (role === 'content') {
            var attrName = null;
            if (ROLE_MAP.content && ROLE_MAP.content.attr) {
                attrName = ROLE_MAP.content.attr;
            } else if (FALLBACK_SELECTORS.content && (FALLBACK_SELECTORS.content.attribute || FALLBACK_SELECTORS.content.attr)) {
                attrName = FALLBACK_SELECTORS.content.attribute || FALLBACK_SELECTORS.content.attr;
            }
            var typeAttr = attrName ? el.getAttribute(attrName) : null;
            meta.postType = typeAttr && typeAttr !== 'content' ? typeAttr : 'post';
            var opcionesAttr = el.getAttribute('opciones') || el.getAttribute('data-gbn-opciones') || '';
            meta.optionsString = opcionesAttr;
            var parsedOptions = parseOptionsString(opcionesAttr);
            var estilosAttr = el.getAttribute('estilos');
            meta.inlineArgs = extractInlineArguments(estilosAttr);
            meta.options = utils.assign({}, parsedOptions);
        }
        
        // Pre-process text content for text role
        if (role === 'text') {
            var existingConfig = readJsonAttribute(el, 'data-gbn-config');
            // Prioritize existing content in DOM if config is missing or if we want to sync
            var currentText = el.innerHTML; // Use innerHTML to preserve basic formatting if any
            
            if (!existingConfig) {
                existingConfig = {};
            }
            
            // If config has no text, or if we want to ensure what's on screen is what's in config
            // (Usually what's on screen is the source of truth initially)
            if (currentText && currentText.trim() !== '') {
                 existingConfig.texto = currentText;
            } else if (!existingConfig.texto) {
                 // Fallback if both are empty?
                 existingConfig.texto = 'Nuevo texto';
                 el.innerHTML = 'Nuevo texto';
            }
            
            el.setAttribute('data-gbn-config', JSON.stringify(existingConfig));
        }

        var block = state.register(role, el, meta);
        el.classList.add('gbn-node');

        // Asegurar que los valores inline iniciales se reflejen en la configuración del bloque
        try {
            var roleDefaults = getRoleDefaults(role);
            var inlineConfig = syncInlineStylesWithConfig(block.styles.inline, roleDefaults.schema, roleDefaults.config);
            var currentConfig = utils.assign({}, block.config || {});
            var mergedWithInline = mergeConfigIfEmpty(currentConfig, inlineConfig);
            block = state.updateConfig(block.id, mergedWithInline) || block;
        } catch (_) {}

        // Aplicar presets persistidos (config y estilos) si existen para este bloque
        try {
            var presets = utils.getConfig().presets || {};
            if (presets.config && presets.config[block.id]) {
                var presetCfg = presets.config[block.id];
                var nextCfg = (presetCfg && typeof presetCfg === 'object' && presetCfg.config) ? presetCfg.config : presetCfg;
                if (nextCfg && typeof nextCfg === 'object') {
                    state.updateConfig(block.id, nextCfg);
                }
            }
            if (presets.styles && presets.styles[block.id]) {
                // Aplicar estilos persistidos directamente al elemento
                var currentInline = utils.parseStyleString(el.getAttribute('style') || '');
                var mergedStyles = utils.assign({}, currentInline, presets.styles[block.id]);
                var styleString = utils.stringifyStyles(mergedStyles);
                if (styleString) {
                    el.setAttribute('style', styleString);
                }
                block.styles.current = utils.assign({}, presets.styles[block.id]);
            }
        } catch (_) {}
        
        // Ensure text content is applied from config if it exists (persistence fix)
        if (role === 'text' && block.config && block.config.texto) {
             // Only update if different to avoid unnecessary DOM touches, 
             // but we must trust config over initial HTML if config exists and we are in editor mode
             // actually, we should just apply it.
             var currentHTML = el.innerHTML;
             if (block.config.texto !== currentHTML) {
                 // Check for controls to preserve them
                 var controls = el.querySelector('.gbn-controls-group');
                 el.innerHTML = block.config.texto;
                 if (controls) {
                     el.appendChild(controls);
                 }
             }
             
             // Also apply typography styles if present in config but not in inline styles
             // This is a bit redundant if presets.styles handled it, but good for safety
             if (block.config.typography && Gbn.ui && Gbn.ui.panelApi && Gbn.ui.panelApi.applyBlockStyles) {
                 Gbn.ui.panelApi.applyBlockStyles(block);
             }
        }

        // Solo aplicar baseline si no hay presets
        if (!presets.styles || !presets.styles[block.id]) {
            styleManager.ensureBaseline(block);
        }

        return block;
    }

    function scan(target) {
        var root = target || document;
        var selectorParts = [];
        Object.keys(ROLE_MAP).forEach(function (role) {
            var map = ROLE_MAP[role];
            if (!map) {
                return;
            }
            if (map.attr) {
                selectorParts.push('[' + map.attr + ']');
            }
            if (map.dataAttr) {
                selectorParts.push('[' + map.dataAttr + ']');
            }
        });
        if (!selectorParts.length) {
            return [];
        }
        var nodes = utils.qsa(selectorParts.join(','), root);
        var blocks = [];
        
        // Reconciliación: Si estamos en modo editor y hay configuración guardada,
        // usamos la configuración como fuente de la verdad para la existencia de bloques.
        var cfg = utils.getConfig();
        var presets = cfg.presets || {};
        var configMap = presets.config;
        var shouldReconcile = cfg.contentMode === 'editor' && configMap && Object.keys(configMap).length > 0;

        nodes.forEach(function (el) {
            // Verificar si el elemento debe existir antes de construirlo
            if (shouldReconcile) {
                // Intentamos obtener el ID sin registrar todavía
                var id = el.getAttribute('data-gbn-id');
                // Si tiene ID y ese ID no está en la config guardada, es un bloque borrado.
                // Si no tiene ID, es un bloque nuevo en el HTML (posiblemente agregado manualmente),
                // en modo editor estricto podríamos borrarlo, pero por seguridad lo dejamos si no tiene ID.
                // Sin embargo, GBN asigna IDs estables. Si el usuario borró el bloque, el ID estaba en el HTML anterior.
                // Al recargar, el HTML sigue teniendo el ID.
                if (id && !configMap[id]) {
                    // Bloque borrado en sesión anterior
                    if (el.parentNode) {
                        el.parentNode.removeChild(el);
                    }
                    return; // Skip buildBlock
                }
            }

            var block = buildBlock(el);
            if (block) {
                blocks.push(block);
            }
        });
        return blocks;
    }

    function extractHtml(res) {
        if (!res) {
            return '';
        }
        if (typeof res === 'string') {
            return res;
        }
        if (res.success && typeof res.data === 'string') {
            return res.data;
        }
        if (res.success && res.data && typeof res.data.data === 'string') {
            return res.data.data;
        }
        return '';
    }

    function emitHydrated(block) {
        if (!block) {
            return;
        }
        try {
            var detail = { id: block.id };
            var event;
            if (typeof global.CustomEvent === 'function') {
                event = new CustomEvent('gbn:contentHydrated', { detail: detail });
            } else {
                event = document.createEvent('CustomEvent');
                event.initCustomEvent('gbn:contentHydrated', false, false, detail);
            }
            global.dispatchEvent(event);
        } catch (_) {}
    }

    function requestContent(block) {
        var ajaxFn = global.gloryAjax || global.enviarAjax;
        if (typeof ajaxFn !== 'function') {
            utils.warn('gloryAjax no disponible; gloryContentRender no fue hidratado.');
            return;
        }
        var opts = utils.assign({
            publicacionesPorPagina: 10,
            claseContenedor: 'glory-content-list',
            claseItem: 'glory-content-item',
            argumentosConsulta: {}
        }, block.meta.options || {}, block.config || {});
        var payload = {
            postType: block.meta.postType || opts.postType || 'post',
            publicacionesPorPagina: opts.publicacionesPorPagina,
            claseContenedor: opts.claseContenedor,
            claseItem: opts.claseItem,
            argumentosConsulta: JSON.stringify(opts.argumentosConsulta || {})
        };
        if (opts.plantilla) {
            payload.plantilla = opts.plantilla;
        }
        utils.debug('Solicitando contenido', payload);
        block.element.classList.add('gbn-loading');
        ajaxFn('obtenerHtmlLista', payload).then(function (res) {
            block.element.classList.remove('gbn-loading');
            var html = extractHtml(res);
            utils.debug('Contenido recibido', html ? ('len=' + html.length) : 'vacío');
            if (html) {
                block.element.innerHTML = html;
                emitHydrated(block);
            }
        }).catch(function (err) {
            block.element.classList.remove('gbn-loading');
            utils.error('Error cargando contenido para', block.id, err);
        });
    }

    function hydrate(blocks) {
        blocks.filter(function (block) { return block.role === 'content'; }).forEach(requestContent);
    }

    Gbn.content = {
        scan: scan,
        hydrate: hydrate,
        parseOptionsString: parseOptionsString,
    };

    if (typeof global.addEventListener === 'function') {
        global.addEventListener('gbn:configChanged', function(e) {
            var id = e.detail && e.detail.id;
            if (!id) return;
            var block = state.get(id);
            if (block && block.role === 'content') {
                requestContent(block);
            }
        });
    }
})(window);

