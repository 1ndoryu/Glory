;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.renderers = Gbn.ui.renderers || {};

    function getStyles(config, block) {
        var styles = {};
        
        if (config.width) { styles['width'] = config.width; }
        if (config.height) { styles['height'] = config.height; }
        if (config.objectFit) { styles['object-fit'] = config.objectFit; }
        if (config.borderRadius) { styles['border-radius'] = config.borderRadius; }
        
        return styles;
    }

    function handleUpdate(block, path, value) {
        // Handle src update
        if (path === 'src') {
            block.element.src = value;
            return true;
        }
        
        // Handle alt update
        if (path === 'alt') {
            block.element.alt = value;
            return true;
        }
        
        // Handle style updates
        if (path === 'width') {
            block.element.style.width = value;
            return true;
        }
        
        if (path === 'height') {
            block.element.style.height = value;
            return true;
        }
        
        if (path === 'objectFit') {
            block.element.style.objectFit = value;
            return true;
        }
        
        if (path === 'borderRadius') {
            block.element.style.borderRadius = value;
            return true;
        }
         
         return true; // Handled
    }

    Gbn.ui.renderers.image = {
        getStyles: getStyles,
        handleUpdate: handleUpdate
    };

})(typeof window !== 'undefined' ? window : this);
