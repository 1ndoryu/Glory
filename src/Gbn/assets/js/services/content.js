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
        content: { attribute: 'gloryContentRender', dataAttribute: 'data-gbnContent' }
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
    Object.keys(containerDefs).forEach(function (role) {
        var data = containerDefs[role] || {};
        ROLE_DEFAULTS[role] = {
            config: utils.assign({}, data.config || {}),
            schema: Array.isArray(data.schema) ? data.schema.slice() : [],
        };
        ensureSelector(role, data.selector || {});
    });

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

        var defaults = getRoleDefaults(role);
        var existingConfig = readJsonAttribute(el, 'data-gbn-config');
        if (!existingConfig || Object.keys(existingConfig).length === 0) {
            el.setAttribute('data-gbn-config', JSON.stringify(defaults.config || {}));
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
        var block = state.register(role, el, meta);
        el.classList.add('gbn-node');
        styleManager.ensureBaseline(block);
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
                styleManager.update(block, presets.styles[block.id]);
            }
        } catch (_) {}
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
        nodes.forEach(function (el) {
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
        }, block.meta.options || {});
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
})(window);

