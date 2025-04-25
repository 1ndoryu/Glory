

const ajaxUrl = typeof ajax_params !== 'undefined' && ajax_params.ajax_url ? ajax_params.ajax_url : '/wp-admin/admin-ajax.php';

async function GloryAjax(action, data = {}) {
    // Asegúrate que ajaxUrl está definido en este scope o globalmente
    if (!enviarAjax) var enviarAjax = {}; // Asegura que el objeto exista si no es global
    if (!enviarAjax.llamadas) {
        enviarAjax.llamadas = {};
    }
    const llave = `${action}:${JSON.stringify(data)}`;
    enviarAjax.llamadas[llave] = (enviarAjax.llamadas[llave] || 0) + 1;

    // Construye el cuerpo incluyendo 'action' y los datos
    // ¡IMPORTANTE! El nonce debe estar DENTRO del objeto 'data' que se pasa a esta función.
    const body = new URLSearchParams({
        action: action,
        ...data // Aquí se incluye el nonce si está en 'data'
    });

    console.log(`GloryAjax: Enviando solicitud AJAX: ${action} | Datos: ${JSON.stringify(data)} | Llamadas: ${enviarAjax.llamadas[llave]}`);
    console.log("GloryAjax: Body:", body.toString()); // Log para ver qué se envía exactamente

    try {
        const response = await fetch(ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: body
        });

        console.log(`GloryAjax: Respuesta recibida para ${action}:`, response);
        const responseText = await response.text(); // Obtener texto para depurar

        if (!response.ok) {
            // Loguear más detalles en caso de error HTTP
             console.error('Error HTTP en la solicitud AJAX:', {
                 status: response.status,
                 statusText: response.statusText,
                 responseText: responseText, // Mostrar la respuesta del servidor
                 action: action,
                 requestData: data
             });
             console.log(`GloryAjax: Error HTTP en la solicitud AJAX: ${action}`);
            throw new Error(`HTTP error! status: ${response.status} - ${response.statusText}`);
        }

        let responseData;
        try {
            // Intenta parsear como JSON, que es lo que wp_send_json_* envía
            responseData = JSON.parse(responseText);
        } catch (jsonError) {
            // Si falla el parseo JSON (ej: devuelve '0' o HTML de error)
            console.log(`GloryAjax: Error al parsear JSON en la respuesta para ${action}`);
            console.error('No se pudo interpretar la respuesta como JSON:', {
                error: jsonError,
                responseText: responseText, // Muestra lo que realmente devolvió el servidor
                action: action,
                requestData: data
            });
            // Devolver un objeto de error consistente si no es JSON
            return { success: false, message: 'Invalid server response.', rawResponse: responseText };
        }

        // Devuelve los datos parseados (esperamos { success: true/false, data: ...})
        return responseData;

    } catch (error) {
        console.log(`GloryAjax: Error en el bloque try-catch para ${action}`);
        // Captura errores de red o el 'throw' del !response.ok
        console.error('Error en la solicitud AJAX (catch):', {
            error: error,
            action: action,
            requestData: data,
            ajaxUrl: ajaxUrl
        });
        // Devuelve un objeto de error consistente
        return { success: false, message: error.message || 'Network error or failed request.' };
    }
}