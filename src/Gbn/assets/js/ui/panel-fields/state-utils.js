;(function(global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.fieldUtils = Gbn.ui.fieldUtils || {};

    /**
     * Módulo de utilidades de estado CSS.
     * Estados soportados: hover, focus, active, visited, focus-visible, focus-within
     * @module state-utils
     */

    var SUPPORTED_STATES = ['hover', 'focus', 'active', 'visited', 'focus-visible', 'focus-within'];

    function getStateConfig(block, state, path) {
        if (!block || !block.config || !block.config._states) return undefined;
        if (SUPPORTED_STATES.indexOf(state) === -1) return undefined;
        
        var stateConfig = block.config._states[state];
        if (!stateConfig) return undefined;
        
        if (path) {
            var CONFIG_TO_CSS_MAP = Gbn.ui.fieldUtils.CONFIG_TO_CSS_MAP;
            var cssProp = CONFIG_TO_CSS_MAP ? CONFIG_TO_CSS_MAP[path] : null;
            if (cssProp) return stateConfig[cssProp];
            return stateConfig[path];
        }
        return stateConfig;
    }

    function hasStateStyles(block) {
        if (!block || !block.config || !block.config._states) return false;
        return Object.keys(block.config._states).length > 0;
    }

    Gbn.ui.fieldUtils.SUPPORTED_STATES = SUPPORTED_STATES;
    Gbn.ui.fieldUtils.getStateConfig = getStateConfig;
    Gbn.ui.fieldUtils.hasStateStyles = hasStateStyles;

})(window);
