;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    var utils = Gbn.utils;
    var state = Gbn.state;

    function ensureBaseline(block) {
        if (!block || !block.element) { return; }
        block.element.classList.add('gbn-node');
        block.element.setAttribute('data-gbn-ready', '1');
        if (Gbn.ui && Gbn.ui.panel && typeof Gbn.ui.panel._applyStyles === 'function') {
            Gbn.ui.panel._applyStyles(block);
        }
    }

    function createConfigButton(block) {
        if (!block || !block.element) { return null; }
        var container = block.element.__gbnControls;
        if (container) { return container; }
        
        container = document.createElement('div');
        container.className = 'gbn-controls-group';
        
        var btnConfig = document.createElement('button');
        btnConfig.type = 'button'; btnConfig.className = 'gbn-config-btn'; btnConfig.textContent = 'Config';
        btnConfig.addEventListener('click', function (event) {
            event.preventDefault(); event.stopPropagation();
            if (Gbn.ui && Gbn.ui.panel && typeof Gbn.ui.panel.open === 'function') {
                Gbn.ui.panel.open(block);
            }
        });
        
        var btnDelete = document.createElement('button');
        btnDelete.type = 'button'; btnDelete.className = 'gbn-delete-btn'; btnDelete.innerHTML = '&times;';
        btnDelete.title = 'Eliminar bloque';
        btnDelete.addEventListener('click', function (event) {
            event.preventDefault(); event.stopPropagation();
            // Eliminación directa sin confirmación (según petición de usuario)
            state.deleteBlock(block.id);
        });

        var btnAdd = document.createElement('button');
        btnAdd.type = 'button'; btnAdd.className = 'gbn-add-btn'; btnAdd.innerHTML = '+';
        btnAdd.title = 'Añadir bloque';
        btnAdd.addEventListener('click', function (event) {
            event.preventDefault(); event.stopPropagation();
            if (Gbn.ui && Gbn.ui.library && typeof Gbn.ui.library.open === 'function') {
                var position = 'after';
                var allowed = [];
                
                // Lógica de roles permitidos
                if (block.role === 'principal') {
                    // Si estoy en un principal, puedo añadir dentro (secundario) o después (otro principal)
                    // Pero el botón "+" es contextual. Asumamos que el "+" del toolbar es para "añadir hijo" si es contenedor,
                    // o "añadir hermano" si no lo es?
                    // El usuario dijo "que no se pueda agregar divprimarios dentro de los div secundarios".
                    
                    // Simplificación:
                    // Si click en "+" de Principal -> Añadir Secundario DENTRO (append)
                    position = 'append';
                    allowed = ['secundario'];
                } else if (block.role === 'secundario') {
                    // Si click en "+" de Secundario -> Añadir Contenido DENTRO (append)
                    position = 'append';
                    allowed = ['content', 'term_list', 'image'];
                } else {
                    // Si click en "+" de Contenido -> Añadir Contenido DESPUÉS (after)
                    position = 'after';
                    allowed = ['content', 'term_list', 'image'];
                }
                
                Gbn.ui.library.open(block.element, position, allowed);
            }
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
            if (active) {
                controls = controls || createConfigButton(block);
                if (controls) {
                    controls.style.display = 'flex';
                    // Activar botones internos
                    var btns = controls.querySelectorAll('button');
                    btns.forEach(function(b) { b.disabled = false; b.tabIndex = 0; });
                }
            } else if (controls) {
                controls.style.display = 'none';
                var btns = controls.querySelectorAll('button');
                btns.forEach(function(b) { b.disabled = true; b.tabIndex = -1; });
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
            var oldWrapper = document.getElementById('glory-gbn-root');
            if (oldWrapper) { oldWrapper.style.display = 'none'; }

            var stored = readStoredState(); var initial = typeof stored === 'boolean' ? stored : !!cfg.initialActive; setActive(initial);
            global.addEventListener('gbn:contentHydrated', handleHydrated);
        }

        return { init: init, setActive: setActive };
    })();

    Gbn.ui = Gbn.ui || {};
    Gbn.ui.inspector = inspector;
})(window);


