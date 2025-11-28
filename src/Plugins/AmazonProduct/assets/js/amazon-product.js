jQuery(document).ready(function($) {
    const $wrapper = $('.amazon-product-wrapper');
    if (!$wrapper.length) return;

    const $gridContainer = $wrapper.find('.amazon-product-grid-container');
    const $loader = $wrapper.find('.amazon-loader');
    const $filterPanel = $('#amazon-filter-panel');
    const $toggleBtn = $('#amazon-toggle-filters');
    const $totalCount = $('#amazon-total-count');
    
    // State
    let state = {
        limit: $wrapper.data('limit'),
        paged: 1,
        paged: 1,
        search: '',
        category: $wrapper.data('category'),
        min_price: $wrapper.data('min-price'),
        max_price: $wrapper.data('max-price'),
        min_rating: '',
        only_prime: $wrapper.data('only-prime'),
        orderby: $wrapper.data('orderby'),
        order: $wrapper.data('order')
    };

    // Initialize Count
    $totalCount.text($('.amazon-product-card').length);

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
                    if (response.data.count !== undefined) {
                        $totalCount.text(response.data.count);
                    }
                }
            },
            complete: function() {
                $gridContainer.css('opacity', '1');
                $loader.hide();
            }
        });
    }

    // Toggle Filters
    $toggleBtn.on('click', function() {
        $filterPanel.toggleClass('open');
        $(this).toggleClass('active');
    });

    // Search
    $('#amazon-search').on('input', debounce(function() {
        state.search = $(this).val();
        state.paged = 1;
        fetchProducts();
    }, 500));

    // Price Range Slider
    $('#amazon-max-price-range').on('input', function() {
        $('#price-display').text($(this).val());
    });

    $('#amazon-max-price-range').on('change', function() {
        state.max_price = $(this).val();
        state.paged = 1;
        fetchProducts();
    });

    // Rating Buttons
    $('.amazon-rating-btn').on('click', function() {
        const rating = $(this).data('rating');
        
        if ($(this).hasClass('active')) {
            $(this).removeClass('active');
            state.min_rating = '';
        } else {
            $('.amazon-rating-btn').removeClass('active');
            $(this).addClass('active');
            state.min_rating = rating;
        }
        
        state.paged = 1;
        fetchProducts();
        fetchProducts();
    });

    // Category Buttons
    $('.amazon-category-btn').on('click', function() {
        const slug = $(this).data('slug');
        
        if ($(this).hasClass('active')) {
            $(this).removeClass('active');
            state.category = '';
        } else {
            $('.amazon-category-btn').removeClass('active');
            $(this).addClass('active');
            state.category = slug;
        }
        
        state.paged = 1;
        fetchProducts();
    });

    // Prime Checkbox
    $('#amazon-prime').on('change', function() {
        state.only_prime = $(this).is(':checked') ? '1' : '';
        state.paged = 1;
        fetchProducts();
    });

    // Sort Dropdown
    $('#amazon-sort').on('change', function() {
        const val = $(this).val().split('-');
        state.orderby = val[0];
        state.order = val[1];
        state.paged = 1;
        fetchProducts();
    });

    // Reset Filters
    $('#amazon-reset-filters').on('click', function() {
        // Reset UI
        $('#amazon-search').val('');
        $('#amazon-max-price-range').val(2000).trigger('input');
        $('#amazon-search').val('');
        $('#amazon-max-price-range').val(2000).trigger('input');
        $('.amazon-rating-btn').removeClass('active');
        $('.amazon-category-btn').removeClass('active');
        $('#amazon-prime').prop('checked', false);
        $('#amazon-sort').val('date-DESC');

        // Reset State
        state = {
            limit: $wrapper.data('limit'),
            paged: 1,
            paged: 1,
            search: '',
            category: '',
            min_price: '',
            max_price: '',
            min_rating: '',
            only_prime: '',
            orderby: 'date',
            order: 'DESC'
        };

        fetchProducts();
    });

    // Clear Search (Empty State)
    $(document).on('click', '#amazon-clear-search', function() {
        $('#amazon-reset-filters').click();
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
