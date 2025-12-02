;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    var utils = Gbn.utils;
    var state = Gbn.state;

    function ensureBaseline(block) {
        if (!block || !block.element) { return; }
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
                    allowed = ['content', 'term_list', 'image'];
                } else {
                    position = 'after';
                    allowed = ['content', 'term_list', 'image'];
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
        container.appendChild(btnAdd);
        container.appendChild(btnDelete);
        
        block.element.appendChild(container);
        block.element.__gbnControls = container;
        return container;
    }

    var inspector = (function () {
        var active = false;
        var cfg = {};
        var toggleBtn = null;
        var wrapper = null;
        var secondaryButtons = [];
        var mainNode = null;
        var mainPaddingCaptured = false;
        var mainPaddingValue = '';

        function ensureMainNode() {
            if (!mainNode) { mainNode = document.querySelector('main'); }
            return mainNode;
        }

        function adjustMainPadding() {
            var node = ensureMainNode();
            if (!node) { return; }
            if (active) {
                if (!mainPaddingCaptured) { mainPaddingCaptured = true; mainPaddingValue = node.style.paddingTop || ''; }
                node.style.paddingTop = '100px'; node.classList.add('gbn-main-offset');
            } else if (mainPaddingCaptured) {
                if (mainPaddingValue) { node.style.paddingTop = mainPaddingValue; }
                else {
                    node.style.removeProperty('padding-top');
                    if (!node.getAttribute('style')) { node.removeAttribute('style'); }
                }
                node.classList.remove('gbn-main-offset');
            }
        }

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

        function ensureBlockSetup(block) {
            ensureBaseline(block);
            if (!block || !block.element) { return; }
            block.element.classList.add('gbn-block');
            block.element.setAttribute('data-gbn-role', block.role || 'block');
            var controls = block.element.__gbnControls;
            if (controls && !controls.parentElement) { block.element.appendChild(controls); }
            
            // Remove old event listeners if any (to avoid duplicates if called multiple times)
            // Ideally we should store the handler reference, but for now let's rely on the fact 
            // that we only add them if active.
            // Actually, let's just add them once.
            if (!block.element.__gbnEventsAttached) {
                block.element.addEventListener('mouseover', function(e) {
                    if (!active) return;
                    e.stopPropagation();
                    // Remove class from all others to ensure exclusivity
                    document.querySelectorAll('.gbn-show-controls').forEach(function(el) {
                        el.classList.remove('gbn-show-controls');
                    });
                    block.element.classList.add('gbn-show-controls');
                });
                
                block.element.addEventListener('mouseout', function(e) {
                    if (!active) return;
                    // e.stopPropagation(); // Don't stop propagation here, let it bubble so parent can handle it?
                    // Actually, if we leave child, we might enter parent.
                    block.element.classList.remove('gbn-show-controls');
                });
                block.element.__gbnEventsAttached = true;
            }

            if (active) {
                controls = controls || createConfigButton(block);
                if (controls) {
                    controls.style.display = 'flex';
                    var btns = controls.querySelectorAll('button');
                    btns.forEach(function(b) { b.disabled = false; b.tabIndex = 0; });
                }
            } else if (controls) {
                controls.style.display = 'none';
                var btns = controls.querySelectorAll('button');
                btns.forEach(function(b) { b.disabled = true; b.tabIndex = -1; });
                block.element.classList.remove('gbn-show-controls');
            }
        }

        function setActive(next) {
            active = !!next; document.documentElement.classList.toggle('gbn-active', active);
            // if (wrapper) { wrapper.setAttribute('data-enabled', active ? '1' : '0'); } // Old wrapper logic
            
            // Update Dock State
            if (Gbn.ui && Gbn.ui.dock && typeof Gbn.ui.dock.updateState === 'function') {
                Gbn.ui.dock.updateState(active);
            }

            state.all().forEach(ensureBlockSetup);
            persistState(); adjustMainPadding();
            if (active) {
                if (Gbn.ui && Gbn.ui.dragDrop && typeof Gbn.ui.dragDrop.enable === 'function') {
                    Gbn.ui.dragDrop.enable();
                }
            } else {
                if (Gbn.ui && Gbn.ui.dragDrop && typeof Gbn.ui.dragDrop.disable === 'function') {
                    Gbn.ui.dragDrop.disable();
                }
                if (Gbn.ui && Gbn.ui.panel && typeof Gbn.ui.panel.close === 'function') { Gbn.ui.panel.close(); }
            }
        }

        function handleToggle(event) { if (event) { event.preventDefault(); } setActive(!active); }

        function handleHydrated(event) {
            var detail = event && event.detail ? event.detail : {};
            
            // Si hay IDs específicos (array o single)
            if (detail.ids && Array.isArray(detail.ids)) {
                detail.ids.forEach(function(id) {
                    var block = state.get(id);
                    if (block) { ensureBlockSetup(block); }
                });
            } else if (detail.id) {
                var block = state.get(detail.id);
                if (block) { ensureBlockSetup(block); }
            } else {
                // Si no hay ID, re-procesar todo (fallback)
                state.all().forEach(ensureBlockSetup);
            }
        }

        function init(blocks, options) {
            cfg = options || {}; (blocks || state.all()).forEach(ensureBaseline);
            if (!cfg.isEditor) { return; }
            if (Gbn.ui && Gbn.ui.panel && typeof Gbn.ui.panel.init === 'function') { Gbn.ui.panel.init(); }
            
            // Initialize Dock
            if (Gbn.ui && Gbn.ui.dock && typeof Gbn.ui.dock.init === 'function') {
                Gbn.ui.dock.init();
            }

            // Ocultar UI antigua si existe
            // var oldWrapper = document.getElementById('glory-gbn-root');
            // if (oldWrapper) { oldWrapper.style.display = 'none'; }

            var stored = readStoredState(); var initial = typeof stored === 'boolean' ? stored : !!cfg.initialActive; setActive(initial);
            global.addEventListener('gbn:contentHydrated', handleHydrated);
        }

        return { init: init, setActive: setActive };
    })();

    Gbn.ui = Gbn.ui || {};
    Gbn.ui.inspector = inspector;
})(window);


