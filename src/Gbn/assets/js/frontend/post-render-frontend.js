(function (global) {
    'use strict';

    /**
     * POST RENDER FRONTEND
     *
     * Maneja las interacciones de usuario en el frontend para PostRender:
     * - Filtro por categorías (sin recarga de página)
     * - Paginación AJAX
     *
     * Este archivo se carga para todos los usuarios (no solo editores).
     *
     * @module GbnPostRenderFrontend
     */

    var GbnPostRender = (global.GbnPostRender = global.GbnPostRender || {});

    /**
     * Configuración por defecto del módulo.
     */
    var defaults = {
        filterButtonClass: 'gbn-pr-filter-btn',
        activeClass: 'active',
        pageButtonClass: 'gbn-pr-page-btn',
        loadingClass: 'gbn-loading',
        itemSelector: '[gloryPostItem]',
        hiddenClass: 'gbn-pr-hidden'
    };

    /**
     * Inicializa todos los PostRender en la página.
     */
    function init() {
        initializeFilters();
        initializePagination();
        initializeClickableCards();
    }

    // =========================================================================
    // FILTRO POR CATEGORÍAS
    // =========================================================================

    /**
     * Inicializa todos los filtros de categorías en la página.
     */
    function initializeFilters() {
        var filters = document.querySelectorAll('.gbn-pr-filter');

        filters.forEach(function (filter) {
            var targetSelector = filter.dataset.target;
            if (!targetSelector) return;

            var container = document.querySelector(targetSelector);
            if (!container) return;

            // Vincular eventos a los botones de filtro
            var buttons = filter.querySelectorAll('.' + defaults.filterButtonClass);
            buttons.forEach(function (button) {
                button.addEventListener('click', function () {
                    handleFilterClick(button, buttons, container);
                });
            });
        });
    }

    /**
     * Maneja el clic en un botón de filtro.
     *
     * @param {HTMLElement} clickedButton Botón clickeado
     * @param {NodeList} allButtons Todos los botones del filtro
     * @param {HTMLElement} container Contenedor de posts
     */
    function handleFilterClick(clickedButton, allButtons, container) {
        var category = clickedButton.dataset.category;

        // Actualizar estado de botones
        allButtons.forEach(function (btn) {
            btn.classList.remove(defaults.activeClass);
        });
        clickedButton.classList.add(defaults.activeClass);

        // Filtrar items
        filterItems(container, category);
    }

    /**
     * Filtra los items del contenedor por categoría.
     *
     * @param {HTMLElement} container Contenedor de posts
     * @param {string} category Categoría a mostrar ('all' para todos)
     */
    function filterItems(container, category) {
        var items = container.querySelectorAll(defaults.itemSelector);

        items.forEach(function (item) {
            if (category === 'all') {
                showItem(item);
                return;
            }

            // Buscar si el item tiene la categoría
            var itemCategories = getItemCategories(item);

            if (itemCategories.indexOf(category) !== -1) {
                showItem(item);
            } else {
                hideItem(item);
            }
        });
    }

    /**
     * Obtiene las categorías de un item.
     * Las categorías se extraen del atributo data-categories
     * o de elementos con clase de categoría.
     *
     * @param {HTMLElement} item Elemento del item
     * @returns {Array} Array de slugs de categorías
     */
    function getItemCategories(item) {
        // Primero intentar data-categories
        var dataCategories = item.dataset.categories;
        if (dataCategories) {
            return dataCategories.split(',').map(function (c) {
                return c.trim();
            });
        }

        // Buscar en elementos internos [gloryPostField="categories"]
        var catField = item.querySelector('[gloryPostField="categories"]');
        if (catField && catField.dataset.slugs) {
            return catField.dataset.slugs.split(',').map(function (c) {
                return c.trim();
            });
        }

        // Buscar enlaces de categoría
        var catLinks = item.querySelectorAll('.category a, .cat-link, [rel="category tag"]');
        if (catLinks.length > 0) {
            var cats = [];
            catLinks.forEach(function (link) {
                // Extraer slug de la URL o del data-attribute
                var slug = link.dataset.slug || extractSlugFromUrl(link.href);
                if (slug) cats.push(slug);
            });
            return cats;
        }

        return [];
    }

    /**
     * Extrae el slug de una URL de categoría.
     *
     * @param {string} url URL de la categoría
     * @returns {string|null} Slug extraído o null
     */
    function extractSlugFromUrl(url) {
        if (!url) return null;

        // Buscar patrón típico: /category/slug/ o ?cat=slug
        var match = url.match(/\/category\/([^\/]+)\/?$/i);
        if (match) return match[1];

        match = url.match(/[?&]cat=([^&]+)/);
        if (match) return match[1];

        return null;
    }

    /**
     * Muestra un item con animación suave.
     *
     * @param {HTMLElement} item Elemento del item
     */
    function showItem(item) {
        item.classList.remove(defaults.hiddenClass);
        item.style.display = '';
        item.style.opacity = '1';
        item.style.transform = 'scale(1)';
    }

    /**
     * Oculta un item con animación suave.
     *
     * @param {HTMLElement} item Elemento del item
     */
    function hideItem(item) {
        item.classList.add(defaults.hiddenClass);
        item.style.opacity = '0';
        item.style.transform = 'scale(0.95)';
        // Después de la transición, ocultar completamente
        setTimeout(function () {
            if (item.classList.contains(defaults.hiddenClass)) {
                item.style.display = 'none';
            }
        }, 300);
    }

    // =========================================================================
    // CLICKABLE CARDS - Navegacion al single page
    // =========================================================================

    /**
     * Inicializa las tarjetas clickeables que navegan al single page del post.
     * Los elementos con [gloryPostItem][data-permalink] se vuelven clickeables.
     */
    function initializeClickableCards() {
        var postItems = document.querySelectorAll(defaults.itemSelector + '[data-permalink]');

        postItems.forEach(function (item) {
            // Evitar inicializar multiples veces
            if (item.dataset.clickableInitialized) return;
            item.dataset.clickableInitialized = 'true';

            item.addEventListener('click', function (event) {
                handleCardClick(event, item);
            });
        });
    }

    /**
     * Maneja el click en una tarjeta de post.
     * Navega al permalink, excepto si el click fue en un enlace interno.
     *
     * @param {Event} event Evento de click
     * @param {HTMLElement} item Elemento del post item
     */
    function handleCardClick(event, item) {
        var target = event.target;

        // Si el click fue en un enlace (anchor), dejar que el navegador maneje la navegacion
        // Esto permite que enlaces internos dentro del card funcionen normalmente
        if (target.closest('a')) {
            return;
        }

        var permalink = item.dataset.permalink;
        if (!permalink) return;

        // Navegar al single page del post
        window.location.href = permalink;
    }

    /**
     * Re-inicializa las tarjetas clickeables (util despues de cargar contenido via AJAX).
     */
    function reinitializeClickableCards() {
        initializeClickableCards();
    }

    // PAGINACIÓN AJAX
    // =========================================================================

    /**
     * Inicializa todos los controles de paginación AJAX.
     */
    function initializePagination() {
        var paginationControls = document.querySelectorAll('.gbn-pr-pagination');

        paginationControls.forEach(function (pagination) {
            var targetSelector = pagination.dataset.target;
            if (!targetSelector) return;

            var container = document.querySelector(targetSelector);
            if (!container) return;

            // Estado de paginación
            var state = {
                currentPage: 1,
                maxPages: parseInt(pagination.querySelector('.gbn-pr-page-info').textContent.split('/')[1]) || 1
            };

            // Vincular eventos a los botones
            var prevBtn = pagination.querySelector('[data-page="prev"]');
            var nextBtn = pagination.querySelector('[data-page="next"]');

            if (prevBtn) {
                prevBtn.addEventListener('click', function () {
                    if (state.currentPage > 1) {
                        loadPage(container, pagination, state, state.currentPage - 1);
                    }
                });
            }

            if (nextBtn) {
                nextBtn.addEventListener('click', function () {
                    if (state.currentPage < state.maxPages) {
                        loadPage(container, pagination, state, state.currentPage + 1);
                    }
                });
            }
        });
    }

    /**
     * Carga una página de posts vía AJAX.
     *
     * @param {HTMLElement} container Contenedor de posts
     * @param {HTMLElement} pagination Control de paginación
     * @param {Object} state Estado de paginación
     * @param {number} page Número de página a cargar
     */
    function loadPage(container, pagination, state, page) {
        // Extraer configuración del contenedor
        var postType = container.dataset.postType || 'post';
        var postsPerPage = container.dataset.postsPerPage || 6;

        // Mostrar loading
        container.classList.add(defaults.loadingClass);

        // Obtener parámetros AJAX
        var ajaxUrl = (global.gloryGbnCfg && global.gloryGbnCfg.ajaxUrl) || (global.ajax_params && global.ajax_params.ajax_url) || '/wp-admin/admin-ajax.php';
        var nonce = (global.gloryGbnCfg && global.gloryGbnCfg.nonce) || '';

        // Preparar datos
        var formData = new FormData();
        formData.append('action', 'gbn_post_render_paginate');
        formData.append('nonce', nonce);
        formData.append('page', page);
        formData.append(
            'config',
            JSON.stringify({
                postType: postType,
                postsPerPage: postsPerPage,
                paged: page
            })
        );

        // Hacer petición AJAX
        fetch(ajaxUrl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                container.classList.remove(defaults.loadingClass);

                if (data.success && data.data && data.data.html) {
                    // Reemplazar contenido con animación
                    container.style.opacity = '0';
                    setTimeout(function () {
                        container.innerHTML = data.data.html;
                        container.style.opacity = '1';
                        // Re-inicializar tarjetas clickeables para el nuevo contenido
                        reinitializeClickableCards();
                    }, 200);

                    // Actualizar estado y UI
                    state.currentPage = page;
                    updatePaginationUI(pagination, state);

                    // Scroll suave al contenedor
                    container.scrollIntoView({behavior: 'smooth', block: 'start'});
                }
            })
            .catch(function (error) {
                container.classList.remove(defaults.loadingClass);
                console.error('[PostRender] Pagination error:', error);
            });
    }

    /**
     * Actualiza la UI de paginación después de cambiar de página.
     *
     * @param {HTMLElement} pagination Control de paginación
     * @param {Object} state Estado de paginación
     */
    function updatePaginationUI(pagination, state) {
        var pageInfo = pagination.querySelector('.gbn-pr-page-info');
        if (pageInfo) {
            var currentSpan = pageInfo.querySelector('.current');
            if (currentSpan) {
                currentSpan.textContent = state.currentPage;
            }
        }

        // Deshabilitar botones en límites
        var prevBtn = pagination.querySelector('[data-page="prev"]');
        var nextBtn = pagination.querySelector('[data-page="next"]');

        if (prevBtn) {
            prevBtn.disabled = state.currentPage <= 1;
        }
        if (nextBtn) {
            nextBtn.disabled = state.currentPage >= state.maxPages;
        }
    }

    // =========================================================================
    // INICIALIZACIÓN
    // =========================================================================

    // Exportar función de inicialización
    GbnPostRender.init = init;
    GbnPostRender.filterItems = filterItems;
    GbnPostRender.loadPage = loadPage;
    GbnPostRender.reinitializeClickableCards = reinitializeClickableCards;

    // Auto-inicializar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Re-inicializar cuando se dispare gloryRecarga (navegacion AJAX)
    document.addEventListener('gloryRecarga', function () {
        init();
    });
})(typeof window !== 'undefined' ? window : this);
