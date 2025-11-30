;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    var utils = Gbn.utils;

    function applyPageSettings(settings) {
        var root = document.querySelector('[data-gbn-root]');
        if (!root) return;
        
        if (settings.background) {
            root.style.backgroundColor = settings.background;
        }
        if (settings.padding) {
            if (typeof settings.padding === 'object') {
                root.style.paddingTop = settings.padding.superior + 'px';
                root.style.paddingRight = settings.padding.derecha + 'px';
                root.style.paddingBottom = settings.padding.inferior + 'px';
                root.style.paddingLeft = settings.padding.izquierda + 'px';
            } else {
                root.style.padding = settings.padding + 'px';
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
        
        if (footer) {
            var newFooterBtn = footer.cloneNode(true);
            footer.parentNode.replaceChild(newFooterBtn, footer);
            footer = newFooterBtn;
            
            footer.disabled = false;
            footer.textContent = 'Guardar Configuración';
            footer.addEventListener('click', function(e) {
                e.preventDefault();
                if (Gbn.ui.panel && Gbn.ui.panel.setStatus) Gbn.ui.panel.setStatus('Guardando...');
                
                Gbn.persistence.savePageSettings(mockBlock.config).then(function(res) {
                    if (res && res.success) {
                        if (Gbn.ui.panel && Gbn.ui.panel.setStatus) Gbn.ui.panel.setStatus('Guardado');
                        applyPageSettings(mockBlock.config);
                    } else {
                        if (Gbn.ui.panel && Gbn.ui.panel.setStatus) Gbn.ui.panel.setStatus('Error al guardar');
                    }
                });
            });
        }
        
        // Monkey-patch updateConfigValue locally for this context
        // We need to ensure panelRender uses this override if we are in page settings mode.
        // But panelRender is global.
        // Better approach: panelRender.updateConfigValue checks the block ID.
        // Or we can rely on the fact that panelRender.updateConfigValue is what panelFields calls.
        // So we can override it on Gbn.ui.panelRender or Gbn.ui.panelApi.
        // Let's assume panel-core sets up the delegation.
        
        // For now, we can define a specific update handler for page settings
        // and register it? No, panelFields calls a global API.
        // We will handle the special logic in panel-render.js or panel-core.js by checking block.id
        // OR we can override it here if we are careful.
        
        var originalUpdate = Gbn.ui.panelApi.updateConfigValue;
        Gbn.ui.panelApi.updateConfigValue = function(block, path, value) {
            if (block.id === 'page-settings') {
                var segments = path.split('.');
                var cursor = mockBlock.config;
                for (var i = 0; i < segments.length - 1; i++) {
                    if (!cursor[segments[i]]) cursor[segments[i]] = {};
                    cursor = cursor[segments[i]];
                }
                cursor[segments[segments.length - 1]] = value;
                applyPageSettings(mockBlock.config);
                return;
            }
            return originalUpdate(block, path, value);
        };
    }

    function applyThemeSettings(settings) {
        var root = document.documentElement;
        if (!settings) return;
        
        // Text Settings
        if (settings.text) {
            if (settings.text.p) {
                if (settings.text.p.color) root.style.setProperty('--gbn-text-color', settings.text.p.color);
                if (settings.text.p.size) root.style.setProperty('--gbn-text-size', settings.text.p.size + 'px');
                if (settings.text.p.font && settings.text.p.font !== 'System') root.style.setProperty('--gbn-text-font', settings.text.p.font);
            }
            if (settings.text.h1) {
                if (settings.text.h1.color) root.style.setProperty('--gbn-h1-color', settings.text.h1.color);
                if (settings.text.h1.size) root.style.setProperty('--gbn-h1-size', settings.text.h1.size + 'px');
            }
        }
        
        // Color Settings
        if (settings.colors) {
            if (settings.colors.primary) root.style.setProperty('--gbn-primary', settings.colors.primary);
            if (settings.colors.secondary) root.style.setProperty('--gbn-secondary', settings.colors.secondary);
            if (settings.colors.accent) root.style.setProperty('--gbn-accent', settings.colors.accent);
            if (settings.colors.background) root.style.setProperty('--gbn-bg', settings.colors.background);
        }
        
        // Page Defaults (handled by applyPageSettings usually, but if global defaults...)
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
                    { tipo: 'select', id: 'text.p.font', etiqueta: 'Fuente', opciones: [{valor: 'Inter'}, {valor: 'Roboto'}, {valor: 'Open Sans'}, {valor: 'System'}] },
                    { tipo: 'text', id: 'text.p.size', etiqueta: 'Tamaño Base (px)', defecto: '16' },
                    { tipo: 'color', id: 'text.p.color', etiqueta: 'Color Texto', defecto: '#333333' },
                    
                    { tipo: 'header', etiqueta: 'Encabezados (h1)' },
                    { tipo: 'text', id: 'text.h1.size', etiqueta: 'Tamaño H1 (px)', defecto: '32' },
                    { tipo: 'color', id: 'text.h1.color', etiqueta: 'Color H1', defecto: '#111111' }
                ];
            } else if (sectionId === 'colors') {
                schema = [
                    { tipo: 'header', etiqueta: 'Paleta Global' },
                    { tipo: 'color', id: 'colors.primary', etiqueta: 'Primario', defecto: '#007bff' },
                    { tipo: 'color', id: 'colors.secondary', etiqueta: 'Secundario', defecto: '#6c757d' },
                    { tipo: 'color', id: 'colors.accent', etiqueta: 'Acento', defecto: '#28a745' },
                    { tipo: 'color', id: 'colors.background', etiqueta: 'Fondo Body', defecto: '#f8f9fa' }
                ];
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
        
        if (footer) {
            var newFooterBtn = footer.cloneNode(true);
            footer.parentNode.replaceChild(newFooterBtn, footer);
            footer = newFooterBtn;
            
            footer.disabled = false;
            footer.textContent = 'Guardar Tema';
            footer.addEventListener('click', function(e) {
                e.preventDefault();
                if (Gbn.ui.panel && Gbn.ui.panel.setStatus) Gbn.ui.panel.setStatus('Guardando...');
                
                Gbn.persistence.saveThemeSettings(mockBlock.config).then(function(res) {
                    if (res && res.success) {
                        if (Gbn.ui.panel && Gbn.ui.panel.setStatus) Gbn.ui.panel.setStatus('Tema Guardado');
                    }
                });
            });
        }
        
        var originalUpdate = Gbn.ui.panelApi.updateConfigValue;
        Gbn.ui.panelApi.updateConfigValue = function(block, path, value) {
            if (block.id === 'theme-settings') {
                var segments = path.split('.');
                var cursor = mockBlock.config;
                for (var i = 0; i < segments.length - 1; i++) {
                    if (!cursor[segments[i]]) cursor[segments[i]] = {};
                    cursor = cursor[segments[i]];
                }
                cursor[segments[segments.length - 1]] = value;
                applyThemeSettings(mockBlock.config);
                return;
            }
            return originalUpdate(block, path, value);
        };
    }

    Gbn.ui = Gbn.ui || {};
    Gbn.ui.panelTheme = {
        renderPageSettingsForm: renderPageSettingsForm,
        renderThemeSettingsForm: renderThemeSettingsForm,
        applyThemeSettings: applyThemeSettings
    };

})(window);
