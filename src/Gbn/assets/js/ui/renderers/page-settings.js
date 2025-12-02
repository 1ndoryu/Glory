;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.renderers = Gbn.ui.renderers || {};

    var utils = Gbn.utils;

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

    function handleUpdate(block, path, value) {
        var current = cloneConfig(block.config);
        var segments = path.split('.');
        var cursor = current;
        for (var i = 0; i < segments.length - 1; i++) {
            if (!cursor[segments[i]]) cursor[segments[i]] = {};
            cursor = cursor[segments[i]];
        }
        cursor[segments[segments.length - 1]] = value;
        
        // Update block config reference (it's a mock block but we need to keep it updated)
        block.config = current;
        
        // Sync to global config
        if (!Gbn.config) Gbn.config = {};
        Gbn.config.pageSettings = current;
        
        if (Gbn.ui.panelTheme && Gbn.ui.panelTheme.applyPageSettings) {
            Gbn.ui.panelTheme.applyPageSettings(current);
        }
        
        // Dispatch event
        var event;
        if (typeof global.CustomEvent === 'function') {
            event = new CustomEvent('gbn:configChanged', { detail: { id: 'page-settings' } });
        } else {
            event = document.createEvent('CustomEvent');
            event.initCustomEvent('gbn:configChanged', false, false, { id: 'page-settings' });
        }
        global.dispatchEvent(event);
        
        return current; // Return updated config
    }

    Gbn.ui.renderers.pageSettings = {
        handleUpdate: handleUpdate
    };

})(typeof window !== 'undefined' ? window : this);
