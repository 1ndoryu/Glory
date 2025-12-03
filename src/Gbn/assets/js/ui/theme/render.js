;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.theme = Gbn.ui.theme || {};

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
            { tipo: 'spacing', id: 'padding', etiqueta: 'Padding (Main)', defecto: 20 },
            { tipo: 'text', id: 'maxAncho', etiqueta: 'Ancho Máximo (Página)', defecto: '100%' }
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
    }

    // Estado persistente para navegación de componentes (Bug 6 fix - Global Module Scope)
    var componentState = {
        currentDetailRole: null,
        renderComponentDetail: null
    };
    
    // Vista actual persistente (Bug 6 fix)
    var currentView = 'menu';

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
        
        // Sincronización automática con CSS en modo dev
        if (gloryGbnCfg && gloryGbnCfg.devMode && Gbn.cssSync && Gbn.cssSync.syncTheme) {
            mockBlock.config = Gbn.cssSync.syncTheme(mockBlock.config);
        }
        
        // Initial apply
        if (Gbn.ui.theme.applicator && Gbn.ui.theme.applicator.applyThemeSettings) {
            Gbn.ui.theme.applicator.applyThemeSettings(mockBlock.config);
            if (Gbn.log) Gbn.log.info('Theme Settings Applied', { 
                view: currentView, 
                role: componentState.currentDetailRole,
                configKeys: Object.keys(mockBlock.config)
            });
        }
        
        // var currentView = 'menu'; // Eliminado, ahora es global de módulo
        
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
                // Resetear estado de componentes al volver al menú (Bug 6 fix)
                componentState.currentDetailRole = null;
                componentState.renderComponentDetail = null;
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
                // Component List & Detail View Logic
                
                var componentListContainer = document.createElement('div');
                componentListContainer.className = 'gbn-component-list-view';
                
                var componentDetailContainer = document.createElement('div');
                componentDetailContainer.className = 'gbn-component-detail-view';
                componentDetailContainer.style.display = 'none';
                
                content.appendChild(componentListContainer);
                content.appendChild(componentDetailContainer);
                
                // Define render functions within scope to access mockBlock and content
                
                var renderComponentList = function() {
                    componentListContainer.innerHTML = '';
                    componentDetailContainer.style.display = 'none';
                    componentListContainer.style.display = 'block';
                    
                    // Restore Button
                    if (gloryGbnCfg && gloryGbnCfg.devMode && Gbn.cssSync && Gbn.cssSync.restore) {
                        var restoreBtn = document.createElement('button');
                        restoreBtn.type = 'button';
                        restoreBtn.className = 'gbn-btn-primary';
                        restoreBtn.innerHTML = '↻ Restaurar desde Código CSS';
                        restoreBtn.title = 'Volver a sincronizar todos los componentes con valores del CSS';
                        restoreBtn.style.marginBottom = '16px';
                        restoreBtn.style.width = '100%';
                        
                        restoreBtn.onclick = function() {
                            if (confirm('¿Restaurar todos los valores desde el código CSS? Esto sobrescribirá los cambios manuales.')) {
                                mockBlock.config = Gbn.cssSync.restore(mockBlock.config);
                                if (Gbn.ui.theme.applicator && Gbn.ui.theme.applicator.applyThemeSettings) {
                                    Gbn.ui.theme.applicator.applyThemeSettings(mockBlock.config);
                                }
                                renderComponentList();
                            }
                        };
                        componentListContainer.appendChild(restoreBtn);
                    }
                    
                    if (Gbn.content && Gbn.content.roles && Gbn.content.roles.getMap) {
                        var roles = Gbn.content.roles.getMap();
                        var roleKeys = Object.keys(roles);
                        
                        var list = document.createElement('div');
                        list.className = 'gbn-menu-list';
                        
                        roleKeys.forEach(function(role) {
                            var defaults = Gbn.content.roles.getRoleDefaults(role);
                            if (defaults && defaults.schema && defaults.schema.length > 0) {
                                var item = document.createElement('button');
                                item.className = 'gbn-menu-item';
                                item.innerHTML = '<span>' + (role.charAt(0).toUpperCase() + role.slice(1)) + '</span>' + 
                                                 '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>';
                                
                                item.onclick = function() {
                                    renderComponentDetail(role);
                                };
                                list.appendChild(item);
                            }
                        });
                        componentListContainer.appendChild(list);
                    }
                };
                
                // Variable para rastrear el rol actual y permitir re-render
                // Ahora usa componentState para persistencia entre cambios de breakpoint
                var configChangeHandler = null;
                
                var renderComponentDetail = function(role) {
                    componentState.currentDetailRole = role;
                    componentListContainer.style.display = 'none';
                    componentDetailContainer.style.display = 'block';
                    componentDetailContainer.innerHTML = '';
                    
                    // Header
                    var detailHeader = document.createElement('div');
                    detailHeader.className = 'gbn-detail-header';
                    detailHeader.style.display = 'flex';
                    detailHeader.style.alignItems = 'center';
                    detailHeader.style.marginBottom = '16px';
                    detailHeader.style.paddingBottom = '8px';
                    detailHeader.style.borderBottom = '1px solid #eee';
                    
                    var backBtn = document.createElement('button');
                    backBtn.className = 'gbn-icon-btn';
                    backBtn.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"></polyline></svg>';
                    backBtn.style.marginRight = '8px';
                    backBtn.style.width = '24px';
                    backBtn.style.height = '24px';
                    backBtn.title = 'Volver a la lista';
                    backBtn.onclick = function() {
                        componentState.currentDetailRole = null;
                        renderComponentList();
                    };
                    
                    var title = document.createElement('h4');
                    title.textContent = role.charAt(0).toUpperCase() + role.slice(1);
                    title.style.margin = '0';
                    title.style.fontSize = '14px';
                    
                    detailHeader.appendChild(backBtn);
                    detailHeader.appendChild(title);
                    componentDetailContainer.appendChild(detailHeader);
                    
                    // Container para los campos (para re-render parcial)
                    var fieldsContainer = document.createElement('div');
                    fieldsContainer.className = 'gbn-component-fields';
                    componentDetailContainer.appendChild(fieldsContainer);
                    
                    // Función para renderizar los campos
                    var renderFields = function() {
                        fieldsContainer.innerHTML = '';
                        var defaults = Gbn.content.roles.getRoleDefaults(role);
                        if (defaults && defaults.schema) {
                            var allowedTypes = ['color', 'spacing', 'typography', 'slider', 'icon_group', 'select', 'text', 'fraction'];
                            var relevantFields = defaults.schema.filter(function(f) {
                                return allowedTypes.indexOf(f.tipo) !== -1 && f.id !== 'texto' && f.id !== 'tag';
                            });
                            
                            var builder = Gbn.ui && Gbn.ui.panelFields && Gbn.ui.panelFields.buildField;
                            if (builder) {
                                relevantFields.forEach(function(field) {
                                    var f = Gbn.utils.assign({}, field);
                                    f.id = 'components.' + role + '.' + f.id;
                                    var control = builder(mockBlock, f);
                                    if (control) fieldsContainer.appendChild(control);
                                });
                            }
                        }
                    };
                    
                    // Renderizar campos inicialmente
                    renderFields();
                    
                    // Escuchar cambios de config para re-renderizar campos condicionales
                    if (configChangeHandler) {
                        window.removeEventListener('gbn:configChanged', configChangeHandler);
                    }
                    
                    var conditionalFields = ['layout', 'display_mode'];
                    configChangeHandler = function(e) {
                        if (!componentState.currentDetailRole) return;
                        var detail = e.detail || {};
                        if (detail.id === 'theme-settings') {
                            // Verificar si el cambio fue en un campo condicional
                            var path = detail.path || '';
                            var fieldId = path.split('.').pop();
                            if (conditionalFields.indexOf(fieldId) !== -1) {
                                // Re-renderizar campos
                                renderFields();
                            }
                        }
                    };
                    window.addEventListener('gbn:configChanged', configChangeHandler);
                };
                
                // Exponer renderComponentDetail en componentState para acceso desde listener
                componentState.renderComponentDetail = renderComponentDetail;

                
                // Initial call - Restaurar estado si existe (Bug 6 fix)
                if (componentState.currentDetailRole) {
                    renderComponentDetail(componentState.currentDetailRole);
                } else {
                    renderComponentList();
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
        
        // El manejo de breakpoint se realiza via re-render completo desde panel-core.js
        // Gracias a componentState y currentView globales, el estado se restaura automáticamente.

        // Disable panel footer button as we use global dock save
        if (footer) {
            footer.disabled = true;
            footer.textContent = 'Usa Guardar en el Dock';
        }
    }

    Gbn.ui.theme.render = {
        renderPageSettingsForm: renderPageSettingsForm,
        renderThemeSettingsForm: renderThemeSettingsForm
    };

})(window);
