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
        var nextState = JSON.parse(JSON.stringify(currentState)); // Deep clone for immutability (simple version)

        switch (action.type) {
            case Actions.INIT_BLOCKS:
                nextState.blocks = action.payload || {};
                break;

            case Actions.UPDATE_BLOCK:
                var blockId = action.id;
                var updates = action.payload;
                var breakpoint = action.breakpoint || nextState.mode;

                if (nextState.blocks[blockId]) {
                    var block = nextState.blocks[blockId];
                    
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
                    nextState.blocks[newBlock.id] = newBlock;
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
            return JSON.parse(JSON.stringify(state)); // Return copy to prevent direct mutation
        },

        dispatch: function(action) {
            // Validation
            if (Gbn.core.validator && !Gbn.core.validator.validateAction(action)) {
                if (Gbn.log) Gbn.log.error('Invalid Action Rejected', action);
                return;
            }

            if (Gbn.log) Gbn.log.info('Action Dispatched', { type: action.type, id: action.id });
            
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
