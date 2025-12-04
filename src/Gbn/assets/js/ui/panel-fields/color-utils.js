;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.fieldUtils = Gbn.ui.fieldUtils || {};

    /**
     * Utilidades para manipulación de colores
     */
    var ColorUtils = {
        
        /**
         * Convierte un valor HEX y Alpha a RGBA string
         * @param {string} hex - Color en formato HEX (#RRGGBB)
         * @param {number} alpha - Valor alpha (0-1)
         * @returns {string} - Color en formato rgba(r, g, b, a)
         */
        hexToRgba: function(hex, alpha) {
            if (!hex) return null;
            
            // Normalizar hex
            hex = hex.replace('#', '');
            if (hex.length === 3) {
                hex = hex.split('').map(function(c) { return c + c; }).join('');
            }
            
            var r = parseInt(hex.substring(0, 2), 16);
            var g = parseInt(hex.substring(2, 4), 16);
            var b = parseInt(hex.substring(4, 6), 16);
            
            // Asegurar que alpha sea número y esté en rango 0-1
            alpha = parseFloat(alpha);
            if (isNaN(alpha)) alpha = 1;
            if (alpha > 1) alpha = 1;
            if (alpha < 0) alpha = 0;
            
            // Redondear alpha a 2 decimales para limpieza
            alpha = Math.round(alpha * 100) / 100;
            
            return 'rgba(' + r + ', ' + g + ', ' + b + ', ' + alpha + ')';
        },

        /**
         * Convierte RGBA a HEX de 8 dígitos (#RRGGBBAA)
         * @param {string} rgba - Color en formato rgba()
         * @returns {string} - Color en formato #RRGGBBAA
         */
        rgbaToHex8: function(rgba) {
            var parts = this.parseColor(rgba);
            if (!parts) return null;
            
            var r = parts.r.toString(16).padStart(2, '0');
            var g = parts.g.toString(16).padStart(2, '0');
            var b = parts.b.toString(16).padStart(2, '0');
            var a = Math.round(parts.a * 255).toString(16).padStart(2, '0');
            
            return '#' + r + g + b + a;
        },
        
        /**
         * Parsea cualquier string de color a objeto {r, g, b, a}
         * @param {string} input - String de color (hex, rgb, rgba)
         * @returns {object|null} - Objeto {r,g,b,a} o null si es inválido
         */
        parseColor: function(input) {
            if (!input) return null;
            if (typeof input !== 'string') return null;
            
            input = input.trim().toLowerCase();
            
            if (input === 'transparent') return { r: 0, g: 0, b: 0, a: 0 };
            
            // HEX 6 (#RRGGBB)
            if (input.match(/^#[0-9a-f]{6}$/)) {
                return {
                    r: parseInt(input.substring(1, 3), 16),
                    g: parseInt(input.substring(3, 5), 16),
                    b: parseInt(input.substring(5, 7), 16),
                    a: 1
                };
            }
            
            // HEX 3 (#RGB)
            if (input.match(/^#[0-9a-f]{3}$/)) {
                return {
                    r: parseInt(input[1] + input[1], 16),
                    g: parseInt(input[2] + input[2], 16),
                    b: parseInt(input[3] + input[3], 16),
                    a: 1
                };
            }
            
            // HEX 8 (#RRGGBBAA)
            if (input.match(/^#[0-9a-f]{8}$/)) {
                return {
                    r: parseInt(input.substring(1, 3), 16),
                    g: parseInt(input.substring(3, 5), 16),
                    b: parseInt(input.substring(5, 7), 16),
                    a: parseInt(input.substring(7, 9), 16) / 255
                };
            }
            
            // RGB / RGBA
            var match = input.match(/^rgba?\s*\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)(?:\s*,\s*([0-9.]+))?\s*\)$/);
            if (match) {
                return {
                    r: parseInt(match[1], 10),
                    g: parseInt(match[2], 10),
                    b: parseInt(match[3], 10),
                    a: match[4] !== undefined ? parseFloat(match[4]) : 1
                };
            }
            
            return null;
        },

        /**
         * Convierte un objeto de color a formato HEX (#RRGGBB) ignorando alpha
         */
        toHex: function(colorObj) {
            if (!colorObj) return null;
            var r = colorObj.r.toString(16).padStart(2, '0');
            var g = colorObj.g.toString(16).padStart(2, '0');
            var b = colorObj.b.toString(16).padStart(2, '0');
            return '#' + r + g + b;
        }
    };

    // Exportar
    Gbn.ui.colorUtils = ColorUtils;

})(window);
