const ajaxUrl = (typeof ajax_params !== 'undefined' && ajax_params.ajax_url) 
    ? ajax_params.ajax_url 
    : '/wp-admin/admin-ajax.php';

async function gloryAjax(action = '', data = {}) {
    let body;
    const headers = {};
    const esFormData = data instanceof FormData;

    if (esFormData) {
        // --- Modo 1: Datos de Formulario (para soportar archivos) ---
        data.append('action', action);
        body = data;
        // NO establecemos Content-Type, el navegador lo hará automáticamente.
    } else {
        // --- Modo 2: Datos de Objeto (comportamiento original, no se rompe nada) ---
        body = new URLSearchParams({
            action,
            ...data
        });
        headers['Content-Type'] = 'application/x-www-form-urlencoded';
    }

    try {
        const response = await fetch(ajaxUrl, {
            method: 'POST',
            headers, // Usa las cabeceras que preparamos
            body     // Usa el cuerpo que preparamos
        });

        const responseText = await response.text();

        if (!response.ok) {
            throw new Error(`Error HTTP ${response.status}: ${response.statusText}`);
        }

        try {
            return JSON.parse(responseText);
        } catch (e) {
            // La respuesta no es JSON, pero la petición fue exitosa (ej: HTML, texto)
            return {
                success: true, 
                message: 'La respuesta del servidor no es un JSON válido.',
                data: responseText
            };
        }
    } catch (error) {
        console.error('Error en gloryAjax:', error);
        return {
            success: false,
            message: error.message || 'Error de red o en la solicitud.'
        };
    }
}