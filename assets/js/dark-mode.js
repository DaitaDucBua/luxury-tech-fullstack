// =====================================================
// DARK MODE FUNCTIONALITY
// =====================================================

(function() {
    'use strict';
    
    // Get saved theme or default to light
    const savedTheme = localStorage.getItem('theme') || 'light';
    
    // Apply theme on page load
    document.documentElement.setAttribute('data-theme', savedTheme);
    
    // Wait for DOM to be ready
    document.addEventListener('DOMContentLoaded', function() {
        // Create dark mode toggle button
        const toggleButton = document.createElement('button');
        toggleButton.className = 'dark-mode-toggle';
        toggleButton.innerHTML = savedTheme === 'dark' ? '<i class="fas fa-sun"></i>' : '<i class="fas fa-moon"></i>';
        toggleButton.setAttribute('aria-label', 'Toggle Dark Mode');
        toggleButton.setAttribute('title', 'Chuyển đổi chế độ sáng/tối');
        document.body.appendChild(toggleButton);
        
        // Toggle dark mode
        toggleButton.addEventListener('click', function() {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            // Add rotation animation
            this.classList.add('rotating');
            setTimeout(() => this.classList.remove('rotating'), 500);
            
            // Update theme
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            
            // Update icon
            this.innerHTML = newTheme === 'dark' ? '<i class="fas fa-sun"></i>' : '<i class="fas fa-moon"></i>';
            
            // Show notification
            if (typeof showNotification === 'function') {
                const message = newTheme === 'dark' ? 'Đã bật chế độ tối' : 'Đã bật chế độ sáng';
                showNotification(message, 'success');
            }
        });
        
        // Keyboard shortcut: Ctrl + Shift + D
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.shiftKey && e.key === 'D') {
                e.preventDefault();
                toggleButton.click();
            }
        });
    });
    
    // Auto dark mode based on system preference (optional)
    if (window.matchMedia) {
        const darkModeQuery = window.matchMedia('(prefers-color-scheme: dark)');
        
        // Only apply if user hasn't set preference
        if (!localStorage.getItem('theme')) {
            const theme = darkModeQuery.matches ? 'dark' : 'light';
            document.documentElement.setAttribute('data-theme', theme);
        }
        
        // Listen for system theme changes
        darkModeQuery.addEventListener('change', function(e) {
            // Only apply if user hasn't set preference
            if (!localStorage.getItem('theme')) {
                const theme = e.matches ? 'dark' : 'light';
                document.documentElement.setAttribute('data-theme', theme);
                
                // Update button icon if it exists
                const toggleButton = document.querySelector('.dark-mode-toggle');
                if (toggleButton) {
                    toggleButton.innerHTML = theme === 'dark' ? '<i class="fas fa-sun"></i>' : '<i class="fas fa-moon"></i>';
                }
            }
        });
    }
})();

// Export functions for external use
window.DarkMode = {
    toggle: function() {
        const button = document.querySelector('.dark-mode-toggle');
        if (button) button.click();
    },
    
    set: function(theme) {
        if (theme !== 'light' && theme !== 'dark') {
            console.error('Invalid theme. Use "light" or "dark"');
            return;
        }
        
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('theme', theme);
        
        const button = document.querySelector('.dark-mode-toggle');
        if (button) {
            button.innerHTML = theme === 'dark' ? '<i class="fas fa-sun"></i>' : '<i class="fas fa-moon"></i>';
        }
    },
    
    get: function() {
        return document.documentElement.getAttribute('data-theme') || 'light';
    },
    
    reset: function() {
        localStorage.removeItem('theme');
        const systemTheme = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        this.set(systemTheme);
    }
};

