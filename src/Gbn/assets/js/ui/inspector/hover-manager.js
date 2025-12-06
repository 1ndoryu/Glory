;(function (global) {
    'use strict';

    /**
     * INSPECTOR - HOVER MANAGER MODULE
     * 
     * Gestiona el hover y selección de bloques en el editor.
     * Usa requestAnimationFrame para rendimiento óptimo.
     * 
     * @module Gbn.ui.inspector.hoverManager
     */

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.inspectorModules = Gbn.ui.inspectorModules || {};

    var state = Gbn.state;

    // Estado privado
    var currentBlockElement = null;
    var rafId = null;

    /**
     * Hover Manager
     * Gestiona la detección de bloques bajo el cursor.
     */
    var HoverManager = {
        /**
         * Inicia el tracking de hover.
         */
        start: function() {
            var GlobalControls = Gbn.ui.inspectorModules.globalControls;
            if (GlobalControls) GlobalControls.init();
            
            document.addEventListener('mousemove', this.onMouseMove, { passive: true });
            document.addEventListener('mouseleave', this.onMouseLeave, { passive: true });
        },

        /**
         * Detiene el tracking de hover.
         */
        stop: function() {
            document.removeEventListener('mousemove', this.onMouseMove);
            document.removeEventListener('mouseleave', this.onMouseLeave);
            this.clear();
        },

        /**
         * Handler de movimiento del mouse.
         * Usa requestAnimationFrame para rendimiento.
         * 
         * @param {MouseEvent} e - Evento de mouse
         */
        onMouseMove: function(e) {
            var inspectorState = Gbn.ui.inspectorModules.state;
            if (!inspectorState || !inspectorState.isActive() || inspectorState.isLocked()) return;
            if (rafId) return;
            
            rafId = requestAnimationFrame(function() {
                rafId = null;
                HoverManager.update(e.clientX, e.clientY);
            });
        },

        /**
         * Handler cuando el mouse sale del documento.
         */
        onMouseLeave: function() {
            var inspectorState = Gbn.ui.inspectorModules.state;
            if (!inspectorState || !inspectorState.isActive() || inspectorState.isLocked()) return;
            HoverManager.clear();
        },

        /**
         * Actualiza el bloque activo según las coordenadas del mouse.
         * 
         * @param {number} x - Coordenada X
         * @param {number} y - Coordenada Y
         */
        update: function(x, y) {
            var target = document.elementFromPoint(x, y);
            if (!target) { 
                HoverManager.clear(); 
                return; 
            }

            // Ignore if over controls
            if (target.closest('.gbn-controls-group') || target.closest('.gbn-width-dropdown')) return;

            var blockEl = target.closest('.gbn-block');
            
            if (blockEl && blockEl !== currentBlockElement) {
                var blockId = blockEl.getAttribute('data-gbn-id');
                var block = blockId ? state.get(blockId) : null;
                
                // Fallback search if no ID
                if (!block) {
                    block = state.all().find(function(b) { return b.element === blockEl; });
                }

                if (block) HoverManager.activate(block);
            } else if (!blockEl && currentBlockElement) {
                HoverManager.clear();
            }
        },

        /**
         * Activa un bloque como el bloque actualmente "hovered".
         * 
         * @param {Object} block - Bloque a activar
         */
        activate: function(block) {
            var GlobalControls = Gbn.ui.inspectorModules.globalControls;
            
            if (currentBlockElement && currentBlockElement !== block.element) {
                if (currentBlockElement.__gbnRootControls) {
                    currentBlockElement.__gbnRootControls.style.display = 'none';
                }
                currentBlockElement.classList.remove('gbn-show-controls');
            }
            
            currentBlockElement = block.element;
            currentBlockElement.classList.add('gbn-show-controls');
            
            // Move Singleton Controls
            if (GlobalControls) GlobalControls.attachTo(block);

            // Root Insertion Logic
            if (block.role === 'principal') {
                var controls = Gbn.ui.inspectorModules.controls;
                if (controls && controls.ensureRootInsertionButtons) {
                    controls.ensureRootInsertionButtons(block);
                } else {
                    HoverManager.ensureRootInsertionButtons(block);
                }
            }
        },

        /**
         * Limpia el estado de hover.
         */
        clear: function() {
            var GlobalControls = Gbn.ui.inspectorModules.globalControls;
            
            if (currentBlockElement) {
                currentBlockElement.classList.remove('gbn-show-controls');
                if (currentBlockElement.__gbnRootControls) {
                    currentBlockElement.__gbnRootControls.style.display = 'none';
                }
                currentBlockElement = null;
            }
            if (GlobalControls) GlobalControls.detach();
        },

        /**
         * Crea botones de inserción para bloques principales (root).
         * 
         * @param {Object} block - Bloque principal
         */
        ensureRootInsertionButtons: function(block) {
            if (block.element.__gbnRootControls) {
                block.element.__gbnRootControls.style.display = 'block';
                return;
            }

            var container = document.createElement('div');
            container.className = 'gbn-root-controls';
            
            var btnTop = document.createElement('button');
            btnTop.className = 'gbn-root-add-btn gbn-root-add-top';
            btnTop.innerHTML = '+'; 
            btnTop.title = 'Añadir Sección Arriba';
            btnTop.onclick = function(e) { 
                e.stopPropagation(); 
                Gbn.ui.library.open(block.element, 'before', ['principal']); 
            };

            var btnBottom = document.createElement('button');
            btnBottom.className = 'gbn-root-add-btn gbn-root-add-bottom';
            btnBottom.innerHTML = '+'; 
            btnBottom.title = 'Añadir Sección Abajo';
            btnBottom.onclick = function(e) { 
                e.stopPropagation(); 
                Gbn.ui.library.open(block.element, 'after', ['principal']); 
            };

            container.appendChild(btnTop);
            container.appendChild(btnBottom);
            
            block.element.appendChild(container);
            block.element.__gbnRootControls = container;
            block.element.__gbnRootControls.style.display = 'block';
        },

        /**
         * Obtiene el elemento del bloque actualmente activo.
         * 
         * @returns {HTMLElement|null}
         */
        getCurrentBlockElement: function() {
            return currentBlockElement;
        }
    };

    // Exportar módulo
    Gbn.ui.inspectorModules.hoverManager = HoverManager;

})(typeof window !== 'undefined' ? window : this);
