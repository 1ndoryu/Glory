(function (global) {
    'use strict';

    /**
     * Determina si estamos en el contexto del editor en vivo de Fusion Builder.
     * 1. Revisa si la URL contiene el parámetro fb-edit.
     * 2. Revisa si existe en el DOM un elemento con la clase
     *    .fusion-builder-live-toolbar (insertada por el editor visual).
     * 3. Maneja también el caso en que el sitio se visualiza dentro de un iframe.
     *
     * @returns {boolean}
     */
    function isFusionBuilderActive() {
        // 1. Verificación del propio documento.
        try {
            if (global.location && global.location.search.includes('fb-edit')) {
                return true;
            }
        } catch (e) {}

        // 2. Si estamos dentro de un iframe, comprobamos el contexto padre/top.
        try {
            if (global.self !== global.top) {
                if (global.top.location.search && global.top.location.search.includes('fb-edit')) {
                    return true;
                }
                // Verificamos la presencia del toolbar en el DOM del padre.
                if (global.parent && global.parent.document && global.parent.document.querySelector('.fusion-builder-live-toolbar')) {
                    return true;
                }
            }
        } catch (e) {
            // Puede fallar por políticas de mismo origen; ignoramos.
        }

        // 3. Comprobación local del toolbar (caso no-iframe).
        return !!global.document.querySelector('.fusion-builder-live-toolbar');
    }

    // Exponer la función globalmente para que otros scripts puedan invocarla dinámicamente.
    global.isFusionBuilderActive = isFusionBuilderActive;

    // Variable booleana evaluada una sola vez al cargar este script.
    // Útil cuando solo interesa conocer el estado al inicio y no se requiere reevaluar.
    global.FUSION_BUILDER_ACTIVE = isFusionBuilderActive();

    // Permitir refrescar manualmente el flag desde otros scripts si fuera necesario.
    global.refreshFusionBuilderFlag = function() {
        try {
            global.FUSION_BUILDER_ACTIVE = isFusionBuilderActive();
        } catch (e) {}
        return global.FUSION_BUILDER_ACTIVE;
    };

    // Reevaluar al cargar el DOM, útil cuando el toolbar o el parámetro llegan tarde.
    if (global.document && global.document.addEventListener) {
        global.document.addEventListener('DOMContentLoaded', function () {
            try {
                global.FUSION_BUILDER_ACTIVE = isFusionBuilderActive();
            } catch (e) {}
        });
    }

})(window); 