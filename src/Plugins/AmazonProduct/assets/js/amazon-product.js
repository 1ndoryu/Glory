/**
 * Amazon Product Plugin - Sistema de Paginacion y Filtros Robusto
 *
 * Caracteristicas:
 * - Reinicializable (compatible con navegacion AJAX via gloryRecarga)
 * - Proteccion contra multiples inicializaciones
 * - Manejo robusto de eventos con debounce
 * - Logging de debug cuando gloryDebug esta activo
 * - A prueba de race conditions
 */
(function () {
    'use strict';

    /*
     * Logging condicional para debugging
     */
    const log = (...args) => {
        if (typeof window !== 'undefined' && window.gloryDebug) {
            console.log('[AmazonProduct]', ...args);
        }
    };

    /*
     * Generador de ID unico para instancias
     */
    let instanceId = 0;
    const generateInstanceId = () => `amazon-instance-${++instanceId}`;

    /*
     * Funcion debounce mejorada con cancelacion
     */
    function debounce(func, wait) {
        let timeout = null;

        const debouncedFn = function (...args) {
            if (timeout) clearTimeout(timeout);
            timeout = setTimeout(() => {
                timeout = null;
                func.apply(this, args);
            }, wait);
        };

        debouncedFn.cancel = () => {
            if (timeout) {
                clearTimeout(timeout);
                timeout = null;
            }
        };

        return debouncedFn;
    }

    /*
     * Clase principal del controlador Amazon
     * Encapsula todo el estado y comportamiento
     */
    class AmazonProductController {
        constructor(wrapper) {
            this.wrapper = wrapper;
            this.instanceId = generateInstanceId();
            this.isLoading = false;
            this.abortController = null;

            // Referencias a elementos del DOM
            this.gridContainer = wrapper.querySelector('.amazon-product-grid-container');
            this.loader = wrapper.querySelector('.amazon-loader');
            this.filterPanel = document.getElementById('amazon-filter-panel');
            this.toggleBtn = document.getElementById('amazon-toggle-filters');
            this.totalCount = document.getElementById('amazon-total-count');

            // Estado inicial desde data attributes
            this.state = {
                limit: wrapper.dataset.limit || 12,
                paged: 1,
                search: wrapper.dataset.search || '',
                category: wrapper.dataset.category || '',
                minPrice: wrapper.dataset.minPrice || '',
                maxPrice: wrapper.dataset.maxPrice || '',
                minRating: wrapper.dataset.minRating || '',
                onlyPrime: wrapper.dataset.onlyPrime || '',
                onlyDeals: wrapper.dataset.onlyDeals || '',
                orderby: wrapper.dataset.orderby || 'date',
                order: wrapper.dataset.order || 'DESC',
                exclude: wrapper.dataset.exclude || '',
                /*
                 * Semilla para orden aleatorio consistente.
                 * Se genera en PHP al cargar la pagina y se mantiene durante
                 * toda la sesion de navegacion para evitar productos repetidos.
                 */
                randomSeed: wrapper.dataset.randomSeed || ''
            };

            // Debounced fetch para busqueda
            this.debouncedFetch = debounce(() => this.fetchProducts(), 500);

            // Inicializar contador total
            if (this.totalCount && wrapper.dataset.totalCount) {
                this.totalCount.textContent = wrapper.dataset.totalCount;
            }

            // Binding de metodos para preservar contexto
            this.handlePaginationClick = this.handlePaginationClick.bind(this);
            this.handleSearchInput = this.handleSearchInput.bind(this);
            this.handlePriceChange = this.handlePriceChange.bind(this);
            this.handleRatingClick = this.handleRatingClick.bind(this);
            this.handleCategoryClick = this.handleCategoryClick.bind(this);
            this.handlePrimeChange = this.handlePrimeChange.bind(this);
            this.handleDealsChange = this.handleDealsChange.bind(this);
            this.handleSortChange = this.handleSortChange.bind(this);
            this.handleResetClick = this.handleResetClick.bind(this);
            this.handleClearSearch = this.handleClearSearch.bind(this);

            log(`Instancia creada: ${this.instanceId}`);
        }

        /*
         * Inicializa todos los event listeners
         */
        init() {
            this.bindPaginationEvents();
            this.bindFilterEvents();
            this.bindSearchEvents();
            this.bindSortEvents();
            this.bindResetEvents();

            log(`Instancia inicializada: ${this.instanceId}`);
        }

        /*
         * Limpia todos los event listeners (para reinicializacion)
         */
        destroy() {
            // Cancelar cualquier fetch pendiente
            if (this.abortController) {
                this.abortController.abort();
                this.abortController = null;
            }

            // Cancelar debounce pendiente
            this.debouncedFetch.cancel();

            // Remover listeners de paginacion
            this.wrapper.removeEventListener('click', this.handlePaginationClick);

            log(`Instancia destruida: ${this.instanceId}`);
        }

        /*
         * Evento principal de paginacion
         * Usa event delegation en el wrapper para capturar clicks en botones de paginacion
         */
        bindPaginationEvents() {
            // Usamos captura en fase de captura (true) para asegurar que recibimos el evento
            // antes que cualquier otro script pueda detenerlo
            this.wrapper.addEventListener('click', this.handlePaginationClick, true);
        }

        handlePaginationClick(e) {
            // Buscar si el click fue en un boton/link de paginacion
            const pageBtn = e.target.closest('.amazon-pagination .page-numbers');

            if (!pageBtn) return;

            log('Click en paginacion detectado', pageBtn);

            // Prevenir comportamiento por defecto y propagacion
            e.preventDefault();
            e.stopPropagation();

            // Obtener numero de pagina
            const page = parseInt(pageBtn.dataset.page, 10);

            // Validaciones
            if (isNaN(page)) {
                log('Pagina invalida:', pageBtn.dataset.page);
                return;
            }

            if (pageBtn.classList.contains('current') || pageBtn.disabled) {
                log('Click en pagina actual, ignorando');
                return;
            }

            if (this.isLoading) {
                log('Fetch en progreso, ignorando click');
                return;
            }

            log(`Navegando a pagina ${page}`);
            this.state.paged = page;
            this.fetchProducts();

            // Scroll suave hacia el wrapper
            this.wrapper.scrollIntoView({behavior: 'smooth', block: 'start'});
        }

        bindFilterEvents() {
            // Rating buttons
            const ratingButtons = this.wrapper.querySelectorAll('.amazon-rating-btn');
            ratingButtons.forEach(btn => {
                btn.addEventListener('click', this.handleRatingClick);
            });

            // Category buttons
            const categoryButtons = this.wrapper.querySelectorAll('.amazon-category-btn');
            categoryButtons.forEach(btn => {
                btn.addEventListener('click', this.handleCategoryClick);
            });

            // Prime checkbox
            const primeCheckbox = document.getElementById('amazon-prime');
            if (primeCheckbox) {
                primeCheckbox.addEventListener('change', this.handlePrimeChange);
            }

            // Deals checkbox
            const dealsCheckbox = document.getElementById('amazon-deals');
            if (dealsCheckbox) {
                dealsCheckbox.addEventListener('change', this.handleDealsChange);
            }

            // Price range
            const priceRange = document.getElementById('amazon-max-price-range');
            const priceDisplay = document.getElementById('price-display');
            if (priceRange) {
                priceRange.addEventListener('input', function () {
                    if (priceDisplay) priceDisplay.textContent = this.value;
                });
                priceRange.addEventListener('change', this.handlePriceChange);
            }
        }

        handleRatingClick(e) {
            const btn = e.currentTarget;
            const rating = btn.dataset.rating;
            const ratingButtons = this.wrapper.querySelectorAll('.amazon-rating-btn');

            if (btn.classList.contains('active')) {
                btn.classList.remove('active');
                this.state.minRating = '';
            } else {
                ratingButtons.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                this.state.minRating = rating;
            }

            this.state.paged = 1;
            this.fetchProducts();
        }

        handleCategoryClick(e) {
            const btn = e.currentTarget;
            const slug = btn.dataset.slug;
            const categoryButtons = this.wrapper.querySelectorAll('.amazon-category-btn');

            if (btn.classList.contains('active')) {
                btn.classList.remove('active');
                this.state.category = '';
            } else {
                categoryButtons.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                this.state.category = slug;
            }

            this.state.paged = 1;
            this.fetchProducts();
        }

        handlePrimeChange(e) {
            this.state.onlyPrime = e.target.checked ? '1' : '';
            this.state.paged = 1;
            this.fetchProducts();
        }

        handleDealsChange(e) {
            this.state.onlyDeals = e.target.checked ? '1' : '';
            this.state.paged = 1;
            this.fetchProducts();
        }

        handlePriceChange(e) {
            this.state.maxPrice = e.target.value;
            this.state.paged = 1;
            this.fetchProducts();
        }

        bindSearchEvents() {
            const searchInput = document.getElementById('amazon-search');
            if (searchInput) {
                searchInput.addEventListener('input', this.handleSearchInput);
            }
        }

        handleSearchInput(e) {
            this.state.search = e.target.value;
            this.state.paged = 1;
            this.debouncedFetch();
        }

        bindSortEvents() {
            const sortSelect = document.getElementById('amazon-sort');
            if (sortSelect) {
                sortSelect.addEventListener('change', this.handleSortChange);
            }
        }

        handleSortChange(e) {
            const [orderby, order] = e.target.value.split('-');
            this.state.orderby = orderby;
            this.state.order = order;
            this.state.paged = 1;

            /*
             * Si cambia a ordenamiento aleatorio, generar nuevo seed.
             * Esto asegura un nuevo orden aleatorio, pero consistente
             * durante la paginacion subsecuente.
             */
            if (orderby === 'random') {
                this.state.randomSeed = Math.floor(Math.random() * 999999999);
            }

            this.fetchProducts();
        }

        bindResetEvents() {
            const resetBtn = document.getElementById('amazon-reset-filters');
            if (resetBtn) {
                resetBtn.addEventListener('click', this.handleResetClick);
            }

            // Evento delegado para boton de limpiar busqueda
            document.addEventListener('click', this.handleClearSearch);
        }

        handleResetClick() {
            // Reset UI elements
            const searchInput = document.getElementById('amazon-search');
            const priceRange = document.getElementById('amazon-max-price-range');
            const priceDisplay = document.getElementById('price-display');
            const primeCheckbox = document.getElementById('amazon-prime');
            const dealsCheckbox = document.getElementById('amazon-deals');
            const sortSelect = document.getElementById('amazon-sort');
            const ratingButtons = this.wrapper.querySelectorAll('.amazon-rating-btn');
            const categoryButtons = this.wrapper.querySelectorAll('.amazon-category-btn');

            if (searchInput) searchInput.value = '';
            if (priceRange) {
                priceRange.value = 2000;
                if (priceDisplay) priceDisplay.textContent = '2000';
            }
            ratingButtons.forEach(b => b.classList.remove('active'));
            categoryButtons.forEach(b => b.classList.remove('active'));
            if (primeCheckbox) primeCheckbox.checked = false;
            if (dealsCheckbox) dealsCheckbox.checked = false;
            if (sortSelect) sortSelect.value = 'date-DESC';

            // Reset state
            this.state.paged = 1;
            this.state.search = '';
            this.state.category = '';
            this.state.minPrice = '';
            this.state.maxPrice = '';
            this.state.minRating = '';
            this.state.onlyPrime = '';
            this.state.onlyDeals = '';
            this.state.orderby = 'date';
            this.state.order = 'DESC';

            this.fetchProducts();
        }

        handleClearSearch(e) {
            if (e.target && e.target.id === 'amazon-clear-search') {
                const resetBtn = document.getElementById('amazon-reset-filters');
                if (resetBtn) resetBtn.click();
            }
        }

        /*
         * Realiza peticion AJAX para obtener productos
         * Con proteccion contra race conditions y cancelacion de peticiones previas
         */
        fetchProducts() {
            if (!this.gridContainer) {
                log('Error: gridContainer no encontrado');
                return;
            }

            // Cancelar peticion anterior si existe
            if (this.abortController) {
                this.abortController.abort();
                log('Peticion anterior cancelada');
            }

            // Crear nuevo controller para esta peticion
            this.abortController = new AbortController();
            this.isLoading = true;

            // UI: mostrar estado de carga
            this.gridContainer.style.opacity = '0.5';
            this.gridContainer.style.pointerEvents = 'none';
            if (this.loader) this.loader.style.display = 'flex';

            log('Iniciando fetch con estado:', {...this.state});

            // Verificar que amazonProductAjax existe
            if (typeof amazonProductAjax === 'undefined') {
                console.error('[AmazonProduct] Error: amazonProductAjax no definido');
                this.resetLoadingState();
                return;
            }

            // Construir FormData
            const formData = new FormData();
            formData.append('action', 'amazon_filter_products');
            formData.append('nonce', amazonProductAjax.nonce);
            formData.append('limit', this.state.limit);
            formData.append('paged', this.state.paged);
            formData.append('search', this.state.search);
            formData.append('category', this.state.category);
            formData.append('min_price', this.state.minPrice);
            formData.append('max_price', this.state.maxPrice);
            formData.append('min_rating', this.state.minRating);
            formData.append('only_prime', this.state.onlyPrime);
            formData.append('only_deals', this.state.onlyDeals);
            formData.append('orderby', this.state.orderby);
            formData.append('order', this.state.order);
            formData.append('exclude', this.state.exclude);
            formData.append('random_seed', this.state.randomSeed);

            fetch(amazonProductAjax.ajax_url, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                signal: this.abortController.signal
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! Status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        this.gridContainer.innerHTML = data.data.html;

                        if (this.totalCount && data.data.count !== undefined) {
                            this.totalCount.textContent = data.data.count;
                        }

                        log('Fetch exitoso, productos actualizados');
                    } else {
                        console.error('[AmazonProduct] Error en respuesta:', data);
                    }
                })
                .catch(error => {
                    // Ignorar errores de abort (son intencionales)
                    if (error.name === 'AbortError') {
                        log('Fetch abortado intencionalmente');
                        return;
                    }
                    console.error('[AmazonProduct] Error en fetch:', error);
                })
                .finally(() => {
                    this.resetLoadingState();
                });
        }

        /*
         * Restaura el estado visual despues del fetch
         */
        resetLoadingState() {
            this.isLoading = false;
            this.abortController = null;

            if (this.gridContainer) {
                this.gridContainer.style.opacity = '1';
                this.gridContainer.style.pointerEvents = '';
            }
            if (this.loader) {
                this.loader.style.display = 'none';
            }
        }
    }

    /*
     * Gestor global de instancias
     * Mantiene track de todas las instancias activas y permite reinicializacion
     */
    const instanceRegistry = new WeakMap();

    function initializeWrapper(wrapper) {
        // Verificar si ya tiene una instancia activa
        if (instanceRegistry.has(wrapper)) {
            const existingInstance = instanceRegistry.get(wrapper);
            existingInstance.destroy();
            log('Instancia previa destruida para reinicializacion');
        }

        // Crear nueva instancia
        const controller = new AmazonProductController(wrapper);
        controller.init();
        instanceRegistry.set(wrapper, controller);
    }

    function initializeAll() {
        const wrappers = document.querySelectorAll('.amazon-product-wrapper');

        if (wrappers.length === 0) {
            log('No se encontraron wrappers de Amazon Product');
            return;
        }

        wrappers.forEach(wrapper => {
            initializeWrapper(wrapper);
        });

        log(`Inicializados ${wrappers.length} wrapper(s)`);
    }

    /*
     * Inicializacion principal
     * Se ejecuta en DOMContentLoaded y tambien escucha gloryRecarga para AJAX navigation
     */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeAll);
    } else {
        // DOM ya esta cargado
        initializeAll();
    }

    // Escuchar evento de recarga para reinicializar despues de navegacion AJAX
    document.addEventListener('gloryRecarga', () => {
        log('Evento gloryRecarga recibido, reinicializando...');
        initializeAll();
    });

    // Exponer para debugging si es necesario
    window.AmazonProductDebug = {
        reinitialize: initializeAll,
        getInstances: () => instanceRegistry
    };
})();
