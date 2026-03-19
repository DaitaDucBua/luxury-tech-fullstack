<?php
session_start();
require_once '../config/config.php';

// Kiểm tra admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$page_title = 'Quản Lý Đánh Giá';

// Xử lý actions - chuyển sang AJAX

// Lấy danh sách reviews
$filter = $_GET['filter'] ?? 'all';
$where = "1=1";
if ($filter === 'pending') $where = "r.status = 'pending'";
elseif ($filter === 'approved') $where = "r.status = 'approved'";
elseif ($filter === 'rejected') $where = "r.status = 'rejected'";

$query = "SELECT r.*, u.username, u.full_name, p.name as product_name, p.slug as product_slug
          FROM reviews r
          JOIN users u ON r.user_id = u.id
          JOIN products p ON r.product_id = p.id
          WHERE $where
          ORDER BY r.created_at DESC";

$reviews = $conn->query($query);

// Đếm theo trạng thái
$counts = [
    'all' => $conn->query("SELECT COUNT(*) as c FROM reviews")->fetch_assoc()['c'],
    'pending' => $conn->query("SELECT COUNT(*) as c FROM reviews WHERE status = 'pending'")->fetch_assoc()['c'],
    'approved' => $conn->query("SELECT COUNT(*) as c FROM reviews WHERE status = 'approved'")->fetch_assoc()['c'],
    'rejected' => $conn->query("SELECT COUNT(*) as c FROM reviews WHERE status = 'rejected'")->fetch_assoc()['c']
];

include 'includes/header.php';
?>

<!-- Page Header -->
<div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 pb-4 mb-6 border-b border-gray-200">
    <h1 class="text-2xl font-semibold text-gray-800 flex items-center">
        <i class="fas fa-star mr-3 text-primary"></i> Quản Lý Đánh Giá
    </h1>
</div>

<?php if (isset($_SESSION['success'])): ?>
    <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 rounded-xl">
        <div class="flex items-start justify-between">
            <p class="text-green-800 font-semibold flex items-center">
                <i class="fas fa-check-circle mr-2"></i><?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </p>
            <button type="button" class="ml-4 text-green-500 hover:text-green-700" data-bs-dismiss="alert">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
<?php endif; ?>

<!-- Filter Tabs -->
<div class="mb-6 border-b-2 border-primary/20">
    <div class="flex flex-wrap gap-2">
        <a href="?filter=all" 
           class="px-4 py-2 rounded-t-lg font-semibold transition-colors <?php echo $filter === 'all' ? 'bg-primary text-secondary border-b-2 border-primary' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
            <i class="fas fa-list mr-1"></i> Tất cả 
            <span class="ml-2 px-2 py-0.5 rounded-full text-xs bg-gray-200 text-gray-700"><?php echo $counts['all']; ?></span>
        </a>
        <a href="?filter=pending" 
           class="px-4 py-2 rounded-t-lg font-semibold transition-colors <?php echo $filter === 'pending' ? 'bg-primary text-secondary border-b-2 border-primary' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
            <i class="fas fa-clock mr-1"></i> Chờ duyệt 
            <span class="ml-2 px-2 py-0.5 rounded-full text-xs bg-amber-100 text-amber-800"><?php echo $counts['pending']; ?></span>
        </a>
        <a href="?filter=approved" 
           class="px-4 py-2 rounded-t-lg font-semibold transition-colors <?php echo $filter === 'approved' ? 'bg-primary text-secondary border-b-2 border-primary' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
            <i class="fas fa-check mr-1"></i> Đã duyệt 
            <span class="ml-2 px-2 py-0.5 rounded-full text-xs bg-green-100 text-green-800"><?php echo $counts['approved']; ?></span>
        </a>
        <a href="?filter=rejected" 
           class="px-4 py-2 rounded-t-lg font-semibold transition-colors <?php echo $filter === 'rejected' ? 'bg-primary text-secondary border-b-2 border-primary' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
            <i class="fas fa-times mr-1"></i> Đã từ chối 
            <span class="ml-2 px-2 py-0.5 rounded-full text-xs bg-red-100 text-red-800"><?php echo $counts['rejected']; ?></span>
        </a>
    </div>
</div>

<!-- Reviews List -->
<div class="bg-white rounded-2xl border border-gray-200 shadow-md overflow-hidden">
    <div class="bg-gradient-to-r from-secondary to-secondary-light px-6 py-4">
        <h2 class="text-lg font-semibold text-primary uppercase tracking-wide flex items-center">
            <i class="fas fa-comments mr-2"></i> Danh sách đánh giá
        </h2>
    </div>
    <div class="p-0">
        <?php if ($reviews->num_rows === 0): ?>
            <div class="text-center py-12">
                <i class="fas fa-star text-6xl text-primary/30 mb-4"></i>
                <p class="text-gray-500 text-lg">Không có đánh giá nào</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-primary uppercase tracking-wider">ID</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-primary uppercase tracking-wider">Sản phẩm</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-primary uppercase tracking-wider hidden md:table-cell">Người đánh giá</th>
                            <th class="px-6 py-4 text-center text-xs font-semibold text-primary uppercase tracking-wider">Rating</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-primary uppercase tracking-wider">Nội dung</th>
                            <th class="px-6 py-4 text-center text-xs font-semibold text-primary uppercase tracking-wider">Trạng thái</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-primary uppercase tracking-wider hidden lg:table-cell">Ngày tạo</th>
                            <th class="px-6 py-4 text-center text-xs font-semibold text-primary uppercase tracking-wider">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php while ($review = $reviews->fetch_assoc()): ?>
                        <tr class="hover:bg-primary/5 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-primary font-semibold">#<?php echo $review['id']; ?></span>
                            </td>
                            <td class="px-6 py-4">
                                <a href="../product-detail.php?slug=<?php echo $review['product_slug']; ?>" 
                                   target="_blank" 
                                   class="text-primary hover:text-primary-dark font-medium">
                                    <?php echo htmlspecialchars($review['product_name']); ?>
                                </a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 hidden md:table-cell">
                                <?php echo htmlspecialchars($review['full_name'] ?: $review['username']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span class="text-primary text-lg">
                                    <?php echo str_repeat('★', $review['rating']) . str_repeat('☆', 5 - $review['rating']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <div class="flex flex-col gap-1">
                                    <strong class="text-gray-800"><?php echo htmlspecialchars($review['title']); ?></strong>
                                    <span class="text-gray-600 text-xs"><?php echo htmlspecialchars(substr($review['comment'], 0, 100)); ?>...</span>
                                    <div class="flex items-center gap-3 text-xs text-gray-500">
                                        <span><i class="fas fa-thumbs-up text-green-500 mr-1"></i><?php echo $review['likes']; ?></span>
                                        <span><i class="fas fa-thumbs-down text-red-500 mr-1"></i><?php echo $review['dislikes']; ?></span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center status-badge">
                                <?php
                                $badge_classes = [
                                    'pending' => 'bg-amber-100 text-amber-800',
                                    'approved' => 'bg-green-100 text-green-800',
                                    'rejected' => 'bg-red-100 text-red-800'
                                ];
                                $status_texts = [
                                    'pending' => 'Chờ duyệt',
                                    'approved' => 'Đã duyệt',
                                    'rejected' => 'Đã từ chối'
                                ];
                                $badge_class = $badge_classes[$review['status']] ?? 'bg-gray-100 text-gray-800';
                                $status_text = $status_texts[$review['status']] ?? $review['status'];
                                ?>
                                <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo $badge_class; ?>">
                                    <?php echo $status_text; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 hidden lg:table-cell">
                                <?php echo date('d/m/Y H:i', strtotime($review['created_at'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center justify-center gap-1 flex-wrap">
                                    <?php if ($review['status'] !== 'approved'): ?>
                                    <button type="button" 
                                            class="inline-flex items-center justify-center w-8 h-8 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors shadow-sm hover:shadow-md" 
                                            data-ajax-action="approve_review" 
                                            data-id="<?php echo $review['id']; ?>" 
                                            data-confirm="Bạn có chắc muốn duyệt đánh giá này?" 
                                            title="Duyệt">
                                        <i class="fas fa-check text-xs"></i>
                                    </button>
                                    <?php endif; ?>

                                    <?php if ($review['status'] !== 'rejected'): ?>
                                    <button type="button" 
                                            class="inline-flex items-center justify-center w-8 h-8 bg-amber-500 text-white rounded-lg hover:bg-amber-600 transition-colors shadow-sm hover:shadow-md" 
                                            data-ajax-action="reject_review" 
                                            data-id="<?php echo $review['id']; ?>" 
                                            data-confirm="Bạn có chắc muốn từ chối đánh giá này?" 
                                            title="Từ chối">
                                        <i class="fas fa-times text-xs"></i>
                                    </button>
                                    <?php endif; ?>

                                    <button class="inline-flex items-center justify-center w-8 h-8 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors shadow-sm hover:shadow-md" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#replyModal<?php echo $review['id']; ?>" 
                                            title="Trả lời">
                                        <i class="fas fa-reply text-xs"></i>
                                    </button>

                                    <button type="button" 
                                            class="inline-flex items-center justify-center w-8 h-8 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors shadow-sm hover:shadow-md" 
                                            data-ajax-action="delete_review" 
                                            data-id="<?php echo $review['id']; ?>" 
                                            data-confirm="Bạn có chắc muốn xóa đánh giá này?" 
                                            title="Xóa">
                                        <i class="fas fa-trash text-xs"></i>
                                    </button>
                                </div>
                                
                                <!-- Reply Modal -->
                                <div class="modal fade" id="replyModal<?php echo $review['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content bg-white rounded-2xl border border-gray-200 shadow-xl overflow-hidden">
                                            <div class="bg-gradient-to-r from-secondary to-secondary-light px-6 py-4 flex justify-between items-center">
                                                <h5 class="text-lg font-semibold text-primary flex items-center">
                                                    <i class="fas fa-reply mr-2"></i>Trả lời đánh giá #<?php echo $review['id']; ?>
                                                </h5>
                                                <button type="button" 
                                                        class="text-primary hover:text-primary-light transition-colors" 
                                                        data-bs-dismiss="modal">
                                                    <i class="fas fa-times text-xl"></i>
                                                </button>
                                            </div>
                                            <div class="p-6">
                                                <input type="hidden" id="replyReviewId" value="<?php echo $review['id']; ?>">
                                                <div class="mb-4 p-4 bg-gray-50 rounded-xl">
                                                    <small class="text-gray-500 font-semibold block mb-2">Đánh giá của khách hàng:</small>
                                                    <p class="font-semibold text-gray-800 mb-1"><?php echo htmlspecialchars($review['title']); ?></p>
                                                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($review['comment']); ?></p>
                                                </div>
                                                <label class="block text-sm font-semibold text-gray-700 mb-2">Câu trả lời của Admin:</label>
                                                <textarea id="replyText<?php echo $review['id']; ?>" 
                                                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-colors" 
                                                          rows="4" 
                                                          placeholder="Nhập câu trả lời..."><?php echo htmlspecialchars($review['admin_reply'] ?? ''); ?></textarea>
                                            </div>
                                            <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3">
                                                <button type="button" 
                                                        class="px-4 py-2 bg-gray-100 text-gray-700 font-semibold rounded-lg hover:bg-gray-200 transition-colors" 
                                                        data-bs-dismiss="modal">
                                                    Đóng
                                                </button>
                                                <button type="button" 
                                                        class="px-4 py-2 bg-gradient-to-r from-primary to-primary-light text-secondary font-semibold rounded-lg hover:from-primary-dark hover:to-primary transition-all shadow-md hover:shadow-lg" 
                                                        onclick="submitReply(<?php echo $review['id']; ?>)">
                                                    <i class="fas fa-paper-plane mr-2"></i> Gửi
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// AJAX handlers for admin reviews
function submitReply(reviewId) {
    const replyText = document.getElementById(`replyText${reviewId}`).value.trim();

    if (!replyText) {
        alert('Vui lòng nhập nội dung trả lời');
        return;
    }

    const formData = new FormData();
    formData.append('action', 'reply_review');
    formData.append('id', reviewId);
    formData.append('reply', replyText);

    fetch('ajax-handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Đã trả lời đánh giá thành công!');
            location.reload(); // Reload to show updated reply
        } else {
            alert('Lỗi: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Có lỗi xảy ra, vui lòng thử lại');
    });
}

// AJAX handlers are already included in admin.js
</script>

<?php include 'includes/footer.php'; ?>

