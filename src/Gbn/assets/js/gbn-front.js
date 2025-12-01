;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};

    // Initialize GBN on frontend
    document.addEventListener('DOMContentLoaded', function() {
        // Only run if not in builder mode (builder handles its own init)
        // We check for the presence of the builder UI or a specific flag
        var isBuilder = document.body.classList.contains('gbn-builder-active') || 
                        document.getElementById('gbn-panel');

        if (!isBuilder) {
            if (Gbn.content && Gbn.content.scan && Gbn.content.hydrate) {
                // Scan the document for GBN blocks
                var blocks = Gbn.content.scan(document.body);
                
                // Hydrate the blocks (load dynamic content)
                Gbn.content.hydrate(blocks);
            }
        }
    });

})(window);
