/**
 * @file gloryBusqueda.js
 * Gestiona todas las funcionalidades de búsqueda dinámica en el sitio.
 * Se inicializa en el evento 'gloryRecarga' (evento personalizado que puede ser disparado tras recargas de contenido AJAX)
 * y se asocia a los inputs con la clase '.busqueda'.
 *
 * Realizado por: @wandorius
 * Revisión y Refactorización: @Gemini AI Agent (basado en tarea de Jules)
 */
function gloryBusqueda() {
    const inputsBusqueda = document.querySelectorAll('.busqueda'); // Obtiene todos los campos de búsqueda.
    const DEBOUNCE_TIMEOUT = 350; // Tiempo (ms) de espera antes de ejecutar la búsqueda tras la última pulsación.
    let debounceTimeoutId; // Almacena el ID del temporizador para el debounce.

    /**
     * Muestra un mensaje dentro de un contenedor HTML. Útil para feedback al usuario.
     * @param {HTMLElement} contenedor - El elemento contenedor donde se insertará el mensaje.
     * @param {string} mensaje - El mensaje a mostrar.
     * @param {'info' | 'error' | 'vacio'} [tipo='info'] - Tipo de mensaje para aplicar estilo CSS.
     */
    function mostrarMensajeEnContenedor(contenedor, mensaje, tipo = 'info') {
        // Se espera que existan clases CSS como: .mensaje-busqueda, .mensaje-info, .mensaje-error, .mensaje-vacio
        contenedor.innerHTML = `<div class="mensaje-busqueda mensaje-${tipo}">${mensaje}</div>`;
    }

    /**
     * Gestiona el evento 'input' de los campos de búsqueda.
     * Valida la longitud del texto, aplica debounce y, si procede, inicia la búsqueda.
     * @param {Event} evento - El objeto evento 'input'.
     */
    function manejarInput(evento) {
        const inputActual = evento.target;
        const textoBusqueda = inputActual.value.trim();

        const opcionesInput = {
            tipos: inputActual.dataset.tipos, // CSV de tipos de contenido a buscar (ej: "post,producto").
            cantidadResultados: parseInt(inputActual.dataset.cantidad, 10) || 2, // Resultados por tipo.
            selectorTarget: inputActual.dataset.target, // Selector CSS del contenedor de resultados.
            renderer: inputActual.dataset.renderer // Identificador del renderer backend.
        };

        // Validación de la configuración esencial del input.
        if (!opcionesInput.tipos || !opcionesInput.selectorTarget || !opcionesInput.renderer) {
            console.error('Error de Configuración: El input de búsqueda no tiene todos los atributos data-* requeridos (data-tipos, data-target, data-renderer). Input problemático:', inputActual);
            return;
        }

        const contenedorResultados = document.querySelector(opcionesInput.selectorTarget);
        if (!contenedorResultados) {
            console.error(`Error de Configuración: No se encontró el contenedor de resultados con el selector '${opcionesInput.selectorTarget}'. Input problemático:`, inputActual);
            return;
        }

        clearTimeout(debounceTimeoutId); // Cancela el temporizador anterior, si existe.

        if (textoBusqueda.length > 2) { // Umbral de caracteres para iniciar la búsqueda.
            // Activar indicadores visuales de búsqueda.
            if (typeof window.mostrarFondo === 'function') { // Dependencia externa opcional.
                window.mostrarFondo();
            }
            contenedorResultados.style.display = 'flex'; // Mostrar contenedor.
            mostrarMensajeEnContenedor(contenedorResultados, 'Buscando...', 'info'); // Feedback de carga.

            debounceTimeoutId = setTimeout(() => {
                ejecutarBusqueda(textoBusqueda, opcionesInput, contenedorResultados);
            }, DEBOUNCE_TIMEOUT);
        } else {
            // Si el texto es corto, limpiar y ocultar resultados.
            if (typeof window.ocultarFondo === 'function') { // Dependencia externa opcional.
                window.ocultarFondo();
            }
            contenedorResultados.innerHTML = ''; // Limpiar.
            contenedorResultados.style.display = 'none'; // Ocultar.
        }
    }

    /**
     * Realiza la petición AJAX para buscar y muestra los resultados.
     * @param {string} textoBusqueda - Término a buscar.
     * @param {object} opcionesInput - Configuración del input que originó la búsqueda.
     * @param {HTMLElement} contenedorResultados - Elemento donde se renderizarán los resultados.
     */
    async function ejecutarBusqueda(textoBusqueda, opcionesInput, contenedorResultados) {
        const datosParaEnviar = {
            action: 'busquedaAjax', // Acción para el manejador AJAX de WordPress (u otro backend).
            texto: textoBusqueda,
            tipos: opcionesInput.tipos,
            cantidad: opcionesInput.cantidadResultados,
            renderer: opcionesInput.renderer
            // Considerar añadir un nonce si el backend lo requiere por seguridad.
            // _ajax_nonce: nonceGlobal // Ejemplo de nonce.
        };

        try {
            // 'gloryAjax' es una función global o importada que gestiona las llamadas AJAX y devuelve una Promesa.
            const respuesta = await gloryAjax(datosParaEnviar.action, datosParaEnviar);

            if (respuesta && respuesta.success && respuesta.data && typeof respuesta.data.html === 'string') {
                // Si la búsqueda fue exitosa y hay HTML para mostrar.
                if (respuesta.data.html.trim() === '') {
                    mostrarMensajeEnContenedor(contenedorResultados, 'No se encontraron resultados para su búsqueda.', 'vacio');
                } else {
                    contenedorResultados.innerHTML = respuesta.data.html;
                }
            } else if (respuesta && !respuesta.success && respuesta.data && typeof respuesta.data.mensaje === 'string') {
                // Si el backend indica un error conocido con un mensaje.
                mostrarMensajeEnContenedor(contenedorResultados, respuesta.data.mensaje, 'error');
                console.warn('Respuesta no exitosa (controlada) de la búsqueda AJAX:', respuesta.data.mensaje, 'Input:', opcionesInput.selectorTarget);
            } else {
                // Respuesta inesperada o no exitosa sin mensaje específico.
                mostrarMensajeEnContenedor(contenedorResultados, 'No se pudo obtener una respuesta válida del servidor.', 'error');
                console.warn('Respuesta inesperada o no exitosa de la búsqueda AJAX:', respuesta, 'Input:', opcionesInput.selectorTarget);
            }
        } catch (error) {
            console.error('Error Crítico: Fallo en la ejecución de la búsqueda AJAX. Input:', opcionesInput.selectorTarget, 'Error:', error);
            mostrarMensajeEnContenedor(contenedorResultados, 'Ocurrió un error crítico al buscar. Por favor, intente más tarde.', 'error');
        }
    }

    // Asignar el manejador de input a cada campo de búsqueda.
    inputsBusqueda.forEach(input => {
        // Limpiar listeners anteriores para evitar duplicados si 'gloryRecarga' se dispara múltiples veces.
        // Se guarda la referencia del listener en el propio input para una correcta eliminación.
        if (input.gloryBusquedaListener) {
            input.removeEventListener('input', input.gloryBusquedaListener);
        }
        input.gloryBusquedaListener = manejarInput; // Guardar la referencia.
        input.addEventListener('input', manejarInput);
    });

    // Consideración para Single Page Applications (SPAs) o limpieza manual:
    // Si fuera necesario desvincular los eventos explícitamente (ej. al cambiar de "vista" en una SPA sin recarga de página),
    // se podría exponer una función de limpieza o escuchar un evento personalizado de "desmontaje".
    // Ejemplo:
    // document.addEventListener('gloryDebeLimpiarBusqueda', () => {
    //     inputsBusqueda.forEach(input => {
    //         if (input.gloryBusquedaListener) {
    //             input.removeEventListener('input', input.gloryBusquedaListener);
    //             delete input.gloryBusquedaListener; // Eliminar la referencia.
    //         }
    //     });
    // });
}

document.addEventListener('gloryRecarga', gloryBusqueda);
