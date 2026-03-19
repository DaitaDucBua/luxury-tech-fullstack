-- =====================================================
-- LUXURYTECH - E-COMMERCE DATABASE
-- Website Thương Mại Điện Tử giống Thế Giới Di Động
-- =====================================================
-- Hướng dẫn import:
-- 1. Xóa database cũ (nếu có)
-- 2. Import file này (nó sẽ tự tạo database)
-- 3. Tài khoản admin: username=admin, password=admin123
-- =====================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- Xóa database cũ và tạo mới
DROP DATABASE IF EXISTS `luxurytech`;
CREATE DATABASE `luxurytech` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `luxurytech`;

-- =====================================================
-- BẢNG USERS (NGƯỜI DÙNG)
-- =====================================================
CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) UNIQUE NOT NULL,
    `email` VARCHAR(100) UNIQUE NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `facebook_id` VARCHAR(255) NULL,
    `google_id` VARCHAR(255) NULL,
    `avatar` VARCHAR(500) NULL,
    `role` ENUM('admin', 'customer') NOT NULL DEFAULT 'customer',
    `full_name` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(20),
    `address` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- BẢNG CATEGORIES (DANH MỤC SẢN PHẨM)
-- =====================================================
CREATE TABLE `categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) UNIQUE NOT NULL,
    `description` TEXT,
    `image` VARCHAR(255),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- BẢNG PRODUCTS (SẢN PHẨM)
-- =====================================================
CREATE TABLE `products` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `category_id` INT NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) UNIQUE NOT NULL,
    `description` TEXT,
    `specifications` TEXT,
    `price` DECIMAL(15, 2) NOT NULL,
    `sale_price` DECIMAL(15, 2),
    `image` VARCHAR(255),
    `images` TEXT,
    `stock` INT DEFAULT 0,
    `views` INT DEFAULT 0,
    `rating` DECIMAL(3, 2) DEFAULT 0,
    `reviews_count` INT DEFAULT 0,
    `is_featured` BOOLEAN DEFAULT 0,
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- BẢNG CART (GIỎ HÀNG)
-- =====================================================
CREATE TABLE `cart` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT,
    `session_id` VARCHAR(255),
    `product_id` INT NOT NULL,
    `quantity` INT NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- BẢNG ORDERS (ĐỚN HÀNG)
-- =====================================================
CREATE TABLE `orders` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT,
    `order_code` VARCHAR(50) UNIQUE NOT NULL,
    `customer_name` VARCHAR(100) NOT NULL,
    `customer_email` VARCHAR(100) NOT NULL,
    `customer_phone` VARCHAR(20) NOT NULL,
    `customer_address` TEXT NOT NULL,
    `total_amount` DECIMAL(15, 2) NOT NULL,
    `discount_amount` DECIMAL(15, 2) DEFAULT 0,
    `coupon_code` VARCHAR(50) NULL,
    `payment_method` ENUM('cod', 'bank_transfer', 'momo', 'vnpay', 'zalopay', 'paypal') NOT NULL DEFAULT 'cod',
    `payment_status` ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
    `status` ENUM('pending', 'confirmed', 'shipping', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    `note` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- BẢNG ORDER_DETAILS (CHI TIẾT ĐƠN HÀNG)
-- =====================================================
CREATE TABLE `order_details` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL,
    `product_id` INT NOT NULL,
    `product_name` VARCHAR(255) NOT NULL,
    `product_image` VARCHAR(255),
    `price` DECIMAL(10, 2) NOT NULL,
    `quantity` INT NOT NULL,
    `subtotal` DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- BẢNG REVIEWS (ĐÁNH GIÁ SẢN PHẨM)
-- =====================================================
CREATE TABLE `reviews` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `product_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `order_id` INT NULL,
    `rating` TINYINT NOT NULL CHECK (`rating` BETWEEN 1 AND 5),
    `title` VARCHAR(255) NOT NULL,
    `comment` TEXT NOT NULL,
    `images` TEXT NULL COMMENT 'JSON array of image URLs',
    `likes` INT DEFAULT 0,
    `dislikes` INT DEFAULT 0,
    `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    `admin_reply` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE SET NULL,
    INDEX `idx_product_status` (`product_id`, `status`),
    INDEX `idx_user` (`user_id`),
    INDEX `idx_rating` (`rating`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- BẢNG REVIEW_LIKES (LIKE/DISLIKE REVIEW)
-- =====================================================
CREATE TABLE `review_likes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `review_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `type` ENUM('like', 'dislike') NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`review_id`) REFERENCES `reviews`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_user_review` (`review_id`, `user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- BẢNG WISHLIST (YÊU THÍCH)
-- =====================================================
CREATE TABLE `wishlist` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `product_id` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_user_product` (`user_id`, `product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- BẢNG COUPONS (MÃ GIẢM GIÁ)
-- =====================================================
CREATE TABLE `coupons` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(50) NOT NULL UNIQUE,
    `description` TEXT,
    `type` ENUM('percent', 'fixed') NOT NULL,
    `value` DECIMAL(10, 2) NOT NULL,
    `min_order_value` DECIMAL(10, 2) DEFAULT 0,
    `max_discount` DECIMAL(10, 2) NULL,
    `usage_limit` INT NULL,
    `used_count` INT DEFAULT 0,
    `start_date` DATETIME NOT NULL,
    `end_date` DATETIME NOT NULL,
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_code` (`code`),
    INDEX `idx_status_dates` (`status`, `start_date`, `end_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- BẢNG COUPON_USAGE (LỊCH SỬ SỬ DỤNG COUPON)
-- =====================================================
CREATE TABLE `coupon_usage` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `coupon_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `order_id` INT NOT NULL,
    `discount_amount` DECIMAL(10, 2) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`coupon_id`) REFERENCES `coupons`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- BẢNG NOTIFICATIONS (THÔNG BÁO)
-- =====================================================
CREATE TABLE `notifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `type` ENUM('order', 'review', 'promotion', 'system') NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `link` VARCHAR(255) NULL,
    `is_read` BOOLEAN DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_user_read` (`user_id`, `is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- BẢNG VIEWED_PRODUCTS (SẢN PHẨM ĐÃ XEM)
-- =====================================================
CREATE TABLE `viewed_products` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NULL,
    `session_id` VARCHAR(255) NULL,
    `product_id` INT NOT NULL,
    `viewed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
    INDEX `idx_user_viewed` (`user_id`, `viewed_at`),
    INDEX `idx_session_viewed` (`session_id`, `viewed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- DỮ LIỆU MẪU - USERS
-- =====================================================
INSERT INTO `users` (`username`, `email`, `password`, `role`, `full_name`, `phone`, `address`) VALUES
('admin', 'admin@luxurytech.com', '$2y$10$L3Pr1PvjS7QsMUe5EyApaOJV8YPDHKqxRSPqKBOA99pQzYMt.o6q.', 'admin', 'Administrator', '0901234567', 'Hà Nội, Việt Nam'),
('customer', 'customer@gmail.com', '$2y$10$L3Pr1PvjS7QsMUe5EyApaOJV8YPDHKqxRSPqKBOA99pQzYMt.o6q.', 'customer', 'Nguyễn Văn A', '0912345678', 'TP. Hồ Chí Minh');

-- Password cho cả 2 tài khoản: admin123
-- Bcrypt hash: $2y$10$L3Pr1PvjS7QsMUe5EyApaOJV8YPDHKqxRSPqKBOA99pQzYMt.o6q.

-- =====================================================
-- DỮ LIỆU MẪU - CATEGORIES
-- =====================================================
INSERT INTO `categories` (`name`, `slug`, `description`, `image`) VALUES
('Điện thoại', 'dien-thoai', 'Điện thoại thông minh cao cấp', 'https://cdn.tgdd.vn/Category/42/dien-thoai-di-dong-220x48.png'),
('Laptop', 'laptop', 'Laptop văn phòng, gaming', 'https://cdn.tgdd.vn/Category/44/laptop-220x48-1.png'),
('Tablet', 'tablet', 'Máy tính bảng iPad, Samsung', 'https://cdn.tgdd.vn/Category/522/may-tinh-bang-220x48.png'),
('Phụ kiện', 'phu-kien', 'Phụ kiện điện thoại, laptop', 'https://cdn.tgdd.vn/Category/54/phu-kien-220x48-1.png'),
('Smartwatch', 'smartwatch', 'Đồng hồ thông minh', 'https://cdn.tgdd.vn/Category/7077/dong-ho-thong-minh-220x48.png'),
('Tai nghe', 'tai-nghe', 'Tai nghe Bluetooth, có dây', 'https://cdn.tgdd.vn/Category/54/tai-nghe-220x48.png');

-- =====================================================
-- DỮ LIỆU MẪU - PRODUCTS
-- =====================================================
INSERT INTO `products` (`category_id`, `name`, `slug`, `description`, `specifications`, `price`, `sale_price`, `image`, `stock`, `is_featured`, `status`) VALUES
(1, 'iPhone 15 Pro Max 256GB', 'iphone-15-pro-max-256gb', 'iPhone 15 Pro Max với chip A17 Pro mạnh mẽ nhất, camera 48MP chuyên nghiệp, màn hình Super Retina XDR 6.7 inch, khung titan cao cấp', 'Màn hình:6.7 inch Super Retina XDR, Camera:48MP + 12MP + 12MP, Pin:4422mAh, Chip:Apple A17 Pro, RAM:8GB, Bộ nhớ:256GB', 29990000, 28490000, 'https://cdn.tgdd.vn/Products/Images/42/305658/iphone-15-pro-max-blue-thumbnew-600x600.jpg', 50, 1, 'active'),

(1, 'Samsung Galaxy S24 Ultra 12GB 256GB', 'samsung-galaxy-s24-ultra', 'Samsung Galaxy S24 Ultra với bút S Pen tích hợp, camera 200MP siêu nét, màn hình Dynamic AMOLED 2X 6.8 inch, chip Snapdragon 8 Gen 3', 'Màn hình:6.8 inch Dynamic AMOLED 2X, Camera:200MP + 50MP + 12MP + 10MP, Pin:5000mAh, Chip:Snapdragon 8 Gen 3, RAM:12GB, Bộ nhớ:256GB', 27990000, 26490000, 'https://cdn.tgdd.vn/Products/Images/42/307174/samsung-galaxy-s24-ultra-grey-thumbnew-600x600.jpg', 45, 1, 'active'),

(1, 'Xiaomi 14 Ultra 16GB 512GB', 'xiaomi-14-ultra', 'Xiaomi 14 Ultra với camera Leica đỉnh cao, màn hình AMOLED 6.73 inch 120Hz, chip Snapdragon 8 Gen 3, sạc nhanh 90W', 'Màn hình:6.73 inch AMOLED, Camera:50MP Leica + 50MP + 50MP + 50MP, Pin:5000mAh, Chip:Snapdragon 8 Gen 3, RAM:16GB, Bộ nhớ:512GB', 24990000, 23990000, 'https://cdn.tgdd.vn/Products/Images/42/320722/xiaomi-14-ultra-black-thumbnew-600x600.jpg', 30, 1, 'active'),

(1, 'OPPO Find N3 Flip', 'oppo-find-n3-flip', 'OPPO Find N3 Flip - điện thoại gập vỏ sò với màn hình phụ lớn 3.26 inch, camera 50MP, chip Dimensity 9200', 'Màn hình:6.8 inch AMOLED gập, Camera:50MP + 48MP + 32MP, Pin:4300mAh, Chip:Dimensity 9200, RAM:12GB, Bộ nhớ:256GB', 19990000, 18990000, 'https://cdn.tgdd.vn/Products/Images/42/309816/oppo-find-n3-flip-pink-thumbnew-600x600.jpg', 25, 1, 'active'),

(2, 'MacBook Air M2 2023 13 inch 8GB 256GB', 'macbook-air-m2-2023', 'MacBook Air M2 với thiết kế mỏng nhẹ chỉ 1.24kg, chip M2 mạnh mẽ, màn hình Liquid Retina 13.6 inch, pin 18 giờ', 'Màn hình:13.6 inch Liquid Retina, CPU:Apple M2 8 nhân, GPU:10 nhân, RAM:8GB, SSD:256GB, Pin:52.6Wh', 27990000, 26990000, 'https://cdn.tgdd.vn/Products/Images/44/282827/apple-macbook-air-m2-2022-xam-1-1.jpg', 30, 1, 'active'),

(2, 'Dell XPS 13 Plus 9320 i7 1360P', 'dell-xps-13-plus', 'Dell XPS 13 Plus với thiết kế cao cấp, màn hình InfinityEdge 13.4 inch, Intel Core i7 thế hệ 13, RAM 16GB', 'Màn hình:13.4 inch FHD+, CPU:Intel Core i7-1360P, RAM:16GB, SSD:512GB, VGA:Intel Iris Xe, Pin:55Wh', 35990000, 33990000, 'https://cdn.tgdd.vn/Products/Images/44/307534/dell-xps-13-plus-9320-i7-u084w11-1.jpg', 25, 1, 'active'),

(2, 'Asus ROG Strix G16 G614JU i7 13650HX', 'asus-rog-strix-g16', 'Asus ROG Strix G16 - laptop gaming mạnh mẽ với RTX 4050, màn hình 16 inch 165Hz, tản nhiệt ROG Intelligent Cooling', 'Màn hình:16 inch FHD 165Hz, CPU:Intel Core i7-13650HX, RAM:16GB, SSD:512GB, VGA:RTX 4050 6GB, Pin:90Wh', 32990000, 30990000, 'https://cdn.tgdd.vn/Products/Images/44/309016/asus-rog-strix-g16-g614ju-i7-n3135w-1-1.jpg', 20, 1, 'active'),

(2, 'Lenovo IdeaPad Slim 5 14IAH8 i5 12450H', 'lenovo-ideapad-slim-5', 'Lenovo IdeaPad Slim 5 - laptop văn phòng mỏng nhẹ, màn hình 14 inch, hiệu năng ổn định với Core i5 thế hệ 12', 'Màn hình:14 inch FHD, CPU:Intel Core i5-12450H, RAM:16GB, SSD:512GB, VGA:Intel UHD, Pin:56.6Wh', 16990000, 15990000, 'https://cdn.tgdd.vn/Products/Images/44/309342/lenovo-ideapad-slim-5-14iah8-i5-83bf002rvn-1.jpg', 40, 0, 'active'),

(3, 'iPad Pro M2 11 inch WiFi 128GB', 'ipad-pro-m2-11-inch', 'iPad Pro M2 với chip M2 mạnh mẽ như laptop, màn hình Liquid Retina 11 inch, hỗ trợ Apple Pencil thế hệ 2', 'Màn hình:11 inch Liquid Retina, Chip:Apple M2, RAM:8GB, Bộ nhớ:128GB, Pin:28.65Wh, Hỗ trợ:Apple Pencil 2', 20990000, 19990000, 'https://cdn.tgdd.vn/Products/Images/522/325530/ipad-pro-11-inch-m2-wifi-gray-1-1.jpg', 40, 1, 'active'),

(3, 'Samsung Galaxy Tab S9 FE+ 5G', 'samsung-galaxy-tab-s9-fe-plus', 'Samsung Galaxy Tab S9 FE+ với màn hình lớn 12.4 inch, bút S Pen trong hộp, pin 10090mAh, chống nước IP68', 'Màn hình:12.4 inch TFT LCD, Chip:Exynos 1380, RAM:8GB, Bộ nhớ:128GB, Pin:10090mAh, Hỗ trợ:S Pen', 13990000, 12990000, 'https://cdn.tgdd.vn/Products/Images/522/320721/samsung-galaxy-tab-s9-fe-plus-5g-gray-1-1.jpg', 35, 1, 'active'),

(3, 'Xiaomi Redmi Pad SE 8GB 256GB', 'xiaomi-redmi-pad-se', 'Xiaomi Redmi Pad SE - tablet giá rẻ với màn hình lớn 11 inch, pin 8000mAh, loa 4 kênh Dolby Atmos', 'Màn hình:11 inch IPS LCD, Chip:Snapdragon 680, RAM:8GB, Bộ nhớ:256GB, Pin:8000mAh, Loa:4 kênh Dolby Atmos', 5990000, 5490000, 'https://cdn.tgdd.vn/Products/Images/522/309816/xiaomi-redmi-pad-se-xanh-1.jpg', 50, 0, 'active'),

(4, 'AirPods Pro 2 USB-C', 'airpods-pro-2', 'AirPods Pro thế hệ 2 với chip H2, chống ồn chủ động ANC 2x mạnh hơn, âm thanh không gian, cổng sạc USB-C', 'Kết nối:Bluetooth 5.3, Pin:6 giờ (ANC bật), Sạc:USB-C, Chống nước:IPX4, Chip:Apple H2, Tính năng:ANC + Transparency', 5990000, 5490000, 'https://cdn.tgdd.vn/Products/Images/54/289780/tai-nghe-bluetooth-airpods-pro-2-usb-c-charge-apple-1.jpg', 100, 1, 'active'),

(4, 'Samsung Galaxy Buds2 Pro', 'samsung-galaxy-buds2-pro', 'Samsung Galaxy Buds2 Pro với chống ồn thông minh, âm thanh 360 Audio, thiết kế nhỏ gọn thoải mái', 'Kết nối:Bluetooth 5.3, Pin:5 giờ (ANC bật), Sạc:USB-C, Chống nước:IPX7, Driver:10mm, Tính năng:ANC + Ambient', 3990000, 3490000, 'https://cdn.tgdd.vn/Products/Images/54/289781/samsung-galaxy-buds2-pro-den-1.jpg', 80, 1, 'active'),

(4, 'Ốp lưng iPhone 15 Pro Max Silicone', 'op-lung-iphone-15-pro-max', 'Ốp lưng Silicone chính hãng Apple cho iPhone 15 Pro Max, chất liệu mềm mại, bảo vệ tốt, nhiều màu sắc', 'Chất liệu:Silicone cao cấp, Tương thích:iPhone 15 Pro Max, Tính năng:Chống sốc, chống bẩn, Màu sắc:Đen, Trắng, Xanh, Hồng', 990000, 790000, 'https://cdn.tgdd.vn/Products/Images/60/325847/op-lung-iphone-15-pro-max-silicone-den-1.jpg', 200, 0, 'active'),

(5, 'Apple Watch Series 9 GPS 41mm', 'apple-watch-series-9', 'Apple Watch Series 9 với chip S9 mạnh mẽ, màn hình luôn bật sáng hơn, theo dõi sức khỏe toàn diện, watchOS 10', 'Màn hình:1.9 inch OLED, Chip:Apple S9, Pin:18 giờ, Kết nối:Bluetooth 5.3, Chống nước:50m, Tính năng:ECG, SpO2, GPS', 9990000, 9490000, 'https://cdn.tgdd.vn/Products/Images/7077/325536/apple-watch-s9-gps-41mm-vien-nhom-day-cao-su-1.jpg', 60, 1, 'active'),

(5, 'Samsung Galaxy Watch6 Classic 43mm', 'samsung-galaxy-watch6-classic', 'Samsung Galaxy Watch6 Classic với vòng bezel xoay cổ điển, màn hình Super AMOLED, theo dõi giấc ngủ chi tiết', 'Màn hình:1.3 inch Super AMOLED, Chip:Exynos W930, Pin:300mAh, Kết nối:Bluetooth 5.3, Chống nước:5ATM, Tính năng:ECG, SpO2', 7990000, 7490000, 'https://cdn.tgdd.vn/Products/Images/7077/309816/samsung-galaxy-watch6-classic-43mm-den-1.jpg', 50, 1, 'active'),

(5, 'Xiaomi Watch 2 Pro', 'xiaomi-watch-2-pro', 'Xiaomi Watch 2 Pro với HyperOS, màn hình AMOLED 1.43 inch, pin 14 ngày, hỗ trợ eSIM độc lập', 'Màn hình:1.43 inch AMOLED, Chip:Snapdragon W5+, Pin:495mAh (14 ngày), Kết nối:Bluetooth 5.2, Chống nước:5ATM, Tính năng:eSIM, GPS', 5990000, 5490000, 'https://cdn.tgdd.vn/Products/Images/7077/320722/xiaomi-watch-2-pro-den-1.jpg', 45, 0, 'active'),

(6, 'Sony WH-1000XM5', 'sony-wh-1000xm5', 'Tai nghe Sony WH-1000XM5 với chống ồn hàng đầu thế giới, âm thanh Hi-Res, pin 30 giờ, thiết kế mới sang trọng', 'Kết nối:Bluetooth 5.2, Pin:30 giờ (ANC bật), Driver:30mm, Chống ồn:ANC 8 mic, Codec:LDAC, AAC, SBC, Sạc nhanh:3 phút = 3 giờ', 7990000, 7490000, 'https://cdn.tgdd.vn/Products/Images/54/289828/tai-nghe-bluetooth-sony-wh-1000xm5-den-1.jpg', 70, 1, 'active'),

(6, 'JBL Tune 770NC', 'jbl-tune-770nc', 'JBL Tune 770NC - tai nghe chống ồn giá rẻ, âm bass mạnh mẽ, pin 70 giờ, gập gọn tiện lợi', 'Kết nối:Bluetooth 5.3, Pin:70 giờ (ANC tắt), Driver:40mm, Chống ồn:ANC, Codec:AAC, SBC, Sạc nhanh:5 phút = 3 giờ', 2490000, 1990000, 'https://cdn.tgdd.vn/Products/Images/54/309342/jbl-tune-770nc-den-1.jpg', 90, 0, 'active'),

(4, 'Sạc nhanh Anker 735 GaN 65W', 'sac-nhanh-anker-735', 'Sạc nhanh Anker 735 GaN II với công suất 65W, 3 cổng sạc (2 USB-C + 1 USB-A), công nghệ GaN nhỏ gọn', 'Công suất:65W, Cổng sạc:2 USB-C + 1 USB-A, Công nghệ:GaN II, Tương thích:iPhone, Samsung, Laptop, Kích thước:Nhỏ gọn 40% hơn sạc thường', 1290000, 990000, 'https://cdn.tgdd.vn/Products/Images/58/320145/sac-anker-735-gan-ii-65w-a2667-1.jpg', 150, 1, 'active');


-- =====================================================
-- DỮ LIỆU MẪU - REVIEWS
-- =====================================================
INSERT INTO `reviews` (`product_id`, `user_id`, `order_id`, `rating`, `title`, `comment`, `status`) VALUES
(1, 2, NULL, 5, 'Sản phẩm tuyệt vời!', 'iPhone 15 Pro Max quá đỉnh, camera siêu nét, pin trâu, màn hình đẹp. Rất hài lòng với sản phẩm này!', 'approved'),
(1, 2, NULL, 4, 'Tốt nhưng hơi đắt', 'Máy chạy mượt, thiết kế đẹp nhưng giá hơi cao. Nếu có khuyến mãi thì rất đáng mua.', 'approved'),
(2, 2, NULL, 5, 'Samsung S24 Ultra quá ngon', 'Bút S Pen tiện lợi, camera 200MP chụp ảnh cực đẹp, màn hình to rõ ràng. Recommend!', 'approved'),
(3, 2, NULL, 5, 'MacBook Air M3 xứng đáng 5 sao', 'Máy mỏng nhẹ, pin trâu cả ngày, chạy mượt mà. Làm việc và giải trí đều tốt!', 'approved'),
(4, 2, NULL, 4, 'Dell XPS 13 ổn', 'Laptop đẹp, màn hình sắc nét, bàn phím gõ tốt. Chỉ tiếc là pin hơi yếu.', 'approved'),
(5, 2, NULL, 5, 'iPad Pro M2 tuyệt vời cho designer', 'Màn hình đẹp, Apple Pencil mượt mà, hiệu năng mạnh mẽ. Rất phù hợp cho công việc thiết kế!', 'approved'),
(10, 2, NULL, 5, 'AirPods Pro 2 chống ồn đỉnh cao', 'Chống ồn tốt, âm thanh trong trẻo, pin trâu. Đáng đồng tiền bát gạo!', 'approved'),
(11, 2, NULL, 4, 'Galaxy Buds2 Pro giá tốt', 'Âm thanh ổn, chống ồn khá, giá rẻ hơn AirPods. Đáng mua cho người dùng Samsung.', 'approved'),
(15, 2, NULL, 5, 'Apple Watch Series 9 quá xịn', 'Theo dõi sức khỏe chính xác, màn hình sáng, pin đủ dùng cả ngày. Rất hài lòng!', 'approved'),
(16, 2, NULL, 3, 'Galaxy Watch 6 tạm ổn', 'Đồng hồ đẹp, tính năng nhiều nhưng pin hơi yếu. Cần cải thiện thêm.', 'approved');

-- =====================================================
-- DỮ LIỆU MẪU - COUPONS
-- =====================================================
INSERT INTO `coupons` (`code`, `description`, `type`, `value`, `min_order_value`, `max_discount`, `usage_limit`, `start_date`, `end_date`, `status`) VALUES
('WELCOME10', 'Giảm 10% cho khách hàng mới', 'percent', 10.00, 1000000, 500000, 100, '2024-01-01 00:00:00', '2024-12-31 23:59:59', 'active'),
('SALE50K', 'Giảm 50.000đ cho đơn hàng từ 500.000đ', 'fixed', 50000.00, 500000, NULL, 200, '2024-01-01 00:00:00', '2024-12-31 23:59:59', 'active'),
('FLASH20', 'Flash Sale - Giảm 20%', 'percent', 20.00, 2000000, 1000000, 50, '2024-01-01 00:00:00', '2024-12-31 23:59:59', 'active'),
('VIP15', 'Giảm 15% cho khách hàng VIP', 'percent', 15.00, 5000000, 2000000, NULL, '2024-01-01 00:00:00', '2024-12-31 23:59:59', 'active'),
('FREESHIP', 'Miễn phí vận chuyển', 'fixed', 30000.00, 300000, NULL, 500, '2024-01-01 00:00:00', '2024-12-31 23:59:59', 'active');

-- =====================================================
-- BẢNG PHONE_OTPS (MÃ OTP ĐIỆN THOẠI)
-- =====================================================
CREATE TABLE `phone_otps` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `phone` VARCHAR(20) NOT NULL,
    `otp` VARCHAR(10) NOT NULL,
    `expiry` DATETIME NOT NULL,
    `verified` BOOLEAN DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_phone` (`phone`),
    INDEX `idx_expiry` (`expiry`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- BẢNG CHAT_CONVERSATIONS (HỘI THOẠI CHAT)
-- =====================================================
CREATE TABLE `chat_conversations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NULL,
    `session_id` VARCHAR(255) NULL,
    `customer_name` VARCHAR(255) NULL,
    `customer_email` VARCHAR(255) NULL,
    `status` ENUM('active', 'closed', 'waiting') DEFAULT 'active',
    `assigned_admin_id` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_session` (`session_id`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- BẢNG CHAT_MESSAGES (TIN NHẮN CHAT)
-- =====================================================
CREATE TABLE `chat_messages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `conversation_id` INT NOT NULL,
    `sender_type` ENUM('customer', 'admin', 'bot') NOT NULL,
    `sender_id` INT NULL,
    `message` TEXT NOT NULL,
    `attachment_url` VARCHAR(500) NULL,
    `is_read` BOOLEAN DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`conversation_id`) REFERENCES `chat_conversations`(`id`) ON DELETE CASCADE,
    INDEX `idx_conversation` (`conversation_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- BẢNG CHAT_QUICK_REPLIES (CÂU TRẢ LỜI NHANH)
-- =====================================================
CREATE TABLE `chat_quick_replies` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `category` VARCHAR(100) NULL,
    `is_active` BOOLEAN DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- BẢNG PAYMENT_TRANSACTIONS (GIAO DỊCH THANH TOÁN)
-- =====================================================
CREATE TABLE `payment_transactions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL,
    `payment_method` ENUM('vnpay', 'momo', 'zalopay', 'paypal', 'cod') NOT NULL,
    `transaction_id` VARCHAR(255) NOT NULL,
    `transaction_no` VARCHAR(255) NULL,
    `amount` DECIMAL(15, 2) NOT NULL,
    `currency` VARCHAR(10) DEFAULT 'VND',
    `status` ENUM('pending', 'processing', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    `response_data` TEXT NULL COMMENT 'JSON response from payment gateway',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
    INDEX `idx_transaction_id` (`transaction_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_created` (`created_at` DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- BẢNG PAYMENT_REFUNDS (HOÀN TIỀN)
-- =====================================================
CREATE TABLE `payment_refunds` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `transaction_id` INT NOT NULL,
    `order_id` INT NOT NULL,
    `refund_amount` DECIMAL(15, 2) NOT NULL,
    `reason` TEXT NULL,
    `status` ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    `refund_transaction_id` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`transaction_id`) REFERENCES `payment_transactions`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- BẢNG FLASH_SALES (CHƯƠNG TRÌNH FLASH SALE)
-- =====================================================
CREATE TABLE `flash_sales` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `start_time` DATETIME NOT NULL,
    `end_time` DATETIME NOT NULL,
    `status` ENUM('upcoming', 'active', 'ended') DEFAULT 'upcoming',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_status` (`status`),
    INDEX `idx_time` (`start_time`, `end_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- BẢNG FLASH_SALE_PRODUCTS (SẢN PHẨM FLASH SALE)
-- =====================================================
CREATE TABLE `flash_sale_products` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `flash_sale_id` INT NOT NULL,
    `product_id` INT NOT NULL,
    `original_price` DECIMAL(15, 2) NOT NULL,
    `flash_price` DECIMAL(15, 2) NOT NULL,
    `discount_percent` INT NOT NULL,
    `quantity_limit` INT NOT NULL COMMENT 'Số lượng giới hạn',
    `quantity_sold` INT DEFAULT 0 COMMENT 'Số lượng đã bán',
    `max_per_customer` INT DEFAULT 1 COMMENT 'Tối đa mỗi khách',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`flash_sale_id`) REFERENCES `flash_sales`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_flash_product` (`flash_sale_id`, `product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- BẢNG FLASH_SALE_ORDERS (ĐƠN HÀNG FLASH SALE)
-- =====================================================
CREATE TABLE `flash_sale_orders` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `flash_sale_id` INT NOT NULL,
    `flash_sale_product_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `order_id` INT NOT NULL,
    `quantity` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`flash_sale_id`) REFERENCES `flash_sales`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`flash_sale_product_id`) REFERENCES `flash_sale_products`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- DỮ LIỆU MẪU - CHAT QUICK REPLIES
-- =====================================================
INSERT INTO `chat_quick_replies` (`title`, `message`, `category`, `is_active`) VALUES
('Chào mừng', 'Xin chào! Tôi có thể giúp gì cho bạn?', 'greeting', 1),
('Giờ làm việc', 'Chúng tôi làm việc từ 8:00 - 22:00 hàng ngày, kể cả T7 và CN.', 'info', 1),
('Chính sách đổi trả', 'Sản phẩm được đổi trả trong vòng 7 ngày nếu còn nguyên seal, đầy đủ phụ kiện.', 'policy', 1),
('Thanh toán', 'Chúng tôi hỗ trợ thanh toán qua VNPay, MoMo, ZaloPay, PayPal và COD.', 'payment', 1),
('Vận chuyển', 'Miễn phí vận chuyển cho đơn hàng từ 500.000đ. Giao hàng trong 1-3 ngày.', 'shipping', 1);

-- =====================================================
-- DỮ LIỆU MẪU - FLASH SALES
-- =====================================================
INSERT INTO `flash_sales` (`name`, `description`, `start_time`, `end_time`, `status`) VALUES
('Flash Sale 12h', 'Giảm giá sốc trong 2 giờ', '2024-12-01 12:00:00', '2024-12-01 14:00:00', 'upcoming'),
('Flash Sale 18h', 'Giảm giá cuối ngày', '2024-12-01 18:00:00', '2024-12-01 20:00:00', 'upcoming'),
('Flash Sale Cuối Tuần', 'Giảm giá cả ngày cuối tuần', '2024-12-07 00:00:00', '2024-12-07 23:59:59', 'upcoming');

-- =====================================================
-- DỮ LIỆU MẪU - FLASH SALE PRODUCTS
-- =====================================================
INSERT INTO `flash_sale_products` (`flash_sale_id`, `product_id`, `original_price`, `flash_price`, `discount_percent`, `quantity_limit`, `max_per_customer`) VALUES
(1, 1, 29990000, 24990000, 17, 50, 1),
(1, 2, 27990000, 22990000, 18, 30, 1),
(1, 3, 24990000, 19990000, 20, 40, 1),
(2, 4, 22990000, 17990000, 22, 25, 1),
(2, 5, 19990000, 15990000, 20, 35, 1);

-- =====================================================
-- TRIGGERS (TỰ ĐỘNG CẬP NHẬT)
-- =====================================================

DELIMITER $$

-- Trigger: Cập nhật rating sản phẩm khi có review mới
CREATE TRIGGER update_product_rating_after_review_insert
AFTER INSERT ON reviews
FOR EACH ROW
BEGIN
    IF NEW.status = 'approved' THEN
        UPDATE products
        SET rating = (
            SELECT AVG(rating)
            FROM reviews
            WHERE product_id = NEW.product_id AND status = 'approved'
        ),
        reviews_count = (
            SELECT COUNT(*)
            FROM reviews
            WHERE product_id = NEW.product_id AND status = 'approved'
        )
        WHERE id = NEW.product_id;
    END IF;
END$$

-- Trigger: Cập nhật rating sản phẩm khi review được cập nhật
CREATE TRIGGER update_product_rating_after_review_update
AFTER UPDATE ON reviews
FOR EACH ROW
BEGIN
    UPDATE products
    SET rating = (
        SELECT AVG(rating)
        FROM reviews
        WHERE product_id = NEW.product_id AND status = 'approved'
    ),
    reviews_count = (
        SELECT COUNT(*)
        FROM reviews
        WHERE product_id = NEW.product_id AND status = 'approved'
    )
    WHERE id = NEW.product_id;
END$$

-- Trigger: Cập nhật số lần sử dụng coupon
CREATE TRIGGER update_coupon_used_count
AFTER INSERT ON coupon_usage
FOR EACH ROW
BEGIN
    UPDATE coupons
    SET used_count = used_count + 1
    WHERE id = NEW.coupon_id;
END$$

-- Trigger: Cập nhật số lượng đã bán flash sale
CREATE TRIGGER update_flash_sale_quantity
AFTER INSERT ON flash_sale_orders
FOR EACH ROW
BEGIN
    UPDATE flash_sale_products
    SET quantity_sold = quantity_sold + NEW.quantity
    WHERE id = NEW.flash_sale_product_id;
END$$

DELIMITER ;

-- =====================================================
-- VIEWS (CÁC VIEW HỮU ÍCH)
-- =====================================================

-- View: Tổng hợp reviews của sản phẩm
CREATE OR REPLACE VIEW product_reviews_summary AS
SELECT
    p.id,
    p.name,
    p.slug,
    COUNT(r.id) as total_reviews,
    AVG(r.rating) as avg_rating,
    SUM(CASE WHEN r.rating = 5 THEN 1 ELSE 0 END) as five_star,
    SUM(CASE WHEN r.rating = 4 THEN 1 ELSE 0 END) as four_star,
    SUM(CASE WHEN r.rating = 3 THEN 1 ELSE 0 END) as three_star,
    SUM(CASE WHEN r.rating = 2 THEN 1 ELSE 0 END) as two_star,
    SUM(CASE WHEN r.rating = 1 THEN 1 ELSE 0 END) as one_star
FROM products p
LEFT JOIN reviews r ON p.id = r.product_id AND r.status = 'approved'
GROUP BY p.id;

-- View: Flash sales đang hoạt động
CREATE OR REPLACE VIEW active_flash_sales AS
SELECT
    fs.*,
    COUNT(fsp.id) as total_products,
    SUM(fsp.quantity_sold) as total_sold
FROM flash_sales fs
LEFT JOIN flash_sale_products fsp ON fs.id = fsp.flash_sale_id
WHERE fs.status = 'active'
  AND fs.start_time <= NOW()
  AND fs.end_time > NOW()
GROUP BY fs.id;

-- =====================================================
-- CLEANUP (DỌN DẸP DỮ LIỆU CŨ)
-- =====================================================

-- Xóa OTP đã hết hạn (quá 1 ngày)
DELETE FROM phone_otps WHERE expiry < DATE_SUB(NOW(), INTERVAL 1 DAY);

-- Xóa sản phẩm đã xem cũ (quá 30 ngày)
DELETE FROM viewed_products WHERE viewed_at < DATE_SUB(NOW(), INTERVAL 30 DAY);

-- Cập nhật coupon hết hạn
UPDATE coupons SET status = 'inactive' WHERE end_date < NOW() AND status = 'active';

-- Cập nhật flash sale đã kết thúc
UPDATE flash_sales SET status = 'ended' WHERE end_time < NOW() AND status != 'ended';

COMMIT;

-- =====================================================
-- HOÀN THÀNH!
-- =====================================================
-- Tài khoản đăng nhập:
-- Admin: username=admin, password=admin123
-- Customer: username=customer, password=admin123
--
-- Đã tạo:
-- - 2 users (1 admin, 1 customer)
-- - 6 categories (Điện thoại, Laptop, Tablet, Phụ kiện, Smartwatch, Tai nghe)
-- - 20 products (iPhone, Samsung, Xiaomi, MacBook, Dell, Asus, iPad, AirPods, v.v.)
-- - 10 reviews mẫu
-- - 5 coupons mẫu (WELCOME10, SALE50K, FLASH20, VIP15, FREESHIP)
-- - 5 chat quick replies
-- - 3 flash sales
-- - 5 flash sale products
-- - 4 triggers (auto update rating, coupon count, flash sale quantity)
-- - 2 views (product reviews summary, active flash sales)
--
-- Tổng cộng: 22 bảng
-- - users, categories, products, cart, orders, order_details
-- - reviews, review_likes, wishlist, coupons, coupon_usage
-- - notifications, viewed_products
-- - chat_conversations, chat_messages, chat_quick_replies
-- - phone_otps
-- - payment_transactions, payment_refunds
-- - flash_sales, flash_sale_products, flash_sale_orders
--
-- Website đã sẵn sàng với đầy đủ chức năng:
-- ✅ Hệ thống đánh giá & review
-- ✅ Wishlist (Yêu thích)
-- ✅ Mã giảm giá (Coupons)
-- ✅ Thông báo
-- ✅ Sản phẩm đã xem
-- ✅ Live Chat với AI Chatbot
-- ✅ Đăng nhập mạng xã hội (Facebook, Google, Phone OTP)
-- ✅ Thanh toán online (VNPay, MoMo, ZaloPay, PayPal)
-- ✅ Flash Sale
-- ✅ Email Marketing
-- ✅ PWA Setup
-- ✅ So sánh sản phẩm
-- ✅ Mega Menu
-- ✅ Mini Cart
-- ✅ Profile người dùng
-- ✅ Theo dõi đơn hàng
-- ✅ Dark Mode
-- ✅ Quick View
-- ✅ Advanced Search
-- ✅ Dashboard Charts
-- =====================================================
