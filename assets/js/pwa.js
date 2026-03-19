/**
 * PWA Installation and Service Worker Registration
 */

(function() {
    'use strict';
    
    let deferredPrompt = null;
    
    // Register service worker
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/LUXURYTECH/service-worker.js')
                .then(registration => {
                    console.log('[PWA] Service Worker registered:', registration);
                    
                    // Check for updates every hour
                    setInterval(() => {
                        registration.update();
                    }, 60 * 60 * 1000);
                })
                .catch(error => {
                    console.error('[PWA] Service Worker registration failed:', error);
                });
        });
    }
    
    // Handle install prompt
    window.addEventListener('beforeinstallprompt', (e) => {
        console.log('[PWA] Install prompt triggered');
        
        // Prevent the mini-infobar from appearing
        e.preventDefault();
        
        // Save the event for later
        deferredPrompt = e;
        
        // Show install button
        showInstallButton();
    });
    
    // Show install button
    function showInstallButton() {
        const installBtn = createInstallButton();
        document.body.appendChild(installBtn);
        
        // Auto-hide after 10 seconds
        setTimeout(() => {
            installBtn.classList.add('hide');
            setTimeout(() => installBtn.remove(), 300);
        }, 10000);
    }
    
    // Create install button
    function createInstallButton() {
        const btn = document.createElement('div');
        btn.className = 'pwa-install-prompt';
        btn.innerHTML = `
            <div class="pwa-install-content">
                <div class="pwa-install-icon">
                    <i class="fas fa-mobile-alt"></i>
                </div>
                <div class="pwa-install-text">
                    <strong>Cài đặt LuxuryTech</strong>
                    <p>Truy cập nhanh hơn, trải nghiệm tốt hơn!</p>
                </div>
                <button class="btn btn-primary btn-sm pwa-install-btn">
                    <i class="fas fa-download"></i> Cài Đặt
                </button>
                <button class="btn btn-link btn-sm pwa-close-btn">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        
        // Install button click
        btn.querySelector('.pwa-install-btn').addEventListener('click', async () => {
            if (!deferredPrompt) return;
            
            // Show the install prompt
            deferredPrompt.prompt();
            
            // Wait for the user's response
            const { outcome } = await deferredPrompt.userChoice;
            console.log('[PWA] User choice:', outcome);
            
            if (outcome === 'accepted') {
                console.log('[PWA] App installed');
            }
            
            // Clear the deferred prompt
            deferredPrompt = null;
            
            // Hide the button
            btn.classList.add('hide');
            setTimeout(() => btn.remove(), 300);
        });
        
        // Close button click
        btn.querySelector('.pwa-close-btn').addEventListener('click', () => {
            btn.classList.add('hide');
            setTimeout(() => btn.remove(), 300);
        });
        
        return btn;
    }
    
    // Handle app installed
    window.addEventListener('appinstalled', () => {
        console.log('[PWA] App installed successfully');
        deferredPrompt = null;
        
        // Show success message
        showToast('Đã cài đặt LuxuryTech thành công!', 'success');
    });
    
    // Request notification permission
    function requestNotificationPermission() {
        if (!('Notification' in window)) {
            console.log('[PWA] Notifications not supported');
            return;
        }
        
        if (Notification.permission === 'default') {
            Notification.requestPermission().then(permission => {
                console.log('[PWA] Notification permission:', permission);
            });
        }
    }
    
    // Show toast notification
    function showToast(message, type = 'success') {
        const bgColor = type === 'success' ? '#28a745' : '#dc3545';
        const icon = type === 'success' ? 'check-circle' : 'times-circle';
        
        const toast = document.createElement('div');
        toast.className = 'pwa-toast';
        toast.innerHTML = `
            <i class="fas fa-${icon}"></i>
            <span>${message}</span>
        `;
        
        toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${bgColor};
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            z-index: 9999;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideInRight 0.3s ease;
        `;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
    
    // Check if running as PWA
    function isPWA() {
        return window.matchMedia('(display-mode: standalone)').matches ||
               window.navigator.standalone === true;
    }
    
    // Log PWA status
    if (isPWA()) {
        console.log('[PWA] Running as installed app');
    } else {
        console.log('[PWA] Running in browser');
    }
    
    // Request notification permission after 5 seconds
    setTimeout(requestNotificationPermission, 5000);
    
    // Export API
    window.PWA = {
        isPWA: isPWA,
        requestNotificationPermission: requestNotificationPermission
    };
})();

// Add CSS animations
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
    
    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
    
    .pwa-install-prompt {
        position: fixed;
        bottom: 20px;
        left: 20px;
        right: 20px;
        max-width: 500px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 8px 24px rgba(0,0,0,0.2);
        z-index: 9998;
        animation: slideInRight 0.3s ease;
    }
    
    .pwa-install-prompt.hide {
        animation: slideOutRight 0.3s ease;
    }
    
    .pwa-install-content {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 20px;
    }
    
    .pwa-install-icon {
        font-size: 32px;
        color: #2f80ed;
    }
    
    .pwa-install-text {
        flex: 1;
    }
    
    .pwa-install-text strong {
        display: block;
        font-size: 16px;
        margin-bottom: 5px;
    }
    
    .pwa-install-text p {
        margin: 0;
        font-size: 14px;
        color: #666;
    }
    
    .pwa-close-btn {
        color: #999;
        padding: 5px 10px;
    }
    
    @media (max-width: 768px) {
        .pwa-install-prompt {
            left: 10px;
            right: 10px;
        }
        
        .pwa-install-content {
            padding: 15px;
        }
    }
`;
document.head.appendChild(style);

