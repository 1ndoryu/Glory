const ajaxUrl = (typeof ajax_params !== 'undefined' && ajax_params.ajax_url) 
    ? ajax_params.ajax_url 
    : '/wp-admin/admin-ajax.php';

async function gloryAjax(action = '', data = {}) {
    const body = new URLSearchParams({
        action,
        ...data
    });

    try {
        const response = await fetch(ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body
        });

        const responseText = await response.text();

        if (!response.ok) {
            throw new Error(`Error HTTP ${response.status}: ${response.statusText}`);
        }

        try {
            return JSON.parse(responseText);
        } catch (e) {
            return {
                success: false,
                message: 'La respuesta del servidor no es un JSON válido.',
                data: responseText
            };
        }
    } catch (error) {
        return {
            success: false,
            message: error.message || 'Error de red o en la solicitud.'
        };
    }
}
