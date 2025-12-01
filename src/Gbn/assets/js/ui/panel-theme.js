;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    var utils = Gbn.utils;

    function toCssValue(val, defaultUnit) {
        if (val === null || val === undefined || val === '') return '';
        var strVal = String(val).trim();
        if (/^[0-9.]+[a-z%]+$/i.test(strVal)) return strVal;
        if (!isNaN(parseFloat(strVal))) return strVal + (defaultUnit || 'px');
        return strVal;
    }

    function applyPageSettings(settings) {
        var root = document.querySelector('[data-gbn-root]');
        if (!root) return;
        
        if (settings.background) {
            root.style.backgroundColor = settings.background;
        }
        if (settings.padding) {
            if (typeof settings.padding === 'object') {
                root.style.paddingTop = toCssValue(settings.padding.superior);
                root.style.paddingRight = toCssValue(settings.padding.derecha);
                root.style.paddingBottom = toCssValue(settings.padding.inferior);
                root.style.paddingLeft = toCssValue(settings.padding.izquierda);
            } else {
                root.style.padding = toCssValue(settings.padding);
            }
        }
    }

    function renderPageSettingsForm(settings, container, footer) {
        if (!container) return;
        container.innerHTML = '';
        var form = document.createElement('form');
        form.className = 'gbn-panel-form';
        
        var builder = Gbn.ui && Gbn.ui.panelFields && Gbn.ui.panelFields.buildField;
        if (!builder) {
            container.innerHTML = 'Error: panelFields no disponible';
            return;
        }

        var schema = [
            { tipo: 'color', id: 'background', etiqueta: 'Color de Fondo (Main)', defecto: '#ffffff' },
            { tipo: 'spacing', id: 'padding', etiqueta: 'Padding (Main)', defecto: 20 }
        ];

        var mockBlock = {
            id: 'page-settings',
            role: 'page',
            config: settings || {}
        };

        schema.forEach(function(field) {
            var control = builder(mockBlock, field);
            if (control) {
                form.appendChild(control);
            }
        });

        container.appendChild(form);
        
        // Disable panel footer button as we use global dock save
        if (footer) {
            footer.disabled = true;
            footer.textContent = 'Usa Guardar en el Dock';
        }
        
        // Monkey-patching removed. Logic moved to panel-render.js
    }

    function applyThemeSettings(settings) {
        // Use data-gbn-root for scoping, fallback to documentElement if not found (but prefer root)
        var root = document.querySelector('[data-gbn-root]') || document.documentElement;
        if (!settings) return;
        
        // Text Settings
        if (settings.text) {
            var tags = ['p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'];
            tags.forEach(function(tag) {
                if (settings.text[tag]) {
                    var s = settings.text[tag];
                    var prefix = '--gbn-' + (tag === 'p' ? 'text' : tag);
                    
                    if (s.color) root.style.setProperty(prefix + '-color', s.color);
                    if (s.size) root.style.setProperty(prefix + '-size', toCssValue(s.size));
                    if (s.font && s.font !== 'System') root.style.setProperty(prefix + '-font', s.font);
                    if (s.lineHeight) root.style.setProperty(prefix + '-lh', s.lineHeight);
                    if (s.letterSpacing) root.style.setProperty(prefix + '-ls', toCssValue(s.letterSpacing));
                    if (s.transform) root.style.setProperty(prefix + '-transform', s.transform);
                }
            });
        }
        
        // Color Settings
        if (settings.colors) {
            if (settings.colors.primary) root.style.setProperty('--gbn-primary', settings.colors.primary);
            if (settings.colors.secondary) root.style.setProperty('--gbn-secondary', settings.colors.secondary);
            if (settings.colors.accent) root.style.setProperty('--gbn-accent', settings.colors.accent);
            if (settings.colors.background) root.style.setProperty('--gbn-bg', settings.colors.background);
            
            // Custom Colors
            if (settings.colors.custom && Array.isArray(settings.colors.custom)) {
                settings.colors.custom.forEach(function(c, i) {
                    if (c.value) {
                        // We can set by index or name. Let's set by index for stability if names change?
                        // Or maybe we don't need CSS vars for custom colors if they are just for the palette?
                        // But if we want to use them in CSS, we might need them.
                        // Let's set them just in case.
                        root.style.setProperty('--gbn-custom-' + i, c.value);
                    }
                });
            }
        }
        
        // Page Defaults
        if (settings.pages) {
            if (settings.pages.background) root.style.setProperty('--gbn-page-bg', settings.pages.background);
        }
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

    Gbn.ui = Gbn.ui || {};
    Gbn.ui.panelTheme = {
        renderPageSettingsForm: renderPageSettingsForm,
        renderThemeSettingsForm: renderThemeSettingsForm,
        applyThemeSettings: applyThemeSettings,
        applyPageSettings: applyPageSettings
    };

})(window);
// Initialize Settings on Load
document.addEventListener('DOMContentLoaded', function() {
    // 1. Identify Root
    // Check if a root is already defined (e.g. by PHP template)
    var existingRoot = document.querySelector('[data-gbn-root]');
    if (!existingRoot) {
        var root = document.querySelector('main') || document.body;
        if (root) {
            root.setAttribute('data-gbn-root', 'true');
        }
    }
    
    // 2. Load Config
    var config = window.gloryGbnCfg || {};
    if (window.Gbn) {
        if (!window.Gbn.config) window.Gbn.config = {};
        if (config.themeSettings) window.Gbn.config.themeSettings = config.themeSettings;
        if (config.pageSettings) window.Gbn.config.pageSettings = config.pageSettings;
    }
    
    // 3. Apply Settings
    if (window.Gbn && window.Gbn.ui && window.Gbn.ui.panelTheme) {
        if (config.themeSettings) {
            window.Gbn.ui.panelTheme.applyThemeSettings(config.themeSettings);
        }
        if (config.pageSettings) {
            window.Gbn.ui.panelTheme.applyPageSettings(config.pageSettings);
        }
    }
});
