;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.theme = Gbn.ui.theme || {};

    /**
     * Theme Settings Render - Orquestador Principal
     * 
     * Este módulo es el punto de entrada para renderizar:
     * - Page Settings Form (configuración de página individual)
     * - Theme Settings Form (configuración global del tema)
     * 
     * REFACTORIZACIÓN (Diciembre 2025):
     * La lógica se dividió en módulos separados siguiendo SRP:
     * - state.js: Gestión de estado global del módulo
     * - utils.js: Utilidades compartidas (tabs, iconos)
     * - renderers/: Renderizadores específicos para cada sección
     *   - page-settings.js: Formulario de página
     *   - menu.js: Menú principal de theme settings
     *   - section-text.js: Sección de tipografía
     *   - section-colors.js: Sección de colores
     *   - section-pages.js: Sección de páginas
     *   - section-components.js: Sección de componentes
     */

    /**
     * Renderiza el formulario de configuración de página.
     * Delega al renderer específico de page-settings.
     * 
     * @param {Object} settings - Configuración de la página
     * @param {HTMLElement} container - Contenedor donde renderizar
     * @param {HTMLElement} footer - Botón del footer
     */
    function renderPageSettingsForm(settings, container, footer) {
        var pageSettingsRenderer = Gbn.ui.theme.renderers && Gbn.ui.theme.renderers.pageSettings;
        
        if (pageSettingsRenderer && pageSettingsRenderer.render) {
            pageSettingsRenderer.render(settings, container, footer);
        } else {
            // Fallback si los módulos no están cargados
            console.warn('[Theme Render] Page Settings renderer not loaded');
            if (container) {
                container.innerHTML = '<p class="gbn-error">Error: Módulos de renderizado no disponibles</p>';
            }
        }
    }

    /**
     * Renderiza el formulario de configuración global del tema.
     * 
     * @param {Object} settings - Configuración del tema
     * @param {HTMLElement} container - Contenedor donde renderizar
     * @param {HTMLElement} footer - Botón del footer
     */
    function renderThemeSettingsForm(settings, container, footer) {
        if (!container) return;
        container.innerHTML = '';
        
        // Obtener referencias a módulos requeridos
        var state = Gbn.ui.theme.state;
        var renderers = Gbn.ui.theme.renderers;
        
        if (!state || !renderers) {
            console.warn('[Theme Render] Required modules not loaded');
            container.innerHTML = '<p class="gbn-error">Error: Módulos de renderizado no disponibles</p>';
            return;
        }
        
        // Crear mock block para theme settings
        var mockBlock = {
            id: 'theme-settings',
            role: 'theme',
            config: settings || {}
        };
        
        // Merge defaults para componentes si no existen
        if (!mockBlock.config.components) mockBlock.config.components = {};
        
        if (Gbn.content && Gbn.content.roles && Gbn.content.roles.getMap) {
            var roles = Gbn.content.roles.getMap();
            Object.keys(roles).forEach(function(role) {
                var defaults = Gbn.content.roles.getRoleDefaults(role);
                if (defaults && defaults.config) {
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
        
        // Sincronización con CSS en modo dev
        if (gloryGbnCfg && gloryGbnCfg.devMode && Gbn.cssSync && Gbn.cssSync.syncTheme) {
            mockBlock.config = Gbn.cssSync.syncTheme(mockBlock.config);
        }
        
        // Aplicar tema inicialmente
        if (Gbn.ui.theme.applicator && Gbn.ui.theme.applicator.applyThemeSettings) {
            Gbn.ui.theme.applicator.applyThemeSettings(mockBlock.config);
            
            if (Gbn.log) {
                Gbn.log.info('Theme Settings Applied', { 
                    view: state.getCurrentView(), 
                    role: state.getComponentState().currentDetailRole,
                    configKeys: Object.keys(mockBlock.config)
                });
            }
        }
        
        /**
         * Función principal de renderizado.
         * Determina qué vista mostrar según el estado actual.
         */
        function render() {
            container.innerHTML = '';
            var currentView = state.getCurrentView();
            
            if (currentView === 'menu') {
                renderMenu();
            } else {
                renderSection(currentView);
            }
        }
        
        /**
         * Renderiza el menú principal de opciones.
         */
        function renderMenu() {
            if (renderers.menu && renderers.menu.render) {
                renderers.menu.render(container, function(sectionId) {
                    state.setCurrentView(sectionId);
                    render();
                });
            }
        }
        
        /**
         * Renderiza una sección específica.
         * @param {string} sectionId - ID de la sección
         */
        function renderSection(sectionId) {
            var sectionContainer = document.createElement('div');
            sectionContainer.className = 'gbn-theme-section';
            
            // Header con botón de volver
            var header = document.createElement('div');
            header.className = 'gbn-theme-section-header';
            
            var backBtn = document.createElement('button');
            backBtn.className = 'gbn-theme-back-btn';
            backBtn.textContent = '← Volver';
            backBtn.onclick = function() {
                // Reset de estado de componentes al volver al menú
                state.setCurrentDetailRole(null);
                state.setRenderComponentDetail(null);
                state.setCurrentView('menu');
                render();
            };
            
            var title = document.createElement('span');
            title.className = 'gbn-theme-section-title';
            title.textContent = sectionId.charAt(0).toUpperCase() + sectionId.slice(1);
            
            header.appendChild(backBtn);
            header.appendChild(title);
            sectionContainer.appendChild(header);
            
            // Contenido de la sección
            var content = document.createElement('div');
            content.className = 'gbn-theme-section-content';
            
            var builder = Gbn.ui && Gbn.ui.panelFields && Gbn.ui.panelFields.buildField;
            
            // Delegar al renderer apropiado según la sección
            switch (sectionId) {
                case 'text':
                    if (renderers.sectionText && renderers.sectionText.render) {
                        renderers.sectionText.render(content, mockBlock, builder);
                    }
                    break;
                    
                case 'colors':
                    if (renderers.sectionColors && renderers.sectionColors.render) {
                        renderers.sectionColors.render(content, mockBlock, builder);
                    }
                    break;
                    
                case 'pages':
                    if (renderers.sectionPages && renderers.sectionPages.render) {
                        renderers.sectionPages.render(content, mockBlock, builder);
                    }
                    break;
                    
                case 'components':
                    if (renderers.sectionComponents && renderers.sectionComponents.render) {
                        renderers.sectionComponents.render(content, mockBlock);
                    }
                    break;
                    
                default:
                    content.innerHTML = '<p>Sección no encontrada: ' + sectionId + '</p>';
            }
            
            sectionContainer.appendChild(content);
            container.appendChild(sectionContainer);
        }
        
        // Render inicial
        render();
        
        // Deshabilitar botón del footer (usamos guardado global)
        if (footer) {
            footer.disabled = true;
            footer.textContent = 'Usa Guardar en el Dock';
        }
    }

    /**
     * Resetea el estado de Theme Settings.
     * Debe llamarse cuando se cierra el panel para evitar estados residuales.
     */
    function resetThemeSettingsState() {
        var state = Gbn.ui.theme.state;
        if (state && state.resetState) {
            state.resetState();
        }
    }

    // API Pública
    Gbn.ui.theme.render = {
        renderPageSettingsForm: renderPageSettingsForm,
        renderThemeSettingsForm: renderThemeSettingsForm,
        resetState: resetThemeSettingsState
    };

})(window);
