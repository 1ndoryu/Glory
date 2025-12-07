(function (global) {
    'use strict';

    /**
     * TEXT RENDERER - Refactorizado Fase 11
     *
     * Este renderer ahora usa los traits centralizados para tipografía, spacing, etc.
     * Solo maneja lógica específica del componente Text.
     *
     * @module Gbn.ui.renderers.text
     */

    var Gbn = (global.Gbn = global.Gbn || {});
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.renderers = Gbn.ui.renderers || {};

    // Referencia a traits para funciones compartidas
    var traits = Gbn.ui.renderers.traits;

    /**
     * BUG-020 FIX: Detecta si un elemento tiene hijos con atributos GBN (glory* o data-gbn-*)
     * Excluye los controles del editor (.gbn-controls-group) de la deteccion
     *
     * @param {HTMLElement} element - Elemento a verificar
     * @returns {boolean} - true si tiene hijos GBN, false en caso contrario
     */
    function hasNestedGbnElements(element) {
        if (!element) return false;

        // Buscar hijos directos o descendientes con atributos glory* o data-gbn-id
        // Excluimos .gbn-controls-group que son controles del editor
        var children = element.children;
        for (var i = 0; i < children.length; i++) {
            var child = children[i];

            // Ignorar controles del editor
            if (child.classList.contains('gbn-controls-group')) {
                continue;
            }

            // Verificar si el hijo tiene atributos GBN
            var attrs = child.attributes;
            for (var j = 0; j < attrs.length; j++) {
                var attrName = attrs[j].name.toLowerCase();
                // Detectar atributos glory* (gloryTexto, gloryDiv, etc.) o data-gbn-id
                if (attrName.startsWith('glory') || attrName === 'data-gbn-id') {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Genera los estilos CSS para un bloque de texto basado en su configuración.
     * Usa traits.getCommonStyles() para propiedades compartidas y agrega específicas de texto.
     */
    function getStyles(config, block) {
        // Obtener estilos comunes via traits
        var styles = traits.getCommonStyles(config);

        // Propiedades específicas de texto que no están en traits
        if (config.alineacion) {
            styles['text-align'] = config.alineacion;
        }

        // Text Shadow (para efectos como .textGlow)
        if (config.textShadow) {
            styles['text-shadow'] = config.textShadow;
        }

        // Tamaño legacy (mantener compatibilidad - prioriza sobre typography.size)
        if (config.size) {
            styles['font-size'] = traits.normalizeSize(config.size);
        }

        return styles;
    }

    /**
     * Maneja actualizaciones de configuración en tiempo real.
     * Aplica cambios directamente al DOM para feedback instantáneo.
     *
     * Fase 11: Delega propiedades comunes a traits.handleCommonUpdate()
     */
    function handleUpdate(block, path, value) {
        if (!block || !block.element) return false;
        var el = block.element;

        // === PROPIEDADES ESPECÍFICAS DE TEXTO ===
        // Estas propiedades son únicas del componente Text y no están en traits

        // Cambio de etiqueta HTML (tag)
        if (path === 'tag') {
            var oldEl = el;
            var newTag = value || 'p';
            if (oldEl.tagName.toLowerCase() !== newTag.toLowerCase()) {
                var newEl = document.createElement(newTag);
                // Copiar todos los atributos
                Array.from(oldEl.attributes).forEach(function (attr) {
                    newEl.setAttribute(attr.name, attr.value);
                });
                newEl.innerHTML = oldEl.innerHTML;
                if (oldEl.parentNode) {
                    oldEl.parentNode.replaceChild(newEl, oldEl);
                    block.element = newEl;
                }
            }
            return true;
        }

        // Actualización de contenido HTML
        if (path === 'texto') {
            // BUG-020 FIX: Detectar si el elemento tiene hijos GBN antes de sobrescribir
            // Si tiene hijos con atributos glory* o data-gbn-*, NO sobrescribir para evitar
            // perdida de estructura anidada
            var hasGbnChildren = hasNestedGbnElements(el);

            if (hasGbnChildren) {
                // No sobrescribir innerHTML cuando hay hijos GBN
                // Esto previene la perdida de estructura como:
                // <div gloryTexto><h2 gloryTexto>...</h2><p gloryTexto>...</p></div>
                console.warn('[GBN] Advertencia: El elemento tiene hijos GBN anidados. ' + 'La edicion de texto esta deshabilitada para este contenedor. ' + 'Edita los elementos hijos directamente.', el);
                return false;
            }

            // Preservar controles GBN si existen
            var controls = el.querySelector('.gbn-controls-group');
            el.innerHTML = value;
            if (controls) el.appendChild(controls);
            return true;
        }

        // Alineación (usa nombre personalizado 'alineacion')
        if (path === 'alineacion') {
            el.style.textAlign = value || '';
            return true;
        }

        // Text Shadow específico
        if (path === 'textShadow') {
            el.style.textShadow = value || '';
            return true;
        }

        // Tamaño legacy
        if (path === 'size') {
            el.style.fontSize = traits.normalizeSize(value) || '';
            return true;
        }

        // === DELEGAR A TRAITS COMUNES ===
        // Typography, Spacing, Border, Background - manejados por traits
        return traits.handleCommonUpdate(el, path, value);
    }

    // Exportar renderer
    Gbn.ui.renderers.text = {
        getStyles: getStyles,
        handleUpdate: handleUpdate
    };
})(typeof window !== 'undefined' ? window : this);
