;(function(global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.fieldUtils = Gbn.ui.fieldUtils || {};

    /**
     * Módulo de mapeo CSS.
     * 
     * Contiene el mapeo entre rutas de configuración GBN y propiedades CSS.
     * Este mapeo es esencial para la sincronización bidireccional entre
     * el panel y los estilos computados del DOM.
     * 
     * REGLA: Toda nueva propiedad de estilo DEBE agregarse aquí.
     * Sin este mapeo, getComputedValueForPath() devuelve undefined.
     * 
     * @module css-map
     */

    /**
     * Mapeo de propiedades de config a propiedades CSS.
     * 
     * Las claves son rutas de configuración GBN (ej: 'padding.superior')
     * Los valores son propiedades CSS en camelCase (ej: 'paddingTop')
     * 
     * [BUG-SYNC FIX] Incluye propiedades de posicionamiento, overflow y dimensiones
     * que antes faltaban y causaban que el panel no pudiera leer estilos de clases CSS.
     */
    var CONFIG_TO_CSS_MAP = {
        // === SPACING ===
        'padding.superior': 'paddingTop',
        'padding.derecha': 'paddingRight',
        'padding.inferior': 'paddingBottom',
        'padding.izquierda': 'paddingLeft',
        'margin.superior': 'marginTop',
        'margin.derecha': 'marginRight',
        'margin.inferior': 'marginBottom',
        'margin.izquierda': 'marginLeft',
        
        // === BACKGROUND ===
        'fondo': 'backgroundColor',
        'background': 'backgroundColor',
        'backgroundColor': 'backgroundColor',
        'backgroundImage': 'backgroundImage',
        'backgroundSize': 'backgroundSize',
        'backgroundPosition': 'backgroundPosition',
        'backgroundRepeat': 'backgroundRepeat',
        'backgroundAttachment': 'backgroundAttachment',
        
        // === LAYOUT ===
        'gap': 'gap',
        'layout': 'display',
        'display': 'display',
        'flexDirection': 'flexDirection',
        'flexWrap': 'flexWrap',
        'flexJustify': 'justifyContent',
        'flexAlign': 'alignItems',
        'justifyContent': 'justifyContent',
        'alignItems': 'alignItems',
        
        // === DIMENSIONS ===
        'height': 'height',
        'ancho': 'width',
        'width': 'width',
        'maxAncho': 'maxWidth',
        'maxWidth': 'maxWidth',
        'maxHeight': 'maxHeight',
        'minHeight': 'minHeight',
        'minWidth': 'minWidth',
        
        // === TEXT ===
        'alineacion': 'textAlign',
        'textAlign': 'textAlign',
        'color': 'color',
        'size': 'fontSize',
        
        // === TYPOGRAPHY (nested) ===
        'typography.font': 'fontFamily',
        'typography.size': 'fontSize',
        'typography.weight': 'fontWeight',
        'typography.lineHeight': 'lineHeight',
        'typography.letterSpacing': 'letterSpacing',
        'typography.transform': 'textTransform',
        
        // === TEXT EFFECTS ===
        'textShadow': 'textShadow',
        
        // === BORDER ===
        'borderWidth': 'borderWidth',
        'borderStyle': 'borderStyle',
        'borderColor': 'borderColor',
        'borderRadius': 'borderRadius',
        
        // === POSITIONING ===
        'position': 'position',
        'zIndex': 'zIndex',
        'top': 'top',
        'right': 'right',
        'bottom': 'bottom',
        'left': 'left',
        
        // === OVERFLOW ===
        'overflow': 'overflow',
        'overflowX': 'overflowX',
        'overflowY': 'overflowY',
        
        // === INTERACTIVITY ===
        'cursor': 'cursor',
        'transition': 'transition',
        'transform': 'transform',
        'opacity': 'opacity',
        
        // === BOX SHADOW ===
        'boxShadow': 'boxShadow'
    };

    /**
     * Propiedades que no requieren unidades.
     * Usadas por el sistema para evitar agregar 'px' automáticamente.
     */
    var UNITLESS_PROPERTIES = [
        'gridColumns',
        'order',
        'zIndex',
        'opacity',
        'flexGrow',
        'flexShrink',
        'fontWeight',
        'lineHeight'
    ];

    /**
     * Valores por defecto del navegador que deben ignorarse.
     * Cuando getComputedStyle devuelve estos valores, no se consideran
     * como valores "configurados" sino como defaults del browser.
     */
    var BROWSER_DEFAULTS = {
        'position': 'static',
        'zIndex': ['auto', '0'],
        'overflow': 'visible',
        'overflowX': 'visible',
        'overflowY': 'visible',
        'width': 'auto',
        'ancho': 'auto',
        'height': 'auto',
        'backgroundColor': ['rgba(0, 0, 0, 0)', 'transparent'],
        'backgroundImage': 'none',
        'gap': ['normal', '0px'],
        'padding': '0px',
        'margin': '0px'
    };

    /**
     * Verifica si un valor es un default del navegador para una propiedad.
     * 
     * @param {string} path - Ruta de configuración
     * @param {string} value - Valor computado
     * @returns {boolean} true si es un default del navegador
     */
    function isBrowserDefault(path, value) {
        // Verificar propiedades con prefijo padding/margin
        if (path.indexOf('padding') === 0 && (value === '0px' || value === '0')) {
            return true;
        }
        if (path.indexOf('margin') === 0 && (value === '0px' || value === '0')) {
            return true;
        }

        var defaultVal = BROWSER_DEFAULTS[path];
        if (defaultVal === undefined) return false;
        
        if (Array.isArray(defaultVal)) {
            return defaultVal.indexOf(value) !== -1;
        }
        return defaultVal === value;
    }

    /**
     * Obtiene la propiedad CSS correspondiente a una ruta de configuración.
     * 
     * @param {string} configPath - Ruta de configuración GBN
     * @returns {string|undefined} Propiedad CSS o undefined si no hay mapeo
     */
    function getCssProperty(configPath) {
        return CONFIG_TO_CSS_MAP[configPath];
    }

    /**
     * Verifica si una propiedad es unitless.
     * 
     * @param {string} property - Nombre de la propiedad
     * @returns {boolean} true si no requiere unidades
     */
    function isUnitless(property) {
        return UNITLESS_PROPERTIES.indexOf(property) !== -1;
    }

    // Exportar
    Gbn.ui.fieldUtils.CONFIG_TO_CSS_MAP = CONFIG_TO_CSS_MAP;
    Gbn.ui.fieldUtils.UNITLESS_PROPERTIES = UNITLESS_PROPERTIES;
    Gbn.ui.fieldUtils.BROWSER_DEFAULTS = BROWSER_DEFAULTS;
    Gbn.ui.fieldUtils.isBrowserDefault = isBrowserDefault;
    Gbn.ui.fieldUtils.getCssProperty = getCssProperty;
    Gbn.ui.fieldUtils.isUnitless = isUnitless;

})(window);
