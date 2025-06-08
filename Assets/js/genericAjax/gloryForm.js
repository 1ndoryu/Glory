function gloryForm() {
    // Wan: Salvaguarda para evitar registrar los listeners múltiples veces.
    if (document.body.dataset.gloryFormListenersAttached) {
        return;
    }
    document.body.dataset.gloryFormListenersAttached = 'true';

    // Wan: Listener para limitar la escritura en tiempo real.
    document.addEventListener('input', event => {
        const input = event.target;
        // Solo aplica a inputs de texto y textareas con el atributo data-limit.
        if ((input.tagName.toLowerCase() === 'textarea' || input.type === 'text') && input.dataset.limit) {
            const limite = parseInt(input.dataset.limit, 10);
            if (!isNaN(limite) && input.value.length > limite) {
                // Si se excede el límite, se corta el texto.
                input.value = input.value.slice(0, limite);
            }
        }
    });

    // Wan: Listener para validar el tamaño del archivo al momento de seleccionarlo.
    document.addEventListener('change', event => {
        const input = event.target;
        // Solo aplica a inputs de tipo archivo con el atributo data-limit.
        if (input.type === 'file' && input.files.length > 0 && input.dataset.limit) {
            const limite = parseInt(input.dataset.limit, 10);
            if (!isNaN(limite) && input.files[0].size > limite) {
                // El valor de data-limit debe estar en bytes. Ejemplo: 1MB = 1048576.
                alert(`El archivo excede el tamaño máximo permitido (${Math.round(limite / 1024)} KB).`);
                // Se limpia la selección de archivo.
                input.value = '';
            }
        }
    });

    // Wan: Listener principal para el envío del formulario.
    document.addEventListener('click', async function (event) {
        if (!event.target.matches('.dataSubir')) {
            return;
        }

        event.preventDefault();
        const boton = event.target;
        const accion = boton.dataset.accion;

        if (!accion) {
            console.error('El botón de envío no tiene un atributo data-accion definido.');
            return;
        }

        const contenedor = boton.closest('.gloryForm');
        if (!contenedor) {
            console.error('El botón .dataSubir debe estar dentro de un elemento con la clase .gloryForm');
            return;
        }

        const datosParaEnviar = new FormData();
        datosParaEnviar.append('accion', accion);

        // Wan: Se agrega el nonce de seguridad a la petición.
        if (!window.dataGlobal || !window.dataGlobal.nonce) {
            console.error('El nonce de seguridad (nonce) no se encontró en window.dataGlobal.');
            alert('Error de configuración de seguridad. No se puede enviar el formulario.');
            return;
        }
        datosParaEnviar.append('nonce', window.dataGlobal.nonce);

        const campos = contenedor.querySelectorAll('input, textarea, select');
        let validacionExitosa = true;

        for (const input of campos) {
            if (!input.name) {
                continue; // Ignora inputs sin nombre.
            }

            // Wan: Doble verificación de límites antes de enviar.
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

            // Recolección de datos.
            const clave = input.name;
            if (input.type === 'file') {
                if (input.files.length > 0) {
                    datosParaEnviar.append(clave, input.files[0]);
                }
            } else if (input.type === 'checkbox') {
                datosParaEnviar.append(clave, input.checked);
            } else if (input.type === 'radio') {
                if (input.checked) {
                    datosParaEnviar.append(clave, input.value);
                }
            } else {
                datosParaEnviar.append(clave, input.value);
            }
        }

        if (!validacionExitosa) {
            return; // Detiene el envío si la validación final falló.
        }

        try {
            const respuesta = await gloryAjax('formService', datosParaEnviar);
            if (respuesta.alert) {
                alert(respuesta.alert);
            }
        } catch (error) {
            console.error(`Error en la petición para "${accion}":`, error);
            const mensajeError = error.alert || 'Ocurrió un error inesperado al procesar su solicitud.';
            alert(`Error en la petición: ${mensajeError}`);
        }
    });
}

document.addEventListener('gloryRecarga', gloryForm);
