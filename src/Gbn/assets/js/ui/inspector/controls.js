;(function (global) {
    'use strict';

    /**
     * INSPECTOR - CONTROLS MODULE
     * 
     * Maneja la creación de botones de control (config, add, delete, width)
     * para los bloques del editor.
     * 
     * @module Gbn.ui.inspector.controls
     */

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.inspectorModules = Gbn.ui.inspectorModules || {};

    var state = Gbn.state;

    /**
     * Obtiene los componentes hijos permitidos para un rol dado.
     * Delega a utils.getAllowedChildrenForRole() para centralización.
     * 
     * @param {string} role - El rol del componente padre
     * @returns {array} Array de roles de componentes permitidos como hijos
     */
    function getAllowedChildrenForRole(role) {
        return Gbn.utils.getAllowedChildrenForRole(role);
    }

    /**
     * Asegura que el bloque tenga las clases y atributos base necesarios.
     * 
     * @param {Object} block - Bloque a configurar
     */
    function ensureBaseline(block) {
        if (!block || !block.element || !block.element.classList) { return; }
        block.element.classList.add('gbn-node');
        block.element.setAttribute('data-gbn-ready', '1');
        if (Gbn.ui && Gbn.ui.panelApi && typeof Gbn.ui.panelApi.applyBlockStyles === 'function') {
            Gbn.ui.panelApi.applyBlockStyles(block);
        }
    }

    /**
     * Crea un botón genérico con clase, HTML y título.
     * 
     * @param {string} cls - Clase CSS del botón
     * @param {string} html - Contenido HTML (icono SVG)
     * @param {string} title - Tooltip del botón
     * @returns {HTMLButtonElement}
     */
    function createButton(cls, html, title) {
        var btn = document.createElement('button');
        btn.type = 'button'; 
        btn.className = cls;
        btn.innerHTML = html; 
        btn.title = title;
        return btn;
    }

    /**
     * Crea control de ancho para bloques secundarios.
     * 
     * @param {Object} block - Bloque objetivo
     * @returns {HTMLElement} Wrapper con botón y dropdown
     */
    function createWidthControl(block) {
        var wrapper = document.createElement('div');
        wrapper.className = 'gbn-width-control';
        
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'gbn-width-btn';
        btn.title = 'Ancho del bloque';
        
        // Dropdown
        var dropdown = document.createElement('div');
        dropdown.className = 'gbn-width-dropdown';
        
        var fractions = ['1/1', '5/6', '4/5', '3/4', '2/3', '3/5', '1/2', '2/5', '1/3', '1/4', '1/5', '1/6'];
        
        fractions.forEach(function(val) {
            var item = document.createElement('button');
            item.type = 'button';
            item.className = 'gbn-width-item';
            item.textContent = val;
            
            item.addEventListener('click', function(e) {
                e.stopPropagation();
                
                dropdown.classList.remove('is-open');
                
                // Lock hover temporarily to prevent immediate changes
                if (Gbn.ui && Gbn.ui.inspector && Gbn.ui.inspector.setLocked) {
                    Gbn.ui.inspector.setLocked(true);
                    setTimeout(function() {
                        if (Gbn.ui && Gbn.ui.inspector && Gbn.ui.inspector.setLocked) {
                            Gbn.ui.inspector.setLocked(false);
                        }
                    }, 300);
                }

                // Force remove hover class from ALL blocks
                document.querySelectorAll('.gbn-show-controls').forEach(function(el) {
                    el.classList.remove('gbn-show-controls');
                });
                
                // Force hide the controls container itself
                if (block && block.element && block.element.__gbnControls) {
                    block.element.__gbnControls.style.display = 'none';
                }

                if (Gbn.ui && Gbn.ui.panelApi && Gbn.ui.panelApi.updateConfigValue) {
                    Gbn.ui.panelApi.updateConfigValue(block, 'width', val);
                    btn.textContent = val;
                }
            });
            dropdown.appendChild(item);
        });

        wrapper.appendChild(btn);
        wrapper.appendChild(dropdown);

        // Toggle Dropdown
        btn.addEventListener('click', function(e) {
            e.preventDefault(); e.stopPropagation();
            var isOpen = dropdown.classList.contains('is-open');
            document.querySelectorAll('.gbn-width-dropdown').forEach(function(d) { 
                d.classList.remove('is-open'); 
            });
            
            if (!isOpen) {
                dropdown.classList.add('is-open');
            }
        });

        // Close on click outside
        document.addEventListener('click', function(e) {
            if (!wrapper.contains(e.target)) {
                dropdown.classList.remove('is-open');
            }
        });

        // Función para actualizar el label del ancho
        function updateLabel() {
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
            btn.textContent = val;
        }

        // Initial update
        updateLabel();

        // Listen for updates
        var updateHandler = function(e) {
            if (e.type === 'gbn:configChanged') {
                if (e.detail && e.detail.id === block.id) updateLabel();
            } else {
                updateLabel();
            }
        };

        window.addEventListener('gbn:configChanged', updateHandler);
        window.addEventListener('gbn:breakpointChanged', updateHandler);

        return wrapper;
    }

    /**
     * Crea botones de control para un bloque específico.
     * 
     * @param {Object} block - Bloque objetivo
     * @returns {HTMLElement|null} Contenedor de controles o null
     */
    function createConfigButton(block) {
        if (!block || !block.element) { return null; }
        var container = block.element.__gbnControls;
        if (container) { return container; }
        
        container = document.createElement('span');
        container.className = 'gbn-controls-group';
        
        // Add specific class based on role
        if (block.role === 'principal') {
            container.classList.add('gbn-controls-principal');
        } else if (block.role === 'secundario') {
            container.classList.add('gbn-controls-secundario');
        } else {
            container.classList.add('gbn-controls-centered');
        }

        // Config Button
        var btnConfig = createButton(
            'gbn-config-btn',
            '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.38a2 2 0 0 0-.73-2.73l-.15-.1a2 2 0 0 1-1-1.72v-.51a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"></path><circle cx="12" cy="12" r="3"></circle></svg>',
            'Configurar'
        );
        btnConfig.addEventListener('click', function(event) {
            event.preventDefault(); event.stopPropagation();
            if (Gbn.ui && Gbn.ui.panel && typeof Gbn.ui.panel.open === 'function') {
                Gbn.ui.panel.open(block);
                
                var evt = new CustomEvent('gbn:block-selected', { detail: { blockId: block.id } });
                document.dispatchEvent(evt);
                
                if (Gbn.core && Gbn.core.store) {
                    Gbn.core.store.dispatch({
                        type: Gbn.core.store.Actions.SELECT_BLOCK,
                        id: block.id
                    });
                }
            }
        });
        
        // Add Button
        var btnAdd = createButton(
            'gbn-add-btn',
            '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>',
            'Añadir bloque'
        );
        btnAdd.addEventListener('click', function(event) {
            event.preventDefault(); event.stopPropagation();
            if (Gbn.ui && Gbn.ui.library && typeof Gbn.ui.library.open === 'function') {
                var position = 'after';
                var allowed = getAllowedChildrenForRole(block.role);
                
                if (block.role === 'principal' || block.role === 'form' || 
                    block.role === 'postRender' || block.role === 'postItem' || 
                    block.role === 'secundario') {
                    position = 'append';
                }
                
                Gbn.ui.library.open(block.element, position, allowed);
            }
        });

        // Delete Button
        var btnDelete = createButton(
            'gbn-delete-btn',
            '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>',
            'Eliminar bloque'
        );
        btnDelete.addEventListener('click', function(event) {
            event.preventDefault(); event.stopPropagation();
            state.deleteBlock(block.id);
        });

        container.appendChild(btnConfig);
        
        // Width Control (Only for Secundario)
        if (block.role === 'secundario') {
            var widthControl = createWidthControl(block);
            if (widthControl) {
                container.appendChild(widthControl);
            }
        }

        container.appendChild(btnAdd);
        container.appendChild(btnDelete);
        
        block.element.appendChild(container);
        block.element.__gbnControls = container;
        return container;
    }

    // Exportar módulo
    Gbn.ui.inspectorModules.controls = {
        ensureBaseline: ensureBaseline,
        createButton: createButton,
        createWidthControl: createWidthControl,
        createConfigButton: createConfigButton,
        getAllowedChildrenForRole: getAllowedChildrenForRole
    };

})(typeof window !== 'undefined' ? window : this);
