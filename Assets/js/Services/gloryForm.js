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

    document.addEventListener('click', async function (event) {
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
        let validacionExitosa = true;

        // --- Inicio: Bloque de validación ---
        for (const input of campos) {
            if (!input.name) {
                continue;
            }

            // 1. Validación de campos obligatorios
            if (input.required) {
                let campoVacio = false;
                if (input.type === 'checkbox') {
                    if (!input.checked) campoVacio = true;
                } else if (input.type === 'file') {
                    if (input.files.length === 0) campoVacio = true;
                } else if (!input.value.trim()) {
                    campoVacio = true;
                }

                if (campoVacio) {
                    const alerta = input.dataset.alertaObligatorio || `El campo "${input.name}" es obligatorio.`;
                    alert(alerta);
                    validacionExitosa = false;
                    break; 
                }
            }

            // 2. Validación de límites
            const limite = input.dataset.limit;
            if (limite) {
                const limiteNumerico = parseInt(limite, 10);
                if (!isNaN(limiteNumerico)) {
                    if (input.type === 'file') {
                        if (input.files.length > 0 && input.files[0].size > limiteNumerico) {
                            alert(`El archivo "${input.name}" excede el tamaño máximo permitido (${Math.round(limiteNumerico / 1024)} KB).`);
                            validacionExitosa = false;
                            break;
                        }
                    } else if (typeof input.value === 'string' && input.value.length > limiteNumerico) {
                        alert(`El campo "${input.name}" excede el límite de ${limiteNumerico} caracteres.`);
                        validacionExitosa = false;
                        break;
                    }
                }
            }
        }
        // --- Fin: Bloque de validación ---

        if (!validacionExitosa) {
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
        
        const metaTarget = contenedor.dataset.metaTarget;
        const objectId = contenedor.dataset.objectId;

        if (metaTarget) {
            datosParaEnviar.append('metaTarget', metaTarget);
        }
        if (objectId) {
            datosParaEnviar.append('objectId', objectId);
        }

        for (const input of campos) {
            if (!input.name) {
                continue;
            }
            const clave = input.name;
            if (input.type === 'file') {
                if (input.files.length > 0) {
                    datosParaEnviar.append(clave, input.files[0]);
                }
            } else if (input.type === 'checkbox') {
                // Envía '1' si está marcado, '0' si no lo está.
                datosParaEnviar.append(clave, input.checked ? '1' : '0');
            } else if (input.type === 'radio') {
                if (input.checked) {
                    datosParaEnviar.append(clave, input.value);
                }
            } else {
                datosParaEnviar.append(clave, input.value);
            }
        }
        
        console.log('GloryForm.js: Verificando datos a enviar para la acción:', subAccion);
        for (let [clave, valor] of datosParaEnviar.entries()) {
            console.log(`- ${clave}:`, valor);
        }

        try {
            const respuesta = await gloryAjax('gloryFormHandler', datosParaEnviar);
            
            if (respuesta.success && respuesta.data && respuesta.data.alert) {
                ocultarFondo(); // Asumo que esta función existe en tu scope
                alert(respuesta.data.alert);
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
