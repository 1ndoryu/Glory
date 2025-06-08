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

        // Lee la configuración desde los atributos data-*
        const config = {
            tipos: input.dataset.tipos,
            cantidad: input.dataset.cantidad || 2,
            target: input.dataset.target,
            renderer: input.dataset.renderer,
            callbackShow: input.dataset.callbackShow,
            callbackHide: input.dataset.callbackHide
        };

        const contenedorResultados = document.querySelector(config.target);

        // Validaciones esenciales
        if (!config.tipos || !config.target || !config.renderer || !contenedorResultados) {
            console.error('El input de búsqueda no tiene la configuración completa (data-tipos, data-target, data-renderer).', input);
            return;
        }

        clearTimeout(debounceTimeout);

        if (texto.length > 2) {
            // Llama a la función de callback para mostrar, si está definida
            if (config.callbackShow && typeof window[config.callbackShow] === 'function') {
                window[config.callbackShow]();
            }
            
            debounceTimeout = setTimeout(() => {
                ejecutarBusqueda(texto, config, contenedorResultados);
            }, 350); // 350ms de espera
        } else {
            // Llama a la función de callback para ocultar, si está definida
            if (config.callbackHide && typeof window[config.callbackHide] === 'function') {
                window[config.callbackHide]();
            }
            contenedorResultados.innerHTML = '';
        }
    }

    /**
     * Ejecuta la llamada AJAX y renderiza los resultados.
     * @param {string} texto - El término de búsqueda.
     * @param {object} config - La configuración del input.
     * @param {HTMLElement} contenedor - El elemento donde se mostrarán los resultados.
     */
    async function ejecutarBusqueda(texto, config, contenedor) {
        const datosParaEnviar = {
            texto: texto,
            tipos: config.tipos,
            cantidad: config.cantidad
        };

        try {
            const respuesta = await gloryAjax('busquedaAjax', datosParaEnviar);

            if (respuesta && typeof respuesta.data !== 'undefined') {
                renderizarResultados(respuesta.data, config.renderer, contenedor);
            } else {
                contenedor.innerHTML = '<div class="resultado-item">Error al cargar resultados.</div>';
            }
        } catch (error) {
            console.error('Error en la búsqueda AJAX:', error);
            contenedor.innerHTML = '<div class="resultado-item">Ocurrió un error.</div>';
        }
    }

    /**
     * Llama a la función de renderizado especificada para construir el HTML.
     * @param {object} data - Los datos recibidos del servidor.
     * @param {string} nombreRenderer - El nombre de la función de renderizado.
     * @param {HTMLElement} contenedor - El elemento donde se mostrarán los resultados.
     */
    function renderizarResultados(data, nombreRenderer, contenedor) {
        if (typeof window[nombreRenderer] === 'function') {
            // Llama a la función de renderizado dinámicamente
            contenedor.innerHTML = window[nombreRenderer](data);
        } else {
            console.error(`La función de renderizado '${nombreRenderer}' no existe.`);
            contenedor.innerHTML = '<div class="resultado-item">Error de configuración de vista.</div>';
        }
    }

    // Asocia el manejador de eventos a cada input de búsqueda
    inputsBusqueda.forEach(input => {
        // Elimina listeners previos para evitar duplicados en recargas (gloryRecarga)
        input.removeEventListener('input', manejarInput);
        input.addEventListener('input', manejarInput);
    });
}



function renderizadorGeneral(datos) {
    let htmlFinal = '';
    let totalResultados = 0;


    for (const tipoGrupo in datos) {
        const items = datos[tipoGrupo];
        if (Array.isArray(items) && items.length > 0) {
            totalResultados += items.length;

            items.forEach(item => {
                const url = item.url ? new URL(item.url).href : '#';
                const titulo = item.titulo || 'Sin título';
                const tipo = item.tipo || 'Desconocido';
                
                // Prepara la imagen si existe
                const imagenHtml = item.imagen
                    ? `<img class="resultado-imagen" src="${new URL(item.imagen).href}" alt="${titulo}">`
                    : '<div class="resultado-imagen placeholder"></div>'; // Un placeholder si no hay imagen

                // Construye el HTML para este item
                htmlFinal += `
                    <a href="${url}" class="resultado-enlace">
                        <div class="resultado-item">
                            ${imagenHtml}
                            <div class="resultado-info">
                                <h3>${titulo}</h3>
                                <p>${tipo}</p>
                            </div>
                        </div>
                    </a>
                `;
            });
        }
    }

    // Si después de todo no hay resultados, muestra un mensaje.
    if (totalResultados === 0) {
        return '<div class="resultado-item--no-encontrado">No se encontraron resultados.</div>';
    }

    return htmlFinal;
}

document.addEventListener('gloryRecarga', gloryBusqueda);