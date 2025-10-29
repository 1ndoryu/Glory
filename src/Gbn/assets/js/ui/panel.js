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

    function updateConfigValue(block, path, value) {
        if (!block || !path) { return; }
        var current = cloneConfig(block.config);
        var segments = path.split('.');
        var cursor = current;
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
    }

    function extractSpacingStyles(spacingConfig) {
        var styles = {};
        if (!spacingConfig || typeof spacingConfig !== 'object') { return styles; }
        var map = { superior: 'padding-top', derecha: 'padding-right', inferior: 'padding-bottom', izquierda: 'padding-left' };
        Object.keys(map).forEach(function (key) {
            var raw = spacingConfig[key];
            if (raw === null || raw === undefined || raw === '') { return; }
            if (typeof raw === 'number') { styles[map[key]] = raw + 'px'; }
            else { styles[map[key]] = raw; }
        });
        return styles;
    }

    var styleResolvers = {
        principal: function (config) {
            var styles = extractSpacingStyles(config.padding);
            if (config.alineacion && config.alineacion !== 'inherit') { styles['text-align'] = config.alineacion; }
            if (config.maxAncho !== null && config.maxAncho !== undefined && config.maxAncho !== '') {
                var max = parseFloat(config.maxAncho);
                styles['max-width'] = !isNaN(max) ? max + 'px' : String(config.maxAncho);
            }
            if (config.fondo) { styles.background = config.fondo; }
            return styles;
        },
        secundario: function (config) {
            var styles = extractSpacingStyles(config.padding);
            if (config.gap !== null && config.gap !== undefined && config.gap !== '') {
                var gap = parseFloat(config.gap);
                if (!isNaN(gap)) { styles.gap = gap + 'px'; }
            }
            if (config.layout) {
                if (config.layout === 'grid') { styles.display = 'grid'; }
                else if (config.layout === 'flex') { styles.display = 'flex'; }
                else { styles.display = 'block'; }
            }
            return styles;
        },
        content: function () { return {}; }
    };

    function applyBlockStyles(block) {
        if (!block || !styleManager || !styleManager.update) { return; }
        var base = (block.styles && block.styles.inline) ? utils.assign({}, block.styles.inline) : {};
        var resolver = styleResolvers[block.role] || function () { return {}; };
        var overrides = resolver(block.config || {}, block) || {};
        var merged = utils.assign({}, base, overrides);
        styleManager.update(block, merged);
    }

    function createSummary(block) {
        var summary = document.createElement('div');
        summary.className = 'gbn-panel-block-summary';
        var idLabel = document.createElement('p');
        idLabel.className = 'gbn-panel-block-id';
        idLabel.innerHTML = 'ID: <code>' + block.id + '</code>';
        summary.appendChild(idLabel);
        var roleLabel = document.createElement('p');
        roleLabel.className = 'gbn-panel-block-role';
        roleLabel.innerHTML = 'Rol: <strong>' + (block.role || 'block') + '</strong>';
        summary.appendChild(roleLabel);
        if (block.meta && block.meta.postType) {
            var typeLabel = document.createElement('p');
            typeLabel.className = 'gbn-panel-block-type';
            typeLabel.textContent = 'Contenido: ' + block.meta.postType;
            summary.appendChild(typeLabel);
        }
        return summary;
    }

    // field builders movidos a panel-fields.js

    function renderBlockControls(block) {
        if (!panelBody) { return; }
        panelBody.innerHTML = ''; panelForm = null; panelBody.appendChild(createSummary(block));
        var schema = Array.isArray(block.schema) ? block.schema : [];
        if (!schema.length) {
            var empty = document.createElement('div'); empty.className = 'gbn-panel-coming-soon'; empty.textContent = 'Este bloque aún no expone controles editables.';
            panelBody.appendChild(empty); setPanelStatus('Sin controles disponibles'); return;
        }
        panelForm = document.createElement('form'); panelForm.className = 'gbn-panel-form';
        var builder = Gbn.ui && Gbn.ui.panelFields && Gbn.ui.panelFields.buildField;
        schema.forEach(function (field) { var control = builder ? builder(block, field) : null; if (control) { panelForm.appendChild(control); } });
        panelBody.appendChild(panelForm); setPanelStatus('Edita las opciones y se aplicarán al instante');
    }

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
            if (panelFooter) { panelFooter.disabled = true; }
            utils.debug('Panel abierto', block ? block.id : null);
        },
        openTheme: function () {
            ensurePanelMounted(); setActiveBlock(null); panelMode = 'theme';
            if (panelRoot) { panelRoot.classList.add('is-open'); panelRoot.setAttribute('aria-hidden', 'false'); }
            if (panelTitle) { panelTitle.textContent = 'Theme settings'; }
            if (panelBody) { panelBody.innerHTML = '<div class="gbn-panel-empty">La configuración global del tema estará disponible en la etapa de configuraciones.</div>'; panelForm = null; }
            setPanelStatus('Próximamente'); if (panelFooter) { panelFooter.disabled = true; }
        },
        openPage: function () {
            ensurePanelMounted(); setActiveBlock(null); panelMode = 'page';
            if (panelRoot) { panelRoot.classList.add('is-open'); panelRoot.setAttribute('aria-hidden', 'false'); }
            if (panelTitle) { panelTitle.textContent = 'Page settings'; }
            if (panelBody) { panelBody.innerHTML = '<div class="gbn-panel-empty">Define padding, fondo y opciones por página en la siguiente fase.</div>'; panelForm = null; }
            setPanelStatus('Próximamente'); if (panelFooter) { panelFooter.disabled = true; }
        },
        openRestore: function () {
            ensurePanelMounted(); setActiveBlock(null); panelMode = 'restore';
            if (panelRoot) { panelRoot.classList.add('is-open'); panelRoot.setAttribute('aria-hidden', 'false'); }
            if (panelTitle) { panelTitle.textContent = 'Restaurar valores'; }
            if (panelBody) { panelBody.innerHTML = '<div class="gbn-panel-empty">La restauración devolverá el marcado original escrito en código. Esta acción se conectará en la etapa de persistencia.</div>'; panelForm = null; }
            setPanelStatus('Próximamente'); if (panelFooter) { panelFooter.disabled = true; }
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


