<?php
/**
 * Chat Error Logger
 * Ghi log các lỗi trong hệ thống chat
 */

// Đảm bảo thư mục logs tồn tại
$log_dir = __DIR__ . '/../logs';
if (!file_exists($log_dir)) {
    mkdir($log_dir, 0755, true);
}

// File log
define('CHAT_LOG_FILE', $log_dir . '/chat-errors.log');
define('CHAT_LOG_MAX_SIZE', 10 * 1024 * 1024); // 10MB

/**
 * Ghi log lỗi chat
 * 
 * @param string $level Mức độ lỗi: ERROR, WARNING, INFO, DEBUG
 * @param string $message Thông điệp lỗi
 * @param array $context Thông tin bổ sung (optional)
 * @param Exception|null $exception Exception object (optional)
 */
function chatLog($level, $message, $context = [], $exception = null) {
    $log_file = CHAT_LOG_FILE;
    
    // Kiểm tra kích thước file, rotate nếu quá lớn
    if (file_exists($log_file) && filesize($log_file) > CHAT_LOG_MAX_SIZE) {
        $backup_file = $log_file . '.' . date('Y-m-d_H-i-s') . '.bak';
        rename($log_file, $backup_file);
    }
    
    // Tạo log entry
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $session_id = session_id() ?? 'no-session';
    $user_id = $_SESSION['user_id'] ?? 'guest';
    
    $log_entry = "[$timestamp] [$level] [$ip] [User:$user_id] [Session:$session_id] $message";
    
    // Thêm context nếu có
    if (!empty($context)) {
        $log_entry .= " | Context: " . json_encode($context, JSON_UNESCAPED_UNICODE);
    }
    
    // Thêm exception info nếu có
    if ($exception instanceof Exception) {
        $log_entry .= " | Exception: " . $exception->getMessage();
        $log_entry .= " | File: " . $exception->getFile() . ":" . $exception->getLine();
        $log_entry .= " | Trace: " . $exception->getTraceAsString();
    }
    
    $log_entry .= PHP_EOL;
    
    // Ghi vào file
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

/**
 * Ghi log ERROR
 */
function chatLogError($message, $context = [], $exception = null) {
    chatLog('ERROR', $message, $context, $exception);
}

/**
 * Ghi log WARNING
 */
function chatLogWarning($message, $context = []) {
    chatLog('WARNING', $message, $context);
}

/**
 * Ghi log INFO
 */
function chatLogInfo($message, $context = []) {
    chatLog('INFO', $message, $context);
}

/**
 * Ghi log DEBUG
 */
function chatLogDebug($message, $context = []) {
    chatLog('DEBUG', $message, $context);
}

/**
 * Đọc log file (cho admin xem)
 * 
 * @param int $lines Số dòng cuối cùng cần đọc
 * @return array
 */
function readChatLogs($lines = 100) {
    $log_file = CHAT_LOG_FILE;
    
    if (!file_exists($log_file)) {
        return [];
    }
    
    $file = file($log_file);
    return array_slice($file, -$lines);
}

/**
 * Xóa log file cũ hơn X ngày
 * 
 * @param int $days Số ngày
 */
function cleanOldChatLogs($days = 30) {
    $log_dir = dirname(CHAT_LOG_FILE);
    $files = glob($log_dir . '/chat-errors.log.*.bak');
    $cutoff_time = time() - ($days * 24 * 60 * 60);
    
    foreach ($files as $file) {
        if (filemtime($file) < $cutoff_time) {
            unlink($file);
        }
    }
}

