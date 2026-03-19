let currentPage = 1;
let searchTimeout;

$(document).ready(function() {
    // Load filters
    loadFilters();
    
    // Initial search
    performSearch();
    
    // Search autocomplete
    $('#searchInput').on('input', function() {
        clearTimeout(searchTimeout);
        const query = $(this).val().trim();
        
        if (query.length < 2) {
            $('#searchAutocomplete').hide();
            return;
        }
        
        searchTimeout = setTimeout(() => {
            const url = (typeof SITE_URL !== 'undefined' ? SITE_URL : '') + '/ajax/search.php';
            $.ajax({
                url: url,
                method: 'GET',
                data: { action: 'autocomplete', q: query },
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.results.length > 0) {
                        showAutocomplete(response.results);
                    } else {
                        $('#searchAutocomplete').hide();
                    }
                }
            });
        }, 300);
    });
    
    // Click outside to close autocomplete
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#searchInput, #searchAutocomplete').length) {
            $('#searchAutocomplete').hide();
        }
    });
    
    // Apply filters
    $('#applyFilters').on('click', function() {
        currentPage = 1;
        performSearch();
    });
    
    // Reset filters
    $('#resetFilters').on('click', function() {
        $('#searchInput').val('');
        $('#categoryFilter').val('0');
        $('#minPrice').val('');
        $('#maxPrice').val('');
        $('#sortFilter').val('newest');
        currentPage = 1;
        performSearch();
    });
    
    // Enter key to search
    $('#searchInput').on('keypress', function(e) {
        if (e.which === 13) {
            currentPage = 1;
            performSearch();
            $('#searchAutocomplete').hide();
        }
    });
    
    // Sort change
    $('#sortFilter').on('change', function() {
        currentPage = 1;
        performSearch();
    });
});

function loadFilters() {
    const url = (typeof SITE_URL !== 'undefined' ? SITE_URL : '') + '/ajax/search.php';
    $.ajax({
        url: url,
        method: 'GET',
        data: { action: 'get_filters' },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Load categories
                response.categories.forEach(cat => {
                    $('#categoryFilter').append(
                        `<option value="${cat.id}">${cat.name} (${cat.product_count})</option>`
                    );
                });

                // Set price placeholders
                if (response.price_range) {
                    const minPrice = Math.floor(response.price_range.min_price / 1000000) * 1000000;
                    const maxPrice = Math.ceil(response.price_range.max_price / 1000000) * 1000000;
                    $('#minPrice').attr('placeholder', formatPrice(minPrice));
                    $('#maxPrice').attr('placeholder', formatPrice(maxPrice));
                }
            }
        }
    });
}

function performSearch() {
    $('#loadingSpinner').show();
    $('#productsGrid').empty();
    $('#noResults').hide();
    $('#pagination').hide();

    const url = (typeof SITE_URL !== 'undefined' ? SITE_URL : '') + '/ajax/search.php';
    $.ajax({
        url: url,
        method: 'POST',
        data: {
            action: 'search',
            query: $('#searchInput').val(),
            category: $('#categoryFilter').val(),
            min_price: $('#minPrice').val(),
            max_price: $('#maxPrice').val(),
            sort: $('#sortFilter').val(),
            page: currentPage
        },
        dataType: 'json',
        success: function(response) {
            $('#loadingSpinner').hide();

            if (response.success) {
                if (response.products.length > 0) {
                    displayProducts(response.products);
                    updatePagination(response.page, response.total_pages);
                    $('#resultsCount').text(`Tìm thấy ${response.total} sản phẩm`);
                } else {
                    $('#noResults').show();
                    $('#resultsCount').text('Không tìm thấy sản phẩm');
                }
            }
        },
        error: function() {
            $('#loadingSpinner').hide();
            showNotification('Có lỗi xảy ra, vui lòng thử lại', 'error');
        }
    });
}

function showAutocomplete(results) {
    let html = '';
    results.forEach(product => {
        html += `
            <div class="autocomplete-item" onclick="window.location.href='product-detail.php?slug=${product.slug}'">
                <img src="${product.image}" alt="${product.name}">
                <div class="flex-grow-1">
                    <div class="fw-bold">${product.name}</div>
                    <div class="text-danger">${formatPrice(product.price)}đ</div>
                </div>
            </div>
        `;
    });
    
    $('#searchAutocomplete').html(html).show();
}

function displayProducts(products) {
    let html = '';
    let delay = 0;
    
    products.forEach(product => {
        const price = product.sale_price || product.price;
        const hasDiscount = product.sale_price && product.sale_price < product.price;
        
        html += `
            <div class="col-md-4 col-6" data-aos="fade-up" data-aos-delay="${delay}">
                <div class="product-card card h-100">
                    ${hasDiscount ? '<span class="badge bg-danger position-absolute top-0 start-0 m-2">SALE</span>' : ''}
                    
                    <a href="product-detail.php?slug=${product.slug}">
                        <img src="${product.image}" class="card-img-top" alt="${product.name}">
                    </a>
                    
                    <div class="card-body">
                        <h6 class="card-title">
                            <a href="product-detail.php?slug=${product.slug}" class="text-decoration-none text-dark">
                                ${product.name}
                            </a>
                        </h6>
                        
                        <div class="price mb-3">
                            <span class="text-danger fw-bold">${formatPrice(price)}đ</span>
                            ${hasDiscount ? `<small class="text-muted text-decoration-line-through ms-2">${formatPrice(product.price)}đ</small>` : ''}
                        </div>
                        
                        <button class="btn btn-primary btn-sm w-100 add-to-cart" data-product-id="${product.id}">
                            <i class="fas fa-cart-plus"></i> Thêm vào giỏ
                        </button>
                    </div>
                </div>
            </div>
        `;
        delay += 100;
    });
    
    $('#productsGrid').html(html);
    AOS.refresh();
}

function updatePagination(currentPage, totalPages) {
    if (totalPages <= 1) {
        $('#pagination').hide();
        return;
    }
    
    let html = '';
    
    // Previous button
    html += `<li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" onclick="changePage(${currentPage - 1}); return false;">
                    <i class="fas fa-chevron-left"></i>
                </a>
             </li>`;
    
    // Page numbers
    for (let i = 1; i <= totalPages; i++) {
        if (i === 1 || i === totalPages || (i >= currentPage - 2 && i <= currentPage + 2)) {
            html += `<li class="page-item ${i === currentPage ? 'active' : ''}">
                        <a class="page-link" href="#" onclick="changePage(${i}); return false;">${i}</a>
                     </li>`;
        } else if (i === currentPage - 3 || i === currentPage + 3) {
            html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
    }
    
    // Next button
    html += `<li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                <a class="page-link" href="#" onclick="changePage(${currentPage + 1}); return false;">
                    <i class="fas fa-chevron-right"></i>
                </a>
             </li>`;
    
    $('#pagination ul').html(html);
    $('#pagination').show();
}

function changePage(page) {
    currentPage = page;
    performSearch();
    $('html, body').animate({ scrollTop: 0 }, 300);
}

function formatPrice(price) {
    return new Intl.NumberFormat('vi-VN').format(price);
}

