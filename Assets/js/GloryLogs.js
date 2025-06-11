// GloryLogs.js - Refactorizado por Jules para modularidad y legibilidad (@wandorius)

const GloryLogsApp = {
    // Propiedades del DOM y de estado
    logsViewer: null,
    logsContainer: null,
    loadMoreButton: null,
    loader: null,
    applyFiltersButton: null,
    clearFiltersButton: null,
    classFilterSelect: null,
    methodFilterSelect: null,
    levelFilterSelect: null,

    ajaxUrl: '',
    nonce: '',
    currentPage: 1,
    isLoading: false,
    hasMore: true,
    currentFilters: {},
    lastLogTimestampGMT: 0,
    pollingInterval: null,

    /**
     * Método principal de inicialización.
     * @Comentario Jules: Orquesta la inicialización de la aplicación de logs.
     */
    init: function () {
        console.log("GloryLogsApp: Iniciando...");
        if (!this.inicializarVariablesYDom()) {
            // Si la inicialización crítica falla (ej. falta gloryLogsData), no continuar.
            return;
        }
        this.registrarManejadoresEventos();
        this.fetchLogs(); // Carga inicial de logs
        this.gestionarPollingVisualizacion();
        console.log("GloryLogsApp: Inicialización completada.");
    },

    /**
     * Inicializa las variables de estado y las referencias a elementos del DOM.
     * Realiza comprobaciones críticas para la configuración.
     * @Comentario Jules: Inicializa las variables de estado y las referencias a elementos del DOM.
     * @return {boolean} True si la inicialización es exitosa, false en caso de error crítico.
     */
    inicializarVariablesYDom: function () {
        this.logsViewer = document.querySelector('.glory-logger-viewer');
        if (!this.logsViewer) {
            // Este es un caso donde la app no debería ni intentar correr si el visor principal no está.
            console.log("GloryLogsApp: No se encontró '.glory-logger-viewer'. La aplicación no se iniciará.");
            return false;
        }

        this.logsContainer = document.getElementById('glory-logs-container');
        this.loadMoreButton = document.getElementById('glory-load-more');
        this.loader = this.logsViewer.querySelector('.glory-loader');
        this.applyFiltersButton = document.getElementById('apply-filters');
        this.clearFiltersButton = document.getElementById('glory-clear-filters');
        this.classFilterSelect = document.getElementById('filter-classes');
        this.methodFilterSelect = document.getElementById('filter-methods');
        this.levelFilterSelect = document.getElementById('filter-level');

        if (typeof gloryLogsData === 'undefined' || !gloryLogsData || !gloryLogsData.ajax_url || !gloryLogsData.nonce) {
            console.error('GloryLogsApp CRITICAL ERROR: El objeto gloryLogsData o sus propiedades ajax_url/nonce no están definidos.');
            this.mostrarErrorCritico('Error Crítico: Faltan datos de configuración esenciales para cargar los logs. Revisa la consola del navegador para más detalles.');
            return false; // Indica fallo crítico
        }
        console.log('GloryLogsApp: gloryLogsData encontrado:', gloryLogsData);

        this.ajaxUrl = gloryLogsData.ajax_url;
        this.nonce = gloryLogsData.nonce;

        // Inicializar propiedades de estado
        this.currentPage = 1;
        this.isLoading = false;
        this.hasMore = true;
        this.currentFilters = {};
        this.lastLogTimestampGMT = 0;
        this.pollingInterval = null;

        return true; // Inicialización exitosa
    },

    /**
     * Muestra un mensaje de error crítico en el contenedor de logs.
     * @param {string} mensaje El mensaje de error a mostrar.
     * @Comentario Jules: Muestra un mensaje de error crítico en el contenedor de logs.
     */
    mostrarErrorCritico: function (mensaje) {
        if (this.logsContainer) {
            this.logsContainer.innerHTML = `<p class="glory-no-logs" style="color:red; font-weight:bold;">${this.escapeHtml(mensaje)}</p>`;
        } else if (this.logsViewer) { // Fallback si logsContainer no existe pero el visor sí
            this.logsViewer.innerHTML = `<p class="glory-no-logs" style="color:red; font-weight:bold;">${this.escapeHtml(mensaje)}</p>`;
        }
    },

    /**
     * Escapa caracteres HTML para prevenir XSS simple.
     * @param {*} unsafe El valor a escapar. Si no es string, se convierte.
     * @return {string} La cadena escapada.
     * @Comentario Jules: Escapa caracteres HTML para prevenir XSS simple.
     */
    escapeHtml: function (unsafe) {
        if (typeof unsafe !== 'string') {
            return unsafe === null || typeof unsafe === 'undefined' ? '' : String(unsafe);
        }
        return unsafe.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    },

    /**
     * Genera el HTML para una única entrada de log.
     * @param {object} log El objeto de log con datos.
     * @return {string} El HTML de la entrada del log.
     * @Comentario Jules: Genera el HTML para una única entrada de log.
     */
    renderizarEntradaLog: function (log) {
        const statusClass = log.status ? String(log.status).toLowerCase() : 'info';
        let displayTitle = log.title || '';
        if (displayTitle.length > 100) {
            displayTitle = displayTitle.substring(0, 97) + '...';
        }

        // El contenido del log (log.content) puede ser HTML complejo y no debe ser escapado aquí.
        // Se asume que el contenido ya viene sanitizado o es seguro por naturaleza desde el servidor.
        // Si el contenido pudiera tener XSS, necesitaría un saneador de HTML más robusto del lado del cliente o servidor.
        return `
         <div class="glory-log-entry status-${statusClass}" data-log-id="${this.escapeHtml(log.id)}" data-timestamp-gmt="${this.escapeHtml(log.timestamp_gmt)}">
             <div class="glory-log-header">
                 <span class="glory-log-title" title="${this.escapeHtml(log.title)}">${this.escapeHtml(displayTitle)}</span>
                 <span class="glory-log-status status-${statusClass}">${this.escapeHtml(log.status)}</span>
                 <span class="glory-log-timestamp">${this.escapeHtml(log.timestamp)}</span>
             </div>
             <div class="glory-log-content">
              <div class="glory-log-body">${log.content}</div>
             </div>
         </div>
     `;
    },

    /**
     * Muestra u oculta un elemento del DOM.
     * @param {HTMLElement} elemento El elemento del DOM a mostrar/ocultar.
     * @param {boolean} mostrar True para mostrar, false para ocultar.
     * @Comentario Jules: Muestra u oculta un elemento del DOM.
     */
    alternarVisibilidad: function (elemento, mostrar) {
        if (elemento) {
            elemento.style.display = mostrar ? 'block' : 'none';
        }
    },

    /**
     * Procesa los datos de logs recibidos del servidor y actualiza el DOM.
     * @param {object} data La respuesta JSON del servidor.
     * @param {boolean} esPolling Indica si la llamada fue originada por el polling.
     * @Comentario Jules: Procesa los datos de logs recibidos del servidor y actualiza el DOM.
     */
    procesarRespuestaLogs: function (data, esPolling) {
        console.log('GloryLogsApp: Procesando respuesta. Polling:', esPolling, 'Datos:', data);
        if (data.success) {
            if (esPolling) {
                if (data.data.logs && data.data.logs.length > 0) {
                    // Invertir para que el más nuevo quede arriba (los logs de polling vienen ASC)
                    data.data.logs.reverse().forEach(log => {
                        this.logsContainer.insertAdjacentHTML('afterbegin', this.renderizarEntradaLog(log));
                    });
                    if (data.data.newest_log_timestamp_gmt > this.lastLogTimestampGMT) {
                        this.lastLogTimestampGMT = data.data.newest_log_timestamp_gmt;
                    }
                }
            } else {
                // Carga inicial o "Cargar más"
                if (this.currentPage === 1) {
                    this.logsContainer.querySelectorAll('.glory-log-entry, .glory-no-logs').forEach(el => el.remove());
                }
                if (data.data.logs && data.data.logs.length > 0) {
                    data.data.logs.forEach(log => {
                        this.logsContainer.insertAdjacentHTML('beforeend', this.renderizarEntradaLog(log));
                    });
                    if (this.currentPage === 1 && data.data.newest_log_timestamp_gmt > 0) {
                        this.lastLogTimestampGMT = data.data.newest_log_timestamp_gmt;
                    }
                    this.hasMore = data.data.has_more;
                    this.alternarVisibilidad(this.loadMoreButton, this.hasMore);
                } else if (this.currentPage === 1) {
                    this.logsContainer.insertAdjacentHTML('beforeend', '<p class="glory-no-logs">No se encontraron logs con los filtros actuales.</p>');
                    this.alternarVisibilidad(this.loadMoreButton, false);
                } else {
                    // No hay más logs para "cargar más"
                    this.alternarVisibilidad(this.loadMoreButton, false);
                     this.hasMore = false; // Asegurar que hasMore esté en false
                }
            }
        } else {
            // data.success es false
            const errorMessage = data.data ? (data.data.message || (typeof data.data === 'string' ? data.data : 'Error desconocido desde el servidor.')) : 'Error desconocido desde el servidor.';
            console.error('GloryLogsApp: Error reportado por el servidor (success:false):', errorMessage, 'Datos completos:', data.data);
            // Mostrar el error en el contenedor, pero no limpiar logs existentes si es polling o carga más.
            const errorHtml = `<p class="glory-no-logs" style="color:red;">Error al cargar logs: ${this.escapeHtml(errorMessage)}</p>`;
            if (this.currentPage === 1 && !esPolling) { // Solo limpiar si es carga inicial y falla
                this.logsContainer.innerHTML = errorHtml;
            } else {
                this.logsContainer.insertAdjacentHTML('beforeend', errorHtml);
            }
        }
    },

    /**
     * Realiza la petición AJAX para obtener los logs del servidor.
     * @param {boolean} esPolling Indica si la llamada es para polling de nuevos logs.
     * @Comentario Jules: Realiza la petición AJAX para obtener los logs del servidor.
     */
    fetchLogs: function (esPolling = false) {
        if (this.isLoading && !esPolling) { // Permitir polling incluso si una carga manual está en curso, pero no múltiples cargas manuales
             console.log('GloryLogsApp: fetchLogs abortado, isLoading es true y no es polling.');
            return;
        }
        this.isLoading = true;
        this.alternarVisibilidad(this.loader, true);
        if (!esPolling) { // Para carga normal o "cargar más"
            this.alternarVisibilidad(this.loadMoreButton, false);
        }


        let ajaxData = {
            action: 'glory_get_logs',
            nonce: this.nonce,
            page: this.currentPage,
            filters: JSON.stringify(this.currentFilters)
        };

        if (esPolling && this.lastLogTimestampGMT > 0) {
            ajaxData.last_timestamp = this.lastLogTimestampGMT;
            delete ajaxData.page; // No necesitamos paginación para polling de nuevos
        }

        console.log('GloryLogsApp: Iniciando fetchLogs. Polling:', esPolling, 'Datos:', ajaxData);

        const formData = new URLSearchParams();
        for (const key in ajaxData) {
            formData.append(key, ajaxData[key]);
        }

        fetch(this.ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: formData
        })
        .then(response => {
            const responseClone = response.clone();
            if (!response.ok) {
                return response.text().then(text => {
                    console.error(`GloryLogsApp: HTTP error! Status: ${response.status}. Raw Response Text (first 500 chars):`, text.substring(0, 500));
                    throw new Error(`Error HTTP ${response.status}. Respuesta del servidor (parcial): ${text.substring(0, 200)}`);
                });
            }
            return response.json().catch(jsonError => {
                return responseClone.text().then(text => {
                    console.error('GloryLogsApp: Error al parsear JSON.', jsonError);
                    console.error('GloryLogsApp: Respuesta cruda del servidor (first 500 chars):', text.substring(0, 500));
                    throw new SyntaxError(`Respuesta del servidor no es JSON válido. Comienzo de la respuesta: "${text.substring(0, 100)}..."`);
                });
            });
        })
        .then(data => {
            this.procesarRespuestaLogs(data, esPolling);
        })
        .catch(error => {
            console.error('GloryLogsApp: Fetch Error General:', error.name, error.message, error);
            const errorHtml = `<p class="glory-no-logs" style="color:red;">Error de conexión o respuesta inesperada: ${this.escapeHtml(error.message)}</p>`;
            if (this.currentPage === 1 && !esPolling && this.logsContainer) {
                 this.logsContainer.innerHTML = errorHtml;
            } else if (this.logsContainer) {
                 this.logsContainer.insertAdjacentHTML('beforeend', errorHtml);
            }
        })
        .finally(() => {
            this.isLoading = false;
            this.alternarVisibilidad(this.loader, false);
            // Restaurar el botón "Cargar más" solo si hay más y no es polling
             if (!esPolling && this.hasMore) {
                this.alternarVisibilidad(this.loadMoreButton, true);
            }
        });
    },

    /**
     * Obtiene los valores de las opciones seleccionadas de un elemento select múltiple.
     * @param {HTMLSelectElement} selectElement El elemento select.
     * @return {string[]} Array de valores seleccionados.
     * @Comentario Jules: Obtiene los valores de las opciones seleccionadas de un elemento select múltiple.
     */
    obtenerOpcionesSeleccionadas: function (selectElement) {
        if (!selectElement) return [];
        return Array.from(selectElement.selectedOptions).map(option => option.value);
    },

    /**
     * Deselecciona todas las opciones de un elemento select múltiple.
     * @param {HTMLSelectElement} selectElement El elemento select.
     * @Comentario Jules: Deselecciona todas las opciones de un elemento select múltiple.
     */
    limpiarSeleccionMultiple: function (selectElement) {
        if (!selectElement) return;
        Array.from(selectElement.options).forEach(option => (option.selected = false));
        // Si se usan librerías JS para selectores (TomSelect, Select2), se necesitaría su API:
        // if (selectElement.tomselect) selectElement.tomselect.clear();
    },

    /**
     * Recopila los filtros seleccionados, actualiza el estado y solicita nuevos logs.
     * @Comentario Jules: Recopila los filtros seleccionados, actualiza el estado y solicita nuevos logs.
     */
    aplicarYObtenerFiltros: function () {
        console.log("GloryLogsApp: Aplicando filtros...");
        this.currentFilters = {
            classes: this.obtenerOpcionesSeleccionadas(this.classFilterSelect),
            methods: this.obtenerOpcionesSeleccionadas(this.methodFilterSelect),
            level: this.levelFilterSelect ? this.levelFilterSelect.value : 'all'
        };
        this.currentPage = 1;
        this.lastLogTimestampGMT = 0; // Resetear para que el polling también se reinicie con los nuevos filtros
        this.hasMore = true; // Asumir que hay más hasta que el fetch lo confirme
        // this.alternarVisibilidad(this.loadMoreButton, false); // Ocultar hasta saber si hay más
        if (this.logsContainer) this.logsContainer.innerHTML = ''; // Limpiar logs actuales
        this.fetchLogs();
    },

    /**
     * Registra todos los manejadores de eventos para los elementos interactivos.
     * @Comentario Jules: Registra todos los manejadores de eventos para los elementos interactivos.
     */
    registrarManejadoresEventos: function () {
        if (this.applyFiltersButton) {
            this.applyFiltersButton.addEventListener('click', () => this.aplicarYObtenerFiltros());
        }

        if (this.clearFiltersButton) {
            this.clearFiltersButton.addEventListener('click', () => {
                this.limpiarSeleccionMultiple(this.classFilterSelect);
                this.limpiarSeleccionMultiple(this.methodFilterSelect);
                if (this.levelFilterSelect) this.levelFilterSelect.value = 'all';

                // Si se usan librerías JS para selectores (TomSelect, Select2), se necesitaría su API:
                // if (this.classFilterSelect && this.classFilterSelect.tomselect) this.classFilterSelect.tomselect.clear();
                // if (this.methodFilterSelect && this.methodFilterSelect.tomselect) this.methodFilterSelect.tomselect.clear();

                this.aplicarYObtenerFiltros();
            });
        }

        if (this.loadMoreButton) {
            this.loadMoreButton.addEventListener('click', () => {
                if (!this.isLoading && this.hasMore) {
                    this.currentPage++;
                    this.fetchLogs();
                }
            });
        }

        if (this.logsContainer) {
            this.logsContainer.addEventListener('click', function (event) {
                const header = event.target.closest('.glory-log-header');
                if (header) {
                    const entry = header.closest('.glory-log-entry');
                    if (entry) {
                        entry.classList.toggle('expanded');
                    }
                }
            });
        }
    },

    /**
     * Administra el polling para nuevos logs y su comportamiento con la visibilidad de la pestaña.
     * @Comentario Jules: Administra el polling para nuevos logs y su comportamiento con la visibilidad de la pestaña.
     */
    gestionarPollingVisualizacion: function () {
        this.startPolling(); // Iniciar polling al cargar

        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                console.log('GloryLogsApp: Pestaña oculta, deteniendo polling.');
                this.stopPolling();
            } else {
                console.log('GloryLogsApp: Pestaña visible, reiniciando polling y buscando logs inmediatamente.');
                this.fetchLogs(true); // Busca inmediatamente al volver
                this.startPolling();
            }
        });
    },

    /**
     * Inicia el intervalo de polling para buscar nuevos logs.
     * @Comentario Jules: Inicia el polling.
     */
    startPolling: function () {
        this.stopPolling(); // Asegurarse de que no haya intervalos duplicados
        this.pollingInterval = setInterval(() => {
            this.fetchLogs(true);
        }, 10000); // 10 segundos
        console.log('GloryLogsApp: Polling iniciado. Interval ID:', this.pollingInterval);
    },

    /**
     * Detiene el intervalo de polling.
     * @Comentario Jules: Detiene el polling.
     */
    stopPolling: function () {
        if (this.pollingInterval) {
            clearInterval(this.pollingInterval);
            console.log('GloryLogsApp: Polling detenido. Interval ID:', this.pollingInterval);
            this.pollingInterval = null;
        }
    }
};

document.addEventListener('DOMContentLoaded', function () {
    // Solo inicializar si el contenedor principal está presente.
    // La comprobación interna de GloryLogsApp.init (via inicializarVariablesYDom) es más específica.
    if (document.querySelector('.glory-logger-viewer')) {
        GloryLogsApp.init();
    } else {
        console.log("GloryLogs.js (DOMContentLoaded): No se encontró '.glory-logger-viewer'. GloryLogsApp no se iniciará.");
    }
});
