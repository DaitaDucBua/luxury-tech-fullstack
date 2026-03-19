# 🚀 LuxuryTech - Cấu trúc thư mục

## Cấu trúc thư mục

```
LUXURYTECH/
│
├── 📁 admin/                          # Admin Panel
│   ├── 📁 includes/
│   │   ├── auth.php                   # Middleware kiểm tra quyền admin
│   │   ├── header.php                 # Header admin với sidebar
│   │   └── footer.php                 # Footer admin
│   ├── 📁 assets/
│   │   ├── 📁 css/
│   │   │   └── admin-ajax.css         # CSS cho AJAX admin
│   │   └── 📁 js/
│   │       └── admin-ajax.js          # JavaScript AJAX admin
│   ├── ajax-handler.php               # Xử lý AJAX requests từ admin
│   ├── index.php                      # Dashboard admin
│   ├── products.php                   # Quản lý sản phẩm
│   ├── product-add.php                # Thêm sản phẩm mới
│   ├── product-edit.php               # Sửa thông tin sản phẩm
│   ├── categories.php                 # Quản lý danh mục
│   ├── orders.php                     # Quản lý đơn hàng
│   ├── users.php                      # Quản lý người dùng
│   ├── reviews.php                    # Quản lý đánh giá
│   ├── coupons.php                    # Quản lý mã giảm giá
│   ├── flash-sales.php                # Quản lý Flash Sale
│   ├── chat-logs.php                  # Xem lịch sử chat
│   ├── dashboard-charts.php           # Biểu đồ thống kê
│   └── upload-image.php               # Upload hình ảnh
│
├── 📁 ajax/                           # AJAX Handlers (Frontend)
│   ├── auth.php                       # Xử lý đăng nhập/đăng ký AJAX
│   ├── cart-handler.php               # Xử lý giỏ hàng (thêm/xóa/cập nhật)
│   ├── chat.php                       # Xử lý chat với AI
│   ├── compare.php                    # So sánh sản phẩm
│   ├── coupon.php                     # Áp dụng mã giảm giá
│   ├── get-user-info.php              # Lấy thông tin người dùng
│   ├── mini-cart.php                  # Cập nhật mini cart
│   ├── phone-otp.php                  # Xử lý OTP đăng nhập
│   ├── profile.php                    # Cập nhật thông tin cá nhân
│   ├── quick-view.php                 # Xem nhanh sản phẩm
│   ├── review.php                     # Thêm/sửa đánh giá
│   ├── search.php                     # Tìm kiếm AJAX
│   └── wishlist.php                   # Xử lý wishlist
│
├── 📁 assets/                         # Tài nguyên tĩnh
│   ├── 📁 css/
│   │   ├── style.css                  # CSS chính frontend
│   │   ├── admin.css                  # CSS admin panel
│   │   ├── advanced-search.css        # CSS tìm kiếm nâng cao
│   │   ├── compare.css                # CSS trang so sánh
│   │   ├── dark-mode.css              # CSS chế độ tối
│   │   ├── flash-sale.css             # CSS Flash Sale
│   │   ├── live-chat.css              # CSS chat trực tuyến
│   │   ├── mega-menu.css              # CSS menu lớn
│   │   ├── mini-cart.css              # CSS giỏ hàng nhỏ
│   │   ├── order-tracking.css         # CSS theo dõi đơn hàng
│   │   ├── quick-view.css             # CSS xem nhanh
│   │   ├── social-login.css           # CSS đăng nhập mạng xã hội
│   │   └── ui-enhancements.css        # CSS cải tiến UI
│   └── 📁 js/
│       ├── main.js                    # JavaScript chính
│       ├── main-config.php            # Config JavaScript (PHP)
│       ├── admin.js                   # JavaScript admin
│       ├── advanced-search.js         # JS tìm kiếm nâng cao
│       ├── ajax-framework.js          # Framework AJAX
│       ├── compare.js                 # JS so sánh sản phẩm
│       ├── dark-mode.js               # JS chế độ tối
│       ├── flash-sale.js              # JS Flash Sale
│       ├── live-chat.js               # JS chat trực tuyến
│       ├── mega-menu.js               # JS menu lớn
│       ├── mini-cart.js               # JS giỏ hàng nhỏ
│       ├── pwa.js                     # Progressive Web App
│       ├── social-login.js            # JS đăng nhập mạng xã hội
│       └── ui-enhancements.js          # JS cải tiến UI
│
├── 📁 auth/                           # Xác thực mạng xã hội
│   ├── facebook-login.php             # Đăng nhập Facebook
│   ├── facebook-callback.php          # Callback Facebook OAuth
│   ├── google-login.php               # Đăng nhập Google
│   └── google-callback.php            # Callback Google OAuth
│
├── 📁 config/                         # Cấu hình hệ thống
│   ├── config.php                     # Cấu hình database & chung
│   ├── email-config.php               # Cấu hình email SMTP
│   ├── payment-config.php             # Cấu hình thanh toán
│   └── social-auth-config.php         # Cấu hình Facebook/Google
│
├── 📁 email-templates/                # Template email
│   └── welcome.php                    # Email chào mừng
│
├── 📁 images/                         # Hình ảnh
│   ├── 📁 banners/                    # Banner quảng cáo
│   ├── 📁 categories/                 # Hình danh mục
│   └── 📁 products/                   # Hình sản phẩm (63+ files)
│
├── 📁 includes/                       # File include chung
│   ├── header.php                     # Header frontend
│   ├── footer.php                     # Footer frontend
│   ├── chat-logger.php                # Logger chat errors
│   ├── email-helper.php               # Helper gửi email
│   └── order-email.php                # Email đơn hàng
│
├── 📁 logs/                           # Log files
│   └── chat-errors.log                # Log lỗi chat
│
├── 📁 payment/                        # Xử lý thanh toán
│   ├── vnpay-payment.php              # Thanh toán VNPay
│   ├── vnpay-return.php               # Callback VNPay
│   ├── momo-payment.php               # Thanh toán MoMo
│   └── momo-return.php                # Callback MoMo
│
├── 📄 index.php                       # Trang chủ
├── 📄 products.php                    # Danh sách sản phẩm
├── 📄 product-detail.php              # Chi tiết sản phẩm
├── 📄 search.php                      # Tìm kiếm
├── 📄 advanced-search.php             # Tìm kiếm nâng cao
├── 📄 cart.php                        # Giỏ hàng
├── 📄 checkout.php                    # Thanh toán
├── 📄 compare.php                     # So sánh sản phẩm
├── 📄 flash-sale.php                  # Trang Flash Sale
├── 📄 wishlist.php                    # Danh sách yêu thích
├── 📄 orders.php                      # Lịch sử đơn hàng
├── 📄 order-detail.php                # Chi tiết đơn hàng
├── 📄 order-success.php               # Đặt hàng thành công
├── 📄 order-tracking.php              # Theo dõi đơn hàng
├── 📄 login.php                       # Đăng nhập
├── 📄 register.php                    # Đăng ký
├── 📄 logout.php                      # Đăng xuất
├── 📄 forgot-password.php             # Quên mật khẩu
├── 📄 reset-password.php              # Đặt lại mật khẩu
├── 📄 profile.php                     # Thông tin cá nhân
├── 📄 privacy-policy.php              # Chính sách bảo mật
├── 📄 terms.php                       # Điều khoản sử dụng
├── 📄 google-login.php                # Đăng nhập Google (legacy)
├── 📄 google-callback.php             # Callback Google (legacy)
├── 📄 database.sql                    # Database schema
├── 📄 manifest.json                   # PWA manifest
├── 📄 service-worker.js               # Service Worker (PWA)
├── 📄 offline.html                    # Trang offline
├── 📄 README.md                       # Hướng dẫn chung
└── 📄 API_KEYS_SETUP.md               # Hướng dẫn cấu hình API keys
```
