<?php
require_once 'includes/auth.php';
require_once '../includes/chat-logger.php';

$page_title = 'Chat Error Logs';

// Xử lý xóa log
if (isset($_GET['clear']) && $_GET['clear'] == '1') {
    if (file_exists(CHAT_LOG_FILE)) {
        file_put_contents(CHAT_LOG_FILE, '');
        $_SESSION['message'] = 'Đã xóa log thành công!';
        $_SESSION['message_type'] = 'success';
        header('Location: chat-logs.php');
        exit;
    }
}

// Đọc logs
$log_lines = readChatLogs(500); // Đọc 500 dòng cuối
$log_content = implode('', $log_lines);

// Làm sạch log cũ
cleanOldChatLogs(30);

include 'includes/header.php';
?>

<div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 pb-4 mb-6 border-b border-gray-200">
    <h1 class="text-2xl font-semibold text-gray-800 flex items-center">
        <i class="fas fa-file-alt mr-3 text-primary"></i> Chat Error Logs
    </h1>
    <div class="flex gap-2">
        <a href="?clear=1" 
           onclick="return confirm('Bạn có chắc muốn xóa tất cả logs?')"
           class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors">
            <i class="fas fa-trash mr-2"></i> Xóa Logs
        </a>
        <a href="chat-logs.php" 
           class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
            <i class="fas fa-sync mr-2"></i> Refresh
        </a>
    </div>
</div>

<?php if (isset($_SESSION['message'])): ?>
<div class="mb-6 p-4 rounded-xl border-l-4 <?php 
    $alert_classes = [
        'success' => 'bg-green-50 border-green-500 text-green-800',
        'danger' => 'bg-red-50 border-red-500 text-red-800',
        'warning' => 'bg-amber-50 border-amber-500 text-amber-800',
        'info' => 'bg-blue-50 border-blue-500 text-blue-800'
    ];
    $msg_type = $_SESSION['message_type'] ?? 'info';
    echo $alert_classes[$msg_type] ?? $alert_classes['info'];
?>">
    <div class="flex items-start justify-between">
        <p class="font-semibold flex-1"><?php echo $_SESSION['message']; ?></p>
        <button type="button" class="ml-4 text-gray-500 hover:text-gray-700" onclick="this.parentElement.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    </div>
</div>
<?php unset($_SESSION['message'], $_SESSION['message_type']); endif; ?>

<!-- Log Info -->
<div class="bg-white rounded-2xl border border-gray-200 shadow-md mb-6 p-6">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <p class="text-sm text-gray-600 mb-1">File Log</p>
            <p class="font-mono text-sm text-gray-800"><?php echo CHAT_LOG_FILE; ?></p>
        </div>
        <div>
            <p class="text-sm text-gray-600 mb-1">Kích thước</p>
            <p class="font-semibold text-gray-800">
                <?php 
                if (file_exists(CHAT_LOG_FILE)) {
                    $size = filesize(CHAT_LOG_FILE);
                    echo $size > 1024 * 1024 ? number_format($size / (1024 * 1024), 2) . ' MB' : number_format($size / 1024, 2) . ' KB';
                } else {
                    echo '0 KB';
                }
                ?>
            </p>
        </div>
        <div>
            <p class="text-sm text-gray-600 mb-1">Số dòng</p>
            <p class="font-semibold text-gray-800"><?php echo count($log_lines); ?> dòng</p>
        </div>
    </div>
</div>

<!-- Log Content -->
<div class="bg-gray-900 rounded-2xl border border-gray-700 shadow-lg overflow-hidden">
    <div class="bg-gray-800 px-6 py-4 border-b border-gray-700 flex items-center justify-between">
        <h2 class="text-lg font-semibold text-white">
            <i class="fas fa-code mr-2"></i> Log Content
        </h2>
        <button onclick="copyLogs()" class="px-4 py-2 bg-gray-700 text-white rounded-lg hover:bg-gray-600 transition-colors text-sm">
            <i class="fas fa-copy mr-2"></i> Copy
        </button>
    </div>
    <div class="p-6 overflow-x-auto">
        <?php if (empty($log_content)): ?>
            <p class="text-gray-400 text-center py-8">Không có log nào.</p>
        <?php else: ?>
            <pre class="text-sm text-green-400 font-mono whitespace-pre-wrap" id="logContent"><?php echo htmlspecialchars($log_content); ?></pre>
        <?php endif; ?>
    </div>
</div>

<script>
function copyLogs() {
    const logContent = document.getElementById('logContent').textContent;
    navigator.clipboard.writeText(logContent).then(() => {
        alert('Đã copy logs vào clipboard!');
    });
}

// Auto refresh mỗi 30 giây
setInterval(() => {
    location.reload();
}, 30000);
</script>

<?php include 'includes/footer.php'; ?>

