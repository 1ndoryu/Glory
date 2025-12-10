/**
 * Amazon Product Plugin - Vanilla JavaScript (sin jQuery)
 * Maneja filtros, busqueda, paginacion y ordenamiento via AJAX
 */
(function () {
    'use strict';

    const wrapper = document.querySelector('.amazon-product-wrapper');
    if (!wrapper) return;

    const gridContainer = wrapper.querySelector('.amazon-product-grid-container');
    const loader = wrapper.querySelector('.amazon-loader');
    const filterPanel = document.getElementById('amazon-filter-panel');
    const toggleBtn = document.getElementById('amazon-toggle-filters');
    const totalCount = document.getElementById('amazon-total-count');

    // Estado inicial desde data attributes
    const state = {
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
        order: wrapper.dataset.order || 'DESC'
    };

    // Inicializar contador con el total real desde PHP (BUG-03 fix)
    // Usamos data-total-count que contiene found_posts, no el conteo de cards visibles
    if (totalCount && wrapper.dataset.totalCount) {
        totalCount.textContent = wrapper.dataset.totalCount;
    }

    /**
     * Funcion debounce para evitar multiples llamadas en busqueda
     */
    function debounce(func, wait) {
        let timeout;
        return function (...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }

    /**
     * Realiza peticion AJAX para filtrar productos
     */
    function fetchProducts() {
        if (!gridContainer) return;

        gridContainer.style.opacity = '0.5';
        if (loader) loader.style.display = 'flex';

        const formData = new FormData();
        formData.append('action', 'amazon_filter_products');
        formData.append('nonce', amazonProductAjax.nonce);
        formData.append('limit', state.limit);
        formData.append('paged', state.paged);
        formData.append('search', state.search);
        formData.append('category', state.category);
        formData.append('min_price', state.minPrice);
        formData.append('max_price', state.maxPrice);
        formData.append('min_rating', state.minRating);
        formData.append('only_prime', state.onlyPrime);
        formData.append('only_deals', state.onlyDeals);
        formData.append('orderby', state.orderby);
        formData.append('order', state.order);

        fetch(amazonProductAjax.ajax_url, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    gridContainer.innerHTML = data.data.html;
                    if (totalCount && data.data.count !== undefined) {
                        totalCount.textContent = data.data.count;
                    }
                    // Re-bind pagination after content update
                    bindPaginationEvents();
                }
            })
            .catch(error => {
                console.error('Amazon Product Filter Error:', error);
            })
            .finally(() => {
                gridContainer.style.opacity = '1';
                if (loader) loader.style.display = 'none';
            });
    }

    /**
     * Toggle del panel de filtros
     */
    if (toggleBtn && filterPanel) {
        toggleBtn.addEventListener('click', () => {
            filterPanel.classList.toggle('open');
            toggleBtn.classList.toggle('active');
        });
    }

    /**
     * Busqueda con debounce
     */
    const searchInput = document.getElementById('amazon-search');
    if (searchInput) {
        searchInput.addEventListener(
            'input',
            debounce(function () {
                state.search = this.value;
                state.paged = 1;
                fetchProducts();
            }, 500)
        );
    }

    /**
     * Slider de precio maximo
     */
    const priceRange = document.getElementById('amazon-max-price-range');
    const priceDisplay = document.getElementById('price-display');

    if (priceRange) {
        priceRange.addEventListener('input', function () {
            if (priceDisplay) {
                priceDisplay.textContent = this.value;
            }
        });

        priceRange.addEventListener('change', function () {
            state.maxPrice = this.value;
            state.paged = 1;
            fetchProducts();
        });
    }

    /**
     * Botones de rating
     */
    const ratingButtons = document.querySelectorAll('.amazon-rating-btn');
    ratingButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            const rating = this.dataset.rating;

            if (this.classList.contains('active')) {
                this.classList.remove('active');
                state.minRating = '';
            } else {
                ratingButtons.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                state.minRating = rating;
            }

            state.paged = 1;
            fetchProducts();
        });
    });

    /**
     * Botones de categoria
     */
    const categoryButtons = document.querySelectorAll('.amazon-category-btn');
    categoryButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            const slug = this.dataset.slug;

            if (this.classList.contains('active')) {
                this.classList.remove('active');
                state.category = '';
            } else {
                categoryButtons.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                state.category = slug;
            }

            state.paged = 1;
            fetchProducts();
        });
    });

    /**
     * Checkbox de Prime
     */
    const primeCheckbox = document.getElementById('amazon-prime');
    if (primeCheckbox) {
        primeCheckbox.addEventListener('change', function () {
            state.onlyPrime = this.checked ? '1' : '';
            state.paged = 1;
            fetchProducts();
        });
    }

    /**
     * Checkbox de Deals (Solo Ofertas)
     */
    const dealsCheckbox = document.getElementById('amazon-deals');
    if (dealsCheckbox) {
        dealsCheckbox.addEventListener('change', function () {
            state.onlyDeals = this.checked ? '1' : '';
            state.paged = 1;
            fetchProducts();
        });
    }

    /**
     * Selector de ordenamiento
     */
    const sortSelect = document.getElementById('amazon-sort');
    if (sortSelect) {
        sortSelect.addEventListener('change', function () {
            const [orderby, order] = this.value.split('-');
            state.orderby = orderby;
            state.order = order;
            state.paged = 1;
            fetchProducts();
        });
    }

    /**
     * Boton de reset de filtros
     */
    const resetBtn = document.getElementById('amazon-reset-filters');
    if (resetBtn) {
        resetBtn.addEventListener('click', function () {
            // Reset UI
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
            state.paged = 1;
            state.search = '';
            state.category = '';
            state.minPrice = '';
            state.maxPrice = '';
            state.minRating = '';
            state.onlyPrime = '';
            state.onlyDeals = '';
            state.orderby = 'date';
            state.order = 'DESC';

            fetchProducts();
        });
    }

    /**
     * Boton de limpiar busqueda (estado vacio)
     */
    document.addEventListener('click', function (e) {
        if (e.target && e.target.id === 'amazon-clear-search') {
            if (resetBtn) resetBtn.click();
        }
    });

    /**
     * Eventos de paginacion (se re-bindean tras cada fetch)
     */
    function bindPaginationEvents() {
        const pageLinks = document.querySelectorAll('.amazon-pagination .page-numbers');
        pageLinks.forEach(link => {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                state.paged = parseInt(this.dataset.page, 10);
                fetchProducts();

                // Scroll suave hacia el wrapper
                wrapper.scrollIntoView({behavior: 'smooth', block: 'start'});
            });
        });
    }

    // Bind inicial
    bindPaginationEvents();
})();
