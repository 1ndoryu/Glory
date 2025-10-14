(function () {
    var log = function () {
        try {
            (console.debug || console.log).apply(console, ['[GBN]'].concat([].slice.call(arguments)));
        } catch (e) {}
    };
    if (!document.body) {
        log('document.body no disponible, abortando');
        return;
    }
    log('Iniciando gbn.js');

    function isBuilderActive() {
        try {
            if (location.search.indexOf('fb-edit') !== -1) {
                log('Detectado fb-edit en URL');
                return true;
            }
            if (window.FusionApp || window.FusionPageBuilder || window.FusionPageBuilderApp) {
                log('Detectado objeto Fusion/Builder en ventana');
                return true;
            }
            var inIframe = window.self !== window.top;
            if (inIframe && window.top && window.top.document) {
                if (window.top.location.search.indexOf('fb-edit') !== -1) {
                    log('Detectado fb-edit en parent');
                    return true;
                }
                if (window.top.document.querySelector('.fusion-builder-live-toolbar')) {
                    log('Detectada toolbar del builder en parent');
                    return true;
                }
            }
        } catch (e) {}
        return false;
    }

    if (isBuilderActive()) {
        log('Builder activo, GBN no se inicia');
        return;
    }

    var cfg = window.gloryGbnCfg || {};
    if (!cfg.ajaxUrl) {
        log('Config no encontrada o ajaxUrl vacío', cfg);
    }
    var footer = document.getElementById('glory-gbn-root');
    if (!footer) {
        log('No se encontró #glory-gbn-root en el DOM');
    } else {
        log('#glory-gbn-root presente', footer.dataset);
    }

    function qsAll(sel, root) {
        return Array.prototype.slice.call((root || document).querySelectorAll(sel));
    }

    var currentRoot = null;
    var pendingValues = {};
    var dirty = false;

    function ensurePanel() {
        var panel = document.getElementById('gbn-panel');
        if (!panel) {
            panel = document.createElement('div');
            panel.id = 'gbn-panel';
            panel.innerHTML = '<div class="gbn-header"><span>Glory Split Content</span><button type="button" id="gbn-close"><svg data-testid="geist-icon" height="16" stroke-linejoin="round" style="color:currentColor" viewBox="0 0 16 16" width="16"><path fill-rule="evenodd" clip-rule="evenodd" d="M12.4697 13.5303L13 14.0607L14.0607 13L13.5303 12.4697L9.06065 7.99999L13.5303 3.53032L14.0607 2.99999L13 1.93933L12.4697 2.46966L7.99999 6.93933L3.53032 2.46966L2.99999 1.93933L1.93933 2.99999L2.46966 3.53032L6.93933 7.99999L2.46966 12.4697L1.93933 13L2.99999 14.0607L3.53032 13.5303L7.99999 9.06065L12.4697 13.5303Z" fill="currentColor"></path></svg></button></div><div class="gbn-body"></div><div class="gbn-footer"><button type="button" id="gbn-save" class="gbn-btn">Save</button></div>';
            panel.dataset.mode = 'block';
            document.body.appendChild(panel);
            panel.__gbnTitleEl = panel.querySelector('.gbn-header span');
            panel.querySelector('#gbn-close').addEventListener('click', function () {
                closePanel(panel);
            });
            var saveBtn = panel.querySelector('#gbn-save');
            if (saveBtn) {
                saveBtn.addEventListener('click', function () {
                    try {
                        handlePanelSave();
                    } catch (e) {}
                });
            }
            log('Panel GBN creado');
        }
        if (!panel.dataset.mode) {
            panel.dataset.mode = 'block';
        }
        if (!panel.__gbnTitleEl) {
            panel.__gbnTitleEl = panel.querySelector('.gbn-header span');
        }
        return panel;
    }

    function closePanel(panel) {
        var pnl = panel || document.getElementById('gbn-panel');
        if (!pnl) return;
        pnl.classList.remove('is-open');
        if ((pnl.dataset.mode || 'block') === 'page') return;
        try {
            if (currentRoot) {
                currentRoot.classList.remove('gbn-open');
                updateDragState(currentRoot, false);
            }
        } catch (e) {}
    }

    function buildInput(ctrl, config) {
        var value = String((config && config[ctrl.key]) || ctrl.defaultValue || '');
        var wrap = document.createElement('div');
        wrap.className = 'gbn-control';
        var label = document.createElement('label');
        label.textContent = ctrl.label || ctrl.key;
        label.setAttribute('for', 'gbn-input-' + ctrl.key);
        wrap.appendChild(label);
        var input;
        if (ctrl.type === 'color') {
            input = document.createElement('input');
            input.type = 'color';
        } else {
            input = document.createElement('input');
            input.type = ctrl.type || 'text';
        }
        input.id = 'gbn-input-' + ctrl.key;
        input.value = value;
        input.dataset.key = ctrl.key;
        input.addEventListener('input', function () {
            pendingValues[ctrl.key] = this.value;
            dirty = true;
        });
        wrap.appendChild(input);
        return wrap;
    }

    function updateConditionalVisibility() {
        // Buscar el tabContent activo en el panel actual
        var panel = document.getElementById('gbn-panel');
        if (!panel || !panel.classList.contains('is-open')) return;
        var tabContent = panel.querySelector('.gbn-tabs-content');
        if (!tabContent) return;

        var conditionalControls = tabContent.querySelectorAll('.gbn-conditional');
        conditionalControls.forEach(function (ctrl) {
            var condition = ctrl.dataset.conditional;
            if (!condition) return;
            var parts = condition.split(':');
            if (parts.length !== 2) return;
            var depKey = parts[0];
            var depValue = parts[1];

            // Buscar el input correspondiente
            var depInputs = tabContent.querySelectorAll('[data-key="' + depKey + '"]');
            var shouldShow = false;

            if (depInputs.length > 0) {
                // Verificar si es un grupo de radio buttons
                var firstInput = depInputs[0];
                if (firstInput.type === 'radio') {
                    // Para radio buttons, buscar cuál está checked
                    var checkedRadio = tabContent.querySelector('[data-key="' + depKey + '"]:checked');
                    shouldShow = checkedRadio && checkedRadio.value === depValue;
                } else if (firstInput.type === 'checkbox') {
                    shouldShow = firstInput.checked === (depValue === 'yes');
                } else {
                    // Para otros tipos de input (text, select, etc.)
                    shouldShow = firstInput.value === depValue;
                }
            }

            // Mostrar/ocultar el control usando clase utilitaria
            ctrl.classList.toggle('is-hidden', !shouldShow);
        });
    }

    function openPanelFor(root) {
        currentRoot = root;
        pendingValues = {};
        dirty = false;
        var panel = ensurePanel();
        panel.dataset.mode = 'block';
        if (panel.__gbnTitleEl) {
            panel.__gbnTitleEl.textContent = 'Glory Split Content';
        }
        var body = panel.querySelector('.gbn-body');
        var schema = root.getAttribute('data-gbn-schema');
        var config = root.getAttribute('data-gbn-config');
        var gbnId = root.getAttribute('data-gbn-id');
        var pageId = root.getAttribute('data-gbn-page-id');
        try {
            schema = JSON.parse(schema || '[]');
        } catch (e) {
            log('Error parseando schema', e);
            schema = [];
        }
        try {
            config = JSON.parse(config || '{}');
        } catch (e) {
            log('Error parseando config', e);
            config = {};
        }
        body.innerHTML = '';
        panel.dataset.gbnId = gbnId || '';
        panel.dataset.pageId = pageId || '';
        panel.dataset.mode = 'block';
        log('Abriendo panel para', {gbnId: gbnId, pageId: pageId, schemaLen: schema.length});

        if (!schema.length || !schema[0].tab) {
            // Schema antiguo (sin tabs), renderizar plano
            schema.forEach(function (ctrl) {
                var wrap = document.createElement('div');
                wrap.className = 'gbn-control';
                var label = document.createElement('label');
                label.textContent = ctrl.label || ctrl.key;
                wrap.appendChild(label);
                var input = createInput(ctrl, config, null);
                wrap.appendChild(input);
                body.appendChild(wrap);
            });
        } else {
            // Nuevo schema con tabs
            var tabsNav = document.createElement('div');
            tabsNav.className = 'gbn-tabs-nav';
            var activeTab = 0;
            schema.forEach(function (tab, idx) {
                var tabBtn = document.createElement('button');
                tabBtn.className = 'gbn-tab-btn' + (idx === 0 ? ' active' : '');
                tabBtn.textContent = tab.tab;
                tabBtn.addEventListener('click', function () {
                    qsAll('.gbn-tab-btn', tabsNav).forEach(function (b) {
                        b.classList.remove('active');
                    });
                    this.classList.add('active');
                    renderTab(idx);
                    updateConditionalVisibility();
                });
                tabsNav.appendChild(tabBtn);
            });
            body.appendChild(tabsNav);

            var tabContent = document.createElement('div');
            tabContent.className = 'gbn-tabs-content';
            body.appendChild(tabContent);

            function renderTab(idx) {
                tabContent.innerHTML = '';
                var tab = schema[idx];
                if (tab && tab.controls) {
                    tab.controls.forEach(function (ctrl) {
                        var wrap = document.createElement('div');
                        wrap.className = 'gbn-control';
                        if (ctrl.conditional) {
                            wrap.className += ' gbn-conditional';
                            wrap.dataset.conditional = ctrl.conditional;
                        }
                        var label = document.createElement('label');
                        label.textContent = ctrl.label || ctrl.key;
                        wrap.appendChild(label);
                        var input = createInput(ctrl, config, updateConditionalVisibility);
                        wrap.appendChild(input);
                        tabContent.appendChild(wrap);
                    });
                }
                // Aplicar condiciones después de renderizar
                updateConditionalVisibility();
            }

            renderTab(activeTab);
        }

        panel.classList.add('is-open');
        // Marcar root como abierto (habilita drag y cursor)
        try {
            root.classList.add('gbn-open');
            updateDragState(root, true);
        } catch (e) {}
    }

    function createInput(ctrl, config, onConditionalUpdate) {
        var input;
        if (ctrl.type === 'range') {
            // Contenedor para el range con valor (inspirado en FormBuilder)
            var container = document.createElement('div');
            container.className = 'range-container';

            var valueSpan = document.createElement('span');
            valueSpan.className = 'range-value';
            valueSpan.id = 'range-value-' + ctrl.key;

            var rangeInput = document.createElement('input');
            rangeInput.type = 'range';
            rangeInput.min = String(ctrl.min || 0);
            rangeInput.max = String(ctrl.max || 100);
            rangeInput.step = String(ctrl.step || 1);
            var val = String(config[ctrl.key] || '')
                .replace('%', '')
                .replace('px', '');
            rangeInput.value = val || String(ctrl.min || 0);

            // Actualizar valor en tiempo real como en FormBuilder
            valueSpan.textContent = rangeInput.value + (ctrl.unit || '');
            rangeInput.addEventListener('input', function () {
                valueSpan.textContent = this.value + (ctrl.unit || '');
                pendingValues[ctrl.key] = this.value;
                dirty = true;
                updateLocalConfig(ctrl.key, this.value);
                debouncedPreview();
                if (onConditionalUpdate) onConditionalUpdate();
            });

            container.appendChild(valueSpan);
            rangeInput.dataset.key = ctrl.key;
            container.appendChild(rangeInput);
            input = container;
        } else if (ctrl.type === 'select') {
            if (ctrl.search) {
                // Select personalizado con dropdown y buscador interno
                var selectedValue = String(config[ctrl.key] || '');
                var wrap = document.createElement('div');
                wrap.className = 'gbn-select-custom';

                var display = document.createElement('button');
                display.type = 'button';
                display.className = 'gbn-select-display';
                var getLabel = function (val) {
                    if (!ctrl.options) return '';
                    if (Object.prototype.hasOwnProperty.call(ctrl.options, val)) return String(ctrl.options[val]);
                    return String(val || '');
                };
                display.textContent = getLabel(selectedValue) || 'Default';

                var hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.value = selectedValue;
                hidden.dataset.key = ctrl.key;

                var dropdown = document.createElement('div');
                dropdown.className = 'gbn-select-dropdown';

                var search = document.createElement('input');
                search.type = 'text';
                search.className = 'gbn-select-search';
                search.placeholder = 'Search font...';

                var list = document.createElement('ul');
                list.className = 'gbn-select-list';

                var buildOption = function (val, label) {
                    var li = document.createElement('li');
                    li.className = 'gbn-select-option' + (String(val) === String(selectedValue) ? ' is-selected' : '');
                    li.textContent = String(label);
                    li.dataset.value = String(val);
                        li.addEventListener('click', function (e) {
                        selectedValue = this.dataset.value || '';
                        hidden.value = selectedValue;
                        display.textContent = getLabel(selectedValue) || 'Seleccionar';
                        pendingValues[ctrl.key] = selectedValue;
                        dirty = true;
                        updateLocalConfig(ctrl.key, selectedValue);
                        reloadBlockPreview();
                        if (onConditionalUpdate) onConditionalUpdate();
                        // actualizar selección visual
                        Array.prototype.forEach.call(list.querySelectorAll('.gbn-select-option'), function (opt) {
                            opt.classList.toggle('is-selected', opt.dataset.value === selectedValue);
                        });
                        wrap.classList.remove('is-open');
                    });
                    return li;
                };

                Object.keys(ctrl.options || {}).forEach(function (k) {
                    list.appendChild(buildOption(k, ctrl.options[k]));
                });

                var filterOptions = function () {
                    var q = (search.value || '').toLowerCase();
                    Array.prototype.forEach.call(list.children, function (li) {
                        var txt = (li.textContent || '').toLowerCase();
                        var hide = !!q && txt.indexOf(q) === -1;
                        li.classList.toggle('is-hidden', hide);
                    });
                };
                search.addEventListener('input', filterOptions);

                dropdown.appendChild(search);
                dropdown.appendChild(list);

                var closeOnOutside = function (e) {
                    if (!wrap.contains(e.target)) {
                        wrap.classList.remove('is-open');
                        document.removeEventListener('click', closeOnOutside);
                    }
                };
                display.addEventListener('click', function () {
                    var isOpen = wrap.classList.toggle('is-open');
                    if (isOpen) {
                        filterOptions();
                        setTimeout(function () {
                            try {
                                search.focus();
                            } catch (e) {}
                        }, 0);
                        document.addEventListener('click', closeOnOutside);
                    }
                });

                wrap.appendChild(display);
                wrap.appendChild(hidden);
                wrap.appendChild(dropdown);
                input = wrap;
            } else {
                input = document.createElement('select');
                Object.keys(ctrl.options || {}).forEach(function (k) {
                    var o = document.createElement('option');
                    o.value = k;
                    o.textContent = ctrl.options[k];
                    input.appendChild(o);
                });
                input.value = String(config[ctrl.key] || '');
                input.addEventListener('change', function () {
                    pendingValues[ctrl.key] = this.value;
                    dirty = true;
                    updateLocalConfig(ctrl.key, this.value);
                    reloadBlockPreview();
                    updateConditionalVisibility();
                });
            }
        } else if (ctrl.type === 'toggle') {
            // Crear radio buttons en lugar de toggle switch
            var radioContainer = document.createElement('div');
            radioContainer.className = 'radio-group';

            // Importante: por convención nuestras toggles usan 'yes'/'no'.
            // Si no hay valor en config, usar el default del schema si existe; de lo contrario 'no'.
            var currentValue = String(
                (typeof config[ctrl.key] !== 'undefined')
                    ? config[ctrl.key]
                    : (typeof ctrl.defaultValue !== 'undefined' ? ctrl.defaultValue : 'no')
            );

            // Opción "No"
            var noLabel = document.createElement('label');
            var noInput = document.createElement('input');
            noInput.type = 'radio';
            noInput.name = 'radio-' + ctrl.key;
            noInput.value = 'no';
            noInput.checked = currentValue === 'no';
            noInput.addEventListener('change', function () {
                pendingValues[ctrl.key] = this.value;
                dirty = true;
                updateLocalConfig(ctrl.key, this.value);
                reloadBlockPreview();
                if (onConditionalUpdate) onConditionalUpdate();
            });
            noInput.dataset.key = ctrl.key;
            noLabel.appendChild(noInput);
            noLabel.appendChild(document.createTextNode(' No'));
            radioContainer.appendChild(noLabel);

            // Opción "Sí"
            var yesLabel = document.createElement('label');
            var yesInput = document.createElement('input');
            yesInput.type = 'radio';
            yesInput.name = 'radio-' + ctrl.key;
            yesInput.value = 'yes';
            yesInput.checked = currentValue === 'yes';
            yesInput.addEventListener('change', function () {
                pendingValues[ctrl.key] = this.value;
                dirty = true;
                updateLocalConfig(ctrl.key, this.value);

                // Lógica especial para page_title_enabled: si se habilita y el campo de texto está vacío, usar site title
                if (ctrl.key === 'page_title_enabled' && this.value === 'yes') {
                    var textField = document.querySelector('[data-key="page_title_text"]');
                    if (textField && (!textField.value || textField.value.trim() === '')) {
                        var siteTitle = window.gloryGbnCfg && window.gloryGbnCfg.siteTitle ? window.gloryGbnCfg.siteTitle : '';
                        if (siteTitle) {
                            textField.value = siteTitle;
                            pendingValues['page_title_text'] = siteTitle;
                            updateLocalConfig('page_title_text', siteTitle);
                        }
                    }
                }

                reloadBlockPreview();
                if (onConditionalUpdate) onConditionalUpdate();
            });
            yesInput.dataset.key = ctrl.key;
            yesLabel.appendChild(yesInput);
            yesLabel.appendChild(document.createTextNode(' Yes'));
            radioContainer.appendChild(yesLabel);

            input = radioContainer;
        } else if (ctrl.type === 'color') {
            input = document.createElement('input');
            input.type = 'color';
            input.value = String(config[ctrl.key] || '#000000');
            input.addEventListener('change', function () {
                pendingValues[ctrl.key] = this.value;
                dirty = true;
                updateLocalConfig(ctrl.key, this.value);
                reloadBlockPreview();
            });
        } else if (ctrl.type === 'textarea') {
            input = document.createElement('textarea');
            input.value = String(config[ctrl.key] || '');
            input.rows = 3;
            input.addEventListener('input', function () {
                pendingValues[ctrl.key] = this.value;
                dirty = true;
                updateLocalConfig(ctrl.key, this.value);
                debouncedPreview();
            });
        } else {
            input = document.createElement('input');
            input.type = 'text';
            input.value = String(config[ctrl.key] || '');
            if (ctrl.unit) input.placeholder = ctrl.unit;
            input.addEventListener('input', function () {
                pendingValues[ctrl.key] = this.value;
                dirty = true;
                updateLocalConfig(ctrl.key, this.value);
                debouncedPreview();
            });
        }
        if (input.dataset) input.dataset.key = ctrl.key;
        return input;
    }

    function updateLocalConfig(key, val) {
        try {
            var rt = currentRoot;
            if (!rt) return;
            var confStr = rt.getAttribute('data-gbn-config') || '{}';
            var confObj = {};
            try {
                confObj = JSON.parse(confStr);
            } catch (e) {
                confObj = {};
            }
            confObj[key] = val;
            rt.setAttribute('data-gbn-config', JSON.stringify(confObj));
            if (key === 'post_type') {
                rt.setAttribute('data-post-type', String(val || ''));
            }
        } catch (e) {}
    }

    var debouncedSave = function () {}; // deshabilitado: ahora solo guardado manual

    function saveBlockOptions(panel) {
        if (!panel) return;
        var body = panel.querySelector('.gbn-body');
        var values = Object.assign({}, pendingValues);
        qsAll('.gbn-control input, .gbn-control select, .gbn-control textarea', body).forEach(function (el) {
            var k = el.dataset.key;
            if (!k) return;
            var v;
            if (el.type === 'checkbox') {
                v = el.checked ? 'yes' : 'no';
            } else if (el.type === 'radio') {
                v = el.checked ? el.value : undefined;
                if (v === undefined) return;
            } else {
                v = el.value;
                if (k.indexOf('width') !== -1 && v && v.indexOf('%') === -1) v = v + '%';
            }
            values[k] = v;
        });
        var fd = new FormData();
        fd.append('action', 'gbn_save_options');
        fd.append('nonce', (cfg || {}).nonce || '');
        fd.append('gbnId', panel.dataset.gbnId || '');
        fd.append('pageId', panel.dataset.pageId || '');
        fd.append('values', JSON.stringify(values));
        log('Guardando opciones', values);
        fetch((cfg || {}).ajaxUrl || '', {method: 'POST', body: fd, credentials: 'same-origin'})
            .then(function (r) {
                return r && r.json ? r.json() : null;
            })
            .then(function (res) {
                log('Respuesta opciones', res);
                try {
                    // Actualizar data-gbn-config del root activo (sin depender del selector)
                    var rt = currentRoot || document.querySelector('.glory-split[data-gbn-id="' + (panel.dataset.gbnId || '') + '"]');
                    if (rt) {
                        var confStr = rt.getAttribute('data-gbn-config') || '{}';
                        var confObj = {};
                        try {
                            confObj = JSON.parse(confStr);
                        } catch (e) {
                            confObj = {};
                        }
                        Object.keys(values).forEach(function (k) {
                            confObj[k] = values[k];
                        });
                        rt.setAttribute('data-gbn-config', JSON.stringify(confObj));
                        // Actualizar data-post-type si cambia
                        if (typeof values.post_type !== 'undefined') {
                            rt.setAttribute('data-post-type', String(values.post_type || ''));
                        }
                        // Mark clean tras guardar
                        pendingValues = {};
                        dirty = false;
                        // Recargar bloque primero, luego cerrar panel
                        try {
                            log('Recargando bloque tras guardar');
                            reloadBlockPreview().then(function() {
                                // Cerrar panel y deshabilitar drag después de recargar
                                try {
                                    log('Cerrando panel tras guardar y recarga');
                                    closePanel(document.getElementById('gbn-panel'));
                                    if (rt) {
                                        rt.classList.remove('gbn-open');
                                        updateDragState(rt, false);
                                    }
                                } catch (e) {
                                    log('Error cerrando panel/drag tras guardar', e);
                                }
                            }).catch(function(e) {
                                log('Error en recarga, cerrando panel de todos modos', e);
                                // Cerrar panel incluso si la recarga falla
                                try {
                                    closePanel(document.getElementById('gbn-panel'));
                                    if (rt) {
                                        rt.classList.remove('gbn-open');
                                        updateDragState(rt, false);
                                    }
                                } catch (e2) {
                                    log('Error cerrando panel/drag tras error de recarga', e2);
                                }
                            });
                        } catch (e) {
                            log('Error iniciando recarga, cerrando panel', e);
                            // Cerrar panel si no se puede recargar
                            try {
                                closePanel(document.getElementById('gbn-panel'));
                                if (rt) {
                                    rt.classList.remove('gbn-open');
                                    updateDragState(rt, false);
                                }
                            } catch (e2) {
                                log('Error cerrando panel tras error de recarga', e2);
                            }
                        }
                    }
                } catch (e) {
                    log('Error actualizando data-gbn-config', e);
                }
            })
            .catch(function (e) {
                log('Error guardando opciones', e);
            });
    }

    function savePageSettings(panel) {
        if (!panel || !currentRoot) return;
        var input = panel.querySelector('[data-key="background_color"]');
        var val = input ? input.value : '';
        var fd = new FormData();
        fd.append('action', 'gbn_save_page_settings');
        fd.append('nonce', (cfg || {}).nonce || '');
        fd.append('pageId', panel.dataset.pageId || '');
        fd.append('values', JSON.stringify({background_color: val}));
        fetch((cfg || {}).ajaxUrl || '', {method: 'POST', body: fd, credentials: 'same-origin'})
            .then(function (r) {
                return r && r.json ? r.json() : null;
            })
            .then(function () {
                try {
                    location.reload();
                } catch (e) {}
            })
            .catch(function (e) {
                log('Error guardando page settings', e);
            });
    }

    function handlePanelSave() {
        var panel = document.getElementById('gbn-panel');
        if (!panel) return;
        var mode = panel.dataset.mode || 'block';
        if (mode === 'page') {
            savePageSettings(panel);
        } else {
            saveBlockOptions(panel);
        }
    }

    function debounce(fn, t) {
        var id;
        return function () {
            var ctx = this,
                args = arguments;
            clearTimeout(id);
            id = setTimeout(function () {
                fn.apply(ctx, args);
            }, t || 300);
        };
    }

    // Debounce global para previsualizaciones intensivas (sliders y typing)
    var debouncedPreview = debounce(reloadBlockPreview, 3000);

    // Edit buttons y sortable
    function initRoot(root) {
        if (!root || root.__gbnInited) return;
        root.__gbnInited = true;
        try {
            if (!root.querySelector('.gbn-floating-edit')) {
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'gbn-floating-edit';
                btn.innerHTML =
                    '<svg data-testid="geist-icon" height="16" stroke-linejoin="round" style="color:currentColor" viewBox="0 0 16 16" width="16"><path d="M12.798 1.24199L13.3283 1.77232L14.0567 1.04389L13.1398 0.574402L12.798 1.24199ZM9.95705 4.0829L9.42672 3.55257L9.95705 4.0829ZM6.5844 6.95555L7.11473 7.48588L7.46767 7.13295L7.27837 6.67111L6.5844 6.95555ZM1.49995 12.04L2.03027 12.5703L2.03028 12.5703L1.49995 12.04ZM1.49994 14.54L0.969615 15.0703H0.969615L1.49994 14.54ZM3.99995 14.54L4.53028 15.0703L3.99995 14.54ZM9.10147 9.43848L9.37633 8.74066L8.91883 8.56046L8.57114 8.90815L9.10147 9.43848ZM14.7848 3.25519L15.4568 2.92229L14.9931 1.98617L14.2544 2.72486L14.7848 3.25519ZM11.9571 6.0829L11.4267 5.55257L11.9571 6.0829ZM10.5428 6.0829L11.0732 5.55257L11.0732 5.55257L10.5428 6.0829ZM9.95705 5.49711L9.42672 6.02744L9.42672 6.02745L9.95705 5.49711ZM12.2676 0.711655L9.42672 3.55257L10.4874 4.61323L13.3283 1.77232L12.2676 0.711655ZM10.7499 1.5C11.3659 1.5 11.9452 1.64794 12.4562 1.90957L13.1398 0.574402C12.4221 0.206958 11.6091 0 10.7499 0V1.5ZM6.99994 5.25C6.99994 3.17893 8.67888 1.5 10.7499 1.5V0C7.85045 0 5.49994 2.3505 5.49994 5.25H6.99994ZM7.27837 6.67111C7.09913 6.23381 6.99994 5.75443 6.99994 5.25H5.49994C5.49994 5.95288 5.63848 6.62528 5.89043 7.23999L7.27837 6.67111ZM6.05407 6.42522L0.969615 11.5097L2.03028 12.5703L7.11473 7.48588L6.05407 6.42522ZM0.969616 11.5097C-0.0136344 12.4929 -0.013635 14.0871 0.969615 15.0703L2.03027 14.0097C1.63281 13.6122 1.63281 12.9678 2.03027 12.5703L0.969616 11.5097ZM0.969615 15.0703C1.95287 16.0536 3.54703 16.0536 4.53028 15.0703L3.46962 14.0097C3.07215 14.4071 2.42774 14.4071 2.03027 14.0097L0.969615 15.0703ZM4.53028 15.0703L9.6318 9.96881L8.57114 8.90815L3.46962 14.0097L4.53028 15.0703ZM10.7499 9C10.2637 9 9.80071 8.90782 9.37633 8.74066L8.82661 10.1363C9.4232 10.3713 10.0724 10.5 10.7499 10.5V9ZM14.4999 5.25C14.4999 7.32107 12.821 9 10.7499 9V10.5C13.6494 10.5 15.9999 8.1495 15.9999 5.25H14.4999ZM14.1127 3.5881C14.3604 4.08811 14.4999 4.65168 14.4999 5.25H15.9999C15.9999 4.4156 15.8047 3.62465 15.4568 2.92229L14.1127 3.5881ZM12.4874 6.61323L15.3151 3.78552L14.2544 2.72486L11.4267 5.55257L12.4874 6.61323ZM10.0125 6.61323C10.6959 7.29665 11.804 7.29665 12.4874 6.61323L11.4267 5.55257C11.3291 5.6502 11.1708 5.6502 11.0732 5.55257L10.0125 6.61323ZM9.42672 6.02745L10.0125 6.61323L11.0732 5.55257L10.4874 4.96678L9.42672 6.02745ZM9.42672 3.55257C8.7433 4.23599 8.7433 5.34403 9.42672 6.02744L10.4874 4.96678C10.3898 4.86915 10.3897 4.71086 10.4874 4.61323L9.42672 3.55257Z" fill="currentColor"></path></svg>';
                btn.addEventListener('click', function () {
                    openPanelFor(root);
                });
                root.appendChild(btn);
                log('Botón Editar insertado', {gbnId: root.getAttribute('data-gbn-id'), pageId: root.getAttribute('data-gbn-page-id')});
            }
            var list = root.querySelector('.glory-content-list');
            if (list) {
                // íconos inline de acciones por título
                try {
                    list.querySelectorAll('.glory-content-item').forEach(function (item) {
                        if (item.__gbnActionsInited) return;
                        item.__gbnActionsInited = true;
                        var titleEl = item.querySelector('.glory-split__title');
                        if (!titleEl) return;
                        var actions = document.createElement('span');
                        actions.className = 'gbn-title-actions';
                        actions.innerHTML =
                            '<button type="button" class="gbn-title-edit" title="Editar"><svg data-testid="geist-icon" height="16" stroke-linejoin="round" style="color:currentColor" viewBox="0 0 16 16" width="16"><path fill-rule="evenodd" clip-rule="evenodd" d="M12.2803 0.719661L11.75 0.189331L11.2197 0.719661L1.09835 10.841C0.395088 11.5442 0 12.4981 0 13.4926V15.25V16H0.75H2.50736C3.50192 16 4.45575 15.6049 5.15901 14.9016L15.2803 4.78032L15.8107 4.24999L15.2803 3.71966L12.2803 0.719661ZM9.81066 4.24999L11.75 2.31065L13.6893 4.24999L11.75 6.18933L9.81066 4.24999ZM8.75 5.31065L2.15901 11.9016C1.73705 12.3236 1.5 12.8959 1.5 13.4926V14.5H2.50736C3.1041 14.5 3.67639 14.2629 4.09835 13.841L10.6893 7.24999L8.75 5.31065Z" fill="currentColor"></path></svg></button>' +
                            '<button type="button" class="gbn-title-add" title="Nuevo"><svg data-testid="geist-icon" height="16" stroke-linejoin="round" style="color:currentColor" viewBox="0 0 16 16" width="16"><path fill-rule="evenodd" clip-rule="evenodd" d="M 8.75,1 H7.25 V7.25 H1.5 V8.75 H7.25 V15 H8.75 V8.75 H14.5 V7.25 H8.75 V1.75 Z" fill="currentColor"></path></svg></button>' +
                            '<button type="button" class="gbn-title-delete" title="Eliminar"><svg data-testid="geist-icon" height="16" stroke-linejoin="round" style="color:currentColor" viewBox="0 0 16 16" width="16"><path fill-rule="evenodd" clip-rule="evenodd" d="M6.75 2.75C6.75 2.05964 7.30964 1.5 8 1.5C8.69036 1.5 9.25 2.05964 9.25 2.75V3H6.75V2.75ZM5.25 3V2.75C5.25 1.23122 6.48122 0 8 0C9.51878 0 10.75 1.23122 10.75 2.75V3H12.9201H14.25H15V4.5H14.25H13.8846L13.1776 13.6917C13.0774 14.9942 11.9913 16 10.6849 16H5.31508C4.00874 16 2.92263 14.9942 2.82244 13.6917L2.11538 4.5H1.75H1V3H1.75H3.07988H5.25ZM4.31802 13.5767L3.61982 4.5H12.3802L11.682 13.5767C11.6419 14.0977 11.2075 14.5 10.6849 14.5H5.31508C4.79254 14.5 4.3581 14.0977 4.31802 13.5767Z" fill="currentColor"></path></svg></button>';
                        titleEl.parentNode.insertBefore(actions, titleEl.nextSibling);

                        // eventos
                        var editBtn = actions.querySelector('.gbn-title-edit');
                        var addBtn = actions.querySelector('.gbn-title-add');
                        var deleteBtn = actions.querySelector('.gbn-title-delete');
                        editBtn.classList.add('u-pointer-auto');
                        addBtn.classList.add('u-pointer-auto');
                        deleteBtn.classList.add('u-pointer-auto');
                        editBtn.addEventListener('click', function (e) {
                            e.preventDefault();
                            e.stopPropagation();
                            openEditModal(item);
                        });
                        addBtn.addEventListener('click', function (e) {
                            e.preventDefault();
                            e.stopPropagation();
                            openAddModal(item);
                        });
                        deleteBtn.addEventListener('click', function (e) {
                            e.preventDefault();
                            e.stopPropagation();
                            openDeleteModal(item);
                        });
                        // asegurar que el título no capture eventos por encima
                        if (titleEl) {
                            titleEl.classList.add('u-pointer-auto');
                        }
                    });
                } catch (e) {
                    log('Error agregando acciones por título', e);
                }
                list.querySelectorAll('.glory-content-item').forEach(function (item) {
                    item.setAttribute('draggable', 'false');
                    item.addEventListener('dragstart', function (e) {
                        if (!root.classList.contains('gbn-open')) {
                            e.preventDefault();
                            return false;
                        }
                        e.dataTransfer.setData('text/plain', String(Array.prototype.indexOf.call(list.children, item)));
                        item.classList.add('is-drag');
                    });
                    item.addEventListener('dragend', function () {
                        item.classList.remove('is-drag');
                    });
                });
                list.addEventListener('dragover', function (e) {
                    e.preventDefault();
                    var dragging = list.querySelector('.is-drag');
                    var target = e.target.closest('.glory-content-item');
                    if (!dragging || !target || dragging === target) return;
                    var rect = target.getBoundingClientRect();
                    var before = e.clientY - rect.top < rect.height / 2;
                    list.insertBefore(dragging, before ? target : target.nextSibling);
                });
                list.addEventListener('drop', function () {
                    saveOrder(root, list);
                });
            }
        } catch (e) {
            log('Error iniciando root', e);
        }
    }

    var roots = qsAll('.glory-split');
    log('Contenedores .glory-split encontrados:', roots.length);
    roots.forEach(initRoot);

    try {
        var obsRoots = new MutationObserver(function (muts) {
            muts.forEach(function (m) {
                Array.prototype.slice.call(m.addedNodes || []).forEach(function (n) {
                    if (n && n.nodeType === 1) {
                        if (n.classList && n.classList.contains('glory-split')) {
                            initRoot(n);
                        }
                        qsAll('.glory-split', n).forEach(initRoot);
                    }
                });
            });
        });
        obsRoots.observe(document.documentElement, {childList: true, subtree: true});
    } catch (e) {
        log('Error iniciando observer de roots', e);
    }

    function saveOrder(root, list) {
        var ids = [];
        list.querySelectorAll('.glory-content-item').forEach(function (item) {
            var id = item.getAttribute('data-post-id');
            if (id) ids.push(id);
        });
        var fd = new FormData();
        fd.append('action', 'gbn_save_order');
        fd.append('nonce', (cfg || {}).nonce || '');
        fd.append('gbnId', root.getAttribute('data-gbn-id') || '');
        fd.append('pageId', root.getAttribute('data-gbn-page-id') || '');
        fd.append('postIds', JSON.stringify(ids));
        log('Guardando orden', ids);
        fetch((cfg || {}).ajaxUrl || '', {method: 'POST', body: fd, credentials: 'same-origin'})
            .then(function (r) {
                return r && r.json ? r.json() : null;
            })
            .then(function (res) {
                log('Respuesta orden', res);
            })
            .catch(function (e) {
                log('Error guardando orden', e);
            });
    }

    function updateDragState(root, enabled) {
        try {
            var list = root.querySelector('.glory-content-list');
            if (!list) return;
            list.querySelectorAll('.glory-content-item').forEach(function (item) {
                item.setAttribute('draggable', enabled ? 'true' : 'false');
            });
        } catch (e) {}
    }

    function getConfig(root) {
        try {
            return JSON.parse(root.getAttribute('data-gbn-config') || '{}');
        } catch (e) {
            return {};
        }
    }

    function reloadBlockPreview() {
        return new Promise(function(resolve, reject) {
            var rt = currentRoot;
            if (!rt) {
                resolve();
                return;
            }
            try {
                var conf = getConfig(rt) || {};
                var fd = new FormData();
                fd.append('action', 'gbn_preview_block');
                fd.append('nonce', (cfg || {}).nonce || '');
                fd.append('gbnId', rt.getAttribute('data-gbn-id') || '');
                fd.append('pageId', rt.getAttribute('data-gbn-page-id') || '');
                fd.append('values', JSON.stringify(conf));
                log('Solicitando preview con conf', conf);
                fetch((cfg || {}).ajaxUrl || '', {method: 'POST', body: fd, credentials: 'same-origin'})
                    .then(function (r) {
                        return r && r.json ? r.json() : null;
                    })
                    .then(function (res) {
                        log('Respuesta preview', res);
                        var html = null;
                        if (res && res.success) {
                            if (res.data && typeof res.data.html === 'string') html = res.data.html;
                            else if (typeof res.html === 'string') html = res.html;
                            else if (typeof res.data === 'string') html = res.data;
                        }
                        if (!html) {
                            log('Preview sin html, abortando');
                            resolve();
                            return;
                        }
                        var parser = new DOMParser();
                        var doc = parser.parseFromString(html, 'text/html');
                        var newRoot = doc.querySelector('.glory-split');
                        if (!newRoot) {
                            log('Preview sin .glory-split');
                            resolve();
                            return;
                        }
                        log('Nuevo root generado para preview', newRoot);
                        // Reemplazar/actualizar el <style id="<instance>-css"> con CSS por instancia
                        try {
                            var instClass =
                                Array.prototype.find.call(newRoot.classList, function (c) {
                                    return /^glory-split-[a-z0-9]+$/i.test(c);
                                }) || null;
                            if (instClass) {
                                var styleId = instClass + '-css';
                                var newStyle = doc.getElementById(styleId);
                                if (newStyle) {
                                    var existingStyle = document.getElementById(styleId);
                                    if (existingStyle) {
                                        existingStyle.textContent = newStyle.textContent;
                                        log('CSS por instancia actualizado en style#' + styleId);
                                    } else {
                                        // Inyectar el CSS a <head> si no existe
                                        try {
                                            document.head.appendChild(newStyle.cloneNode(true));
                                            log('CSS por instancia insertado en <head> style#' + styleId);
                                        } catch (e) {}
                                    }
                                } else {
                                    log('No se encontró style por instancia en la respuesta para', styleId);
                                }
                            } else {
                                log('No se pudo determinar clase de instancia (glory-split-*)');
                            }
                        } catch (e) {
                            log('Error actualizando style por instancia', e);
                        }
                        // Reemplazar el root actual (solo si aún tiene padre)
                        if (rt.parentNode) {
                            rt.outerHTML = newRoot.outerHTML;
                        } else {
                            log('Elemento ya no tiene padre, no se puede reemplazar');
                            resolve();
                            return;
                        }
                        // Reasignar referencia
                        var selector = '.glory-split[data-gbn-id="' + (rt.getAttribute('data-gbn-id') || '') + '"]';
                        var updated = document.querySelector(selector) || document.querySelector('.glory-split');
                        if (!updated) {
                            log('No se pudo encontrar el root actualizado');
                            resolve();
                            return;
                        }
                        currentRoot = updated;
                        // Inyectar botones flotantes si faltan
                        if (!updated.querySelector('.gbn-floating-edit')) {
                            var btn = document.createElement('button');
                            btn.type = 'button';
                            btn.className = 'gbn-floating-edit';
                            btn.innerHTML =
                                '<svg data-testid="geist-icon" height="16" stroke-linejoin="round" style="color:currentColor" viewBox="0 0 16 16" width="16"><path d="M12.798 1.24199L13.3283 1.77232L14.0567 1.04389L13.1398 0.574402L12.798 1.24199ZM9.95705 4.0829L9.42672 3.55257L9.95705 4.0829ZM6.5844 6.95555L7.11473 7.48588L7.46767 7.13295L7.27837 6.67111L6.5844 6.95555ZM1.49995 12.04L2.03027 12.5703L2.03028 12.5703L1.49995 12.04ZM1.49994 14.54L0.969615 15.0703H0.969615L1.49994 14.54ZM3.99995 14.54L4.53028 15.0703L3.99995 14.54ZM9.10147 9.43848L9.37633 8.74066L8.91883 8.56046L8.57114 8.90815L9.10147 9.43848ZM14.7848 3.25519L15.4568 2.92229L14.9931 1.98617L14.2544 2.72486L14.7848 3.25519ZM11.9571 6.0829L11.4267 5.55257L11.9571 6.0829ZM10.5428 6.0829L11.0732 5.55257L11.0732 5.55257L10.5428 6.0829ZM9.95705 5.49711L9.42672 6.02744L9.42672 6.02745L9.95705 5.49711ZM12.2676 0.711655L9.42672 3.55257L10.4874 4.61323L13.3283 1.77232L12.2676 0.711655ZM10.7499 1.5C11.3659 1.5 11.9452 1.64794 12.4562 1.90957L13.1398 0.574402C12.4221 0.206958 11.6091 0 10.7499 0V1.5ZM6.99994 5.25C6.99994 3.17893 8.67888 1.5 10.7499 1.5V0C7.85045 0 5.49994 2.3505 5.49994 5.25H6.99994ZM7.27837 6.67111C7.09913 6.23381 6.99994 5.75443 6.99994 5.25H5.49994C5.49994 5.95288 5.63848 6.62528 5.89043 7.23999L7.27837 6.67111ZM6.05407 6.42522L0.969615 11.5097L2.03028 12.5703L7.11473 7.48588L6.05407 6.42522ZM0.969616 11.5097C-0.0136344 12.4929 -0.013635 14.0871 0.969615 15.0703L2.03027 14.0097C1.63281 13.6122 1.63281 12.9678 2.03027 12.5703L0.969616 11.5097ZM0.969615 15.0703C1.95287 16.0536 3.54703 16.0536 4.53028 15.0703L3.46962 14.0097C3.07215 14.4071 2.42774 14.4071 2.03027 14.0097L0.969615 15.0703ZM4.53028 15.0703L9.6318 9.96881L8.57114 8.90815L3.46962 14.0097L4.53028 15.0703ZM10.7499 9C10.2637 9 9.80071 8.90782 9.37633 8.74066L8.82661 10.1363C9.4232 10.3713 10.0724 10.5 10.7499 10.5V9ZM14.4999 5.25C14.4999 7.32107 12.821 9 10.7499 9V10.5C13.6494 10.5 15.9999 8.1495 15.9999 5.25H14.4999ZM14.1127 3.5881C14.3604 4.08811 14.4999 4.65168 14.4999 5.25H15.9999C15.9999 4.4156 15.8047 3.62465 15.4568 2.92229L14.1127 3.5881ZM12.4874 6.61323L15.3151 3.78552L14.2544 2.72486L11.4267 5.55257L12.4874 6.61323ZM10.0125 6.61323C10.6959 7.29665 11.804 7.29665 12.4874 6.61323L11.4267 5.55257C11.3291 5.6502 11.1708 5.6502 11.0732 5.55257L10.0125 6.61323ZM9.42672 6.02745L10.0125 6.61323L11.0732 5.55257L10.4874 4.96678L9.42672 6.02745ZM9.42672 3.55257C8.7433 4.23599 8.7433 5.34403 9.42672 6.02744L10.4874 4.96678C10.3898 4.86915 10.3897 4.71086 10.4874 4.61323L9.42672 3.55257Z" fill="currentColor"></path></svg>';
                            btn.addEventListener('click', function () {
                                openPanelFor(updated);
                            });
                            updated.appendChild(btn);
                        }
                        if (!updated.querySelector('.gbn-floating-settings')) {
                            var gear = document.createElement('button');
                            gear.type = 'button';
                            gear.className = 'gbn-floating-settings';
                            gear.innerHTML =
                                '<svg data-testid="geist-icon" height="14" width="14" viewBox="0 0 16 16" style="color:currentColor"><path d="M7.70059 1.73618L7.74488 1.5H8.2551L8.29938 1.73618C8.4406 2.48936 8.98357 3.04807 9.63284 3.27226C9.82296 3.33791 10.008 3.41476 10.1871 3.50207C10.805 3.80328 11.5845 3.7922 12.2172 3.35933L12.4158 3.22342L12.7766 3.5842L12.6407 3.78284C12.2078 4.41549 12.1967 5.19496 12.4979 5.81292C12.5852 5.99203 12.6621 6.17703 12.7277 6.36714C12.9519 7.01642 13.5106 7.55938 14.2638 7.7006L14.5 7.74489V8.25511L14.2638 8.2994C13.5106 8.44062 12.9519 8.98359 12.7277 9.63286C12.6621 9.82298 12.5852 10.008 12.4979 10.1871C12.1967 10.805 12.2078 11.5845 12.6407 12.2172L12.7766 12.4158L12.4158 12.7766L12.2172 12.6407C11.5845 12.2078 10.805 12.1967 10.1871 12.4979C10.008 12.5852 9.82296 12.6621 9.63284 12.7277C8.98357 12.9519 8.4406 13.5106 8.29938 14.2638L8.2551 14.5H7.74488L7.70059 14.2638C7.55937 13.5106 7.0164 12.9519 6.36713 12.7277C6.17702 12.6621 5.99202 12.5852 5.8129 12.4979C5.19495 12.1967 4.41548 12.2078 3.78283 12.6407L3.5842 12.7766L3.22342 12.4158L3.35932 12.2172C3.79219 11.5845 3.80326 10.8051 3.50206 10.1871C3.41475 10.008 3.3379 9.82298 3.27225 9.63285C3.04806 8.98358 2.48935 8.44061 1.73616 8.29939L1.5 8.25511V7.74489L1.73616 7.70061C2.48935 7.55939 3.04806 7.01642 3.27225 6.36715C3.3379 6.17703 3.41475 5.99203 3.50205 5.81291C3.80326 5.19496 3.79218 4.41549 3.35931 3.78283L3.2234 3.5842L3.58418 3.22342L3.78282 3.35932C4.41547 3.79219 5.19494 3.80327 5.8129 3.50207C5.99201 3.41476 6.17701 3.33791 6.36713 3.27226C7.0164 3.04807 7.55937 2.48936 7.70059 1.73618ZM9.49998 8C9.49998 8.82843 8.82841 9.5 7.99998 9.5C7.17156 9.5 6.49998 8.82843 6.49998 8C6.49998 7.17157 7.17156 6.5 7.99998 6.5C8.82841 6.5 9.49998 7.17157 9.49998 8ZM11 8C11 9.65685 9.65684 11 7.99998 11C6.34313 11 4.99998 9.65685 4.99998 8C4.99998 6.34315 6.34313 5 7.99998 5C9.65684 5 11 6.34315 11 8Z" fill="currentColor"></path></svg>';
                            gear.addEventListener('click', function () {
                                openPageSettings(updated);
                            });
                            updated.appendChild(gear);
                        }
                        // Habilitar drag
                        try {
                            updated.classList.add('gbn-open');
                            updateDragState(updated, true);
                        } catch (e) {}
                        log('Preview actualizado en el DOM', updated);
                        resolve();
                    }).catch(function(e) {
                        log('Error en fetch de preview', e);
                        reject(e);
                    });
            } catch (e) {
                log('Error general recargando preview', e);
                reject(e);
            }
        });
    }

    function refreshActiveContent(root, conf) {
        try {
            var active = root.querySelector('.glory-split__item.is-active .glory-split__title');
            if (active) {
                active.click();
                return;
            }
            if ((conf || {}).auto_open_first_item === 'yes') {
                var first = root.querySelector('.glory-split__item .glory-split__title');
                // Si el contenedor de contenido ya tiene algo, no hacer click (contenido precargado por PHP)
                var content = root.querySelector('.glory-split__content');
                if (first && content && !content.hasChildNodes()) {
                    first.click();
                }
            }
        } catch (e) {}
    }

    // Auto Open First Item instantáneo: asegurar apertura inmediata si está habilitado
    (function instantAutoOpen() {
        try {
            qsAll('.glory-split').forEach(function (root) {
                var conf = getConfig(root);
                var slug = (location.hash || '').replace(/^#/, '');
                var hasHashTarget = slug ? !!root.querySelector('.glory-split__item[data-post-slug="' + CSS.escape(slug) + '"]') : false;
                if ((conf || {}).auto_open_first_item === 'yes' && !hasHashTarget) {
                    var tryOpen = function () {
                        var first = root.querySelector('.glory-split__item .glory-split__title');
                        var content = root.querySelector('.glory-split__content');
                        if (first && content && !content.hasChildNodes()) {
                            first.click();
                            return true;
                        }
                        return false;
                    };
                    if (!tryOpen()) {
                        var obs = new MutationObserver(function () {
                            if (tryOpen()) {
                                try {
                                    obs.disconnect();
                                } catch (e) {}
                            }
                        });
                        obs.observe(root, {childList: true, subtree: true});
                    }
                }
            });
        } catch (e) {}
    })();

    // Modales mínimos
    function ensureModal() {
        var modal = document.getElementById('gbn-mini-modal');
        if (modal) return modal;
        modal = document.createElement('div');
        modal.id = 'gbn-mini-modal';
        modal.innerHTML = '<div class="gbn-mini-overlay"></div><div class="gbn-mini-dialog"><div class="gbn-mini-body"></div><div class="gbn-mini-actions"></div></div>';
        document.body.appendChild(modal);
        modal.addEventListener('click', function (e) {
            if (e.target.classList.contains('gbn-mini-overlay')) closeModal();
        });
        return modal;
    }
    function openModal(html, actions) {
        var modal = ensureModal();
        modal.querySelector('.gbn-mini-body').innerHTML = html || '';
        var act = modal.querySelector('.gbn-mini-actions');
        act.innerHTML = '';
        (actions || []).forEach(function (btn) {
            var b = document.createElement('button');
            b.type = 'button';
            b.textContent = btn.label;
            b.className = 'gbn-mini-btn gbn-btn';
        b.addEventListener('click', function () {
            try {
                btn.onClick && btn.onClick();
            } finally {
                // Si el botón indica mantener abierto, no cerrar aquí
                if (!btn.keepOpen) {
                    closeModal();
                }
            }
        });
            act.appendChild(b);
        });
        modal.classList.add('is-open');
    }
    function closeModal() {
        var m = document.getElementById('gbn-mini-modal');
        if (m) m.classList.remove('is-open');
    }

    function openEditModal(item) {
        var postId = item.getAttribute('data-post-id');
        var postUrl = item.getAttribute('data-post-url') || '';
        var isHeader = item.classList.contains('glory-split__item--header');
        if (!postId && !postUrl) return;

        // Si es un link (tiene data-post-url), abrir modal de edición de link
        if (postUrl) {
            openLinkEditModal(item);
            return;
        }

        // Si es un header, abrir modal de edición de header
        if (isHeader) {
            openHeaderEditModal(item);
            return;
        }

        openModal('<span>Choose editing mode</span>', [
            {
                label: 'Easy edit',
                onClick: function () {
                    if (postId) {
                        window.location.href = '/wp-admin/post.php?post=' + encodeURIComponent(postId) + '&action=edit&classic-editor';
                    }
                }
            },
            {
                label: 'Advanced edit',
                onClick: function () {
                    var permalink = item.getAttribute('data-post-permalink') || '';
                    if (permalink) {
                        window.location.href = permalink + (permalink.indexOf('?') === -1 ? '?' : '&') + 'fb-edit=1';
                    }
                }
            }
        ]);
    }

    function openDeleteModal(item) {
        var postId = item.getAttribute('data-post-id');
        var postUrl = item.getAttribute('data-post-url') || '';
        var isHeader = item.classList.contains('glory-split__item--header');
        var isLink = !!postUrl;

        var itemType = 'post';
        if (isHeader) itemType = 'header';
        else if (isLink) itemType = 'link';

        var titleText = item.querySelector('.glory-split__title-text').textContent.trim();

        openModal('<div class="gbn-mini-padded">¿Está seguro de que desea eliminar "' + titleText + '"?<br><small>Este elemento será movido a la papelera.</small></div>', [
            {
                label: 'Cancelar',
                onClick: function () {
                    closeModal();
                }
            },
            {
                label: 'Eliminar',
                onClick: function () {
                    deleteItem(item, itemType, postId, postUrl);
                }
            }
        ]);
    }

    function deleteItem(item, itemType, postId, postUrl) {
        var fd = new FormData();
        fd.append('action', 'gbn_delete_item');
        fd.append('nonce', (cfg || {}).nonce || '');
        fd.append('item_type', itemType);

        if (itemType === 'link') {
            fd.append('post_url', postUrl);
        } else {
            fd.append('post_id', postId);
        }

        fetch((cfg || {}).ajaxUrl || '/wp-admin/admin-ajax.php', {
            method: 'POST',
            body: fd,
            credentials: 'same-origin'
        }).then(function(r) { return r.json(); }).then(function(res) {
            if (res.success) {
                closeModal();
                location.reload(); // Recargar para mostrar los cambios
            } else {
                alert('Error: ' + (res.data || 'Error desconocido al eliminar'));
            }
        }).catch(function(e) {
            alert('Error: ' + e.message);
        });
    }

    function openLinkEditModal(item) {
        // Cerrar cualquier modal abierto antes de abrir el nuevo
        closeModal();

        var isEdit = item && item.getAttribute('data-post-url'); // Es edición si tiene data-post-url (es un link)
        var postId = isEdit ? item.getAttribute('data-post-id') : null;
        var title = isEdit ? item.querySelector('.glory-split__title-text').textContent.trim() : '';
        var url = isEdit ? item.getAttribute('data-post-url') : '';

        var html = '<div class="gbn-mini-content">';
        html += '<h3 class="gbn-mini-title">' + (isEdit ? 'Edit Link' : 'Create New Link') + '</h3>';

        html += '<div class="gbn-control">';
        html += '<label>Title:</label>';
        html += '<input type="text" id="link-title" placeholder="Enter link title" value="' + (title || '') + '">';
        html += '</div>';

        html += '<div class="gbn-control">';
        html += '<label>URL:</label>';
        html += '<input type="url" id="link-url" placeholder="https://example.com" value="' + (url || '') + '">';
        html += '</div>';

        html += '</div>';

        openModal(html, [
            {
                label: 'Cancel',
                onClick: function () {
                    closeModal();
                }
            },
            {
                label: isEdit ? 'Update' : 'Create',
                primary: true,
                onClick: function () {
                    var newTitle = document.getElementById('link-title').value.trim();
                    var newUrl = document.getElementById('link-url').value.trim();

                    if (!newTitle || !newUrl) {
                        alert('Please fill in both title and URL');
                        return;
                    }

                    var fd = new FormData();
                    fd.append('action', isEdit ? 'update_glory_link' : 'create_glory_link');
                    fd.append('nonce', (cfg || {}).nonce || '');
                    if (isEdit) fd.append('post_id', postId);
                    fd.append('title', newTitle);
                    fd.append('url', newUrl);

                    fetch((cfg || {}).ajaxUrl || '/wp-admin/admin-ajax.php', {
                        method: 'POST',
                        body: fd,
                        credentials: 'same-origin'
                    }).then(function(r) { return r.json(); }).then(function(res) {
                        if (res.success) {
                            closeModal();
                            location.reload(); // Recargar para mostrar los cambios
                        } else {
                            alert('Error: ' + (res.data || 'Unknown error'));
                        }
                    }).catch(function(e) {
                        alert('Error: ' + e.message);
                    });
                }
            }
        ]);
    }

    function openHeaderEditModal(item) {
        // Cerrar cualquier modal abierto antes de abrir el nuevo
        closeModal();

        var isEdit = item && item.classList.contains('glory-split__item--header'); // Es edición si es un header
        var postId = isEdit ? item.getAttribute('data-post-id') : null;
        var title = isEdit ? item.querySelector('.glory-split__title-text').textContent.trim() : '';
        var paddingTop = '';
        var paddingBottom = '';

        // Si es edición, intentar obtener los valores de padding desde el estilo inline
        if (isEdit && item.style.paddingTop) {
            paddingTop = item.style.paddingTop;
        }
        if (isEdit && item.style.paddingBottom) {
            paddingBottom = item.style.paddingBottom;
        }

        var html = '<div class="gbn-mini-content">';
        html += '<h3 class="gbn-mini-title">' + (isEdit ? 'Edit Header' : 'Create New Header') + '</h3>';

        html += '<div class="gbn-control">';
        html += '<label>Title:</label>';
        html += '<input type="text" id="header-title" placeholder="Enter header title" value="' + (title || '') + '">';
        html += '</div>';

        html += '<div class="gbn-control">';
        html += '<label>Padding Top:</label>';
        html += '<input type="text" id="header-padding-top" placeholder="0px" value="' + (paddingTop || '') + '">';
        html += '</div>';

        html += '<div class="gbn-control">';
        html += '<label>Padding Bottom:</label>';
        html += '<input type="text" id="header-padding-bottom" placeholder="0px" value="' + (paddingBottom || '') + '">';
        html += '</div>';

        html += '</div>';

        openModal(html, [
            {
                label: 'Cancel',
                onClick: function () {
                    closeModal();
                }
            },
            {
                label: isEdit ? 'Update' : 'Create',
                primary: true,
                onClick: function () {
                    var newTitle = document.getElementById('header-title').value.trim();
                    var newPaddingTop = document.getElementById('header-padding-top').value.trim();
                    var newPaddingBottom = document.getElementById('header-padding-bottom').value.trim();

                    if (!newTitle) {
                        alert('Please enter a title');
                        return;
                    }

                    var fd = new FormData();
                    fd.append('action', isEdit ? 'update_glory_header' : 'create_glory_header');
                    fd.append('nonce', (cfg || {}).nonce || '');
                    if (isEdit) fd.append('post_id', postId);
                    fd.append('title', newTitle);
                    fd.append('padding_top', newPaddingTop);
                    fd.append('padding_bottom', newPaddingBottom);

                    fetch((cfg || {}).ajaxUrl || '/wp-admin/admin-ajax.php', {
                        method: 'POST',
                        body: fd,
                        credentials: 'same-origin'
                    }).then(function(r) { return r.json(); }).then(function(res) {
                        if (res.success) {
                            closeModal();
                            location.reload(); // Recargar para mostrar los cambios
                        } else {
                            alert('Error: ' + (res.data || 'Unknown error'));
                        }
                    }).catch(function(e) {
                        alert('Error: ' + e.message);
                    });
                }
            }
        ]);
    }

    function openAddModal(contextItem) {
        openModal('<div>Create new</div>', [
            {
                label: 'New Post',
                onClick: function () {
                    window.location.href = '/wp-admin/post-new.php';
                }
            },
            {
                label: 'New Link',
                keepOpen: true,
                onClick: function () {
                    openLinkEditModal(null); // null indica modo crear
                }
            },
            {
                label: 'New Title',
                keepOpen: true,
                onClick: function () {
                    openHeaderEditModal(null); // null indica modo crear
                }
            }
        ]);
    }

    // Botón de ajustes de página (engranaje) junto al botón editar
    try {
        qsAll('.glory-split').forEach(function (root) {
            if (root.__gbnSettingsBtn) return;
            root.__gbnSettingsBtn = true;
            var gear = document.createElement('button');
            gear.type = 'button';
            gear.className = 'gbn-floating-settings';
            gear.innerHTML =
                '<svg data-testid="geist-icon" height="14" width="14" viewBox="0 0 16 16" style="color:currentColor"><path d="M7.70059 1.73618L7.74488 1.5H8.2551L8.29938 1.73618C8.4406 2.48936 8.98357 3.04807 9.63284 3.27226C9.82296 3.33791 10.008 3.41476 10.1871 3.50207C10.805 3.80328 11.5845 3.7922 12.2172 3.35933L12.4158 3.22342L12.7766 3.5842L12.6407 3.78284C12.2078 4.41549 12.1967 5.19496 12.4979 5.81292C12.5852 5.99203 12.6621 6.17703 12.7277 6.36714C12.9519 7.01642 13.5106 7.55938 14.2638 7.7006L14.5 7.74489V8.25511L14.2638 8.2994C13.5106 8.44062 12.9519 8.98359 12.7277 9.63286C12.6621 9.82298 12.5852 10.008 12.4979 10.1871C12.1967 10.805 12.2078 11.5845 12.6407 12.2172L12.7766 12.4158L12.4158 12.7766L12.2172 12.6407C11.5845 12.2078 10.805 12.1967 10.1871 12.4979C10.008 12.5852 9.82296 12.6621 9.63284 12.7277C8.98357 12.9519 8.4406 13.5106 8.29938 14.2638L8.2551 14.5H7.74488L7.70059 14.2638C7.55937 13.5106 7.0164 12.9519 6.36713 12.7277C6.17702 12.6621 5.99202 12.5852 5.8129 12.4979C5.19495 12.1967 4.41548 12.2078 3.78283 12.6407L3.5842 12.7766L3.22342 12.4158L3.35932 12.2172C3.79219 11.5845 3.80326 10.8051 3.50206 10.1871C3.41475 10.008 3.3379 9.82298 3.27225 9.63285C3.04806 8.98358 2.48935 8.44061 1.73616 8.29939L1.5 8.25511V7.74489L1.73616 7.70061C2.48935 7.55939 3.04806 7.01642 3.27225 6.36715C3.3379 6.17703 3.41475 5.99203 3.50205 5.81291C3.80326 5.19496 3.79218 4.41549 3.35931 3.78283L3.2234 3.5842L3.58418 3.22342L3.78282 3.35932C4.41547 3.79219 5.19494 3.80327 5.8129 3.50207C5.99201 3.41476 6.17701 3.33791 6.36713 3.27226C7.0164 3.04807 7.55937 2.48936 7.70059 1.73618ZM9.49998 8C9.49998 8.82843 8.82841 9.5 7.99998 9.5C7.17156 9.5 6.49998 8.82843 6.49998 8C6.49998 7.17157 7.17156 6.5 7.99998 6.5C8.82841 6.5 9.49998 7.17157 9.49998 8ZM11 8C11 9.65685 9.65684 11 7.99998 11C6.34313 11 4.99998 9.65685 4.99998 8C4.99998 6.34315 6.34313 5 7.99998 5C9.65684 5 11 6.34315 11 8Z" fill="currentColor"></path></svg>';
            gear.addEventListener('click', function () {
                openPageSettings(root);
            });
            root.appendChild(gear);
        });
    } catch (e) {
        log('Error settings btn', e);
    }

    // Observer global para asegurar que los botones flotantes estén presentes después de cargas dinámicas
    try {
        var globalObserver = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                if (mutation.type === 'childList') {
                    mutation.addedNodes.forEach(function (node) {
                        if (node.nodeType === 1) {
                            // Si se agregó un root de glory-split
                            if (node.classList && node.classList.contains('glory-split')) {
                                ensureSettingsButton(node);
                            }
                            // Si se agregó contenido dentro de un root existente
                            if (node.querySelectorAll) {
                                node.querySelectorAll('.glory-split').forEach(ensureSettingsButton);
                            }
                        }
                    });
                }
            });
        });

        // Función para asegurar que el botón de settings esté presente
        function ensureSettingsButton(root) {
            if (!root || !root.classList || !root.classList.contains('glory-split')) return;
            if (root.__gbnSettingsBtn) return;

            root.__gbnSettingsBtn = true;
            var gear = document.createElement('button');
            gear.type = 'button';
            gear.className = 'gbn-floating-settings';
            gear.innerHTML =
                '<svg data-testid="geist-icon" height="14" width="14" viewBox="0 0 16 16" style="color:currentColor"><path d="M7.70059 1.73618L7.74488 1.5H8.2551L8.29938 1.73618C8.4406 2.48936 8.98357 3.04807 9.63284 3.27226C9.82296 3.33791 10.008 3.41476 10.1871 3.50207C10.805 3.80328 11.5845 3.7922 12.2172 3.35933L12.4158 3.22342L12.7766 3.5842L12.6407 3.78284C12.2078 4.41549 12.1967 5.19496 12.4979 5.81292C12.5852 5.99203 12.6621 6.17703 12.7277 6.36714C12.9519 7.01642 13.5106 7.55938 14.2638 7.7006L14.5 7.74489V8.25511L14.2638 8.2994C13.5106 8.44062 12.9519 8.98359 12.7277 9.63286C12.6621 9.82298 12.5852 10.008 12.4979 10.1871C12.1967 10.805 12.2078 11.5845 12.6407 12.2172L12.7766 12.4158L12.4158 12.7766L12.2172 12.6407C11.5845 12.2078 10.805 12.1967 10.1871 12.4979C10.008 12.5852 9.82296 12.6621 9.63284 12.7277C8.98357 12.9519 8.4406 13.5106 8.29938 14.2638L8.2551 14.5H7.74488L7.70059 14.2638C7.55937 13.5106 7.0164 12.9519 6.36713 12.7277C6.17702 12.6621 5.99202 12.5852 5.8129 12.4979C5.19495 12.1967 4.41548 12.2078 3.78283 12.6407L3.5842 12.7766L3.22342 12.4158L3.35932 12.2172C3.79219 11.5845 3.80326 10.8051 3.50206 10.1871C3.41475 10.008 3.3379 9.82298 3.27225 9.63285C3.04806 8.98358 2.48935 8.44061 1.73616 8.29939L1.5 8.25511V7.74489L1.73616 7.70061C2.48935 7.55939 3.04806 7.01642 3.27225 6.36715C3.3379 6.17703 3.41475 5.99203 3.50205 5.81291C3.80326 5.19496 3.79218 4.41549 3.35931 3.78283L3.2234 3.5842L3.58418 3.22342L3.78282 3.35932C4.41547 3.79219 5.19494 3.80327 5.8129 3.50207C5.99201 3.41476 6.17701 3.33791 6.36713 3.27226C7.0164 3.04807 7.55937 2.48936 7.70059 1.73618ZM9.49998 8C9.49998 8.82843 8.82841 9.5 7.99998 9.5C7.17156 9.5 6.49998 8.82843 6.49998 8C6.49998 7.17157 7.17156 6.5 7.99998 6.5C8.82841 6.5 9.49998 7.17157 9.49998 8ZM11 8C11 9.65685 9.65684 11 7.99998 11C6.34313 11 4.99998 9.65685 4.99998 8C4.99998 6.34315 6.34313 5 7.99998 5C9.65684 5 11 6.34315 11 8Z" fill="currentColor"></path></svg>';
            gear.addEventListener('click', function () {
                openPageSettings(root);
            });
            root.appendChild(gear);
            log('Botón de settings agregado dinámicamente');
        }

        // Observar cambios en el documento
        globalObserver.observe(document.body, {
            childList: true,
            subtree: true
        });

        // También verificar periódicamente si falta el botón (como fallback)
        setInterval(function () {
            qsAll('.glory-split').forEach(function (root) {
                if (!root.querySelector('.gbn-floating-settings')) {
                    ensureSettingsButton(root);
                }
            });
        }, 2000); // Verificar cada 2 segundos

    } catch (e) {
        log('Error configurando observer global', e);
    }

    function openPageSettings(root) {
        try {
            var panel = ensurePanel();
            panel.dataset.mode = 'page';
            if (panel.__gbnTitleEl) {
                panel.__gbnTitleEl.textContent = 'Page Settings';
            }
            var body = panel.querySelector('.gbn-body');
            body.innerHTML = '';
            var controls = [
                {key: 'background_color', type: 'color', label: 'Background Color', defaultValue: '#ffffff'}
            ];
            var localConfig = {};
            controls.forEach(function (ctrl) {
                localConfig[ctrl.key] = ctrl.defaultValue;
                body.appendChild(buildInput(ctrl, localConfig));
            });
            panel.dataset.pageId = root.getAttribute('data-gbn-page-id') || '';
            panel.dataset.gbnId = root.getAttribute('data-gbn-id') || '';
            currentRoot = root;
            dirty = false;

            var fd = new FormData();
            fd.append('action', 'gbn_get_page_settings');
            fd.append('nonce', (cfg || {}).nonce || '');
            fd.append('pageId', panel.dataset.pageId || '');
            fetch((cfg || {}).ajaxUrl || '', {method: 'POST', body: fd, credentials: 'same-origin'})
                .then(function (r) {
                    return r && r.json ? r.json() : null;
                })
                .then(function (res) {
                    try {
                        var data = res && res.success && res.data ? res.data : {};
                        qsAll('.gbn-control input, .gbn-control select, .gbn-control textarea', body).forEach(function (el) {
                            var key = el.dataset.key;
                            if (!key) return;
                            if (typeof data[key] !== 'undefined') {
                                el.value = data[key];
                                pendingValues[key] = data[key];
                            }
                        });
                    } catch (e) {}
                })
                .catch(function (e) {
                    log('Error cargando page settings', e);
                });

            panel.classList.add('is-open');
        } catch (e) {
            log('Error openPageSettings', e);
        }
    }
})();
