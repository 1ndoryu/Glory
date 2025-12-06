;(function(global) {
    'use strict';
    
    // Core definition
    var GbnIcons = global.GbnIcons = global.GbnIcons || {};
    GbnIcons.registry = {};
    
    // Parts container for split loading
    GbnIcons.parts = GbnIcons.parts || {};

    /**
     * Initializes the registry by merging all loaded parts.
     * Can be called multiple times as new parts are loaded.
     */
    GbnIcons.init = function() {
        if (GbnIcons.parts) {
            for (var key in GbnIcons.parts) {
                if (GbnIcons.parts.hasOwnProperty(key)) {
                    // Start logging for debugging
                    // console.log("[GbnIcons] Merging part: " + key);
                    var part = GbnIcons.parts[key];
                    for (var iconKey in part) {
                        if (part.hasOwnProperty(iconKey)) {
                            GbnIcons.registry[iconKey] = part[iconKey];
                        }
                    }
                }
            }
        }
    };

    /**
     * Get an icon by key
     * @param {string} key - Icon key (e.g. 'layout.grid')
     * @param {Object} attrs - Optional attributes overrides
     * @returns {string} SVG string
     */
    GbnIcons.get = function(key, attrs) {
        // Ensure init has run at least once or runs now
        if (Object.keys(GbnIcons.registry).length === 0) {
            GbnIcons.init();
        }

        var icon = GbnIcons.registry[key];
        
        if (!icon) {
            console.warn('[IconRegistry] Icon not found: ' + key);
            return '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>';
        }

        attrs = attrs || {};
        if (Object.keys(attrs).length > 0) {
            for (var attr in attrs) {
                if (attrs.hasOwnProperty(attr)) {
                    var value = attrs[attr];
                    var regex = new RegExp(attr + '="[^"]*"', 'g');
                    if (regex.test(icon)) {
                        icon = icon.replace(regex, attr + '="' + value + '"');
                    } else {
                        // Add if missing
                        icon = icon.replace('<svg', '<svg ' + attr + '="' + value + '"');
                    }
                }
            }
        }

        return icon;
    };

    /**
     * Get options array for iconGroup
     */
    GbnIcons.getOptions = function(keys) {
        var options = [];
        for (var i = 0; i < keys.length; i++) {
            var key = keys[i];
            options.push({
                value: key.split('.').pop(),
                icon: GbnIcons.get(key)
            });
        }
        return options;
    };

    // Auto-init immediately in case parts are already loaded
    GbnIcons.init();

})(window);
