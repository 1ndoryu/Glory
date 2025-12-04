;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    var utils = function() { return Gbn.ui.fieldUtils; };

    /**
     * Construye un campo de imagen (URL + Preview)
     */
    function buildImageField(block, field) {
        var u = utils();
        var wrapper = document.createElement('div');
        wrapper.className = 'gbn-field';
        
        var label = document.createElement('label');
        label.className = 'gbn-field-label';
        label.textContent = field.etiqueta || field.id;
        wrapper.appendChild(label);
        
        var container = document.createElement('div');
        container.style.display = 'flex';
        container.style.flexDirection = 'column';
        container.style.gap = '10px';

        // Input URL
        var input = document.createElement('input');
        input.type = 'text';
        input.className = 'gbn-input';
        input.placeholder = 'https://...';
        
        // Preview
        var preview = document.createElement('img');
        preview.style.maxWidth = '100%';
        preview.style.height = 'auto';
        preview.style.borderRadius = '4px';
        preview.style.border = '1px solid #333';
        preview.style.display = 'none';
        preview.style.marginTop = '5px';

        var current = u.getConfigValue(block, field.id);
        var themeDefault = u.getThemeDefault(block.role, field.id);
        
        if (current === undefined || current === null) {
            wrapper.classList.add('gbn-field-inherited');
            if (themeDefault !== undefined && themeDefault !== null) {
                input.placeholder = themeDefault;
                preview.src = themeDefault;
                preview.style.display = 'block';
            } else {
                input.placeholder = field.defecto || '';
            }
        } else {
            wrapper.classList.add('gbn-field-override');
            input.value = current;
            preview.src = current;
            preview.style.display = 'block';
        }
        
        input.dataset.role = block.role;
        input.dataset.prop = field.id;
        
        input.addEventListener('input', function () {
            var value = input.value.trim();
            
            if (value === '') {
                wrapper.classList.add('gbn-field-inherited');
                wrapper.classList.remove('gbn-field-override');
                preview.style.display = 'none';
            } else {
                wrapper.classList.remove('gbn-field-inherited');
                wrapper.classList.add('gbn-field-override');
                preview.src = value;
                preview.style.display = 'block';
            }
            
            var api = Gbn.ui && Gbn.ui.panelApi;
            if (api && api.updateConfigValue && block) {
                api.updateConfigValue(block, field.id, value === '' ? null : value);
            }
        });
        
        container.appendChild(input);
        container.appendChild(preview);
        wrapper.appendChild(container);
        u.appendFieldDescription(wrapper, field);
        
        return wrapper;
    }

    // Exportar
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.imageField = { build: buildImageField };

    if (Gbn.ui.panelFields && Gbn.ui.panelFields.registry) {
        Gbn.ui.panelFields.registry.register('image', buildImageField);
    }

})(window);
