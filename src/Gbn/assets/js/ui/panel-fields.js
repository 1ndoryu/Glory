;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    var utils = Gbn.utils;
    var state = Gbn.state;

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

    function appendFieldDescription(container, field) {
        if (!field || !field.descripcion) { return; }
        var hint = document.createElement('p');
        hint.className = 'gbn-field-hint';
        hint.textContent = field.descripcion;
        container.appendChild(hint);
    }

    function parseSpacingValue(raw, fallbackUnit) {
        if (raw === null || raw === undefined || raw === '') { return { valor: '', unidad: fallbackUnit || 'px' }; }
        if (typeof raw === 'number') { return { valor: String(raw), unidad: fallbackUnit || 'px' }; }
        var match = /^(-?\d+(?:\.\d+)?)([a-z%]*)$/i.exec(String(raw).trim());
        if (!match) { return { valor: String(raw), unidad: fallbackUnit || 'px' }; }
        return { valor: match[1], unidad: match[2] || fallbackUnit || 'px' };
    }

    function buildSpacingField(block, field) {
        var wrapper = document.createElement('fieldset');
        wrapper.className = 'gbn-field gbn-field-spacing';
        var legend = document.createElement('legend'); legend.textContent = field.etiqueta || field.id; wrapper.appendChild(legend);
        var unidades = Array.isArray(field.unidades) && field.unidades.length ? field.unidades : ['px'];
        var campos = Array.isArray(field.campos) && field.campos.length ? field.campos : ['superior', 'derecha', 'inferior', 'izquierda'];
        var baseConfig = getConfigValue(block, field.id) || {};
        var unidadActual = unidades[0];
        for (var i = 0; i < campos.length; i += 1) { var parsed = parseSpacingValue(baseConfig[campos[i]], unidades[0]); if (parsed.unidad) { unidadActual = parsed.unidad; break; } }
        var unitSelect = document.createElement('select'); unitSelect.className = 'gbn-spacing-unit';
        unidades.forEach(function (opt) { var option = document.createElement('option'); option.value = opt; option.textContent = opt; unitSelect.appendChild(option); });
        if (unidades.indexOf(unidadActual) !== -1) { unitSelect.value = unidadActual; }
        wrapper.dataset.unit = unitSelect.value;
        var grid = document.createElement('div'); grid.className = 'gbn-spacing-grid';
        function handleSpacingInput(event) {
            var input = event.target; var value = input.value.trim(); var unit = wrapper.dataset.unit || unitSelect.value || 'px';
            if (input.__gbnUnit) { input.__gbnUnit.textContent = unit; }
            var path = input.dataset.configPath;
            var finalValue = value === '' ? null : (isNaN(parseFloat(value)) ? null : value + unit);
            var api = Gbn.ui && Gbn.ui.panelApi; var blk = api && api.getActiveBlock ? api.getActiveBlock() : null;
            if (api && api.updateConfigValue && blk) { api.updateConfigValue(blk, path, finalValue); }
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
                var api = Gbn.ui && Gbn.ui.panelApi; var blk = api && api.getActiveBlock ? api.getActiveBlock() : null;
                if (!api || !api.updateConfigValue || !blk) { return; }
                if (input.value === '') { api.updateConfigValue(blk, input.dataset.configPath, null); }
                else { api.updateConfigValue(blk, input.dataset.configPath, input.value + unitSelect.value); }
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
            var value = input.value.trim();
            var numeric = parseFloat(value);
            if (isNaN(numeric) || value === '') {
                valueBadge.textContent = 'auto';
                var api = Gbn.ui && Gbn.ui.panelApi; var blk = api && api.getActiveBlock ? api.getActiveBlock() : null;
                if (api && api.updateConfigValue && blk) { api.updateConfigValue(blk, field.id, null); }
            } else {
                valueBadge.textContent = numeric + (field.unidad ? field.unidad : '');
                var api = Gbn.ui && Gbn.ui.panelApi; var blk = api && api.getActiveBlock ? api.getActiveBlock() : null;
                if (api && api.updateConfigValue && blk) { api.updateConfigValue(blk, field.id, numeric); }
            }
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
        select.addEventListener('change', function () {
            var api = Gbn.ui && Gbn.ui.panelApi; var blk = api && api.getActiveBlock ? api.getActiveBlock() : null;
            if (api && api.updateConfigValue && blk) { api.updateConfigValue(blk, field.id, select.value); }
        });
        wrapper.appendChild(select); appendFieldDescription(wrapper, field); return wrapper;
    }

    function buildToggleField(block, field) {
        var wrapper = document.createElement('label'); wrapper.className = 'gbn-field gbn-field-toggle';
        var input = document.createElement('input'); input.type = 'checkbox'; input.className = 'gbn-toggle';
        var current = !!getConfigValue(block, field.id); input.checked = current;
        input.addEventListener('change', function () {
            var api = Gbn.ui && Gbn.ui.panelApi; var blk = api && api.getActiveBlock ? api.getActiveBlock() : null;
            if (api && api.updateConfigValue && blk) { api.updateConfigValue(blk, field.id, !!input.checked); }
        });
        var span = document.createElement('span'); span.textContent = field.etiqueta || field.id; wrapper.appendChild(input); wrapper.appendChild(span);
        appendFieldDescription(wrapper, field); return wrapper;
    }

    function buildTextField(block, field) {
        var wrapper = document.createElement('div'); wrapper.className = 'gbn-field';
        var label = document.createElement('label'); label.className = 'gbn-field-label'; label.textContent = field.etiqueta || field.id; wrapper.appendChild(label);
        var input = document.createElement('input'); input.type = 'text'; input.className = 'gbn-input';
        var current = getConfigValue(block, field.id); if (current !== undefined && current !== null) { input.value = current; }
        input.addEventListener('input', function () {
            var value = input.value.trim();
            var api = Gbn.ui && Gbn.ui.panelApi; var blk = api && api.getActiveBlock ? api.getActiveBlock() : null;
            if (api && api.updateConfigValue && blk) { api.updateConfigValue(blk, field.id, value === '' ? null : value); }
        });
        wrapper.appendChild(input); appendFieldDescription(wrapper, field); return wrapper;
    }

    function buildColorField(block, field) {
        var wrapper = document.createElement('div'); wrapper.className = 'gbn-field';
        var label = document.createElement('label'); label.className = 'gbn-field-label'; label.textContent = field.etiqueta || field.id; wrapper.appendChild(label);
        var input = document.createElement('input'); input.type = 'color'; input.className = 'gbn-input-color';
        var current = getConfigValue(block, field.id);
        if (typeof current === 'string' && current.trim() !== '') { input.value = current; } else { input.value = '#ffffff'; }
        input.addEventListener('input', function () {
            var value = input.value.trim();
            var api = Gbn.ui && Gbn.ui.panelApi; var blk = api && api.getActiveBlock ? api.getActiveBlock() : null;
            if (api && api.updateConfigValue && blk) { api.updateConfigValue(blk, field.id, value === '' ? null : value); }
        });
        wrapper.appendChild(input); appendFieldDescription(wrapper, field); return wrapper;
    }

    function shouldShowField(block, field) {
        if (!field || !field.condicion || !Array.isArray(field.condicion) || field.condicion.length !== 2) {
            return true; // Mostrar si no hay condición
        }
        var conditionField = field.condicion[0];
        var conditionValue = field.condicion[1];
        var currentValue = getConfigValue(block, conditionField);
        return currentValue === conditionValue;
    }

    function buildField(block, field) {
        if (!field || !field.id) { return null; }
        if (!shouldShowField(block, field)) { return null; }
        switch (field.tipo) {
            case 'spacing': return buildSpacingField(block, field);
            case 'slider': return buildSliderField(block, field);
            case 'select': return buildSelectField(block, field);
            case 'toggle': return buildToggleField(block, field);
            case 'color': return buildColorField(block, field);
            case 'text':
            default: return buildTextField(block, field);
        }
    }

    Gbn.ui = Gbn.ui || {};
    Gbn.ui.panelFields = { buildField: buildField };
})(window);


