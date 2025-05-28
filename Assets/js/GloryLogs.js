// GloryLogs.js

document.addEventListener('DOMContentLoaded', function () {
    // Verificar si estamos en la página correcta
    const logsViewer = document.querySelector('.glory-logger-viewer');
    if (!logsViewer) {
        console.log("GloryLogs.js: No se encontró '.glory-logger-viewer'. Saliendo.");
        return;
    }

    const logsContainer = document.getElementById('glory-logs-container');
    const loadMoreButton = document.getElementById('glory-load-more');
    const loader = logsViewer.querySelector('.glory-loader');
    const applyFiltersButton = document.getElementById('apply-filters');
    const clearFiltersButton = document.getElementById('glory-clear-filters');
    const classFilterSelect = document.getElementById('filter-classes');
    const methodFilterSelect = document.getElementById('filter-methods');
    const levelFilterSelect = document.getElementById('filter-level');

    // --- INICIO: Comprobación de datos localizados ---
    if (typeof gloryLogsData === 'undefined' || !gloryLogsData || !gloryLogsData.ajax_url || !gloryLogsData.nonce) {
        console.error('GloryLogs.js CRITICAL ERROR: El objeto gloryLogsData o sus propiedades ajax_url/nonce no están definidos. Verifica la configuración de ScriptManager (wp_localize_script).');
        if (logsContainer) {
            logsContainer.innerHTML = '<p class="glory-no-logs" style="color:red; font-weight:bold;">Error Crítico: Faltan datos de configuración esenciales para cargar los logs. Revisa la consola del navegador para más detalles.</p>';
        }
        // Detener la ejecución adicional si los datos críticos no están presentes
        return;
    }
    console.log('GloryLogs.js: gloryLogsData encontrado:', gloryLogsData);
    // --- FIN: Comprobación de datos localizados ---

    const ajaxUrl = gloryLogsData.ajax_url;
    const nonce = gloryLogsData.nonce;

    let currentPage = 1;
    let isLoading = false;
    let hasMore = true;
    let currentFilters = {};
    let lastLogTimestampGMT = 0;
    let pollingInterval;

    // Helper para escapar HTML y prevenir XSS simple al renderizar (CORREGIDO)
    function escapeHtml(unsafe) {
        if (typeof unsafe !== 'string') {
            return unsafe === null || typeof unsafe === 'undefined' ? '' : String(unsafe);
        }
        return unsafe.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;'); // o &apos;
    }

    function renderLogEntry(log) {
        const statusClass = log.status ? log.status.toLowerCase() : 'info';
        let displayTitle = log.title || '';
        if (displayTitle.length > 100) {
            displayTitle = displayTitle.substring(0, 97) + '...';
        }

        return `
         <div class="glory-log-entry status-${statusClass}" data-log-id="${log.id}" data-timestamp-gmt="${log.timestamp_gmt}">
             <div class="glory-log-header">
                 <span class="glory-log-title" title="${escapeHtml(log.title)}">${escapeHtml(displayTitle)}</span>
                 <span class="glory-log-status status-${statusClass}">${escapeHtml(log.status)}</span>
                 <span class="glory-log-timestamp">${escapeHtml(log.timestamp)}</span>
             </div>
             <div class="glory-log-content">
              <div class="glory-log-body">${log.content}</div>
             </div>
         </div>
     `;
    }

    function fetchLogs(isPolling = false) {
        if (isLoading) return;
        isLoading = true;
        if (loader) loader.style.display = 'block';
        if (!isPolling && loadMoreButton) loadMoreButton.style.display = 'none';

        let ajaxData = {
            action: 'glory_get_logs',
            nonce: nonce,
            page: currentPage,
            filters: JSON.stringify(currentFilters)
        };

        if (isPolling && lastLogTimestampGMT > 0) {
            ajaxData.last_timestamp = lastLogTimestampGMT;
            delete ajaxData.page; // No necesitamos paginación para polling de nuevos
        }

        console.log('GloryLogs.js: Iniciando fetchLogs. Polling:', isPolling, 'Datos:', ajaxData);

        const formData = new URLSearchParams();
        for (const key in ajaxData) {
            formData.append(key, ajaxData[key]);
        }

        fetch(ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: formData
        })
            .then(response => {
                const responseClone = response.clone(); // Clonar para poder leer el cuerpo dos veces si es necesario

                if (!response.ok) {
                    // Si el estado HTTP no es OK (ej. 403, 404, 500)
                    return response.text().then(text => {
                        // Intenta obtener el cuerpo como texto
                        console.error(`GloryLogs.js: HTTP error! Status: ${response.status}. Raw Response Text (first 500 chars):`, text.substring(0, 500));
                        throw new Error(`Error HTTP ${response.status}. Respuesta del servidor (parcial): ${text.substring(0, 200)}`);
                    });
                }

                // Intentar parsear como JSON
                return response.json().catch(jsonError => {
                    // Si response.json() falla, es porque no es JSON válido.
                    // Usamos la respuesta clonada para obtener el texto crudo.
                    return responseClone.text().then(text => {
                        console.error('GloryLogs.js: Error al parsear JSON.', jsonError);
                        console.error('GloryLogs.js: Respuesta cruda del servidor (first 500 chars):', text.substring(0, 500));
                        // Construye un error más descriptivo
                        throw new SyntaxError(`Respuesta del servidor no es JSON válido. Comienzo de la respuesta: "${text.substring(0, 100)}..."`);
                    });
                });
            })
            .then(data => {
                // 'data' aquí es el JSON parseado exitosamente
                console.log('GloryLogs.js: Respuesta JSON recibida:', data);
                if (data.success) {
                    if (isPolling) {
                        if (data.data.logs && data.data.logs.length > 0) {
                            // Invertir para que el más nuevo quede arriba (los logs de polling vienen ASC)
                            data.data.logs.reverse().forEach(log => {
                                logsContainer.insertAdjacentHTML('afterbegin', renderLogEntry(log));
                            });
                            if (data.data.newest_log_timestamp_gmt > lastLogTimestampGMT) {
                                lastLogTimestampGMT = data.data.newest_log_timestamp_gmt;
                            }
                        }
                    } else {
                        // Carga inicial o "Cargar más"
                        if (currentPage === 1) {
                            logsContainer.querySelectorAll('.glory-log-entry, .glory-no-logs').forEach(el => el.remove());
                        }
                        if (data.data.logs && data.data.logs.length > 0) {
                            data.data.logs.forEach(log => {
                                logsContainer.insertAdjacentHTML('beforeend', renderLogEntry(log));
                            });
                            if (currentPage === 1 && data.data.newest_log_timestamp_gmt > 0) {
                                lastLogTimestampGMT = data.data.newest_log_timestamp_gmt;
                            }
                            hasMore = data.data.has_more;
                            if (hasMore && loadMoreButton) {
                                loadMoreButton.style.display = 'block';
                            } else if (loadMoreButton) {
                                loadMoreButton.style.display = 'none';
                            }
                        } else if (currentPage === 1) {
                            logsContainer.insertAdjacentHTML('beforeend', '<p class="glory-no-logs">No se encontraron logs con los filtros actuales.</p>');
                            if (loadMoreButton) loadMoreButton.style.display = 'none';
                        } else if (loadMoreButton) {
                            // No hay más logs para "cargar más"
                            loadMoreButton.style.display = 'none';
                        }
                    }
                } else {
                    // data.success es false
                    const errorMessage = data.data ? data.data.message || (typeof data.data === 'string' ? data.data : 'Error desconocido desde el servidor.') : 'Error desconocido desde el servidor.';
                    console.error('GloryLogs.js: Error reportado por el servidor (success:false):', errorMessage, 'Datos completos:', data.data);
                    logsContainer.insertAdjacentHTML('beforeend', `<p class="glory-no-logs">Error al cargar logs: ${escapeHtml(errorMessage)}</p>`);
                }
            })
            .catch(error => {
                // Este catch maneja errores de red, errores lanzados por !response.ok,
                // o errores lanzados por el fallo de response.json() y el posterior throw.
                console.error('GloryLogs.js: Fetch Error General:', error.name, error.message, error);
                logsContainer.insertAdjacentHTML('beforeend', `<p class="glory-no-logs">Error de conexión o respuesta inesperada: ${escapeHtml(error.message)}</p>`);
            })
            .finally(() => {
                isLoading = false;
                if (loader) loader.style.display = 'none';
            });
    }

    function getSelectedOptions(selectElement) {
        if (!selectElement) return [];
        return Array.from(selectElement.selectedOptions).map(option => option.value);
    }

    function clearMultiSelect(selectElement) {
        if (!selectElement) return;
        Array.from(selectElement.options).forEach(option => (option.selected = false));
        // Para selectores nativos, esto es suficiente.
        // Si usas librerías como Select2/Choices.js, podrías necesitar `$(selectElement).trigger('change');` o similar.
    }

    function applyAndFetchFilters() {
        currentFilters = {
            classes: getSelectedOptions(classFilterSelect),
            methods: getSelectedOptions(methodFilterSelect),
            level: levelFilterSelect ? levelFilterSelect.value : 'all'
        };
        currentPage = 1;
        lastLogTimestampGMT = 0; // Resetear para que el polling obtenga todo lo nuevo si los filtros cambian
        if (loadMoreButton) loadMoreButton.style.display = 'none'; // Ocultar hasta que sepamos si hay más
        fetchLogs();
    }

    if (applyFiltersButton) {
        applyFiltersButton.addEventListener('click', applyAndFetchFilters);
    }

    if (clearFiltersButton) {
        clearFiltersButton.addEventListener('click', function () {
            clearMultiSelect(classFilterSelect);
            clearMultiSelect(methodFilterSelect);
            if (levelFilterSelect) levelFilterSelect.value = 'all';
            // Si estás usando TomSelect o similar para los selects, necesitas usar su API para limpiar:
            // if (classFilterSelect.tomselect) classFilterSelect.tomselect.clear();
            // if (methodFilterSelect.tomselect) methodFilterSelect.tomselect.clear();
            applyAndFetchFilters();
        });
    }

    if (loadMoreButton) {
        loadMoreButton.addEventListener('click', function () {
            if (!isLoading && hasMore) {
                currentPage++;
                fetchLogs();
            }
        });
    }

    if (logsContainer) {
        logsContainer.addEventListener('click', function (event) {
            const header = event.target.closest('.glory-log-header');
            if (header) {
                const entry = header.closest('.glory-log-entry');
                if (entry) {
                    entry.classList.toggle('expanded');
                }
            }
        });
    }

    // Carga inicial
    fetchLogs();

    // Polling
    function startPolling() {
        stopPolling(); // Asegurarse de que no haya intervalos duplicados
        pollingInterval = setInterval(function () {
            fetchLogs(true);
        }, 10000); // 10 segundos
        console.log('GloryLogs.js: Polling iniciado. Interval ID:', pollingInterval);
    }

    function stopPolling() {
        if (pollingInterval) {
            clearInterval(pollingInterval);
            console.log('GloryLogs.js: Polling detenido. Interval ID:', pollingInterval);
            pollingInterval = null;
        }
    }

    startPolling();

    // Manejo de visibilidad de la pestaña
    document.addEventListener('visibilitychange', function () {
        if (document.hidden) {
            console.log('GloryLogs.js: Pestaña oculta, deteniendo polling.');
            stopPolling();
        } else {
            console.log('GloryLogs.js: Pestaña visible, reiniciando polling y buscando logs inmediatamente.');
            fetchLogs(true); // Busca inmediatamente al volver
            startPolling();
        }
    });
});
