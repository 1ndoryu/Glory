;(function (global) {
    'use strict';

    /**
     * INSPECTOR - GLOBAL CONTROLS MODULE
     * 
     * Singleton Manager para los controles del inspector.
     * Gestiona un único set de controles que se mueve entre bloques.
     * 
     * @module Gbn.ui.inspector.globalControls
     */

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.inspectorModules = Gbn.ui.inspectorModules || {};

    var state = Gbn.state;

    /**
     * Singleton Controls Manager
     * Un único set de controles que se mueve entre bloques.
     */
    var GlobalControls = {
        element: null,
        widthBtn: null,
        widthDropdown: null,
        widthWrapper: null,
        currentBlock: null,

        /**
         * Inicializa los controles singleton.
         */
        init: function() {
            if (this.element) return;
            
            var controls = Gbn.ui.inspectorModules.controls;
            
            // Create Container
            this.element = document.createElement('span');
            this.element.className = 'gbn-controls-group';
            
            // Config Button
            var btnConfig = controls.createButton(
                'gbn-config-btn',
                '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.38a2 2 0 0 0-.73-2.73l-.15-.1a2 2 0 0 1-1-1.72v-.51a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"></path><circle cx="12" cy="12" r="3"></circle></svg>',
                'Configurar'
            );
            btnConfig.onclick = this.handleConfig.bind(this);
            
            // Add Button
            var btnAdd = controls.createButton(
                'gbn-add-btn',
                '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>',
                'Añadir bloque'
            );
            btnAdd.onclick = this.handleAdd.bind(this);

            // Delete Button
            var btnDelete = controls.createButton(
                'gbn-delete-btn',
                '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>',
                'Eliminar bloque'
            );
            btnDelete.onclick = this.handleDelete.bind(this);

            // Width Control
            var widthCtrl = this.createWidthControl();

            this.element.appendChild(btnConfig);
            this.element.appendChild(widthCtrl);
            this.element.appendChild(btnAdd);
            this.element.appendChild(btnDelete);
        },

        /**
         * Crea control de ancho para el singleton.
         * 
         * @returns {HTMLElement} Wrapper con botón y dropdown
         */
        createWidthControl: function() {
            var self = this;
            var wrapper = document.createElement('div');
            wrapper.className = 'gbn-width-control';
            wrapper.style.display = 'none';

            this.widthBtn = document.createElement('button');
            this.widthBtn.type = 'button';
            this.widthBtn.className = 'gbn-width-btn';
            this.widthBtn.title = 'Ancho del bloque';
            
            this.widthDropdown = document.createElement('div');
            this.widthDropdown.className = 'gbn-width-dropdown';
            
            var fractions = ['1/1', '5/6', '4/5', '3/4', '2/3', '3/5', '1/2', '2/5', '1/3', '1/4', '1/5', '1/6'];
            fractions.forEach(function(val) {
                var item = document.createElement('button');
                item.type = 'button';
                item.className = 'gbn-width-item';
                item.textContent = val;
                item.onclick = function(e) {
                    e.stopPropagation();
                    self.widthDropdown.classList.remove('is-open');
                    
                    // Lock hover temporarily
                    if (Gbn.ui && Gbn.ui.inspector && Gbn.ui.inspector.setLocked) {
                        Gbn.ui.inspector.setLocked(true);
                        setTimeout(function() {
                            if (Gbn.ui && Gbn.ui.inspector && Gbn.ui.inspector.setLocked) {
                                Gbn.ui.inspector.setLocked(false);
                            }
                        }, 300);
                    }

                    if (self.currentBlock && Gbn.ui && Gbn.ui.panelApi && Gbn.ui.panelApi.updateConfigValue) {
                        Gbn.ui.panelApi.updateConfigValue(self.currentBlock, 'width', val);
                        self.widthBtn.textContent = val;
                    }
                };
                self.widthDropdown.appendChild(item);
            });

            this.widthBtn.onclick = function(e) {
                e.preventDefault(); e.stopPropagation();
                var isOpen = self.widthDropdown.classList.contains('is-open');
                document.querySelectorAll('.gbn-width-dropdown').forEach(function(d) { 
                    d.classList.remove('is-open'); 
                });
                
                if (!isOpen) self.widthDropdown.classList.add('is-open');
            };

            // Close on click outside
            document.addEventListener('click', function(e) {
                if (!wrapper.contains(e.target)) {
                    self.widthDropdown.classList.remove('is-open');
                }
            });

            wrapper.appendChild(this.widthBtn);
            wrapper.appendChild(this.widthDropdown);
            this.widthWrapper = wrapper;
            return wrapper;
        },

        /**
         * Adjunta los controles a un bloque específico.
         * 
         * @param {Object} block - Bloque al que adjuntar
         */
        attachTo: function(block) {
            this.currentBlock = block;
            
            // Update Theme Classes
            this.element.className = 'gbn-controls-group';
            if (block.role === 'principal') this.element.classList.add('gbn-controls-principal');
            else if (block.role === 'secundario') this.element.classList.add('gbn-controls-secundario');
            else this.element.classList.add('gbn-controls-centered');

            // Update Width Control Visibility & Value
            if (block.role === 'secundario') {
                this.widthWrapper.style.display = 'inline-block';
                this.updateWidthLabel(block);
            } else {
                this.widthWrapper.style.display = 'none';
            }

            // Append to block
            if (block.element) {
                block.element.appendChild(this.element);
                this.element.style.display = 'flex';
            }
        },

        /**
         * Desadjunta los controles del bloque actual.
         */
        detach: function() {
            if (this.element && this.element.parentElement) {
                this.element.parentElement.removeChild(this.element);
            }
            this.currentBlock = null;
            if (this.widthDropdown) this.widthDropdown.classList.remove('is-open');
        },

        /**
         * Actualiza el label del control de ancho.
         * 
         * @param {Object} block - Bloque a leer
         */
        updateWidthLabel: function(block) {
            if (!this.widthBtn) return;
            var freshBlock = (Gbn.state && Gbn.state.get) ? Gbn.state.get(block.id) : block;
            if (!freshBlock) freshBlock = block;

            var bp = (Gbn.responsive && Gbn.responsive.getCurrentBreakpoint) 
                ? Gbn.responsive.getCurrentBreakpoint() 
                : 'desktop';
            var val = '1/1';
            
            if (Gbn.ui && Gbn.ui.fieldUtils && Gbn.ui.fieldUtils.getResponsiveConfigValue) {
                val = Gbn.ui.fieldUtils.getResponsiveConfigValue(freshBlock, 'width', bp) || '1/1';
            } else if (freshBlock.config) {
                if (bp === 'desktop') val = freshBlock.config.width || '1/1';
                else if (freshBlock.config._responsive && 
                         freshBlock.config._responsive[bp] && 
                         freshBlock.config._responsive[bp].width) {
                    val = freshBlock.config._responsive[bp].width;
                } else {
                    val = freshBlock.config.width || '1/1'; 
                }
            }
            this.widthBtn.textContent = val;
        },

        // Handlers
        handleConfig: function(e) {
            e.preventDefault(); e.stopPropagation();
            if (!this.currentBlock) return;
            if (Gbn.ui && Gbn.ui.panel && typeof Gbn.ui.panel.open === 'function') {
                Gbn.ui.panel.open(this.currentBlock);
                var evt = new CustomEvent('gbn:block-selected', { detail: { blockId: this.currentBlock.id } });
                document.dispatchEvent(evt);
                if (Gbn.core && Gbn.core.store) {
                    Gbn.core.store.dispatch({ 
                        type: Gbn.core.store.Actions.SELECT_BLOCK, 
                        id: this.currentBlock.id 
                    });
                }
            }
        },

        handleAdd: function(e) {
            e.preventDefault(); e.stopPropagation();
            if (!this.currentBlock) return;
            if (Gbn.ui && Gbn.ui.library && typeof Gbn.ui.library.open === 'function') {
                var controls = Gbn.ui.inspectorModules.controls;
                var position = 'after';
                var allowed = controls.getAllowedChildrenForRole(this.currentBlock.role);
                var role = this.currentBlock.role;
                
                if (role === 'principal' || role === 'form' || 
                    role === 'postRender' || role === 'postItem' || 
                    role === 'secundario') {
                    position = 'append';
                }
                Gbn.ui.library.open(this.currentBlock.element, position, allowed);
            }
        },

        handleDelete: function(e) {
            e.preventDefault(); e.stopPropagation();
            if (!this.currentBlock) return;
            state.deleteBlock(this.currentBlock.id);
        }
    };

    // Exportar módulo
    Gbn.ui.inspectorModules.globalControls = GlobalControls;

})(typeof window !== 'undefined' ? window : this);
