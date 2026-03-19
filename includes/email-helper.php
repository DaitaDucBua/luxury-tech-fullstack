<?php
/**
 * Email Helper Functions using Gmail SMTP
 */

require_once __DIR__ . '/../config/email-config.php';

// Include PHPMailer (nếu có) hoặc dùng SMTP trực tiếp
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Send email using SMTP
 */
function sendEmail($to, $subject, $body, $isHTML = true) {
    if (!EMAIL_ENABLED) {
        return true; // Skip sending if disabled
    }

    // Kiểm tra PHPMailer có tồn tại không
    $phpmailerPath = __DIR__ . '/../vendor/autoload.php';

    if (file_exists($phpmailerPath)) {
        // Sử dụng PHPMailer
        require_once $phpmailerPath;
        return sendEmailWithPHPMailer($to, $subject, $body, $isHTML);
    } else {
        // Sử dụng SMTP Socket trực tiếp
        return sendEmailWithSMTP($to, $subject, $body, $isHTML);
    }
}

/**
 * Send email with PHPMailer library
 */
function sendEmailWithPHPMailer($to, $subject, $body, $isHTML = true) {
    $mail = new PHPMailer(true);

    try {
        // SMTP Configuration
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;
        $mail->CharSet = 'UTF-8';

        // Sender & Recipient
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to);

        // Content
        $mail->isHTML($isHTML);
        $mail->Subject = $subject;
        $mail->Body = $body;

        return $mail->send();
    } catch (Exception $e) {
        if (EMAIL_DEBUG) {
            error_log("Email Error: " . $mail->ErrorInfo);
        }
        return false;
    }
}

/**
 * Send email with native SMTP (SSL port 465)
 */
function sendEmailWithSMTP($to, $subject, $body, $isHTML = true) {
    $smtp_user = SMTP_USERNAME;
    $smtp_pass = SMTP_PASSWORD;
    $from_email = SMTP_FROM_EMAIL;
    $from_name = SMTP_FROM_NAME;

    // Kết nối SSL trực tiếp (port 465)
    $context = stream_context_create([
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        ]
    ]);

    $socket = @stream_socket_client(
        'ssl://smtp.gmail.com:465',
        $errno,
        $errstr,
        30,
        STREAM_CLIENT_CONNECT,
        $context
    );

    if (!$socket) {
        if (EMAIL_DEBUG) {
            error_log("SMTP Connection Failed: $errstr ($errno)");
        }
        return false;
    }

    // Đọc greeting
    fgets($socket, 515);

    // EHLO
    fputs($socket, "EHLO localhost\r\n");
    while ($line = fgets($socket, 515)) {
        if (substr($line, 3, 1) == ' ') break;
    }

    // AUTH LOGIN
    fputs($socket, "AUTH LOGIN\r\n");
    fgets($socket, 515);

    fputs($socket, base64_encode($smtp_user) . "\r\n");
    fgets($socket, 515);

    fputs($socket, base64_encode($smtp_pass) . "\r\n");
    $auth_response = fgets($socket, 515);

    if (substr($auth_response, 0, 3) != '235') {
        if (EMAIL_DEBUG) {
            error_log("SMTP Auth Failed: $auth_response");
        }
        fclose($socket);
        return false;
    }

    // MAIL FROM
    fputs($socket, "MAIL FROM: <$from_email>\r\n");
    fgets($socket, 515);

    // RCPT TO
    fputs($socket, "RCPT TO: <$to>\r\n");
    fgets($socket, 515);

    // DATA
    fputs($socket, "DATA\r\n");
    fgets($socket, 515);

    // Headers & Body
    $contentType = $isHTML ? 'text/html' : 'text/plain';
    $headers = "From: =?UTF-8?B?" . base64_encode($from_name) . "?= <$from_email>\r\n";
    $headers .= "To: $to\r\n";
    $headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: $contentType; charset=UTF-8\r\n";
    $headers .= "\r\n";

    fputs($socket, $headers . $body . "\r\n.\r\n");
    $data_response = fgets($socket, 515);

    // QUIT
    fputs($socket, "QUIT\r\n");
    fclose($socket);

    return (substr($data_response, 0, 3) == '250');
}

/**
 * Send welcome email
 */
function sendWelcomeEmail($userEmail, $userName) {
    $subject = 'Chào mừng đến với LuxuryTech!';
    
    $body = getEmailTemplate('welcome', [
        'name' => $userName,
        'email' => $userEmail
    ]);
    
    return sendEmail($userEmail, $subject, $body);
}

/**
 * Get email template
 */
function getEmailTemplate($template, $data = []) {
    $templateFile = __DIR__ . '/../email-templates/' . $template . '.php';

    if (!file_exists($templateFile)) {
        return 'Template not found';
    }

    ob_start();
    extract($data);
    include $templateFile;
    return ob_get_clean();
}
?>

