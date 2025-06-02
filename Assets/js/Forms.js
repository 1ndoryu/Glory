function inicializarManejoAjaxFormulariosGlory() {
    const formularios = document.querySelectorAll('form.glory-ajax-form');

    // Asegúrate de que GloryAjax esté disponible.
    if (typeof GloryAjax !== 'function') {
        console.error('Glory formManagerComponent JS: La función GloryAjax no está definida. Los formularios y las funciones de borrado no funcionarán con AJAX.');
        formularios.forEach(form => {
            const responseDiv = form.querySelector('.glory-form-ajax-response');
            if (responseDiv) {
                responseDiv.innerHTML = '<p>Error de configuración: Componente AJAX (GloryAjax) no encontrado.</p>';
                responseDiv.className = 'glory-form-ajax-response glory-ajax-error';
            }
        });
        // No detenemos aquí, porque la lógica de borrado podría seguir funcionando si se implementa sin GloryAjax,
        // pero como vamos a usar GloryAjax para borrado también, es un punto de fallo común.
        // Por ahora, continuaremos y dejaremos que la lógica de borrado también falle si GloryAjax no está.
    }

    formularios.forEach(formulario => {
        const responseDiv = formulario.querySelector('.glory-form-ajax-response');
        
        if (!responseDiv) {
            console.warn('Glory formManagerComponent JS: No se encontró el div de respuesta (.glory-form-ajax-response) para el formulario con ID:', formulario.id || '(sin ID)');
            // No retornar aquí, puede haber otros formularios o lógica de borrado a inicializar.
        }

        formulario.addEventListener('submit', async function (evento) {
            evento.preventDefault();

            const botonSubmit = formulario.querySelector('button[type="submit"], input[type="submit"]');
            
            if (responseDiv) {
                responseDiv.innerHTML = 'Enviando...'; 
                responseDiv.className = 'glory-form-ajax-response glory-ajax-loading';
            }

            if (botonSubmit) {
                botonSubmit.disabled = true;
            }

            const formData = new FormData(formulario);
            const wpAjaxAction = formData.get('action'); 

            if (!wpAjaxAction) {
                console.error('Glory formManagerComponent JS: No se encontró el campo "action" en el FormData para el formulario:', formulario.id);
                if (responseDiv) {
                    responseDiv.innerHTML = '<p>Error de configuración interna del formulario (falta "action").</p>';
                    responseDiv.className = 'glory-form-ajax-response glory-ajax-error';
                }
                if (botonSubmit) botonSubmit.disabled = false;
                return;
            }

            const datosParaGloryAjax = {};
            for (const [key, value] of formData.entries()) {
                if (key !== 'action') { // 'action' es el primer parámetro de GloryAjax
                    datosParaGloryAjax[key] = value;
                }
            }

            try {
                const resultado = await GloryAjax(wpAjaxAction, datosParaGloryAjax);

                if (resultado.success) {
                    const mensajeExito = (resultado.data && resultado.data.message) ? resultado.data.message : (resultado.message || '¡Formulario enviado con éxito!');
                    if (responseDiv) {
                        responseDiv.innerHTML = `<p>${mensajeExito}</p>`;
                        responseDiv.className = 'glory-form-ajax-response glory-ajax-success';
                    }
                    if (typeof formulario.reset === 'function') {
                        formulario.reset();
                    }
                } else {
                    const mensajeErrorUsuario = (resultado.data && resultado.data.message) ? resultado.data.message : (resultado.message || 'Ocurrió un error al procesar el formulario.');
                    if (responseDiv) {
                        responseDiv.innerHTML = `<p>${mensajeErrorUsuario}</p>`;
                        responseDiv.className = 'glory-form-ajax-response glory-ajax-error';
                    }
                }
            } catch (error) {
                console.error('Glory formManagerComponent JS: Error inesperado procesando la respuesta de GloryAjax para envío de formulario:', error);
                if (responseDiv) {
                    responseDiv.innerHTML = `<p>Ocurrió un error inesperado. Por favor, inténtelo de nuevo más tarde.</p>`;
                    responseDiv.className = 'glory-form-ajax-response glory-ajax-error';
                }
            } finally {
                if (botonSubmit) {
                    botonSubmit.disabled = false;
                }
            }
        });
    });

    // --- LÓGICA PARA BORRADO DE MENSAJES EN EL PANEL DE ADMINISTRACIÓN ---
    
    // Función auxiliar para mostrar mensajes temporales
    function mostrarMensajeTemporal(elementoReferencia, mensaje, tipo = 'info') {
        const mensajeId = 'glory-admin-message-' + Date.now();
        let p = document.getElementById(mensajeId);
        if (!p) {
            p = document.createElement('p');
            p.id = mensajeId;
            p.style.marginLeft = '10px';
            p.style.display = 'inline-block';
            elementoReferencia.parentNode.insertBefore(p, elementoReferencia.nextSibling);
        }
        p.textContent = mensaje;
        p.className = tipo === 'error' ? 'glory-text-error' : 'glory-text-success'; // Asume que tienes estas clases CSS o estilízalas
        
        setTimeout(() => {
            if (p && p.parentNode) {
                p.parentNode.removeChild(p);
            }
        }, 5000); // El mensaje desaparece después de 5 segundos
    }


    // Borrado individual de mensajes
    document.querySelectorAll('.glory-delete-single-submission').forEach(boton => {
        boton.addEventListener('click', async function() {
            if (!confirm( '¿Estás seguro de que quieres borrar este mensaje? Esta acción no se puede deshacer.')) {
                return;
            }

            const formId = this.dataset.formId;
            const submissionIndex = this.dataset.submissionIndex;
            const nonce = this.dataset.nonce;
            const filaParaBorrar = this.closest('tr');

            if (!formId || submissionIndex === undefined || !nonce) {
                console.error('Glory Admin JS: Faltan datos para borrar el mensaje (formId, submissionIndex, nonce).');
                mostrarMensajeTemporal(this, 'Error: Faltan datos para la operación.', 'error');
                return;
            }
            
            if (typeof GloryAjax !== 'function') {
                alert('Error: La función GloryAjax no está disponible. No se puede borrar el mensaje.');
                return;
            }

            this.disabled = true;
            const textoOriginalBoton = this.textContent;
            this.textContent = 'Borrando...';

            try {
                const resultado = await GloryAjax('glory_delete_single_submission', {
                    form_id: formId,
                    submission_index: submissionIndex,
                    nonce: nonce,
                    // action: 'glory_delete_single_submission' // GloryAjax lo añade como primer parámetro
                });

                if (resultado.success) {
                    if (filaParaBorrar) {
                        filaParaBorrar.style.transition = 'opacity 0.5s ease-out';
                        filaParaBorrar.style.opacity = '0';
                        setTimeout(() => {
                            filaParaBorrar.remove();
                            // Comprobar si la tabla está vacía después de borrar
                            const tbody = document.querySelector(`table.glory-submissions-table[data-form-id="${formId}"] tbody`);
                            if (tbody && tbody.children.length === 0) {
                                const numColumnas = tbody.parentNode.querySelector('thead tr').children.length;
                                tbody.innerHTML = `<tr><td colspan="${numColumnas}">${'No hay envíos para este formulario todavía.'}</td></tr>`;
                            }
                        }, 500);
                    }
                    // No mostramos mensaje temporal aquí porque la fila se elimina, lo cual es suficiente feedback.
                    // Si se quiere, se puede añadir un mensaje global en el panel.
                } else {
                    mostrarMensajeTemporal(this, resultado.message || 'Error al borrar el mensaje.', 'error');
                    this.disabled = false;
                    this.textContent = textoOriginalBoton;
                }
            } catch (error) {
                console.error('Glory Admin JS: Error en AJAX al borrar mensaje individual:', error);
                mostrarMensajeTemporal(this, 'Error de red o del servidor.', 'error');
                this.disabled = false;
                this.textContent = textoOriginalBoton;
            }
        });
    });

    // Borrado de todos los mensajes para un formulario
    document.querySelectorAll('.glory-delete-all-submissions').forEach(boton => {
        boton.addEventListener('click', async function() {
            if (!confirm('¿Estás seguro de que quieres borrar TODOS los mensajes de este formulario? Esta acción no se puede deshacer.')) {
                return;
            }

            const formId = this.dataset.formId;
            const nonce = this.dataset.nonce;
            const tablaAfectada = document.querySelector(`table.glory-submissions-table[data-form-id="${formId}"]`);

            if (!formId || !nonce) {
                console.error('Glory Admin JS: Faltan datos para borrar todos los mensajes (formId, nonce).');
                mostrarMensajeTemporal(this, 'Error: Faltan datos para la operación.', 'error');
                return;
            }

            if (typeof GloryAjax !== 'function') {
                alert('Error: La función GloryAjax no está disponible. No se pueden borrar los mensajes.');
                return;
            }
            
            this.disabled = true;
            const textoOriginalBoton = this.textContent;
            this.textContent = 'Borrando todo...';

            try {
                const resultado = await GloryAjax('glory_delete_all_submissions', {
                    form_id: formId,
                    nonce: nonce,
                    // action: 'glory_delete_all_submissions' // GloryAjax lo añade como primer parámetro
                });

                if (resultado.success) {
                    if (tablaAfectada) {
                        const tbody = tablaAfectada.querySelector('tbody');
                        const numColumnas = tablaAfectada.querySelector('thead tr').children.length;
                        if (tbody) {
                            tbody.innerHTML = `<tr><td colspan="${numColumnas}">${'No hay envíos para este formulario todavía.'}</td></tr>`;
                        }
                    }
                    mostrarMensajeTemporal(this, resultado.message || 'Todos los mensajes borrados.', 'success');
                    // El botón sigue existiendo y habilitado por si se quiere usar para algo más o si la operación falló y se reintenta.
                    // Considerar si el botón "Borrar Todos" debe ocultarse o deshabilitarse permanentemente tras éxito.
                    // Por ahora, lo re-habilito.
                    this.disabled = false; 
                    this.textContent = textoOriginalBoton;

                } else {
                    mostrarMensajeTemporal(this, resultado.message || 'Error al borrar todos los mensajes.', 'error');
                    this.disabled = false;
                    this.textContent = textoOriginalBoton;
                }
            } catch (error) {
                console.error('Glory Admin JS: Error en AJAX al borrar todos los mensajes:', error);
                mostrarMensajeTemporal(this, 'Error de red o del servidor.', 'error');
                this.disabled = false;
                this.textContent = textoOriginalBoton;
            }
        });
    });
}


// Inicialización (igual que antes)
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', inicializarManejoAjaxFormulariosGlory);
} else {
  inicializarManejoAjaxFormulariosGlory();
}