;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    var state = Gbn.state;
    
    // Ensure namespace exists
    Gbn.content = Gbn.content || {};

    // Re-export main functions for backward compatibility and ease of use
    // These functions are now implemented in sub-modules but exposed here as the main API
    
    // Scan: Scans the DOM for GBN blocks
    Gbn.content.scan = function(target) {
        if (Gbn.content.scanner && Gbn.content.scanner.scan) {
            return Gbn.content.scanner.scan(target);
        }
        return [];
    };

    // Hydrate: Loads dynamic content for content blocks
    Gbn.content.hydrate = function(blocks) {
        if (Gbn.content.hydrator && Gbn.content.hydrator.hydrate) {
            return Gbn.content.hydrator.hydrate(blocks);
        }
    };

    // ParseOptionsString: Helper exposed for utility
    Gbn.content.parseOptionsString = function(str) {
        if (Gbn.content.config && Gbn.content.config.parseOptions) {
            return Gbn.content.config.parseOptions(str);
        }
        return {};
    };

    // Global Event Listener for Config Changes
    if (typeof global.addEventListener === 'function') {
        global.addEventListener('gbn:configChanged', function(e) {
            var id = e.detail && e.detail.id;
            if (!id) return;
            var block = state.get(id);
            if (block && block.role === 'content') {
                if (Gbn.content.hydrator && Gbn.content.hydrator.requestContent) {
                    Gbn.content.hydrator.requestContent(block);
                }
            }
        });
    }

})(window);
