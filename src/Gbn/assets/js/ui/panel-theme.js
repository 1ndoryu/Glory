;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    var utils = Gbn.utils;

    // Functions moved to theme-applicator.js
    // We keep local wrappers that delegate to Gbn.ui.themeApplicator
    
    function toCssValue(val, defaultUnit) {
        return Gbn.ui.themeApplicator ? Gbn.ui.themeApplicator.toCssValue(val, defaultUnit) : val;
    }

    function renderThemeSettingsForm(settings, container, footer) {
        if (!container) return;
        container.innerHTML = '';
        
        var mockBlock = {
            id: 'theme-settings',
            role: 'theme',
            config: settings || {}
        };
        
        // Initial apply
        applyThemeSettings(mockBlock.config);
        
        var currentView = 'menu'; // menu, text, colors, pages
        
        function render() {
            container.innerHTML = '';
            if (currentView === 'menu') {
                renderMenu();
            } else {
                renderSection(currentView);
            }
        }
        
        function renderMenu() {
            var menuContainer = document.createElement('div');
            menuContainer.className = 'gbn-theme-menu';
            
            var options = [
                { 
                    id: 'text', 
                    label: 'Texto', 
                    icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7V4h16v3M9 20h6M12 4v16"/></svg>' 
                },
                { 
                    id: 'colors', 
                    label: 'Colores', 
                    icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/></svg>' 
                },
                { 
                    id: 'pages', 
                    label: 'Páginas', 
                    icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg>' 
                }
            ];
            
            options.forEach(function(opt) {
                var btn = document.createElement('button');
                btn.className = 'gbn-theme-menu-btn';
                btn.innerHTML = opt.icon + '<span>' + opt.label + '</span>';
                btn.onclick = function() {
                    currentView = opt.id;
                    render();
                };
                menuContainer.appendChild(btn);
            });
            
            container.appendChild(menuContainer);
        }
        
        function renderSection(sectionId) {
            var sectionContainer = document.createElement('div');
            sectionContainer.className = 'gbn-theme-section';
            
            var header = document.createElement('div');
            header.className = 'gbn-theme-section-header';
            
            var backBtn = document.createElement('button');
            backBtn.className = 'gbn-theme-back-btn';
            backBtn.textContent = '← Volver';
            backBtn.onclick = function() {
                currentView = 'menu';
                render();
            };
            
            var title = document.createElement('span');
            title.className = 'gbn-theme-section-title';
            title.textContent = sectionId.charAt(0).toUpperCase() + sectionId.slice(1);
            
            header.appendChild(backBtn);
            header.appendChild(title);
            sectionContainer.appendChild(header);
            
            var content = document.createElement('div');
            content.className = 'gbn-theme-section-content';
            
            var schema = [];
            if (sectionId === 'text') {
                schema = [
                    { tipo: 'header', etiqueta: 'Párrafos (p)' },
                    { tipo: 'typography', id: 'text.p', etiqueta: 'Tipografía' },
                    { tipo: 'color', id: 'text.p.color', etiqueta: 'Color Texto', defecto: '#333333' }
                ];
                
                // Add headers h1-h6
                ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'].forEach(function(tag) {
                    schema.push({ tipo: 'header', etiqueta: tag.toUpperCase() });
                    schema.push({ tipo: 'typography', id: 'text.' + tag, etiqueta: 'Tipografía' });
                    schema.push({ tipo: 'color', id: 'text.' + tag + '.color', etiqueta: 'Color ' + tag.toUpperCase(), defecto: '' });
                });
                
            } else if (sectionId === 'colors') {
                schema = [
                    { tipo: 'header', etiqueta: 'Paleta Global' },
                    { tipo: 'color', id: 'colors.primary', etiqueta: 'Primario', defecto: '#007bff', hidePalette: true },
                    { tipo: 'color', id: 'colors.secondary', etiqueta: 'Secundario', defecto: '#6c757d', hidePalette: true },
                    { tipo: 'color', id: 'colors.accent', etiqueta: 'Acento', defecto: '#28a745', hidePalette: true },
                    { tipo: 'color', id: 'colors.background', etiqueta: 'Fondo Body', defecto: '#f8f9fa', hidePalette: true }
                ];
                
                // Custom Colors Section
                var customHeader = document.createElement('div');
                customHeader.className = 'gbn-field-header-separator';
                customHeader.innerHTML = '<h4>Colores Personalizados</h4>';
                
                // Container for custom colors list
                var customList = document.createElement('div');
                customList.className = 'gbn-custom-colors-list';
                
                var customColors = (mockBlock.config.colors && mockBlock.config.colors.custom) ? mockBlock.config.colors.custom : [];
                
                function renderCustomList() {
                    customList.innerHTML = '';
                    customColors.forEach(function(c, index) {
                        var item = document.createElement('div');
                        item.className = 'gbn-custom-color-item';
                        var colorInput = document.createElement('input');
                        colorInput.type = 'color';
                        colorInput.className = 'gbn-custom-color-preview';
                        colorInput.value = c.value;
                        colorInput.title = 'Cambiar color';
                        colorInput.oninput = function() {
                            c.value = colorInput.value;
                            updateCustomColors(true); // true = skip render to keep focus
                        };
                        
                        var nameInput = document.createElement('input');
                        nameInput.type = 'text';
                        nameInput.className = 'gbn-custom-color-name';
                        nameInput.value = c.name;
                        nameInput.placeholder = 'Nombre';
                        nameInput.oninput = function() {
                            c.name = nameInput.value;
                            updateCustomColors(true);
                        };
                        
                        var delBtn = document.createElement('button');
                        delBtn.type = 'button';
                        delBtn.className = 'gbn-custom-color-delete';
                        delBtn.innerHTML = '&times;';
                        delBtn.title = 'Eliminar color';
                        delBtn.onclick = function() {
                            // No confirmation as requested
                            customColors.splice(index, 1);
                            updateCustomColors();
                        };
                        
                        item.appendChild(colorInput);
                        item.appendChild(nameInput);
                        item.appendChild(delBtn);
                        customList.appendChild(item);
                    });
                }
                
                function updateCustomColors(skipRender) {
                    // Update config
                    var api = Gbn.ui && Gbn.ui.panelApi;
                    if (api && api.updateConfigValue) {
                        if (!mockBlock.config.colors) mockBlock.config.colors = {};
                        api.updateConfigValue(mockBlock, 'colors.custom', customColors);
                    }
                    if (!skipRender) {
                        renderCustomList();
                    }
                }
                
                renderCustomList();
                
                // Add New Color Form
                var addForm = document.createElement('div');
                addForm.className = 'gbn-add-color-form';
                
                var row1 = document.createElement('div');
                row1.className = 'gbn-add-color-row';
                
                var nameInput = document.createElement('input');
                nameInput.type = 'text';
                nameInput.className = 'gbn-add-color-input';
                nameInput.placeholder = 'Nombre del nuevo color';
                
                var colorInput = document.createElement('input');
                colorInput.type = 'color';
                colorInput.className = 'gbn-custom-color-preview'; // Reuse preview style
                colorInput.style.height = '38px'; // Match input height
                colorInput.style.width = '38px';
                colorInput.value = '#000000';
                
                row1.appendChild(colorInput);
                row1.appendChild(nameInput);
                
                var addBtn = document.createElement('button');
                addBtn.type = 'button';
                addBtn.className = 'gbn-add-btn-primary';
                addBtn.textContent = 'Añadir Color';
                addBtn.onclick = function() {
                    var name = nameInput.value.trim();
                    var val = colorInput.value;
                    if (!name) {
                        alert('Por favor ingresa un nombre para el color.');
                        return;
                    }
                    customColors.push({ name: name, value: val });
                    nameInput.value = '';
                    updateCustomColors();
                };
                
                addForm.appendChild(row1);
                addForm.appendChild(addBtn);
                
                // Append to schema via a custom wrapper since builder expects fields
                // We can append directly to content
                setTimeout(function() {
                    if (content) {
                        content.appendChild(customHeader);
                        content.appendChild(customList);
                        content.appendChild(addForm);
                    }
                }, 0);
            } else if (sectionId === 'pages') {
                schema = [
                    { tipo: 'header', etiqueta: 'Defaults de Página' },
                    { tipo: 'color', id: 'pages.background', etiqueta: 'Fondo Default', defecto: '#ffffff' },
                    { tipo: 'spacing', id: 'pages.padding', etiqueta: 'Padding Default', defecto: 20 }
                ];
            }
            
            var builder = Gbn.ui && Gbn.ui.panelFields && Gbn.ui.panelFields.buildField;
            if (builder) {
                schema.forEach(function(field) {
                    var control = builder(mockBlock, field);
                    if (control) content.appendChild(control);
                });
            }
            
            sectionContainer.appendChild(content);
            container.appendChild(sectionContainer);
        }
        
        render();
        
        // Disable panel footer button as we use global dock save
        if (footer) {
            footer.disabled = true;
            footer.textContent = 'Usa Guardar en el Dock';
        }
        
        // Monkey-patching removed. Logic moved to panel-render.js
    }

    function applyPageSettings(settings) {
        if (Gbn.ui.themeApplicator) {
            Gbn.ui.themeApplicator.applyPageSettings(settings);
        }
    }

    function applyThemeSettings(settings) {
        if (Gbn.ui.themeApplicator) {
            Gbn.ui.themeApplicator.applyThemeSettings(settings);
        }
    }

    // ... render functions remain ...

    Gbn.ui = Gbn.ui || {};
    Gbn.ui.panelTheme = {
        renderPageSettingsForm: renderPageSettingsForm,
        renderThemeSettingsForm: renderThemeSettingsForm,
        applyThemeSettings: applyThemeSettings,
        applyPageSettings: applyPageSettings
    };

})(window);
// Initialization logic removed as it is now in theme-applicator.js
