<?php
/**
 * Social Authentication Configuration
 * 
 * Cấu hình cho đăng nhập qua Facebook, Google
 */

// Facebook App Configuration
define('FACEBOOK_APP_ID', '828014703558379');
define('FACEBOOK_APP_SECRET', '28552cfdad39e544f98dfa3393342c05');
define('FACEBOOK_REDIRECT_URI', 'http://localhost/LUXURYTECH/auth/facebook-callback.php');

// Google OAuth Configuration
define('GOOGLE_CLIENT_ID', 'YOUR_GOOGLE_CLIENT_ID');
define('GOOGLE_CLIENT_SECRET', 'YOUR_GOOGLE_CLIENT_SECRET');
define('GOOGLE_REDIRECT_URI', 'http://localhost/LUXURYTECH/auth/google-callback.php');

// OTP Configuration
define('OTP_EXPIRY_MINUTES', 5); // OTP hết hạn sau 5 phút
define('OTP_LENGTH', 6); // Độ dài mã OTP

// SMS API Configuration (Twilio example)
define('TWILIO_ACCOUNT_SID', 'YOUR_TWILIO_ACCOUNT_SID');
define('TWILIO_AUTH_TOKEN', 'YOUR_TWILIO_AUTH_TOKEN');
define('TWILIO_PHONE_NUMBER', 'YOUR_TWILIO_PHONE_NUMBER');

/**
 * Hướng dẫn cấu hình:
 * 
 * 1. FACEBOOK:
 *    - Truy cập: https://developers.facebook.com/
 *    - Tạo ứng dụng mới
 *    - Lấy App ID và App Secret
 *    - Thêm redirect URI vào Facebook Login settings
 * 
 * 2. GOOGLE:
 *    - Truy cập: https://console.cloud.google.com/
 *    - Tạo project mới
 *    - Enable Google+ API
 *    - Tạo OAuth 2.0 credentials
 *    - Lấy Client ID và Client Secret
 *    - Thêm redirect URI vào Authorized redirect URIs
 * 
 * 3. TWILIO (SMS OTP):
 *    - Truy cập: https://www.twilio.com/
 *    - Đăng ký tài khoản
 *    - Lấy Account SID và Auth Token
 *    - Mua số điện thoại hoặc dùng trial number
 */
?>

