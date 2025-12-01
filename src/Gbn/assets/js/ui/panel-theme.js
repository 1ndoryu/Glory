;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    var utils = Gbn.utils;

    // Functions moved to theme-applicator.js
    // We keep local wrappers that delegate to Gbn.ui.themeApplicator
    
    function toCssValue(val, defaultUnit) {
        return Gbn.ui.themeApplicator ? Gbn.ui.themeApplicator.toCssValue(val, defaultUnit) : val;
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
        
        // Helper to set or remove property
        function setOrRemove(prop, val) {
            if (val !== null && val !== undefined && val !== '') {
                root.style.setProperty(prop, val);
            } else {
                root.style.removeProperty(prop);
            }
        }

        // Helper to set or remove property with unit conversion
        function setOrRemoveValue(prop, val) {
             if (val !== null && val !== undefined && val !== '') {
                root.style.setProperty(prop, toCssValue(val));
            } else {
                root.style.removeProperty(prop);
            }
        }
        
        // Text Settings
        if (settings.text) {
            var tags = ['p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'];
            tags.forEach(function(tag) {
                if (settings.text[tag]) {
                    var s = settings.text[tag];
                    var prefix = '--gbn-' + (tag === 'p' ? 'text' : tag);
                    
                    setOrRemove(prefix + '-color', s.color);
                    setOrRemoveValue(prefix + '-size', s.size);
                    
                    if (s.font && s.font !== 'System') {
                        root.style.setProperty(prefix + '-font', s.font);
                    } else {
                        root.style.removeProperty(prefix + '-font');
                    }
                    
                    setOrRemove(prefix + '-lh', s.lineHeight);
                    setOrRemoveValue(prefix + '-ls', s.letterSpacing);
                    setOrRemove(prefix + '-transform', s.transform);
                }
            });
        }
        
        // Color Settings
        if (settings.colors) {
            setOrRemove('--gbn-primary', settings.colors.primary);
            setOrRemove('--gbn-secondary', settings.colors.secondary);
            setOrRemove('--gbn-accent', settings.colors.accent);
            setOrRemove('--gbn-bg', settings.colors.background);
            
            // Custom Colors
            if (settings.colors.custom && Array.isArray(settings.colors.custom)) {
                settings.colors.custom.forEach(function(c, i) {
                    if (c.value) {
                        root.style.setProperty('--gbn-custom-' + i, c.value);
                    }
                });
            }
        }
        
        // Page Defaults
        if (settings.pages) {
            setOrRemove('--gbn-page-bg', settings.pages.background);
            // Page padding is handled in applyPageSettings usually, but if it's a theme default for pages:
            // We might want a variable for it if we want it to be overridable
        }
        
        // Component Defaults (Principal, Secundario, etc)
        if (settings.components) {
             Object.keys(settings.components).forEach(function(role) {
                 var comp = settings.components[role];
                 if (!comp) return;
                 
                 // Map specific known properties to CSS variables
                 if (role === 'principal') {
                     setOrRemoveValue('--gbn-principal-padding', comp.padding);
                     setOrRemove('--gbn-principal-background', comp.background);
                     setOrRemove('--gbn-principal-gap', comp.gap);
                     // Layout defaults could be vars too if we updated CSS
                 } else if (role === 'secundario') {
                     setOrRemoveValue('--gbn-secundario-padding', comp.padding);
                     setOrRemove('--gbn-secundario-background', comp.background);
                     setOrRemove('--gbn-secundario-width', comp.width);
                 }
             });
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
        
        // Merge defaults for components if not present
        if (!mockBlock.config.components) mockBlock.config.components = {};
        
        if (Gbn.content && Gbn.content.roles && Gbn.content.roles.getMap) {
            var roles = Gbn.content.roles.getMap();
            Object.keys(roles).forEach(function(role) {
                var defaults = Gbn.content.roles.getRoleDefaults(role);
                if (defaults && defaults.config) {
                    // Only set if not already set
                    if (!mockBlock.config.components[role]) {
                        mockBlock.config.components[role] = {};
                    }
                    // Deep merge or just copy missing keys?
                    // Let's copy missing keys from defaults.config to mockBlock.config.components[role]
                    // But wait, defaults.config has keys like 'padding', 'background', etc.
                    // We need to map them.
                    // Actually, the schema uses IDs like 'components.principal.padding'.
                    // So updateConfigValue will store them in config.components.principal.padding.
                    // So we just need to ensure config.components.principal has the default values.
                    
                    var compDefaults = defaults.config;
                    var currentComp = mockBlock.config.components[role];
                    
                    Object.keys(compDefaults).forEach(function(key) {
                        if (currentComp[key] === undefined) {
                            currentComp[key] = compDefaults[key];
                        }
                    });
                }
            });
        }
        
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
                },
                {
                    id: 'components',
                    label: 'Componentes',
                    icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12h20M2 12l4-4m-4 4l4 4M22 12l-4-4m4 4l-4 4"/></svg>'
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
            } else if (sectionId === 'components') {
                // Dynamic Component Defaults
                if (Gbn.content && Gbn.content.roles && Gbn.content.roles.getMap) {
                    var roles = Gbn.content.roles.getMap();
                    var roleKeys = Object.keys(roles);
                    
                    // Filter roles that have defaults/schema
                    roleKeys.forEach(function(role) {
                        var defaults = Gbn.content.roles.getRoleDefaults(role);
                        if (defaults && defaults.schema && defaults.schema.length > 0) {
                            // Filter relevant fields for global defaults (style related)
                            var allowedTypes = ['color', 'spacing', 'typography', 'slider', 'icon_group', 'select'];
                            var relevantFields = defaults.schema.filter(function(f) {
                                return allowedTypes.indexOf(f.tipo) !== -1 && f.id !== 'texto' && f.id !== 'tag';
                            });
                            
                            if (relevantFields.length > 0) {
                                schema.push({ tipo: 'header', etiqueta: role.charAt(0).toUpperCase() + role.slice(1) });
                                relevantFields.forEach(function(field) {
                                    // Clone field to avoid modifying original schema
                                    var f = Gbn.utils.assign({}, field);
                                    // Prefix id with components.{role}.
                                    f.id = 'components.' + role + '.' + f.id;
                                    // Adjust label if needed? Maybe not.
                                    schema.push(f);
                                });
                            }
                        }
                    });
                }
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
