;(function (global) {
    'use strict';

    /**
     * POST FIELD RENDERER
     * 
     * Renderer para componentes PostField (campos semánticos).
     * Estos campos se llenan automáticamente con datos del post en el renderizado PHP.
     * En el editor, muestran placeholders editables.
     * 
     * @module Gbn.ui.renderers.postField
     */

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.renderers = Gbn.ui.renderers || {};

    // Referencia a traits para funciones compartidas
    var traits = Gbn.ui.renderers.traits;

    /**
     * Placeholders por tipo de campo.
     * Se muestran en el editor cuando el campo no tiene contenido real.
     */
    var PLACEHOLDERS = {
        'title': 'Título del Post',
        'featuredImage': '[Imagen Destacada]',
        'excerpt': 'Extracto del contenido aparecerá aquí...',
        'content': 'Contenido completo del post...',
        'date': 'DD MMM, YYYY',
        'author': 'Nombre del Autor',
        'authorAvatar': '[Avatar]',
        'link': 'Leer más',
        'categories': 'Categoría 1, Categoría 2',
        'tags': 'Tag 1, Tag 2',
        'commentCount': '0 comentarios',
        'meta': '[Meta Field]',
        'acf': '[Campo ACF]',
        'taxonomy': '[Taxonomía]'
    };

    /**
     * Genera estilos CSS para el campo.
     * Los campos semánticos generalmente no tienen estilos propios,
     * se estilizan mediante clases CSS.
     * 
     * @param {Object} config Configuración del bloque
     * @param {Object} block Referencia al bloque
     * @returns {Object} Estilos CSS como objeto
     */
    function getStyles(config, block) {
        // Los PostField heredan estilos del elemento padre (PostItem)
        // Por defecto no aplicamos estilos inline
        return {};
    }

    /**
     * Maneja actualizaciones en tiempo real del componente.
     * 
     * @param {Object} block Referencia al bloque
     * @param {string} path Path de la propiedad modificada
     * @param {*} value Nuevo valor
     * @returns {boolean} true si se manejó la actualización
     */
    function handleUpdate(block, path, value) {
        if (!block || !block.element) return false;
        var el = block.element;

        // === TIPO DE CAMPO ===
        
        if (path === 'fieldType') {
            // Actualizar el atributo gloryPostField
            el.setAttribute('gloryPostField', value);
            
            // Actualizar placeholder si está vacío
            updatePlaceholder(block, value);
            
            // Actualizar ícono/indicador en el editor
            updateFieldIndicator(block, value);
            
            return true;
        }

        // === OPCIONES ESPECÍFICAS POR TIPO ===
        
        if (path === 'linkText') {
            // Solo aplica para tipo 'link'
            if (block.config.fieldType === 'link') {
                el.textContent = value || PLACEHOLDERS.link;
            }
            return true;
        }

        if (path === 'dateFormat') {
            // Solo visual, el formato real se aplica en PHP
            // Mostrar ejemplo con el formato
            if (block.config.fieldType === 'date') {
                el.textContent = formatDateExample(value);
            }
            return true;
        }

        if (path === 'wordLimit') {
            // Solo aplica para 'excerpt', regenerar preview
            if (block.config.fieldType === 'excerpt') {
                var words = PLACEHOLDERS.excerpt.split(' ').slice(0, value || 20);
                el.textContent = words.join(' ') + '...';
            }
            return true;
        }

        if (path === 'imageSize') {
            // Solo aplica para 'featuredImage', mostrar indicador
            if (block.config.fieldType === 'featuredImage') {
                el.dataset.imageSize = value || 'medium';
            }
            return true;
        }

        if (path === 'avatarSize') {
            // Solo aplica para 'authorAvatar'
            if (block.config.fieldType === 'authorAvatar') {
                el.style.width = value + 'px';
                el.style.height = value + 'px';
            }
            return true;
        }

        if (path === 'metaKey') {
            // Para tipo 'meta', mostrar el nombre del campo
            if (block.config.fieldType === 'meta') {
                el.textContent = value ? '[Meta: ' + value + ']' : PLACEHOLDERS.meta;
            }
            return true;
        }

        if (path === 'acfField') {
            // Para tipo 'acf', mostrar el nombre del campo
            if (block.config.fieldType === 'acf') {
                el.textContent = value ? '[ACF: ' + value + ']' : PLACEHOLDERS.acf;
            }
            return true;
        }

        if (path === 'tag') {
            // Cambiar la etiqueta HTML del elemento
            // Esto requiere reemplazar el elemento en el DOM
            if (value && value !== el.tagName.toLowerCase()) {
                replaceElementTag(block, value);
            }
            return true;
        }

        // === DELEGAR A TRAITS COMUNES ===
        return traits.handleCommonUpdate(el, path, value);
    }

    /**
     * Actualiza el placeholder del campo según el tipo.
     * 
     * @param {Object} block Referencia al bloque
     * @param {string} fieldType Tipo de campo
     */
    function updatePlaceholder(block, fieldType) {
        var el = block.element;
        var placeholder = PLACEHOLDERS[fieldType] || '[' + fieldType + ']';

        // Solo actualizar si está vacío o es un placeholder anterior
        var currentText = el.textContent.trim();
        var isPlaceholder = Object.values(PLACEHOLDERS).some(function(p) {
            return currentText === p || currentText === '';
        });

        if (isPlaceholder || currentText.startsWith('[')) {
            // Para imágenes, manejar de forma especial
            if (fieldType === 'featuredImage' || fieldType === 'authorAvatar') {
                el.textContent = '';
                el.style.background = '#f0f0f0';
                el.dataset.placeholder = placeholder;
            } else {
                el.textContent = placeholder;
            }
        }
    }

    /**
     * Actualiza el indicador visual del tipo de campo en el editor.
     * 
     * @param {Object} block Referencia al bloque
     * @param {string} fieldType Tipo de campo
     */
    function updateFieldIndicator(block, fieldType) {
        var el = block.element;
        
        // Agregar data attribute para CSS styling en el editor
        el.dataset.gbnFieldType = fieldType;
    }

    /**
     * Genera un ejemplo de fecha con el formato especificado.
     * 
     * @param {string} format Formato PHP de fecha
     * @returns {string} Ejemplo de fecha formateada
     */
    function formatDateExample(format) {
        // Mapeo básico de formato PHP a ejemplo visual
        var examples = {
            'd M, Y': '15 Dic, 2024',
            'd/m/Y': '15/12/2024',
            'F j, Y': 'Diciembre 15, 2024',
            'Y-m-d': '2024-12-15',
            'd-m-Y': '15-12-2024',
            'M j': 'Dic 15',
            'j M Y': '15 Dic 2024'
        };

        return examples[format] || '15 Dic, 2024';
    }

    /**
     * Reemplaza la etiqueta HTML del elemento manteniendo atributos y contenido.
     * 
     * @param {Object} block Referencia al bloque
     * @param {string} newTag Nueva etiqueta HTML
     */
    function replaceElementTag(block, newTag) {
        var oldEl = block.element;
        var newEl = document.createElement(newTag);

        // Copiar atributos
        Array.from(oldEl.attributes).forEach(function(attr) {
            newEl.setAttribute(attr.name, attr.value);
        });

        // Copiar contenido
        newEl.innerHTML = oldEl.innerHTML;

        // Reemplazar en el DOM
        oldEl.parentNode.replaceChild(newEl, oldEl);

        // Actualizar referencia en el bloque
        block.element = newEl;

        // Re-registrar en el store si es necesario
        if (Gbn.store && Gbn.store.updateBlockElement) {
            Gbn.store.updateBlockElement(block.id, newEl);
        }
    }

    /**
     * Inicializa el componente PostField.
     * 
     * @param {Object} block Referencia al bloque
     */
    function init(block) {
        if (!block || !block.element) return;

        var config = block.config || {};
        var el = block.element;

        // Obtener tipo de campo del atributo o config
        var fieldType = el.getAttribute('gloryPostField') || config.fieldType || 'title';
        config.fieldType = fieldType;

        // Aplicar placeholder si está vacío
        if (!el.textContent.trim() && !el.querySelector('img')) {
            updatePlaceholder(block, fieldType);
        }

        // Aplicar indicador visual
        updateFieldIndicator(block, fieldType);

        // Marcar como inicializado
        el.dataset.gbnInitialized = 'true';
    }

    // Exportar renderer
    Gbn.ui.renderers.postField = {
        getStyles: getStyles,
        handleUpdate: handleUpdate,
        init: init,
        PLACEHOLDERS: PLACEHOLDERS
    };

})(typeof window !== 'undefined' ? window : this);
