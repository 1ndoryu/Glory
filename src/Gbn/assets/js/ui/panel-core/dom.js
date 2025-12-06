;(function (global) {
    'use strict';

    /**
     * panel-core/dom.js - Funciones de montaje y creación del DOM
     * 
     * Maneja la creación e inicialización del elemento del panel.
     * Incluye la estructura HTML base y binding de listeners globales.
     * 
     * Parte del REFACTOR-003: Refactorización de panel-core.js
     * 
     * @module panel-core/dom
     */

    var Gbn = global.Gbn = global.Gbn || {};
    var utils = Gbn.utils;
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.panelCore = Gbn.ui.panelCore || {};

    var stateModule = Gbn.ui.panelCore.state;
    var statusModule = Gbn.ui.panelCore.status;

    /**
     * Renderiza el mensaje de placeholder cuando no hay bloque seleccionado.
     * @param {string} [message] - Mensaje a mostrar
     */
    function renderPlaceholder(message) {
        var state = stateModule.get();
        if (!state.body) { return; }
        
        stateModule.set('form', null);
        
        var text = message || 'Selecciona un bloque para configurar.';
        state.body.innerHTML = '<div class="gbn-panel-empty">' + text + '</div>';
        
        if (state.footer) {
            if (state.root) {
                var currentFooter = state.root.querySelector('.gbn-footer-primary');
                if (currentFooter) {
                    stateModule.set('footer', currentFooter);
                }
            }
            
            // Actualizar referencia después del set
            state = stateModule.get();
            
            state.footer.disabled = true;
            state.footer.textContent = 'Guardar (próximamente)';
            
            if (state.footer && state.footer.parentNode) {
                var newFooterBtn = state.footer.cloneNode(true);
                state.footer.parentNode.replaceChild(newFooterBtn, state.footer);
                stateModule.set('footer', newFooterBtn);
            }
        }
        
        statusModule.set('Cambios en vivo');
    }

    /**
     * Asegura que el panel esté montado en el DOM.
     * Crea la estructura HTML si no existe y configura listeners.
     * 
     * @returns {HTMLElement} El elemento raíz del panel
     */
    function ensurePanelMounted() {
        var state = stateModule.get();
        
        if (state.root) { 
            return state.root; 
        }
        
        var panelRoot = document.getElementById('gbn-panel');
        
        if (!panelRoot) {
            panelRoot = document.createElement('aside');
            panelRoot.id = 'gbn-panel';
            panelRoot.setAttribute('aria-hidden', 'true');
            panelRoot.innerHTML = ''
                + '<header class="gbn-panel-header" style="display: flex; flex-direction: column; background: #fafafa; border-bottom: 1px solid #e0e0e0;">'
                + '  <div style="display: flex; align-items: center; justify-content: space-between; width: 100%; padding: 15px; padding-bottom: 10px;">'
                + '    <span class="gbn-panel-header-title" style="font-weight: 600; font-size: 13px;">GBN Panel</span>'
                + '    <button type="button" class="gbn-panel-header-close" data-gbn-action="close-panel" aria-label="Close panel" style="background:none; border:none; cursor:pointer; font-size:18px;">×</button>'
                + '  </div>'
                + '  <div class="gbn-panel-header-tabs-area" style="width: 100%;"></div>'
                + '</header>'
                + '<div class="gbn-body" style="padding-bottom: 120px;"></div>' /* Space for footer */
                + '<footer class="gbn-footer" style="position: absolute; bottom: 0; left: 0; right: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 8px 14px; border-top: 1px solid #f0f0f0; background: #fafafa;">'
                + '  <div class="gbn-footer-states-area" style="width: 100%; margin-bottom: 8px;"></div>'
                + '  <span class="gbn-footer-status" style="font-size:11px; margin-bottom: 5px; color: #666;">Cambios en vivo</span>'
                + '  <button type="button" class="gbn-footer-primary" disabled style="width:100%;">Guardar (próximamente)</button>'
                + '</footer>';
            document.body.appendChild(panelRoot);
        }
        
        // Cachear referencias a elementos
        stateModule.set('root', panelRoot);
        stateModule.set('body', panelRoot.querySelector('.gbn-body'));
        stateModule.set('title', panelRoot.querySelector('.gbn-panel-header-title'));
        stateModule.set('footer', panelRoot.querySelector('.gbn-footer-primary'));
        stateModule.set('notice', panelRoot.querySelector('.gbn-footer-status'));
        
        // Refrescar state después de los sets
        state = stateModule.get();
        
        // Configurar botón guardar
        if (state.footer && !state.footer.__gbnBound) {
            state.footer.__gbnBound = true;
            state.footer.disabled = false;
            state.footer.textContent = 'Guardar';
            state.footer.addEventListener('click', function (event) {
                event.preventDefault();
                if (!Gbn.persistence || typeof Gbn.persistence.savePageConfig !== 'function') { return; }
                statusModule.set('Guardando...');
                Gbn.persistence.savePageConfig().then(function (res) {
                    statusModule.flash(res && res.success ? 'Guardado' : 'Error al guardar');
                }).catch(function () { 
                    statusModule.flash('Error al guardar'); 
                });
            });
        }
        
        renderPlaceholder();
        
        // Configurar listeners globales (solo una vez)
        if (!state.listenersBound) {
            stateModule.set('listenersBound', true);
            bindGlobalListeners(panelRoot);
        }
        
        return panelRoot;
    }

    /**
     * Configura los listeners globales del panel.
     * Se llama solo una vez durante la inicialización.
     * 
     * @param {HTMLElement} panelRoot - Elemento raíz del panel
     */
    function bindGlobalListeners(panelRoot) {
        // Botón cerrar
        var closeBtn = panelRoot.querySelector('[data-gbn-action="close-panel"]');
        if (closeBtn) {
            closeBtn.addEventListener('click', function (event) {
                event.preventDefault();
                if (Gbn.ui.panel && Gbn.ui.panel.close) {
                    Gbn.ui.panel.close();
                }
            });
        }
        
        // Escape para cerrar
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && Gbn.ui.panel && Gbn.ui.panel.isOpen && Gbn.ui.panel.isOpen()) {
                Gbn.ui.panel.close();
            }
        });
        
        // Listener para refrescar panel cuando cambia el breakpoint
        window.addEventListener('gbn:breakpointChanged', function(event) {
            if (!Gbn.ui.panel || !Gbn.ui.panel.isOpen || !Gbn.ui.panel.isOpen()) return;
            
            var detail = event.detail || {};
            var state = stateModule.get();
            
            if (utils && utils.debug) {
                utils.debug('Breakpoint cambiado a: ' + detail.current + ', refrescando panel');
            }
            
            if (state.mode === 'block' && state.activeBlock) {
                if (Gbn.ui.panelRender && Gbn.ui.panelRender.renderBlockControls) {
                    Gbn.ui.panelRender.renderBlockControls(state.activeBlock, state.body);
                }
            }
            
            if (state.mode === 'theme') {
                Gbn.ui.panel.openTheme();
            }
            
            if (state.mode === 'page') {
                Gbn.ui.panel.openPage();
            }
        });
    }

    // === EXPONER API ===
    Gbn.ui.panelCore.dom = {
        ensureMounted: ensurePanelMounted,
        renderPlaceholder: renderPlaceholder
    };

})(window);
