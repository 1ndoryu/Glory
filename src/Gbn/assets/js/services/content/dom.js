;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    var utils = Gbn.utils;
    
    Gbn.content = Gbn.content || {};

    function normalizeAttributes(el, role) {
        if (!role) {
            return;
        }
        var roles = Gbn.content.roles;
        var configHelpers = Gbn.content.config;
        
        var meta = roles.getMap()[role];
        if (!meta) {
            return;
        }
        if (meta.attr && !el.hasAttribute(meta.attr)) {
            el.setAttribute(meta.attr, role);
        }
        if (meta.dataAttr && !el.hasAttribute(meta.dataAttr)) {
            el.setAttribute(meta.dataAttr, '1');
        }
        if (!el.hasAttribute('data-gbn-role')) {
            el.setAttribute('data-gbn-role', role);
        }

        // Inyección automática de clases por defecto
        if (role === 'principal') {
            if (!el.classList.contains('primario')) {
                el.classList.add('primario');
            }
        } else if (role === 'secundario') {
            if (!el.classList.contains('secundario')) {
                el.classList.add('secundario');
            }
        }

        var defaults = roles.getRoleDefaults(role);
        var existingConfig = configHelpers.readJsonAttr(el, 'data-gbn-config');

        // Solo procesar config si ya existe una configuración previa
        // Los elementos sin configuración heredarán visualmente del Panel de Tema vía variables CSS
        if (!existingConfig || Object.keys(existingConfig).length === 0) {
            // Inicializar con configuración vacía para permitir herencia del tema
            el.setAttribute('data-gbn-config', JSON.stringify({}));
        } else {
            // Mantener configuración existente (persistida o del atributo data-gbn-config)
            el.setAttribute('data-gbn-config', JSON.stringify(existingConfig));
        }
        var config = utils.getConfig();
        var existingSchema = configHelpers.readJsonAttr(el, 'data-gbn-schema');
        
        // Bug 11: Solo inyectar schema si el usuario es editor
        // Esto limpia la salida HTML para usuarios finales
        if (config.isEditor) {
            if (!Array.isArray(existingSchema) || existingSchema.length === 0) {
                el.setAttribute('data-gbn-schema', JSON.stringify(defaults.schema || []));
            }
        }
    }

    Gbn.content.dom = {
        normalizeAttributes: normalizeAttributes
    };

})(window);
