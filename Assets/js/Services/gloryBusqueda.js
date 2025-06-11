// @tarea-pendiente Jules: Realizar una revisión más exhaustiva de este archivo JavaScript en una tarea futura para optimización y refactorización avanzada.
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
            renderer: input.dataset.renderer
        };

        const contenedorResultados = document.querySelector(config.target);

        if (!config.tipos || !config.target || !config.renderer || !contenedorResultados) {
            console.error('El input de búsqueda no tiene la configuración completa (data-tipos, data-target, data-renderer).', input);
            return;
        }

        clearTimeout(debounceTimeout);

        if (texto.length > 2) {
            // Mostramos el fondo y preparamos para los resultados.
            if (typeof window.mostrarFondo === 'function') {
                window.mostrarFondo();
            }
            // Hacemos visible el contenedor específico de resultados
            contenedorResultados.style.display = 'flex';

            debounceTimeout = setTimeout(() => {
                ejecutarBusqueda(texto, config, contenedorResultados);
            }, 350);
        } else {
            // Si el texto es muy corto o se borra, ocultamos todo.
            if (typeof window.ocultarFondo === 'function') {
                window.ocultarFondo();
            }
            // Adicionalmente, limpiamos y ocultamos el contenedor por si acaso.
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
