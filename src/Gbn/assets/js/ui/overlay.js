;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    var utils = Gbn.utils;
    var state = Gbn.state;

    var panelRoot = null;
    var panelBody = null;
    var panelTitle = null;
    var panelFooter = null;
    var activeBlock = null;
    var listenersBound = false;

    function renderPlaceholder(message) {
        if (!panelBody) { return; }
        var text = message || 'Selecciona un bloque para configurar.';
        panelBody.innerHTML = '<div class="gbn-panel-empty">' + text + '</div>';
    }

    function ensurePanelMounted() {
        if (panelRoot) { return panelRoot; }

        panelRoot = document.getElementById('gbn-panel');
        if (!panelRoot) {
            panelRoot = document.createElement('aside');
            panelRoot.id = 'gbn-panel';
            panelRoot.setAttribute('aria-hidden', 'true');
            panelRoot.innerHTML = ''
                + '<header class="gbn-header">'
                + '  <span class="gbn-header-title">GBN Panel</span>'
                + '  <button type="button" class="gbn-header-close" data-gbn-action="close-panel" aria-label="Close panel">×</button>'
                + '</header>'
                + '<div class="gbn-body"></div>'
                + '<footer class="gbn-footer">'
                + '  <button type="button" class="gbn-footer-primary" disabled>Guardar</button>'
                + '</footer>';
            document.body.appendChild(panelRoot);
        }

        panelBody = panelRoot.querySelector('.gbn-body');
        panelTitle = panelRoot.querySelector('.gbn-header-title');
        panelFooter = panelRoot.querySelector('.gbn-footer-primary');

        renderPlaceholder();

        if (!listenersBound) {
            listenersBound = true;
            var closeBtn = panelRoot.querySelector('[data-gbn-action="close-panel"]');
            if (closeBtn) {
                closeBtn.addEventListener('click', function (event) {
                    event.preventDefault();
                    panel.close();
                });
            }
            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && panel.isOpen()) {
                    panel.close();
                }
            });
        }

        return panelRoot;
    }

    var panel = {
        init: function () {
            ensurePanelMounted();
        },
        isOpen: function () {
            return !!(panelRoot && panelRoot.classList.contains('is-open'));
        },
        open: function (block) {
            ensurePanelMounted();
            activeBlock = block || null;
            if (panelRoot) {
                panelRoot.classList.add('is-open');
                panelRoot.setAttribute('aria-hidden', 'false');
            }
            var title = 'GBN Panel';
            if (block) {
                title = block.meta && block.meta.label
                    ? block.meta.label
                    : (block.role ? 'Configuración: ' + block.role : 'Configuración');
            }
            if (panelTitle) {
                panelTitle.textContent = title;
            }
            if (!panelBody) {
                return;
            }
            if (!block) {
                renderPlaceholder();
                return;
            }
            panelBody.innerHTML = ''
                + '<div class="gbn-panel-block-summary">'
                + '  <p class="gbn-panel-block-id">ID: <code>' + block.id + '</code></p>'
                + '  <p class="gbn-panel-block-role">Rol: <strong>' + (block.role || 'block') + '</strong></p>'
                + '</div>'
                + '<div class="gbn-panel-coming-soon">El panel interactivo estará disponible en la siguiente fase.</div>';
            if (panelFooter) {
                panelFooter.disabled = true;
            }
            utils.debug('Panel abierto', block ? block.id : null);
        },
        close: function () {
            if (panelRoot) {
                panelRoot.classList.remove('is-open');
                panelRoot.setAttribute('aria-hidden', 'true');
            }
            activeBlock = null;
            renderPlaceholder();
            utils.debug('Panel cerrado');
        }
    };

    function ensureBaseline(block) {
        if (!block || !block.element) {
            return;
        }
        block.element.classList.add('gbn-node');
        block.element.setAttribute('data-gbn-ready', '1');
    }

    function getStoreKey(cfg) {
        if (!cfg || !cfg.isEditor) {
            return null;
        }
        var parts = ['gbn-active'];
        if (cfg.userId) {
            parts.push(String(cfg.userId));
        }
        parts.push(String(cfg.pageId || 'global'));
        return parts.join('-');
    }

    function createConfigButton(block) {
        if (!block || !block.element) {
            return null;
        }
        var btn = block.element.__gbnBtn;
        if (btn) {
            return btn;
        }
        btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'gbn-config-btn';
        btn.textContent = 'Config';
        btn.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            panel.open(block);
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
        var mainNode = null;
        var mainPaddingCaptured = false;
        var mainPaddingValue = '';

        function ensureMainNode() {
            if (!mainNode) {
                mainNode = document.querySelector('main');
            }
            return mainNode;
        }

        function adjustMainPadding() {
            var node = ensureMainNode();
            if (!node) {
                return;
            }
            if (active) {
                if (!mainPaddingCaptured) {
                    mainPaddingCaptured = true;
                    mainPaddingValue = node.style.paddingTop || '';
                }
                node.style.paddingTop = '100px';
                node.classList.add('gbn-main-offset');
            } else if (mainPaddingCaptured) {
                if (mainPaddingValue) {
                    node.style.paddingTop = mainPaddingValue;
                } else {
                    node.style.removeProperty('padding-top');
                    if (!node.getAttribute('style')) {
                        node.removeAttribute('style');
                    }
                }
                node.classList.remove('gbn-main-offset');
            }
        }

        function updateToggleLabel() {
            if (!toggleBtn) {
                return;
            }
            toggleBtn.dataset.gbnState = active ? 'on' : 'off';
            toggleBtn.setAttribute('aria-pressed', active ? 'true' : 'false');
            toggleBtn.textContent = active ? 'Close GBN' : 'Open GBN';
        }

        function persistState() {
            var key = getStoreKey(cfg);
            if (!key) {
                return;
            }
            try {
                global.localStorage.setItem(key, active ? '1' : '0');
            } catch (_) {}
        }

        function readStoredState() {
            var key = getStoreKey(cfg);
            if (!key) {
                return null;
            }
            try {
                var stored = global.localStorage.getItem(key);
                if (stored === '1') {
                    return true;
                }
                if (stored === '0') {
                    return false;
                }
            } catch (_) {}
            return null;
        }

        function ensureBlockSetup(block) {
            ensureBaseline(block);
            if (!block || !block.element) {
                return;
            }
            block.element.classList.add('gbn-block');
            block.element.setAttribute('data-gbn-role', block.role || 'block');
            var btn = block.element.__gbnBtn;
            if (btn && !btn.parentElement) {
                block.element.appendChild(btn);
            }
            if (active) {
                btn = btn || createConfigButton(block);
                if (btn) {
                    btn.setAttribute('aria-hidden', 'false');
                }
            } else if (btn) {
                btn.setAttribute('aria-hidden', 'true');
            }
        }

        function setActive(next) {
            active = !!next;
            document.documentElement.classList.toggle('gbn-active', active);
            if (wrapper) {
                wrapper.setAttribute('data-enabled', active ? '1' : '0');
            }
            updateToggleLabel();
            state.all().forEach(ensureBlockSetup);
            persistState();
            adjustMainPadding();
            if (!active) {
                panel.close();
            }
        }

        function handleToggle(event) {
            if (event) {
                event.preventDefault();
            }
            setActive(!active);
        }

        function attachToggle() {
            wrapper = document.getElementById('glory-gbn-root');
            if (!wrapper) {
                return;
            }
            toggleBtn = wrapper.querySelector('#gbn-toggle');
            if (toggleBtn) {
                toggleBtn.addEventListener('click', handleToggle);
            }
        }

        function handleHydrated(event) {
            var detail = event && event.detail ? event.detail : {};
            if (!detail.id) {
                return;
            }
            var block = state.get(detail.id);
            if (block) {
                ensureBlockSetup(block);
            }
        }

        function init(blocks, options) {
            cfg = options || {};
            (blocks || state.all()).forEach(ensureBaseline);

            if (!cfg.isEditor) {
                return;
            }

            panel.init();

            attachToggle();

            var stored = readStoredState();
            var initial = typeof stored === 'boolean' ? stored : !!cfg.initialActive;
            setActive(initial);

            global.addEventListener('gbn:contentHydrated', handleHydrated);
        }

        return {
            init: init,
            setActive: setActive,
        };
    })();

    Gbn.ui = Gbn.ui || {};
    Gbn.ui.panel = panel;
    Gbn.ui.inspector = inspector;
})(window);

