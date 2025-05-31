function inicializarManejoAjaxFormulariosGlory() {
    const formularios = document.querySelectorAll('form.glory-ajax-form');

    // Primero, asegúrate de que GloryAjax esté disponible.
    if (typeof GloryAjax !== 'function') {
        console.error('Glory formManagerComponent JS: La función GloryAjax no está definida. Los formularios no funcionarán con AJAX.');
        // Podrías incluso añadir un mensaje a todos los responseDiv si es crítico.
        formularios.forEach(form => {
            const responseDiv = form.querySelector('.glory-form-ajax-response');
            if (responseDiv) {
                responseDiv.innerHTML = '<p>Error de configuración: Componente AJAX (GloryAjax) no encontrado.</p>';
                responseDiv.className = 'glory-form-ajax-response glory-ajax-error';
            }
        });
        return; // Detener la inicialización si GloryAjax no está.
    }

    formularios.forEach(formulario => {
        const responseDiv = formulario.querySelector('.glory-form-ajax-response');
        
        if (!responseDiv) {
            console.warn('Glory formManagerComponent JS: No se encontró el div de respuesta (.glory-form-ajax-response) para el formulario con ID:', formulario.id || '(sin ID)');
            return;
        }

        formulario.addEventListener('submit', async function (evento) {
            evento.preventDefault();

            const botonSubmit = formulario.querySelector('button[type="submit"], input[type="submit"]');
            
            responseDiv.innerHTML = 'Enviando...'; 
            responseDiv.className = 'glory-form-ajax-response glory-ajax-loading';

            if (botonSubmit) {
                botonSubmit.disabled = true;
            }

            const formData = new FormData(formulario);
            
            // Obtener la acción de WordPress AJAX del campo oculto 'action' en el formulario
            const wpAjaxAction = formData.get('action'); 

            if (!wpAjaxAction) {
                console.error('Glory formManagerComponent JS: No se encontró el campo "action" en el FormData para el formulario:', formulario.id);
                responseDiv.innerHTML = '<p>Error de configuración interna del formulario (falta "action").</p>';
                responseDiv.className = 'glory-form-ajax-response glory-ajax-error';
                if (botonSubmit) botonSubmit.disabled = false;
                return;
            }

            // Convertir FormData a un objeto simple para GloryAjax, excluyendo el campo 'action'
            // ya que 'action' se pasa como el primer parámetro a GloryAjax.
            const datosParaGloryAjax = {};
            for (const [key, value] of formData.entries()) {
                if (key !== 'action') {
                    datosParaGloryAjax[key] = value;
                }
            }
            // datosParaGloryAjax ahora contendrá _glory_form_id, _glory_form_ajax_nonce, y todos los campos visibles.

            try {
                // Llamar a GloryAjax con la acción y los datos del formulario
                // GloryAjax se encarga de la URL (ajaxUrl) y de la estructura de la petición.
                const resultado = await GloryAjax(wpAjaxAction, datosParaGloryAjax);

                // GloryAjax devuelve un objeto con { success: true/false, data: ..., message: ... }
                if (resultado.success) {
                    // Si hay datos específicos en 'data' y un mensaje, usar el mensaje de 'data'.
                    // De lo contrario, podría haber un mensaje general en 'resultado.message'.
                    const mensajeExito = (resultado.data && resultado.data.message) ? resultado.data.message : (resultado.message || '¡Formulario enviado con éxito!');
                    responseDiv.innerHTML = `<p>${mensajeExito}</p>`;
                    responseDiv.className = 'glory-form-ajax-response glory-ajax-success';
                    if (typeof formulario.reset === 'function') {
                        formulario.reset();
                    }
                } else {
                    // Si success es false, GloryAjax ya debería haber logueado el error.
                    // Usar el mensaje proporcionado por GloryAjax o un fallback.
                    const mensajeErrorUsuario = (resultado.data && resultado.data.message) ? resultado.data.message : (resultado.message || 'Ocurrió un error al procesar el formulario.');
                    
                    responseDiv.innerHTML = `<p>${mensajeErrorUsuario}</p>`;
                    responseDiv.className = 'glory-form-ajax-response glory-ajax-error';
                    // El console.warn/error ya debería haber sido manejado por GloryAjax.
                }

            } catch (error) { // Este catch es para errores inesperados en este bloque, no en GloryAjax.
                console.error('Glory formManagerComponent JS: Error inesperado procesando la respuesta de GloryAjax:', error);
                responseDiv.innerHTML = `<p>Ocurrió un error inesperado. Por favor, inténtelo de nuevo más tarde.</p>`;
                responseDiv.className = 'glory-form-ajax-response glory-ajax-error';
            } finally {
                if (botonSubmit) {
                    botonSubmit.disabled = false;
                }
            }
        });
    });
}


if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', inicializarManejoAjaxFormulariosGlory);
} else {
  inicializarManejoAjaxFormulariosGlory();
}
