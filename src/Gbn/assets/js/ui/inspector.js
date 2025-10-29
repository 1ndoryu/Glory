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
        var btn = block.element.__gbnBtn;
        if (btn) { return btn; }
        btn = document.createElement('button');
        btn.type = 'button'; btn.className = 'gbn-config-btn'; btn.textContent = 'Config';
        btn.addEventListener('click', function (event) {
            event.preventDefault(); event.stopPropagation();
            if (Gbn.ui && Gbn.ui.panel && typeof Gbn.ui.panel.open === 'function') {
                Gbn.ui.panel.open(block);
            }
        });
        block.element.appendChild(btn);
        block.element.__gbnBtn = btn;
        return btn;
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
            var btn = block.element.__gbnBtn;
            if (btn && !btn.parentElement) { block.element.appendChild(btn); }
            if (active) {
                btn = btn || createConfigButton(block);
                if (btn) {
                    btn.setAttribute('aria-hidden', 'false');
                    btn.disabled = false; btn.tabIndex = 0;
                }
            } else if (btn) {
                btn.setAttribute('aria-hidden', 'true');
                btn.disabled = true; btn.tabIndex = -1;
            }
        }

        function setActive(next) {
            active = !!next; document.documentElement.classList.toggle('gbn-active', active);
            if (wrapper) { wrapper.setAttribute('data-enabled', active ? '1' : '0'); }
            updateToggleLabel();
            secondaryButtons.forEach(function (btn) {
                if (!btn) { return; }
                btn.disabled = !active; btn.setAttribute('aria-disabled', active ? 'false' : 'true');
            });
            state.all().forEach(ensureBlockSetup);
            persistState(); adjustMainPadding();
            if (!active && Gbn.ui && Gbn.ui.panel && typeof Gbn.ui.panel.close === 'function') { Gbn.ui.panel.close(); }
        }

        function handleToggle(event) { if (event) { event.preventDefault(); } setActive(!active); }

        function attachToggle() {
            wrapper = document.getElementById('glory-gbn-root'); if (!wrapper) { return; }
            toggleBtn = wrapper.querySelector('#gbn-toggle'); if (toggleBtn) { toggleBtn.addEventListener('click', handleToggle); }
            secondaryButtons = utils.qsa('.gbn-secondary-btn', wrapper);
            secondaryButtons.forEach(function (btn) {
                btn.addEventListener('click', function (event) {
                    event.preventDefault(); if (!active) { return; }
                    var action = btn.dataset.gbnAction;
                    if (action === 'theme' && Gbn.ui.panel) { Gbn.ui.panel.openTheme(); }
                    else if (action === 'page' && Gbn.ui.panel) { Gbn.ui.panel.openPage(); }
                    else if (action === 'restore' && Gbn.ui.panel) { Gbn.ui.panel.openRestore(); }
                });
            });
        }

        function handleHydrated(event) {
            var detail = event && event.detail ? event.detail : {};
            if (!detail.id) { return; }
            var block = state.get(detail.id);
            if (block) { ensureBlockSetup(block); }
        }

        function init(blocks, options) {
            cfg = options || {}; (blocks || state.all()).forEach(ensureBaseline);
            if (!cfg.isEditor) { return; }
            if (Gbn.ui && Gbn.ui.panel && typeof Gbn.ui.panel.init === 'function') { Gbn.ui.panel.init(); }
            attachToggle();
            var stored = readStoredState(); var initial = typeof stored === 'boolean' ? stored : !!cfg.initialActive; setActive(initial);
            global.addEventListener('gbn:contentHydrated', handleHydrated);
        }

        return { init: init, setActive: setActive };
    })();

    Gbn.ui = Gbn.ui || {};
    Gbn.ui.inspector = inspector;
})(window);


