<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Admin Dashboard'; ?> - <?php echo SITE_NAME; ?></title>
    
    <!-- Cache Control Headers -->
    <?php
    header("Cache-Control: no-cache, no-store, must-revalidate");
    header("Pragma: no-cache");
    header("Expires: 0");
    ?>

    <!-- Meta Cache Control -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Bootstrap CSS (giữ lại cho một số components cần thiết) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/admin.css?v=<?php echo getAssetVersion('assets/css/admin.css'); ?>">

    <!-- Admin AJAX CSS -->
    <link rel="stylesheet" href="assets/css/admin-ajax.css?v=<?php echo time(); ?>">
    
    <!-- Tailwind Config -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#c9a050',
                        'primary-dark': '#b08d3e',
                        'primary-light': '#dbb970',
                        secondary: '#1a1a2e',
                        'secondary-light': '#16213e',
                        'secondary-dark': '#0f0f1a',
                    }
                }
            }
        }
    </script>
</head>
<body>
    <!-- Top Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid px-4">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-gem"></i> LUXURYTECH Admin
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto flex items-center gap-3">
                    <li class="nav-item">
                        <a class="inline-flex items-center px-4 py-2 bg-primary/20 text-primary font-semibold rounded-lg hover:bg-primary/30 transition-colors border border-primary/30" 
                           href="../index.php" 
                           target="_blank">
                            <i class="fas fa-external-link-alt mr-2"></i> Xem website
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle flex items-center" 
                           href="#" 
                           id="userDropdown" 
                           role="button" 
                           data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle mr-2"></i> <?php echo htmlspecialchars($_SESSION['username']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="../profile.php"><i class="fas fa-user me-2"></i> Hồ sơ</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i> Đăng xuất</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="px-3 mb-3">
            <small class="text-uppercase" style="color: rgba(255,255,255,0.4); font-size: 0.7rem; letter-spacing: 1px;">Menu chính</small>
        </div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>" href="index.php">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'products.php' || basename($_SERVER['PHP_SELF']) == 'product-add.php' || basename($_SERVER['PHP_SELF']) == 'product-edit.php' ? 'active' : ''; ?>" href="products.php">
                    <i class="fas fa-box"></i> Sản phẩm
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : ''; ?>" href="categories.php">
                    <i class="fas fa-tags"></i> Danh mục
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : ''; ?>" href="orders.php">
                    <i class="fas fa-shopping-cart"></i> Đơn hàng
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>" href="users.php">
                    <i class="fas fa-users"></i> Người dùng
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'coupons.php' ? 'active' : ''; ?>" href="coupons.php">
                    <i class="fas fa-ticket-alt"></i> Mã giảm giá
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'flash-sales.php' ? 'active' : ''; ?>" href="flash-sales.php">
                    <i class="fas fa-bolt text-warning"></i> Flash Sale
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reviews.php' ? 'active' : ''; ?>" href="reviews.php">
                    <i class="fas fa-star"></i> Đánh giá
                </a>
            </li>
        </ul>
    </nav>

    <!-- Main Content -->
    <main>
        <?php displayMessage(); ?>

