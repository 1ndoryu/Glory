;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    var utils = Gbn.utils;

    if (!utils || !utils.hasDocumentBody()) {
        return;
    }

    if (utils.isBuilderActive()) {
        utils.debug('Modo builder detectado; GBN no se inicia.');
        return;
    }

    var config = utils.getConfig();
    if (config && config.enabled === false) {
        utils.debug('GBN desactivado desde configuración.');
        return;
    }

    utils.debug('Iniciando GBN modular');

    var content = Gbn.content;
    var inspector = Gbn.ui && Gbn.ui.inspector;

    if (!content) {
        utils.error('Gbn.content no disponible; abortando.');
        return;
    }

    var blocks = content.scan(document);
    content.hydrate(blocks);

    if (inspector && typeof inspector.init === 'function') {
        inspector.init(blocks, config);
    }
})(window);

