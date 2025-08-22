const ajaxUrl = (typeof ajax_params !== 'undefined' && ajax_params.ajax_url) 
    ? ajax_params.ajax_url 
    : '/wp-admin/admin-ajax.php';

async function gloryAjax(action = '', data = {}) {
    const gloryLog = (...args) => { if (typeof window !== 'undefined' && window.gloryDebug) console.log(...args); };
    if (window.location.href.includes('fb-edit=1')) {
        console.warn('gloryAjax: modo fb-edit detectado, petición cancelada.');
        return { success: false, message: 'Solicitud cancelada en modo edición FB.' };
    }
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

        if (!response.ok) {
            throw new Error(`Error HTTP ${response.status}: ${response.statusText}`);
        }

        // Detectar si la respuesta es un archivo/attachment (CSV, binarios, etc.)
        const contentType = (response.headers.get('Content-Type') || '').toLowerCase();
        const contentDisp = response.headers.get('Content-Disposition') || '';
        const looksLikeAttachment = contentDisp.toLowerCase().includes('attachment') || /filename\s*=\s*"?[^";]+"?/i.test(contentDisp);

        if (looksLikeAttachment || contentType.includes('application/octet-stream') || contentType.includes('text/csv') || contentType.includes('application/vnd')) {
            // Devolver el blob y el filename si existe
            const blob = await response.blob();
            let filename = null;
            const match = contentDisp.match(/filename\s*=\s*"?([^";]+)"?/i);
            if (match && match[1]) {
                filename = match[1];
            }
            return { success: true, blob: blob, filename: filename };
        }

        // No es attachment: intentar leer como texto y parsear JSON si aplica
        const responseText = await response.text();
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
        gloryLog('Error en gloryAjax:', error);
        return {
            success: false,
            message: error.message || 'Error de red o en la solicitud.'
        };
    }
}