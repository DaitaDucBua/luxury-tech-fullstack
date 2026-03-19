<?php
// main-config.php - Generates inline JavaScript configuration
// This file should be included in header.php to inject user state into JavaScript

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($conn)) {
    require_once __DIR__ . '/../config/config.php';
}

// Debug logging
error_log("main-config.php - isLoggedIn: " . (isLoggedIn() ? 'true' : 'false'));
if (isLoggedIn()) {
    error_log("main-config.php - user_id: " . $_SESSION['user_id']);
}
?>
<script>
    // Global JavaScript configuration
    window.SITE_CONFIG = {
        isLoggedIn: <?php echo isLoggedIn() ? 'true' : 'false'; ?>,
        userId: <?php echo isLoggedIn() ? $_SESSION['user_id'] : 'null'; ?>,
        userName: <?php echo isLoggedIn() ? json_encode($_SESSION['username']) : 'null'; ?>,
        userRole: <?php echo isLoggedIn() ? json_encode($_SESSION['role']) : 'null'; ?>,
        siteUrl: '<?php echo SITE_URL; ?>',
        loginUrl: '<?php echo SITE_URL; ?>/login.php'
    };

    // Helper function to check login status from JavaScript
    function isLoggedIn() {
        return window.SITE_CONFIG.isLoggedIn;
    }

    // Helper function to require login
    function requireLogin(redirectTo = null) {
        if (!isLoggedIn()) {
            const redirect = redirectTo || window.location.pathname + window.location.search;
            window.location.href = window.SITE_CONFIG.loginUrl + '?redirect=' + encodeURIComponent(redirect);
            return false;
        }
        return true;
    }

    // Log config for debugging
    console.log('SITE_CONFIG:', window.SITE_CONFIG);
</script>
