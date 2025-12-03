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
    console.log('[GBN-DEBUG] GBN Main Init');

    var content = Gbn.content;
    var inspector = Gbn.ui && Gbn.ui.inspector;

    if (!content) {
        utils.error('Gbn.content no disponible; abortando.');
        return;
    }

    // Bug 33 Fix V8: Race Condition Solver
    // Esperar a que el applicator esté disponible antes de iniciar la hidratación.
    function initGBN() {
        if (config.themeSettings && (!Gbn.ui || !Gbn.ui.theme || !Gbn.ui.theme.applicator)) {
            // Si hay settings pero no applicator, esperamos un poco (máximo 500ms)
            if (!initGBN.retries) initGBN.retries = 0;
            if (initGBN.retries < 10) {
                initGBN.retries++;
                utils.debug('Esperando a Theme Applicator... intento ' + initGBN.retries);
                setTimeout(initGBN, 50);
                return;
            }
            utils.error('Theme Applicator no cargó a tiempo. Iniciando sin él.');
        }

        if (config.themeSettings && Gbn.ui && Gbn.ui.theme && Gbn.ui.theme.applicator) {
            utils.debug('Aplicando Theme Settings antes de hidratación (Sincronizado)');
            Gbn.ui.theme.applicator.applyThemeSettings(config.themeSettings);
        }
        
        var blocks = content.scan(document);
        content.hydrate(blocks);
        
        if (inspector && typeof inspector.init === 'function') {
            inspector.init(blocks, config);
        }
    }

    initGBN();
    
    // Initialize Debug Overlay
    if (Gbn.ui.debug && Gbn.ui.debug.overlay) {
        Gbn.ui.debug.overlay.init();
    }
    
    // Note: store-subscriber.js auto-initializes on load if store exists
    // Note: validator.js is a static module, no init needed
})(window);

