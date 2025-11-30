;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    var utils = Gbn.utils;
    var state = Gbn.state;
    var styleManager = Gbn.styleManager;

    var panelRoot = null;
    var panelBody = null;
    var panelTitle = null;
    var panelFooter = null;
    var activeBlock = null;
    var panelForm = null;
    var panelMode = 'idle';
    var panelNotice = null;
    var panelStatusTimer = null;
    var listenersBound = false;

    function renderPlaceholder(message) {
        if (!panelBody) { return; }
        panelMode = 'idle';
        panelForm = null;
        var text = message || 'Selecciona un bloque para configurar.';
        panelBody.innerHTML = '<div class="gbn-panel-empty">' + text + '</div>';
        if (panelFooter) {
            panelFooter.disabled = true;
            panelFooter.textContent = 'Guardar (próximamente)';
        }
        setPanelStatus('Cambios en vivo');
    }

    function ensurePanelMounted() {
        if (panelRoot) { return panelRoot; }
        panelRoot = document.getElementById('gbn-panel');
        if (!panelRoot) {
            panelRoot = document.createElement('aside');
            panelRoot.id = 'gbn-panel';
            panelRoot.setAttribute('aria-hidden', 'true');
            panelRoot.innerHTML = ''
                + '<header class="gbn-header">'
                + '  <span class="gbn-header-title">GBN Panel</span>'
                + '  <button type="button" class="gbn-header-close" data-gbn-action="close-panel" aria-label="Close panel">×</button>'
                + '</header>'
                + '<div class="gbn-body"></div>'
                + '<footer class="gbn-footer">'
                + '  <span class="gbn-footer-status">Cambios en vivo</span>'
                + '  <button type="button" class="gbn-footer-primary" disabled>Guardar (próximamente)</button>'
                + '</footer>';
            document.body.appendChild(panelRoot);
        }
        panelBody = panelRoot.querySelector('.gbn-body');
        panelTitle = panelRoot.querySelector('.gbn-header-title');
        panelFooter = panelRoot.querySelector('.gbn-footer-primary');
        panelNotice = panelRoot.querySelector('.gbn-footer-status');
        if (panelFooter && !panelFooter.__gbnBound) {
            panelFooter.__gbnBound = true;
            panelFooter.disabled = false;
            panelFooter.textContent = 'Guardar';
            panelFooter.addEventListener('click', function (event) {
                event.preventDefault();
                if (!Gbn.persistence || typeof Gbn.persistence.savePageConfig !== 'function') { return; }
                setPanelStatus('Guardando...');
                Gbn.persistence.savePageConfig().then(function (res) {
                    flashPanelStatus(res && res.success ? 'Guardado' : 'Error al guardar');
                }).catch(function () { flashPanelStatus('Error al guardar'); });
            });
        }
        renderPlaceholder();
        if (!listenersBound) {
            listenersBound = true;
            var closeBtn = panelRoot.querySelector('[data-gbn-action="close-panel"]');
            if (closeBtn) {
                closeBtn.addEventListener('click', function (event) {
                    event.preventDefault();
                    panel.close();
                });
            }
            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && panel.isOpen()) {
                    panel.close();
                }
            });
        }
        return panelRoot;
    }

    function setActiveBlock(nextBlock) {
        if (activeBlock && activeBlock.element) {
            activeBlock.element.classList.remove('gbn-block-active');
            if (activeBlock.element.__gbnBtn) {
                activeBlock.element.__gbnBtn.classList.remove('is-active');
            }
        }
        activeBlock = nextBlock || null;
        if (activeBlock && activeBlock.element) {
            activeBlock.element.classList.add('gbn-block-active');
            if (activeBlock.element.__gbnBtn) {
                activeBlock.element.__gbnBtn.classList.add('is-active');
            }
        }
    }

    function setPanelStatus(text) {
        if (panelNotice) {
            panelNotice.textContent = text;
        }
    }

    function flashPanelStatus(text) {
        if (!panelNotice) { return; }
        panelNotice.textContent = text;
        if (panelStatusTimer) { clearTimeout(panelStatusTimer); }
        panelStatusTimer = setTimeout(function () {
            panelNotice.textContent = 'Cambios en vivo';
        }, 1600);
    }

    function cloneConfig(config) {
        var output = utils.assign({}, config || {});
        Object.keys(output).forEach(function (key) {
            var item = output[key];
            if (item && typeof item === 'object' && !Array.isArray(item)) {
                output[key] = utils.assign({}, item);
            }
        });
        return output;
    }

    function getInlineValueForPath(block, path) {
        if (!block || !block.styles || !block.styles.inline || !path) {
            return null;
        }

        var inline = block.styles.inline;

        // Mapeo de rutas de configuración a propiedades CSS
        var pathToCssMap = {
            'padding.superior': 'padding-top',
            'padding.derecha': 'padding-right',
            'padding.inferior': 'padding-bottom',
            'padding.izquierda': 'padding-left',
            'height': 'height',
            'alineacion': 'text-align',
            'maxAncho': 'max-width',
            'fondo': 'background'
        };

        var cssProp = pathToCssMap[path];
        if (cssProp && inline[cssProp] !== undefined) {
            return inline[cssProp];
        }

        return null;
    }

    function getDefaultValueForPath(block, path) {
        if (!block || !path) { return null; }

        var defaults = getRoleDefaults(block.role);
        if (!defaults || !defaults.config) { return null; }

        var segments = path.split('.');
        var cursor = defaults.config;
        for (var i = 0; i < segments.length; i += 1) {
            if (cursor === null || cursor === undefined) { return null; }
            cursor = cursor[segments[i]];
        }
        return cursor;
    }

    function updateConfigValue(block, path, value) {
        if (!block || !path) { return; }

        var current = cloneConfig(block.config);
        var segments = path.split('.');
        var cursor = current;

        // Si el valor está vacío, intentar usar el valor inline o por defecto
        if (value === null || value === undefined || value === '') {
            var inlineValue = getInlineValueForPath(block, path);
            if (inlineValue !== null) {
                value = inlineValue;
            } else {
                var defaultValue = getDefaultValueForPath(block, path);
                if (defaultValue !== null && defaultValue !== undefined) {
                    value = defaultValue;
                }
            }
        }

        for (var i = 0; i < segments.length - 1; i += 1) {
            var key = segments[i];
            var existing = cursor[key];
            if (!existing || typeof existing !== 'object' || Array.isArray(existing)) {
                existing = {};
            } else {
                existing = utils.assign({}, existing);
            }
            cursor[key] = existing;
            cursor = existing;
        }
        cursor[segments[segments.length - 1]] = value;
        var updated = state.updateConfig(block.id, current);
        applyBlockStyles(updated);
        activeBlock = updated;
        flashPanelStatus('Cambios aplicados');

        // Notificar cambio de configuración para habilitar botón guardar en Dock
        var event;
        if (typeof global.CustomEvent === 'function') {
            event = new CustomEvent('gbn:configChanged', { detail: { id: block.id } });
        } else {
            event = document.createEvent('CustomEvent');
            event.initCustomEvent('gbn:configChanged', false, false, { id: block.id });
        }
        global.dispatchEvent(event);

        // Refrescar panel si el campo modificado puede afectar condiciones
        var conditionalFields = ['layout']; // Campos que pueden mostrar/ocultar otros campos
        if (conditionalFields.indexOf(path) !== -1) {
            renderBlockControls(updated);
        }
    }

    function extractSpacingStyles(spacingConfig) {
        var styles = {};
        if (!spacingConfig || typeof spacingConfig !== 'object') { return styles; }
        var map = { superior: 'padding-top', derecha: 'padding-right', inferior: 'padding-bottom', izquierda: 'padding-left' };
        Object.keys(map).forEach(function (key) {
            var raw = spacingConfig[key];
            if (raw === null || raw === undefined || raw === '') { return; }
            if (typeof raw === 'number') { styles[map[key]] = raw + 'px'; }
            else { styles[map[key]] = raw; }
        });
        return styles;
    }

    var styleResolvers = {
        principal: function (config) {
            var styles = extractSpacingStyles(config.padding);
            if (config.height && config.height !== 'auto') {
                if (config.height === 'min-content') {
                    styles['height'] = 'min-content';
                } else if (config.height === '100vh') {
                    styles['height'] = '100vh';
                }
            }
            if (config.alineacion && config.alineacion !== 'inherit') { styles['text-align'] = config.alineacion; }
            if (config.maxAncho !== null && config.maxAncho !== undefined && config.maxAncho !== '') {
                var max = parseFloat(config.maxAncho);
                styles['max-width'] = !isNaN(max) ? max + 'px' : String(config.maxAncho);
            }
            if (config.fondo) { styles.background = config.fondo; }
            return styles;
        },
        secundario: function (config) {
            var styles = extractSpacingStyles(config.padding);
            if (config.height && config.height !== 'auto') {
                if (config.height === 'min-content') {
                    styles['height'] = 'min-content';
                } else if (config.height === '100vh') {
                    styles['height'] = '100vh';
                }
            }
            if (config.gap !== null && config.gap !== undefined && config.gap !== '') {
                var gap = parseFloat(config.gap);
                if (!isNaN(gap)) { styles.gap = gap + 'px'; }
            }
            if (config.layout) {
                if (config.layout === 'grid') {
                    styles.display = 'grid';
                    if (config.gridColumns) {
                        styles['grid-template-columns'] = 'repeat(' + config.gridColumns + ', 1fr)';
                    }
                    if (config.gridRows && config.gridRows !== 'auto') {
                        styles['grid-template-rows'] = config.gridRows;
                    }
                } else if (config.layout === 'flex') {
                    styles.display = 'flex';
                    if (config.flexDirection) { styles['flex-direction'] = config.flexDirection; }
                    if (config.flexWrap) { styles['flex-wrap'] = config.flexWrap; }
                    if (config.flexJustify) { styles['justify-content'] = config.flexJustify; }
                    if (config.flexAlign) { styles['align-items'] = config.flexAlign; }
                } else {
                    styles.display = 'block';
                }
            }
            return styles;
        },
        content: function () { return {}; }
    };

    function applyBlockStyles(block) {
        if (!block || !styleManager || !styleManager.update) { return; }
        var resolver = styleResolvers[block.role] || function () { return {}; };
        var computedStyles = resolver(block.config || {}, block) || {};
        styleManager.update(block, computedStyles);
    }

    function createSummary(block) {
        var summary = document.createElement('div');
        summary.className = 'gbn-panel-block-summary';
        var idLabel = document.createElement('p');
        idLabel.className = 'gbn-panel-block-id';
        idLabel.innerHTML = 'ID: <code>' + block.id + '</code>';
        summary.appendChild(idLabel);
        var roleLabel = document.createElement('p');
        roleLabel.className = 'gbn-panel-block-role';
        roleLabel.innerHTML = 'Rol: <strong>' + (block.role || 'block') + '</strong>';
        summary.appendChild(roleLabel);
        if (block.meta && block.meta.postType) {
            var typeLabel = document.createElement('p');
            typeLabel.className = 'gbn-panel-block-type';
            typeLabel.textContent = 'Contenido: ' + block.meta.postType;
            summary.appendChild(typeLabel);
        }
        return summary;
    }

    function renderBlockControls(block) {
        if (!panelBody) { return; }
        panelBody.innerHTML = ''; panelForm = null; panelBody.appendChild(createSummary(block));
        var schema = Array.isArray(block.schema) ? block.schema : [];
        if (!schema.length) {
            var empty = document.createElement('div'); empty.className = 'gbn-panel-coming-soon'; empty.textContent = 'Este bloque aún no expone controles editables.';
            panelBody.appendChild(empty); setPanelStatus('Sin controles disponibles'); return;
        }
        panelForm = document.createElement('form'); panelForm.className = 'gbn-panel-form';
        var builder = Gbn.ui && Gbn.ui.panelFields && Gbn.ui.panelFields.buildField;
        schema.forEach(function (field) { var control = builder ? builder(block, field) : null; if (control) { panelForm.appendChild(control); } });
        panelBody.appendChild(panelForm); setPanelStatus('Edita las opciones y se aplicarán al instante');
    }

    function renderPageSettingsForm(settings) {
        if (!panelBody) return;
        panelBody.innerHTML = '';
        panelForm = document.createElement('form');
        panelForm.className = 'gbn-panel-form';
        
        var builder = Gbn.ui && Gbn.ui.panelFields && Gbn.ui.panelFields.buildField;
        if (!builder) {
            panelBody.innerHTML = 'Error: panelFields no disponible';
            return;
        }

        // Schema para configuración de página
        var schema = [
            { type: 'color', key: 'background', label: 'Color de Fondo (Main)', default: '#ffffff' },
            { type: 'spacing', key: 'padding', label: 'Padding (Main)', default: 20 }
        ];

        // Mock block object for builder context
        var mockBlock = {
            id: 'page-settings',
            role: 'page',
            config: settings || {}
        };

        schema.forEach(function(field) {
            var control = builder(mockBlock, field);
            if (control) {
                // Interceptar cambios para guardar en settings local y habilitar guardado global
                var input = control.querySelector('input, select, textarea');
                if (input) {
                    input.addEventListener('change', function() {
                        // Actualizar settings localmente (mockBlock.config se actualiza por referencia en algunos casos, 
                        // pero panelFields suele llamar a updateConfigValue. Necesitamos interceptar eso o usar el mock.)
                        // panelFields usa Gbn.ui.panelApi.updateConfigValue.
                        // Como mockBlock no está en state, updateConfigValue fallará o no hará nada útil para persistencia global.
                        // Necesitamos un mecanismo para guardar estos settings específicos.
                        
                        // Solución rápida: Botón guardar explícito en el footer para página/tema.
                        // panel.js ya tiene un listener en el footer que llama a Gbn.persistence.savePageConfig().
                        // Pero savePageConfig guarda BLOQUES. Aquí queremos guardar SETTINGS.
                        // Necesitamos cambiar el comportamiento del botón guardar según el modo.
                    });
                }
                panelForm.appendChild(control);
            }
        });

        panelBody.appendChild(panelForm);
        
        // Actualizar comportamiento del botón guardar
        if (panelFooter) {
            panelFooter.onclick = null; // Limpiar listeners anteriores (hacky, mejor usar removeEventListener si se tuviera referencia)
            // Recrear el botón para limpiar listeners es más seguro
            var newFooterBtn = panelFooter.cloneNode(true);
            panelFooter.parentNode.replaceChild(newFooterBtn, panelFooter);
            panelFooter = newFooterBtn;
            
            panelFooter.disabled = false;
            panelFooter.textContent = 'Guardar Configuración';
            panelFooter.addEventListener('click', function(e) {
                e.preventDefault();
                setPanelStatus('Guardando...');
                
                // Recolectar valores del form (o usar mockBlock.config si logramos que se actualice)
                // Dado que panelFields intenta actualizar state, y mockBlock no está en state, 
                // panelFields podría fallar o no actualizar nada.
                // Necesitamos que panelFields soporte un modo "standalone" o "callback".
                // O simplemente leer los valores del DOM aquí.
                
                var newSettings = {};
                // Leer valores manualmente por ahora (simplificado)
                // Esto es frágil si los controles son complejos.
                // Mejor opción: Sobrescribir panelApi.updateConfigValue temporalmente.
                
                // Vamos a confiar en que panelFields actualizó mockBlock.config SI panelApi lo permite.
                // Pero panelApi.updateConfigValue busca el bloque en state.
                
                // ESTRATEGIA: Leer los inputs del form.
                // Asumimos inputs simples por ahora.
                var inputs = panelForm.querySelectorAll('[data-gbn-key]');
                inputs.forEach(function(inp) {
                    var key = inp.getAttribute('data-gbn-key');
                    var val = inp.value;
                    if (key) newSettings[key] = val;
                });
                
                // Para spacing y color complejos, esto puede no bastar.
                // Vamos a iterar sobre el schema y buscar los valores en mockBlock.config
                // Pero necesitamos que panelFields escriba en mockBlock.config.
                
                // HACK: Monkey-patch panelApi.updateConfigValue para este contexto
                
                Gbn.persistence.savePageSettings(mockBlock.config).then(function(res) {
                    if (res && res.success) {
                        setPanelStatus('Guardado');
                        // Aplicar cambios visuales inmediatos (opcional, si queremos live preview real)
                        applyPageSettings(mockBlock.config);
                    } else {
                        setPanelStatus('Error al guardar');
                    }
                });
            });
        }
        
        // Monkey-patch updateConfigValue para actualizar nuestro mockBlock local
        var originalUpdate = Gbn.ui.panelApi.updateConfigValue;
        Gbn.ui.panelApi.updateConfigValue = function(block, path, value) {
            if (block.id === 'page-settings') {
                // Actualizar mockBlock.config
                var segments = path.split('.');
                var cursor = mockBlock.config;
                for (var i = 0; i < segments.length - 1; i++) {
                    if (!cursor[segments[i]]) cursor[segments[i]] = {};
                    cursor = cursor[segments[i]];
                }
                cursor[segments[segments.length - 1]] = value;
                
                // Live preview
                applyPageSettings(mockBlock.config);
                return;
            }
            return originalUpdate(block, path, value);
        };
    }

    function applyPageSettings(settings) {
        var root = document.querySelector('[data-gbn-root]');
        if (!root) return;
        
        if (settings.background) {
            root.style.backgroundColor = settings.background;
        }
        if (settings.padding) {
            // Asumiendo padding simple o objeto
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

    function renderThemeSettingsForm(settings) {
        if (!panelBody) return;
        panelBody.innerHTML = '';
        
        // Crear Tabs
        var tabsContainer = document.createElement('div');
        tabsContainer.className = 'gbn-panel-tabs';
        var tabButtons = ['Texto', 'Colores', 'Páginas'];
        var activeTab = 'Texto';
        
        var tabsHeader = document.createElement('div');
        tabsHeader.className = 'gbn-tabs-header';
        
        var tabsContent = document.createElement('div');
        tabsContent.className = 'gbn-tabs-content';
        
        var mockBlock = {
            id: 'theme-settings',
            role: 'theme',
            config: settings || {}
        };
        
        // Función para renderizar contenido de tab
        function renderTabContent(tabName) {
            tabsContent.innerHTML = '';
            var schema = [];
            
            if (tabName === 'Texto') {
                schema = [
                    { type: 'header', label: 'Párrafos (p)' },
                    { type: 'select', key: 'text.p.font', label: 'Fuente', options: ['Inter', 'Roboto', 'Open Sans', 'System'] },
                    { type: 'text', key: 'text.p.size', label: 'Tamaño Base (px)', default: '16' },
                    { type: 'color', key: 'text.p.color', label: 'Color Texto', default: '#333333' },
                    
                    { type: 'header', label: 'Encabezados (h1)' },
                    { type: 'text', key: 'text.h1.size', label: 'Tamaño H1 (px)', default: '32' },
                    { type: 'color', key: 'text.h1.color', label: 'Color H1', default: '#111111' }
                ];
            } else if (tabName === 'Colores') {
                schema = [
                    { type: 'header', label: 'Paleta Global' },
                    { type: 'color', key: 'colors.primary', label: 'Primario', default: '#007bff' },
                    { type: 'color', key: 'colors.secondary', label: 'Secundario', default: '#6c757d' },
                    { type: 'color', key: 'colors.accent', label: 'Acento', default: '#28a745' },
                    { type: 'color', key: 'colors.background', label: 'Fondo Body', default: '#f8f9fa' }
                ];
            } else if (tabName === 'Páginas') {
                schema = [
                    { type: 'header', label: 'Defaults de Página' },
                    { type: 'color', key: 'pages.background', label: 'Fondo Default', default: '#ffffff' },
                    { type: 'spacing', key: 'pages.padding', label: 'Padding Default', default: 20 }
                ];
            }
            
            var builder = Gbn.ui && Gbn.ui.panelFields && Gbn.ui.panelFields.buildField;
            if (builder) {
                schema.forEach(function(field) {
                    var control = builder(mockBlock, field);
                    if (control) tabsContent.appendChild(control);
                });
            }
        }
        
        tabButtons.forEach(function(name) {
            var btn = document.createElement('button');
            btn.className = 'gbn-tab-btn' + (name === activeTab ? ' active' : '');
            btn.textContent = name;
            btn.onclick = function(e) {
                e.preventDefault();
                activeTab = name;
                // Update UI classes
                Array.from(tabsHeader.children).forEach(function(b) { b.classList.remove('active'); });
                btn.classList.add('active');
                renderTabContent(name);
            };
            tabsHeader.appendChild(btn);
        });
        
        tabsContainer.appendChild(tabsHeader);
        tabsContainer.appendChild(tabsContent);
        panelBody.appendChild(tabsContainer);
        
        // Render inicial
        renderTabContent(activeTab);
        
        // Configurar botón guardar
        if (panelFooter) {
            var newFooterBtn = panelFooter.cloneNode(true);
            panelFooter.parentNode.replaceChild(newFooterBtn, panelFooter);
            panelFooter = newFooterBtn;
            
            panelFooter.disabled = false;
            panelFooter.textContent = 'Guardar Tema';
            panelFooter.addEventListener('click', function(e) {
                e.preventDefault();
                setPanelStatus('Guardando...');
                
                Gbn.persistence.saveThemeSettings(mockBlock.config).then(function(res) {
                    if (res && res.success) {
                        setPanelStatus('Tema Guardado');
                        // Aquí podríamos recargar la página o inyectar CSS global nuevo
                    }
                });
            });
        }
        
        // Monkey-patch updateConfigValue para theme
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
                return;
            }
            return originalUpdate(block, path, value);
        };
    }

    // ...

    var panel = {
        init: function () { ensurePanelMounted(); },
        isOpen: function () { return !!(panelRoot && panelRoot.classList.contains('is-open')); },
        open: function (block) {
            ensurePanelMounted(); setActiveBlock(block || null); panelMode = block ? 'block' : 'idle';
            if (panelRoot) { 
                panelRoot.classList.add('is-open'); 
                panelRoot.setAttribute('aria-hidden', 'false'); 
                
                // Reset classes
                panelRoot.classList.remove('gbn-panel-primary', 'gbn-panel-secondary', 'gbn-panel-component', 'gbn-panel-theme', 'gbn-panel-page');
                
                if (block) {
                    if (block.role === 'principal') {
                        panelRoot.classList.add('gbn-panel-primary');
                    } else if (block.role === 'secundario') {
                        panelRoot.classList.add('gbn-panel-secondary');
                    } else {
                        panelRoot.classList.add('gbn-panel-component');
                    }
                }
            }
            var title = 'GBN Panel';
            if (block) { if (block.meta && block.meta.label) { title = block.meta.label; } else if (block.role) { title = 'Configuración: ' + block.role; } }
            if (panelTitle) { panelTitle.textContent = title; }
            if (!panelBody) { return; }
            if (!block) { renderPlaceholder(); return; }
            renderBlockControls(block);
            utils.debug('Panel abierto', block ? block.id : null);
        },
        openTheme: function () {
            ensurePanelMounted(); setActiveBlock(null); panelMode = 'theme';
            if (panelRoot) { 
                panelRoot.classList.add('is-open'); 
                panelRoot.setAttribute('aria-hidden', 'false');
                panelRoot.classList.remove('gbn-panel-primary', 'gbn-panel-secondary', 'gbn-panel-component', 'gbn-panel-page');
                panelRoot.classList.add('gbn-panel-theme');
            }
            if (panelTitle) { panelTitle.textContent = 'Configuración del Tema'; }
            
            if (panelBody) {
                panelBody.innerHTML = '<div class="gbn-panel-loading">Cargando configuración...</div>';
                panelForm = null;
                setPanelStatus('Cargando...');

                if (Gbn.persistence && typeof Gbn.persistence.getThemeSettings === 'function') {
                    Gbn.persistence.getThemeSettings().then(function(res) {
                        if (res && res.success) {
                            var settings = res.data || {};
                            renderThemeSettingsForm(settings);
                            setPanelStatus('Listo');
                        } else {
                            panelBody.innerHTML = '<div class="gbn-panel-error">Error al cargar configuración.</div>';
                            setPanelStatus('Error');
                        }
                    }).catch(function() {
                        panelBody.innerHTML = '<div class="gbn-panel-error">Error de conexión.</div>';
                        setPanelStatus('Error');
                    });
                } else {
                    panelBody.innerHTML = '<div class="gbn-panel-error">Persistencia no disponible.</div>';
                }
            }
        },
        openPage: function () {
            ensurePanelMounted(); setActiveBlock(null); panelMode = 'page';
            if (panelRoot) { 
                panelRoot.classList.add('is-open'); 
                panelRoot.setAttribute('aria-hidden', 'false');
                panelRoot.classList.remove('gbn-panel-primary', 'gbn-panel-secondary', 'gbn-panel-component', 'gbn-panel-theme');
                panelRoot.classList.add('gbn-panel-page');
            }
            if (panelTitle) { panelTitle.textContent = 'Configuración de Página'; }
            
            if (panelBody) {
                panelBody.innerHTML = '<div class="gbn-panel-loading">Cargando configuración...</div>';
                panelForm = null;
                setPanelStatus('Cargando...');

                if (Gbn.persistence && typeof Gbn.persistence.getPageSettings === 'function') {
                    Gbn.persistence.getPageSettings().then(function(res) {
                        if (res && res.success) {
                            var settings = res.data || {};
                            renderPageSettingsForm(settings);
                            setPanelStatus('Listo');
                        } else {
                            panelBody.innerHTML = '<div class="gbn-panel-error">Error al cargar configuración.</div>';
                            setPanelStatus('Error');
                        }
                    }).catch(function() {
                        panelBody.innerHTML = '<div class="gbn-panel-error">Error de conexión.</div>';
                        setPanelStatus('Error');
                    });
                } else {
                    panelBody.innerHTML = '<div class="gbn-panel-error">Persistencia no disponible.</div>';
                }
            }
        },
        openRestore: function () {
            ensurePanelMounted(); setActiveBlock(null); panelMode = 'restore';
            if (panelRoot) { panelRoot.classList.add('is-open'); panelRoot.setAttribute('aria-hidden', 'false'); }
            if (panelTitle) { panelTitle.textContent = 'Restaurar valores'; }
            
            if (panelBody) {
                panelBody.innerHTML = '';
                
                var container = document.createElement('div');
                container.className = 'gbn-panel-restore';
                container.style.padding = '20px';
                
                var desc = document.createElement('p');
                desc.textContent = 'Esta acción eliminará todas las configuraciones personalizadas de GBN para esta página y restaurará el contenido original definido en el código. Esta acción no se puede deshacer.';
                desc.style.marginBottom = '20px';
                desc.style.fontSize = '13px';
                desc.style.lineHeight = '1.5';
                desc.style.color = '#666';
                
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'gbn-btn-primary gbn-btn-danger';
                btn.textContent = 'Restaurar Defaults';
                btn.style.width = '100%';
                btn.style.padding = '10px';
                btn.style.backgroundColor = '#d9534f';
                btn.style.color = '#fff';
                btn.style.border = 'none';
                btn.style.borderRadius = '4px';
                btn.style.cursor = 'pointer';
                
                btn.addEventListener('click', function() {
                    // Confirmación eliminada por solicitud del usuario
                    
                    btn.disabled = true;
                    btn.textContent = 'Restaurando...';
                    setPanelStatus('Restaurando...');
                    
                    if (Gbn.persistence && typeof Gbn.persistence.restorePage === 'function') {
                        Gbn.persistence.restorePage().then(function(res) {
                            if (res && res.success) {
                                setPanelStatus('Restaurado. Recargando...');
                                setTimeout(function() {
                                    window.location.reload();
                                }, 500);
                            } else {
                                setPanelStatus('Error al restaurar');
                                btn.disabled = false;
                                btn.textContent = 'Restaurar Defaults';
                            }
                        }).catch(function() {
                            setPanelStatus('Error de conexión');
                            btn.disabled = false;
                            btn.textContent = 'Restaurar Defaults';
                        });
                    }
                });
                
                container.appendChild(desc);
                container.appendChild(btn);
                panelBody.appendChild(container);
            }
            
            panelForm = null;
            setPanelStatus('Esperando confirmación');
        },
        close: function () {
            if (panelRoot) { panelRoot.classList.remove('is-open'); panelRoot.setAttribute('aria-hidden', 'true'); }
            setActiveBlock(null); renderPlaceholder(); utils.debug('Panel cerrado');
        }
    };

    panel._applyStyles = applyBlockStyles;

    // Exponer API mínima para consumo desde panel-fields.js
    var panelApi = {
        getActiveBlock: function () { return activeBlock; },
        updateConfigValue: function (block, path, value) { return updateConfigValue(block, path, value); },
        flashStatus: function (text) { return flashPanelStatus(text); },
        applyBlockStyles: function (block) { return applyBlockStyles(block); }
    };

    Gbn.ui.panelApi = panelApi;

    Gbn.ui = Gbn.ui || {};
    Gbn.ui.panel = panel;
})(window);


