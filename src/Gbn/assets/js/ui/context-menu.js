;(function(global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    var ui = Gbn.ui = Gbn.ui || {};

    // Iconos de Roles (componentes)
    var ROLE_ICONS = {
        principal: '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>',
        secundario: '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="9" y1="3" x2="9" y2="21"/></svg>',
        text: '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7V4h16v3M9 20h6M12 4v16"/></svg>',
        button: '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="8" width="18" height="8" rx="2"/></svg>',
        image: '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>',
        postRender: '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>',
        postItem: '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6"/></svg>',
        postField: '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>'
    };

    // Iconos de Tabs
    var TAB_ICONS = {
        'Contenido': '<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/></svg>',
        'Estilo': '<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M14.31 8l5.74 9.94M9.69 8h11.48M7.38 12l5.74-9.94"/></svg>',
        'Layout': '<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="9" y1="3" x2="9" y2="21"/></svg>',
        'Query': '<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></svg>',
        'Interacción': '<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3l7.07 16.97 2.51-7.39 7.39-2.51L3 3z"/></svg>',
        'Avanzado': '<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09a1.65 1.65 0 00-1-1.51 1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09a1.65 1.65 0 001.51-1 1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/></svg>'
    };

    var ContextMenu = {
        menu: null,

        init: function() {
            this.createMenu();
            this.bindEvents();
            this.injectStyles();
        },

        injectStyles: function() {
            var style = document.createElement('style');
            style.textContent = `
                .gbn-context-menu {
                    position: fixed;
                    z-index: 999999;
                    background: #fff;
                    box-shadow: 0 4px 16px rgba(0,0,0,0.12), 0 0 0 1px rgba(0,0,0,0.05);
                    border-radius: 8px;
                    padding: 4px;
                    min-width: 160px;
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                    font-size: 12px;
                }
                .gbn-ctx-block {
                    padding: 2px;
                }
                .gbn-ctx-block + .gbn-ctx-block {
                    border-top: 1px solid #f0f0f0;
                    margin-top: 2px;
                    padding-top: 4px;
                }
                .gbn-ctx-block-header {
                    padding: 4px 8px;
                    font-weight: 600;
                    font-size: 11px;
                    color: #333;
                    display: flex;
                    align-items: center;
                    gap: 5px;
                    border-radius: 4px;
                    cursor: pointer;
                    transition: background 0.1s;
                }
                .gbn-ctx-block-header:hover {
                    background: #f5f5f5;
                }
                .gbn-ctx-block-header svg {
                    opacity: 0.7;
                }
                .gbn-ctx-tabs {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 2px;
                    padding: 2px 0 2px 20px;
                }
                .gbn-ctx-tab {
                    padding: 3px 6px;
                    border-radius: 4px;
                    cursor: pointer;
                    color: #666;
                    display: flex;
                    align-items: center;
                    gap: 4px;
                    transition: all 0.1s;
                    font-size: 11px;
                }
                .gbn-ctx-tab:hover {
                    background: #e8f4fc;
                    color: #2271b1;
                }
                .gbn-ctx-tab svg {
                    opacity: 0.6;
                }
                .gbn-ctx-tab:hover svg {
                    opacity: 1;
                }
                .gbn-ctx-delete {
                    padding: 3px 6px;
                    border-radius: 4px;
                    cursor: pointer;
                    color: #999;
                    font-size: 10px;
                    margin-left: auto;
                }
                .gbn-ctx-delete:hover {
                    background: #fef0f0;
                    color: #d63638;
                }
            `;
            document.head.appendChild(style);
        },

        createMenu: function() {
            if (this.menu) return;
            this.menu = document.createElement('div');
            this.menu.className = 'gbn-context-menu';
            this.menu.style.display = 'none';
            document.body.appendChild(this.menu);
        },

        bindEvents: function() {
            document.addEventListener('contextmenu', this.handleContextMenu.bind(this));
            document.addEventListener('click', this.close.bind(this));
            document.addEventListener('scroll', this.close.bind(this), true);
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') this.close();
            });
        },

        getBlocksAtPoint: function(x, y) {
            var elements = document.elementsFromPoint(x, y);
            var blocks = [];
            var seen = new Set();
            
            for (var i = 0; i < elements.length; i++) {
                var el = elements[i];
                var blockEl = el.closest('.gbn-block');
                
                while (blockEl) {
                    var id = blockEl.getAttribute('data-gbn-id');
                    if (id && !seen.has(id) && Gbn.state) {
                        var block = Gbn.state.get(id);
                        if (block) {
                            blocks.push(block);
                            seen.add(id);
                        }
                    }
                    blockEl = blockEl.parentElement ? blockEl.parentElement.closest('.gbn-block') : null;
                }
            }
            return blocks;
        },

        handleContextMenu: function(e) {
            if (!document.documentElement.classList.contains('gbn-active')) return;
            if (e.target.closest('.gbn-panel, .gbn-controls-group, .gbn-context-menu, .gbn-dock')) return;
            
            var blocks = this.getBlocksAtPoint(e.clientX, e.clientY);
            
            if (blocks.length > 0) {
                e.preventDefault();
                e.stopPropagation();
                this.renderContent(blocks);
                this.show(e.clientX, e.clientY);
            }
        },

        renderContent: function(blocks) {
            var self = this;
            this.menu.innerHTML = '';
            
            blocks.forEach(function(block) {
                var group = document.createElement('div');
                group.className = 'gbn-ctx-block';
                
                // Header con icono del rol
                var header = document.createElement('div');
                header.className = 'gbn-ctx-block-header';
                
                var icon = ROLE_ICONS[block.role] || ROLE_ICONS.secundario;
                var roleName = (block.role || 'bloque').charAt(0).toUpperCase() + (block.role || 'bloque').slice(1);
                
                header.innerHTML = icon + '<span>' + roleName + '</span>';
                
                // Click en header abre el panel directamente
                header.onclick = function(e) {
                    e.stopPropagation();
                    self.close();
                    self.openPanel(block, null);
                };
                
                // Botón eliminar inline
                var del = document.createElement('span');
                del.className = 'gbn-ctx-delete';
                del.innerHTML = '✕';
                del.title = 'Eliminar';
                del.onclick = function(e) {
                    e.stopPropagation();
                    self.close();
                    if (Gbn.state) Gbn.state.deleteBlock(block.id);
                };
                header.appendChild(del);
                
                group.appendChild(header);
                
                // Tabs con iconos
                var schema = block.schema || [];
                var tabs = new Set();
                schema.forEach(function(field) {
                    if (field.tab) tabs.add(field.tab);
                });
                
                if (tabs.size > 0) {
                    var tabsContainer = document.createElement('div');
                    tabsContainer.className = 'gbn-ctx-tabs';
                    
                    var sortedTabs = self.sortTabs(Array.from(tabs));
                    sortedTabs.forEach(function(tab) {
                        var tabEl = document.createElement('span');
                        tabEl.className = 'gbn-ctx-tab';
                        
                        var tabIcon = TAB_ICONS[tab] || '';
                        tabEl.innerHTML = tabIcon + tab;
                        
                        tabEl.onclick = function(e) {
                            e.stopPropagation();
                            self.close();
                            self.openPanel(block, tab);
                        };
                        
                        tabsContainer.appendChild(tabEl);
                    });
                    
                    group.appendChild(tabsContainer);
                }
                
                self.menu.appendChild(group);
            });
        },

        sortTabs: function(tabs) {
            var order = ['Contenido', 'Estilo', 'Layout', 'Query', 'Interacción', 'Avanzado'];
            return tabs.sort(function(a, b) {
                var ia = order.indexOf(a);
                var ib = order.indexOf(b);
                if (ia !== -1 && ib !== -1) return ia - ib;
                if (ia !== -1) return -1;
                if (ib !== -1) return 1;
                return a.localeCompare(b);
            });
        },

        openPanel: function(block, tab) {
            if (Gbn.ui && Gbn.ui.panel) {
                Gbn.ui.panel.open(block);
                if (tab) {
                    setTimeout(function() {
                        var panel = document.getElementById('gbn-panel');
                        if (panel) {
                            var tabBtns = panel.querySelectorAll('.gbn-tab-btn');
                            for (var i = 0; i < tabBtns.length; i++) {
                                if (tabBtns[i].textContent && tabBtns[i].textContent.includes(tab)) {
                                    tabBtns[i].click();
                                    break;
                                }
                            }
                        }
                    }, 50);
                }
            }
        },

        show: function(x, y) {
            this.menu.style.visibility = 'hidden';
            this.menu.style.display = 'block';
            
            var rect = this.menu.getBoundingClientRect();
            var winW = window.innerWidth;
            var winH = window.innerHeight;

            if (x + rect.width > winW) x = winW - rect.width - 10;
            if (y + rect.height > winH) y = winH - rect.height - 10;
            x = Math.max(10, x);
            y = Math.max(10, y);

            this.menu.style.left = x + 'px';
            this.menu.style.top = y + 'px';
            this.menu.style.visibility = 'visible';
        },

        close: function() {
            if (this.menu) {
                this.menu.style.display = 'none';
            }
        }
    };

    ui.contextMenu = ContextMenu;
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() { ContextMenu.init(); });
    } else {
        ContextMenu.init();
    }

})(window);
