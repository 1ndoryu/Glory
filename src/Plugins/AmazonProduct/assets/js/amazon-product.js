jQuery(document).ready(function($) {
    const $wrapper = $('.amazon-product-wrapper');
    if (!$wrapper.length) return;

    const $gridContainer = $wrapper.find('.amazon-product-grid-container');
    const $loader = $wrapper.find('.amazon-loader');
    
    // State
    let state = {
        limit: $wrapper.data('limit'),
        paged: 1,
        search: '',
        min_price: $wrapper.data('min-price'),
        max_price: $wrapper.data('max-price'),
        only_prime: $wrapper.data('only-prime'),
        orderby: $wrapper.data('orderby'),
        order: $wrapper.data('order')
    };

    // Debounce function
    function debounce(func, wait) {
        let timeout;
        return function() {
            const context = this, args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(context, args), wait);
        };
    }

    // Fetch Products
    function fetchProducts() {
        $gridContainer.css('opacity', '0.5');
        $loader.show();

        $.ajax({
            url: amazonProductAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'amazon_filter_products',
                nonce: amazonProductAjax.nonce,
                ...state
            },
            success: function(response) {
                if (response.success) {
                    $gridContainer.html(response.data.html);
                }
            },
            complete: function() {
                $gridContainer.css('opacity', '1');
                $loader.hide();
            }
        });
    }

    // Event Listeners
    $('#amazon-search').on('input', debounce(function() {
        state.search = $(this).val();
        state.paged = 1;
        fetchProducts();
    }, 500));

    $('#amazon-min-price, #amazon-max-price').on('input', debounce(function() {
        state.min_price = $('#amazon-min-price').val();
        state.max_price = $('#amazon-max-price').val();
        state.paged = 1;
        fetchProducts();
    }, 500));

    $('#amazon-prime').on('change', function() {
        state.only_prime = $(this).is(':checked') ? '1' : '';
        state.paged = 1;
        fetchProducts();
    });

    $('#amazon-sort').on('change', function() {
        const val = $(this).val().split('-');
        state.orderby = val[0];
        state.order = val[1];
        state.paged = 1;
        fetchProducts();
    });

    // Pagination Click
    $(document).on('click', '.amazon-pagination .page-numbers', function(e) {
        e.preventDefault();
        state.paged = $(this).data('page');
        fetchProducts();
        $('html, body').animate({
            scrollTop: $wrapper.offset().top - 100
        }, 500);
    });
});
