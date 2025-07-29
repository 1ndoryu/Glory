document.addEventListener('gloryRecarga', function () {
    // Usamos delegación de eventos en el body para manejar contenedores de paginación que se añadan dinámicamente.
    document.body.addEventListener('click', handlePaginationClick);
});

// Almacena un registro de los listeners para evitar duplicados, aunque con la delegación en body no es estrictamente necesario.
if (typeof window.gloryPaginationInitialized === 'undefined') {
    window.gloryPaginationInitialized = true;
}

async function handlePaginationClick(e) {
    const paginationLink = e.target.closest('.glory-pagination-container .page-numbers[data-page]');

    // Si el clic no es en un enlace de paginación válido, no hacemos nada.
    if (!paginationLink) {
        return;
    }

    // Prevenimos el comportamiento por defecto del enlace (recarga de página).
    e.preventDefault();
    e.stopPropagation();

    const container = paginationLink.closest('.glory-pagination-container');
    if (!container) {
        console.error('Glory Pagination: No se encontró el contenedor padre .glory-pagination-container');
        return;
    }

    const page = paginationLink.dataset.page;
    const data = container.dataset;

    // Identificamos los contenedores de contenido y paginación usando los data-attributes.
    const contentTarget = document.querySelector(data.contentTarget);
    const paginationTarget = document.querySelector(data.paginationTarget);

    if (!contentTarget || !paginationTarget) {
        console.error('Glory Pagination: No se encontraron los contenedores de destino para el contenido o la paginación.');
        return;
    }

    // Proporcionamos feedback visual durante la carga.
    container.style.opacity = '0.5';
    container.style.pointerEvents = 'none';

    const ajaxData = {
        nonce: data.nonce,
        paged: page,
        post_type: data.postType,
        posts_per_page: data.postsPerPage,
        template_callback: data.templateCallback,
        container_class: data.containerClass,
        item_class: data.itemClass,
    };

    try {
        // Usamos la función genérica gloryAjax para consistencia con el framework.
        const result = await gloryAjax('glory_pagination', ajaxData);

        if (result.success && result.data.html) {
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = result.data.html;

            const newContentContainer = tempDiv.querySelector(data.contentTarget);
            const newPaginationContainer = tempDiv.querySelector(data.paginationTarget);

            if (newContentContainer) {
                contentTarget.innerHTML = newContentContainer.innerHTML;
            }
            if (newPaginationContainer) {
                paginationTarget.innerHTML = newPaginationContainer.innerHTML;
            }

            // Actualizamos la URL del navegador para reflejar el cambio de página.
            const newUrl = new URL(window.location);
            newUrl.searchParams.set('paged', page);
            window.history.pushState({ path: newUrl.href }, '', newUrl.href);

            // Disparamos el evento 'gloryRecarga' para reinicializar otros scripts en el nuevo contenido.
            const event = new CustomEvent('gloryRecarga', { bubbles: true, cancelable: true });
            container.dispatchEvent(event);

        } else {
            console.error('Error en la paginación AJAX:', result.message || 'Error desconocido');
        }
    } catch (error) {
        console.error('Error de fetch durante la paginación:', error);
    } finally {
        // Restauramos la interactividad del contenedor.
        container.style.opacity = '1';
        container.style.pointerEvents = 'auto';

        // Hacemos scroll suave a la parte superior del contenido.
        if (contentTarget) {
            contentTarget.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }
}