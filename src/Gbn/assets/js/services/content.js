;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    var utils = Gbn.utils;
    var state = Gbn.state;
    var styleManager = Gbn.styleManager;

    var ROLE_MAP = {
        principal: {
            attr: 'gloryDiv',
            dataAttr: 'data-gbnPrincipal'
        },
        secundario: {
            attr: 'gloryDivSecundario',
            dataAttr: 'data-gbnSecundario'
        },
        content: {
            attr: 'gloryContentRender',
            dataAttr: 'data-gbnContent'
        }
    };

    function detectRole(el) {
        if (el.hasAttribute(ROLE_MAP.content.attr) || el.hasAttribute(ROLE_MAP.content.dataAttr)) {
            return 'content';
        }
        if (el.hasAttribute(ROLE_MAP.secundario.attr) || el.hasAttribute(ROLE_MAP.secundario.dataAttr)) {
            return 'secundario';
        }
        if (el.hasAttribute(ROLE_MAP.principal.attr) || el.hasAttribute(ROLE_MAP.principal.dataAttr)) {
            return 'principal';
        }
        return null;
    }

    function normalizeAttributes(el, role) {
        if (!role) {
            return;
        }
        var meta = ROLE_MAP[role];
        if (!meta) {
            return;
        }
        if (!el.hasAttribute(meta.attr)) {
            el.setAttribute(meta.attr, role);
        }
        if (!el.hasAttribute(meta.dataAttr)) {
            el.setAttribute(meta.dataAttr, '1');
        }
        if (!el.hasAttribute('data-gbn-config')) {
            el.setAttribute('data-gbn-config', '{}');
        }
        if (!el.hasAttribute('data-gbn-schema')) {
            el.setAttribute('data-gbn-schema', '[]');
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
            var typeAttr = el.getAttribute(ROLE_MAP.content.attr);
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
        return block;
    }

    function scan(target) {
        var root = target || document;
        var selector = Object.keys(ROLE_MAP).map(function (role) {
            var map = ROLE_MAP[role];
            return '[' + map.attr + '],[' + map.dataAttr + ']';
        }).join(',');
        var nodes = utils.qsa(selector, root);
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
        block.element.classList.add('gbn-loading');
        ajaxFn('obtenerHtmlLista', payload).then(function (res) {
            block.element.classList.remove('gbn-loading');
            var html = extractHtml(res);
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

