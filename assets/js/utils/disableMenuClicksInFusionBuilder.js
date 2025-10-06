'use strict';

(function (global) {
    // --- Configuración de reintentos ---
    var MAX_ATTEMPTS = 10; // Máximo número de intentos para detectar el editor
    var attempt = 0;

    // Comprueba si Fusion Builder está activo.
    function isBuilderActive() {
        try {
            if (global.isFusionBuilderActive) {
                return !!global.isFusionBuilderActive();
            }
            if (typeof global.FUSION_BUILDER_ACTIVE !== 'undefined') {
                return !!global.FUSION_BUILDER_ACTIVE;
            }
            // Fallback: detectar la barra del editor en el DOM
            return !!global.document.querySelector('.fusion-builder-live-toolbar');
        } catch (e) {
            return false;
        }
    }

    // Aplica desactivación de clics si corresponde, con reintentos.
    function maybeApply() {
        var active = isBuilderActive();
        if (!active && attempt < MAX_ATTEMPTS) {
            attempt++;
            
            setTimeout(maybeApply, 400);
            return;
        }

        

        if (!active) {
            
            return; // No hacemos nada si no está activo.
        }

        /**
         * Deshabilita los clics en los contenedores del menú principal cuando
         * estamos dentro del editor visual de Fusion Builder.
         * Permite seguir usando los enlaces (<a>) internos.
         */
        function disableSiteMenuClicks() {
            var containers = global.document.querySelectorAll('.siteMenuContainer');
            
            if (!containers.length) {
                
                return;
            }

            containers.forEach(function (container) {
                // Desactivar interacciones en el contenedor.
                container.style.pointerEvents = 'none';
                container.style.zIndex = '0';

                // Volver a habilitar pointer-events en enlaces y otros elementos interactivos.
                var interactiveEls = container.querySelectorAll('a, button, input, [role="button"], [tabindex]');
                interactiveEls.forEach(function (el) {
                    el.style.pointerEvents = 'auto';
                });
            });
           
        }

        // Ejecutamos cuando el DOM esté completamente cargado.
        if (global.document.readyState === 'loading') {
            global.document.addEventListener('DOMContentLoaded', disableSiteMenuClicks);
        } else {
            disableSiteMenuClicks();
        }
    }

    // Ejecutamos cuando el DOM esté completamente cargado.
    if (global.document.readyState === 'loading') {
        global.document.addEventListener('DOMContentLoaded', maybeApply);
    } else {
        maybeApply();
    }
})(window); 