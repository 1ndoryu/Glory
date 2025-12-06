;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.core = Gbn.core || {};

    // Initial State
    var initialState = {
        blocks: {},      // Map of ID -> Block Config
        selection: null, // Current selected block ID
        mode: 'desktop', // Current responsive mode
        isDirty: false   // Unsaved changes flag
    };

    var state = JSON.parse(JSON.stringify(initialState));
    var listeners = [];

    // Action Types
    var Actions = {
        INIT_BLOCKS: 'INIT_BLOCKS',
        UPDATE_BLOCK: 'UPDATE_BLOCK',
        ADD_BLOCK: 'ADD_BLOCK',
        DELETE_BLOCK: 'DELETE_BLOCK',
        SELECT_BLOCK: 'SELECT_BLOCK',
        SET_MODE: 'SET_MODE'
    };

    // Reducer
    function reducer(currentState, action) {
        // Shallow clone state and blocks map to preserve references (like DOM elements)
        var nextState = Object.assign({}, currentState);
        if (nextState.blocks) {
            nextState.blocks = Object.assign({}, nextState.blocks);
        }

        switch (action.type) {
            case Actions.INIT_BLOCKS:
                nextState.blocks = action.payload || {};
                break;

            case Actions.UPDATE_BLOCK:
                var blockId = action.id;
                var updates = action.payload;
                var breakpoint = action.breakpoint || nextState.mode;

                if (nextState.blocks[blockId]) {
                    // Shallow clone the block to avoid mutating previous state
                    var block = Object.assign({}, nextState.blocks[blockId]);
                    // Deep clone config to avoid mutation (config should be serializable)
                    if (block.config) {
                        block.config = JSON.parse(JSON.stringify(block.config));
                    }
                    nextState.blocks[blockId] = block;
                    
                    // If updating specific breakpoint that is NOT desktop/base
                    if (breakpoint !== 'desktop') {
                        if (!block.config._responsive) block.config._responsive = {};
                        if (!block.config._responsive[breakpoint]) block.config._responsive[breakpoint] = {};
                        
                        Object.assign(block.config._responsive[breakpoint], updates);
                    } else {
                        // Base update
                        Object.assign(block.config, updates);
                    }
                    
                    nextState.isDirty = true;
                }
                break;

            case Actions.ADD_BLOCK:
                var newBlock = action.payload;
                if (newBlock && newBlock.id) {
                    // [FIX BUG-011] Deep clone the block to prevent shared references
                    // Especially critical for nested objects like padding, margin, typography
                    var clonedBlock = Object.assign({}, newBlock);
                    
                    // Deep clone config (serializable data)
                    if (clonedBlock.config) {
                        try {
                            clonedBlock.config = JSON.parse(JSON.stringify(clonedBlock.config));
                        } catch (e) {
                            // Fallback to shallow copy ifJSON fails (though config should always be serializable)
                            clonedBlock.config = Object.assign({}, clonedBlock.config);
                        }
                    }
                    
                    // Deep clone styles if present
                    if (clonedBlock.styles) {
                        clonedBlock.styles = Object.assign({}, clonedBlock.styles);
                        if (clonedBlock.styles.inline) {
                            clonedBlock.styles.inline = Object.assign({}, clonedBlock.styles.inline);
                        }
                        if (clonedBlock.styles.current) {
                            clonedBlock.styles.current = Object.assign({}, clonedBlock.styles.current);
                        }
                    }
                    
                    // Shallow clone meta (usually contains simple values)
                    if (clonedBlock.meta) {
                        clonedBlock.meta = Object.assign({}, clonedBlock.meta);
                    }
                    
                    // Keep element reference (DOM element, should not be cloned)
                    // Keep schema array reference (read-only data from PHP)
                    
                    nextState.blocks[newBlock.id] = clonedBlock;
                    nextState.isDirty = true;
                }
                break;

            case Actions.DELETE_BLOCK:
                if (nextState.blocks[action.id]) {
                    delete nextState.blocks[action.id];
                    if (nextState.selection === action.id) {
                        nextState.selection = null;
                    }
                    nextState.isDirty = true;
                }
                break;

            case Actions.SELECT_BLOCK:
                nextState.selection = action.id;
                break;

            case Actions.SET_MODE:
                nextState.mode = action.mode;
                break;

            default:
                return currentState; // No change
        }

        return nextState;
    }

    // Store API
    var Store = {
        getState: function() {
            // Shallow clone to preserve DOM references
            var copy = Object.assign({}, state);
            if (copy.blocks) {
                copy.blocks = Object.assign({}, copy.blocks);
            }
            return copy;
        },

        dispatch: function(action) {
            // Validation
            if (Gbn.core.validator && !Gbn.core.validator.validateAction(action)) {
                if (Gbn.log) Gbn.log.error('Invalid Action Rejected', action);
                return;
            }

            // if (Gbn.log) Gbn.log.info('Action Dispatched', { type: action.type, id: action.id });
            if (Gbn.log && (action.type === 'UPDATE_BLOCK' || action.type === 'UPDATE_THEME')) {
                 Gbn.log.info('Store Action', { type: action.type, id: action.id, breakpoint: action.breakpoint });
            }
            
            var prevState = state;
            state = reducer(state, action);

            // Notify listeners
            listeners.forEach(function(listener) {
                listener(state, prevState, action);
            });
        },

        subscribe: function(listener) {
            listeners.push(listener);
            return function unsubscribe() {
                var index = listeners.indexOf(listener);
                if (index > -1) {
                    listeners.splice(index, 1);
                }
            };
        },
        
        Actions: Actions
    };

    Gbn.core.store = Store;
    // Alias for ease of use
    Gbn.store = Store;

})(typeof window !== 'undefined' ? window : this);
