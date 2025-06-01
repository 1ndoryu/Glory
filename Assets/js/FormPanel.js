document.addEventListener('DOMContentLoaded', () => {
    // Acceder a los datos localizados
    const panelData = window.gloryAdminPanelData || {};
    const ajaxUrl = panelData.ajaxUrl;

    if (!ajaxUrl) {
        console.error('Glory Form Panel: ajaxUrl no está definido. Las acciones de borrado no funcionarán.');
        return;
    }

    /**
     * Realiza la llamada AJAX para borrar envíos.
     * @param {string} action - La acción AJAX de WordPress.
     * @param {object} data - Datos a enviar (form_id, submission_index, nonce).
     * @param {HTMLButtonElement} button - El botón que disparó la acción.
     * @returns {Promise<object>} - La respuesta JSON del servidor.
     */
    async function performAjaxDelete(action, data, button) {
        const originalButtonText = button.innerHTML;
        button.disabled = true;
        button.innerHTML = panelData.textDeleting || 'Borrando...';

        const formData = new FormData();
        formData.append('action', action);
        for (const key in data) {
            formData.append(key, data[key]);
        }

        // --- Logs existentes para depuración (mantenidos) ---
        console.log('Glory Form Panel - performAjaxDelete Invoked:');
        // ajaxUrl se define fuera, pero lo logueamos aquí también para contexto
        console.log('Raw ajaxUrl from panelData (accessed from outer scope):', ajaxUrl);
        console.log('Action for AJAX:', action);
        console.log('Data for AJAX (before FormData):', data);

        let formDataEntries = {};
        for (var pair of formData.entries()) {
            formDataEntries[pair[0]] = pair[1];
        }
        console.log('Content of FormData to be sent:', formDataEntries);
        // --- Fin de logs existentes ---

        let finalAjaxUrl;
        try {
            // Validar y resolver la URL
            if (!ajaxUrl || typeof ajaxUrl !== 'string' || ajaxUrl.trim() === '') {
                throw new Error('ajaxUrl no está definido, es inválido o está vacío.');
            }
            // URL constructor valida si la cadena es una URL absoluta.
            // Si ajaxUrl fuera relativa (lo que no debería pasar con admin_url()), necesitaría un segundo argumento base.
            const urlObject = new URL(ajaxUrl);
            finalAjaxUrl = urlObject.href;
            console.log('Glory Form Panel - Validated and final ajaxUrl for fetch:', finalAjaxUrl);
        } catch (urlError) {
            console.error('Glory Form Panel - Error al validar ajaxUrl:', urlError.message);
            console.error('Glory Form Panel - Valor original de ajaxUrl que causó el error:', ajaxUrl);
            button.innerHTML = originalButtonText;
            button.disabled = false;
            // Usamos panelData.textErrorConfig, o un fallback si no está definido.
            const errorMsg = panelData.textErrorConfig || 'Error de configuración: La URL para las operaciones AJAX no es válida.';
            alert(errorMsg);
            return {success: false, data: {message: errorMsg}};
        }

        try {
            const response = await fetch(finalAjaxUrl, {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                console.error('Glory Form Panel - Respuesta HTTP no OK:', response.status, response.statusText, 'URL:', finalAjaxUrl);
                let responseBody = await response.text();
                console.error('Glory Form Panel - Cuerpo de la respuesta (no OK):', responseBody);

                let errorMessage = `Error del servidor: ${response.status} ${response.statusText}.`;
                try {
                    const errorJson = JSON.parse(responseBody);
                    if (errorJson && errorJson.data && errorJson.data.message) {
                        errorMessage = errorJson.data.message;
                    } else if (errorJson && errorJson.message) {
                        errorMessage = errorJson.message;
                    } else if (responseBody) {
                        errorMessage += ` Detalles: ${responseBody.substring(0, 200)}...`;
                    }
                } catch (e) {
                    // No era JSON o no tenía el formato esperado
                    if (responseBody) {
                        errorMessage += ` Detalles: ${responseBody.substring(0, 200)}...`;
                    }
                }
                throw new Error(errorMessage);
            }

            const result = await response.json();
            console.log('Glory Form Panel - Respuesta AJAX exitosa:', result);
            return result;
        } catch (error) {
            // Este catch atrapará errores del fetch mismo (ej. red, CORS), o el error lanzado arriba si !response.ok
            console.error('Error en la solicitud AJAX (catch principal de performAjaxDelete):', error.message, 'URL:', finalAjaxUrl);
            // Si el error ya tiene un mensaje (como el que construimos para !response.ok), lo usamos.
            // De lo contrario, usamos el genérico.
            const errorMessageToDisplay = error.message || panelData.textErrorGeneric || 'Ocurrió un error al procesar la solicitud.';
            return {success: false, data: {message: errorMessageToDisplay}};
        } finally {
            button.innerHTML = originalButtonText;
            button.disabled = false;
        }
    }

    /**
     * Actualiza el tbody de una tabla para mostrar que no hay mensajes.
     * @param {HTMLTableSectionElement} tbodyElement - El elemento tbody a actualizar.
     * @param {number} columnCount - El número de columnas en la tabla.
     */
    function showNoMessagesRow(tbodyElement, columnCount) {
        tbodyElement.innerHTML = ''; // Limpiar tbody
        const tr = document.createElement('tr');
        const td = document.createElement('td');
        td.setAttribute('colspan', columnCount);
        td.textContent = panelData.textNoMessages || 'No hay envíos para este formulario todavía.';
        td.style.textAlign = 'center';
        tr.appendChild(td);
        tbodyElement.appendChild(tr);
    }

    /**
     * Maneja el clic en el botón de borrar un solo envío.
     */
    function handleDeleteSingleSubmission(event) {
        const button = event.target.closest('.glory-delete-single-submission');
        if (!button) return;

        if (!confirm(panelData.textConfirmDeleteSingle || '¿Seguro que quieres borrar este mensaje?')) {
            return;
        }

        const formId = button.dataset.formId;
        const submissionIndex = button.dataset.submissionIndex;
        const nonce = button.dataset.nonce;

        if (!formId || submissionIndex === undefined || !nonce) {
            alert(panelData.textErrorGeneric || 'Faltan datos para la operación.');
            return;
        }

        performAjaxDelete(panelData.deleteSingleAction, {form_id: formId, submission_index: submissionIndex, nonce: nonce}, button).then(response => {
            if (response.success) {
                const rowToDelete = button.closest('tr');
                if (rowToDelete) {
                    const tbody = rowToDelete.parentNode;
                    rowToDelete.remove();
                    // Verificar si el tbody está vacío
                    if (tbody && tbody.childElementCount === 0) {
                        const table = tbody.closest('.glory-submissions-table');
                        if (table) {
                            const headerCells = table.querySelectorAll('thead th');
                            showNoMessagesRow(tbody, headerCells.length);
                        }
                    }
                }
                // Podrías mostrar un mensaje de éxito temporal si lo deseas
            } else {
                const message = response.data && response.data.message ? response.data.message : panelData.textErrorGeneric || 'Error al borrar.';
                alert(message);
            }
        });
    }

    /**
     * Maneja el clic en el botón de borrar todos los envíos de un formulario.
     */
    function handleDeleteAllSubmissions(event) {
        const button = event.target.closest('.glory-delete-all-submissions');
        if (!button) return;

        if (!confirm(panelData.textConfirmDeleteAll || '¿Seguro que quieres borrar TODOS los mensajes de este formulario?')) {
            return;
        }

        const formId = button.dataset.formId;
        const nonce = button.dataset.nonce;

        if (!formId || !nonce) {
            alert(panelData.textErrorGeneric || 'Faltan datos para la operación.');
            return;
        }

        performAjaxDelete(panelData.deleteAllAction, {form_id: formId, nonce: nonce}, button).then(response => {
            if (response.success) {
                const table = document.querySelector(`.glory-submissions-table[data-form-id="${formId}"]`);
                if (table) {
                    const tbody = table.querySelector('tbody');
                    const headerCells = table.querySelectorAll('thead th');
                    if (tbody && headerCells.length > 0) {
                        showNoMessagesRow(tbody, headerCells.length);
                    }
                }
                // Podrías mostrar un mensaje de éxito temporal si lo deseas
            } else {
                const message = response.data && response.data.message ? response.data.message : panelData.textErrorGeneric || 'Error al borrar.';
                alert(message);
            }
        });
    }

    // Añadir event listeners usando delegación en un contenedor común si es posible,
    // o directamente en document.body si las tablas se cargan dinámicamente en toda la página.
    // Para este panel, los elementos deberían estar presentes al cargar.

    const adminWrap = document.querySelector('.wrap'); // Contenedor principal del panel de admin

    if (adminWrap) {
        adminWrap.addEventListener('click', event => {
            if (event.target.matches('.glory-delete-single-submission, .glory-delete-single-submission *')) {
                handleDeleteSingleSubmission(event);
            } else if (event.target.matches('.glory-delete-all-submissions, .glory-delete-all-submissions *')) {
                handleDeleteAllSubmissions(event);
            }
        });
    } else {
        // Fallback si .wrap no es el contenedor adecuado o no se encuentra, aunque debería estar.
        document.body.addEventListener('click', event => {
            if (event.target.matches('.glory-delete-single-submission, .glory-delete-single-submission *')) {
                handleDeleteSingleSubmission(event);
            } else if (event.target.matches('.glory-delete-all-submissions, .glory-delete-all-submissions *')) {
                handleDeleteAllSubmissions(event);
            }
        });
        console.warn("Glory Form Panel: No se encontró el contenedor '.wrap'. Usando document.body para la delegación de eventos.");
    }
});
