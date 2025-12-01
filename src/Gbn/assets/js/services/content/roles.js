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
        content: { attribute: 'gloryContentRender', dataAttribute: 'data-gbnContent' },
        term_list: { attribute: 'gloryTermRender', dataAttribute: 'data-gbn-term-list' },
        image: { attribute: 'gloryImage', dataAttribute: 'data-gbn-image' },
        text: { attribute: 'gloryTexto', dataAttribute: 'data-gbn-text' }
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
                    schema: Array.isArray(def.schema) ? def.schema.slice() : []
                };
                if (def.selector) {
                    ensureSelector(role, def.selector);
                }
            }
        });

        // Definición de defaults para roles principales si no existen
        if (!ROLE_DEFAULTS.principal) {
            ROLE_DEFAULTS.principal = {
                config: {
                    layout: 'flex',
                    direction: 'row',
                    wrap: 'wrap',
                    justify: 'flex-start',
                    align: 'stretch',
                    padding: '20px'
                },
                schema: [
                    { 
                        id: 'layout', 
                        tipo: 'icon_group', 
                        etiqueta: 'Layout', 
                        opciones: [
                            {valor: 'block', icon: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>', etiqueta: 'Bloque'},
                            {valor: 'flex', icon: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M12 3v18"/></svg>', etiqueta: 'Flexbox'},
                            {valor: 'grid', icon: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 3v18"/><path d="M15 3v18"/><path d="M3 9h18"/><path d="M3 15h18"/></svg>', etiqueta: 'Grid'}
                        ] 
                    },
                    { 
                        id: 'gap', 
                        tipo: 'slider', 
                        etiqueta: 'Separación (Gap)', 
                        unidad: 'px', 
                        min: 0, 
                        max: 120, 
                        paso: 2,
                        condicion: ['layout', 'flex'] // Also for grid, logic handled in panel-render
                    },
                    { 
                        id: 'direction', // Note: PHP uses flexDirection, JS defaults used direction. Let's align to PHP if possible, but panel-render handles both.
                        tipo: 'icon_group', 
                        etiqueta: 'Dirección', 
                        condicion: ['layout', 'flex'],
                        opciones: [
                            { valor: 'row', icon: '<svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none"><path d="M4 12h16m-4-4l4 4-4 4"/></svg>', etiqueta: 'Fila' },
                            { valor: 'column', icon: '<svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none"><path d="M12 4v16m-4-4l4 4 4-4"/></svg>', etiqueta: 'Columna' }
                        ]
                    },
                    { 
                        id: 'wrap', 
                        tipo: 'icon_group', 
                        etiqueta: 'Wrap', 
                        condicion: ['layout', 'flex'],
                        opciones: [
                            { valor: 'nowrap', icon: '<svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M8 12h8"/></svg>', etiqueta: 'No Wrap' },
                            { valor: 'wrap', icon: '<svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none"><path d="M4 8h16M4 16h10"/></svg>', etiqueta: 'Wrap' }
                        ]
                    },
                    { 
                        id: 'justify', 
                        tipo: 'icon_group', 
                        etiqueta: 'Justify Content', 
                        condicion: ['layout', 'flex'],
                        opciones: [
                            { valor: 'flex-start', icon: '<svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none"><path d="M4 6h4M4 12h4M4 18h4"/></svg>', etiqueta: 'Start' },
                            { valor: 'center', icon: '<svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none"><path d="M10 6h4M10 12h4M10 18h4"/></svg>', etiqueta: 'Center' },
                            { valor: 'flex-end', icon: '<svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none"><path d="M16 6h4M16 12h4M16 18h4"/></svg>', etiqueta: 'End' },
                            { valor: 'space-between', icon: '<svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none"><path d="M4 6h2m14 0h2M4 12h2m14 0h2M4 18h2m14 0h2"/></svg>', etiqueta: 'Space Between' }
                        ]
                    },
                    { 
                        id: 'align', 
                        tipo: 'icon_group', 
                        etiqueta: 'Align Items', 
                        condicion: ['layout', 'flex'],
                        opciones: [
                            { valor: 'flex-start', icon: '<svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none"><path d="M4 6h16M4 10h16"/></svg>', etiqueta: 'Start' },
                            { valor: 'center', icon: '<svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none"><path d="M4 11h16M4 13h16"/></svg>', etiqueta: 'Center' },
                            { valor: 'flex-end', icon: '<svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none"><path d="M4 14h16M4 18h16"/></svg>', etiqueta: 'End' },
                            { valor: 'stretch', icon: '<svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none"><rect x="4" y="4" width="16" height="16" rx="2"/></svg>', etiqueta: 'Stretch' }
                        ]
                    },
                    {
                        id: 'gridColumns',
                        tipo: 'slider',
                        etiqueta: 'Columnas Grid',
                        min: 1,
                        max: 12,
                        paso: 1,
                        condicion: ['layout', 'grid']
                    },
                    {
                        id: 'gridGap',
                        tipo: 'slider',
                        etiqueta: 'Separación Grid',
                        unidad: 'px',
                        min: 0,
                        max: 120,
                        paso: 2,
                        condicion: ['layout', 'grid']
                    },
                    { id: 'padding', tipo: 'spacing', etiqueta: 'Padding' },
                    { id: 'background', tipo: 'color', etiqueta: 'Fondo' }
                ]
            };
        }

        if (!ROLE_DEFAULTS.secundario) {
            ROLE_DEFAULTS.secundario = {
                config: {
                    width: '1/1',
                    padding: '20px'
                },
                schema: [
                    { id: 'width', tipo: 'fraction', etiqueta: 'Ancho' },
                    { id: 'padding', tipo: 'spacing', etiqueta: 'Padding' },
                    { id: 'background', tipo: 'color', etiqueta: 'Fondo' }
                ]
            };
        }

        // Defaults hardcoded para gloryTexto si no vienen de containerDefs
        if (!ROLE_DEFAULTS.text) {
            ROLE_DEFAULTS.text = {
                config: {
                    tag: 'p',
                    texto: 'Nuevo texto',
                    alineacion: '',
                    color: '',
                    size: ''
                },
                schema: [
                    { id: 'tag', tipo: 'select', etiqueta: 'Etiqueta HTML', opciones: [
                        { valor: 'p', etiqueta: 'Párrafo (p)' },
                        { valor: 'h1', etiqueta: 'Encabezado 1 (h1)' },
                        { valor: 'h2', etiqueta: 'Encabezado 2 (h2)' },
                        { valor: 'h3', etiqueta: 'Encabezado 3 (h3)' },
                        { valor: 'h4', etiqueta: 'Encabezado 4 (h4)' },
                        { valor: 'h5', etiqueta: 'Encabezado 5 (h5)' },
                        { valor: 'h6', etiqueta: 'Encabezado 6 (h6)' },
                        { valor: 'span', etiqueta: 'Span' },
                        { valor: 'div', etiqueta: 'Div' }
                    ]},
                    { id: 'texto', tipo: 'rich_text', etiqueta: 'Contenido' },
                    { id: 'typography', tipo: 'typography', etiqueta: 'Tipografía' },
                    { id: 'alineacion', tipo: 'icon_group', etiqueta: 'Alineación', opciones: [
                        { valor: 'left', icon: '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><path d="M17 9.5H3M21 4.5H3M21 14.5H3M17 19.5H3"/></svg>', etiqueta: 'Izquierda' },
                        { valor: 'center', icon: '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><path d="M19 9.5H5M21 4.5H3M21 14.5H3M19 19.5H5"/></svg>', etiqueta: 'Centro' },
                        { valor: 'right', icon: '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><path d="M21 9.5H7M21 4.5H3M21 14.5H3M21 19.5H7"/></svg>', etiqueta: 'Derecha' },
                        { valor: 'justify', icon: '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><path d="M21 9.5H3M21 4.5H3M21 14.5H3M21 19.5H3"/></svg>', etiqueta: 'Justificado' }
                    ]},
                    { id: 'color', tipo: 'color', etiqueta: 'Color' }
                ]
            };
        }

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
        ['content', 'secundario', 'principal'].forEach(function (role) {
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
