;(function (global) {
    'use strict';

    /**
     * RENDERER TRAITS - Fase 11 Refactorización SOLID
     * 
     * Este módulo centraliza funciones reutilizables para los renderers JS,
     * siguiendo el mismo principio que los Traits PHP (HasTypography, HasSpacing, etc.)
     * 
     * PRINCIPIO: Eliminar código duplicado entre button.js, text.js, etc.
     * 
     * @module Gbn.ui.renderers.traits
     */

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.renderers = Gbn.ui.renderers || {};

    // ============================================================
    // HELPERS COMUNES
    // ============================================================

    /**
     * Normaliza valores de tamaño CSS.
     * Si es un número puro, agrega 'px'. Si ya tiene unidad, lo deja como está.
     * 
     * @param {string|number|null} val - Valor a normalizar
     * @returns {string|null} - Valor normalizado con unidad CSS
     * 
     * @example
     * normalizeSize(16)       // "16px"
     * normalizeSize("2rem")   // "2rem"
     * normalizeSize("50%")    // "50%"
     * normalizeSize("")       // null
     */
    function normalizeSize(val) {
        if (!val) return val;
        var strVal = String(val).trim();
        if (strVal === '' || strVal === 'null') return null;
        // Si ya tiene unidad, dejarlo como está
        if (/[a-z%]+$/i.test(strVal)) return strVal;
        // Si es un número puro, agregar px
        if (!isNaN(parseFloat(strVal)) && isFinite(strVal)) {
            return strVal + 'px';
        }
        return strVal;
    }

    /**
     * Convierte camelCase a kebab-case para propiedades CSS.
     * 
     * @param {string} str - Nombre en camelCase
     * @returns {string} - Nombre en kebab-case
     * 
     * @example
     * toKebabCase('backgroundColor') // "background-color"
     * toKebabCase('fontSize')        // "font-size"
     */
    function toKebabCase(str) {
        return str.replace(/([A-Z])/g, function(match) { 
            return '-' + match.toLowerCase(); 
        });
    }

    // ============================================================
    // TRAIT: TYPOGRAPHY
    // Equivalente JS de HasTypography.php
    // ============================================================

    /**
     * Genera estilos CSS de tipografía desde configuración.
     * 
     * @param {Object} typographyConfig - Objeto con campos de tipografía
     * @returns {Object} - Objeto con propiedades CSS
     */
    function getTypographyStyles(typographyConfig) {
        var styles = {};
        if (!typographyConfig) return styles;
        
        var t = typographyConfig;
        if (t.font && t.font !== 'Default' && t.font !== 'System') {
            styles['font-family'] = t.font + ', sans-serif';
        }
        if (t.size) {
            styles['font-size'] = normalizeSize(t.size);
        }
        if (t.weight) {
            styles['font-weight'] = t.weight;
        }
        if (t.lineHeight) {
            styles['line-height'] = t.lineHeight;
        }
        if (t.letterSpacing) {
            styles['letter-spacing'] = normalizeSize(t.letterSpacing);
        }
        if (t.transform && t.transform !== 'none') {
            styles['text-transform'] = t.transform;
        }
        
        return styles;
    }

    /**
     * Aplica cambios de tipografía al DOM en tiempo real.
     * Maneja paths anidados como 'typography.size', 'typography.font', etc.
     * 
     * @param {HTMLElement} element - Elemento DOM a modificar
     * @param {string} property - Propiedad de tipografía (font, size, weight, etc.)
     * @param {string|number} value - Valor a aplicar
     * @returns {boolean} - true si se manejó la propiedad
     */
    function applyTypography(element, property, value) {
        switch (property) {
            case 'font':
                if (value && value !== 'System' && value !== 'Default') {
                    element.style.fontFamily = value + ', sans-serif';
                } else {
                    element.style.fontFamily = '';
                }
                return true;
            case 'size':
                element.style.fontSize = normalizeSize(value) || '';
                return true;
            case 'weight':
                element.style.fontWeight = value || '';
                return true;
            case 'lineHeight':
                element.style.lineHeight = value || '';
                return true;
            case 'letterSpacing':
                element.style.letterSpacing = normalizeSize(value) || '';
                return true;
            case 'transform':
                element.style.textTransform = (value && value !== 'none') ? value : '';
                return true;
            default:
                return false;
        }
    }

    // ============================================================
    // TRAIT: SPACING
    // Equivalente JS de HasSpacing.php
    // ============================================================

    /**
     * Genera estilos CSS de spacing (padding/margin) desde configuración.
     * 
     * @param {Object} spacingConfig - Objeto con {superior, derecha, inferior, izquierda}
     * @param {string} property - 'padding' o 'margin'
     * @returns {Object} - Objeto con propiedades CSS
     */
    function getSpacingStyles(spacingConfig, property) {
        var styles = {};
        if (!spacingConfig) return styles;
        
        var prop = property || 'padding';
        
        // Mapeo de nombres español a dirección CSS
        var map = {
            superior: prop + '-top',
            derecha: prop + '-right',
            inferior: prop + '-bottom',
            izquierda: prop + '-left'
        };
        
        Object.keys(map).forEach(function(key) {
            if (spacingConfig[key]) {
                styles[map[key]] = normalizeSize(spacingConfig[key]);
            }
        });
        
        return styles;
    }

    /**
     * Aplica cambios de spacing al DOM en tiempo real.
     * Maneja paths anidados como 'padding.superior', 'margin.izquierda', etc.
     * 
     * @param {HTMLElement} element - Elemento DOM a modificar
     * @param {string} type - 'padding' o 'margin'
     * @param {string} direction - Dirección (superior, derecha, inferior, izquierda)
     * @param {string|number} value - Valor a aplicar
     * @returns {boolean} - true si se manejó la propiedad
     */
    function applySpacing(element, type, direction, value) {
        var normalizedValue = normalizeSize(value) || '';
        var styleProp = '';
        
        // Mapeo de dirección a propiedad CSS
        switch (direction) {
            case 'superior':
                styleProp = type === 'padding' ? 'paddingTop' : 'marginTop';
                break;
            case 'derecha':
                styleProp = type === 'padding' ? 'paddingRight' : 'marginRight';
                break;
            case 'inferior':
                styleProp = type === 'padding' ? 'paddingBottom' : 'marginBottom';
                break;
            case 'izquierda':
                styleProp = type === 'padding' ? 'paddingLeft' : 'marginLeft';
                break;
            default:
                return false;
        }
        
        element.style[styleProp] = normalizedValue;
        return true;
    }

    // ============================================================
    // TRAIT: BORDER
    // Centraliza lógica de borde (nuevo trait)
    // ============================================================

    /**
     * Genera estilos CSS de borde desde configuración.
     * 
     * @param {Object} config - Configuración del bloque
     * @returns {Object} - Objeto con propiedades CSS de borde
     */
    function getBorderStyles(config) {
        var styles = {};
        if (!config) return styles;
        
        // Si el borde está explícitamente desactivado
        if (config.hasBorder === false) {
            styles['border-style'] = 'none';
            styles['border-width'] = '0';
            return styles;
        }
        
        if (config.borderWidth) {
            styles['border-width'] = normalizeSize(config.borderWidth);
        }
        if (config.borderStyle) {
            styles['border-style'] = config.borderStyle;
        }
        if (config.borderColor) {
            styles['border-color'] = config.borderColor;
        }
        if (config.borderRadius) {
            styles['border-radius'] = normalizeSize(config.borderRadius);
        }
        
        return styles;
    }

    /**
     * Aplica cambios de borde al DOM en tiempo real.
     * 
     * @param {HTMLElement} element - Elemento DOM a modificar
     * @param {string} property - Propiedad de borde (borderWidth, borderStyle, etc.)
     * @param {string|number} value - Valor a aplicar
     * @returns {boolean} - true si se manejó la propiedad
     */
    function applyBorder(element, property, value) {
        switch (property) {
            case 'hasBorder':
                if (value === false) {
                    element.style.borderStyle = 'none';
                    element.style.borderWidth = '0';
                    element.style.borderColor = '';
                    element.style.borderRadius = '';
                } else {
                    // Al activar, asegurar un estilo visible por defecto si no tiene
                    if (!element.style.borderStyle || element.style.borderStyle === 'none') {
                        element.style.borderStyle = 'solid';
                    }
                    if (!element.style.borderWidth || element.style.borderWidth === '0px') {
                         // No forzar ancho, dejar que el usuario lo ponga o que tome default
                    }
                }
                return true;
            case 'borderWidth':
                element.style.borderWidth = normalizeSize(value) || '';
                return true;
            case 'borderStyle':
                element.style.borderStyle = value || '';
                return true;
            case 'borderColor':
                element.style.borderColor = value || '';
                return true;
            case 'borderRadius':
                element.style.borderRadius = normalizeSize(value) || '';
                return true;
            default:
                return false;
        }
    }

    // ============================================================
    // TRAIT: BACKGROUND
    // Manejo de fondos e imágenes
    // ============================================================

    /**
     * Genera estilos CSS de fondo desde configuración.
     * 
     * @param {Object} config - Configuración del bloque
     * @returns {Object} - Objeto con propiedades CSS de fondo
     */
    function getBackgroundStyles(config) {
        var styles = {};
        if (!config) return styles;
        
        if (config.background) {
            styles['background'] = config.background;
        }
        if (config.backgroundColor) {
            styles['background-color'] = config.backgroundColor;
        }
        if (config.backgroundImage) {
            styles['background-image'] = 'url("' + config.backgroundImage + '")';
        }
        if (config.backgroundSize) {
            styles['background-size'] = config.backgroundSize;
        }
        if (config.backgroundPosition) {
            styles['background-position'] = config.backgroundPosition;
        }
        if (config.backgroundRepeat) {
            styles['background-repeat'] = config.backgroundRepeat;
        }
        if (config.backgroundAttachment) {
            styles['background-attachment'] = config.backgroundAttachment;
        }
        
        return styles;
    }

    /**
     * Aplica cambios de fondo al DOM en tiempo real.
     * 
     * @param {HTMLElement} element - Elemento DOM a modificar
     * @param {string} property - Propiedad de fondo
     * @param {string} value - Valor a aplicar
     * @returns {boolean} - true si se manejó la propiedad
     */
    function applyBackground(element, property, value) {
        switch (property) {
            case 'background':
            case 'backgroundColor':
                element.style.background = value || '';
                return true;
            case 'backgroundImage':
                element.style.backgroundImage = value ? 'url("' + value + '")' : '';
                return true;
            case 'backgroundSize':
                element.style.backgroundSize = value || '';
                return true;
            case 'backgroundPosition':
                element.style.backgroundPosition = value || '';
                return true;
            case 'backgroundRepeat':
                element.style.backgroundRepeat = value || '';
                return true;
            case 'backgroundAttachment':
                element.style.backgroundAttachment = value || '';
                return true;
            default:
                return false;
        }
    }

    // ============================================================
    // HANDLER UNIVERSAL
    // Para paths anidados comunes (typography.*, padding.*, etc.)
    // ============================================================

    /**
     * Manejador universal de updates para paths comunes.
     * Delega a los traits específicos según el path recibido.
     * 
     * USO: En tu renderer, en lugar de duplicar todo el código de handleUpdate,
     * puedes llamar a este handler primero y solo manejar manualmente las
     * propiedades específicas de tu componente.
     * 
     * @param {HTMLElement} element - Elemento DOM a modificar
     * @param {string} path - Path de la propiedad (ej: 'typography.size', 'padding.superior')
     * @param {*} value - Valor a aplicar
     * @returns {boolean} - true si se manejó la propiedad, false si no
     * 
     * @example
     * function handleUpdate(block, path, value) {
     *     // Primero intentar con traits comunes
     *     if (traits.handleCommonUpdate(block.element, path, value)) {
     *         return true;
     *     }
     *     // Luego manejar propiedades específicas del componente
     *     if (path === 'miPropiedadEspecifica') {
     *         // ...
     *         return true;
     *     }
     *     return false;
     * }
     */
    function handleCommonUpdate(element, path, value) {
        if (!element) return false;
        
        // Typography (typography.*)
        if (path.indexOf('typography.') === 0) {
            var typoProp = path.replace('typography.', '');
            return applyTypography(element, typoProp, value);
        }
        
        // Padding (padding.*)
        if (path.indexOf('padding.') === 0) {
            var paddingDir = path.replace('padding.', '');
            return applySpacing(element, 'padding', paddingDir, value);
        }
        
        // Margin (margin.*)
        if (path.indexOf('margin.') === 0) {
            var marginDir = path.replace('margin.', '');
            return applySpacing(element, 'margin', marginDir, value);
        }
        
        // Border (borderWidth, borderStyle, borderColor, borderRadius, hasBorder)
        if (path.indexOf('border') === 0 || path === 'hasBorder') {
            return applyBorder(element, path, value);
        }
        
        // Background (backgroundColor, backgroundImage, etc.)
        if (path.indexOf('background') === 0 || path === 'fondo') {
            return applyBackground(element, path, value);
        }
        
        // Propiedades simples de estilo directo
        var directStyleProps = ['color', 'display', 'width', 'height', 'textAlign', 
                                'cursor', 'transition', 'transform', 'textShadow',
                                'position', 'zIndex', 'overflow'];
        
        if (directStyleProps.indexOf(path) !== -1) {
            // Convertir camelCase a propiedad de estilo (JS usa camelCase directamente)
            element.style[path] = value || '';
            return true;
        }
        
        return false;
    }

    /**
     * Genera estilos comunes a partir de una configuración.
     * Combina todos los traits para generar un objeto de estilos CSS completo.
     * 
     * @param {Object} config - Configuración del bloque
     * @returns {Object} - Objeto con todos los estilos CSS generados
     */
    function getCommonStyles(config) {
        var styles = {};
        if (!config) return styles;
        
        // Typography
        Object.assign(styles, getTypographyStyles(config.typography));
        
        // Spacing
        Object.assign(styles, getSpacingStyles(config.padding, 'padding'));
        Object.assign(styles, getSpacingStyles(config.margin, 'margin'));
        
        // Border
        Object.assign(styles, getBorderStyles(config));
        
        // Background
        Object.assign(styles, getBackgroundStyles(config));
        
        // Propiedades directas comunes
        if (config.color) styles['color'] = config.color;
        if (config.display) styles['display'] = config.display;
        if (config.width && config.width !== 'auto') styles['width'] = config.width;
        if (config.height && config.height !== 'auto') styles['height'] = config.height;
        if (config.textAlign) styles['text-align'] = config.textAlign;
        if (config.textShadow) styles['text-shadow'] = config.textShadow;
        if (config.cursor && config.cursor !== 'default') styles['cursor'] = config.cursor;
        if (config.transition) styles['transition'] = config.transition;
        if (config.transform) styles['transform'] = config.transform;
        
        return styles;
    }

    // ============================================================
    // FACTORY HELPER
    // Simplifica la creación de renderers
    // ============================================================

    /**
     * Crea un renderer básico que usa los traits comunes.
     * Para componentes simples que no necesitan lógica especial.
     * 
     * @param {Object} options - Opciones del renderer
     * @param {Function} [options.getExtraStyles] - Función para estilos adicionales
     * @param {Function} [options.handleSpecialUpdate] - Función para updates especiales
     * @returns {Object} - Objeto renderer con getStyles y handleUpdate
     * 
     * @example
     * // Crear un renderer simple
     * Gbn.ui.renderers.miComponente = traits.createRenderer({
     *     getExtraStyles: function(config) {
     *         return { 'mi-propiedad': config.miValor };
     *     },
     *     handleSpecialUpdate: function(block, path, value) {
     *         if (path === 'miPropiedad') {
     *             block.element.dataset.valor = value;
     *             return true;
     *         }
     *         return false;
     *     }
     * });
     */
    function createRenderer(options) {
        options = options || {};
        
        return {
            getStyles: function(config, block) {
                var styles = getCommonStyles(config);
                
                if (options.getExtraStyles) {
                    var extraStyles = options.getExtraStyles(config, block);
                    Object.assign(styles, extraStyles);
                }
                
                return styles;
            },
            
            handleUpdate: function(block, path, value) {
                if (!block || !block.element) return false;
                
                // Primero intentar propiedades especiales del componente
                if (options.handleSpecialUpdate) {
                    if (options.handleSpecialUpdate(block, path, value)) {
                        return true;
                    }
                }
                
                // Luego usar handler común
                return handleCommonUpdate(block.element, path, value);
            }
        };
    }

    // ============================================================
    // EXPORTAR API PÚBLICA
    // ============================================================

    Gbn.ui.renderers.traits = {
        // Helpers
        normalizeSize: normalizeSize,
        toKebabCase: toKebabCase,
        
        // Typography Trait
        getTypographyStyles: getTypographyStyles,
        applyTypography: applyTypography,
        
        // Spacing Trait
        getSpacingStyles: getSpacingStyles,
        applySpacing: applySpacing,
        
        // Border Trait
        getBorderStyles: getBorderStyles,
        applyBorder: applyBorder,
        
        // Background Trait
        getBackgroundStyles: getBackgroundStyles,
        applyBackground: applyBackground,
        
        // Universal Handlers
        handleCommonUpdate: handleCommonUpdate,
        getCommonStyles: getCommonStyles,
        
        // Factory
        createRenderer: createRenderer
    };

})(typeof window !== 'undefined' ? window : this);
