;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.renderers = Gbn.ui.renderers || {};

    // Helper to get value using shared getResponsiveValue
    function get(block, path, bp) {
        return Gbn.ui.renderers.shared.getResponsiveValue(block, path, bp);
    }

    function renderFlex(block, bp) {
        var styles = {};
        styles.display = 'flex';
        
        var direction = get(block, 'direction', bp) || get(block, 'flexDirection', bp);
        var wrap = get(block, 'wrap', bp) || get(block, 'flexWrap', bp);
        var justify = get(block, 'justify', bp) || get(block, 'flexJustify', bp);
        var align = get(block, 'align', bp) || get(block, 'flexAlign', bp);
        var gap = get(block, 'gap', bp);
        
        if (direction) { styles['flex-direction'] = direction; }
        if (wrap) { styles['flex-wrap'] = wrap; }
        if (justify) { styles['justify-content'] = justify; }
        if (align) { styles['align-items'] = align; }
        if (gap) { styles.gap = gap + 'px'; }
        
        return styles;
    }

    Gbn.ui.renderers.layoutFlex = renderFlex;

})(typeof window !== 'undefined' ? window : this);
