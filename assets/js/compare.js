/**
 * Product Compare Functionality
 */

(function() {
    'use strict';
    
    // Remove compare bar immediately if it exists
    function removeCompareBar() {
        const compareBar = document.getElementById('compareBar');
        if (compareBar) {
            compareBar.remove();
        }
        // Also remove by class in case ID doesn't match
        const compareBars = document.querySelectorAll('.compare-bar');
        compareBars.forEach(bar => bar.remove());
    }
    
    // Remove on page load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', removeCompareBar);
    } else {
        removeCompareBar();
    }
    
    // Also remove periodically to catch any dynamically created bars
    setInterval(removeCompareBar, 1000);
    
    // Update compare count on page load
    updateCompareCount();
    
    // Add to compare button click
    document.addEventListener('click', function(e) {
        if (e.target.closest('.compare-btn')) {
            e.preventDefault();
            const btn = e.target.closest('.compare-btn');
            const productId = btn.dataset.productId;
            if (productId) {
                addToCompare(productId);
            }
        }
        
        // Toggle compare button
        if (e.target.closest('.compare-toggle-btn')) {
            e.preventDefault();
            const btn = e.target.closest('.compare-toggle-btn');
            const productId = btn.dataset.productId;
            
            if (!productId) return;
            
            // Check if in compare
            fetch(`ajax/compare.php?action=check&product_id=${productId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.in_compare) {
                        removeFromCompare(productId);
                    } else {
                        addToCompare(productId);
                    }
                }
            })
            .catch(error => {
                console.error('Error checking compare status:', error);
            });
        }
    });
    
    /**
     * Add product to compare
     */
    function addToCompare(productId) {
        const formData = new FormData();
        formData.append('action', 'add');
        formData.append('product_id', productId);

        fetch('ajax/compare.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success notification
                if (typeof Ajax !== 'undefined') {
                    Ajax.showNotification(data.message, 'success');
                } else {
                    showToast('success', data.message);
                }
                
                // Update count
                updateCompareCount();
                
                // Update button state
                const btn = document.querySelector(`.compare-toggle-btn[data-product-id="${productId}"]`);
                if (btn) {
                    btn.classList.add('active');
                    btn.innerHTML = '<i class="fas fa-check-circle"></i> Đã thêm';
                }
                
                // Compare bar removed - no longer showing
                // showCompareBar();
                
                // If count >= 2, suggest going to compare page
                if (data.count >= 2) {
                    setTimeout(() => {
                        if (typeof Ajax !== 'undefined') {
                            // Add timestamp to avoid cache
                            const compareUrl = 'compare.php?t=' + Date.now();
                            Ajax.showNotification(`Bạn có ${data.count} sản phẩm. <a href="${compareUrl}" style="color: white; text-decoration: underline;">Xem so sánh ngay</a>`, 'info');
                        }
                    }, 1000);
                }
                
                // If user is on compare page, auto-refresh it
                if (window.location.pathname.includes('compare.php')) {
                    setTimeout(() => {
                        location.replace('compare.php?t=' + Date.now());
                    }, 500);
                }
            } else {
                if (typeof Ajax !== 'undefined') {
                    Ajax.showNotification(data.message, 'error');
                } else {
                    showToast('error', data.message);
                }
            }
        })
        .catch(error => {
            console.error('Error adding to compare:', error);
            if (typeof Ajax !== 'undefined') {
                Ajax.showNotification('Có lỗi xảy ra. Vui lòng thử lại!', 'error');
            } else {
                showToast('error', 'Có lỗi xảy ra. Vui lòng thử lại!');
            }
        });
    }
    
    /**
     * Remove product from compare
     */
    function removeFromCompare(productId) {
        const formData = new FormData();
        formData.append('action', 'remove');
        formData.append('product_id', productId);

        fetch('ajax/compare.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (typeof Ajax !== 'undefined') {
                    Ajax.showNotification(data.message, 'success');
                } else {
                    showToast('success', data.message);
                }
                
                updateCompareCount();
                
                // Update button state
                const btn = document.querySelector(`.compare-toggle-btn[data-product-id="${productId}"]`);
                if (btn) {
                    btn.classList.remove('active');
                    btn.innerHTML = '<i class="fas fa-balance-scale"></i> So sánh';
                }
                
                // Compare bar removed - no longer showing
                // if (data.count === 0) {
                //     hideCompareBar();
                // }
            } else {
                if (typeof Ajax !== 'undefined') {
                    Ajax.showNotification(data.message, 'error');
                } else {
                    showToast('error', data.message);
                }
            }
        })
        .catch(error => {
            console.error('Error removing from compare:', error);
            if (typeof Ajax !== 'undefined') {
                Ajax.showNotification('Có lỗi xảy ra. Vui lòng thử lại!', 'error');
            } else {
                showToast('error', 'Có lỗi xảy ra. Vui lòng thử lại!');
            }
        });
    }
    
    /**
     * Update compare count badge
     */
    function updateCompareCount() {
        fetch('ajax/compare.php?action=get_list')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const count = data.count;
                
                // Update badge
                document.querySelectorAll('.compare-count').forEach(badge => {
                    badge.textContent = count;
                    if (count > 0) {
                        badge.style.display = 'inline-block';
                    } else {
                        badge.style.display = 'none';
                    }
                });
                
                // Compare bar removed - no longer showing
                // if (count > 0) {
                //     showCompareBar();
                // } else {
                //     hideCompareBar();
                // }
                
                // Update button states
                if (data.products) {
                    data.products.forEach(product => {
                        const btn = document.querySelector(`.compare-toggle-btn[data-product-id="${product.id}"]`);
                        if (btn) {
                            btn.classList.add('active');
                            btn.innerHTML = '<i class="fas fa-check-circle"></i> Đã thêm';
                        }
                    });
                }
            }
        })
        .catch(error => {
            console.error('Error updating compare count:', error);
        });
    }
    
    /**
     * Compare bar removed - no longer used
     */
    // function showCompareBar() { ... }
    // function hideCompareBar() { ... }
    // function createCompareBar() { ... }
    
    /**
     * Show toast notification (use Ajax.showNotification)
     */
    function showToast(type, message) {
        // Use Ajax.showNotification if available
        if (typeof Ajax !== 'undefined' && typeof Ajax.showNotification === 'function') {
            Ajax.showNotification(message, type);
            return;
        }
        
        // Fallback to global showNotification
        if (typeof window.showNotification === 'function') {
            window.showNotification(message, type);
            return;
        }
        
        // Final fallback to alert
        alert(message);
    }
    
    // Export to global scope
    window.addToCompare = addToCompare;
    window.removeFromCompare = removeFromCompare;
    window.updateCompareCount = updateCompareCount;
    
})();

/**
 * Clear compare list (global function)
 */
function clearCompareList() {
    if (!confirm('Xóa tất cả sản phẩm khỏi danh sách so sánh?')) return;
    
    const formData = new FormData();
    formData.append('action', 'clear');

    fetch('ajax/compare.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (typeof Ajax !== 'undefined') {
                Ajax.showNotification(data.message, 'success');
            }
            location.reload();
        } else {
            if (typeof Ajax !== 'undefined') {
                Ajax.showNotification(data.message, 'error');
            } else {
                alert(data.message);
            }
        }
    })
    .catch(error => {
        console.error('Error clearing compare:', error);
        if (typeof Ajax !== 'undefined') {
            Ajax.showNotification('Có lỗi xảy ra. Vui lòng thử lại!', 'error');
        } else {
            alert('Có lỗi xảy ra');
        }
    });
}

