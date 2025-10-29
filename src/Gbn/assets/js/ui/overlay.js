;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    var utils = Gbn.utils;
    var state = Gbn.state;

    var panel = {
        open: function (block) {
            utils.debug('Open config panel (stub)', block ? block.id : null);
        },
        close: function () {
            utils.debug('Close config panel (stub)');
        },
        init: function () {
            utils.debug('Init config panel (stub)');
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

