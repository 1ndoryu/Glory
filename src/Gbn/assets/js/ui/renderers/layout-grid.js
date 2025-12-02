;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.renderers = Gbn.ui.renderers || {};

    // Helper to get value using shared getResponsiveValue
    function get(block, path, bp) {
        return Gbn.ui.renderers.shared.getResponsiveValue(block, path, bp);
    }

    function renderGrid(block, bp) {
        var styles = {};
        styles.display = 'grid';
        
        var gridColumns = get(block, 'gridColumns', bp);
        if (gridColumns) {
            styles['grid-template-columns'] = 'repeat(' + gridColumns + ', 1fr)';
        }
        
        var gridRows = get(block, 'gridRows', bp);
        if (gridRows && gridRows !== 'auto') {
            styles['grid-template-rows'] = gridRows;
        }
        
        var gridGap = get(block, 'gridGap', bp);
        var gap = get(block, 'gap', bp);
        
        if (gridGap) { styles.gap = gridGap + 'px'; }
        else if (gap) { styles.gap = gap + 'px'; }
        
        return styles;
    }

    Gbn.ui.renderers.layoutGrid = renderGrid;

})(typeof window !== 'undefined' ? window : this);
