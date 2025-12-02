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
    if (Gbn.log) Gbn.log.info('GBN Initialized', { version: config.version || 'unknown' });

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
    
    // Initialize Debug Overlay
    if (Gbn.ui.debug && Gbn.ui.debug.overlay) {
        Gbn.ui.debug.overlay.init();
    }
    
    // Note: store-subscriber.js auto-initializes on load if store exists
    // Note: validator.js is a static module, no init needed
})(window);

