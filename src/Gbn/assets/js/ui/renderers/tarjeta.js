(function (global) {
    'use strict';

    var Gbn = (global.Gbn = global.Gbn || {});
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.renderers = Gbn.ui.renderers || {};

    var styleComposer = Gbn.ui.renderers.styleComposer;
    var traits = Gbn.ui.renderers.traits;

    /**
     * TarjetaComponent Renderer
     *
     * Maneja los estilos de las tarjetas con imagen de fondo.
     * La imagen de fondo se aplica al elemento interno .card-bg-image
     *
     * Estructura HTML esperada:
     * <div gloryTarjeta class="service-card card-dark">
     *     <div class="card-content">...</div>
     *     <div class="card-bg-image" style="background-image: url(...)"></div>
     * </div>
     */

    /**
     * Obtiene los estilos CSS para el componente
     * @param {Object} config - Configuracion del bloque
     * @param {Object} block - Bloque completo
     * @returns {Object} Objeto con propiedades CSS
     */
    function getStyles(config, block) {
        var bp = Gbn.responsive && Gbn.responsive.getCurrentBreakpoint ? Gbn.responsive.getCurrentBreakpoint() : 'desktop';
        var role = block.role || 'tarjeta';
        var schema = global.gloryGbnCfg && global.gloryGbnCfg.roleSchemas && global.gloryGbnCfg.roleSchemas[role] ? global.gloryGbnCfg.roleSchemas[role] : {};

        // Usar styleComposer para estilos base (igual que secundario)
        var styles = styleComposer.compose(block, schema, bp);

        return styles;
    }

    /**
     * Maneja actualizaciones en tiempo real
     * @param {Object} block - Bloque a actualizar
     * @param {string} path - Ruta de la propiedad cambiada
     * @param {*} value - Nuevo valor
     * @returns {boolean} true si se manejo la actualizacion
     */
    function handleUpdate(block, path, value) {
        if (!block || !block.element) return false;
        var el = block.element;

        // Propiedad especifica: imagen de fondo de la tarjeta
        if (path === 'cardBackgroundImage') {
            var bgImageEl = el.querySelector('.card-bg-image');

            // Si no existe el elemento .card-bg-image, crearlo
            if (!bgImageEl) {
                bgImageEl = document.createElement('div');
                bgImageEl.className = 'card-bg-image';
                el.appendChild(bgImageEl);
            }

            // Aplicar la imagen de fondo
            if (value && value.url) {
                bgImageEl.style.backgroundImage = "url('" + value.url + "')";
            } else if (typeof value === 'string' && value) {
                bgImageEl.style.backgroundImage = "url('" + value + "')";
            } else {
                bgImageEl.style.backgroundImage = '';
            }
            return true;
        }

        // Delegar propiedades comunes a traits
        if (traits && traits.handleCommonUpdate) {
            return traits.handleCommonUpdate(el, path, value);
        }

        return false;
    }

    // Exportar renderer
    Gbn.ui.renderers.tarjeta = {
        getStyles: getStyles,
        handleUpdate: handleUpdate
    };
})(typeof window !== 'undefined' ? window : this);
