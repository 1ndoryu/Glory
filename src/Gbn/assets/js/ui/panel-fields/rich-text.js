;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    var utils = function() { return Gbn.ui.fieldUtils; };

    /**
     * Editor de texto enriquecido con dos vistas: Visual y Code
     * 
     * Vista Visual: Edición WYSIWYG con bold/italic
     * Vista Code: Ver/editar el HTML raw (para <br/>, <span>, etc.)
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
        
        // Toolbar con toggle de vista
        var toolbar = document.createElement('div');
        toolbar.className = 'gbn-rich-text-toolbar';
        
        // Acciones de formato (solo visibles en modo visual)
        var formatActions = document.createElement('div');
        formatActions.className = 'gbn-rich-text-format-actions';
        
        var actions = [
            { cmd: 'bold', icon: '<b>B</b>', title: 'Negrita' },
            { cmd: 'italic', icon: '<i>I</i>', title: 'Cursiva' }
        ];
        
        // Editor visual (contentEditable)
        var visualEditor = document.createElement('div');
        visualEditor.className = 'gbn-rich-text-editor gbn-rich-text-visual';
        visualEditor.contentEditable = true;
        
        // Editor código (textarea)
        var codeEditor = document.createElement('textarea');
        codeEditor.className = 'gbn-rich-text-editor gbn-rich-text-code';
        codeEditor.style.display = 'none';
        codeEditor.spellcheck = false;
        
        // Estado actual de la vista
        var currentView = 'visual';
        
        // Crear botones de formato
        actions.forEach(function(action) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'gbn-rich-text-btn';
            btn.innerHTML = action.icon;
            btn.title = action.title;
            btn.addEventListener('click', function() {
                if (currentView === 'visual') {
                    // Asegurar que el editor visual tenga foco
                    visualEditor.focus();
                    document.execCommand(action.cmd, false, null);
                    syncAndNotify();
                }
            });
            formatActions.appendChild(btn);
        });
        
        toolbar.appendChild(formatActions);
        
        // Toggle de vista (Visual/Code)
        var viewToggle = document.createElement('div');
        viewToggle.className = 'gbn-rich-text-view-toggle';
        
        var visualBtn = document.createElement('button');
        visualBtn.type = 'button';
        visualBtn.className = 'gbn-view-btn active';
        visualBtn.textContent = 'Visual';
        visualBtn.title = 'Vista visual (WYSIWYG)';
        
        var codeBtn = document.createElement('button');
        codeBtn.type = 'button';
        codeBtn.className = 'gbn-view-btn';
        codeBtn.innerHTML = '&lt;/&gt;';
        codeBtn.title = 'Vista código (HTML)';
        
        viewToggle.appendChild(visualBtn);
        viewToggle.appendChild(codeBtn);
        toolbar.appendChild(viewToggle);
        
        container.appendChild(toolbar);
        
        // Función para sincronizar contenido y notificar cambios
        function syncAndNotify() {
            var content = currentView === 'visual' 
                ? visualEditor.innerHTML 
                : codeEditor.value;
            var api = Gbn.ui && Gbn.ui.panelApi;
            if (api && api.updateConfigValue && block) {
                api.updateConfigValue(block, field.id, content);
            }
        }
        
        // Cambiar a vista visual
        function switchToVisual() {
            if (currentView === 'visual') return;
            
            // Sincronizar código -> visual
            visualEditor.innerHTML = codeEditor.value;
            
            visualEditor.style.display = '';
            codeEditor.style.display = 'none';
            formatActions.classList.remove('disabled');
            
            visualBtn.classList.add('active');
            codeBtn.classList.remove('active');
            currentView = 'visual';
        }
        
        // Cambiar a vista código
        function switchToCode() {
            if (currentView === 'code') return;
            
            // Sincronizar visual -> código
            codeEditor.value = visualEditor.innerHTML;
            
            visualEditor.style.display = 'none';
            codeEditor.style.display = '';
            formatActions.classList.add('disabled');
            
            codeBtn.classList.add('active');
            visualBtn.classList.remove('active');
            currentView = 'code';
        }
        
        visualBtn.addEventListener('click', switchToVisual);
        codeBtn.addEventListener('click', switchToCode);
        
        // Cargar contenido inicial
        var current = u.getConfigValue(block, field.id);
        if (current === undefined || current === null) {
            current = field.defecto || '';
        }
        visualEditor.innerHTML = current;
        codeEditor.value = current;
        
        // Eventos de edición
        visualEditor.addEventListener('input', syncAndNotify);
        codeEditor.addEventListener('input', syncAndNotify);
        
        // Agregar editores al contenedor
        container.appendChild(visualEditor);
        container.appendChild(codeEditor);
        wrapper.appendChild(container);
        u.appendFieldDescription(wrapper, field);
        
        return wrapper;
    }

    // Exportar
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.richTextField = { build: buildRichTextField };

})(window);



