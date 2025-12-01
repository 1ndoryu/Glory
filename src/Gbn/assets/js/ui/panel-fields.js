/**
 * Panel Fields - Wrapper de Compatibilidad
 * 
 * Este archivo mantiene compatibilidad con código existente que importa panel-fields.js
 * La lógica real está modularizada en la carpeta panel-fields/
 * 
 * Módulos:
 * - utils.js: Utilidades compartidas (getDeepValue, getThemeDefault, etc.)
 * - sync.js: Indicadores de sincronización con CSS
 * - spacing.js: Campo de spacing (padding/margin)
 * - slider.js: Campo slider/range
 * - select.js: Campo select/dropdown
 * - toggle.js: Campo toggle on/off
 * - text.js: Campo de texto simple
 * - color.js: Campo de color con paleta
 * - typography.js: Campo de tipografía compuesto
 * - icon-group.js: Grupo de botones con íconos
 * - fraction.js: Selector de fracciones de ancho
 * - rich-text.js: Editor de texto enriquecido
 * - header.js: Separador/header de sección
 * - index.js: Dispatcher principal
 */
;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};

    // Verificar que los módulos están cargados
    if (!Gbn.ui.panelFields || !Gbn.ui.panelFields.buildField) {
        console.warn('[GBN] panel-fields: Los módulos no están cargados correctamente');
    }

    // Re-exportar API para compatibilidad
    // El módulo index.js ya expone Gbn.ui.panelFields con buildField, addSyncIndicator, updatePlaceholdersFromTheme

})(window);
