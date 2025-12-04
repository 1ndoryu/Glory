;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.renderers = Gbn.ui.renderers || {};

    function getStyles(config, block) {
        var styles = {};
        
        // Base styles for button reset if needed, but usually handled by class
        styles['display'] = config.width === '100%' ? 'block' : 'inline-block';
        styles['text-decoration'] = 'none';
        styles['cursor'] = 'pointer';
        styles['text-align'] = 'center';
        
        // Size presets
        if (config.size === 'small') {
            styles['padding'] = '0.5rem 1rem';
            styles['font-size'] = '0.875rem';
        } else if (config.size === 'large') {
            styles['padding'] = '1rem 2rem';
            styles['font-size'] = '1.25rem';
        } else {
            // Medium default
            styles['padding'] = '0.75rem 1.5rem';
            styles['font-size'] = '1rem';
        }
        
        // Variant presets (can be overridden by custom colors)
        // These should ideally come from CSS classes, but we apply some defaults here for the editor
        // if classes are missing or for immediate feedback.
        // However, best practice is to toggle classes.
        
        if (config.width) {
            styles['width'] = config.width;
        }
        
        if (config.customBg) {
            styles['background-color'] = config.customBg;
            styles['border-color'] = config.customBg;
        }
        
        if (config.customColor) {
            styles['color'] = config.customColor;
        }
        
        if (config.borderRadius) {
            styles['border-radius'] = config.borderRadius;
        }
        
        // Typography overrides
        if (config.typography) {
            var t = config.typography;
            if (t.font && t.font !== 'System') styles['font-family'] = t.font;
            if (t.size) styles['font-size'] = t.size;
            if (t.weight) styles['font-weight'] = t.weight;
            if (t.transform) styles['text-transform'] = t.transform;
            if (t.letterSpacing) styles['letter-spacing'] = t.letterSpacing;
        }
        
        return styles;
    }

    function handleUpdate(block, path, value) {
        var el = block.element;
        
        if (path === 'texto') {
            el.textContent = value;
            // Restore controls if they were inside
            // But usually controls are appended after render
            return true;
        }
        
        if (path === 'url') {
            el.setAttribute('href', value);
            return true;
        }
        
        if (path === 'target') {
            el.setAttribute('target', value);
            return true;
        }
        
        if (path === 'variant') {
            // Remove old variant classes
            el.classList.remove('btn-primary', 'btn-secondary', 'btn-ghost', 'btn-link');
            // Add new
            el.classList.add('btn-' + value);
            return true;
        }
        
        if (path === 'size') {
            // We handle size via styles in getStyles, but if we had classes:
            // el.classList.remove('btn-sm', 'btn-lg');
            // if (value === 'small') el.classList.add('btn-sm');
            // if (value === 'large') el.classList.add('btn-lg');
            return false; // Let style composer handle it via getStyles
        }
        
        return false; // Let standard style composer handle the rest
    }

    Gbn.ui.renderers.button = {
        getStyles: getStyles,
        handleUpdate: handleUpdate
    };

})(typeof window !== 'undefined' ? window : this);
