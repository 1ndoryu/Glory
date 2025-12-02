;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    var utils = function() { return Gbn.ui.fieldUtils; };

    /**
     * Construye un editor de texto enriquecido básico
     */
    function buildRichTextField(block, field) {
        var u = utils();
        var wrapper = document.createElement('div');
        wrapper.className = 'gbn-field gbn-field-rich-text';
        
        var label = document.createElement('label');
        label.className = 'gbn-field-label';
        label.textContent = field.etiqueta || field.id;
        wrapper.appendChild(label);
        
        var container = document.createElement('div');
        container.className = 'gbn-rich-text-container';
        
        // Toolbar
        var toolbar = document.createElement('div');
        toolbar.className = 'gbn-rich-text-toolbar';
        
        var actions = [
            { cmd: 'bold', icon: '<b>B</b>', title: 'Negrita' },
            { cmd: 'italic', icon: '<i>I</i>', title: 'Cursiva' }
        ];
        
        // Editor
        var editor = document.createElement('div');
        editor.className = 'gbn-rich-text-editor';
        editor.contentEditable = true;
        
        actions.forEach(function(action) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'gbn-rich-text-btn';
            btn.innerHTML = action.icon;
            btn.title = action.title;
            btn.addEventListener('click', function() {
                document.execCommand(action.cmd, false, null);
                var content = editor.innerHTML;
                var api = Gbn.ui && Gbn.ui.panelApi;
                if (api && api.updateConfigValue && block) {
                    api.updateConfigValue(block, field.id, content);
                }
            });
            toolbar.appendChild(btn);
        });
        
        container.appendChild(toolbar);
        
        var current = u.getConfigValue(block, field.id);
        if (current === undefined || current === null) {
            current = field.defecto || '';
        }
        editor.innerHTML = current;
        
        editor.addEventListener('input', function() {
            var content = editor.innerHTML;
            var api = Gbn.ui && Gbn.ui.panelApi;
            if (api && api.updateConfigValue && block) {
                api.updateConfigValue(block, field.id, content);
            }
        });
        
        container.appendChild(editor);
        wrapper.appendChild(container);
        u.appendFieldDescription(wrapper, field);
        
        return wrapper;
    }

    // Exportar
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.richTextField = { build: buildRichTextField };

})(window);


