;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    var utils = Gbn.utils;
    var state = Gbn.state;

    function ensureBaseline(block) {
        if (!block || !block.element || !block.element.classList) { return; }
        block.element.classList.add('gbn-node');
        block.element.setAttribute('data-gbn-ready', '1');
        if (Gbn.ui && Gbn.ui.panelApi && typeof Gbn.ui.panelApi.applyBlockStyles === 'function') {
            Gbn.ui.panelApi.applyBlockStyles(block);
        }
    }

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
        var btnConfig = document.createElement('button');
        btnConfig.type = 'button'; btnConfig.className = 'gbn-config-btn'; 
        btnConfig.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.38a2 2 0 0 0-.73-2.73l-.15-.1a2 2 0 0 1-1-1.72v-.51a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"></path><circle cx="12" cy="12" r="3"></circle></svg>';
        btnConfig.title = 'Configurar';
        btnConfig.addEventListener('click', function (event) {
            event.preventDefault(); event.stopPropagation();
            if (Gbn.ui && Gbn.ui.panel && typeof Gbn.ui.panel.open === 'function') {
                Gbn.ui.panel.open(block);
                
                // Dispatch selection event for Debug Overlay (Legacy/Direct)
                var evt = new CustomEvent('gbn:block-selected', { detail: { blockId: block.id } });
                document.dispatchEvent(evt);
                
                // Dispatch to Store
                if (Gbn.core && Gbn.core.store) {
                    Gbn.core.store.dispatch({
                        type: Gbn.core.store.Actions.SELECT_BLOCK,
                        id: block.id
                    });
                }
            }
        });
        
        // Add Button
        var btnAdd = document.createElement('button');
        btnAdd.type = 'button'; btnAdd.className = 'gbn-add-btn'; 
        btnAdd.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>';
        btnAdd.title = 'Añadir bloque';
        btnAdd.addEventListener('click', function (event) {
            event.preventDefault(); event.stopPropagation();
            if (Gbn.ui && Gbn.ui.library && typeof Gbn.ui.library.open === 'function') {
                var position = 'after';
                var allowed = [];
                
                if (block.role === 'principal') {
                    position = 'append';
                    allowed = ['secundario'];
                } else if (block.role === 'secundario') {
                    position = 'append';
                    allowed = ['secundario', 'text', 'image', 'button'];
                } else {
                    position = 'after';
                    allowed = ['secundario', 'text', 'image', 'button'];
                }
                
                Gbn.ui.library.open(block.element, position, allowed);
            }
        });

        // Delete Button
        var btnDelete = document.createElement('button');
        btnDelete.type = 'button'; btnDelete.className = 'gbn-delete-btn'; 
        btnDelete.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>';
        btnDelete.title = 'Eliminar bloque';
        btnDelete.addEventListener('click', function (event) {
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
                
                // Close dropdown immediately
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
                    // Force update label immediately for responsiveness
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
            // Close others
            document.querySelectorAll('.gbn-width-dropdown').forEach(function(d) { d.classList.remove('is-open'); });
            
            if (!isOpen) {
                dropdown.classList.add('is-open');
            } else {
                dropdown.classList.remove('is-open');
            }
        });

        // Close on click outside
        document.addEventListener('click', function(e) {
            if (!wrapper.contains(e.target)) {
                dropdown.classList.remove('is-open');
            }
        });

        function updateLabel() {
            // Fetch fresh block state to avoid stale closure
            var freshBlock = (Gbn.state && Gbn.state.get) ? Gbn.state.get(block.id) : block;
            if (!freshBlock) freshBlock = block;

            var bp = (Gbn.responsive && Gbn.responsive.getCurrentBreakpoint) ? Gbn.responsive.getCurrentBreakpoint() : 'desktop';
            var val = '1/1'; // Default
            
            // Try to get value using fieldUtils if available
            if (Gbn.ui && Gbn.ui.fieldUtils && Gbn.ui.fieldUtils.getResponsiveConfigValue) {
                val = Gbn.ui.fieldUtils.getResponsiveConfigValue(freshBlock, 'width', bp) || '1/1';
            } else if (freshBlock.config) {
                // Fallback manual logic
                if (bp === 'desktop') val = freshBlock.config.width || '1/1';
                else if (freshBlock.config._responsive && freshBlock.config._responsive[bp] && freshBlock.config._responsive[bp].width) {
                    val = freshBlock.config._responsive[bp].width;
                } else {
                    // Inherit logic simplified
                    val = freshBlock.config.width || '1/1'; 
                }
            }
            btn.textContent = val;
        }

        // Initial update
        updateLabel();

        // Listen for updates
        var updateHandler = function(e) {
            // Update on config change or breakpoint change
            if (e.type === 'gbn:configChanged') {
                if (e.detail && e.detail.id === block.id) updateLabel();
            } else {
                updateLabel();
            }
        };

        window.addEventListener('gbn:configChanged', updateHandler);
        window.addEventListener('gbn:breakpointChanged', updateHandler);
        
        // Attach handler to element to allow cleanup if needed (though we don't have a destroy lifecycle here easily)
        // A simple check inside handler if element is connected could work, but for now this is standard.

        return wrapper;
    }

    var inspector = (function () {
        var active = false;
        var cfg = {};
        var toggleBtn = null;
        var mainNode = null;
        
        // Hover Manager State
        var currentBlock = null;
        var isLocked = false; 
        var rafId = null;

        function updateToggleLabel() {
            if (!toggleBtn) { return; }
            toggleBtn.dataset.gbnState = active ? 'on' : 'off';
            toggleBtn.setAttribute('aria-pressed', active ? 'true' : 'false');
            toggleBtn.textContent = active ? 'Close GBN' : 'Open GBN';
        }

        function getStoreKey(cfg) {
            if (!cfg || !cfg.isEditor) { return null; }
            var parts = ['gbn-active'];
            if (cfg.userId) { parts.push(String(cfg.userId)); }
            parts.push(String(cfg.pageId || 'global'));
            return parts.join('-');
        }

        function persistState() {
            var key = getStoreKey(cfg); if (!key) { return; }
            try { global.localStorage.setItem(key, active ? '1' : '0'); } catch (_) {}
        }

        function readStoredState() {
            var key = getStoreKey(cfg); if (!key) { return null; }
            try {
                var stored = global.localStorage.getItem(key);
                if (stored === '1') { return true; }
                if (stored === '0') { return false; }
            } catch (_) {}
            return null;
        }

        // Singleton Controls Manager
        var GlobalControls = {
            element: null,
            widthBtn: null,
            widthDropdown: null,
            currentBlock: null,

            init: function() {
                if (this.element) return;
                
                // Create Container
                this.element = document.createElement('span');
                this.element.className = 'gbn-controls-group';
                
                // Config Button
                var btnConfig = this.createBtn('gbn-config-btn', '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.38a2 2 0 0 0-.73-2.73l-.15-.1a2 2 0 0 1-1-1.72v-.51a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"></path><circle cx="12" cy="12" r="3"></circle></svg>', 'Configurar');
                btnConfig.onclick = (e) => this.handleConfig(e);
                
                // Add Button
                var btnAdd = this.createBtn('gbn-add-btn', '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>', 'Añadir bloque');
                btnAdd.onclick = (e) => this.handleAdd(e);

                // Delete Button
                var btnDelete = this.createBtn('gbn-delete-btn', '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>', 'Eliminar bloque');
                btnDelete.onclick = (e) => this.handleDelete(e);

                // Width Control
                var widthCtrl = this.createWidthControl();

                this.element.appendChild(btnConfig);
                this.element.appendChild(widthCtrl); // Inserted here, visibility toggled later
                this.element.appendChild(btnAdd);
                this.element.appendChild(btnDelete);
            },

            createBtn: function(cls, html, title) {
                var btn = document.createElement('button');
                btn.type = 'button'; btn.className = cls;
                btn.innerHTML = html; btn.title = title;
                return btn;
            },

            createWidthControl: function() {
                var wrapper = document.createElement('div');
                wrapper.className = 'gbn-width-control';
                wrapper.style.display = 'none'; // Hidden by default

                this.widthBtn = document.createElement('button');
                this.widthBtn.type = 'button';
                this.widthBtn.className = 'gbn-width-btn';
                this.widthBtn.title = 'Ancho del bloque';
                
                this.widthDropdown = document.createElement('div');
                this.widthDropdown.className = 'gbn-width-dropdown';
                
                var fractions = ['1/1', '5/6', '4/5', '3/4', '2/3', '3/5', '1/2', '2/5', '1/3', '1/4', '1/5', '1/6'];
                fractions.forEach((val) => {
                    var item = document.createElement('button');
                    item.type = 'button';
                    item.className = 'gbn-width-item';
                    item.textContent = val;
                    item.onclick = (e) => {
                        e.stopPropagation();
                        this.widthDropdown.classList.remove('is-open');
                        
                        // Lock hover temporarily
                        if (Gbn.ui && Gbn.ui.inspector && Gbn.ui.inspector.setLocked) {
                            Gbn.ui.inspector.setLocked(true);
                            setTimeout(() => {
                                if (Gbn.ui && Gbn.ui.inspector && Gbn.ui.inspector.setLocked) {
                                    Gbn.ui.inspector.setLocked(false);
                                }
                            }, 300);
                        }

                        if (this.currentBlock && Gbn.ui && Gbn.ui.panelApi && Gbn.ui.panelApi.updateConfigValue) {
                            Gbn.ui.panelApi.updateConfigValue(this.currentBlock, 'width', val);
                            this.widthBtn.textContent = val;
                        }
                    };
                    this.widthDropdown.appendChild(item);
                });

                this.widthBtn.onclick = (e) => {
                    e.preventDefault(); e.stopPropagation();
                    var isOpen = this.widthDropdown.classList.contains('is-open');
                    // Close others (if any, though singleton)
                    document.querySelectorAll('.gbn-width-dropdown').forEach(d => d.classList.remove('is-open'));
                    
                    if (!isOpen) this.widthDropdown.classList.add('is-open');
                    else this.widthDropdown.classList.remove('is-open');
                };

                // Close on click outside
                document.addEventListener('click', (e) => {
                    if (!wrapper.contains(e.target)) {
                        this.widthDropdown.classList.remove('is-open');
                    }
                });

                wrapper.appendChild(this.widthBtn);
                wrapper.appendChild(this.widthDropdown);
                this.widthWrapper = wrapper;
                return wrapper;
            },

            attachTo: function(block) {
                this.currentBlock = block;
                
                // Update Theme Classes
                this.element.className = 'gbn-controls-group'; // Reset
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

            detach: function() {
                if (this.element && this.element.parentElement) {
                    this.element.parentElement.removeChild(this.element);
                }
                this.currentBlock = null;
                if (this.widthDropdown) this.widthDropdown.classList.remove('is-open');
            },

            updateWidthLabel: function(block) {
                if (!this.widthBtn) return;
                var freshBlock = (Gbn.state && Gbn.state.get) ? Gbn.state.get(block.id) : block;
                if (!freshBlock) freshBlock = block;

                var bp = (Gbn.responsive && Gbn.responsive.getCurrentBreakpoint) ? Gbn.responsive.getCurrentBreakpoint() : 'desktop';
                var val = '1/1';
                
                if (Gbn.ui && Gbn.ui.fieldUtils && Gbn.ui.fieldUtils.getResponsiveConfigValue) {
                    val = Gbn.ui.fieldUtils.getResponsiveConfigValue(freshBlock, 'width', bp) || '1/1';
                } else if (freshBlock.config) {
                    if (bp === 'desktop') val = freshBlock.config.width || '1/1';
                    else if (freshBlock.config._responsive && freshBlock.config._responsive[bp] && freshBlock.config._responsive[bp].width) {
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
                        Gbn.core.store.dispatch({ type: Gbn.core.store.Actions.SELECT_BLOCK, id: this.currentBlock.id });
                    }
                }
            },

            handleAdd: function(e) {
                e.preventDefault(); e.stopPropagation();
                if (!this.currentBlock) return;
                if (Gbn.ui && Gbn.ui.library && typeof Gbn.ui.library.open === 'function') {
                    var position = 'after';
                    var allowed = [];
                    var role = this.currentBlock.role;
                    
                    if (role === 'principal') {
                        position = 'append'; allowed = ['secundario'];
                    } else if (role === 'secundario') {
                        position = 'append'; allowed = ['secundario', 'text', 'image', 'button'];
                    } else {
                        position = 'after'; allowed = ['secundario', 'text', 'image', 'button'];
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

        // Global Hover Manager
        var HoverManager = {
            start: function() {
                GlobalControls.init();
                document.addEventListener('mousemove', this.onMouseMove, { passive: true });
                document.addEventListener('mouseleave', this.onMouseLeave, { passive: true });
            },
            stop: function() {
                document.removeEventListener('mousemove', this.onMouseMove);
                document.removeEventListener('mouseleave', this.onMouseLeave);
                this.clear();
            },
            onMouseMove: function(e) {
                if (!active || isLocked) return;
                if (rafId) return;
                rafId = requestAnimationFrame(function() {
                    rafId = null;
                    HoverManager.update(e.clientX, e.clientY);
                });
            },
            onMouseLeave: function() {
                if (!active || isLocked) return;
                HoverManager.clear();
            },
            update: function(x, y) {
                var target = document.elementFromPoint(x, y);
                if (!target) { HoverManager.clear(); return; }

                // Ignore if over controls
                if (target.closest('.gbn-controls-group') || target.closest('.gbn-width-dropdown')) return;

                var blockEl = target.closest('.gbn-block');
                
                if (blockEl && blockEl !== currentBlock) {
                    // Find block object
                    // We need to find the block object associated with this element. 
                    // Usually state.all() has it, but searching every frame is slow.
                    // Ideally the element has the ID.
                    // Assuming state.get() works if we have an ID, but we don't attach ID to DOM yet?
                    // Let's assume we can find it via state.all() for now or attach ID to DOM in ensureBlockSetup.
                    
                    // Optimization: Attach ID to DOM in ensureBlockSetup
                    var blockId = blockEl.getAttribute('data-gbn-id');
                    var block = blockId ? state.get(blockId) : null;
                    
                    // Fallback search if no ID (shouldn't happen with new ensureBlockSetup)
                    if (!block) {
                        block = state.all().find(b => b.element === blockEl);
                    }

                    if (block) HoverManager.activate(block);
                } else if (!blockEl && currentBlock) {
                    HoverManager.clear();
                }
            },
            activate: function(block) {
                if (currentBlock && currentBlock !== block.element) {
                    if (currentBlock.__gbnRootControls) currentBlock.__gbnRootControls.style.display = 'none';
                    currentBlock.classList.remove('gbn-show-controls');
                }
                
                currentBlock = block.element;
                currentBlock.classList.add('gbn-show-controls');
                
                // Move Singleton Controls
                GlobalControls.attachTo(block);

                // Root Insertion Logic
                if (block.role === 'principal') {
                    ensureRootInsertionButtons(block);
                }
            },
            clear: function() {
                if (currentBlock) {
                    currentBlock.classList.remove('gbn-show-controls');
                    if (currentBlock.__gbnRootControls) currentBlock.__gbnRootControls.style.display = 'none';
                    currentBlock = null;
                }
                GlobalControls.detach();
            }
        };

        function ensureBlockSetup(block) {
            ensureBaseline(block);
            if (!block || !block.element) { return; }
            block.element.classList.add('gbn-block');
            block.element.setAttribute('data-gbn-role', block.role || 'block');
            block.element.setAttribute('data-gbn-id', block.id); // Critical for HoverManager lookup
            
            // NOTE: No event listeners, no per-block controls.
            
            if (!active) {
                block.element.classList.remove('gbn-show-controls');
            }
        }

        function ensureRootInsertionButtons(block) {
            if (block.element.__gbnRootControls) {
                block.element.__gbnRootControls.style.display = 'block';
                return;
            }

            var container = document.createElement('div');
            container.className = 'gbn-root-controls';
            
            var btnTop = document.createElement('button');
            btnTop.className = 'gbn-root-add-btn gbn-root-add-top';
            btnTop.innerHTML = '+'; btnTop.title = 'Añadir Sección Arriba';
            btnTop.onclick = (e) => { e.stopPropagation(); Gbn.ui.library.open(block.element, 'before', ['principal']); };

            var btnBottom = document.createElement('button');
            btnBottom.className = 'gbn-root-add-btn gbn-root-add-bottom';
            btnBottom.innerHTML = '+'; btnBottom.title = 'Añadir Sección Abajo';
            btnBottom.onclick = (e) => { e.stopPropagation(); Gbn.ui.library.open(block.element, 'after', ['principal']); };

            container.appendChild(btnTop);
            container.appendChild(btnBottom);
            
            block.element.appendChild(container);
            block.element.__gbnRootControls = container;
            block.element.__gbnRootControls.style.display = 'block';
        }

        function setActive(next) {
            active = !!next; document.documentElement.classList.toggle('gbn-active', active);
            
            if (Gbn.ui && Gbn.ui.dock && typeof Gbn.ui.dock.updateState === 'function') {
                Gbn.ui.dock.updateState(active);
            }

            state.all().forEach(ensureBlockSetup);
            persistState();
            
            if (active) {
                HoverManager.start();
                if (Gbn.ui && Gbn.ui.dragDrop && typeof Gbn.ui.dragDrop.enable === 'function') {
                    Gbn.ui.dragDrop.enable();
                }
            } else {
                HoverManager.stop();
                if (Gbn.ui && Gbn.ui.dragDrop && typeof Gbn.ui.dragDrop.disable === 'function') {
                    Gbn.ui.dragDrop.disable();
                }
                if (Gbn.ui && Gbn.ui.panel && typeof Gbn.ui.panel.close === 'function') { Gbn.ui.panel.close(); }
            }
        }

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

        function init(blocks, options) {
            cfg = options || {}; (blocks || state.all()).forEach(ensureBaseline);
            if (!cfg.isEditor) { return; }
            if (Gbn.ui && Gbn.ui.panel && typeof Gbn.ui.panel.init === 'function') { Gbn.ui.panel.init(); }
            if (Gbn.ui && Gbn.ui.dock && typeof Gbn.ui.dock.init === 'function') { Gbn.ui.dock.init(); }

            var stored = readStoredState(); var initial = typeof stored === 'boolean' ? stored : !!cfg.initialActive; setActive(initial);
            global.addEventListener('gbn:contentHydrated', handleHydrated);
            
            // Listen for config changes to update width label if needed
            window.addEventListener('gbn:configChanged', (e) => {
                if (GlobalControls.currentBlock && e.detail && e.detail.id === GlobalControls.currentBlock.id) {
                    GlobalControls.updateWidthLabel(GlobalControls.currentBlock);
                }
            });
        }

        return { 
            init: init, 
            setActive: setActive,
            setLocked: function(v) { isLocked = !!v; }
        };
    })();

    Gbn.ui = Gbn.ui || {};
    Gbn.ui.inspector = inspector;
})(window);


