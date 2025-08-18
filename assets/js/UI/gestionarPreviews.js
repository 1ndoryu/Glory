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
            elementoPreview.innerHTML = '';
            const img = document.createElement('img');
            img.src = e.target.result;
            img.style.width = '100%';
            img.style.height = '100%';
            img.style.objectFit = 'contain';
            elementoPreview.appendChild(img);
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
     * Gestiona el clic para abrir el selector de archivos, aplicando filtros.
     * @param {Event} evento
     */
    function alHacerClick(evento) {
        const botonPreview = evento.target.closest('[class*="botonPreview"]');
        const zonaInteractiva = evento.target.closest('.preview, .previewImagen, .previewAudio, .previewFile');
        const contenedorPrincipal = evento.target.closest('.previewContenedor');

        let inputADisparar = null;
        let elementoDeReferencia = null; // Botón o área que define el tipo de archivo

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
            inputADisparar.click();
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
}


document.addEventListener('gloryRecarga', gestionarPreviews);
