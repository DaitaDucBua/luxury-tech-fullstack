<?php
/**
 * Phone OTP Handler
 */

session_start();
require_once '../config/config.php';
require_once '../config/social-auth-config.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'send_otp':
        $phone = $_POST['phone'] ?? '';
        
        // Validate phone number (Vietnamese format)
        if (!preg_match('/^(0|\+84)[0-9]{9}$/', $phone)) {
            echo json_encode(['success' => false, 'message' => 'Số điện thoại không hợp lệ']);
            exit;
        }
        
        // Generate OTP
        $otp = str_pad(rand(0, 999999), OTP_LENGTH, '0', STR_PAD_LEFT);
        $expiry = date('Y-m-d H:i:s', strtotime('+' . OTP_EXPIRY_MINUTES . ' minutes'));
        
        // Save OTP to database
        $phone_escaped = $conn->real_escape_string($phone);
        
        // Delete old OTPs for this phone
        $conn->query("DELETE FROM phone_otps WHERE phone = '$phone_escaped'");
        
        // Insert new OTP
        $insert = "INSERT INTO phone_otps (phone, otp, expiry) VALUES ('$phone_escaped', '$otp', '$expiry')";
        
        if ($conn->query($insert)) {
            // Send SMS (using Twilio or other SMS service)
            $sms_sent = sendSMS($phone, "Mã OTP của bạn là: $otp. Có hiệu lực trong " . OTP_EXPIRY_MINUTES . " phút.");
            
            if ($sms_sent) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Mã OTP đã được gửi đến số điện thoại của bạn',
                    'debug_otp' => $otp // REMOVE IN PRODUCTION!
                ]);
            } else {
                // For development: still return success with OTP
                echo json_encode([
                    'success' => true, 
                    'message' => 'Mã OTP (Development): ' . $otp,
                    'debug_otp' => $otp
                ]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Không thể tạo mã OTP']);
        }
        break;
        
    case 'verify_otp':
        $phone = $_POST['phone'] ?? '';
        $otp = $_POST['otp'] ?? '';
        
        $phone_escaped = $conn->real_escape_string($phone);
        $otp_escaped = $conn->real_escape_string($otp);
        
        // Check OTP
        $query = "SELECT * FROM phone_otps 
                  WHERE phone = '$phone_escaped' 
                  AND otp = '$otp_escaped' 
                  AND expiry > NOW() 
                  AND verified = 0";
        
        $result = $conn->query($query);
        
        if ($result->num_rows > 0) {
            // Mark OTP as verified
            $conn->query("UPDATE phone_otps SET verified = 1 WHERE phone = '$phone_escaped' AND otp = '$otp_escaped'");
            
            // Check if user exists
            $user_query = "SELECT * FROM users WHERE phone = '$phone_escaped'";
            $user_result = $conn->query($user_query);
            
            if ($user_result->num_rows > 0) {
                // User exists - login
                $user = $user_result->fetch_assoc();
                
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Đăng nhập thành công',
                    'action' => 'login'
                ]);
            } else {
                // New user - need to register
                echo json_encode([
                    'success' => true, 
                    'message' => 'Xác thực thành công. Vui lòng hoàn tất đăng ký.',
                    'action' => 'register',
                    'phone' => $phone
                ]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Mã OTP không đúng hoặc đã hết hạn']);
        }
        break;
        
    case 'register_with_phone':
        $phone = $_POST['phone'] ?? '';
        $full_name = $_POST['full_name'] ?? '';
        $email = $_POST['email'] ?? '';
        
        $phone_escaped = $conn->real_escape_string($phone);
        $full_name_escaped = $conn->real_escape_string($full_name);
        $email_escaped = $conn->real_escape_string($email);
        
        // Generate username
        $username = 'user' . substr($phone, -6);
        $password = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
        
        $insert = "INSERT INTO users (username, email, password, full_name, phone, role) 
                   VALUES ('$username', '$email_escaped', '$password', '$full_name_escaped', '$phone_escaped', 'customer')";
        
        if ($conn->query($insert)) {
            $user_id = $conn->insert_id;
            
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $username;
            $_SESSION['email'] = $email;
            $_SESSION['role'] = 'customer';
            
            echo json_encode(['success' => true, 'message' => 'Đăng ký thành công']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Không thể tạo tài khoản']);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

// Send SMS function (using Twilio)
function sendSMS($to, $message) {
    // For development, return false to use debug mode
    if ($_SERVER['SERVER_NAME'] === 'localhost') {
        return false;
    }
    
    // Twilio implementation
    try {
        $ch = curl_init();
        
        $url = 'https://api.twilio.com/2010-04-01/Accounts/' . TWILIO_ACCOUNT_SID . '/Messages.json';
        
        $data = [
            'From' => TWILIO_PHONE_NUMBER,
            'To' => $to,
            'Body' => $message
        ];
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, TWILIO_ACCOUNT_SID . ':' . TWILIO_AUTH_TOKEN);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $http_code === 201;
    } catch (Exception $e) {
        return false;
    }
}
?>

