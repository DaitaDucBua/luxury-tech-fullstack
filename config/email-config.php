<?php
/**
 * Email Configuration
 */

// SMTP Configuration
define('SMTP_HOST', 'smtp.gmail.com'); // Gmail SMTP
define('SMTP_PORT', 587); // TLS port
define('SMTP_USERNAME', 'thinhpro0310@gmail.com');
define('SMTP_PASSWORD', 'vntk dugo nqmf avmx'); // App Password
define('SMTP_FROM_EMAIL', 'thinhpro0310@gmail.com');
define('SMTP_FROM_NAME', 'LuxuryTech');

// Email Settings
define('EMAIL_ENABLED', true);
define('EMAIL_DEBUG', false); // Set to true for debugging

/**
 * HƯỚNG DẪN CẤU HÌNH GMAIL:
 * 
 * 1. Bật 2-Step Verification:
 *    - Truy cập: https://myaccount.google.com/security
 *    - Bật "2-Step Verification"
 * 
 * 2. Tạo App Password:
 *    - Truy cập: https://myaccount.google.com/apppasswords
 *    - Chọn app: Mail
 *    - Chọn device: Other (Custom name)
 *    - Copy password và paste vào SMTP_PASSWORD
 * 
 * 3. Hoặc sử dụng dịch vụ khác:
 *    - SendGrid: https://sendgrid.com/
 *    - Mailgun: https://www.mailgun.com/
 *    - Amazon SES: https://aws.amazon.com/ses/
 */
?>

