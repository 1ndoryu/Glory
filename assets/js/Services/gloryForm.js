// @tarea-pendiente Jules: Realizar una revisión más exhaustiva de este archivo JavaScript en una tarea futura para optimización y refactorización avanzada.
function gloryForm() {
    if (document.body.dataset.gloryFormListenersAttached) {
        return;
    }
    document.body.dataset.gloryFormListenersAttached = 'true';

    document.addEventListener('input', event => {
        const input = event.target;
        if ((input.tagName.toLowerCase() === 'textarea' || input.type === 'text') && input.dataset.limit) {
            const limite = parseInt(input.dataset.limit, 10);
            if (!isNaN(limite) && input.value.length > limite) {
                input.value = input.value.slice(0, limite);
            }
        }
    });

    document.addEventListener('change', event => {
        const input = event.target;
        if (input.type === 'file' && input.files.length > 0 && input.dataset.limit) {
            const limite = parseInt(input.dataset.limit, 10);
            if (!isNaN(limite) && input.files[0].size > limite) {
                alert(`El archivo excede el tamaño máximo permitido (${Math.round(limite / 1024)} KB).`);
                input.value = '';
            }
        }
    });

    document.addEventListener('click', async function(event) {
        if (!event.target.matches('.dataSubir')) {
            return;
        }

        event.preventDefault();
        const boton = event.target;
        const subAccion = boton.dataset.accion;

        if (!subAccion) {
            console.error('El botón de envío no tiene un atributo data-accion definido.');
            return;
        }

        const contenedor = boton.closest('.gloryForm');
        if (!contenedor) {
            console.error('El botón .dataSubir debe estar dentro de un elemento con la clase .gloryForm');
            return;
        }

        const campos = contenedor.querySelectorAll('input:not([type="button"]):not([type="submit"]), textarea, select');
        const errores = [];
        const nombresCamposValidados = new Set();

        for (const input of campos) {
            if (!input.name || nombresCamposValidados.has(input.name)) {
                continue;
            }

            const alertaObligatorio = input.dataset.alertaObligatorio || `El campo "${input.name}" es obligatorio.`;
            const nombreAmigable = input.dataset.nombreAmigable || input.name;

            if (input.required) {
                let campoVacio = false;
                if (input.type === 'radio') {
                    const grupoRadios = contenedor.querySelectorAll(`input[type="radio"][name="${input.name}"]`);
                    if (!Array.from(grupoRadios).some(radio => radio.checked)) {
                        campoVacio = true;
                    }
                    nombresCamposValidados.add(input.name);
                } else if (input.type === 'checkbox') {
                    if (!input.checked) campoVacio = true;
                } else if (input.type === 'file') {
                    if (input.files.length === 0) campoVacio = true;
                } else if (!input.value.trim()) {
                    campoVacio = true;
                }

                if (campoVacio) {
                    errores.push(alertaObligatorio);
                }
            }
            
            const minimo = input.dataset.minimo;
            if (minimo) {
                const minimoNumerico = parseInt(minimo, 10);
                if (!isNaN(minimoNumerico)) {
                    if (input.type === 'file' && input.files.length > 0 && input.files[0].size < minimoNumerico) {
                        errores.push(`El archivo "${nombreAmigable}" no cumple con el tamaño mínimo de ${Math.round(minimoNumerico / 1024)} KB.`);
                    } else if (typeof input.value === 'string' && input.value.trim().length > 0 && input.value.trim().length < minimoNumerico) {
                        errores.push(`El campo "${nombreAmigable}" debe tener al menos ${minimoNumerico} caracteres.`);
                    }
                }
            }

            const limite = input.dataset.limit;
            if (limite) {
                const limiteNumerico = parseInt(limite, 10);
                if (!isNaN(limiteNumerico)) {
                    if (input.type === 'file' && input.files.length > 0 && input.files[0].size > limiteNumerico) {
                        errores.push(`El archivo "${nombreAmigable}" excede el tamaño máximo de ${Math.round(limiteNumerico / 1024)} KB.`);
                    } else if (typeof input.value === 'string' && input.value.length > limiteNumerico) {
                        errores.push(`El campo "${nombreAmigable}" excede el límite de ${limiteNumerico} caracteres.`);
                    }
                }
            }
        }

        if (errores.length > 0) {
            alert("Por favor, corrige los siguientes errores:\n\n- " + errores.join('\n- '));
            return;
        }

        if (!window.dataGlobal || !window.dataGlobal.nonce) {
            console.error('El nonce de seguridad (nonce) no se encontró en window.dataGlobal.');
            alert('Error de configuración de seguridad. No se puede enviar el formulario.');
            return;
        }

        const datosParaEnviar = new FormData();
        datosParaEnviar.append('subAccion', subAccion);
        datosParaEnviar.append('nonce', window.dataGlobal.nonce);

        // Recopilar configuración del backend desde los atributos data-* del contenedor
        const config = contenedor.dataset;
        if (config.metaTarget) datosParaEnviar.append('metaTarget', config.metaTarget);
        if (config.objectId) datosParaEnviar.append('objectId', config.objectId);
        if (config.postType) datosParaEnviar.append('postType', config.postType);
        if (config.postStatus) datosParaEnviar.append('postStatus', config.postStatus);

        for (const input of campos) {
            if (!input.name) continue;
            
            const clave = input.name;
            if (input.type === 'file') {
                if (input.files.length > 0) {
                    datosParaEnviar.append(clave, input.files[0]);
                }
            } else if (input.type === 'checkbox') {
                datosParaEnviar.append(clave, input.checked ? '1' : '0');
            } else if (input.type === 'radio') {
                if (input.checked) {
                    datosParaEnviar.append(clave, input.value);
                }
            } else {
                datosParaEnviar.append(clave, input.value);
            }
        }
        
        // console.log('GloryForm.js: Verificando datos a enviar para la acción:', subAccion);
        // for (let [clave, valor] of datosParaEnviar.entries()) {
        //     console.log(`- ${clave}:`, valor);
        // }

        try {
            const respuesta = await gloryAjax('gloryFormHandler', datosParaEnviar);

            if (respuesta.success && respuesta.data && respuesta.data.alert) {
                if (typeof ocultarFondo === 'function') {
                    ocultarFondo();
                }
                alert(respuesta.data.alert);

                // Limpiar campos del formulario tras envío exitoso (contenedor no es <form>)
                const camposParaLimpiar = contenedor.querySelectorAll('input, textarea, select');
                camposParaLimpiar.forEach(campo => {
                    const tag = campo.tagName.toLowerCase();
                    if (tag === 'input') {
                        if (['text', 'number', 'date', 'email', 'tel', 'range'].includes(campo.type)) {
                            campo.value = '';
                        } else if (['checkbox', 'radio'].includes(campo.type)) {
                            campo.checked = false;
                        }
                    } else if (tag === 'textarea') {
                        campo.value = '';
                    } else if (tag === 'select') {
                        campo.selectedIndex = 0;
                    }
                });

                // Disparar evento 'change' en todos los <select> para actualizar posibles lógicas externas
                contenedor.querySelectorAll('select').forEach(sel => {
                    sel.dispatchEvent(new Event('change'));
                });
            } else if (!respuesta.success && respuesta.data && respuesta.data.alert) {
                alert(respuesta.data.alert);
            }

        } catch (error) {
            console.error(`Error en la petición para "${subAccion}":`, error);
            const mensajeError = error.message || 'Ocurrió un error inesperado al procesar su solicitud.';
            alert(`Error en la petición: ${mensajeError}`);
        }
    });
}

document.addEventListener('gloryRecarga', gloryForm);