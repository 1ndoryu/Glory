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
        
        var role = block.role;
        var prefix = role ? '--gbn-' + role + '-' : null;

        var gridColumns = get(block, 'gridColumns', bp);
        if (gridColumns) {
            styles['grid-template-columns'] = 'repeat(' + gridColumns + ', 1fr)';
        } else if (prefix) {
            // [GBN-DEBUG] Trazar lectura de fallback Grid
            console.log('[GBN-DEBUG] Grid Fallback:', prefix + 'grid-columns');
            
            // Fallback a variable del tema. 
            // Bug 32 Fix V6: Intentamos con el ID exacto 'gridColumns' y su versión kebab 'grid-columns'
            styles['grid-template-columns'] = 'repeat(var(' + prefix + 'grid-columns, var(' + prefix + 'gridColumns, 1)), 1fr)';
        }
        
        var gridRows = get(block, 'gridRows', bp);
        if (gridRows && gridRows !== 'auto') {
            styles['grid-template-rows'] = gridRows;
        }
        
        var gridGap = get(block, 'gridGap', bp);
        var gap = get(block, 'gap', bp);
        
        if (gridGap) { 
            styles.gap = gridGap + 'px'; 
        } else if (gap) { 
            styles.gap = gap + 'px'; 
        } else if (prefix) {
            styles.gap = 'var(' + prefix + 'gap)';
        }
        
        return styles;
    }

    Gbn.ui.renderers.layoutGrid = renderGrid;

})(typeof window !== 'undefined' ? window : this);
