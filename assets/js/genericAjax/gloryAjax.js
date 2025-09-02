const ajaxUrl = (typeof ajax_params !== 'undefined' && ajax_params.ajax_url)
    ? ajax_params.ajax_url
    : '/wp-admin/admin-ajax.php';

async function gloryAjax(action = '', data = {}) {
    const gloryLog = (...args) => { if (typeof window !== 'undefined' && window.gloryDebug) console.log(...args); };
    if (typeof window !== 'undefined' && window.location.href.includes('fb-edit=1')) {
        console.warn('gloryAjax: modo fb-edit detectado, petición cancelada.');
        return { success: false, message: 'Solicitud cancelada en modo edición FB.' };
    }
    let body;
    const headers = {};
    const esFormData = (typeof FormData !== 'undefined') && (data instanceof FormData);

    if (esFormData) {
        data.append('action', action);
        body = data;
    } else {
        body = new URLSearchParams({ action, ...data });
        headers['Content-Type'] = 'application/x-www-form-urlencoded';
    }

    try {
        const response = await fetch(ajaxUrl, { method: 'POST', headers, body });
        if (!response.ok) {
            const respText = await response.text().catch(() => '');
            const msg = `Error HTTP ${response.status}: ${response.statusText}` + (respText ? ` - ${respText}` : '');
            throw new Error(msg);
        }

        const contentType = (response.headers.get('Content-Type') || '').toLowerCase();
        const contentDisp = response.headers.get('Content-Disposition') || '';
        const looksLikeAttachment = contentDisp.toLowerCase().includes('attachment') || /filename\s*=\s*"?[^";]+"?/i.test(contentDisp);

        if (looksLikeAttachment || contentType.includes('application/octet-stream') || contentType.includes('text/csv') || contentType.includes('application/vnd')) {
            const blob = await response.blob();
            let filename = null;
            const match = contentDisp.match(/filename\s*=\s*"?([^";]+)"?/i);
            if (match && match[1]) filename = match[1];
            return { success: true, blob, filename };
        }

        const responseText = await response.text();
        try { return JSON.parse(responseText); } catch (e) {
            return { success: true, message: 'La respuesta del servidor no es un JSON válido.', data: responseText };
        }
    } catch (error) {
        gloryLog('Error en gloryAjax:', error);
        return { success: false, message: error.message || 'Error de red o en la solicitud.' };
    }
}

if (typeof window !== 'undefined' && typeof window.enviarAjax !== 'function') {
    window.enviarAjax = function(action, data = {}) { return gloryAjax(action, data); };
}

if (typeof window !== 'undefined' && typeof window.gloryRefresh !== 'function') {
    window.gloryRefresh = async function(opts = {}) {
        const { postId = null, tipo = 'tarea', lista = false, filtro = null, args = {}, targetSelector = null, raw = false } = opts || {};
        try {
            if (postId) {
                const res = await gloryAjax('obtenerHtmlPost', { id: postId, tipo });
                if (raw) return res;
                const html = res && res.success
                    ? (typeof res.data === 'string' ? res.data : (typeof res === 'string' ? res : (res.data && typeof res.data.data === 'string' ? res.data.data : '')))
                    : '';
                if (html) {
                    if (targetSelector) {
                        const target = document.querySelector(targetSelector);
                        if (target) target.insertAdjacentHTML('afterbegin', html);
                    }
                    return html;
                }
                return null;
            }
            if (lista) {
                const res = await gloryAjax('obtenerHtmlLista', { filtro, ...args });
                if (raw) return res;
                const html = res && res.success
                    ? (typeof res.data === 'string' ? res.data : (typeof res === 'string' ? res : (res.data && typeof res.data.data === 'string' ? res.data.data : '')))
                    : '';
                return html || '';
            }
            return null;
        } catch (e) {
            console.error('gloryRefresh: error', e);
            return null;
        }
    };
}

if (typeof window !== 'undefined' && typeof window.reiniciarPost !== 'function') {
    window.reiniciarPost = function(id, tipo = 'tarea') { return window.gloryRefresh({ postId: id, tipo }); };
}

// Refresca listas de contenido renderizadas por ContentRender (agnóstico al tipo)
if (typeof window !== 'undefined' && typeof window.reiniciarContenido !== 'function') {
    window.reiniciarContenido = async function(optsOrLimpiar = {}) {
        // Compatibilidad con firma antigua: (limpiar:boolean, filtro:string, tipo:string)
        if (typeof optsOrLimpiar !== 'object' || optsOrLimpiar === null) {
            const tipoAntiguo = arguments[2] || 'post';
            return window.reiniciarContenido({ postType: tipoAntiguo });
        }
        const opts = optsOrLimpiar || {};
        const {
            postType = 'post',
            plantilla = null,
            publicacionesPorPagina = 10,
            argumentosConsulta = {},
            claseContenedor = null,
            claseItem = null,
            targetSelector = null,
            replace = true
        } = opts;

        // No soporta reiniciar un solo post: usar reiniciarPost para eso.
        if (opts.postId) {
            console.warn('reiniciarContenido: no soporta postId. Use reiniciarPost para un solo post.');
        }

        const payload = {
            postType,
            plantilla,
            publicacionesPorPagina,
            claseContenedor,
            claseItem,
            argumentosConsulta: JSON.stringify(argumentosConsulta)
        };

        const res = await gloryAjax('obtenerHtmlLista', payload);
        const html = res && res.success ? (typeof res.data === 'string' ? res.data : (typeof res === 'string' ? res : '')) : '';
        if (!html) return '';

        try {
            if (targetSelector) {
                const target = document.querySelector(targetSelector);
                if (target) {
                    if (replace) target.innerHTML = html; else target.insertAdjacentHTML('beforeend', html);
                }
            } else if (claseContenedor) {
                const firstClass = (claseContenedor || '').split(/\s+/).filter(Boolean)[0];
                if (firstClass) {
                    const current = document.querySelector('.' + firstClass);
                    if (current) {
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        const next = doc.querySelector('.' + firstClass);
                        if (next) current.outerHTML = next.outerHTML; else current.innerHTML = html;
                    }
                }
            }
        } catch (_) {
            // Si hay un error manipulando el DOM, devolvemos el HTML para que el caller decida.
        }

        try { window.dispatchEvent(new Event('reiniciar')); } catch (_) {}
        return html;
    };
}

// Alias opcional para compatibilidad con el nombre anterior usado en esta sesión
if (typeof window !== 'undefined') {
    window.gloryRefreshList = window.reiniciarContenido;
}