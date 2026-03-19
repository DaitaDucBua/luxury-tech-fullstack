// AJAX Framework is loaded separately

$(document).ready(function() {

    // ========================================
    // PAGE LOADER
    // ========================================
    setTimeout(function() {
        $('.page-loader').addClass('hidden');
    }, 500);

    // ========================================
    // BACK TO TOP BUTTON
    // ========================================
    const backToTop = $('<button class="back-to-top"><i class="fas fa-arrow-up"></i></button>');
    $('body').append(backToTop);

    $(window).scroll(function() {
        if ($(this).scrollTop() > 300) {
            backToTop.addClass('show');
        } else {
            backToTop.removeClass('show');
        }
    });

    backToTop.on('click', function() {
        $('html, body').animate({ scrollTop: 0 }, 600);
    });

    // ========================================
    // COUNTER ANIMATION
    // ========================================
    let counterAnimated = false;

    function animateCounter() {
        if (counterAnimated) return;

        $('.counter').each(function() {
            const $this = $(this);
            const target = parseInt($this.data('target'));
            const duration = 2000;
            const increment = target / (duration / 16);
            let current = 0;

            const timer = setInterval(function() {
                current += increment;
                if (current >= target) {
                    current = target;
                    clearInterval(timer);
                }
                $this.text(Math.floor(current).toLocaleString('vi-VN'));
            }, 16);
        });

        counterAnimated = true;
    }

    // Trigger counter when stats section is visible
    $(window).on('scroll', function() {
        const statsSection = $('.stats-section');
        if (statsSection.length) {
            const sectionTop = statsSection.offset().top;
            const sectionHeight = statsSection.outerHeight();
            const scrollTop = $(window).scrollTop();
            const windowHeight = $(window).height();

            if (scrollTop + windowHeight > sectionTop + sectionHeight / 2) {
                animateCounter();
            }
        }
    });

    // ========================================
    // SMOOTH SCROLL FOR ANCHOR LINKS
    // ========================================
    $('a[href^="#"]').on('click', function(e) {
        const target = $(this.getAttribute('href'));
        if (target.length) {
            e.preventDefault();
            $('html, body').stop().animate({
                scrollTop: target.offset().top - 100
            }, 800);
        }
    });

    // ========================================
    // ADD TO CART ANIMATION
    // ========================================
    function flyToCart(button) {
        const cart = $('.cart-icon');
        if (!cart.length) return;

        const productImg = button.closest('.product-card, .product-detail').find('img').first();
        if (!productImg.length) return;

        const imgClone = productImg.clone()
            .css({
                'position': 'fixed',
                'z-index': '9999',
                'width': '80px',
                'height': '80px',
                'object-fit': 'contain',
                'top': productImg.offset().top,
                'left': productImg.offset().left,
                'opacity': '0.8',
                'pointer-events': 'none'
            })
            .appendTo('body');

        const cartPos = cart.offset();

        imgClone.animate({
            top: cartPos.top,
            left: cartPos.left,
            width: 0,
            height: 0,
            opacity: 0
        }, 800, function() {
            $(this).remove();
            cart.addClass('animate__animated animate__rubberBand');
            setTimeout(() => cart.removeClass('animate__animated animate__rubberBand'), 1000);
        });
    }

    // ========================================
    // CART FUNCTIONALITY
    // ========================================

    // Thêm sản phẩm vào giỏ hàng
    $('.add-to-cart').on('click', function() {
        const productId = $(this).data('product-id');
        const button = $(this);
        const originalText = button.html();

        button.prop('disabled', true).html('<span class="loading"></span> Đang thêm...');

        const url = (typeof SITE_URL !== 'undefined' ? SITE_URL : '') + '/ajax/cart-handler.php';
        $.ajax({
            url: url,
            method: 'POST',
            data: {
                action: 'add',
                product_id: productId,
                quantity: 1
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Animation bay vào giỏ hàng
                    flyToCart(button);

                    // Cập nhật số lượng giỏ hàng
                    updateCartCount(response.cart_count);

                    // Cập nhật Mini Cart ngay lập tức
                    if (typeof MiniCart !== 'undefined' && MiniCart.load) {
                        MiniCart.load();
                    }

                    // Hiển thị thông báo
                    Ajax.showNotification(response.message, 'success');

                    button.html('<i class="fas fa-check"></i> Đã thêm');
                    setTimeout(() => {
                        button.html(originalText).prop('disabled', false);
                    }, 2000);
                } else {
                    Ajax.showNotification(response.message, 'error');
                    button.html(originalText).prop('disabled', false);
                }
            },
            error: function() {
                if (typeof Ajax !== 'undefined') {
                    Ajax.showNotification('Có lỗi xảy ra, vui lòng thử lại', 'error');
                } else {
                    alert('Có lỗi xảy ra, vui lòng thử lại');
                }
                button.html(originalText).prop('disabled', false);
            }
        });
    });
    
    // Thêm sản phẩm vào giỏ hàng từ trang chi tiết
    $('.add-to-cart-detail').on('click', function() {
        const productId = $(this).data('product-id');
        const quantity = parseInt($('#quantity').val());
        const button = $(this);
        const originalText = button.html();

        button.prop('disabled', true).html('<span class="loading"></span> Đang thêm...');

        const url = (typeof SITE_URL !== 'undefined' ? SITE_URL : '') + '/ajax/cart-handler.php';
        $.ajax({
            url: url,
            method: 'POST',
            data: {
                action: 'add',
                product_id: productId,
                quantity: quantity
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Animation bay vào giỏ hàng
                    flyToCart(button);

                    updateCartCount(response.cart_count);

                    // Cập nhật Mini Cart ngay lập tức
                    if (typeof MiniCart !== 'undefined' && MiniCart.load) {
                        MiniCart.load();
                    }

                    Ajax.showNotification(response.message, 'success');
                    button.html('<i class="fas fa-check"></i> Đã thêm');
                    setTimeout(() => {
                        button.html(originalText).prop('disabled', false);
                    }, 2000);
                } else {
                    Ajax.showNotification(response.message, 'error');
                    button.html(originalText).prop('disabled', false);
                }
            },
            error: function() {
                if (typeof Ajax !== 'undefined') {
                    Ajax.showNotification('Có lỗi xảy ra, vui lòng thử lại', 'error');
                } else {
                    alert('Có lỗi xảy ra, vui lòng thử lại');
                }
                button.html(originalText).prop('disabled', false);
            }
        });
    });
    
    // Tăng/giảm số lượng trong trang chi tiết
    $('#increaseQty').on('click', function() {
        const input = $('#quantity');
        const max = parseInt(input.attr('max'));
        const current = parseInt(input.val());
        if (current < max) {
            input.val(current + 1);
        }
    });
    
    $('#decreaseQty').on('click', function() {
        const input = $('#quantity');
        const min = parseInt(input.attr('min'));
        const current = parseInt(input.val());
        if (current > min) {
            input.val(current - 1);
        }
    });
    
    // Cập nhật số lượng trong giỏ hàng
    $('.update-cart-qty').on('click', function() {
        const action = $(this).data('action');
        const row = $(this).closest('tr');
        const input = row.find('.cart-quantity');
        const cartId = row.data('cart-id');
        const max = parseInt(input.attr('max'));
        const min = parseInt(input.attr('min'));
        let quantity = parseInt(input.val());
        
        if (action === 'increase' && quantity < max) {
            quantity++;
        } else if (action === 'decrease' && quantity > min) {
            quantity--;
        } else {
            return;
        }
        
        input.val(quantity);
        updateCartItem(cartId, quantity, row);
    });
    
    // Thay đổi số lượng trực tiếp
    $('.cart-quantity').on('change', function() {
        const row = $(this).closest('tr');
        const cartId = row.data('cart-id');
        const quantity = parseInt($(this).val());
        const max = parseInt($(this).attr('max'));
        const min = parseInt($(this).attr('min'));
        
        if (quantity < min) {
            $(this).val(min);
            return;
        }
        if (quantity > max) {
            $(this).val(max);
            if (typeof Ajax !== 'undefined') {
                Ajax.showNotification('Vượt quá số lượng trong kho', 'error');
            } else {
                alert('Vượt quá số lượng trong kho');
            }
            return;
        }
        
        updateCartItem(cartId, quantity, row);
    });
    
    // Xóa sản phẩm khỏi giỏ hàng
    $('.remove-from-cart').on('click', function() {
        if (!confirm('Bạn có chắc muốn xóa sản phẩm này?')) {
            return;
        }

        const cartId = $(this).data('cart-id');
        const row = $(this).closest('tr');

        const url = (typeof SITE_URL !== 'undefined' ? SITE_URL : '') + '/ajax/cart-handler.php';
        $.ajax({
            url: url,
            method: 'POST',
            data: {
                action: 'remove',
                cart_id: cartId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    row.fadeOut(300, function() {
                        $(this).remove();
                        updateCartTotal();
                        updateCartCount(response.cart_count);

                        // Nếu giỏ hàng trống, reload trang
                        if (response.cart_count === 0) {
                            location.reload();
                        }
                    });
                    Ajax.showNotification(response.message, 'success');
                } else {
                    Ajax.showNotification(response.message, 'error');
                }
            },
            error: function() {
                if (typeof Ajax !== 'undefined') {
                    Ajax.showNotification('Có lỗi xảy ra, vui lòng thử lại', 'error');
                } else {
                    alert('Có lỗi xảy ra, vui lòng thử lại');
                }
            }
        });
    });
    
    // Hàm cập nhật item trong giỏ hàng
    function updateCartItem(cartId, quantity, row) {
        const url = (typeof SITE_URL !== 'undefined' ? SITE_URL : '') + '/ajax/cart-handler.php';
        $.ajax({
            url: url,
            method: 'POST',
            data: {
                action: 'update',
                cart_id: cartId,
                quantity: quantity
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    row.find('.item-subtotal').text(response.subtotal);
                    updateCartTotal();
                    updateCartCount(response.cart_count);
                } else {
                    Ajax.showNotification(response.message, 'error');
                }
            }
        });
    }
    
    // Hàm cập nhật tổng giỏ hàng
    function updateCartTotal() {
        let total = 0;
        $('.item-subtotal').each(function() {
            const price = parseFloat($(this).text().replace(/[^\d]/g, ''));
            total += price;
        });
        const formattedTotal = new Intl.NumberFormat('vi-VN').format(total) + 'đ';
        $('#cart-subtotal, #cart-total').text(formattedTotal);
    }
    
    // Hàm cập nhật số lượng giỏ hàng
    function updateCartCount(count) {
        // Tìm badge trong cart icon
        const cartIcon = $('.cart-icon');
        let badge = cartIcon.find('.cart-count');

        if (count > 0) {
            if (badge.length) {
                const oldCount = parseInt(badge.text()) || 0;
                badge.text(count).show();
                
                // Add bounce animation if count changed
                if (oldCount !== count) {
                    badge.addClass('updated');
                    setTimeout(() => {
                        badge.removeClass('updated');
                    }, 500);
                }
            } else {
                cartIcon.append(
                    `<span class="badge rounded-pill cart-count bg-info" style="display: inline-flex; align-items: center; justify-content: center; min-width: 20px; height: 20px; font-size: 11px; font-weight: bold; padding: 0; margin-left: 8px;">${count}</span>`
                );
            }
        } else {
            badge.hide();
        }
    }
    
    // Notification handled by Ajax Framework

    // ========================================
    // LUXURY TECH ENHANCEMENTS
    // ========================================

    // Product Card Hover Effects
    $('.product-card').on('mouseenter', function() {
        $(this).find('.card-actions').css('opacity', '1');
    }).on('mouseleave', function() {
        $(this).find('.card-actions').css('opacity', '0');
    });

    // Smooth Image Loading
    $('img').on('load', function() {
        $(this).addClass('loaded');
    }).each(function() {
        if (this.complete) {
            $(this).addClass('loaded');
        }
    });

    // Button Ripple Effect
    $('.btn').on('click', function(e) {
        const btn = $(this);
        const ripple = $('<span class="ripple-effect"></span>');
        const x = e.pageX - btn.offset().left;
        const y = e.pageY - btn.offset().top;

        ripple.css({
            left: x + 'px',
            top: y + 'px'
        });

        btn.append(ripple);

        setTimeout(() => ripple.remove(), 600);
    });

    // Navbar Scroll Effect
    let lastScroll = 0;
    $(window).on('scroll', function() {
        const currentScroll = $(this).scrollTop();
        const mainHeader = $('.main-header');

        if (currentScroll > 100) {
            mainHeader.addClass('scrolled');
        } else {
            mainHeader.removeClass('scrolled');
        }

        lastScroll = currentScroll;
    });

    // Form Input Focus Effects
    $('.form-control').on('focus', function() {
        $(this).parent().addClass('focused');
    }).on('blur', function() {
        $(this).parent().removeClass('focused');
    });

    // Lazy Load Images
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    if (img.dataset.src) {
                        img.src = img.dataset.src;
                        img.removeAttribute('data-src');
                    }
                    img.classList.add('fade-in');
                    observer.unobserve(img);
                }
            });
        });

        document.querySelectorAll('img[data-src]').forEach(img => {
            imageObserver.observe(img);
        });
    }

    // Tooltip Initialization
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Add loading state to buttons on form submit
    $('form').on('submit', function() {
        const submitBtn = $(this).find('button[type="submit"]');
        if (!submitBtn.hasClass('no-loading')) {
            submitBtn.prop('disabled', true);
            const originalText = submitBtn.html();
            submitBtn.data('original-text', originalText);
            submitBtn.html('<span class="spinner-border spinner-border-sm me-2"></span>Đang xử lý...');
        }
    });
});

// Add CSS for ripple effect
const rippleStyle = document.createElement('style');
rippleStyle.textContent = `
    .btn {
        position: relative;
        overflow: hidden;
    }
    .ripple-effect {
        position: absolute;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.4);
        transform: scale(0);
        animation: ripple 0.6s linear;
        pointer-events: none;
    }
    @keyframes ripple {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }
    .main-header.scrolled {
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    }
    img.fade-in {
        animation: fadeIn 0.5s ease;
    }
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    .input-group.focused .form-control,
    .input-group.focused .input-group-text {
        border-color: #c9a050 !important;
        box-shadow: 0 0 0 3px rgba(201, 160, 80, 0.15);
    }
`;
document.head.appendChild(rippleStyle);

// ========================================
// WISHLIST FUNCTIONALITY
// ========================================

// Toggle wishlist
$(document).on('click', '.wishlist-toggle-btn', function(e) {
    e.preventDefault();
    e.stopPropagation();
    
    const button = $(this);
    const productId = button.data('product-id');
    const icon = button.find('i');
    
    // Disable button during request
    button.prop('disabled', true);
    const originalIcon = icon.clone();
    icon.css('opacity', '0.5');
    
    // Lấy URL từ config hoặc dùng đường dẫn tương đối
    let url = 'ajax/wishlist.php';
    if (typeof window.SITE_CONFIG !== 'undefined' && window.SITE_CONFIG.siteUrl) {
        url = window.SITE_CONFIG.siteUrl + '/ajax/wishlist.php';
    } else if (typeof SITE_URL !== 'undefined') {
        url = SITE_URL + '/ajax/wishlist.php';
    }
    
    $.ajax({
        url: url,
        method: 'POST',
        data: {
            action: 'toggle',
            product_id: productId
        },
        dataType: 'json',
        success: function(response) {
            button.prop('disabled', false);
            icon.css('opacity', '1');
            
            console.log('Wishlist Response:', response);
            
            // Check if response is valid
            if (!response) {
                console.error('Invalid response from server');
                if (typeof Ajax !== 'undefined' && Ajax.showNotification) {
                    Ajax.showNotification('Phản hồi không hợp lệ từ server', 'error');
                } else {
                    alert('Phản hồi không hợp lệ từ server');
                }
                return;
            }
            
            if (response.success) {
                // Update icon based on action
                if (response.action === 'added') {
                    icon.removeClass('far').addClass('fas');
                    button.addClass('active');
                } else {
                    icon.removeClass('fas').addClass('far');
                    button.removeClass('active');
                }
                
                // Update wishlist count in header
                if (response.wishlist_count !== undefined) {
                    $('.wishlist-count').text(response.wishlist_count).show();
                    if (response.wishlist_count === 0) {
                        $('.wishlist-count').hide();
                    }
                }
                
                // If on wishlist page and product was added, reload page immediately to show new item
                if (response.action === 'added') {
                    const currentPath = window.location.pathname;
                    if (currentPath.includes('wishlist.php')) {
                        // Show notification nhanh rồi reload ngay
                        if (typeof Ajax !== 'undefined' && Ajax.showNotification) {
                            Ajax.showNotification(response.message, 'success');
                        }
                        // Reload ngay lập tức với cache-busting
                        setTimeout(function() {
                            const baseUrl = currentPath;
                            let queryString = window.location.search;
                            // Loại bỏ các tham số cache cũ
                            queryString = queryString.replace(/[?&]t=\d+/g, '').replace(/[?&]nocache=[^&]*/g, '');
                            const separator = queryString ? '&' : '?';
                            window.location.href = baseUrl + queryString + separator + 't=' + Date.now() + '&nocache=' + Math.random();
                        }, 200); // Delay ngắn để notification hiển thị
                        return;
                    }
                }
                
                // Show notification
                if (typeof Ajax !== 'undefined' && Ajax.showNotification) {
                    Ajax.showNotification(response.message, 'success');
                } else {
                    alert(response.message);
                }
            } else {
                // If response indicates login required, redirect to login
                if (response.message && (response.message.includes('đăng nhập') || response.message.includes('login') || response.requires_login)) {
                    const currentUrl = encodeURIComponent(window.location.href);
                    window.location.href = (typeof SITE_URL !== 'undefined' ? SITE_URL : '') + '/login.php?redirect=' + currentUrl;
                    return;
                }
                
                if (typeof Ajax !== 'undefined' && Ajax.showNotification) {
                    Ajax.showNotification(response.message || 'Có lỗi xảy ra', 'error');
                } else {
                    alert(response.message || 'Có lỗi xảy ra');
                }
            }
            
        },
        error: function(xhr, status, error) {
            button.prop('disabled', false);
            icon.css('opacity', '1');
            
            console.error('Wishlist AJAX Error:', {
                status: status,
                error: error,
                response: xhr.responseText,
                url: url,
                productId: productId
            });
            
            if (typeof Ajax !== 'undefined' && Ajax.showNotification) {
                Ajax.showNotification('Có lỗi xảy ra, vui lòng thử lại', 'error');
            } else {
                alert('Có lỗi xảy ra: ' + (error || 'Unknown error') + '\nVui lòng mở Console (F12) để xem chi tiết.');
            }
        }
    });
});

// Check wishlist status on page load
function updateWishlistButtons() {
    // Check for both logged-in and anonymous users
    $('.wishlist-toggle-btn').each(function() {
        const button = $(this);
        const productId = button.data('product-id');
        
        const url = (typeof SITE_URL !== 'undefined' ? SITE_URL : '') + '/ajax/wishlist.php';
        $.ajax({
            url: url,
            method: 'POST',
            data: {
                action: 'check',
                product_id: productId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success && response.in_wishlist) {
                    const icon = button.find('i');
                    icon.removeClass('far').addClass('fas');
                    button.addClass('active');
                }
            }
        });
    });
}

// Update wishlist buttons on page load
$(document).ready(function() {
    updateWishlistButtons();
});

// Update wishlist count
function updateWishlistCount() {
    // Works for both logged-in and anonymous users
    const url = (typeof SITE_URL !== 'undefined' ? SITE_URL : '') + '/ajax/wishlist.php';
    $.ajax({
        url: url,
        method: 'POST',
        data: { action: 'get_count' },
        dataType: 'json',
        success: function(response) {
            if (response.success && response.count !== undefined) {
                $('.wishlist-count').text(response.count);
                if (response.count > 0) {
                    $('.wishlist-count').show();
                } else {
                    $('.wishlist-count').hide();
                }
            }
        }
    });
}

// Export function
window.updateWishlistCount = updateWishlistCount;
