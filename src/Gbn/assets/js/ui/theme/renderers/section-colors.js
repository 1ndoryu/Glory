;(function(global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.theme = Gbn.ui.theme || {};
    Gbn.ui.theme.renderers = Gbn.ui.theme.renderers || {};

    /**
     * Renderizador de la sección Colors de Theme Settings.
     * 
     * Responsabilidad: Paleta global de colores y colores personalizados.
     */

    /**
     * Genera el schema base para colores.
     * @returns {Array} Schema de campos de color
     */
    function getSchema() {
        return [
            { tipo: 'header', etiqueta: 'Paleta Global' },
            { tipo: 'color', id: 'colors.primary', etiqueta: 'Primario', defecto: '#007bff', hidePalette: true },
            { tipo: 'color', id: 'colors.secondary', etiqueta: 'Secundario', defecto: '#6c757d', hidePalette: true },
            { tipo: 'color', id: 'colors.accent', etiqueta: 'Acento', defecto: '#28a745', hidePalette: true },
            { tipo: 'color', id: 'colors.background', etiqueta: 'Fondo Body', defecto: '#f8f9fa', hidePalette: true }
        ];
    }

    /**
     * Crea la UI para colores personalizados.
     * 
     * @param {Object} mockBlock - Mock block con config
     * @returns {Object} {header, list, form}
     */
    function createCustomColorsUI(mockBlock) {
        // Header de sección
        var customHeader = document.createElement('div');
        customHeader.className = 'gbn-field-header-separator';
        customHeader.innerHTML = '<h4>Colores Personalizados</h4>';
        
        // Lista de colores
        var customList = document.createElement('div');
        customList.className = 'gbn-custom-colors-list';
        
        // Obtener colores existentes
        var customColors = (mockBlock.config.colors && mockBlock.config.colors.custom) 
            ? mockBlock.config.colors.custom 
            : [];
        
        /**
         * Renderiza la lista de colores personalizados
         */
        function renderList() {
            customList.innerHTML = '';
            
            customColors.forEach(function(c, index) {
                var item = document.createElement('div');
                item.className = 'gbn-custom-color-item';
                
                // Input de color
                var colorInput = document.createElement('input');
                colorInput.type = 'color';
                colorInput.className = 'gbn-custom-color-preview';
                colorInput.value = c.value;
                colorInput.title = 'Cambiar color';
                colorInput.oninput = function() {
                    c.value = colorInput.value;
                    updateConfig(true);
                };
                
                // Input de nombre
                var nameInput = document.createElement('input');
                nameInput.type = 'text';
                nameInput.className = 'gbn-custom-color-name';
                nameInput.value = c.name;
                nameInput.placeholder = 'Nombre';
                nameInput.oninput = function() {
                    c.name = nameInput.value;
                    updateConfig(true);
                };
                
                // Botón eliminar
                var delBtn = document.createElement('button');
                delBtn.type = 'button';
                delBtn.className = 'gbn-custom-color-delete';
                delBtn.innerHTML = '&times;';
                delBtn.title = 'Eliminar color';
                delBtn.onclick = function() {
                    customColors.splice(index, 1);
                    updateConfig();
                };
                
                item.appendChild(colorInput);
                item.appendChild(nameInput);
                item.appendChild(delBtn);
                customList.appendChild(item);
            });
        }
        
        /**
         * Actualiza la configuración de colores
         * @param {boolean} skipRender - Si true, no re-renderiza la lista (mantiene focus)
         */
        function updateConfig(skipRender) {
            var api = Gbn.ui && Gbn.ui.panelApi;
            if (api && api.updateConfigValue) {
                if (!mockBlock.config.colors) mockBlock.config.colors = {};
                api.updateConfigValue(mockBlock, 'colors.custom', customColors);
            }
            if (!skipRender) {
                renderList();
            }
        }
        
        // Render inicial
        renderList();
        
        // Formulario para agregar nuevo color
        var addForm = document.createElement('div');
        addForm.className = 'gbn-add-color-form';
        
        var row1 = document.createElement('div');
        row1.className = 'gbn-add-color-row';
        
        var newNameInput = document.createElement('input');
        newNameInput.type = 'text';
        newNameInput.className = 'gbn-add-color-input';
        newNameInput.placeholder = 'Nombre del nuevo color';
        
        var newColorInput = document.createElement('input');
        newColorInput.type = 'color';
        newColorInput.className = 'gbn-custom-color-preview';
        newColorInput.style.height = '38px';
        newColorInput.style.width = '38px';
        newColorInput.value = '#000000';
        
        row1.appendChild(newColorInput);
        row1.appendChild(newNameInput);
        
        var addBtn = document.createElement('button');
        addBtn.type = 'button';
        addBtn.className = 'gbn-add-btn-primary';
        addBtn.textContent = 'Añadir Color';
        addBtn.onclick = function() {
            var name = newNameInput.value.trim();
            var val = newColorInput.value;
            
            if (!name) {
                alert('Por favor ingresa un nombre para el color.');
                return;
            }
            
            customColors.push({ name: name, value: val });
            newNameInput.value = '';
            updateConfig();
        };
        
        addForm.appendChild(row1);
        addForm.appendChild(addBtn);
        
        return {
            header: customHeader,
            list: customList,
            form: addForm
        };
    }

    /**
     * Renderiza la sección de colores.
     * 
     * @param {HTMLElement} content - Contenedor de contenido
     * @param {Object} mockBlock - Mock block para el builder
     * @param {Function} builder - Función buildField
     */
    function render(content, mockBlock, builder) {
        var schema = getSchema();
        
        // Renderizar campos base
        schema.forEach(function(field) {
            var control = builder(mockBlock, field);
            if (control) content.appendChild(control);
        });
        
        // Agregar sección de colores personalizados
        var customUI = createCustomColorsUI(mockBlock);
        content.appendChild(customUI.header);
        content.appendChild(customUI.list);
        content.appendChild(customUI.form);
    }

    // Exportar
    Gbn.ui.theme.renderers.sectionColors = {
        render: render,
        getSchema: getSchema
    };

})(window);
