/**
 * Gestiona la previsualización interactiva de archivos (imágenes, audio, etc.)
 * para inputs de tipo "file", soportando arrastrar y soltar (drag & drop) y
 * selección por clic.
 *
 * --- ESTRUCTURA HTML REQUERIDA ---
 * Se necesita un contenedor principal con la clase `.previewContenedor`. Dentro de él,
 * deben coexistir el input de archivo y el/los elemento(s) de previsualización.
 *
 * <div class="previewContenedor">
 * <input type="file" name="mi_archivo" style="display: none;">
 *
 * <div class="previewImagen oculto">
 * </div>
 *
 * <button type="button" class="botonPreviewImagen" data-extrapreview=".selector">Seleccionar Imagen</button>
 * </div>
 *
 * --- CLASES DE PREVIEW Y BOTONES ---
 * El script identifica los elementos por sus clases:
 * - Previews: `.previewImagen`, `.previewAudio`, `.previewFile`. Usa `.oculto` para ocultarlos inicialmente.
 * - Botones: `.botonPreviewImagen`, `.botonPreviewAudio`, `.botonPreviewFile`. Un clic en ellos activa el input.
 * - Genérico: `.preview` puede usarse como fallback para imágenes.
 *
 * --- FUNCIONAMIENTO AUTOMÁTICO ---
 * 1. Arrastrar y Soltar: Al arrastrar un archivo sobre `.previewContenedor`, el área
 * de preview correspondiente al tipo de archivo se hará visible (quitando `.oculto`)
 * y al soltar, se mostrará la previsualización.
 * 2. Clic: Un clic en un botón (`.botonPreview...`) o en un área de preview ya visible
 * abrirá el selector de archivos, filtrando por el tipo de archivo (ej. `image/*`).
 *
 * --- ATRIBUTOS DE CONFIGURACIÓN (Opcionales) ---
 * - data-uploadclick="true": En `.previewContenedor`, permite que un clic en cualquier
 * parte del contenedor abra el selector de archivos.
 * - data-preview-id="mi-id" y data-preview-for="mi-id": Permiten vincular manualmente
 * un input y su preview si no están en la misma jerarquía `.previewContenedor`.
 * - data-extrapreview=".selector": Muestra un elemento extra (usando un selector CSS)
 * durante la operación de arrastre o al hacer clic en un botón que lo contenga.
 */
function gestionarPreviews() {
    // Logging controlado por window.gloryDebug
    const gloryLog = (...args) => { if (typeof window !== 'undefined' && window.gloryDebug) console.log(...args); };
    gloryLog('✨ [gestionarPreviews] Función gestionarPreviews() inicializada.');

    // Evitar registrar múltiples veces los mismos listeners en recargas sucesivas
    if (window.__gloryPreviewsHandlersBound) {
        gloryLog('✨ [gestionarPreviews] Listeners ya estaban vinculados. Abortando nueva vinculación.');
        return;
    }

    /**
     * Muestra la previsualización de un archivo de imagen.
     */
    function mostrarImagen(archivo, elementoPreview) {
        if (!archivo || !archivo.type.startsWith('image/')) {
            console.warn('El archivo no es una imagen válida.');
            elementoPreview.innerHTML = '<span class="preview-text">Archivo no válido</span>';
            return;
        }
        const lector = new FileReader();
        lector.onload = function(e) {
            // Guardar contenido original una sola vez para poder restaurarlo al eliminar
            if (!elementoPreview.dataset.__original_html_saved) {
                elementoPreview.dataset.__original_html_saved = '1';
                elementoPreview.dataset.__original_html = elementoPreview.innerHTML;
            }
            // Limpiar contenido previo si no es un placeholder, y asegurar que el placeholder esté oculto
            elementoPreview.querySelectorAll('img, .preview-text').forEach(el => el.remove());
            const placeholderSpan = elementoPreview.querySelector('.image-preview-placeholder');
            if (placeholderSpan) placeholderSpan.classList.add('oculto');

            const img = document.createElement('img');
            img.src = e.target.result;
            img.style.width = '100%';
            img.style.height = '100%';
            img.style.objectFit = 'contain';
            elementoPreview.appendChild(img);

            // Asegurar que el botón de eliminar esté visible (crear si falta)
            let btn = elementoPreview.querySelector('.previewRemover');
            const svgClose = '<svg data-testid="geist-icon" height="16" stroke-linejoin="round" style="color:currentColor" viewBox="0 0 16 16" width="16"><path fill-rule="evenodd" clip-rule="evenodd" d="M12.4697 13.5303L13 14.0607L14.0607 13L13.5303 12.4697L9.06065 7.99999L13.5303 3.53032L14.0607 2.99999L13 1.93933L12.4697 2.46966L7.99999 6.93933L3.53032 2.46966L2.99999 1.93933L1.93933 2.99999L2.46966 3.53032L6.93933 7.99999L2.46966 12.4697L1.93933 13L2.99999 14.0607L3.53032 13.5303L7.99999 9.06065L12.4697 13.5303Z" fill="currentColor"></path></svg>';
            if (!btn) {
                try {
                    btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'previewRemover';
                    btn.setAttribute('aria-label', 'Eliminar imagen');
                    btn.innerHTML = svgClose;
                    elementoPreview.appendChild(btn);
                } catch (e) { btn = null; }
            }
            if (btn) {
                btn.classList.remove('oculto');
                if (!btn.innerHTML || btn.innerHTML.trim() === '&times;') btn.innerHTML = svgClose;
            }
        };
        lector.readAsDataURL(archivo);
    }

    /**
     * Busca los componentes asociados (input y preview) con una lógica híbrida.
     * @param {HTMLElement} origen - El elemento que inició la acción.
     * @param {File|null} archivo - Opcional. El archivo para determinar el tipo de preview a buscar.
     * @returns {{input: HTMLElement, preview: HTMLElement}|null}
     */
    function obtenerComponentes(origen, archivo = null) {
        const previewPorId = origen.closest('[data-preview-id]');
        if (previewPorId) {
            const previewId = previewPorId.dataset.previewId;
            const contenedorBusqueda = origen.closest('.previewContenedor') || document.body;
            const inputPorFor = contenedorBusqueda.querySelector(`input[type="file"][data-preview-for="${previewId}"]`);
            if (inputPorFor) {
                return { input: inputPorFor, preview: previewPorId };
            }
        }
        if (origen.matches('input[type="file"][data-preview-for]')) {
            const previewId = origen.dataset.previewFor;
            const contenedorBusqueda = origen.closest('.previewContenedor') || document.body;
            const previewEncontrado = contenedorBusqueda.querySelector(`[data-preview-id="${previewId}"]`);
            if (previewEncontrado) {
                return { input: origen, preview: previewEncontrado };
            }
        }

        const contenedor = origen.closest('.previewContenedor') || origen.parentElement;
        if (!contenedor) return null;

        let preview = null;
        if (archivo) {
            if (archivo.type.startsWith('image/')) {
                preview = contenedor.querySelector('.previewImagen') || contenedor.querySelector('.preview');
            } else if (archivo.type.startsWith('audio/')) {
                preview = contenedor.querySelector('.previewAudio');
            } else {
                preview = contenedor.querySelector('.previewFile');
            }
        } else {
            preview = origen.closest('.preview, .previewImagen, .previewAudio, .previewFile') ||
                      contenedor.querySelector('.preview, .previewImagen, .previewAudio, .previewFile');
        }

        const input = contenedor.querySelector('input[type="file"]');
        return (input && preview) ? { input, preview } : null;
    }

    /**
     * Muestra una imagen directamente desde una URL.
     * Similar a `mostrarImagen` pero para URLs preexistentes.
     * @param {string} imageUrl - La URL de la imagen a mostrar.
     * @param {HTMLElement} elementoPreview - El elemento donde se mostrará la preview.
     * @param {string|number} [imageId=''] - Opcional, el ID de la imagen si está disponible.
     */
    function mostrarImagenDesdeUrl(imageUrl, elementoPreview, imageId = '') {
        if (!imageUrl) {
            console.warn('✨ [mostrarImagenDesdeUrl] No se proporcionó URL para mostrar la imagen.');
            return;
        }
        gloryLog('✨ [mostrarImagenDesdeUrl] Llamada con imageUrl:', imageUrl, 'imageId:', imageId, 'elementoPreview:', elementoPreview.outerHTML);

        // Limpiar contenido previo si no es un placeholder, y asegurar que el placeholder esté oculto
        elementoPreview.querySelectorAll('img, .preview-text').forEach(el => el.remove());
        const placeholderSpan = elementoPreview.querySelector('.image-preview-placeholder');
        if (placeholderSpan) placeholderSpan.classList.add('oculto');

        let img = elementoPreview.querySelector('img');
        if (!img) {
            img = document.createElement('img');
            elementoPreview.appendChild(img);
        }
        img.src = imageUrl;
        img.style.width = '100%';
        img.style.height = '100%';
        img.style.objectFit = 'contain';
        if (imageId) img.dataset.imageId = imageId; // Guardar el ID si existe

        // Asegurarse de que el botón de eliminar esté visible (crear si falta)
        let btn = elementoPreview.querySelector('.previewRemover');
        const svgClose = '<svg data-testid="geist-icon" height="16" stroke-linejoin="round" style="color:currentColor" viewBox="0 0 16 16" width="16"><path fill-rule="evenodd" clip-rule="evenodd" d="M12.4697 13.5303L13 14.0607L14.0607 13L13.5303 12.4697L9.06065 7.99999L13.5303 3.53032L14.0607 2.99999L13 1.93933L12.4697 2.46966L7.99999 6.93933L3.53032 2.46966L2.99999 1.93933L1.93933 2.99999L2.46966 3.53032L6.93933 7.99999L2.46966 12.4697L1.93933 13L2.99999 14.0607L3.53032 13.5303L7.99999 9.06065L12.4697 13.5303Z" fill="currentColor"></path></svg>';
        if (!btn) {
            try {
                btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'previewRemover';
                btn.setAttribute('aria-label', 'Eliminar imagen');
                btn.innerHTML = svgClose;
                elementoPreview.appendChild(btn);
            } catch (e) { btn = null; }
        }
        if (btn) {
            btn.classList.remove('oculto');
            if (!btn.innerHTML || btn.innerHTML.trim() === '&times;') btn.innerHTML = svgClose;
        }
        const currentPlaceholderSpan = elementoPreview.querySelector('.image-preview-placeholder');
        const currentBtn = elementoPreview.querySelector('.previewRemover');
        gloryLog('✨ [mostrarImagenDesdeUrl] Placeholder.classList después de ocultar:', currentPlaceholderSpan?.classList.toString(), 'btn.classList después de mostrar:', currentBtn?.classList.toString());

        gloryLog('✨ [mostrarImagenDesdeUrl] IMG creado/actualizado. src:', img?.src, 'display:', img?.style.display, 'visibility:', img?.style.visibility, 'opacity:', img?.style.opacity, 'width:', img?.offsetWidth, 'height:', img?.offsetHeight);
        gloryLog('✨ [mostrarImagenDesdeUrl] elementoPreview.outerHTML al final:', elementoPreview.outerHTML);

        elementoPreview.classList.remove('oculto');
        const contenedor = elementoPreview.closest('.previewContenedor');
        if (contenedor) contenedor.classList.remove('oculto');
    }

    /**
     * Gestiona el clic para abrir el selector de archivos, aplicando filtros.
     * @param {Event} evento
     */
    function alHacerClick(evento) {
        const botonPreview = evento.target.closest('[class*="botonPreview"]');
        const zonaInteractiva = evento.target.closest('.preview, .previewImagen, .previewAudio, .previewFile');
        const contenedorPrincipal = evento.target.closest('.previewContenedor');
        const eliminarBtn = evento.target.closest('.previewRemover');

        let inputADisparar = null;
        let elementoDeReferencia = null; // Botón o área que define el tipo de archivo

        // Si se pulsó el botón de eliminar dentro de la preview
        if (eliminarBtn) {
            evento.preventDefault();
            const previewElem = eliminarBtn.closest('.previewImagen, .preview');
            if (!previewElem) return;
            const cont = previewElem.closest('.previewContenedor');
            // limpiar imagen mostrada y los campos ocultos
            previewElem.querySelectorAll('img').forEach(i => i.remove());

            // Mostrar el placeholder y ocultar el botón de eliminar
            let placeholderSpan = previewElem.querySelector('.image-preview-placeholder');
            // Si no existe o está vacío, intentar restaurar desde el HTML original guardado o insertar uno por defecto
            if (!placeholderSpan || !placeholderSpan.textContent || placeholderSpan.textContent.trim() === '') {
                let restoredHtml = '';
                try {
                    const tmp = document.createElement('div');
                    tmp.innerHTML = previewElem.dataset.__original_html || '';
                    const p = tmp.querySelector('.image-preview-placeholder');
                    if (p && p.textContent && p.textContent.trim()) {
                        restoredHtml = `<span class="image-preview-placeholder">${p.textContent.trim()}</span>`;
                    }
                } catch(_) {}
                if (!restoredHtml) {
                    restoredHtml = '<span class="image-preview-placeholder">Haz clic para subir una imagen</span>';
                }
                if (!placeholderSpan) {
                    previewElem.insertAdjacentHTML('afterbegin', restoredHtml);
                    placeholderSpan = previewElem.querySelector('.image-preview-placeholder');
                } else {
                    placeholderSpan.innerHTML = restoredHtml.replace(/^[^>]*>|<[^>]*>$/g, '');
                }
            }
            if (placeholderSpan) placeholderSpan.classList.remove('oculto');
            eliminarBtn.classList.add('oculto');

            gloryLog('🗑️ [gestionarPreviews][alHacerClick] Botón eliminar clicado. previewElem.outerHTML antes de eliminación:', previewElem.outerHTML);
            gloryLog('🗑️ [gestionarPreviews][alHacerClick] Placeholder.classList después de mostrar:', placeholderSpan?.classList.toString(), 'eliminarBtn.classList después de ocultar:', eliminarBtn?.classList.toString());

            if (cont) {
                const hiddenId = cont.querySelector('.glory-image-id');
                if (hiddenId) hiddenId.value = '';
                const hiddenUrl = cont.querySelector('input[name$="_url"]');
                if (hiddenUrl) hiddenUrl.value = '';
                gloryLog('🗑️ [gestionarPreviews][alHacerClick] Valores hidden inputs después de limpiar: glory-image-id:', cont?.querySelector('.glory-image-id')?.value, 'glory-image-url:', cont?.querySelector('input[name$="_url"]')?.value);
                // Añadir el campo oculto para indicar explícitamente la eliminación de la imagen al guardar
                const deleteInputName = (hiddenId ? hiddenId.name : 'image_id') + '_delete';
                let deleteInput = cont.querySelector(`input[name="${deleteInputName}"]`);
                if (!deleteInput) {
                    deleteInput = document.createElement('input');
                    deleteInput.type = 'hidden';
                    deleteInput.name = deleteInputName;
                    cont.appendChild(deleteInput);
                }
                deleteInput.value = '1'; // Establecer a '1' para indicar eliminación

                // Limpiar el input file para permitir volver a elegir el mismo archivo y dispare 'change'
                const inputFile = cont.querySelector('input[type="file"]');
                if (inputFile) {
                    try { inputFile.value = ''; } catch(e) {}
                }
            }
            return;
        }

        if (botonPreview) {
            elementoDeReferencia = botonPreview;
            const contenedor = botonPreview.closest('.previewContenedor');
            if (contenedor) {
                inputADisparar = contenedor.querySelector('input[type="file"]');
            }
        } else if (zonaInteractiva) {
            elementoDeReferencia = zonaInteractiva;
            const componentes = obtenerComponentes(zonaInteractiva, null);
            if (componentes) {
                inputADisparar = componentes.input;
            }
        } else if (contenedorPrincipal && contenedorPrincipal.dataset.uploadclick === 'true') {
            elementoDeReferencia = contenedorPrincipal;
            inputADisparar = contenedorPrincipal.querySelector('input[type="file"]');
        } else {
            return; // No hay un objetivo interactivo válido
        }

        if (inputADisparar && elementoDeReferencia) {
            let acceptType = '';
            // Se priorizan los tipos más específicos primero
            if (elementoDeReferencia.matches('[class*="botonPreviewAudio"]') || elementoDeReferencia.matches('.previewAudio')) {
                acceptType = 'audio/*';
            } else if (elementoDeReferencia.matches('[class*="botonPreviewImagen"]') || elementoDeReferencia.matches('.previewImagen') || elementoDeReferencia.matches('.preview')) {
                acceptType = 'image/*';
            }
            // Caso contenedor con data-uploadclick: inferir tipo por contenido si aún no se definió
            if (!acceptType && contenedorPrincipal) {
                if (contenedorPrincipal.querySelector('.previewImagen, .preview')) {
                    acceptType = 'image/*';
                } else if (contenedorPrincipal.querySelector('.previewAudio')) {
                    acceptType = 'audio/*';
                }
            }
            
            // **NUEVO**: Activar extra preview si está definido en el botón o elemento de referencia
            if (elementoDeReferencia.dataset.extrapreview) {
                const modo = elementoDeReferencia.dataset.extrapreviewOn || (contenedorPrincipal?.dataset.extrapreviewOn) || 'drag';
                document.querySelectorAll(elementoDeReferencia.dataset.extrapreview).forEach(el => {
                    el.classList.add('activo');
                    if (modo === 'click' || modo === 'always') {
                        el.classList.remove('oculto');
                    }
                });
            }

            inputADisparar.accept = acceptType;
            // Evitar doble apertura: recordar timestamp y sólo permitir abrir si hace más de 300ms
            const lastOpen = inputADisparar.dataset.__last_open_ts || 0;
            const now = Date.now();
            if (now - lastOpen > 300) {
                inputADisparar.dataset.__last_open_ts = now;
                inputADisparar.click();
            }
        }
    }

    /**
     * Gestiona la previsualización cuando un archivo es seleccionado.
     * @param {Event} evento
     */
    function alCambiarArchivo(evento) {
        if (!evento.target.matches('input[type="file"]')) return;
        const input = evento.target;

        // Limpia el atributo 'accept' después de su uso
        input.accept = '';

        if (!input.files || input.files.length === 0) return;

        const archivo = input.files[0];
        const componentes = obtenerComponentes(input, archivo);

        if (componentes && componentes.preview) {
            const contenedor = componentes.preview.closest('.previewContenedor');
            if (contenedor) {
                // Oculta otras posibles previews en el mismo contenedor
                contenedor.querySelectorAll('.preview, .previewImagen, .previewAudio, .previewFile').forEach(p => {
                    if (p !== componentes.preview) {
                        p.classList.add('oculto');
                    }
                });
            }
            
            // Muestra la preview correcta
            componentes.preview.classList.remove('oculto');
            if(contenedor) contenedor.classList.remove('oculto');


            if (archivo.type.startsWith('image/')) {
                // Limpiar hidden inputs previos (ID / URL) al seleccionar nuevo archivo
                try {
                    const cont = componentes.preview.closest('.previewContenedor');
                    if (cont) {
                        const hiddenId = cont.querySelector('.glory-image-id');
                        if (hiddenId) hiddenId.value = '';
                        const hiddenUrl = cont.querySelector('input[name$="_url"]');
                        if (hiddenUrl) hiddenUrl.value = '';
                        // Si existía la bandera de eliminación, limpiarla
                        try {
                            const deleteInput = cont.querySelector('input[name$="_delete"]');
                            if (deleteInput) deleteInput.value = '';
                        } catch(_) {}
                    }
                } catch (e) {
                    // noop
                }

                mostrarImagen(archivo, componentes.preview);
            } else if (archivo.type.startsWith('audio/')) {
                componentes.preview.innerHTML = `<span><svg width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" d="M12 3v10.55c-.59-.34-1.27-.55-2-.55c-2.21 0-4 1.79-4 4s1.79 4 4 4s4-1.79 4-4V7h4V3h-6Z"/></svg> Audio: ${archivo.name}</span>`;
            } else {
                componentes.preview.innerHTML = `<span><svg width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6Zm-1 13v-3h-2v3H9v-4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v4h-2Zm-1-5a1 1 0 0 1-1-1V5.5L14.5 9H12Z"/></svg> Archivo: ${archivo.name}</span>`;
            }
        }
    }

    function alArrastrarSobre(evento) {
        evento.preventDefault();
        let contenedor = evento.target.closest('.previewContenedor');
        // Permitir activar un contenedor oculto cuando se arrastra sobre un elemento externo
        const activador = evento.target.closest('[data-activapreview]');
        if (!contenedor && activador) {
            const selectorContenedor = activador.dataset.activapreview;
            const contenedorActivado = document.querySelector(selectorContenedor);
            if (contenedorActivado) {
                contenedor = contenedorActivado;
                contenedor.classList.remove('oculto');
            }
        }
        if (!contenedor) return;

        contenedor.classList.add('arrastrando');

        if (evento.dataTransfer?.items?.length > 0) {
            const tipo = evento.dataTransfer.items[0].type;
            let previewTarget;
            if (tipo.startsWith('image/')) {
                previewTarget = contenedor.querySelector('.previewImagen') || contenedor.querySelector('.preview');
            } else if (tipo.startsWith('audio/')) {
                previewTarget = contenedor.querySelector('.previewAudio');
            } else {
                previewTarget = contenedor.querySelector('.previewFile');
            }

            if (previewTarget) {
                previewTarget.classList.remove('oculto');
                previewTarget.classList.add('arrastrando');
            }
        } else {
            const previewDirecto = evento.target.closest('.preview, .previewImagen, .previewAudio, .previewFile');
            if (previewDirecto) {
                previewDirecto.classList.add('arrastrando');
            }
        }

        if (contenedor.dataset.extrapreview) {
            const modo = contenedor.dataset.extrapreviewOn || 'drag';
            document.querySelectorAll(contenedor.dataset.extrapreview).forEach(el => {
                el.classList.add('activo');
                if (modo === 'drag' || modo === 'always') {
                    el.classList.remove('oculto');
                }
            });
        }
    }

    function alDejarDeArrastrar(evento) {
        const zonaDejada = evento.target.closest('.preview, .previewImagen, .previewAudio, .previewFile, .previewContenedor');

        if (zonaDejada && !zonaDejada.contains(evento.relatedTarget)) {
            const contenedor = zonaDejada.closest('.previewContenedor') || zonaDejada;
            contenedor.classList.remove('arrastrando');
            contenedor.querySelectorAll('.arrastrando').forEach(el => el.classList.remove('arrastrando'));

            if (contenedor.dataset.extrapreview) {
                const modo = contenedor.dataset.extrapreviewOn || 'drag';
                document.querySelectorAll(contenedor.dataset.extrapreview).forEach(el => {
                    el.classList.remove('activo');
                    if (modo === 'drag') {
                        el.classList.add('oculto');
                    }
                });
            }
        }
    }

    function alSoltarArchivo(evento) {
        const zonaDrop = evento.target.closest('.preview, .previewImagen, .previewAudio, .previewFile, .previewContenedor');
        if (zonaDrop) {
            evento.preventDefault();
            const contenedor = zonaDrop.closest('.previewContenedor') || zonaDrop;
            contenedor.classList.remove('arrastrando');
            contenedor.querySelectorAll('.arrastrando').forEach(el => el.classList.remove('arrastrando'));

            const archivos = evento.dataTransfer.files;
            if (archivos?.length > 0) {
                const componentes = obtenerComponentes(contenedor, archivos[0]);
                if (componentes?.input) {
                    componentes.input.files = archivos;
                    const eventoChange = new Event('change', { bubbles: true });
                    componentes.input.dispatchEvent(eventoChange);
                }
                // Mostrar extra preview si el modo es 'drop' o 'always'
                if (contenedor.dataset.extrapreview) {
                    const modo = contenedor.dataset.extrapreviewOn || 'drag';
                    if (modo === 'drop' || modo === 'always') {
                        document.querySelectorAll(contenedor.dataset.extrapreview).forEach(el => {
                            el.classList.add('activo');
                            el.classList.remove('oculto');
                        });
                    }
                }
            }
        }
    }

    document.addEventListener('click', alHacerClick);
    document.addEventListener('change', alCambiarArchivo);
    document.addEventListener('dragover', alArrastrarSobre);
    document.addEventListener('dragleave', alDejarDeArrastrar);
    document.addEventListener('drop', alSoltarArchivo);

    // Marcar como vinculados para evitar duplicados en futuras gloryRecarga
    window.__gloryPreviewsHandlersBound = true;

    // Mover el listener del evento personalizado dentro de este scope
    if (window.gloryImageUploaderListenerInitialized) return;
    window.gloryImageUploaderListenerInitialized = true;

    document.addEventListener('gloryImageUploader:showExistingImage', (event) => {
        const { imageUrl, imageId, uploaderContainer } = event.detail;
        const previewElement = uploaderContainer.querySelector('.previewImagen'); // CAMBIADO DE .image-preview
        if (previewElement) {
            gloryLog('✨ [gestionarPreviews] Evento gloryImageUploader:showExistingImage recibido. imageUrl:', imageUrl, 'imageId:', imageId, 'uploaderContainer:', uploaderContainer.outerHTML);
            gloryLog('✨ [gestionarPreviews] previewElement encontrado:', previewElement.outerHTML);
            gloryLog('✨ [gestionarPreviews] previewElement.outerHTML antes de mostrarImagenDesdeUrl:', previewElement.outerHTML);
            mostrarImagenDesdeUrl(imageUrl, previewElement, imageId);
            // Asegurarse de que el hidden input de image_id tenga el valor si lo tenemos
            const hiddenIdInput = uploaderContainer.querySelector('.glory-image-id');
            if (hiddenIdInput) {
                hiddenIdInput.value = imageId;
            }
            // Asegurarse de que el hidden input de image_id_url tenga el valor si lo tenemos
            const hiddenUrlInput = uploaderContainer.querySelector('.glory-image-url');
            if (hiddenUrlInput) {
                hiddenUrlInput.value = imageUrl;
            }
        } else {
            console.warn('✨ [gestionarPreviews] No se encontró previewElement para gloryImageUploader:showExistingImage en', uploaderContainer);
        }
    });
}

document.addEventListener('gloryRecarga', gestionarPreviews);
