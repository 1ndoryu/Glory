;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    var utils = Gbn.utils;
    
    // Namespacing
    Gbn.content = Gbn.content || {};

    var ROLE_DEFAULTS = {};
    var ROLE_MAP = {};

    var FALLBACK_SELECTORS = {
        principal: { attribute: 'gloryDiv', dataAttribute: 'data-gbnPrincipal' },
        secundario: { attribute: 'gloryDivSecundario', dataAttribute: 'data-gbnSecundario' },
        text: { attribute: 'gloryTexto', dataAttribute: 'data-gbn-text' },
        button: { attribute: 'gloryButton', dataAttribute: 'data-gbn-button' },
        image: { attribute: 'gloryImagen', dataAttribute: 'data-gbn-image' },
        // Fase 13: Componentes PostRender
        postRender: { attribute: 'gloryPostRender', dataAttribute: 'data-gbn-post-render' },
        postItem: { attribute: 'gloryPostItem', dataAttribute: 'data-gbn-post-item' },
        postField: { attribute: 'gloryPostField', dataAttribute: 'data-gbn-post-field' },
        // Fase 14: Componentes de Formulario
        form: { attribute: 'gloryForm', dataAttribute: 'data-gbn-form' },
        input: { attribute: 'gloryInput', dataAttribute: 'data-gbn-input' },
        textarea: { attribute: 'gloryTextarea', dataAttribute: 'data-gbn-textarea' },
        select: { attribute: 'glorySelect', dataAttribute: 'data-gbn-select' },
        submit: { attribute: 'glorySubmit', dataAttribute: 'data-gbn-submit' }
    };


    function ensureSelector(role, selector) {
        var existing = ROLE_MAP[role] || {};
        var fallback = FALLBACK_SELECTORS[role] || {};
        var attr = existing.attr
            || (selector && (selector.attribute || selector.attr))
            || fallback.attribute
            || fallback.attr
            || null;
        var dataAttr = existing.dataAttr
            || (selector && (selector.dataAttribute || selector.dataAttr))
            || fallback.dataAttribute
            || fallback.dataAttr
            || null;
        ROLE_MAP[role] = {
            attr: attr,
            dataAttr: dataAttr,
        };
    }

    function initRoles() {
        var containerDefs = utils.getConfig().containers || {};
        
        // Merge PHP definitions into ROLE_DEFAULTS
        Object.keys(containerDefs).forEach(function (role) {
            var def = containerDefs[role];
            if (def) {
                ROLE_DEFAULTS[role] = {
                    config: utils.assign({}, def.config || {}),
                    schema: Array.isArray(def.schema) ? def.schema.slice() : [],
                    icon: def.icon || null
                };
                if (def.selector) {
                    ensureSelector(role, def.selector);
                }
            }
        });



        if (!Object.keys(ROLE_DEFAULTS).length) {
            var legacyRoles = utils.getConfig().roles || {};
            Object.keys(legacyRoles).forEach(function (role) {
                var data = legacyRoles[role] || {};
                ROLE_DEFAULTS[role] = {
                    config: utils.assign({}, data.config || {}),
                    schema: Array.isArray(data.schema) ? data.schema.slice() : [],
                };
                ensureSelector(role, {});
            });
        }

        Object.keys(FALLBACK_SELECTORS).forEach(function (role) {
            ensureSelector(role, FALLBACK_SELECTORS[role]);
        });
    }

    var ROLE_PRIORITY = [];
    function initPriority() {
        ['secundario', 'principal'].forEach(function (role) {
            if (ROLE_MAP[role] && ROLE_PRIORITY.indexOf(role) === -1) {
                ROLE_PRIORITY.push(role);
            }
        });
        Object.keys(ROLE_MAP).forEach(function (role) {
            if (ROLE_PRIORITY.indexOf(role) === -1) {
                ROLE_PRIORITY.push(role);
            }
        });
    }

    function detectRole(el) {
        if (!ROLE_PRIORITY.length) {
            initRoles();
            initPriority();
        }
        for (var i = 0; i < ROLE_PRIORITY.length; i += 1) {
            var role = ROLE_PRIORITY[i];
            var meta = ROLE_MAP[role];
            if (!meta) {
                continue;
            }
            if ((meta.attr && el.hasAttribute(meta.attr)) || (meta.dataAttr && el.hasAttribute(meta.dataAttr))) {
                return role;
            }
        }
        return null;
    }

    function getRoleDefaults(role) {
        if (!ROLE_DEFAULTS[role]) {
            // Try init if empty
            initRoles();
        }
        var defaults = ROLE_DEFAULTS[role] || {};
        return {
            config: utils.assign({}, defaults.config || {}),
            schema: Array.isArray(defaults.schema) ? defaults.schema.slice() : [],
            icon: defaults.icon || null
        };
    }

    // Exports
    Gbn.content.roles = {
        init: initRoles,
        ensureSelector: ensureSelector,
        detectRole: detectRole,
        getRoleDefaults: getRoleDefaults,
        getMap: function() { return ROLE_MAP; },
        getFallback: function() { return FALLBACK_SELECTORS; }
    };

    // Initialize immediately to populate ROLE_MAP for scanner
    initRoles();
    initPriority();

})(window);
