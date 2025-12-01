;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    var utils = Gbn.utils;
    
    Gbn.content = Gbn.content || {};

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
        
        // Send all options to backend to support dynamic CSS generation
        var payload = utils.assign({}, opts);
        
        // Overrides and specific handling
        payload.postType = block.meta.postType || opts.postType || 'post';
        payload.argumentosConsulta = JSON.stringify(opts.argumentosConsulta || {});
        
        // Generate instanceId based on block ID for unique CSS scoping
        payload.instanceId = 'gbn-cr-' + block.id;

        // Ensure plantilla from meta options is used if not present in config (fix for null override)
        if (!opts.plantilla && block.meta.options && block.meta.options.plantilla) {
            payload.plantilla = block.meta.options.plantilla;
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

    Gbn.content.hydrator = {
        requestContent: requestContent,
        hydrate: hydrate
    };

})(window);
