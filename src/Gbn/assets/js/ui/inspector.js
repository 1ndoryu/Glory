;(function (global) {
    'use strict';

    /**
     * INSPECTOR (Orquestador)
     * 
     * Inspector visual para GBN - maneja hover, selección y controles de bloques.
     * 
     * ARQUITECTURA MODULAR (Refactorizado Dic 2025):
     * Este archivo actúa como orquestador. La lógica está dividida en:
     * - inspector/state.js          → Estado (active, locked, persistencia)
     * - inspector/controls.js       → Creación de botones de control
     * - inspector/global-controls.js → Singleton Manager para controles
     * - inspector/hover-manager.js  → Gestión de hover con RAF
     * 
     * @module Gbn.ui.inspector
     */

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};

    var state = Gbn.state;

    // Referencias a módulos (cargados como dependencias)
    function getModules() {
        return Gbn.ui.inspectorModules || {};
    }

    var inspector = (function () {
        /**
         * Configura un bloque para el inspector.
         * 
         * @param {Object} block - Bloque a configurar
         */
        function ensureBlockSetup(block) {
            var modules = getModules();
            if (modules.controls) {
                modules.controls.ensureBaseline(block);
            }
            
            if (!block || !block.element) { return; }
            block.element.classList.add('gbn-block');
            block.element.setAttribute('data-gbn-role', block.role || 'block');
            block.element.setAttribute('data-gbn-id', block.id);
            
            var inspectorState = modules.state;
            if (!inspectorState || !inspectorState.isActive()) {
                block.element.classList.remove('gbn-show-controls');
            }
        }

        /**
         * Activa o desactiva el inspector.
         * 
         * @param {boolean} next - Nuevo estado
         */
        function setActive(next) {
            var modules = getModules();
            var inspectorState = modules.state;
            var hoverManager = modules.hoverManager;
            
            var active = !!next;
            if (inspectorState) {
                inspectorState.setActive(active);
            }
            
            document.documentElement.classList.toggle('gbn-active', active);
            
            if (Gbn.ui && Gbn.ui.dock && typeof Gbn.ui.dock.updateState === 'function') {
                Gbn.ui.dock.updateState(active);
            }

            state.all().forEach(ensureBlockSetup);
            
            if (active) {
                if (hoverManager) hoverManager.start();
                if (Gbn.ui && Gbn.ui.dragDrop && typeof Gbn.ui.dragDrop.enable === 'function') {
                    Gbn.ui.dragDrop.enable();
                }
            } else {
                if (hoverManager) hoverManager.stop();
                if (Gbn.ui && Gbn.ui.dragDrop && typeof Gbn.ui.dragDrop.disable === 'function') {
                    Gbn.ui.dragDrop.disable();
                }
                if (Gbn.ui && Gbn.ui.panel && typeof Gbn.ui.panel.close === 'function') { 
                    Gbn.ui.panel.close(); 
                }
            }
        }

        /**
         * Handler para cuando se hidratan nuevos bloques.
         * 
         * @param {CustomEvent} event - Evento de hidratación
         */
        function handleHydrated(event) {
            var detail = event && event.detail ? event.detail : {};
            if (detail.ids && Array.isArray(detail.ids)) {
                detail.ids.forEach(function(id) {
                    var block = state.get(id);
                    if (block) { ensureBlockSetup(block); }
                });
            } else if (detail.id) {
                var block = state.get(detail.id);
                if (block) { ensureBlockSetup(block); }
            } else {
                state.all().forEach(ensureBlockSetup);
            }
        }

        /**
         * Inicializa el inspector.
         * 
         * @param {Array} blocks - Bloques iniciales
         * @param {Object} options - Opciones de configuración
         */
        function init(blocks, options) {
            var modules = getModules();
            var inspectorState = modules.state;
            var globalControls = modules.globalControls;
            var controls = modules.controls;
            
            // Configurar estado
            if (inspectorState) {
                inspectorState.configure(options || {});
            }
            
            // Configurar baseline para bloques existentes
            (blocks || state.all()).forEach(function(block) {
                if (controls) controls.ensureBaseline(block);
            });
            
            var cfg = options || {};
            if (!cfg.isEditor) { return; }
            
            // Inicializar panel y dock
            if (Gbn.ui && Gbn.ui.panel && typeof Gbn.ui.panel.init === 'function') { 
                Gbn.ui.panel.init(); 
            }
            if (Gbn.ui && Gbn.ui.dock && typeof Gbn.ui.dock.init === 'function') { 
                Gbn.ui.dock.init(); 
            }

            // Leer estado inicial
            var stored = inspectorState ? inspectorState.readStoredState() : null;
            var initial = typeof stored === 'boolean' ? stored : !!cfg.initialActive;
            setActive(initial);
            
            // Listener para hidratación
            global.addEventListener('gbn:contentHydrated', handleHydrated);
            
            // Listener para cambios de config (actualizar width label)
            window.addEventListener('gbn:configChanged', function(e) {
                if (globalControls && 
                    globalControls.currentBlock && 
                    e.detail && 
                    e.detail.id === globalControls.currentBlock.id) {
                    globalControls.updateWidthLabel(globalControls.currentBlock);
                }
            });
        }

        return { 
            init: init, 
            setActive: setActive,
            setLocked: function(v) { 
                var modules = getModules();
                if (modules.state) {
                    modules.state.setLocked(!!v);
                }
            }
        };
    })();

    Gbn.ui.inspector = inspector;
})(window);
