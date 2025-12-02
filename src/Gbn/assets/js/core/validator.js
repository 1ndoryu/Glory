;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.core = Gbn.core || {};

    var Validator = {
        /**
         * Validates a block structure.
         * @param {Object} block 
         * @returns {boolean} True if valid, false otherwise.
         */
        validateBlock: function(block) {
            if (!block || typeof block !== 'object') {
                console.error('[GBN Validator] Invalid block: must be an object', block);
                return false;
            }
            if (!block.id || typeof block.id !== 'string') {
                console.error('[GBN Validator] Invalid block: missing or invalid ID', block);
                return false;
            }
            if (!block.role || typeof block.role !== 'string') {
                console.warn('[GBN Validator] Block ' + block.id + ' missing role, defaulting to "block"');
                block.role = 'block';
            }
            if (!block.config || typeof block.config !== 'object') {
                console.warn('[GBN Validator] Block ' + block.id + ' missing config, defaulting to {}');
                block.config = {};
            }
            return true;
        },

        /**
         * Sanitizes a value based on expected type (heuristic).
         * @param {*} value 
         * @returns {*} Sanitized value.
         */
        sanitizeValue: function(value) {
            if (value === null || value === undefined) return undefined;
            if (Number.isNaN(value)) return undefined;
            return value;
        },

        /**
         * Validates an action payload before dispatch.
         * @param {Object} action 
         * @returns {boolean}
         */
        validateAction: function(action) {
            if (!action || !action.type) {
                console.error('[GBN Validator] Invalid action: missing type', action);
                return false;
            }
            
            if (action.type === 'UPDATE_BLOCK') {
                if (!action.id) {
                    console.error('[GBN Validator] UPDATE_BLOCK missing ID');
                    return false;
                }
                if (!action.payload || typeof action.payload !== 'object') {
                    console.error('[GBN Validator] UPDATE_BLOCK missing payload object');
                    return false;
                }
            }
            
            return true;
        }
    };

    Gbn.core.validator = Validator;

})(typeof window !== 'undefined' ? window : this);
