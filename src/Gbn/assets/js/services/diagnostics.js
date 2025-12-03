;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    var utils = Gbn.utils;

    async function validateComponents() {
        var ajaxFn = global.gloryAjax || global.enviarAjax;
        var cfg = utils.getConfig();

        if (typeof ajaxFn !== 'function' || !cfg || !cfg.nonce) {
            if (utils) utils.warn('Diagnostics: gloryAjax or config not available');
            return;
        }

        try {
            var response = await ajaxFn('gbn_diagnostics_validate', { nonce: cfg.nonce });
            if (response && response.success) {
                var report = response.data;
                if (report.score < 100) {
                    utils.warn('Diagnostics: Validation found issues (Score: ' + report.score + ')', report.issues);
                    // TODO: Dispatch action to UI to show warning or disable components
                } else {
                    utils.debug('Diagnostics: All components validated successfully.');
                }
            } else {
                utils.error('Diagnostics: Validation failed', response);
            }
        } catch (e) {
            utils.error('Diagnostics: Error running validation', e);
        }
    }

    Gbn.diagnostics = {
        validateComponents: validateComponents
    };

    // Auto-run validation if in editor mode
    var config = utils.getConfig();
    if (config && config.isEditor) {
        // Wait for DOM ready or just run immediately?
        // Since it's an AJAX call, it doesn't depend on DOM much, but it's better to wait until main init is done?
        // Actually, let's just run it.
        validateComponents();
    }

})(window);
