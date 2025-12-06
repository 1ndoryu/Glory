/**
 * IconRegistry - Registro centralizado de iconos SVG para GBN Builder
 * 
 * Uso:
 *   import { Icons } from './icons/index.js';
 *   const gridIcon = Icons.get('layout.grid');
 */

import { layoutIcons } from './layout-icons.js';
import { actionIcons } from './action-icons.js';
import { stateIcons } from './state-icons.js';
import { tabIcons } from './tab-icons.js';

export const Icons = {
    _registry: {
        ...layoutIcons,
        ...actionIcons,
        ...stateIcons,
        ...tabIcons
    },

    /**
     * Obtiene un icono por su clave
     * @param {string} key - Clave del icono (ej: 'layout.grid')
     * @param {Object} attrs - Atributos opcionales
     * @returns {string} SVG del icono
     */
    get(key, attrs = {}) {
        let icon = this._registry[key];
        
        if (!icon) {
            console.warn(`IconRegistry: Icono no encontrado: ${key}`);
            return this._fallback();
        }

        // Sobrescribir atributos si se proporcionan
        if (Object.keys(attrs).length > 0) {
            Object.entries(attrs).forEach(([attr, value]) => {
                // Si el atributo ya existe
                const regex = new RegExp(`${attr}="[^"]*"`, 'g');
                if (regex.test(icon)) {
                    icon = icon.replace(regex, `${attr}="${value}"`);
                } else {
                    // Si no existe, agregarlo al inicio del tag
                    icon = icon.replace('<svg', `<svg ${attr}="${value}"`);
                }
            });
        }

        return icon;
    },

    /**
     * Obtiene múltiples iconos como array de opciones
     * Útil par iconGroup
     */
    getOptions(keys) {
        return keys.map(key => ({
            value: key.split('.').pop(),
            icon: this.get(key)
        }));
    },

    /**
     * Helper para inyectar en window si es necesario
     */
    init() {
        if (typeof window !== 'undefined') {
            window.GbnIcons = this;
        }
    },

    _fallback() {
        return '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg>';
    }
};

// Auto-inicializar si estamos en navegador
Icons.init();
