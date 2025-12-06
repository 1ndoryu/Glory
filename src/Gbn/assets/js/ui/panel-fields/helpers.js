;(function(global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.fieldUtils = Gbn.ui.fieldUtils || {};

    /**
     * Módulo de utilidades generales para campos del panel.
     * @module helpers
     */

    // Helper para obtener iconos de forma segura
    function getIcon(key, fallback) {
        if (global.GbnIcons && global.GbnIcons.get) {
            return global.GbnIcons.get(key);
        }
        return fallback || '';
    }

    // Iconos SVG para campos de spacing
    var ICONS = {
        superior: getIcon('spacing.superior'),
        derecha: getIcon('spacing.derecha'),
        inferior: getIcon('spacing.inferior'),
        izquierda: getIcon('spacing.izquierda')
    };

    /**
     * Agrega descripción/hint a un campo
     */
    function appendFieldDescription(container, field) {
        if (!field || !field.descripcion) return;
        var hint = document.createElement('p');
        hint.className = 'gbn-field-hint';
        hint.textContent = field.descripcion;
        container.appendChild(hint);
    }

    /**
     * Parsea un valor de spacing en valor numérico y unidad
     */
    function parseSpacingValue(raw, fallbackUnit) {
        if (raw === null || raw === undefined || raw === '') {
            return { valor: '', unidad: fallbackUnit || 'px' };
        }
        if (typeof raw === 'number') {
            return { valor: String(raw), unidad: fallbackUnit || 'px' };
        }
        var match = /^(-?\d+(?:\.\d+)?)([a-z%]*)$/i.exec(String(raw).trim());
        if (!match) {
            return { valor: String(raw), unidad: fallbackUnit || 'px' };
        }
        return { valor: match[1], unidad: match[2] || fallbackUnit || 'px' };
    }

    /**
     * Obtiene el schema de un role desde ContainerRegistry
     */
    function obtenerSchemaDelRole(role) {
        if (!role) return [];
        
        if (typeof gloryGbnCfg !== 'undefined' && gloryGbnCfg.roleSchemas && gloryGbnCfg.roleSchemas[role]) {
            var roleData = gloryGbnCfg.roleSchemas[role];
            if (roleData.schema && Array.isArray(roleData.schema)) {
                return roleData.schema;
            }
        }
        
        if (Gbn.content && Gbn.content.roles && Gbn.content.roles.getRoleDefaults) {
            var defaults = Gbn.content.roles.getRoleDefaults(role);
            if (defaults && defaults.schema && Array.isArray(defaults.schema)) {
                return defaults.schema;
            }
        }
        
        return [];
    }

    // Exportar
    Gbn.ui.fieldUtils.ICONS = ICONS;
    Gbn.ui.fieldUtils.getIcon = getIcon;
    Gbn.ui.fieldUtils.appendFieldDescription = appendFieldDescription;
    Gbn.ui.fieldUtils.parseSpacingValue = parseSpacingValue;
    Gbn.ui.fieldUtils.obtenerSchemaDelRole = obtenerSchemaDelRole;

})(window);
