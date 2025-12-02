;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.renderers = Gbn.ui.renderers || {};

    var styleComposer = Gbn.ui.renderers.styleComposer;

    function getStyles(config, block) {
        var bp = (Gbn.responsive && Gbn.responsive.getCurrentBreakpoint) ? Gbn.responsive.getCurrentBreakpoint() : 'desktop';
        var role = block.role || 'principal';
        var schema = (global.gloryGbnCfg && global.gloryGbnCfg.roleSchemas && global.gloryGbnCfg.roleSchemas[role]) ? global.gloryGbnCfg.roleSchemas[role] : {};
        
        return styleComposer.compose(block, schema, bp);
    }

    Gbn.ui.renderers.principal = {
        getStyles: getStyles
    };

})(typeof window !== 'undefined' ? window : this);
