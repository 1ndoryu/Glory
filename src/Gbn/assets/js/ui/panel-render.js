;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    var utils = Gbn.utils;
    var state = Gbn.state;
    var styleManager = Gbn.styleManager;

    var shared = Gbn.ui.renderers.shared;
    var cloneConfig = shared.cloneConfig;
    var getThemeSettingsValue = shared.getThemeSettingsValue;
    var getConfigWithThemeFallback = shared.getConfigWithThemeFallback;
    
    // Fase 10: Variables de Estado
    var currentEditingState = 'normal'; // 'normal', 'hover', 'focus'
    var lastBlockId = null;
    var lastActiveTab = null; // [FIX] Persistir pestaña activa entre refrescos
    var fieldUtils = Gbn.ui.fieldUtils || {};
    var CONFIG_TO_CSS_MAP = fieldUtils.CONFIG_TO_CSS_MAP || {};


    


    function updateConfigValue(block, path, value) {
        if (!block || !path) { return; }

        // --- FASE 10: ESCRITURA DE ESTADOS (Hover/Focus) ---
        if (currentEditingState !== 'normal') {
            // Mapear el path de config (ej: 'typography.size') a propiedad CSS (ej: 'fontSize')
            var map = Gbn.ui.fieldUtils ? Gbn.ui.fieldUtils.CONFIG_TO_CSS_MAP : {};
            var cssProp = map[path];
            
            // [BUG FIX] Manejo explícito para spacing (padding.*, margin.*)
            // Estos paths usan nombres en español que deben traducirse a CSS camelCase
            if (!cssProp && (path.indexOf('padding.') === 0 || path.indexOf('margin.') === 0)) {
                var parts = path.split('.');
                var spType = parts[0]; // 'padding' o 'margin'
                var dir = parts[1]; // 'superior', 'derecha', 'inferior', 'izquierda'
                
                var dirMap = {
                    'superior': 'Top',
                    'derecha': 'Right',
                    'inferior': 'Bottom',
                    'izquierda': 'Left'
                };
                
                if (dirMap[dir]) {
                    cssProp = spType + dirMap[dir]; // 'paddingTop', 'marginRight', etc.
                }
            }
            
            // Si no hay mapeo explícito, usar el último segmento del path
            // Esto permite que propiedades directas como 'color', 'transform' funcionen
            if (!cssProp) {
                var segments = path.split('.');
                cssProp = segments[segments.length - 1];
            }

            if (Gbn.services.stateStyles && Gbn.services.stateStyles.setStateProperty) {
                // Guardar en config._states (esto también actualiza el state store)
                Gbn.services.stateStyles.setStateProperty(block, currentEditingState, cssProp, value);
                
                // Obtener el bloque actualizado del store
                var updatedBlock = state.get(block.id);
                
                // Asegurar que el elemento tenga la clase de simulación
                // para que los estilos CSS se muestren inmediatamente
                var targetElement = (updatedBlock && updatedBlock.element) || block.element;
                if (targetElement) {
                    var simClass = 'gbn-simulated-' + currentEditingState;
                    if (!targetElement.classList.contains(simClass)) {
                        targetElement.classList.add(simClass);
                    }
                }
                
                // Construir los estilos del estado directamente para aplicar visualmente
                var stateStyles = {};
                stateStyles[cssProp] = value;
                
                // Si hay más propiedades guardadas en el estado, incluirlas
                if (updatedBlock && updatedBlock.config && updatedBlock.config._states && 
                    updatedBlock.config._states[currentEditingState]) {
                    stateStyles = Object.assign({}, updatedBlock.config._states[currentEditingState]);
                }
                
                // Aplicar visualmente
                var targetBlock = updatedBlock || block;
                if (styleManager && styleManager.applyStateCss && targetBlock.id) {
                    styleManager.applyStateCss(targetBlock, currentEditingState, stateStyles);
                }
                
                // Disparar evento de cambio de configuración para activar el botón de guardar
                var event;
                if (typeof global.CustomEvent === 'function') {
                    event = new CustomEvent('gbn:configChanged', { detail: { id: block.id, state: currentEditingState } });
                } else {
                    event = document.createEvent('CustomEvent');
                    event.initCustomEvent('gbn:configChanged', false, false, { id: block.id, state: currentEditingState });
                }
                global.dispatchEvent(event);
                
                // Notificar cambio visual
                if (Gbn.ui.panel && Gbn.ui.panel.flashStatus) {
                    Gbn.ui.panel.flashStatus('Cambio en ' + currentEditingState + ' aplicado');
                }
                
                return updatedBlock || block;
            }
        }

        // 1. Delegate to role-specific update handler FIRST
        // This is critical for Theme Settings which has its own responsive logic
        var role = block.id === 'page-settings' ? 'pageSettings' : 
                   block.id === 'theme-settings' ? 'themeSettings' : 
                   block.role;
                   
        if (role && Gbn.ui.renderers[role] && Gbn.ui.renderers[role].handleUpdate) {
            if (Gbn.log) Gbn.log.info('Delegating update to renderer', { role: role, path: path });
            var result = Gbn.ui.renderers[role].handleUpdate(block, path, value);
            if (result === true) {
                if (Gbn.log) Gbn.log.info('Renderer handled update completely', { role: role });
                
                // Los renderers de themeSettings y pageSettings manejan su propia persistencia
                // y disparan sus propios eventos, así que no necesitamos hacer nada más aquí.
                var selfManagedRenderers = ['themeSettings', 'pageSettings'];
                if (selfManagedRenderers.indexOf(role) !== -1) {
                    // Estos renderers ya guardaron la config y dispararon el evento
                    return state.get(block.id);
                }
                
                // [FIX] El renderer aplicó los estilos al DOM, pero TAMBIÉN necesitamos
                // guardar el valor en la config del state para que:
                // 1. El valor persista al guardar
                // 2. Los campos condicionados (como opciones de borde) puedan evaluar sus condiciones
                // [BUG FIX] Usar bloque fresco del store para evitar pérdida de datos
                var freshBlock = state.get(block.id) || block;
                var current = cloneConfig(freshBlock.config);
                var segments = path.split('.');
                var cursor = current;
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
                
                // [FIX] Bug Regresión: El botón guardar no se activaba cuando el renderer
                // manejaba la actualización porque no se disparaba el evento configChanged.
                var event;
                if (typeof global.CustomEvent === 'function') {
                    event = new CustomEvent('gbn:configChanged', { detail: { id: block.id, path: path } });
                } else {
                    event = document.createEvent('CustomEvent');
                    event.initCustomEvent('gbn:configChanged', false, false, { id: block.id, path: path });
                }
                global.dispatchEvent(event);
                
                // Refrescar panel si es un campo que afecta la visibilidad de otros campos
                // (como hasBorder que controla la visibilidad de borderWidth, borderStyle, etc.)
                // [FIX] Agregado 'fieldType' para PostField - cambia las opciones visibles según el tipo
                var conditionalTriggers = ['hasBorder', 'layout', 'display_mode', 'img_show', 'title_show', 'interaccion_modo', 'fieldType'];
                if (conditionalTriggers.indexOf(path) !== -1) {
                    if (Gbn.ui.panel && Gbn.ui.panel.refreshControls) {
                        Gbn.ui.panel.refreshControls(updated);
                    }
                }
                
                return updated; // Return updated block
            } else if (typeof result === 'object') {
                if (Gbn.log) Gbn.log.info('Renderer returned updated config', { role: role });
                
                // [FIX] También disparar evento cuando se retorna un objeto
                var event;
                if (typeof global.CustomEvent === 'function') {
                    event = new CustomEvent('gbn:configChanged', { detail: { id: block.id, path: path } });
                } else {
                    event = document.createEvent('CustomEvent');
                    event.initCustomEvent('gbn:configChanged', false, false, { id: block.id, path: path });
                }
                global.dispatchEvent(event);
                
                return result;
            }
        } else {
             if (Gbn.log && role === 'themeSettings') Gbn.log.warn('Theme Settings Renderer NOT found or has no handleUpdate', { role: role, available: !!(Gbn.ui.renderers[role]) });
        }

        // 2. Generic Responsive Logic: If not desktop, use setResponsiveValue
        var breakpoint = (Gbn.responsive && Gbn.responsive.getCurrentBreakpoint) ? Gbn.responsive.getCurrentBreakpoint() : 'desktop';
        
        if (breakpoint !== 'desktop' && Gbn.responsive && Gbn.responsive.setResponsiveValue) {
            // [BUG FIX] Mismo fix que abajo: usar bloque fresco del store
            var freshBlock = state.get(block.id) || block;
            var current = cloneConfig(freshBlock.config);
            
            // Usar helper para escribir en la estructura correcta dentro de current
            // Pasamos un objeto mock {config: current} porque setResponsiveValue espera block.config
            var mockBlock = { config: current };
            Gbn.responsive.setResponsiveValue(mockBlock, path, value, breakpoint);
            
            // Continuar con el flujo normal de guardado usando 'current' actualizado
            var updated = state.updateConfig(block.id, current);
            applyBlockStyles(updated);
            
            // Notify core
            if (Gbn.ui.panel && Gbn.ui.panel.updateActiveBlock) {
                Gbn.ui.panel.updateActiveBlock(updated);
            }
            
            if (Gbn.ui.panel && Gbn.ui.panel.flashStatus) {
                Gbn.ui.panel.flashStatus('Cambios aplicados (' + breakpoint + ')');
            }

            var event;
            if (typeof global.CustomEvent === 'function') {
                event = new CustomEvent('gbn:configChanged', { detail: { id: block.id } });
            } else {
                event = document.createEvent('CustomEvent');
                event.initCustomEvent('gbn:configChanged', false, false, { id: block.id });
            }
            global.dispatchEvent(event);

            var conditionalFields = ['layout', 'display_mode', 'img_show', 'title_show', 'interaccion_modo'];
            if (conditionalFields.indexOf(path) !== -1) {
                if (Gbn.ui.panel && Gbn.ui.panel.refreshControls) {
                    Gbn.ui.panel.refreshControls(updated);
                }
            }
            
            return updated;
        }

        // 3. Generic Desktop Logic
        // [BUG FIX] El bloque pasado a los campos del panel es una referencia capturada
        // en el momento de renderizado del panel. Esta referencia NO se actualiza cuando
        // el usuario hace cambios. Por lo tanto, debemos obtener el bloque fresco del store.
        // Sin este fix, al editar padding-top y luego padding-bottom, el segundo update
        // clonaba la config vieja (sin padding-top) causando pérdida de datos.
        var freshBlock = state.get(block.id) || block;
        var current = cloneConfig(freshBlock.config);
        var segments = path.split('.');
        var cursor = current;

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
        
        // Notify core to update active block
        if (Gbn.ui.panel && Gbn.ui.panel.updateActiveBlock) {
            Gbn.ui.panel.updateActiveBlock(updated);
        }
        
        if (Gbn.ui.panel && Gbn.ui.panel.flashStatus) {
            Gbn.ui.panel.flashStatus('Cambios aplicados');
        }

        var event;
        if (typeof global.CustomEvent === 'function') {
            event = new CustomEvent('gbn:configChanged', { detail: { id: block.id } });
        } else {
            event = document.createEvent('CustomEvent');
            event.initCustomEvent('gbn:configChanged', false, false, { id: block.id });
        }
        global.dispatchEvent(event);

        var conditionalFields = ['layout', 'display_mode', 'img_show', 'title_show', 'interaccion_modo'];
        if (conditionalFields.indexOf(path) !== -1) {
            if (Gbn.ui.panel && Gbn.ui.panel.refreshControls) {
                Gbn.ui.panel.refreshControls(updated);
            }
        }
        
        return updated;
    }

    var styleResolvers = {
        principal: function (config, block) {
            return Gbn.ui.renderers.principal ? Gbn.ui.renderers.principal.getStyles(config, block) : {};
        },
        secundario: function (config, block) {
            return Gbn.ui.renderers.secundario ? Gbn.ui.renderers.secundario.getStyles(config, block) : {};
        },
        content: function () { return {}; },
        text: function(config, block) { 
            return Gbn.ui.renderers.text ? Gbn.ui.renderers.text.getStyles(config, block) : {};
        },
        button: function(config, block) {
            return Gbn.ui.renderers.button ? Gbn.ui.renderers.button.getStyles(config, block) : {};
        },
        image: function(config, block) {
            return Gbn.ui.renderers.image ? Gbn.ui.renderers.image.getStyles(config, block) : {};
        },
        // Fase 13: PostRender - Contenido Dinámico
        postRender: function(config, block) {
            return Gbn.ui.renderers.postRender ? Gbn.ui.renderers.postRender.getStyles(config, block) : {};
        },
        postItem: function(config, block) {
            return Gbn.ui.renderers.postItem ? Gbn.ui.renderers.postItem.getStyles(config, block) : {};
        },
        postField: function(config, block) {
            return Gbn.ui.renderers.postField ? Gbn.ui.renderers.postField.getStyles(config, block) : {};
        },
        // Fase 14: Form Components
        form: function(config, block) {
            return Gbn.ui.renderers.form ? Gbn.ui.renderers.form.getStyles(config, block) : {};
        },
        input: function(config, block) {
            return Gbn.ui.renderers.input ? Gbn.ui.renderers.input.getStyles(config, block) : {};
        },
        textarea: function(config, block) {
            return Gbn.ui.renderers.textarea ? Gbn.ui.renderers.textarea.getStyles(config, block) : {};
        },
        select: function(config, block) {
            return Gbn.ui.renderers.select ? Gbn.ui.renderers.select.getStyles(config, block) : {};
        },
        submit: function(config, block) {
            return Gbn.ui.renderers.submit ? Gbn.ui.renderers.submit.getStyles(config, block) : {};
        },
        // Fase 15: Layout Components (Header, Footer, Menu, Logo)
        header: function(config, block) {
            return Gbn.ui.renderers.header ? Gbn.ui.renderers.header.getStyles(config, block) : {};
        },
        logo: function(config, block) {
            return Gbn.ui.renderers.logo ? Gbn.ui.renderers.logo.getStyles(config, block) : {};
        },
        menu: function(config, block) {
            return Gbn.ui.renderers.menu ? Gbn.ui.renderers.menu.getStyles(config, block) : {};
        },
        footer: function(config, block) {
            return Gbn.ui.renderers.footer ? Gbn.ui.renderers.footer.getStyles(config, block) : {};
        },
        menuItem: function(config, block) {
            return Gbn.ui.renderers.menuItem ? Gbn.ui.renderers.menuItem.getStyles(config, block) : {};
        }
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
        // [MOD] Usuario solicitó ocultar ID y Rol
        // var idLabel = document.createElement('p');
        // idLabel.className = 'gbn-panel-block-id';
        // idLabel.innerHTML = 'ID: <code>' + block.id + '</code>';
        // summary.appendChild(idLabel);
        // var roleLabel = document.createElement('p');
        // roleLabel.className = 'gbn-panel-block-role';
        // roleLabel.innerHTML = 'Rol: <strong>' + (block.role || 'block') + '</strong>';
        // summary.appendChild(roleLabel);
        if (block.meta && block.meta.postType) {
            var typeLabel = document.createElement('p');
            typeLabel.className = 'gbn-panel-block-type';
            typeLabel.textContent = 'Contenido: ' + block.meta.postType;
            summary.appendChild(typeLabel);
        }
        return summary;
    }

    /**
     * Renderiza el selector de estados (Normal, Hover, Focus)
     */
    function renderStateSelector(container, block) {
        var wrapper = document.createElement('div');
        wrapper.className = 'gbn-state-selector';
        // wrapper.style.marginBottom = '15px'; // [MOD] Removed margin as requested
        wrapper.style.width = '100%';

        var btnGroup = document.createElement('div');
        btnGroup.className = 'gbn-btn-group';
        btnGroup.style.display = 'flex';
        btnGroup.style.width = '100%';
        btnGroup.style.background = 'var(--gbn-bg-secondary, #f1f1f1)'; // Fondo gris suave contenedor
        btnGroup.style.padding = '3px';
        btnGroup.style.borderRadius = '6px';
        btnGroup.style.gap = '0'; // Sin espacio entre botones (controlado por padding contenedor)

        var states = [
            { 
                id: 'normal', 
                label: 'Normal',
                icon: '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3l7.07 16.97 2.51-7.39 7.39-2.51L3 3z"></path></svg>'
            },
            { 
                id: 'hover', 
                label: 'Hover',
                icon: '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 11V6a2 2 0 0 0-2-2v0a2 2 0 0 0-2 2v0"></path><path d="M14 10V4a2 2 0 0 0-2-2v0a2 2 0 0 0-2 2v6"></path><path d="M10 10.5V6a2 2 0 0 0-2-2v0a2 2 0 0 0-2 2v8"></path><path d="M18 8a2 2 0 1 1 4 0v6a8 8 0 0 1-8 8h-2c-2.8 0-4.5-.86-5.99-2.34l-3.6-3.6a2 2 0 0 1 2.83-2.82L7 15"></path></svg>'
            },
            { 
                id: 'focus', 
                label: 'Focus',
                icon: '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><circle cx="12" cy="12" r="3"></circle></svg>'
            }
        ];

        states.forEach(function(s) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.innerHTML = s.icon;
            btn.title = s.label;
            
            // Estilos base
            btn.style.flex = '1'; // Ocupar espacio igual
            btn.style.display = 'flex';
            btn.style.alignItems = 'center';
            btn.style.justifyContent = 'center';
            btn.style.padding = '6px 0';
            btn.style.cursor = 'pointer';
            btn.style.borderRadius = '4px';
            btn.style.border = 'none';
            btn.style.transition = 'all 0.2s ease';
            
            // Estilos de estado (Activo vs Inactivo)
            if (currentEditingState === s.id) {
                btn.className = 'gbn-btn-active'; // Clase marcador
                btn.style.background = '#ffffff';
                btn.style.color = '#2271b1'; // Color primario WP o del tema
                btn.style.boxShadow = '0 1px 3px rgba(0,0,0,0.1)';
            } else {
                btn.className = 'gbn-btn-inactive';
                btn.style.background = 'transparent';
                btn.style.color = '#646970';
                btn.style.boxShadow = 'none';
            }
            
            // Hover effect visual (JS-based para simplicidad inline)
            btn.onmouseenter = function() {
                if (currentEditingState !== s.id) {
                    btn.style.background = 'rgba(255,255,255,0.5)';
                    btn.style.color = '#1d2327';
                }
            };
            btn.onmouseleave = function() {
                if (currentEditingState !== s.id) {
                    btn.style.background = 'transparent';
                    btn.style.color = '#646970';
                }
            };
            
            btn.onclick = function() {
                if (currentEditingState === s.id) return;
                
                // Limpiar clases de simulación anteriores
                if (block.element) {
                    block.element.classList.remove('gbn-simulated-hover', 'gbn-simulated-focus');
                }
                
                currentEditingState = s.id;
                
                // Aplicar nueva clase de simulación y estilos del estado
                if (currentEditingState !== 'normal' && block.element) {
                    block.element.classList.add('gbn-simulated-' + currentEditingState);
                    
                    // Aplicar estilos CSS existentes del estado
                    if (block.config._states && block.config._states[currentEditingState]) {
                        if (styleManager && styleManager.applyStateCss) {
                            styleManager.applyStateCss(block, currentEditingState, block.config._states[currentEditingState]);
                        }
                    }
                }
                
                // Re-renderizar controles
                if (Gbn.ui.panel && Gbn.ui.panel.refreshControls) {
                    Gbn.ui.panel.refreshControls(block);
                }
                
                // Feedback visual
                if (Gbn.ui.panel && Gbn.ui.panel.flashStatus) {
                    Gbn.ui.panel.flashStatus('Editando estado: ' + s.label);
                }
            };
            
            btnGroup.appendChild(btn);
        });

        wrapper.appendChild(btnGroup);
        container.appendChild(wrapper);
    }

    function renderBlockControls(block, container) {
        if (!container) { return; }
        
        // Reset state if switching blocks
        if (block.id !== lastBlockId) {
            currentEditingState = 'normal';
            lastBlockId = block.id;
            lastActiveTab = null; // [FIX] Reset active tab for new block
            // Ensure simulation classes are cleared (safety)
            if (block.element) {
                block.element.classList.remove('gbn-simulated-hover', 'gbn-simulated-focus');
            }
        }
        
        // [FIX] Persistir scroll position
        var savedScrollTop = container.scrollTop;
        
        container.innerHTML = ''; 
        container.innerHTML = ''; 
        
        // [MOD] Locate Header and Footer containers
        var tabsContainer = document.querySelector('.gbn-header-tabs-area');
        var footerStatesContainer = document.querySelector('.gbn-footer-states-area');
        
        // Clean external containers
        if (tabsContainer) tabsContainer.innerHTML = '';
        if (footerStatesContainer) footerStatesContainer.innerHTML = '';

        // Renderizar selector de estados (Fase 10) en el FOOTER
        // Fase 13: Agregados postRender, postItem, postField a la lista de roles con soporte de estados
        // Fase 14: Agregados form, input, textarea, select, submit
        var supportedRoles = ['principal', 'secundario', 'text', 'button', 'image', 'postRender', 'postItem', 'postField', 'form', 'input', 'textarea', 'select', 'submit', 'header', 'logo', 'menu', 'footer', 'menuItem'];
        if (block.role && supportedRoles.indexOf(block.role) !== -1) {
            if (footerStatesContainer) {
                renderStateSelector(footerStatesContainer, block);
            }
        }
        
        var schema = Array.isArray(block.schema) ? block.schema : [];
        if (!schema.length) {
            var empty = document.createElement('div'); 
            empty.className = 'gbn-panel-coming-soon'; 
            empty.textContent = 'Este bloque aún no expone controles editables.';
            container.appendChild(empty); 
            if (Gbn.ui.panel && Gbn.ui.panel.setStatus) {
                Gbn.ui.panel.setStatus('Sin controles disponibles');
            }
            return;
        }

        // Group fields by tab
        var tabs = {};
        var hasTabs = false;
        var defaultTab = 'Contenido';

        schema.forEach(function(field) {
            var tabName = field.tab || defaultTab;
            if (field.tab) hasTabs = true;
            if (!tabs[tabName]) tabs[tabName] = [];
            tabs[tabName].push(field);
        });

        // Common Form Styling
        function applyFormStyles(form) {
            form.style.display = 'flex';
            form.style.flexDirection = 'column';
            form.style.gap = '12px';
            form.style.marginBottom = '100px'; // Prevent footer overlap
        }

        if (!hasTabs) {
            var form = document.createElement('form'); 
            form.className = 'gbn-panel-form';
            applyFormStyles(form);
            
            var builder = Gbn.ui && Gbn.ui.panelFields && Gbn.ui.panelFields.buildField;
            
            schema.forEach(function (field) { 
                var control = builder ? builder(block, field) : null; 
                if (control) { form.appendChild(control); } 
            });
            container.appendChild(form);
        } else {
            // Render Tabs
            var tabNav = document.createElement('div');
            tabNav.className = 'gbn-panel-tabs';
            // Apply requested styles for tabs container
            tabNav.style.display = 'flex';
            tabNav.style.gap = '4px';
            tabNav.style.padding = '10px';
            tabNav.style.paddingTop = '0';
            
            var tabContent = document.createElement('div');
            tabContent.className = 'gbn-panel-tabs-content';
            
            var tabNames = Object.keys(tabs);
            // Sort tabs: Contenido, Estilo, Avanzado, others...
            var order = ['Contenido', 'Estilo', 'Avanzado'];
            tabNames.sort(function(a, b) {
                var ia = order.indexOf(a);
                var ib = order.indexOf(b);
                if (ia !== -1 && ib !== -1) return ia - ib;
                if (ia !== -1) return -1;
                if (ib !== -1) return 1;
                return a.localeCompare(b);
            });

            // Icons mapping using IconRegistry (Phase 6)
            var getIcon = function(name) {
                var Icons = (global.GbnIcons || (typeof window !== 'undefined' ? window.GbnIcons : null));
                if (!Icons) return '';

                var map = {
                    'Contenido': 'tab.content',
                    'Configuración': 'tab.content', // Alias
                    'Estilo': 'tab.style',
                    'Interacción': 'tab.interaction',
                    'Layout': 'tab.layout',
                    'Query': 'tab.query',
                    'Avanzado': 'tab.advanced',
                    'Móvil': 'tab.mobile',
                    'Movil': 'tab.mobile'
                };
                
                var key = map[name];
                return key ? Icons.get(key) : ''; 
            };

            var activeTab = lastActiveTab && tabNames.indexOf(lastActiveTab) !== -1 ? lastActiveTab : tabNames[0];

            tabNames.forEach(function(name) {
                var btn = document.createElement('button');
                btn.className = 'gbn-tab-btn' + (name === activeTab ? ' active' : '');
                btn.type = 'button';
                btn.innerHTML = getIcon(name) + '<span>' + name + '</span>';
                btn.title = name; // Tooltip for inactive tabs
                btn.onclick = function() {
                    // Switch tab
                    var allBtns = tabNav.querySelectorAll('.gbn-tab-btn');
                    var allPanes = tabContent.querySelectorAll('.gbn-tab-pane');
                    
                    for(var i=0; i<allBtns.length; i++) allBtns[i].classList.remove('active');
                    for(var i=0; i<allPanes.length; i++) allPanes[i].classList.remove('active');
                    
                    btn.classList.add('active');
                    var pane = tabContent.querySelector('.gbn-tab-pane[data-tab="' + name + '"]');
                    if (pane) pane.classList.add('active');
                    
                    lastActiveTab = name; // [FIX] Save active tab state
                };
                tabNav.appendChild(btn);

                var pane = document.createElement('div');
                pane.className = 'gbn-tab-pane' + (name === activeTab ? ' active' : '');
                pane.setAttribute('data-tab', name);
                
                var form = document.createElement('form'); 
                form.className = 'gbn-panel-form';
                applyFormStyles(form);

                var builder = Gbn.ui && Gbn.ui.panelFields && Gbn.ui.panelFields.buildField;
                
                tabs[name].forEach(function (field) { 
                    var control = builder ? builder(block, field) : null; 
                    if (control) { form.appendChild(control); } 
                });
                
                pane.appendChild(form);
                tabContent.appendChild(pane);
            });

            // Append nav to header container if available, otherwise fallback
            if (tabsContainer) {
                tabsContainer.appendChild(tabNav);
            } else {
                container.appendChild(tabNav);
            }
            container.appendChild(tabContent);
        }
        
        if (Gbn.ui.panel && Gbn.ui.panel.setStatus) {
            Gbn.ui.panel.setStatus('Edita las opciones y se aplicarán al instante');
        }

        // [FIX] Restaurar scroll position
        if (savedScrollTop > 0) {
            // Usar setTimeout para asegurar que el DOM se ha renderizado
            setTimeout(function() {
                container.scrollTop = savedScrollTop;
            }, 0);
        }
    }

    /**
     * Aplica los nuevos defaults del tema a todos los bloques que no tienen override
     */
    function applyThemeDefaultsToBlocks(role, property, value) {
        if (!state || !state.all) return;
        
        var blocks = state.all();
        blocks.forEach(function(block) {
            if (block.role !== role) return;
            
            // Verificar si el bloque tiene un override para esta propiedad
            // Usamos getDefaultValueForPath para ver si hay valor configurado
            // Pero getDefaultValueForPath busca en defaults si no hay config.
            // Necesitamos chequear directamente block.config
            
            var hasOverride = false;
            var currentConfig = block.config || {};
            
            // Navegar por el objeto config para ver si existe la propiedad
            var segments = property.split('.');
            var cursor = currentConfig;
            for (var i = 0; i < segments.length; i++) {
                if (cursor === undefined || cursor === null) break;
                cursor = cursor[segments[i]];
            }
            
            // Si cursor tiene valor (y no es null/undefined/''), es un override
            if (cursor !== undefined && cursor !== null && cursor !== '') {
                hasOverride = true;
            }
            
            // Si NO tiene override, debemos re-aplicar estilos para que tome el nuevo default
            if (!hasOverride) {
                applyBlockStyles(block);
            }
        });
    }

    // Escuchar evento global
    if (typeof window !== 'undefined') {
        window.addEventListener('gbn:themeDefaultsChanged', function(e) {
            if (e.detail && e.detail.role) {
                applyThemeDefaultsToBlocks(e.detail.role, e.detail.property, e.detail.value);
            }
        });
        
        // Re-aplicar estilos cuando cambia el breakpoint para simular media queries en el editor
        window.addEventListener('gbn:breakpointChanged', function(e) {
            var bp = e.detail ? e.detail.current : 'desktop';
            
            // 1. Re-aplicar variables CSS del tema con el nuevo breakpoint
            if (Gbn.ui.theme.applicator && Gbn.ui.theme.applicator.applyThemeSettings) {
                // Obtener settings actuales (local o global)
                var settings = (Gbn.config && Gbn.config.themeSettings) || (gloryGbnCfg && gloryGbnCfg.themeSettings);
                if (settings) {
                    Gbn.ui.theme.applicator.applyThemeSettings(settings, bp);
                }
            }

            // 2. Re-aplicar estilos inline a bloques (si fuera necesario, aunque idealmente todo debería ser CSS vars)
            applyThemeStylesToAllBlocks(); 
            
            // 3. También actualizar el panel activo si hay uno seleccionado
            if (Gbn.ui.panel && Gbn.ui.panel.refreshControls && state.activeBlockId) {
                var block = state.get(state.activeBlockId);
                if (block) Gbn.ui.panel.refreshControls(block);
            }
        });
    }

    /**
     * Aplica los estilos basados en Theme Settings a todos los bloques existentes
     * Útil para aplicar cambios después de cargar Theme Settings del servidor
     */
    function applyThemeStylesToAllBlocks() {
        if (!state || !state.all) return;
        
        var blocks = state.all();
        blocks.forEach(function(block) {
            if (block.role === 'principal' || block.role === 'secundario') {
                applyBlockStyles(block);
            }
        });
    }

    Gbn.ui = Gbn.ui || {};
    Gbn.ui.panelRender = {
        renderBlockControls: renderBlockControls,
        updateConfigValue: updateConfigValue,
        applyBlockStyles: applyBlockStyles,
        applyThemeStylesToAllBlocks: applyThemeStylesToAllBlocks,
        getThemeSettingsValue: getThemeSettingsValue,
        getConfigWithThemeFallback: getConfigWithThemeFallback,
        getCurrentState: function() { return currentEditingState; }
    };

})(window);
