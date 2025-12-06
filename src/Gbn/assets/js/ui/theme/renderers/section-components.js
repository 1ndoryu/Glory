;(function(global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.theme = Gbn.ui.theme || {};
    Gbn.ui.theme.renderers = Gbn.ui.theme.renderers || {};

    /**
     * Renderizador de la sección Components de Theme Settings.
     * 
     * Responsabilidad: Lista de componentes y vista de detalle para configurar
     * estilos globales de cada tipo de componente.
     */

    /**
     * Renderiza la sección de componentes.
     * 
     * @param {HTMLElement} content - Contenedor de contenido
     * @param {Object} mockBlock - Mock block con config
     */
    function render(content, mockBlock) {
        var state = Gbn.ui.theme.state;
        var utils = Gbn.ui.theme.utils;
        
        // Contenedores para lista y detalle
        var componentListContainer = document.createElement('div');
        componentListContainer.className = 'gbn-component-list-view';
        
        var componentDetailContainer = document.createElement('div');
        componentDetailContainer.className = 'gbn-component-detail-view';
        componentDetailContainer.style.display = 'none';
        
        content.appendChild(componentListContainer);
        content.appendChild(componentDetailContainer);

        /**
         * Renderiza la lista de componentes disponibles
         */
        var renderComponentList = function() {
            componentListContainer.innerHTML = '';
            componentDetailContainer.style.display = 'none';
            componentListContainer.style.display = 'block';
            
            // Botón de restaurar (solo en modo dev)
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
            
            // Listar roles disponibles
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
                        
                        var chevronIcon = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>';
                        
                        item.innerHTML = '<span>' + (role.charAt(0).toUpperCase() + role.slice(1)) + '</span>' + 
                                         (defaults.icon || chevronIcon);
                        
                        item.onclick = function() {
                            renderComponentDetail(role);
                        };
                        list.appendChild(item);
                    }
                });
                componentListContainer.appendChild(list);
            }
        };

        /**
         * Renderiza el detalle de un componente específico
         * @param {string} role - Rol del componente
         */
        var renderComponentDetail = function(role) {
            state.setCurrentDetailRole(role);
            componentListContainer.style.display = 'none';
            componentDetailContainer.style.display = 'block';
            componentDetailContainer.innerHTML = '';
            
            // Header con botón de volver
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
                state.setCurrentDetailRole(null);
                renderComponentList();
            };
            
            var title = document.createElement('h4');
            title.textContent = role.charAt(0).toUpperCase() + role.slice(1);
            title.style.margin = '0';
            title.style.fontSize = '14px';
            
            detailHeader.appendChild(backBtn);
            detailHeader.appendChild(title);
            componentDetailContainer.appendChild(detailHeader);
            
            // Contenedor de campos
            var fieldsContainer = document.createElement('div');
            fieldsContainer.className = 'gbn-component-fields';
            componentDetailContainer.appendChild(fieldsContainer);
            
            /**
             * Renderiza los campos del componente
             */
            var renderFields = function() {
                fieldsContainer.innerHTML = '';
                
                var defaults = Gbn.content.roles.getRoleDefaults(role);
                if (!defaults || !defaults.schema) return;
                
                var builder = Gbn.ui && Gbn.ui.panelFields && Gbn.ui.panelFields.buildField;
                if (!builder) return;
                
                // Filtrar campos relevantes para estilos globales
                var allowedTypes = ['color', 'spacing', 'typography', 'slider', 'icon_group', 'select', 'text', 'fraction'];
                var relevantFields = defaults.schema.filter(function(f) {
                    return allowedTypes.indexOf(f.tipo) !== -1 && f.id !== 'texto' && f.id !== 'tag';
                });
                
                // Agrupar por tab
                var grouped = utils.groupFieldsByTab(relevantFields, 'Contenido');
                
                if (!grouped.hasTabs) {
                    // Sin tabs: lista plana
                    relevantFields.forEach(function(field) {
                        var f = Gbn.utils.assign({}, field);
                        f.id = 'components.' + role + '.' + f.id;
                        var control = builder(mockBlock, f);
                        if (control) fieldsContainer.appendChild(control);
                    });
                } else {
                    // Con tabs: usar utilidad de tabs
                    var tabsUI = utils.createTabsUI({
                        tabs: grouped.tabs,
                        builder: builder,
                        mockBlock: mockBlock,
                        fieldIdPrefix: 'components.' + role + '.',
                        tabOrder: ['Contenido', 'Estilo', 'Avanzado']
                    });
                    
                    fieldsContainer.appendChild(tabsUI.tabNav);
                    fieldsContainer.appendChild(tabsUI.tabContent);
                }
            };
            
            // Render inicial
            renderFields();
            
            // Listener para campos condicionales
            var conditionalFields = ['layout', 'display_mode'];
            
            var configChangeHandler = function(e) {
                if (!state.getComponentState().currentDetailRole) return;
                
                var detail = e.detail || {};
                if (detail.id === 'theme-settings') {
                    var path = detail.path || '';
                    var fieldId = path.split('.').pop();
                    
                    if (conditionalFields.indexOf(fieldId) !== -1) {
                        renderFields();
                    }
                }
            };
            
            // Registrar handler (limpia el anterior automáticamente)
            state.setActiveConfigChangeHandler(configChangeHandler);
            window.addEventListener('gbn:configChanged', configChangeHandler);
        };
        
        // Exponer renderComponentDetail para acceso desde listener
        state.setRenderComponentDetail(renderComponentDetail);
        
        // Render inicial - restaurar estado si existe
        var componentState = state.getComponentState();
        if (componentState.currentDetailRole) {
            renderComponentDetail(componentState.currentDetailRole);
        } else {
            renderComponentList();
        }
    }

    // Exportar
    Gbn.ui.theme.renderers.sectionComponents = {
        render: render
    };

})(window);
