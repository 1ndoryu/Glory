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
            callbackShow: input.dataset.callbackShow,
            callbackHide: input.dataset.callbackHide
        };

        const contenedorResultados = document.querySelector(config.target);

        if (!config.tipos || !config.target || !config.renderer || !contenedorResultados) {
            console.error('El input de búsqueda no tiene la configuración completa (data-tipos, data-target, data-renderer).', input);
            return;
        }

        clearTimeout(debounceTimeout);

        if (texto.length > 2) {
            if (config.callbackShow && typeof window[config.callbackShow] === 'function') {
                window[config.callbackShow]();
            }
            
            debounceTimeout = setTimeout(() => {
                ejecutarBusqueda(texto, config, contenedorResultados);
            }, 350);
        } else {
            if (config.callbackHide && typeof window[config.callbackHide] === 'function') {
                window[config.callbackHide]();
            }
            contenedorResultados.innerHTML = '';
        }
    }

    /**
     * Ejecuta la llamada AJAX y renderiza el HTML recibido.
     * @param {string} texto - El término de búsqueda.
     * @param {object} config - La configuración del input.
     * @param {HTMLElement} contenedor - El elemento donde se mostrarán los resultados.
     */
    async function ejecutarBusqueda(texto, config, contenedor) {
        const datosParaEnviar = {
            texto: texto,
            tipos: config.tipos,
            cantidad: config.cantidad,
            renderer: config.renderer // Se envía el nombre del renderer a PHP
        };

        try {
            const respuesta = await gloryAjax('busquedaAjax', datosParaEnviar);

            // La respuesta ahora contiene el HTML pre-renderizado.
            if (respuesta && typeof respuesta.html !== 'undefined') {
                contenedor.innerHTML = respuesta.html;
            } else {
                contenedor.innerHTML = '<div class="resultado-item">Error al cargar resultados.</div>';
            }
        } catch (error) {
            console.error('Error en la búsqueda AJAX:', error);
            contenedor.innerHTML = '<div class="resultado-item">Ocurrió un error.</div>';
        }
    }

    inputsBusqueda.forEach(input => {
        input.removeEventListener('input', manejarInput);
        input.addEventListener('input', manejarInput);
    });
}

document.addEventListener('gloryRecarga', gloryBusqueda);