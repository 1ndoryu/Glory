(function (global) {
    'use strict';

    var Gbn = (global.Gbn = global.Gbn || {});
    var store = Gbn.core && Gbn.core.store ? Gbn.core.store : null;

    if (!store) {
        console.warn('GBN Store not found. Subscriber not initialized.');
        return;
    }

    function handleStateChange(state, prevState, action) {
        if (!action) return;

        switch (action.type) {
            case store.Actions.UPDATE_BLOCK:
                var blockId = action.id;
                var block = state.blocks[blockId];

                // Update DOM styles
                if (block && Gbn.ui.panelRender && Gbn.ui.panelRender.applyBlockStyles) {
                    Gbn.ui.panelRender.applyBlockStyles(block);
                }

                // If this is the active block, refresh panel controls if needed
                // (This logic is currently duplicated in panel-render.js,
                // eventually we should move it all here)
                if (state.selection === blockId) {
                    // Refresh logic could go here
                }
                break;

            case store.Actions.SET_MODE:
                // Re-apply styles for all blocks when mode changes (responsive)
                if (Gbn.ui.panelRender && Gbn.ui.panelRender.applyThemeStylesToAllBlocks) {
                    Gbn.ui.panelRender.applyThemeStylesToAllBlocks();
                }
                break;
        }
    }

    // Subscribe
    store.subscribe(handleStateChange);
    console.log('GBN Store Subscriber Initialized');
})(typeof window !== 'undefined' ? window : this);
