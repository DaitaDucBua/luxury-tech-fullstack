/**
 * Flash Sale Countdown Timer
 */

(function() {
    'use strict';
    
    let countdownInterval = null;
    
    // Initialize countdown
    function initCountdown() {
        const $countdown = $('.flash-sale-countdown');
        
        if ($countdown.length === 0) return;
        
        const startTime = new Date($countdown.data('start')).getTime();
        const endTime = new Date($countdown.data('end')).getTime();
        const now = new Date().getTime();
        
        let targetTime;
        
        if (now < startTime) {
            // Flash sale chưa bắt đầu - đếm ngược đến start time
            targetTime = startTime;
        } else if (now >= startTime && now < endTime) {
            // Flash sale đang diễn ra - đếm ngược đến end time
            targetTime = endTime;
        } else {
            // Flash sale đã kết thúc
            showEnded();
            return;
        }
        
        updateCountdown(targetTime);
        countdownInterval = setInterval(() => updateCountdown(targetTime), 1000);
    }
    
    // Update countdown display
    function updateCountdown(targetTime) {
        const now = new Date().getTime();
        const distance = targetTime - now;
        
        if (distance < 0) {
            clearInterval(countdownInterval);
            location.reload(); // Reload page when countdown ends
            return;
        }
        
        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);
        
        $('#hours').text(pad(hours));
        $('#minutes').text(pad(minutes));
        $('#seconds').text(pad(seconds));
        
        // Add ending soon animation if less than 10 minutes
        if (distance < 10 * 60 * 1000) {
            $('.flash-sale-countdown').addClass('flash-sale-ending-soon');
        }
    }
    
    // Show ended message
    function showEnded() {
        $('.flash-sale-countdown').html(`
            <div class="alert alert-warning">
                <i class="fas fa-clock"></i> Flash Sale đã kết thúc!
            </div>
        `);
    }
    
    // Pad number with zero
    function pad(num) {
        return num.toString().padStart(2, '0');
    }
    
    // Add to cart
    window.addToCart = function(productId) {
        const url = (typeof SITE_URL !== 'undefined' ? SITE_URL : '') + '/ajax/cart-handler.php';
        $.post(url, {
            action: 'add',
            product_id: productId,
            quantity: 1
        }, function(response) {
            if (response.success) {
                showToast('Đã thêm vào giỏ hàng!', 'success');

                // Update cart count
                if (typeof updateCartCount === 'function') {
                    updateCartCount(response.cart_count);
                }

                // Reload mini cart if exists
                if (typeof MiniCart !== 'undefined') {
                    MiniCart.load();
                }
            } else {
                showToast(response.message || 'Có lỗi xảy ra!', 'error');
            }
        }).fail(function() {
            showToast('Có lỗi xảy ra!', 'error');
        });
    };
    
    // Show toast notification
    function showToast(message, type = 'success') {
        const bgColor = type === 'success' ? '#28a745' : '#dc3545';
        const icon = type === 'success' ? 'check-circle' : 'times-circle';
        
        const toast = $(`
            <div class="flash-sale-toast">
                <i class="fas fa-${icon}"></i>
                <span>${message}</span>
            </div>
        `);
        
        toast.css({
            position: 'fixed',
            top: '20px',
            right: '20px',
            background: bgColor,
            color: 'white',
            padding: '15px 20px',
            borderRadius: '8px',
            boxShadow: '0 4px 12px rgba(0,0,0,0.2)',
            zIndex: 9999,
            display: 'flex',
            alignItems: 'center',
            gap: '10px',
            animation: 'slideInRight 0.3s ease'
        });
        
        $('body').append(toast);
        
        setTimeout(() => {
            toast.fadeOut(300, function() {
                $(this).remove();
            });
        }, 3000);
    }
    
    // Initialize on document ready
    $(document).ready(initCountdown);
})();

// Add CSS animation
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
`;
document.head.appendChild(style);

