/**
 * UI Enhancements - LuxuryTech
 * Premium UI/UX JavaScript
 */

(function() {
    'use strict';

    // ========================================
    // 1. STICKY HEADER ON SCROLL
    // ========================================
    const navbar = document.querySelector('.navbar-main');
    
    if (navbar) {
        let lastScroll = 0;
        
        window.addEventListener('scroll', () => {
            const currentScroll = window.pageYOffset;
            
            if (currentScroll > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
            
            lastScroll = currentScroll;
        });
    }

    // ========================================
    // 2. BACK TO TOP BUTTON
    // ========================================
    const backToTopBtn = document.createElement('button');
    backToTopBtn.className = 'back-to-top';
    backToTopBtn.innerHTML = '<i class="fas fa-chevron-up"></i>';
    backToTopBtn.setAttribute('aria-label', 'Back to top');
    document.body.appendChild(backToTopBtn);

    window.addEventListener('scroll', () => {
        if (window.pageYOffset > 300) {
            backToTopBtn.classList.add('visible');
        } else {
            backToTopBtn.classList.remove('visible');
        }
    });

    backToTopBtn.addEventListener('click', () => {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });

    // ========================================
    // 3. TOAST NOTIFICATION SYSTEM
    // ========================================
    window.LuxuryToast = {
        container: null,
        
        init() {
            if (!this.container) {
                this.container = document.createElement('div');
                this.container.className = 'toast-container';
                document.body.appendChild(this.container);
            }
        },
        
        show(message, type = 'info', title = '', duration = 5000) {
            this.init();
            
            const icons = {
                success: 'fa-check',
                error: 'fa-times',
                warning: 'fa-exclamation',
                info: 'fa-info'
            };
            
            const titles = {
                success: 'Thành công!',
                error: 'Lỗi!',
                warning: 'Cảnh báo!',
                info: 'Thông báo'
            };
            
            const toast = document.createElement('div');
            toast.className = `toast-notification ${type}`;
            toast.innerHTML = `
                <div class="toast-icon">
                    <i class="fas ${icons[type]}"></i>
                </div>
                <div class="toast-content">
                    <div class="toast-title">${title || titles[type]}</div>
                    <div class="toast-message">${message}</div>
                </div>
                <button class="toast-close">
                    <i class="fas fa-times"></i>
                </button>
                <div class="toast-progress"></div>
            `;
            
            this.container.appendChild(toast);
            
            // Close button
            toast.querySelector('.toast-close').addEventListener('click', () => {
                this.close(toast);
            });
            
            // Auto close
            setTimeout(() => {
                this.close(toast);
            }, duration);
        },
        
        close(toast) {
            toast.style.animation = 'slideOut 0.4s ease forwards';
            setTimeout(() => {
                toast.remove();
            }, 400);
        },
        
        success(message, title) { this.show(message, 'success', title); },
        error(message, title) { this.show(message, 'error', title); },
        warning(message, title) { this.show(message, 'warning', title); },
        info(message, title) { this.show(message, 'info', title); }
    };

    // ========================================
    // 4. BUTTON RIPPLE EFFECT
    // ========================================
    document.addEventListener('click', (e) => {
        const button = e.target.closest('.btn-ripple, .btn-luxury, .btn-primary, .btn-outline-primary');
        
        if (button) {
            const ripple = document.createElement('span');
            ripple.className = 'ripple';
            
            const rect = button.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            
            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = (e.clientX - rect.left - size / 2) + 'px';
            ripple.style.top = (e.clientY - rect.top - size / 2) + 'px';
            
            button.appendChild(ripple);
            
            setTimeout(() => ripple.remove(), 600);
        }
    });

    // ========================================
    // 5. PAGE LOADER
    // ========================================
    window.addEventListener('load', () => {
        const loader = document.querySelector('.page-loader');
        if (loader) {
            setTimeout(() => {
                loader.classList.add('hidden');
            }, 500);
        }
    });
})();

