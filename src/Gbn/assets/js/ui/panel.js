;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    var utils = Gbn.utils;
    var state = Gbn.state;
    var styleManager = Gbn.styleManager;

    var panelRoot = null;
    var panelBody = null;
    var panelTitle = null;
    var panelFooter = null;
    var activeBlock = null;
    var panelForm = null;
    var panelMode = 'idle';
    var panelNotice = null;
    var panelStatusTimer = null;
    var listenersBound = false;

    function renderPlaceholder(message) {
        if (!panelBody) { return; }
        panelMode = 'idle';
        panelForm = null;
        var text = message || 'Selecciona un bloque para configurar.';
        panelBody.innerHTML = '<div class="gbn-panel-empty">' + text + '</div>';
        if (panelFooter) {
            panelFooter.disabled = true;
            panelFooter.textContent = 'Guardar (próximamente)';
        }
        setPanelStatus('Cambios en vivo');
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
                + '  <span class="gbn-footer-status">Cambios en vivo</span>'
                + '  <button type="button" class="gbn-footer-primary" disabled>Guardar (próximamente)</button>'
                + '</footer>';
            document.body.appendChild(panelRoot);
        }
        panelBody = panelRoot.querySelector('.gbn-body');
        panelTitle = panelRoot.querySelector('.gbn-header-title');
        panelFooter = panelRoot.querySelector('.gbn-footer-primary');
        panelNotice = panelRoot.querySelector('.gbn-footer-status');
        if (panelFooter && !panelFooter.__gbnBound) {
            panelFooter.__gbnBound = true;
            panelFooter.disabled = false;
            panelFooter.textContent = 'Guardar';
            panelFooter.addEventListener('click', function (event) {
                event.preventDefault();
                if (!Gbn.persistence || typeof Gbn.persistence.savePageConfig !== 'function') { return; }
                setPanelStatus('Guardando...');
                Gbn.persistence.savePageConfig().then(function (res) {
                    flashPanelStatus(res && res.success ? 'Guardado' : 'Error al guardar');
                }).catch(function () { flashPanelStatus('Error al guardar'); });
            });
        }
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

    function setActiveBlock(nextBlock) {
        if (activeBlock && activeBlock.element) {
            activeBlock.element.classList.remove('gbn-block-active');
            if (activeBlock.element.__gbnBtn) {
                activeBlock.element.__gbnBtn.classList.remove('is-active');
            }
        }
        activeBlock = nextBlock || null;
        if (activeBlock && activeBlock.element) {
            activeBlock.element.classList.add('gbn-block-active');
            if (activeBlock.element.__gbnBtn) {
                activeBlock.element.__gbnBtn.classList.add('is-active');
            }
        }
    }

    function setPanelStatus(text) {
        if (panelNotice) {
            panelNotice.textContent = text;
        }
    }

    function flashPanelStatus(text) {
        if (!panelNotice) { return; }
        panelNotice.textContent = text;
        if (panelStatusTimer) { clearTimeout(panelStatusTimer); }
        panelStatusTimer = setTimeout(function () {
            panelNotice.textContent = 'Cambios en vivo';
        }, 1600);
    }

    function cloneConfig(config) {
        var output = utils.assign({}, config || {});
        Object.keys(output).forEach(function (key) {
            var item = output[key];
            if (item && typeof item === 'object' && !Array.isArray(item)) {
                output[key] = utils.assign({}, item);
            }
        });
        return output;
    }

    function getInlineValueForPath(block, path) {
        if (!block || !block.styles || !block.styles.inline || !path) {
            return null;
        }

        var inline = block.styles.inline;

        // Mapeo de rutas de configuración a propiedades CSS
        var pathToCssMap = {
            'padding.superior': 'padding-top',
            'padding.derecha': 'padding-right',
            'padding.inferior': 'padding-bottom',
            'padding.izquierda': 'padding-left',
            'height': 'height',
            'alineacion': 'text-align',
            'maxAncho': 'max-width',
            'fondo': 'background'
        };

        var cssProp = pathToCssMap[path];
        if (cssProp && inline[cssProp] !== undefined) {
            return inline[cssProp];
        }

        return null;
    }

    function getDefaultValueForPath(block, path) {
        if (!block || !path) { return null; }

        var defaults = getRoleDefaults(block.role);
        if (!defaults || !defaults.config) { return null; }

        var segments = path.split('.');
        var cursor = defaults.config;
        for (var i = 0; i < segments.length; i += 1) {
            if (cursor === null || cursor === undefined) { return null; }
            cursor = cursor[segments[i]];
        }
        return cursor;
    }

    function updateConfigValue(block, path, value) {
        if (!block || !path) { return; }

        var current = cloneConfig(block.config);
        var segments = path.split('.');
        var cursor = current;

        // Si el valor está vacío, intentar usar el valor inline o por defecto
        if (value === null || value === undefined || value === '') {
            var inlineValue = getInlineValueForPath(block, path);
            if (inlineValue !== null) {
                value = inlineValue;
            } else {
                var defaultValue = getDefaultValueForPath(block, path);
                if (defaultValue !== null && defaultValue !== undefined) {
                    value = defaultValue;
                }
            }
        }

        for (var i = 0; i < segments.length - 1; i += 1) {
            var key = segments[i];
            var existing = cursor[key];
            if (!existing || typeof existing !== 'object' || Array.isArray(existing)) {
                existing = {};
            } else {
                existing = utils.assign({}, existing);
            }
            cursor[key] = existing;
            cursor = existing;
        }
        cursor[segments[segments.length - 1]] = value;
        var updated = state.updateConfig(block.id, current);
        applyBlockStyles(updated);
        activeBlock = updated;
        flashPanelStatus('Cambios aplicados');

        // Notificar cambio de configuración para habilitar botón guardar en Dock
        var event;
        if (typeof global.CustomEvent === 'function') {
            event = new CustomEvent('gbn:configChanged', { detail: { id: block.id } });
        } else {
            event = document.createEvent('CustomEvent');
            event.initCustomEvent('gbn:configChanged', false, false, { id: block.id });
        }
        global.dispatchEvent(event);

        // Refrescar panel si el campo modificado puede afectar condiciones
        var conditionalFields = ['layout']; // Campos que pueden mostrar/ocultar otros campos
        if (conditionalFields.indexOf(path) !== -1) {
            renderBlockControls(updated);
        }
    }

    // ... (rest of functions)

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
                + '  <span class="gbn-footer-status">Cambios en vivo</span>'
                + '</footer>';
            document.body.appendChild(panelRoot);
        }
        panelBody = panelRoot.querySelector('.gbn-body');
        panelTitle = panelRoot.querySelector('.gbn-header-title');
        panelNotice = panelRoot.querySelector('.gbn-footer-status');
        
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

    // ...

    function renderPlaceholder(message) {
        if (!panelBody) { return; }
        panelMode = 'idle';
        panelForm = null;
        var text = message || 'Selecciona un bloque para configurar.';
        panelBody.innerHTML = '<div class="gbn-panel-empty">' + text + '</div>';
        setPanelStatus('Cambios en vivo');
    }

    // ...

    var panel = {
        init: function () { ensurePanelMounted(); },
        isOpen: function () { return !!(panelRoot && panelRoot.classList.contains('is-open')); },
        open: function (block) {
            ensurePanelMounted(); setActiveBlock(block || null); panelMode = block ? 'block' : 'idle';
            if (panelRoot) { panelRoot.classList.add('is-open'); panelRoot.setAttribute('aria-hidden', 'false'); }
            var title = 'GBN Panel';
            if (block) { if (block.meta && block.meta.label) { title = block.meta.label; } else if (block.role) { title = 'Configuración: ' + block.role; } }
            if (panelTitle) { panelTitle.textContent = title; }
            if (!panelBody) { return; }
            if (!block) { renderPlaceholder(); return; }
            renderBlockControls(block);
            utils.debug('Panel abierto', block ? block.id : null);
        },
        openTheme: function () {
            ensurePanelMounted(); setActiveBlock(null); panelMode = 'theme';
            if (panelRoot) { panelRoot.classList.add('is-open'); panelRoot.setAttribute('aria-hidden', 'false'); }
            if (panelTitle) { panelTitle.textContent = 'Theme settings'; }
            if (panelBody) { panelBody.innerHTML = '<div class="gbn-panel-empty">La configuración global del tema estará disponible en la etapa de configuraciones.</div>'; panelForm = null; }
            setPanelStatus('Próximamente');
        },
        openPage: function () {
            ensurePanelMounted(); setActiveBlock(null); panelMode = 'page';
            if (panelRoot) { panelRoot.classList.add('is-open'); panelRoot.setAttribute('aria-hidden', 'false'); }
            if (panelTitle) { panelTitle.textContent = 'Page settings'; }
            if (panelBody) { panelBody.innerHTML = '<div class="gbn-panel-empty">Define padding, fondo y opciones por página en la siguiente fase.</div>'; panelForm = null; }
            setPanelStatus('Próximamente');
        },
        openRestore: function () {
            ensurePanelMounted(); setActiveBlock(null); panelMode = 'restore';
            if (panelRoot) { panelRoot.classList.add('is-open'); panelRoot.setAttribute('aria-hidden', 'false'); }
            if (panelTitle) { panelTitle.textContent = 'Restaurar valores'; }
            if (panelBody) { panelBody.innerHTML = '<div class="gbn-panel-empty">La restauración devolverá el marcado original escrito en código. Esta acción se conectará en la etapa de persistencia.</div>'; panelForm = null; }
            setPanelStatus('Próximamente');
        },
        close: function () {
            if (panelRoot) { panelRoot.classList.remove('is-open'); panelRoot.setAttribute('aria-hidden', 'true'); }
            setActiveBlock(null); renderPlaceholder(); utils.debug('Panel cerrado');
        }
    };

    panel._applyStyles = applyBlockStyles;

    // Exponer API mínima para consumo desde panel-fields.js
    var panelApi = {
        getActiveBlock: function () { return activeBlock; },
        updateConfigValue: function (block, path, value) { return updateConfigValue(block, path, value); },
        flashStatus: function (text) { return flashPanelStatus(text); },
        applyBlockStyles: function (block) { return applyBlockStyles(block); }
    };

    Gbn.ui.panelApi = panelApi;

    Gbn.ui = Gbn.ui || {};
    Gbn.ui.panel = panel;
})(window);


