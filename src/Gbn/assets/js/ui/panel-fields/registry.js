;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.panelFields = Gbn.ui.panelFields || {};

    var registry = {};

    function register(type, builderFn) {
        if (!type || !builderFn) {
            console.error('Gbn.ui.panelFields.registry: Invalid arguments for register', type);
            return;
        }
        registry[type] = builderFn;
    }

    function get(type) {
        return registry[type];
    }

    Gbn.ui.panelFields.registry = {
        register: register,
        get: get
    };

})(typeof window !== 'undefined' ? window : this);
