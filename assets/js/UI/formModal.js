function gloryFormModal() {
    if (window.gloryFormModalInitialized) return;
    window.gloryFormModalInitialized = true;
    const callAjax = async (action, payload) => {
        if (typeof window.gloryAjax === 'function') {
            // Para gloryAjax, pasar un objeto plano o FormData. Evitar URLSearchParams.
            const data = (payload instanceof FormData) ? payload : (payload || {});
            return await window.gloryAjax(action, data);
        }
        const body = new URLSearchParams({ action, ...(payload || {}) });
        const url = (typeof window.ajax_params !== 'undefined' && window.ajax_params.ajax_url) ? window.ajax_params.ajax_url : '/wp-admin/admin-ajax.php';
        const resp = await fetch(url, { method: 'POST', body, headers: { 'Content-Type': 'application/x-www-form-urlencoded' } });
        const text = await resp.text();
        try { return JSON.parse(text); } catch(_) { return { success: true, data: text }; }
    };

    document.addEventListener('gloryModal:open', async (ev) => {
        const { modal, trigger } = ev.detail || {};
        if (!modal) return;

        const form = modal.querySelector('.gloryForm');
        if (!form) return;

        const btnSubmit = form.querySelector('.dataSubir');
        const titulo = modal.querySelector('h2');

        const dispatch = (name, extra = {}) => document.dispatchEvent(new CustomEvent(name, { detail: { modal, form, trigger, ...extra } }));

        // -------- Helpers y configuración declarativa (agnóstico) --------
        const namesToEnableSubmit = (form.dataset.fmSubmitHabilitarCuando || '')
            .split(',').map(s => s.trim()).filter(Boolean);

        const cssEscape = (s) => s.replace(/"/g, '\\"');
        const getFieldByName = (name) => form.querySelector(`[name="${cssEscape(name)}"]`);
        const getValueFor = (el) => {
            if (!el) return '';
            const tag = el.tagName.toLowerCase();
            if (tag === 'select') return el.value || '';
            if (tag === 'textarea') return el.value || '';
            if (tag === 'input') {
                if (['checkbox'].includes(el.type)) return el.checked ? '1' : '';
                if (['radio'].includes(el.type)) {
                    const checked = form.querySelector(`input[type="radio"][name="${cssEscape(el.name)}"]:checked`);
                    return checked ? (checked.value || '1') : '';
                }
                return el.value || '';
            }
            return '';
        };
        const isFilled = (name) => {
            const el = getFieldByName(name);
            return !!getValueFor(el);
        };
        const updateSubmitState = () => {
            if (!btnSubmit || namesToEnableSubmit.length === 0) return;
            const ok = namesToEnableSubmit.every(isFilled);
            btnSubmit.disabled = !ok;
        };
        const attachSubmitWatcher = () => {
            if (!btnSubmit || namesToEnableSubmit.length === 0) return;
            form.addEventListener('input', updateSubmitState);
            form.addEventListener('change', updateSubmitState);
            updateSubmitState();
        };

        // Select All para grupos de servicios
        const wireServiciosSelectAll = () => {
            const container = form.querySelector('.services-field');
            if (!container) return;
            const checkboxes = container.querySelectorAll('input[type="checkbox"][name="services[]"]');
            if (!checkboxes.length) return;
            const chkAll = Array.from(checkboxes).find(ch => ch.value === 'all');
            const others = Array.from(checkboxes).filter(ch => ch.value !== 'all');
            if (!chkAll) return;

            const syncAllState = () => {
                const allChecked = others.length > 0 && others.every(ch => ch.checked);
                chkAll.checked = allChecked;
            };
            chkAll.addEventListener('change', () => {
                const state = chkAll.checked;
                others.forEach(ch => { ch.checked = state; });
            });
            others.forEach(ch => ch.addEventListener('change', () => {
                if (!ch.checked && chkAll.checked) chkAll.checked = false;
                else syncAllState();
            }));
            // Inicial: si el servidor indica bandera de "todos", marcar todos
            const termAll = form.querySelector('input[name="term_id"][value]');
            if (termAll && termAll.value) {
                // No podemos leer meta aquí, pero al entrar en edición cargamos ids, y si equivalen a todos, quedará marcado por sync
                syncAllState();
            } else {
                syncAllState();
            }
        };

        // Carga de opciones para <select> declarados
        const parseOptionsFromResponse = (resp) => {
            const d = (resp && resp.data) ? resp.data : resp;
            let src = d && (d.options || d);
            if (!src) return [];
            if (Array.isArray(src)) {
                // [{value, text}] o ['a','b']
                return src.map(item => (
                    typeof item === 'object' && item !== null
                        ? { value: String(item.value), text: String(item.text ?? item.label ?? item.value) }
                        : { value: String(item), text: String(item) }
                ));
            }
            if (typeof src === 'object') {
                // {valor: texto}
                return Object.entries(src).map(([value, text]) => ({ value: String(value), text: String(text) }));
            }
            return [];
        };
        const repopulateSelect = (selectEl, options, placeholder) => {
            const previous = selectEl.value;
            selectEl.innerHTML = '';
            if (placeholder) {
                const opt = document.createElement('option');
                opt.value = '';
                opt.textContent = placeholder;
                selectEl.appendChild(opt);
            }
            options.forEach(({ value, text }) => {
                const opt = document.createElement('option');
                opt.value = value;
                opt.textContent = text;
                selectEl.appendChild(opt);
            });
            if (selectEl.dataset.fmSelectedValue) {
                selectEl.value = String(selectEl.dataset.fmSelectedValue);
            } else if (previous) {
                selectEl.value = previous;
            }
        };
        const wireOptionsSelect = (selectEl) => {
            const action = selectEl.dataset.fmAccionOpciones;
            const depends = (selectEl.dataset.fmDepende || '').split(',').map(s => s.trim()).filter(Boolean);
            const placeholderDisabled = selectEl.dataset.fmPlaceholderDeshabilitado || '';
            if (!action || depends.length === 0) return;

            const tryLoad = async () => {
                // Verificar dependencias llenas
                const payload = {};
                for (const dep of depends) {
                    const el = getFieldByName(dep);
                    const val = getValueFor(el);
                    if (!val) {
                        // deshabilitar
                        selectEl.disabled = true;
                        if (placeholderDisabled) repopulateSelect(selectEl, [], placeholderDisabled);
                        return;
                    }
                    payload[dep] = val;
                }
                // cargar opciones
                const resp = await callAjax(action, payload);
                const options = parseOptionsFromResponse(resp);
                repopulateSelect(selectEl, options, '');
                selectEl.disabled = options.length === 0;
                dispatch('gloryFormModal:optionsLoaded', { select: selectEl, options, payload });
                updateSubmitState();
            };

            // Observadores en dependencias
            depends.forEach(dep => {
                const el = getFieldByName(dep);
                if (el) el.addEventListener('change', tryLoad);
            });

            // Intento inmediato
            tryLoad();
        };

        // CREATE MODE
        if (trigger && trigger.dataset.formMode === 'create') {
            dispatch('gloryFormModal:beforeCreate');
            const inputs = form.querySelectorAll('input, textarea, select');
            inputs.forEach(el => {
                const tag = el.tagName.toLowerCase();
                if (tag === 'input') {
                    if (['text','number','date','email','tel','range','hidden'].includes(el.type)) el.value = '';
                    if (['checkbox','radio'].includes(el.type)) el.checked = false;
                } else if (tag === 'textarea') {
                    el.value = '';
                } else if (tag === 'select') {
                    if (!el.multiple) el.selectedIndex = 0;
                }
            });
            form.removeAttribute('data-object-id');
            if (btnSubmit) {
                if (trigger.dataset.submitText) btnSubmit.textContent = trigger.dataset.submitText;
                if (trigger.dataset.submitAction) btnSubmit.dataset.accion = trigger.dataset.submitAction;
            }
            if (titulo) {
                const tituloCreate = trigger.dataset.modalTitleCreate || trigger.dataset.modalTitle;
                if (tituloCreate) titulo.textContent = tituloCreate;
            }
            dispatch('gloryFormModal:afterCreate');
            // Enlazar selects declarativos (agnóstico)
            form.querySelectorAll('select[data-fm-accion-opciones][data-fm-depende]').forEach(wireOptionsSelect);
            wireServiciosSelectAll();
            attachSubmitWatcher();
            return;
        }

        // EDIT MODE
        const modo = trigger && trigger.dataset.formMode;
        const fetchAction = trigger && trigger.dataset.fetchAction;
        const objectId = trigger && trigger.dataset.objectId;
        if (modo === 'edit' && fetchAction && objectId) {
            // Ajustar el título al entrar en modo edición
            if (titulo) {
                const tituloEdit = trigger.dataset.modalTitleEdit || trigger.dataset.modalTitle;
                if (tituloEdit) titulo.textContent = tituloEdit;
            }
            dispatch('gloryFormModal:beforeEdit', { objectId });
            try {
                const json = await callAjax(fetchAction, { id: objectId });
                if (!json || !json.success || !json.data) {
                    alert((json && json.data && json.data.mensaje) ? json.data.mensaje : 'No se pudo cargar la información.');
                    return;
                }
                const d = json.data;
                const campos = form.querySelectorAll('input[name], textarea[name], select[name]');
                campos.forEach(el => {
                    const name = el.name;
                    if (typeof d[name] === 'undefined') return;
                    const val = d[name];

                    if (el.classList.contains('glory-image-id')) {
                        const uploader = el.closest('.glory-image-uploader');
                        if (uploader) {
                            const preview = uploader.querySelector('.image-preview');
                            const removeBtn = uploader.querySelector('.glory-remove-image-button');
                            // Asumimos que `val` es un objeto {id, url} o solo un ID.
                            const hasValue = val && (val.id || (typeof val === 'string' && val));
                            const imageId = hasValue ? (val.id || val) : '';
                            const imageUrl = hasValue ? val.url : '';

                            el.value = imageId;

                            if (preview) {
                                if (imageId && imageUrl) {
                                    preview.innerHTML = `<img src="${imageUrl}" alt="Previsualización">`;
                                } else {
                                    const placeholder = preview.dataset.placeholder || '';
                                    preview.innerHTML = `<span class="image-preview-placeholder">${placeholder}</span>`;
                                }
                            }
                            if (removeBtn) {
                                removeBtn.style.display = imageId ? '' : 'none';
                            }
                        }
                    } else if (el.type === 'checkbox' && el.name.endsWith('[]') && Array.isArray(val)) {
                        // Para grupos de checkboxes, p.ej. name="servicios[]"
                        el.checked = val.map(String).includes(el.value);
                    } else if (el.tagName.toLowerCase() === 'select') {
                        el.value = String(val);
                        // Si el select tiene carga declarativa, recordar el valor para re-seleccionarlo tras poblar
                        if (el.dataset.fmAccionOpciones) el.dataset.fmSelectedValue = String(val);
                    } else if (el.type === 'checkbox') {
                        el.checked = !!val && val !== '0';
                    } else if (el.type === 'radio') {
                        if (el.value === String(val)) el.checked = true;
                    } else {
                        el.value = String(val ?? '');
                    }
                });
                // Si el backend indica que son todos, marcar el checkbox "all"
                const chkAll = form.querySelector('.services-field input[type="checkbox"][name="services[]"][value="all"]');
                if (chkAll && d['services_all'] === '1') {
                    chkAll.checked = true;
                    const others = form.querySelectorAll('.services-field input[type="checkbox"][name="services[]"]');
                    others.forEach(ch => { if (ch.value !== 'all') ch.checked = true; });
                }
                form.dataset.objectId = objectId;
                if (btnSubmit) {
                    if (trigger.dataset.submitText) btnSubmit.textContent = trigger.dataset.submitText;
                    if (trigger.dataset.submitAction) btnSubmit.dataset.accion = trigger.dataset.submitAction;
                }
                dispatch('gloryFormModal:afterEdit', { objectId, data: d });

                // Enlazar selects declarativos (agnóstico) y cargar opciones con dependencias actuales
                form.querySelectorAll('select[data-fm-accion-opciones][data-fm-depende]').forEach(wireOptionsSelect);
                wireServiciosSelectAll();
                attachSubmitWatcher();
            } catch (e) {
                console.error('formModal: error cargando datos', e);
                alert('Error de red al cargar los datos.');
            }
        }
    });
}

document.addEventListener('gloryRecarga', gloryFormModal);
document.addEventListener('DOMContentLoaded', gloryFormModal);


