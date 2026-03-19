/**
 * Mega Menu Functionality
 */

(function() {
    'use strict';
    
    // Mobile menu toggle
    $('.nav-item.has-mega-menu > .nav-link').on('click', function(e) {
        if ($(window).width() < 992) {
            e.preventDefault();
            $(this).parent().toggleClass('active');
            $(this).parent().siblings().removeClass('active');
        }
    });
    
    // Close mega menu when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.nav-item.has-mega-menu').length) {
            $('.nav-item.has-mega-menu').removeClass('active');
        }
    });
    
    // Sticky header on scroll
    let lastScroll = 0;
    $(window).on('scroll', function() {
        const currentScroll = $(this).scrollTop();
        
        if (currentScroll > 100) {
            $('.main-nav').addClass('sticky');
        } else {
            $('.main-nav').removeClass('sticky');
        }
        
        lastScroll = currentScroll;
    });
    
    // Load featured products for mega menu
    loadMegaMenuProducts();
    
    /**
     * Load featured products for mega menu
     */
    function loadMegaMenuProducts() {
        // This would typically load from an AJAX endpoint
        // For now, we'll use static data
        
        // You can implement AJAX loading here if needed
        // $.get('ajax/get-featured-products.php', function(data) {
        //     // Populate mega menu with products
        // });
    }
    
    // Prevent mega menu from closing when clicking inside
    $('.mega-menu').on('click', function(e) {
        e.stopPropagation();
    });
    
    // Keyboard navigation
    $('.nav-item.has-mega-menu > .nav-link').on('keydown', function(e) {
        // Enter or Space to toggle
        if (e.keyCode === 13 || e.keyCode === 32) {
            e.preventDefault();
            $(this).parent().toggleClass('active');
        }
        
        // Escape to close
        if (e.keyCode === 27) {
            $(this).parent().removeClass('active');
        }
    });
    
    // Accessibility: Focus management
    $('.mega-menu a').on('focus', function() {
        $(this).closest('.nav-item.has-mega-menu').addClass('active');
    });
    
    // Close mega menu on window resize
    $(window).on('resize', function() {
        if ($(window).width() >= 992) {
            $('.nav-item.has-mega-menu').removeClass('active');
        }
    });
    
})();

