;(function(global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.panelRender = Gbn.ui.panelRender || {};

    /**
     * Selector de Estados (Fase 10: Hover/Focus Editing)
     * 
     * Este módulo renderiza el selector de estados (Normal, Hover, Focus)
     * que permite al usuario editar estilos condicionales a pseudo-clases CSS.
     */

    /**
     * Configuración de estados con iconos del IconRegistry
     * @returns {Array} Configuración de estados
     */
    function getStatesConfig() {
        var Icons = global.GbnIcons;
        
        return [
            { 
                id: 'normal', 
                label: 'Normal',
                icon: Icons ? Icons.get('state.normal') : '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3l7.07 16.97 2.51-7.39 7.39-2.51L3 3z"></path></svg>'
            },
            { 
                id: 'hover', 
                label: 'Hover',
                icon: Icons ? Icons.get('state.hover') : '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 11V6a2 2 0 0 0-2-2v0a2 2 0 0 0-2 2v0"></path><path d="M14 10V4a2 2 0 0 0-2-2v0a2 2 0 0 0-2 2v6"></path><path d="M10 10.5V6a2 2 0 0 0-2-2v0a2 2 0 0 0-2 2v8"></path><path d="M18 8a2 2 0 1 1 4 0v6a8 8 0 0 1-8 8h-2c-2.8 0-4.5-.86-5.99-2.34l-3.6-3.6a2 2 0 0 1 2.83-2.82L7 15"></path></svg>'
            },
            { 
                id: 'focus', 
                label: 'Focus',
                icon: Icons ? Icons.get('state.focus') : '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><circle cx="12" cy="12" r="3"></circle></svg>'
            }
        ];
    }

    /**
     * Renderiza el selector de estados en un contenedor
     * 
     * @param {HTMLElement} container - Contenedor donde renderizar
     * @param {Object} block - Bloque actual
     */
    function render(container, block) {
        var panelState = Gbn.ui.panelRender.state;
        var styleManager = Gbn.styleManager;
        var currentEditingState = panelState.getCurrentEditingState();
        
        var wrapper = document.createElement('div');
        wrapper.className = 'gbn-state-selector';
        wrapper.style.width = '100%';

        var btnGroup = document.createElement('div');
        btnGroup.className = 'gbn-btn-group';
        btnGroup.style.display = 'flex';
        btnGroup.style.width = '100%';
        btnGroup.style.background = 'var(--gbn-bg-secondary, #f1f1f1)';
        btnGroup.style.padding = '3px';
        btnGroup.style.borderRadius = '6px';
        btnGroup.style.gap = '0';

        var states = getStatesConfig();

        states.forEach(function(s) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.innerHTML = s.icon;
            btn.title = s.label;
            
            // Estilos base
            btn.style.flex = '1';
            btn.style.display = 'flex';
            btn.style.alignItems = 'center';
            btn.style.justifyContent = 'center';
            btn.style.padding = '6px 0';
            btn.style.cursor = 'pointer';
            btn.style.borderRadius = '4px';
            btn.style.border = 'none';
            btn.style.transition = 'all 0.2s ease';
            
            // Estilos de estado activo/inactivo
            if (currentEditingState === s.id) {
                btn.className = 'gbn-btn-active';
                btn.style.background = '#ffffff';
                btn.style.color = '#2271b1';
                btn.style.boxShadow = '0 1px 3px rgba(0,0,0,0.1)';
            } else {
                btn.className = 'gbn-btn-inactive';
                btn.style.background = 'transparent';
                btn.style.color = '#646970';
                btn.style.boxShadow = 'none';
            }
            
            // Hover effects
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
            
            // Click handler
            btn.onclick = function() {
                if (currentEditingState === s.id) return;
                
                // Limpiar clases de simulación anteriores
                if (block.element) {
                    block.element.classList.remove('gbn-simulated-hover', 'gbn-simulated-focus');
                }
                
                // Actualizar estado
                panelState.setCurrentEditingState(s.id);
                
                // Aplicar nueva clase de simulación
                if (s.id !== 'normal' && block.element) {
                    block.element.classList.add('gbn-simulated-' + s.id);
                    
                    // Aplicar estilos CSS existentes del estado
                    if (block.config._states && block.config._states[s.id]) {
                        if (styleManager && styleManager.applyStateCss) {
                            styleManager.applyStateCss(block, s.id, block.config._states[s.id]);
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

    // API Pública
    Gbn.ui.panelRender.stateSelector = {
        render: render,
        getStatesConfig: getStatesConfig
    };

})(window);
