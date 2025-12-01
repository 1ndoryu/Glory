;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};

    /**
     * Dispatcher principal para construir campos del panel
     * Delega a los módulos específicos según el tipo de campo
     */
    function buildField(block, field) {
        if (!field) return null;
        
        // Headers no requieren ID
        if (field.tipo !== 'header' && !field.id) return null;
        
        // Verificar condiciones de visibilidad
        var utils = Gbn.ui.fieldUtils;
        if (utils && utils.shouldShowField && !utils.shouldShowField(block, field)) {
            return null;
        }
        
        switch (field.tipo) {
            case 'header':
                return Gbn.ui.headerField && Gbn.ui.headerField.build(block, field);
            case 'spacing':
                return Gbn.ui.spacingField && Gbn.ui.spacingField.build(block, field);
            case 'slider':
                return Gbn.ui.sliderField && Gbn.ui.sliderField.build(block, field);
            case 'select':
                return Gbn.ui.selectField && Gbn.ui.selectField.build(block, field);
            case 'toggle':
                return Gbn.ui.toggleField && Gbn.ui.toggleField.build(block, field);
            case 'color':
                return Gbn.ui.colorField && Gbn.ui.colorField.build(block, field);
            case 'typography':
                return Gbn.ui.typographyField && Gbn.ui.typographyField.build(block, field);
            case 'icon_group':
                return Gbn.ui.iconGroupField && Gbn.ui.iconGroupField.build(block, field);
            case 'fraction':
                return Gbn.ui.fractionField && Gbn.ui.fractionField.build(block, field);
            case 'rich_text':
                return Gbn.ui.richTextField && Gbn.ui.richTextField.build(block, field);
            case 'text':
            default:
                return Gbn.ui.textField && Gbn.ui.textField.build(block, field);
        }
    }

    // API pública compatible con la versión anterior
    Gbn.ui.panelFields = {
        buildField: buildField,
        // Alias para compatibilidad
        addSyncIndicator: function(wrapper, block, fieldId) {
            if (Gbn.ui.fieldSync && Gbn.ui.fieldSync.addSyncIndicator) {
                return Gbn.ui.fieldSync.addSyncIndicator(wrapper, block, fieldId);
            }
        },
        updatePlaceholdersFromTheme: function(role, property, newValue) {
            if (Gbn.ui.fieldSync && Gbn.ui.fieldSync.updatePlaceholdersFromTheme) {
                return Gbn.ui.fieldSync.updatePlaceholdersFromTheme(role, property, newValue);
            }
        }
    };

})(window);

