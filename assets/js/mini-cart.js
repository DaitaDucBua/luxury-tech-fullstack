/**
 * Mini Cart Dropdown Functionality
 */

(function() {
    'use strict';
    
    let cartData = { items: [], total: 0, count: 0 };
    
    // Initialize
    $(document).ready(function() {
        createMiniCart();
        loadMiniCart();
        
        // Toggle mini cart
        $(document).on('click', '.cart-icon', function(e) {
            e.preventDefault();
            e.stopPropagation();
            toggleMiniCart();
        });
        
        // Close mini cart
        $(document).on('click', '.mini-cart-close', function() {
            closeMiniCart();
        });
        
        // Close when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.mini-cart-dropdown, .cart-icon').length) {
                closeMiniCart();
            }
        });
        
        // Prevent closing when clicking inside
        $(document).on('click', '.mini-cart-dropdown', function(e) {
            e.stopPropagation();
        });
        
        // Update quantity
        $(document).on('click', '.mini-cart-qty-btn', function() {
            const productId = $(this).data('product-id');
            const action = $(this).data('action');
            const currentQty = parseInt($(this).siblings('span').text());
            const newQty = action === 'increase' ? currentQty + 1 : currentQty - 1;
            
            if (newQty >= 0) {
                updateCartQuantity(productId, newQty);
            }
        });
        
        // Remove item
        $(document).on('click', '.mini-cart-item-remove', function() {
            const productId = $(this).data('product-id');
            removeCartItem(productId);
        });
    });
    
    /**
     * Create mini cart HTML
     */
    function createMiniCart() {
        if ($('#miniCartDropdown').length > 0) return;
        
        const html = `
            <div id="miniCartDropdown" class="mini-cart-dropdown">
                <div class="mini-cart-header">
                    <h6><i class="fas fa-shopping-cart me-2"></i>Giỏ Hàng</h6>
                    <button class="mini-cart-close"><i class="fas fa-times"></i></button>
                </div>
                <div class="mini-cart-body">
                    <div class="mini-cart-items"></div>
                </div>
                <div class="mini-cart-footer">
                    <div class="mini-cart-total">
                        <span>Tổng cộng:</span>
                        <span class="mini-cart-total-amount">0đ</span>
                    </div>
                    <div class="mini-cart-actions">
                        <a href="cart.php?t=${Date.now()}" class="btn btn-outline-primary">Xem Giỏ Hàng</a>
                        <a href="checkout.php?t=${Date.now()}" class="btn btn-primary">Thanh Toán</a>
                    </div>
                </div>
            </div>
        `;
        
        $('.cart-icon').parent().css('position', 'relative').append(html);
    }
    
    /**
     * Load mini cart data
     */
    function loadMiniCart() {
        const url = (typeof SITE_URL !== 'undefined' ? SITE_URL : '') + '/ajax/mini-cart.php';
        $.get(url, { action: 'get_cart' }, function(response) {
            if (response.success) {
                cartData = response;
                renderMiniCart();
                updateCartBadge();
            }
        }, 'json');
    }
    
    /**
     * Render mini cart
     */
    function renderMiniCart() {
        const $items = $('.mini-cart-items');
        
        if (cartData.items.length === 0) {
            $items.html(`
                <div class="mini-cart-empty">
                    <i class="fas fa-shopping-cart"></i>
                    <p>Giỏ hàng trống</p>
                </div>
            `);
            $('.mini-cart-total').hide();
            $('.mini-cart-footer').show();
            $('.mini-cart-actions a[href*="checkout"]').hide();
        } else {
            let html = '';
            cartData.items.forEach(function(item) {
                html += `
                    <div class="mini-cart-item" data-product-id="${item.product_id}">
                        <div class="mini-cart-item-image">
                            <img src="${item.image_url}" alt="${item.name}">
                        </div>
                        <div class="mini-cart-item-info">
                            <div class="mini-cart-item-name">
                                <a href="${typeof SITE_URL !== 'undefined' ? SITE_URL : ''}/product-detail.php?slug=${item.slug}">${item.name}</a>
                            </div>
                            <div class="mini-cart-item-price">${formatPrice(item.price)}đ</div>
                            <div class="mini-cart-quantity">
                                <button class="mini-cart-qty-btn" data-product-id="${item.product_id}" data-action="decrease">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <span>${item.quantity}</span>
                                <button class="mini-cart-qty-btn" data-product-id="${item.product_id}" data-action="increase" ${item.quantity >= item.stock ? 'disabled' : ''}>
                                    <i class="fas fa-plus"></i>
                                </button>
                                <button class="mini-cart-item-remove" data-product-id="${item.product_id}">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            $items.html(html);
            $('.mini-cart-total-amount').text(formatPrice(cartData.total) + 'đ');
            $('.mini-cart-total').show();
            $('.mini-cart-footer').show();
            $('.mini-cart-actions a[href*="checkout"]').show();
        }
    }
    
    /**
     * Update cart badge
     */
    function updateCartBadge() {
        const $badge = $('.cart-count');
        const $cartIcon = $('.cart-icon');

        if (cartData.count > 0) {
            if ($badge.length === 0) {
                // Create badge if it doesn't exist
                $cartIcon.append(
                    `<span class="badge rounded-pill cart-count bg-info" style="display: inline-flex; align-items: center; justify-content: center; min-width: 20px; height: 20px; font-size: 11px; font-weight: bold; padding: 0; margin-left: 8px;">${cartData.count}</span>`
                );
            } else {
                const oldCount = parseInt($badge.text()) || 0;
                $badge.text(cartData.count).show();
                
                // Add bounce animation if count changed
                if (oldCount !== cartData.count) {
                    $badge.addClass('updated');
                    setTimeout(() => {
                        $badge.removeClass('updated');
                    }, 500);
                }
            }
        } else {
            $badge.hide();
        }
    }
    
    /**
     * Toggle mini cart
     */
    function toggleMiniCart() {
        $('#miniCartDropdown').toggleClass('show');
    }
    
    /**
     * Close mini cart
     */
    function closeMiniCart() {
        $('#miniCartDropdown').removeClass('show');
    }
    
    /**
     * Update cart quantity
     */
    function updateCartQuantity(productId, quantity) {
        const url = (typeof SITE_URL !== 'undefined' ? SITE_URL : '') + '/ajax/mini-cart.php';
        $.post(url, {
            action: 'update_quantity',
            product_id: productId,
            quantity: quantity
        }, function(response) {
            if (response.success) {
                loadMiniCart();
            } else {
                alert(response.message);
            }
        }, 'json');
    }
    
    /**
     * Remove cart item
     */
    function removeCartItem(productId) {
        const url = (typeof SITE_URL !== 'undefined' ? SITE_URL : '') + '/ajax/mini-cart.php';
        $.post(url, {
            action: 'remove_item',
            product_id: productId
        }, function(response) {
            if (response.success) {
                // Animate removal
                $(`.mini-cart-item[data-product-id="${productId}"]`).fadeOut(300, function() {
                    loadMiniCart();
                });
            }
        }, 'json');
    }
    
    /**
     * Format price
     */
    function formatPrice(price) {
        return new Intl.NumberFormat('vi-VN').format(price);
    }
    
    // Export functions
    window.MiniCart = {
        load: loadMiniCart,
        toggle: toggleMiniCart,
        close: closeMiniCart
    };
    
})();

