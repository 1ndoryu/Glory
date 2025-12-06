;(function (global) {
    'use strict';

    /**
     * SELECT RENDERER - Selector Desplegable
     * 
     * Maneja campos select con opciones dinámicas.
     * 
     * @module Gbn.ui.renderers.select
     */

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.renderers = Gbn.ui.renderers || {};

    var traits = Gbn.ui.renderers.traits;

    /**
     * Genera los estilos inline para el contenedor del select.
     */
    function getStyles(config, block) {
        var styles = traits.getCommonStyles(config);
        return styles;
    }

    /**
     * Encuentra el elemento select dentro del contenedor.
     */
    function findSelect(container) {
        return container.querySelector('select') || container;
    }

    /**
     * Encuentra el elemento label dentro del contenedor.
     */
    function findLabel(container) {
        return container.querySelector('label');
    }

    /**
     * Parsea las opciones desde el formato "valor:etiqueta" (una por línea).
     */
    function parseOptions(optionsString) {
        if (!optionsString) return [];
        
        return optionsString.split('\n')
            .map(function(line) {
                line = line.trim();
                if (!line) return null;
                
                var parts = line.split(':');
                var valor = parts[0] ? parts[0].trim() : '';
                var etiqueta = parts[1] ? parts[1].trim() : valor;
                
                return { valor: valor, etiqueta: etiqueta };
            })
            .filter(Boolean);
    }

    /**
     * Actualiza las opciones del select.
     */
    function updateSelectOptions(select, optionsString, placeholder) {
        var options = parseOptions(optionsString);
        
        // Limpiar opciones existentes
        select.innerHTML = '';
        
        // Agregar placeholder
        if (placeholder) {
            var placeholderOption = document.createElement('option');
            placeholderOption.value = '';
            placeholderOption.disabled = true;
            placeholderOption.selected = true;
            placeholderOption.textContent = placeholder;
            select.appendChild(placeholderOption);
        }
        
        // Agregar opciones
        options.forEach(function(opt) {
            var option = document.createElement('option');
            option.value = opt.valor;
            option.textContent = opt.etiqueta;
            select.appendChild(option);
        });
    }

    /**
     * Maneja actualizaciones en tiempo real.
     */
    function handleUpdate(block, path, value) {
        if (!block || !block.element) return false;
        var el = block.element;
        var select = findSelect(el);
        var label = findLabel(el);

        // Propiedades específicas del select
        if (path === 'name') {
            select.name = value || '';
            if (label) {
                label.setAttribute('for', value || '');
            }
            return true;
        }

        if (path === 'label') {
            if (label) {
                label.textContent = value || '';
            }
            return true;
        }

        if (path === 'placeholder') {
            var currentOptions = block.config ? block.config.options : '';
            updateSelectOptions(select, currentOptions, value);
            return true;
        }

        if (path === 'options') {
            var currentPlaceholder = block.config ? block.config.placeholder : 'Seleccione una opción';
            updateSelectOptions(select, value, currentPlaceholder);
            return true;
        }

        if (path === 'required') {
            if (value) {
                select.setAttribute('required', '');
            } else {
                select.removeAttribute('required');
            }
            return true;
        }

        // Estilos que aplican al select específicamente
        if (path === 'color' || path === 'backgroundColor') {
            var cssProp = path === 'backgroundColor' ? 'background-color' : 'color';
            select.style[cssProp] = value || '';
            return true;
        }

        // Delegar a traits comunes
        return traits.handleCommonUpdate(el, path, value);
    }

    // Exportar el renderer
    Gbn.ui.renderers.select = {
        getStyles: getStyles,
        handleUpdate: handleUpdate
    };

})(typeof window !== 'undefined' ? window : this);
