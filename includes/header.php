<?php
if (!isset($conn)) {
    require_once __DIR__ . '/../config/config.php';
}

// Không cần định nghĩa cache_version ở đây, sẽ dùng hàm getAssetVersion() trực tiếp

// Lấy danh mục
$categories_sql = "SELECT * FROM categories ORDER BY name";
$categories_result = $conn->query($categories_sql);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?><?php echo SITE_NAME; ?></title>
    
    <!-- Cache Control Headers -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            DEFAULT: '#2563eb',
                            light: '#3b82f6',
                            dark: '#1d4ed8',
                            hover: '#1e40af',
                        },
                        accent: {
                            blue: '#4361ee',
                            purple: '#7209b7',
                            cyan: '#00d9ff',
                            green: '#00c896',
                            red: '#ff4757',
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                        display: ['Inter', 'system-ui', 'sans-serif'],
                    }
                }
            }
        }
    </script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts - Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- AOS - Animate On Scroll -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <!-- Swiper CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">

    <!-- GLightbox CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css?v=<?php echo getAssetVersion('assets/css/style.css'); ?>">

    <!-- Live Chat CSS -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/live-chat.css?v=<?php echo getAssetVersion('assets/css/live-chat.css'); ?>">

    <!-- Social Login CSS -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/social-login.css?v=<?php echo getAssetVersion('assets/css/social-login.css'); ?>">

    <!-- Flash Sale CSS -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/flash-sale.css?v=<?php echo getAssetVersion('assets/css/flash-sale.css'); ?>">

    <!-- Compare CSS -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/compare.css?v=<?php echo getAssetVersion('assets/css/compare.css'); ?>">

    <!-- Mini Cart CSS -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/mini-cart.css?v=<?php echo getAssetVersion('assets/css/mini-cart.css'); ?>">

    <!-- Mega Menu CSS -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/mega-menu.css?v=<?php echo getAssetVersion('assets/css/mega-menu.css'); ?>">

    <!-- Order Tracking CSS -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/order-tracking.css?v=<?php echo getAssetVersion('assets/css/order-tracking.css'); ?>">

    <!-- Advanced Search CSS -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/advanced-search.css?v=<?php echo getAssetVersion('assets/css/advanced-search.css'); ?>">
    
    <!-- UI Enhancements CSS -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/ui-enhancements.css?v=<?php echo getAssetVersion('assets/css/ui-enhancements.css'); ?>">

    <!-- PWA Manifest -->
    <link rel="manifest" href="<?php echo SITE_URL; ?>/manifest.json">
    <meta name="theme-color" content="#2f80ed">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="LuxuryTech">
    <link rel="apple-touch-icon" href="<?php echo SITE_URL; ?>/assets/images/icons/icon-192x192.png">

    <!-- Main Configuration (MUST be before main.js) -->
    <?php include __DIR__ . '/../assets/js/main-config.php'; ?>
</head>
<body>
    <!-- Page Loader -->
    <div class="page-loader">
        <div class="loader-content">
            <div class="loader-logo"><?php echo SITE_NAME; ?></div>
            <div class="loader-spinner"></div>
        </div>
    </div>

    <!-- Top Header -->
    <div class="bg-white border-b border-gray-100 py-2.5 hidden md:block">
        <div class="container mx-auto px-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4 text-sm text-gray-600">
                    <div class="flex items-center gap-1.5">
                        <i class="fas fa-phone-alt text-primary"></i>
                        <span>Hotline: <strong class="text-gray-900 font-medium"><?php echo SITE_PHONE; ?></strong></span>
                    </div>
                    <span class="text-gray-300">|</span>
                    <div class="flex items-center gap-1.5">
                        <i class="fas fa-clock"></i>
                        <span>8:00 - 21:30</span>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <div id="user-info-container" class="flex items-center gap-2 text-sm">
                        <?php if (isLoggedIn()): ?>
                            <span class="text-primary font-medium flex items-center gap-1">
                                <i class="fas fa-user-circle"></i>
                                <?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Khách'); ?>
                            </span>
                            <span class="text-gray-300">|</span>
                            <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
                            <a href="<?php echo SITE_URL; ?>/admin/" class="text-gray-600 hover:text-primary transition-colors flex items-center gap-1">
                                <i class="fas fa-shield-alt"></i>Admin
                            </a>
                            <span class="text-gray-300">|</span>
                            <?php endif; ?>
                            <a href="<?php echo SITE_URL; ?>/profile.php" class="text-gray-600 hover:text-primary transition-colors">Tài khoản</a>
                            <span class="text-gray-300">|</span>
                            <a href="<?php echo SITE_URL; ?>/orders.php" class="text-gray-600 hover:text-primary transition-colors">Đơn hàng</a>
                            <span class="text-gray-300">|</span>
                            <a href="<?php echo SITE_URL; ?>/logout.php" class="text-gray-600 hover:text-primary transition-colors" onclick="return handleLogout(event)">Đăng xuất</a>
                        <?php else: ?>
                            <a href="<?php echo SITE_URL; ?>/login.php" class="text-gray-600 hover:text-primary transition-colors flex items-center gap-1">
                                <i class="fas fa-sign-in-alt"></i> Đăng nhập
                            </a>
                            <span class="text-gray-300">|</span>
                            <a href="<?php echo SITE_URL; ?>/register.php" class="text-primary font-medium hover:text-primary-dark transition-colors flex items-center gap-1">
                                <i class="fas fa-user-plus"></i> Đăng ký
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Header -->
    <header class="sticky top-0 z-50 bg-white border-b border-gray-100 shadow-sm backdrop-blur-sm bg-white/95">
        <div class="container mx-auto px-4">
            <div class="flex items-center justify-between py-4 gap-4">
                <!-- Logo -->
                <div class="flex-shrink-0">
                    <a href="<?php echo SITE_URL; ?>/index.php" class="flex items-center group">
                        <h3 class="text-2xl font-bold text-primary group-hover:text-primary-dark transition-colors tracking-tight">
                            <?php echo SITE_NAME; ?>
                        </h3>
                    </a>
                </div>

                <!-- Search Bar -->
                <div class="hidden md:flex flex-1 max-w-2xl mx-4">
                    <form action="<?php echo SITE_URL; ?>/search.php" method="GET" class="w-full">
                        <div class="flex rounded-full border border-gray-200 shadow-sm focus-within:border-primary focus-within:ring-2 focus-within:ring-primary/20 transition-all overflow-hidden">
                            <input type="text" name="q" class="flex-1 px-5 py-3 text-sm border-0 focus:outline-none focus:ring-0" placeholder="Tìm kiếm sản phẩm..." required>
                            <button type="submit" class="px-6 bg-primary text-white font-medium hover:bg-primary-dark transition-colors flex items-center gap-2">
                                <i class="fas fa-search"></i>
                                <span class="hidden lg:inline">Tìm kiếm</span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Cart & User Actions -->
                <div class="flex items-center gap-3">
                    <!-- Wishlist -->
                    <a href="<?php echo SITE_URL; ?>/wishlist.php" class="relative p-2 text-gray-600 hover:text-primary transition-colors rounded-lg hover:bg-gray-50" title="Yêu thích">
                        <i class="fas fa-heart text-lg"></i>
                        <?php if (isLoggedIn()): ?>
                        <span class="wishlist-count absolute -top-1 -right-1 bg-primary text-white text-xs font-semibold rounded-full min-w-[20px] h-5 flex items-center justify-center px-1.5 hidden">0</span>
                        <?php endif; ?>
                    </a>

                    <!-- Compare -->
                    <a href="<?php echo SITE_URL; ?>/compare.php?t=<?php echo time(); ?>&nocache=<?php echo rand(1000, 9999); ?>" class="relative hidden lg:block p-2 text-gray-600 hover:text-primary transition-colors rounded-lg hover:bg-gray-50" title="So sánh" onclick="this.href='<?php echo SITE_URL; ?>/compare.php?t=' + Date.now() + '&nocache=' + Math.random();">
                        <i class="fas fa-balance-scale text-lg"></i>
                        <span class="compare-count absolute -top-1 -right-1 bg-primary text-white text-xs font-semibold rounded-full min-w-[20px] h-5 flex items-center justify-center px-1.5 hidden">0</span>
                    </a>

                    <!-- Cart -->
                    <a href="<?php echo SITE_URL; ?>/cart.php?t=<?php echo time(); ?>" class="relative p-2 text-gray-600 hover:text-primary transition-colors rounded-lg hover:bg-gray-50 cart-icon" id="miniCartToggle" data-cart-url="<?php echo SITE_URL; ?>/cart.php" title="Giỏ hàng">
                        <i class="fas fa-shopping-cart text-lg"></i>
                        <?php
                        $cart_count = getCartCount();
                        if ($cart_count > 0):
                        ?>
                        <span class="cart-count absolute -top-1 -right-1 bg-primary text-white text-xs font-semibold rounded-full min-w-[20px] h-5 flex items-center justify-center px-1.5">
                            <?php echo $cart_count; ?>
                        </span>
                        <?php endif; ?>
                    </a>

                    <!-- Mobile Menu Toggle -->
                    <button id="mobileMenuToggle" class="md:hidden p-2 text-gray-600 hover:text-primary transition-colors rounded-lg hover:bg-gray-50" type="button">
                        <i class="fas fa-bars text-lg"></i>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Navigation -->
    <nav class="bg-white border-b border-gray-100 hidden md:block">
        <div class="container mx-auto px-4">
            <div id="navbarNav" class="flex items-center gap-1">
                <a href="<?php echo SITE_URL; ?>/index.php" class="px-5 py-4 text-sm font-medium text-gray-700 hover:text-primary hover:bg-gray-50 transition-colors rounded-lg flex items-center gap-1.5">
                    <i class="fas fa-home"></i>
                    <span>Trang chủ</span>
                </a>
                <?php while ($category = $categories_result->fetch_assoc()): ?>
                <a href="<?php echo SITE_URL; ?>/products.php?category=<?php echo $category['slug']; ?>" class="px-5 py-4 text-sm font-medium text-gray-700 hover:text-primary hover:bg-gray-50 transition-colors rounded-lg">
                    <?php echo htmlspecialchars($category['name']); ?>
                </a>
                <?php endwhile; ?>
                <a href="<?php echo SITE_URL; ?>/products.php?featured=1" class="px-5 py-4 text-sm font-medium text-primary hover:bg-primary/10 transition-colors rounded-lg flex items-center gap-1.5">
                    <i class="fas fa-crown"></i>
                    <span>Nổi bật</span>
                </a>
                <a href="<?php echo SITE_URL; ?>/flash-sale.php" class="px-5 py-4 text-sm font-bold text-red-600 hover:bg-red-50 transition-colors rounded-lg flex items-center gap-1.5">
                    <i class="fas fa-bolt"></i>
                    <span>Flash Sale</span>
                </a>
            </div>
        </div>
    </nav>

    <!-- Mobile Menu -->
    <div id="mobileMenu" class="hidden md:hidden bg-white border-b border-gray-100">
        <div class="container mx-auto px-4 py-4">
            <!-- Mobile Search -->
            <form action="<?php echo SITE_URL; ?>/search.php" method="GET" class="mb-4">
                <div class="flex rounded-lg border border-gray-200 overflow-hidden">
                    <input type="text" name="q" class="flex-1 px-4 py-2.5 text-sm border-0 focus:outline-none focus:ring-0" placeholder="Tìm kiếm..." required>
                    <button type="submit" class="px-4 bg-primary text-white hover:bg-primary-dark transition-colors">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>

            <!-- Mobile Navigation -->
            <div class="space-y-1">
                <a href="<?php echo SITE_URL; ?>/index.php" class="block px-4 py-3 text-sm font-medium text-gray-700 hover:text-primary hover:bg-gray-50 transition-colors rounded-lg flex items-center gap-2">
                    <i class="fas fa-home w-5"></i>
                    <span>Trang chủ</span>
                </a>
                <?php 
                // Reset categories result pointer
                $categories_result->data_seek(0);
                while ($category = $categories_result->fetch_assoc()): 
                ?>
                <a href="<?php echo SITE_URL; ?>/products.php?category=<?php echo $category['slug']; ?>" class="block px-4 py-3 text-sm font-medium text-gray-700 hover:text-primary hover:bg-gray-50 transition-colors rounded-lg">
                    <?php echo htmlspecialchars($category['name']); ?>
                </a>
                <?php endwhile; ?>
                <a href="<?php echo SITE_URL; ?>/products.php?featured=1" class="block px-4 py-3 text-sm font-medium text-primary hover:bg-primary/10 transition-colors rounded-lg flex items-center gap-2">
                    <i class="fas fa-crown w-5"></i>
                    <span>Nổi bật</span>
                </a>
                <a href="<?php echo SITE_URL; ?>/flash-sale.php" class="block px-4 py-3 text-sm font-bold text-red-600 hover:bg-red-50 transition-colors rounded-lg flex items-center gap-2">
                    <i class="fas fa-bolt w-5"></i>
                    <span>Flash Sale</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <main class="min-h-screen bg-gray-50 py-6">
        <div class="container mx-auto px-4">
            <?php displayMessage(); ?>

