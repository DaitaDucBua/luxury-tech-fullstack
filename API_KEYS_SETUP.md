# 🔑 HƯỚNG DẪN CẤU HÌNH API KEYS

## 📋 **TỔNG QUAN**

Website LuxuryTech cần cấu hình **3 file config** để các chức năng hoạt động:

1. ✅ **`config/config.php`** - Database (đã có)
2. ⚠️ **`config/social-auth-config.php`** - Facebook, Google, SMS OTP
3. ⚠️ **`config/payment-config.php`** - VNPay, MoMo, ZaloPay, PayPal
4. ⚠️ **`config/email-config.php`** - Gmail SMTP

---

## 🎯 **QUAN TRỌNG:**

### **Nếu KHÔNG cấu hình API keys:**
- ❌ Không đăng nhập được bằng Facebook/Google
- ❌ Không gửi được OTP qua SMS
- ❌ Không thanh toán online được
- ❌ Không gửi email được

### **Nhưng vẫn hoạt động:**
- ✅ Đăng nhập bằng username/password
- ✅ Xem sản phẩm, thêm giỏ hàng
- ✅ Đặt hàng COD (thanh toán khi nhận hàng)
- ✅ Tất cả chức năng khác (wishlist, review, compare, v.v.)

---

## 1️⃣ **FACEBOOK LOGIN (TÙY CHỌN)**

### **Bước 1: Tạo Facebook App**
1. Truy cập: https://developers.facebook.com/
2. Click **"My Apps"** > **"Create App"**
3. Chọn **"Consumer"** > **"Next"**
4. Nhập tên app: **"LuxuryTech"**
5. Click **"Create App"**

### **Bước 2: Cấu hình Facebook Login**
1. Vào **"Add Product"** > Chọn **"Facebook Login"**
2. Chọn **"Web"**
3. Nhập Site URL: `http://localhost/LUXURYTECH/`
4. Vào **"Settings"** > **"Basic"**
5. Copy **App ID** và **App Secret**

### **Bước 3: Thêm Valid OAuth Redirect URIs**
1. Vào **"Facebook Login"** > **"Settings"**
2. Thêm vào **"Valid OAuth Redirect URIs"**:
   ```
   http://localhost/LUXURYTECH/auth/facebook-callback.php
   ```
3. Click **"Save Changes"**

### **Bước 4: Cập nhật config**
Mở file `config/social-auth-config.php`, sửa:
```php
define('FACEBOOK_APP_ID', 'YOUR_APP_ID_HERE');
define('FACEBOOK_APP_SECRET', 'YOUR_APP_SECRET_HERE');
```

---

## 2️⃣ **GOOGLE LOGIN (TÙY CHỌN)**

### **Bước 1: Tạo Google Cloud Project**
1. Truy cập: https://console.cloud.google.com/
2. Click **"Select a project"** > **"New Project"**
3. Nhập tên: **"LuxuryTech"**
4. Click **"Create"**

### **Bước 2: Enable Google+ API**
1. Vào **"APIs & Services"** > **"Library"**
2. Tìm **"Google+ API"**
3. Click **"Enable"**

### **Bước 3: Tạo OAuth Credentials**
1. Vào **"APIs & Services"** > **"Credentials"**
2. Click **"Create Credentials"** > **"OAuth client ID"**
3. Chọn **"Web application"**
4. Nhập tên: **"LuxuryTech Web"**
5. Thêm **"Authorized redirect URIs"**:
   ```
   http://localhost/LUXURYTECH/auth/google-callback.php
   ```
6. Click **"Create"**
7. Copy **Client ID** và **Client Secret**

### **Bước 4: Cập nhật config**
Mở file `config/social-auth-config.php`, sửa:
```php
define('GOOGLE_CLIENT_ID', 'YOUR_CLIENT_ID_HERE');
define('GOOGLE_CLIENT_SECRET', 'YOUR_CLIENT_SECRET_HERE');
```

---

## 3️⃣ **SMS OTP - TWILIO (TÙY CHỌN)**

### **Bước 1: Đăng ký Twilio**
1. Truy cập: https://www.twilio.com/try-twilio
2. Đăng ký tài khoản miễn phí
3. Verify số điện thoại của bạn

### **Bước 2: Lấy credentials**
1. Vào **Dashboard**
2. Copy **Account SID** và **Auth Token**
3. Vào **"Phone Numbers"** > **"Manage"** > **"Buy a number"**
4. Chọn số điện thoại (trial account có $15 credit)

### **Bước 3: Cập nhật config**
Mở file `config/social-auth-config.php`, sửa:
```php
define('TWILIO_ACCOUNT_SID', 'YOUR_ACCOUNT_SID');
define('TWILIO_AUTH_TOKEN', 'YOUR_AUTH_TOKEN');
define('TWILIO_PHONE_NUMBER', '+1234567890'); // Số điện thoại Twilio
```

⚠️ **Lưu ý:** Trial account chỉ gửi SMS đến số đã verify!

---

## 4️⃣ **GMAIL SMTP (TÙY CHỌN)**

### **Bước 1: Bật 2-Step Verification**
1. Truy cập: https://myaccount.google.com/security
2. Bật **"2-Step Verification"**

### **Bước 2: Tạo App Password**
1. Truy cập: https://myaccount.google.com/apppasswords
2. Chọn app: **"Mail"**
3. Chọn device: **"Other"** > Nhập **"LuxuryTech"**
4. Click **"Generate"**
5. Copy password (16 ký tự)

### **Bước 3: Cập nhật config**
Mở file `config/email-config.php`, sửa:
```php
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-16-char-app-password');
```

---

## 5️⃣ **PAYMENT GATEWAYS (TÙY CHỌN)**

### **⚠️ LƯU Ý QUAN TRỌNG:**

Các cổng thanh toán Việt Nam yêu cầu:
- Doanh nghiệp đã đăng ký kinh doanh
- Giấy phép kinh doanh
- Tài khoản ngân hàng doanh nghiệp
- Hợp đồng với cổng thanh toán

**Không thể test với tài khoản cá nhân!**

### **Giải pháp cho testing:**
1. ✅ Sử dụng **COD** (thanh toán khi nhận hàng)
2. ✅ Tạm thời comment code thanh toán online
3. ✅ Hoặc dùng **PayPal Sandbox** (dễ nhất)

---

## 6️⃣ **PAYPAL SANDBOX (KHUYẾN NGHỊ)**

### **Bước 1: Đăng ký PayPal Developer**
1. Truy cập: https://developer.paypal.com/
2. Đăng nhập bằng tài khoản PayPal (hoặc tạo mới)

### **Bước 2: Tạo Sandbox App**
1. Vào **"Dashboard"** > **"My Apps & Credentials"**
2. Click **"Create App"**
3. Nhập tên: **"LuxuryTech"**
4. Click **"Create App"**
5. Copy **Client ID** và **Secret**

### **Bước 3: Cập nhật config**
Mở file `config/payment-config.php`, sửa:
```php
define('PAYPAL_CLIENT_ID', 'YOUR_CLIENT_ID');
define('PAYPAL_SECRET', 'YOUR_SECRET');
define('PAYPAL_MODE', 'sandbox'); // Giữ nguyên
```

### **Bước 4: Test với Sandbox Account**
1. Vào **"Sandbox"** > **"Accounts"**
2. Sử dụng **Personal account** để test thanh toán
3. Click **"View/Edit account"** để xem email/password

---

## ✅ **KIỂM TRA CẤU HÌNH**

Sau khi cấu hình xong, test từng chức năng:

### **1. Test Facebook Login:**
- Vào trang login
- Click nút "Đăng nhập bằng Facebook"
- Nếu redirect đến Facebook và quay lại → ✅ Thành công

### **2. Test Google Login:**
- Click nút "Đăng nhập bằng Google"
- Chọn tài khoản Google
- Nếu đăng nhập thành công → ✅ Thành công

### **3. Test Email:**
- Đăng ký tài khoản mới
- Kiểm tra email welcome
- Nếu nhận được email → ✅ Thành công

### **4. Test PayPal:**
- Thêm sản phẩm vào giỏ hàng
- Checkout > Chọn PayPal
- Đăng nhập bằng sandbox account
- Nếu thanh toán thành công → ✅ Thành công

---

## 🎯 **KHUYẾN NGHỊ:**

### **Cho Development (localhost):**
- ✅ Cấu hình: Gmail SMTP (dễ nhất)
- ✅ Cấu hình: PayPal Sandbox (nếu cần test thanh toán)
- ⚠️ Tùy chọn: Facebook/Google Login
- ❌ Bỏ qua: VNPay, MoMo, ZaloPay, Twilio

### **Cho Production (website thật):**
- ✅ Cấu hình tất cả
- ✅ Đăng ký doanh nghiệp
- ✅ Hợp đồng với cổng thanh toán

---

## 📞 **HỖ TRỢ:**

Nếu gặp lỗi khi cấu hình, hãy cho tôi biết:
1. Chức năng nào bị lỗi
2. Thông báo lỗi
3. Screenshot (nếu có)

Tôi sẽ giúp bạn fix ngay! 😊

