function gloryFormModal() {
    // Helper de logging controlado por `window.gloryDebug`
    const gloryLog = (...args) => { if (typeof window !== 'undefined' && window.gloryDebug) console.log(...args); };
    gloryLog('⚡️ [formModal] Función gloryFormModal() inicializada.');
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

        const cssEscape = (s) => s.replace(/"/g, '\"');
        const getFieldByName = (name) => form.querySelector(`[name="${cssEscape(name)}"]`);
        
        // Permite que el área de previsualización dentro de `.glory-image-uploader`
        // funcione como disparador del selector de imagen (redirige al botón existente).
        const wireImagePreviewClicks = () => {
            try {
                form.querySelectorAll('.glory-image-uploader .image-preview, .glory-image-uploader .previewImagen, .gloryImageUploader .image-preview, .gloryImageUploader .previewImagen').forEach(preview => {
                    // Evitar múltiples handlers
                    preview.removeEventListener('click', preview.__gloryPreviewHandler);
                    const handler = () => {
                        const uploader = preview.closest('.glory-image-uploader');
                        if (!uploader) return;
                        const btn = uploader.querySelector('.glory-upload-image-button');
                        if (btn) btn.click();
                    };
                    preview.__gloryPreviewHandler = handler;
                    preview.addEventListener('click', handler);
                });
            } catch (e) { /* silencioso */ }
        };
        // Delegación como fallback: manejar clicks en previews incluso si se añaden después
        form.removeEventListener('click', form.__gloryPreviewDelegate);
        const delegateHandler = (ev) => {
            const preview = ev.target.closest('.glory-image-uploader .image-preview, .gloryImageUploader .image-preview, .glory-image-uploader .previewImagen, .gloryImageUploader .previewImagen');
            if (!preview) return;
            const uploader = preview.closest('.glory-image-uploader, .gloryImageUploader');
            if (!uploader) return;
            const btn = uploader.querySelector('.glory-upload-image-button');
            if (btn) btn.click();
        };
        form.__gloryPreviewDelegate = delegateHandler;
        form.addEventListener('click', delegateHandler);

        // Inicializador interno para el media uploader en formularios (fallback cuando
        // no existe el código jQuery específico del panel). Usa la API `wp.media`.
        function ensureRemoveButton(previewElem) {
            try {
                if (!previewElem) return;
                // Si ya existe el botón, no hacemos nada
                if (previewElem.querySelector('.previewRemover')) return;
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'previewRemover oculto';
                btn.setAttribute('aria-label', 'Eliminar imagen');
                btn.innerHTML = '<svg data-testid="geist-icon" height="16" stroke-linejoin="round" style="color:currentColor" viewBox="0 0 16 16" width="16"><path fill-rule="evenodd" clip-rule="evenodd" d="M12.4697 13.5303L13 14.0607L14.0607 13L13.5303 12.4697L9.06065 7.99999L13.5303 3.53032L14.0607 2.99999L13 1.93933L12.4697 2.46966L7.99999 6.93933L3.53032 2.46966L2.99999 1.93933L1.93933 2.99999L2.46966 3.53032L6.93933 7.99999L2.46966 12.4697L1.93933 13L2.99999 14.0607L3.53032 13.5303L7.99999 9.06065L12.4697 13.5303Z" fill="currentColor"></path></svg>';
                previewElem.appendChild(btn);
            } catch (e) { /* silencioso */ }
        }

        // Inicializador interno para el media uploader en formularios (fallback cuando
        // no existe el código jQuery específico del panel). Usa la API `wp.media`.
        let __gloryFormMediaUploader = null;
        const inicializarImageUploaderForForm = () => {
            // Delegación en el formulario
            form.removeEventListener('click', form.__gloryUploaderDelegate);
            const delegado = (ev) => {
                const btn = ev.target.closest('.glory-upload-image-button');
                if (btn) {
                    ev.preventDefault();
                    const uploaderContainer = btn.closest('.glory-image-uploader, .gloryImageUploader');
                    if (!uploaderContainer) return;
                    if (__gloryFormMediaUploader) { __gloryFormMediaUploader.open(); return; }
                    try {
                        __gloryFormMediaUploader = wp.media.frames.file_frame = wp.media({
                            title: 'Seleccionar una Imagen',
                            button: { text: 'Usar esta imagen' },
                            multiple: false
                        });
                        __gloryFormMediaUploader.on('select', function () {
                            const attachment = __gloryFormMediaUploader.state().get('selection').first().toJSON();
                            const previewUrl = (attachment.sizes && attachment.sizes.thumbnail) ? attachment.sizes.thumbnail.url : attachment.url;
                            const hidden = uploaderContainer.querySelector('.glory-image-id');
                            if (hidden) hidden.value = attachment.id;
                            const preview = uploaderContainer.querySelector('.image-preview, .imagePreview, .previewImagen, .preview');
                            if (preview) {
                                preview.innerHTML = '<img src="' + previewUrl + '" alt="Previsualización">';
                                // Asegurar que exista botón remove compatible
                                ensureRemoveButton(preview);
                            }
                            const removeBtn = uploaderContainer.querySelector('.glory-remove-image-button, .previewRemover');
                            if (removeBtn) removeBtn.classList.remove('oculto');
                        });
                        __gloryFormMediaUploader.open();
                    } catch (e) {
                        console.error('wp.media no disponible', e);
                    }
                }

                const removeBtn = ev.target.closest('.glory-remove-image-button, .previewRemover');
                if (removeBtn) {
                    ev.preventDefault();
                    const uploaderContainer = removeBtn.closest('.glory-image-uploader, .gloryImageUploader');
                    if (!uploaderContainer) return;
                    const hidden = uploaderContainer.querySelector('.glory-image-id');
                    if (hidden) hidden.value = ''; // Limpiar el ID de la imagen
                    const preview = uploaderContainer.querySelector('.image-preview, .imagePreview, .previewImagen, .preview');
                    const placeholderText = preview ? (preview.dataset.placeholder || '') : '';
                    if (preview) preview.innerHTML = '<span class="image-preview-placeholder">' + placeholderText + '</span>';
                    removeBtn.classList.add('oculto');

                    // Añadir un campo oculto para indicar explícitamente la eliminación de la imagen al guardar
                    const deleteInputName = hidden.name + '_delete';
                    let deleteInput = form.querySelector(`input[name="${deleteInputName}"]`);
                    if (!deleteInput) {
                        deleteInput = document.createElement('input');
                        deleteInput.type = 'hidden';
                        deleteInput.name = deleteInputName;
                        form.appendChild(deleteInput);
                    }
                    deleteInput.value = '1'; // Establecer a '1' para indicar eliminación
                }
            };
            form.__gloryUploaderDelegate = delegado;
            form.addEventListener('click', delegado);
        };
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
            // Notificar a campos dependientes que el valor (posiblemente restaurado) está listo
            try {
                const evt = new Event('change', { bubbles: true });
                selectEl.dispatchEvent(evt);
            } catch(_) {}
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
                // Si estamos editando un objeto y el select define un parámetro de exclusión, incluirlo
                const excludeParam = selectEl.dataset.fmExcludeParam;
                if (excludeParam && form && form.dataset && form.dataset.objectId) {
                    payload[excludeParam] = form.dataset.objectId;
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
            // Evitar reiniciar el formulario si se está reabriendo el mismo modal (aplicación de UX solicitada)
            const reopenSame = (trigger && trigger.dataset && trigger.dataset.reopenSame) || false;

            // Si es re-apertura del mismo modal (flag en el trigger), NO reiniciamos los valores del formulario
            if (!reopenSame) {
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
                // Asegurar que no quedan indicadores de edición anteriores
                try {
                    // Eliminar cualquier input hidden que indique eliminación previa
                    form.querySelectorAll('input[name$="_delete"]').forEach(i => i.remove());
                } catch (e) {}
                // Limpiar las previews de los uploaders de imagen al crear un nuevo objeto
                try {
                    form.querySelectorAll('.glory-image-uploader, .gloryImageUploader').forEach(uploader => {
                        const hidden = uploader.querySelector('.glory-image-id');
                        if (hidden) hidden.value = '';
                        const preview = uploader.querySelector('.image-preview, .imagePreview, .previewImagen, .preview');
                        if (preview) {
                            const placeholder = preview.dataset.placeholder || '';
                            preview.innerHTML = '<span class="image-preview-placeholder">' + placeholder + '</span>';
                        }
                        const removeBtn = uploader.querySelector('.glory-remove-image-button, .previewRemover');
                        if (removeBtn) removeBtn.classList.add('oculto');
                    });
                } catch (e) { /* silencioso */ }
                if (btnSubmit) {
                    if (trigger.dataset.submitText) btnSubmit.textContent = trigger.dataset.submitText;
                    if (trigger.dataset.submitAction) btnSubmit.dataset.accion = trigger.dataset.submitAction;
                }
                if (titulo) {
                    const tituloCreate = trigger.dataset.modalTitleCreate || trigger.dataset.modalTitle;
                    if (tituloCreate) titulo.textContent = tituloCreate;
                }
                dispatch('gloryFormModal:afterCreate');
            }

            // Asegurar que al abrir en modo CREATE siempre se limpien/restablezcan las previews
            try {
                form.querySelectorAll('.glory-image-uploader, .gloryImageUploader').forEach(uploader => {
                    const hidden = uploader.querySelector('.glory-image-id');
                    if (hidden) hidden.value = '';
                    const hiddenUrl = uploader.querySelector('.glory-image-url');
                    if (hiddenUrl) hiddenUrl.value = '';
                    const preview = uploader.querySelector('.image-preview, .imagePreview, .previewImagen, .preview');
                    if (preview) {
                        const placeholder = preview.dataset.placeholder || 'Haz clic para subir una imagen';
                        // Eliminar cualquier imagen residual
                        preview.querySelectorAll('img, .preview-text').forEach(i => i.remove());
                        preview.innerHTML = '<span class="image-preview-placeholder">' + placeholder + '</span>';
                        preview.classList.remove('oculto');
                    }
                    const removeBtn = uploader.querySelector('.glory-remove-image-button, .previewRemover');
                    if (removeBtn) removeBtn.classList.add('oculto');

                    // Limpiar input file para evitar que permanezca seleccionado
                    const inputFile = uploader.querySelector('input[type="file"]');
                    if (inputFile) {
                        try { inputFile.value = ''; } catch (e) {}
                    }
                });
            } catch (e) { /* silencioso */ }

            // Limpieza extra por si hay inputs ocultos fuera del <form> (p.ej. term_id renderizado fuera)
            try {
                // Limpiar posibles campos de identificación que provoquen ediciones en lugar de creación
                const modalRoot = modal;
                ['term_id', 'id', 'object_id'].forEach(name => {
                    modalRoot.querySelectorAll(`input[name="${name}"]`).forEach(i => { i.value = ''; });
                });
                // Limpiar cualquier input oculto relacionado con imagenes en todo el modal
                modalRoot.querySelectorAll('input.glory-image-id, input[name$="_image_id"], input[name="image_id"]').forEach(i => { i.value = ''; });
                // Eliminar flags de delete previos
                modalRoot.querySelectorAll('input[name$="_delete"]').forEach(i => i.remove());
                // Asegurar que el formulario no tenga data-object-id
                try { form.removeAttribute('data-object-id'); } catch (e) {}
            } catch (e) {}

            // Enlazar selects declarativos (agnóstico)
            form.querySelectorAll('select[data-fm-accion-opciones][data-fm-depende]').forEach(wireOptionsSelect);
            wireServiciosSelectAll();
            attachSubmitWatcher();
            // Vincular clicks en las previews de imagen para abrir el uploader
            wireImagePreviewClicks();
            // Inicializar el uploader de medios para este formulario (usa wp.media)
            inicializarImageUploaderForForm();
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

            // Limpieza previa a cargar datos de edición para evitar reutilizar valores del modal anterior
            try {
                const modalRoot = modal || document;
                // Limpiar uploaders dentro del modal
                modalRoot.querySelectorAll('.glory-image-uploader, .gloryImageUploader').forEach(uploader => {
                    const hidden = uploader.querySelector('.glory-image-id');
                    if (hidden) hidden.value = '';
                    const hiddenUrl = uploader.querySelector('.glory-image-url');
                    if (hiddenUrl) hiddenUrl.value = '';
                    // eliminar imgs residuales
                    const preview = uploader.querySelector('.image-preview, .imagePreview, .previewImagen, .preview');
                    if (preview) {
                        preview.querySelectorAll('img, .preview-text').forEach(i => i.remove());
                        const placeholder = preview.dataset.placeholder || 'Haz clic para subir una imagen';
                        // asegurar placeholder visible
                        while (preview.firstChild) preview.removeChild(preview.firstChild);
                        const spanPh = document.createElement('span');
                        spanPh.className = 'image-preview-placeholder';
                        spanPh.textContent = placeholder;
                        preview.appendChild(spanPh);
                        preview.classList.remove('oculto');
                    }
                    const removeBtn = uploader.querySelector('.glory-remove-image-button, .previewRemover');
                    if (removeBtn) removeBtn.classList.add('oculto');
                    const inputFile = uploader.querySelector('input[type="file"]');
                    if (inputFile) try { inputFile.value = ''; } catch(e) {}
                });
                // Eliminar flags de delete previos
                modalRoot.querySelectorAll('input[name$="_delete"]').forEach(i => i.remove());
            } catch (e) {}

            try {
                gloryLog('⚡️ [formModal] Llamando AJAX con action:', fetchAction, 'y objectId:', objectId);
                const json = await callAjax(fetchAction, { id: objectId });
                gloryLog('⚡️ [formModal] Respuesta AJAX recibida:', json);
                if (!json || !json.success || !json.data) {
                    alert((json && json.data && json.data.mensaje) ? json.data.mensaje : 'No se pudo cargar la información.');
                    return;
                }
                const d = json.data;
                gloryLog('⚡️ [formModal] Datos procesados (d) del AJAX:', d);
                const campos = form.querySelectorAll('input[name], textarea[name], select[name]');
                campos.forEach(el => {
                    const name = el.name;
                    if (typeof d[name] === 'undefined') return;
                    const val = d[name];

                    if (el.classList.contains('glory-image-id')) {
                        const uploader = el.closest('.glory-image-uploader');
                        if (uploader) {
                            const preview = uploader.querySelector('.image-preview, .imagePreview, .previewImagen, .preview'); // Soporta múltiples variantes de clase
                            const removeBtn = uploader.querySelector('.glory-remove-image-button');
                            // Asumimos que `val` es un objeto {id, url} o solo un ID.
                            const hasValue = val && (val.id || val.url || (typeof val === 'string' && val));
                            const imageId = (val && val.id) ? val.id : '';
                            const imageUrl = (val && val.url) ? val.url : '';

                            gloryLog('⚡️ [formModal] Manejando campo glory-image-id. Valor actual (val):', val);
                            gloryLog('⚡️ [formModal] ImageId extraído:', imageId, 'ImageUrl extraído:', imageUrl);

                            // Asegurar que el campo oculto se sincronice (vacío si no hay ID)
                            el.value = imageId || '';

                            gloryLog('⚡️ [formModal] Valor de uploader:', uploader, 'Valor de preview:', preview); // Nuevo log
                            if (preview) {
                                gloryLog('⚡️ [formModal] Evaluando condicion if (imageUrl): ', imageUrl); // Nuevo log

                                // Si el backend proporciona URL, delegamos la renderización a gestionarPreviews
                                if (imageUrl) {
                                    // Emitir un evento para que gestionarPreviews.js se encargue de mostrar la URL
                                    const event = new CustomEvent('gloryImageUploader:showExistingImage', {
                                        bubbles: true,
                                        detail: { imageUrl, imageId, uploaderContainer: uploader }
                                    });
                                    document.dispatchEvent(event);
                                    gloryLog('⚡️ [formModal] CONDICION CUMPLIDA. Emitiendo evento gloryImageUploader:showExistingImage con imageUrl:', imageUrl, 'imageId:', imageId, 'en uploaderContainer:', uploader); // Log modificado

                                // Si hay sólo ID (sin URL), mostramos placeholder salvo que ya haya imagen
                                } else if (imageId) {
                                    const placeholder = preview.dataset.placeholder || 'Haz clic para subir una imagen';
                                    const hasImg = !!preview.querySelector('img');
                                    const hasPlaceholder = !!preview.querySelector('.image-preview-placeholder');
                                    if (!hasImg && !hasPlaceholder) {
                                        preview.innerHTML = `<span class="image-preview-placeholder">${placeholder}</span>`;
                                    }
                                    gloryLog('⚡️ [formModal] Solo ImageId presente (sin URL). Asegurando placeholder si era necesario.', 'imageId:', imageId);

                                // Si NO hay ni URL ni ID, limpiar explícitamente la preview para evitar reutilizar imágenes previas
                                } else {
                                    const placeholder = preview.dataset.placeholder || 'Haz clic para subir una imagen';
                                    // Forzar placeholder y eliminar cualquier <img> residual
                                    preview.querySelectorAll('img, .preview-text').forEach(i => i.remove());
                                    preview.innerHTML = `<span class="image-preview-placeholder">${placeholder}</span>`;
                                    // Asegurar que la preview y su contenedor estén visibles
                                    try {
                                        preview.classList.remove('oculto');
                                        const cont = preview.closest('.previewContenedor');
                                        if (cont) cont.classList.remove('oculto');
                                    } catch (e) {}
                                    gloryLog('⚡️ [formModal] No hay imageId ni imageUrl. Limpiando preview y mostrando placeholder.');
                                }
                            } else {
                                console.error('⚡️ [formModal] ERROR: Elemento preview no encontrado para el uploader.', uploader);
                            }

                            // Actualizar visibilidad/estado del botón eliminar
                            if (removeBtn) {
                                if (imageId || imageUrl) removeBtn.classList.remove('oculto'); else removeBtn.classList.add('oculto');
                            }
                        }
                    } else if (el.type === 'checkbox' && el.name.endsWith('[]') && Array.isArray(val)) {
                        // Para grupos de checkboxes, p.ej. name="servicios[]"
                        el.checked = val.map(String).includes(el.value);
                    } else if (el.tagName.toLowerCase() === 'select') {
                        el.value = String(val);
                        // Si el select tiene carga declarativa, recordar el valor para re-seleccionarlo tras poblar
                        if (el.dataset.fmAccionOpciones) el.dataset.fmSelectedValue = String(val);
                        // Si se solicita mantener el valor actual visible aunque aún no existan opciones, insertarlo temporalmente
                        if (el.dataset.fmKeepCurrent === '1') {
                            const current = String(val ?? '');
                            if (current) {
                                const exists = Array.from(el.options).some(o => o.value === current);
                                if (!exists) {
                                    const opt = document.createElement('option');
                                    opt.value = current;
                                    opt.textContent = current;
                                    el.appendChild(opt);
                                }
                                el.value = current;
                            }
                        }
                    } else if (el.type === 'checkbox') {
                        el.checked = !!val && val !== '0';
                    } else if (el.type === 'radio') {
                        if (el.value === String(val)) el.checked = true;
                    } else {
                        el.value = String(val ?? '');
                    }
                });
                // Lógica específica de campos debe implementarse en el tema
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
                // Vincular clicks en las previews de imagen para abrir el uploader
                wireImagePreviewClicks();
                // Inicializar el uploader de medios para este formulario (usa wp.media)
                inicializarImageUploaderForForm();
            } catch (e) {
                console.error('formModal: error cargando datos', e);
                alert('Error de red al cargar los datos.');
            }
        }
    });
}

document.addEventListener('gloryRecarga', gloryFormModal);
document.addEventListener('DOMContentLoaded', gloryFormModal);


