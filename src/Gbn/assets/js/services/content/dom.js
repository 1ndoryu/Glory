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
        var inlineStyles = utils.parseStyleString(el.getAttribute('style') || '');

        // Inyección de estilos por defecto si no hay inline styles ni config previa
        // Esto asegura que los nuevos elementos (o los que no tienen estilos) tengan los defaults requeridos
        // 1. Principal: padding 20px, display flex
        // 2. Secundario: padding 20px
        var hasInlinePadding = inlineStyles['padding'] || inlineStyles['padding-top']; // Chequeo básico
        var hasInlineDisplay = inlineStyles['display'];

        if (role === 'principal') {
            if (!hasInlinePadding) {
                inlineStyles['padding'] = '20px';
            }
            if (!hasInlineDisplay) {
                inlineStyles['display'] = 'flex';
                // Defaults adicionales para flex si se desea
                if (!inlineStyles['flex-wrap']) inlineStyles['flex-wrap'] = 'wrap';
            }
        } else if (role === 'secundario') {
            if (!hasInlinePadding) {
                inlineStyles['padding'] = '20px';
            }
        }

        if (!existingConfig || Object.keys(existingConfig).length === 0) {
            // Si no hay configuración existente, sincronizar estilos inline (incluyendo los defaults inyectados) con defaults
            var initialConfig = configHelpers.syncInlineStyles(inlineStyles, defaults.schema, defaults.config);
            
            // Asegurar que los defaults inyectados se reflejen en la config inicial si syncInlineStylesWithConfig no los capturó
            // (syncInlineStylesWithConfig mapea padding-top etc, pero 'padding' shorthand puede necesitar manejo especial en utils o aquí)
            // Por simplicidad, si inyectamos 'padding: 20px', deberíamos asegurarnos que la config lo tenga.
            // syncInlineStylesWithConfig maneja padding-top/right/bottom/left.
            // Vamos a expandir el shorthand 'padding' para que sync lo agarre si es necesario, 
            // o mejor, dejar que el styleManager aplique los estilos y la config se derive.
            
            // NOTA: syncInlineStylesWithConfig actualmente solo mira padding-top, etc.
            // Si inlineStyles tiene 'padding', necesitamos expandirlo para que sync lo vea.
            if (inlineStyles['padding']) {
                var pVal = inlineStyles['padding'];
                // Asumimos 1 valor para simplificar por ahora (20px)
                if (!inlineStyles['padding-top']) inlineStyles['padding-top'] = pVal;
                if (!inlineStyles['padding-right']) inlineStyles['padding-right'] = pVal;
                if (!inlineStyles['padding-bottom']) inlineStyles['padding-bottom'] = pVal;
                if (!inlineStyles['padding-left']) inlineStyles['padding-left'] = pVal;
            }

            // Re-sincronizar con los valores expandidos
            initialConfig = configHelpers.syncInlineStyles(inlineStyles, defaults.schema, defaults.config);
            
            // Forzar layout flex en config para principal si se inyectó
            if (role === 'principal' && inlineStyles['display'] === 'flex') {
                // Asumiendo que hay una propiedad 'layout' en el schema/config
                // Si no existe en defaults.config, se agregará.
                if (!initialConfig.layout) initialConfig.layout = 'flex';
            }

            el.setAttribute('data-gbn-config', JSON.stringify(initialConfig));
        } else {
            var mergedConfig = utils.assign({}, defaults.config || {}, existingConfig || {});
            el.setAttribute('data-gbn-config', JSON.stringify(mergedConfig));
        }
        var existingSchema = configHelpers.readJsonAttr(el, 'data-gbn-schema');
        if (!Array.isArray(existingSchema) || existingSchema.length === 0) {
            el.setAttribute('data-gbn-schema', JSON.stringify(defaults.schema || []));
        }
    }

    Gbn.content.dom = {
        normalizeAttributes: normalizeAttributes
    };

})(window);
