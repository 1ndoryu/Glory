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

    function getConfigValue(block, path) {
        if (!block || !block.config || !path) { return undefined; }
        var value = block.config;
        var segments = path.split('.');
        for (var i = 0; i < segments.length; i += 1) {
            if (value === null || value === undefined) { return undefined; }
            value = value[segments[i]];
        }
        return value;
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

    function parseSpacingValue(raw, fallbackUnit) {
        if (raw === null || raw === undefined || raw === '') {
            return { valor: '', unidad: fallbackUnit || 'px' };
        }
        if (typeof raw === 'number') {
            return { valor: String(raw), unidad: fallbackUnit || 'px' };
        }
        var match = /^(-?\d+(?:\.\d+)?)([a-z%]*)$/i.exec(String(raw).trim());
        if (!match) { return { valor: String(raw), unidad: fallbackUnit || 'px' }; }
        return { valor: match[1], unidad: match[2] || fallbackUnit || 'px' };
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

    function appendFieldDescription(container, field) {
        if (!field || !field.descripcion) { return; }
        var hint = document.createElement('p');
        hint.className = 'gbn-field-hint';
        hint.textContent = field.descripcion;
        container.appendChild(hint);
    }

    function buildSpacingField(block, field) {
        var wrapper = document.createElement('fieldset');
        wrapper.className = 'gbn-field gbn-field-spacing';
        var legend = document.createElement('legend');
        legend.textContent = field.etiqueta || field.id;
        wrapper.appendChild(legend);
        var unidades = Array.isArray(field.unidades) && field.unidades.length ? field.unidades : ['px'];
        var campos = Array.isArray(field.campos) && field.campos.length ? field.campos : ['superior', 'derecha', 'inferior', 'izquierda'];
        var baseConfig = getConfigValue(block, field.id) || {};
        var unidadActual = unidades[0];
        for (var i = 0; i < campos.length; i += 1) {
            var parsed = parseSpacingValue(baseConfig[campos[i]], unidades[0]);
            if (parsed.unidad) { unidadActual = parsed.unidad; break; }
        }
        var unitSelect = document.createElement('select');
        unitSelect.className = 'gbn-spacing-unit';
        unidades.forEach(function (opt) {
            var option = document.createElement('option');
            option.value = opt; option.textContent = opt; unitSelect.appendChild(option);
        });
        if (unidades.indexOf(unidadActual) !== -1) { unitSelect.value = unidadActual; }
        wrapper.dataset.unit = unitSelect.value;
        var grid = document.createElement('div');
        grid.className = 'gbn-spacing-grid';
        function handleSpacingInput(event) {
            var input = event.target; var value = input.value; var unit = wrapper.dataset.unit || unitSelect.value || 'px';
            if (input.__gbnUnit) { input.__gbnUnit.textContent = unit; }
            var path = input.dataset.configPath; var finalValue = value === '' ? null : value + unit;
            updateConfigValue(activeBlock, path, finalValue);
        }
        campos.forEach(function (nombre) {
            var parsed = parseSpacingValue(baseConfig[nombre], unitSelect.value);
            var item = document.createElement('label'); item.className = 'gbn-spacing-input'; item.setAttribute('data-field', nombre);
            var title = document.createElement('span'); title.textContent = nombre.charAt(0).toUpperCase() + nombre.slice(1); item.appendChild(title);
            var input = document.createElement('input'); input.type = 'number';
            if (field.min !== undefined) { input.min = field.min; }
            if (field.max !== undefined) { input.max = field.max; }
            if (field.paso !== undefined) { input.step = field.paso; }
            input.value = parsed.valor; input.placeholder = 'auto'; input.dataset.configPath = field.id + '.' + nombre; input.addEventListener('input', handleSpacingInput);
            item.appendChild(input);
            var unitLabel = document.createElement('span'); unitLabel.className = 'gbn-spacing-unit-label'; unitLabel.textContent = unitSelect.value; input.__gbnUnit = unitLabel; item.appendChild(unitLabel);
            grid.appendChild(item);
        });
        unitSelect.addEventListener('change', function () {
            wrapper.dataset.unit = unitSelect.value;
            var inputs = grid.querySelectorAll('input[data-config-path]');
            inputs.forEach(function (input) {
                if (input.__gbnUnit) { input.__gbnUnit.textContent = unitSelect.value; }
                if (input.value === '') { updateConfigValue(activeBlock, input.dataset.configPath, null); }
                else { updateConfigValue(activeBlock, input.dataset.configPath, input.value + unitSelect.value); }
            });
        });
        wrapper.appendChild(unitSelect);
        wrapper.appendChild(grid);
        appendFieldDescription(wrapper, field);
        return wrapper;
    }

    function buildSliderField(block, field) {
        var wrapper = document.createElement('div'); wrapper.className = 'gbn-field gbn-field-range';
        var header = document.createElement('div'); header.className = 'gbn-field-header';
        var label = document.createElement('span'); label.className = 'gbn-field-label'; label.textContent = field.etiqueta || field.id;
        var valueBadge = document.createElement('span'); valueBadge.className = 'gbn-field-value'; header.appendChild(label); header.appendChild(valueBadge); wrapper.appendChild(header);
        var input = document.createElement('input'); input.type = 'range';
        if (field.min !== undefined) { input.min = field.min; }
        if (field.max !== undefined) { input.max = field.max; }
        input.step = field.paso || 1; var current = getConfigValue(block, field.id);
        if (current === null || current === undefined || current === '') { current = field.min !== undefined ? field.min : 0; }
        input.value = current; valueBadge.textContent = input.value + (field.unidad ? field.unidad : ''); input.dataset.configPath = field.id;
        input.addEventListener('input', function () {
            var numeric = parseFloat(input.value); if (isNaN(numeric)) { numeric = 0; }
            valueBadge.textContent = numeric + (field.unidad ? field.unidad : '');
            updateConfigValue(activeBlock, field.id, numeric);
        });
        wrapper.appendChild(input); appendFieldDescription(wrapper, field); return wrapper;
    }

    function buildSelectField(block, field) {
        var wrapper = document.createElement('div'); wrapper.className = 'gbn-field';
        var label = document.createElement('label'); label.className = 'gbn-field-label'; label.textContent = field.etiqueta || field.id; wrapper.appendChild(label);
        var select = document.createElement('select'); select.className = 'gbn-select';
        var opciones = Array.isArray(field.opciones) ? field.opciones : [];
        opciones.forEach(function (opt) { var option = document.createElement('option'); option.value = opt.valor; option.textContent = opt.etiqueta || opt.valor; select.appendChild(option); });
        var current = getConfigValue(block, field.id); if (current !== undefined && current !== null && current !== '') { select.value = current; }
        select.addEventListener('change', function () { updateConfigValue(activeBlock, field.id, select.value); });
        wrapper.appendChild(select); appendFieldDescription(wrapper, field); return wrapper;
    }

    function buildToggleField(block, field) {
        var wrapper = document.createElement('label'); wrapper.className = 'gbn-field gbn-field-toggle';
        var input = document.createElement('input'); input.type = 'checkbox'; input.className = 'gbn-toggle';
        var current = !!getConfigValue(block, field.id); input.checked = current;
        input.addEventListener('change', function () { updateConfigValue(activeBlock, field.id, !!input.checked); });
        var span = document.createElement('span'); span.textContent = field.etiqueta || field.id; wrapper.appendChild(input); wrapper.appendChild(span);
        appendFieldDescription(wrapper, field); return wrapper;
    }

    function buildTextField(block, field) {
        var wrapper = document.createElement('div'); wrapper.className = 'gbn-field';
        var label = document.createElement('label'); label.className = 'gbn-field-label'; label.textContent = field.etiqueta || field.id; wrapper.appendChild(label);
        var input = document.createElement('input'); input.type = 'text'; input.className = 'gbn-input';
        var current = getConfigValue(block, field.id); if (current !== undefined && current !== null) { input.value = current; }
        input.addEventListener('input', function () { updateConfigValue(activeBlock, field.id, input.value); });
        wrapper.appendChild(input); appendFieldDescription(wrapper, field); return wrapper;
    }

    function buildColorField(block, field) {
        var wrapper = document.createElement('div'); wrapper.className = 'gbn-field';
        var label = document.createElement('label'); label.className = 'gbn-field-label'; label.textContent = field.etiqueta || field.id; wrapper.appendChild(label);
        var input = document.createElement('input'); input.type = 'color'; input.className = 'gbn-input-color';
        var current = getConfigValue(block, field.id);
        if (typeof current === 'string' && current.trim() !== '') { input.value = current; } else { input.value = '#ffffff'; }
        input.addEventListener('input', function () { updateConfigValue(activeBlock, field.id, input.value); });
        wrapper.appendChild(input); appendFieldDescription(wrapper, field); return wrapper;
    }

    function buildField(block, field) {
        if (!field || !field.id) { return null; }
        switch (field.tipo) {
            case 'spacing': return buildSpacingField(block, field);
            case 'slider': return buildSliderField(block, field);
            case 'select': return buildSelectField(block, field);
            case 'toggle': return buildToggleField(block, field);
            case 'color': return buildColorField(block, field);
            case 'text': default: return buildTextField(block, field);
        }
    }

    function renderBlockControls(block) {
        if (!panelBody) { return; }
        panelBody.innerHTML = ''; panelForm = null; panelBody.appendChild(createSummary(block));
        var schema = Array.isArray(block.schema) ? block.schema : [];
        if (!schema.length) {
            var empty = document.createElement('div'); empty.className = 'gbn-panel-coming-soon'; empty.textContent = 'Este bloque aún no expone controles editables.';
            panelBody.appendChild(empty); setPanelStatus('Sin controles disponibles'); return;
        }
        panelForm = document.createElement('form'); panelForm.className = 'gbn-panel-form';
        schema.forEach(function (field) { var control = buildField(block, field); if (control) { panelForm.appendChild(control); } });
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

    Gbn.ui = Gbn.ui || {};
    Gbn.ui.panel = panel;
})(window);


