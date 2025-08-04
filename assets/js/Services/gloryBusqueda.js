/**
 * Gestiona todas las funcionalidades de búsqueda dinámica en el sitio.
 * Se inicializa en el evento 'gloryRecarga' y se asocia a los inputs
 * con la clase '.busqueda'.
 */
function gloryBusqueda() {
    const inputsBusqueda = document.querySelectorAll('.busqueda');
    let debounceTimeout;

    /**
     * Maneja el evento 'input' para un campo de búsqueda específico.
     * @param {Event} e - El evento del input.
     */
    function manejarInput(e) {
        const input = e.target;
        const texto = input.value.trim();

        const config = {
            tipos: input.dataset.tipos,
            cantidad: input.dataset.cantidad || 2,
            target: input.dataset.target,
            renderer: input.dataset.renderer,
            overlay: input.dataset.overlay === 'true' || false,
            modal: input.dataset.modal || '',
            modalRelative: input.dataset.modalRelative === 'true' || false,
            inputEl: input
        };

        const contenedorResultados = document.querySelector(config.target);

        if (!config.tipos || !config.target || !config.renderer || !contenedorResultados) {
            console.error('El input de búsqueda no tiene la configuración completa (data-tipos, data-target, data-renderer).', input);
            return;
        }

        clearTimeout(debounceTimeout);

        if (texto.length > 2) {
            // Si no se usa modal, se puede mostrar un fondo opcional inmediatamente.
            if (!config.modal && config.overlay && typeof window.mostrarFondo === 'function') {
                window.mostrarFondo();
            }
            // Hacemos visible el contenedor específico de resultados
            contenedorResultados.style.display = 'flex';

            debounceTimeout = setTimeout(() => {
                ejecutarBusqueda(texto, config, contenedorResultados);
            }, 350);
        } else {
            // Si el texto es muy corto o se borra, ocultamos todo.
            // Si había un modal abierto, lo cerramos.
            if (config.modal) {
                const modalEl = document.getElementById(config.modal);
                if (modalEl) {
                    modalEl.style.display = 'none';
                }
                if (typeof window.ocultarFondo === 'function') {
                    window.ocultarFondo();
                }
            } else if (config.overlay && typeof window.ocultarFondo === 'function') {
                window.ocultarFondo();
            }
            // Limpiamos y ocultamos contenedor por si acaso.
            contenedorResultados.innerHTML = '';
            contenedorResultados.style.display = 'none';
        }
    }

    /**
     * Ejecuta la llamada AJAX y renderiza el HTML recibido.
     * @param {string} texto - El término de búsqueda.
     * @param {object} config - La configuración del input.
     * @param {HTMLElement} contenedor - El elemento donde se mostrarán los resultados.
     */
    async function ejecutarBusqueda(texto, config, contenedor) {
        // console.log(`Ejecutando búsqueda para: "${texto}"`); // Log opcional para depuración
        const datosParaEnviar = {
            texto: texto,
            tipos: config.tipos,
            cantidad: config.cantidad,
            renderer: config.renderer
        };

        try {
            const respuesta = await gloryAjax('busquedaAjax', datosParaEnviar);

            if (respuesta && respuesta.success && respuesta.data && typeof respuesta.data.html !== 'undefined') {
                contenedor.innerHTML = respuesta.data.html;
            } else {
                contenedor.innerHTML = '<div class="resultado-item">No se encontraron resultados.</div>';
            }

            // Si se especificó un modal, lo abrimos tras recibir resultados
            if (config.modal) {
                const modalEl = document.getElementById(config.modal);
                if (modalEl) {
                    // Posicionamiento relativo si se solicitó
                    if (config.modalRelative && config.inputEl) {
                        const rect = config.inputEl.getBoundingClientRect();
                        modalEl.style.position = 'fixed';
                        modalEl.style.top = `${rect.bottom + 4}px`;
                        modalEl.style.left = `${rect.left}px`;
                        modalEl.style.transform = 'none';
                    } else {
                        // Restaurar a estilo centrado por defecto
                        modalEl.style.position = '';
                        modalEl.style.top = '';
                        modalEl.style.left = '';
                        modalEl.style.transform = '';
                    }

                    modalEl.style.zIndex = '1001';
                    modalEl.style.display = 'flex';
                    if (typeof window.mostrarFondo === 'function') {
                        window.mostrarFondo();
                    }
                }
            }
        } catch (error) {
            console.error('Error catastrófico en la búsqueda AJAX:', error);
            contenedor.innerHTML = '<div class="resultado-item">Ocurrió un error.</div>';
        }
    }

    inputsBusqueda.forEach(input => {
        // Aseguramos que no haya listeners duplicados en recargas.
        input.removeEventListener('input', manejarInput);
        input.addEventListener('input', manejarInput);
    });
}

document.addEventListener('gloryRecarga', gloryBusqueda);
