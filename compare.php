<?php
// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/config.php';

// Prevent caching - Strong headers
if (!headers_sent()) {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Cache-Control: post-check=0, pre-check=0', false);
    header('Pragma: no-cache');
    header('Expires: 0');
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
    header('ETag: ' . md5(time() . session_id()));
}

$page_title = 'So Sánh Sản Phẩm';

// Force session read - ensure we get latest data
session_write_close();
session_start();

// Lấy danh sách sản phẩm so sánh từ session (fresh read)
$compare_list = $_SESSION['compare_list'] ?? [];
$products = [];

if (!empty($compare_list)) {
    $ids_str = implode(',', $compare_list);
    $query = "SELECT p.*, c.name as category_name,
              (SELECT AVG(rating) FROM reviews WHERE product_id = p.id AND status = 'approved') as avg_rating,
              (SELECT COUNT(*) FROM reviews WHERE product_id = p.id AND status = 'approved') as review_count
              FROM products p
              LEFT JOIN categories c ON p.category_id = c.id
              WHERE p.id IN ($ids_str)
              ORDER BY FIELD(p.id, $ids_str)";
    
    $result = $conn->query($query);
    while ($row = $result->fetch_assoc()) {
        // Parse specifications
        $specs = [];
        if (!empty($row['specifications'])) {
            $lines = explode("\n", $row['specifications']);
            foreach ($lines as $line) {
                if (strpos($line, ':') !== false) {
                    list($key, $value) = explode(':', $line, 2);
                    $specs[trim($key)] = trim($value);
                }
            }
        }
        $row['specs_array'] = $specs;
        $row['avg_rating'] = round($row['avg_rating'] ?? 0, 1);
        // Calculate discount percent: if sale_price exists, calculate from price
        $row['discount_percent'] = 0;
        if (!empty($row['sale_price']) && $row['sale_price'] < $row['price'] && $row['price'] > 0) {
            $row['discount_percent'] = round((($row['price'] - $row['sale_price']) / $row['price']) * 100);
        }
        
        $products[] = $row;
    }
}

// Lấy tất cả keys của specifications
$all_spec_keys = [];
foreach ($products as $product) {
    $all_spec_keys = array_merge($all_spec_keys, array_keys($product['specs_array']));
}
$all_spec_keys = array_unique($all_spec_keys);

include 'includes/header.php';
?>

<div class="container mx-auto px-4 py-8 max-w-7xl" id="compare-container">
    <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-semibold text-gray-800 mb-2 flex items-center">
                <i class="fas fa-balance-scale text-amber-600 mr-3"></i>
                So Sánh Sản Phẩm
            </h2>
            <?php if (!empty($products)): ?>
            <p class="text-gray-500 text-sm">
                Đang so sánh <strong class="text-amber-600 font-semibold"><?php echo count($products); ?></strong> sản phẩm 
                <?php if (count($products) < 4): ?>
                (Có thể thêm tối đa <strong>4</strong> sản phẩm)
                <?php else: ?>
                <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">Đã đạt giới hạn</span>
                <?php endif; ?>
            </p>
            <?php endif; ?>
        </div>
        <?php if (!empty($products)): ?>
        <div class="flex gap-3">
            <a href="products.php" 
               class="inline-flex items-center px-4 py-2 border-2 border-amber-600 text-amber-600 hover:bg-amber-50 font-medium rounded-lg transition-colors">
                <i class="fas fa-plus mr-2"></i> Thêm sản phẩm
            </a>
            <button class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-colors" 
                    onclick="clearCompare(event)">
                <i class="fas fa-trash mr-2"></i> Xóa Tất Cả
            </button>
        </div>
        <?php endif; ?>
    </div>
    
    <?php if (empty($products)): ?>
        <!-- Empty State -->
        <div class="text-center py-12" data-aos="fade-up">
            <div class="w-24 h-24 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                <i class="fas fa-balance-scale text-4xl text-gray-400"></i>
            </div>
            <h3 class="text-xl font-semibold text-gray-700 mb-2">Chưa có sản phẩm nào để so sánh</h3>
            <p class="text-gray-500 mb-6">Hãy thêm ít nhất 2 sản phẩm vào danh sách so sánh để xem sự khác biệt</p>
            <a href="products.php" 
               class="inline-flex items-center px-6 py-3 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg transition-colors shadow-sm">
                <i class="fas fa-shopping-bag mr-2"></i> Xem Sản Phẩm
            </a>
        </div>
    <?php elseif (count($products) < 2): ?>
        <!-- Only 1 product -->
        <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <div class="flex items-start">
                <i class="fas fa-info-circle text-blue-600 mr-3 mt-0.5"></i>
                <div>
                    <strong class="text-blue-900 block mb-1">Bạn đang có <?php echo count($products); ?> sản phẩm trong danh sách so sánh.</strong>
                    <p class="text-blue-800 text-sm mb-0">Cần ít nhất 2 sản phẩm để so sánh. Hãy thêm thêm sản phẩm khác!</p>
                </div>
            </div>
        </div>
        
        <!-- Show single product info -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-center">
                    <div class="md:col-span-3 text-center">
                        <img src="<?php echo htmlspecialchars($products[0]['image']); ?>" 
                             alt="<?php echo htmlspecialchars($products[0]['name']); ?>"
                             class="w-full max-w-[200px] mx-auto rounded-lg">
                    </div>
                    <div class="md:col-span-6">
                        <h5 class="text-lg font-semibold text-gray-900 mb-2"><?php echo htmlspecialchars($products[0]['name']); ?></h5>
                        <p class="text-gray-500 mb-3"><?php echo htmlspecialchars($products[0]['category_name']); ?></p>
                        <h4 class="text-red-600 font-bold text-2xl mb-4"><?php echo number_format($products[0]['sale_price'] ?: $products[0]['price']); ?>đ</h4>
                        <a href="product-detail.php?slug=<?php echo $products[0]['slug']; ?>" 
                           class="inline-flex items-center px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg transition-colors">
                            <i class="fas fa-eye mr-2"></i> Xem Chi Tiết
                        </a>
                    </div>
                    <div class="md:col-span-3 text-center space-y-3">
                        <button class="w-full inline-flex items-center justify-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-colors" 
                                onclick="removeFromCompare(<?php echo $products[0]['id']; ?>, event);"
                                data-product-id="<?php echo $products[0]['id']; ?>"
                                data-action="remove-compare">
                            <i class="fas fa-times mr-2"></i> Xóa
                        </button>
                        <a href="products.php?category=<?php echo $products[0]['category_id']; ?>" 
                           class="w-full inline-flex items-center justify-center px-4 py-2 border-2 border-amber-600 text-amber-600 hover:bg-amber-50 font-medium rounded-lg transition-colors">
                            <i class="fas fa-plus mr-2"></i> Thêm sản phẩm cùng danh mục
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Suggest products from same category -->
        <?php
        $suggested = $conn->query("SELECT * FROM products WHERE category_id = {$products[0]['category_id']} AND id != {$products[0]['id']} AND status = 'active' LIMIT 4");
        if ($suggested->num_rows > 0):
        ?>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h5 class="text-lg font-semibold text-gray-800 flex items-center">
                    <i class="fas fa-lightbulb text-yellow-500 mr-2"></i>
                    Gợi ý sản phẩm cùng danh mục để so sánh
                </h5>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <?php while ($suggest = $suggested->fetch_assoc()): ?>
                    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden hover:shadow-md transition-shadow">
                        <a href="product-detail.php?slug=<?php echo $suggest['slug']; ?>">
                            <img src="<?php echo htmlspecialchars($suggest['image']); ?>" 
                                 class="w-full h-40 object-cover">
                        </a>
                        <div class="p-3">
                            <h6 class="font-medium text-gray-900 text-sm mb-2 line-clamp-2">
                                <a href="product-detail.php?slug=<?php echo $suggest['slug']; ?>" 
                                   class="text-gray-900 hover:text-amber-600 transition-colors">
                                    <?php echo htmlspecialchars($suggest['name']); ?>
                                </a>
                            </h6>
                            <div class="mb-3">
                                <strong class="text-red-600 font-semibold"><?php echo number_format($suggest['sale_price'] ?: $suggest['price']); ?>đ</strong>
                            </div>
                            <button class="w-full inline-flex items-center justify-center px-3 py-2 border-2 border-amber-600 text-amber-600 hover:bg-amber-50 text-sm font-medium rounded-lg transition-colors compare-toggle-btn" 
                                    data-product-id="<?php echo $suggest['id']; ?>">
                                <i class="fas fa-balance-scale mr-1"></i> So sánh
                            </button>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    <?php else: ?>
        <!-- Compare Table -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-2">
                    <h5 class="text-lg font-semibold text-gray-800 flex items-center">
                        <i class="fas fa-table mr-2 text-amber-600"></i>Bảng So Sánh Chi Tiết
                    </h5>
                    <small class="text-gray-600 text-sm flex items-center">
                        <i class="fas fa-info-circle mr-1"></i>
                        Ô màu xanh = Tốt nhất, Ô màu đỏ = Cần lưu ý
                    </small>
                </div>
            </div>
            <div class="overflow-x-auto" style="max-height: 80vh; overflow-y: auto;">
                <table class="w-full border-collapse compare-table">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="w-48 px-4 py-3 text-left text-sm font-semibold text-gray-700 border-b border-gray-200 sticky left-0 bg-gray-50 z-10">Thông Tin</th>
                            <?php foreach ($products as $product): ?>
                            <th class="min-w-[250px] px-4 py-3 text-center text-sm font-semibold text-gray-700 border-b border-gray-200">
                                <button class="inline-flex items-center px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors mb-2" 
                                        onclick="removeFromCompare(<?php echo $product['id']; ?>, event);" 
                                        data-product-id="<?php echo $product['id']; ?>"
                                        data-action="remove-compare"
                                        title="Xóa khỏi so sánh">
                                    <i class="fas fa-times mr-1"></i> Xóa
                                </button>
                            </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Product Image & Name -->
                        <tr class="border-b border-gray-200">
                            <td class="px-4 py-3 font-semibold text-gray-900 sticky left-0 bg-white z-9 border-r border-gray-200"><strong>Sản phẩm</strong></td>
                            <?php foreach ($products as $index => $product): ?>
                            <td class="px-4 py-3 text-center border-r border-gray-200 last:border-r-0" data-product-id="<?php echo $product['id']; ?>">
                                <img src="<?php echo htmlspecialchars($product['image']); ?>"
                                     alt="<?php echo htmlspecialchars($product['name']); ?>"
                                     class="w-full max-w-[150px] mx-auto mb-2 rounded-lg">
                                <h6 class="font-semibold text-gray-900 mb-1"><?php echo htmlspecialchars($product['name']); ?></h6>
                                <small class="text-gray-500"><?php echo htmlspecialchars($product['category_name']); ?></small>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                            
                        <!-- Price -->
                        <tr class="border-b border-gray-200">
                            <td class="px-4 py-3 font-semibold text-gray-900 sticky left-0 bg-white z-9 border-r border-gray-200"><strong>Giá</strong></td>
                            <?php 
                            $prices = array_map(function($p) { return $p['sale_price'] ?: $p['price']; }, $products);
                            $minPrice = min($prices);
                            foreach ($products as $product): 
                                $currentPrice = $product['sale_price'] ?: $product['price'];
                                $isBestPrice = ($currentPrice == $minPrice);
                            ?>
                            <td class="px-4 py-3 text-center border-r border-gray-200 last:border-r-0 <?php echo $isBestPrice ? 'highlight-best' : ''; ?>" data-product-id="<?php echo $product['id']; ?>">
                                <?php if ($product['sale_price']): ?>
                                    <h4 class="text-red-600 font-bold text-lg mb-1"><?php echo number_format($product['sale_price']); ?>đ</h4>
                                    <div class="flex items-center justify-center gap-2">
                                        <small class="text-gray-400 line-through">
                                            <?php echo number_format($product['price']); ?>đ
                                        </small>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">-<?php echo round((($product['price'] - $product['sale_price']) / $product['price']) * 100); ?>%</span>
                                    </div>
                                <?php else: ?>
                                    <h4 class="text-red-600 font-bold text-lg mb-1"><?php echo number_format($product['price']); ?>đ</h4>
                                <?php endif; ?>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                        
                        <!-- Rating -->
                        <tr class="border-b border-gray-200">
                            <td class="px-4 py-3 font-semibold text-gray-900 sticky left-0 bg-white z-9 border-r border-gray-200"><strong>Đánh giá</strong></td>
                            <?php 
                            $ratings = array_map(function($p) { return $p['avg_rating'] ?: 0; }, $products);
                            $maxRating = max($ratings);
                            foreach ($products as $product): 
                                $rating = $product['avg_rating'] ?: 0;
                                $isBestRating = ($rating == $maxRating && $rating > 0);
                            ?>
                            <td class="px-4 py-3 text-center border-r border-gray-200 last:border-r-0 <?php echo $isBestRating ? 'highlight-best' : ''; ?>" data-product-id="<?php echo $product['id']; ?>">
                                <?php if ($product['review_count'] > 0): ?>
                                    <div class="flex items-center justify-center text-amber-500 mb-1">
                                        <?php
                                        for ($i = 1; $i <= 5; $i++) {
                                            if ($i <= floor($rating)) {
                                                echo '<i class="fas fa-star"></i>';
                                            } elseif ($i - 0.5 <= $rating) {
                                                echo '<i class="fas fa-star-half-alt"></i>';
                                            } else {
                                                echo '<i class="far fa-star"></i>';
                                            }
                                        }
                                        ?>
                                    </div>
                                    <small class="text-gray-500 text-sm"><?php echo $rating; ?>/5 (<?php echo $product['review_count']; ?> đánh giá)</small>
                                <?php else: ?>
                                    <small class="text-gray-500">Chưa có đánh giá</small>
                                <?php endif; ?>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                        
                        <!-- Stock -->
                        <tr class="border-b border-gray-200">
                            <td class="px-4 py-3 font-semibold text-gray-900 sticky left-0 bg-white z-9 border-r border-gray-200"><strong>Tình trạng</strong></td>
                            <?php foreach ($products as $product): ?>
                            <td class="px-4 py-3 text-center border-r border-gray-200 last:border-r-0 <?php echo $product['stock'] > 0 ? 'highlight-best' : 'highlight-worst'; ?>" data-product-id="<?php echo $product['id']; ?>">
                                <?php if ($product['stock'] > 0): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Còn hàng (<?php echo $product['stock']; ?>)</span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Hết hàng</span>
                                <?php endif; ?>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                        
                        <!-- Specifications -->
                        <?php foreach ($all_spec_keys as $spec_key): ?>
                        <tr class="border-b border-gray-200">
                            <td class="px-4 py-3 font-semibold text-gray-900 sticky left-0 bg-white z-9 border-r border-gray-200"><strong><?php echo htmlspecialchars($spec_key); ?></strong></td>
                            <?php foreach ($products as $product): ?>
                            <td class="px-4 py-3 text-center border-r border-gray-200 last:border-r-0" data-product-id="<?php echo $product['id']; ?>">
                                <?php echo htmlspecialchars($product['specs_array'][$spec_key] ?? '-'); ?>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                        
                        <!-- Actions -->
                        <tr>
                            <td class="px-4 py-3 font-semibold text-gray-900 sticky left-0 bg-white z-9 border-r border-gray-200"><strong>Thao tác</strong></td>
                            <?php foreach ($products as $product): ?>
                            <td class="px-4 py-3 text-center border-r border-gray-200 last:border-r-0" data-product-id="<?php echo $product['id']; ?>">
                                <div class="space-y-2">
                                    <a href="product-detail.php?slug=<?php echo $product['slug']; ?>" 
                                       class="w-full inline-flex items-center justify-center px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium rounded-lg transition-colors">
                                        <i class="fas fa-eye mr-2"></i> Xem Chi Tiết
                                    </a>
                                    <?php if ($product['stock'] > 0): ?>
                                    <button class="w-full inline-flex items-center justify-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors add-to-cart" 
                                            data-product-id="<?php echo $product['id']; ?>">
                                        <i class="fas fa-cart-plus mr-2"></i> Thêm Vào Giỏ
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.compare-table {
    font-size: 14px;
}

.compare-table th,
.compare-table td {
    vertical-align: middle;
}

/* Highlight tooltips */
.highlight-best {
    background-color: #d1fae5 !important;
    font-weight: 600;
    position: relative;
}

.highlight-best::before {
    content: '⭐';
    position: absolute;
    top: 5px;
    right: 5px;
    font-size: 0.7em;
}

.highlight-worst {
    background-color: #fee2e2 !important;
    font-weight: 500;
    position: relative;
}

.highlight-worst::before {
    content: '⚠️';
    position: absolute;
    top: 5px;
    right: 5px;
    font-size: 0.7em;
}

@media (max-width: 768px) {
    .compare-table {
        font-size: 12px;
    }
    
    .compare-table th,
    .compare-table td {
        padding: 8px;
    }
    
    .compare-table img {
        max-height: 100px;
    }
}
</style>

<script>
function removeFromCompare(productId, event) {
    // Get event from window.event if not passed
    if (!event && window.event) {
        event = window.event;
    }
    
    // Allow event to be optional for backward compatibility
    if (event) {
        if (event.preventDefault) {
            event.preventDefault();
        }
        if (event.stopPropagation) {
            event.stopPropagation();
        }
    }
    
    if (!confirm('Xóa sản phẩm khỏi danh sách so sánh?')) return;

    const formData = new FormData();
    formData.append('action', 'remove');
    formData.append('product_id', productId);

    // Show loading state - try multiple ways to find the button
    let button = null;
    
    // Method 1: Try to find from event target (works even if clicked on icon inside button)
    if (event && event.target) {
        // Try to find button from any clicked element (button, icon, text, etc.)
        button = event.target.closest('button');
        
        // If clicked on icon or text inside button, find parent button
        if (!button) {
            let element = event.target;
            while (element && element !== document.body) {
                if (element.tagName === 'BUTTON') {
                    button = element;
                    break;
                }
                element = element.parentElement;
            }
        }
    }
    
    // Method 2: Try to find by data attribute (most reliable)
    if (!button) {
        button = document.querySelector(`button[data-product-id="${productId}"][data-action="remove-compare"]`);
    }
    
    // Method 3: Try to find by onclick attribute with productId
    if (!button) {
        const allButtons = document.querySelectorAll('button[onclick*="removeFromCompare"]');
        for (let btn of allButtons) {
            const onclickAttr = btn.getAttribute('onclick') || '';
            if (onclickAttr.includes(productId.toString())) {
                button = btn;
                break;
            }
        }
    }
    
    const originalHtml = button ? button.innerHTML : '';
    if (button) {
        button.disabled = true;
        button.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
    }

    fetch('ajax/compare.php?t=' + Date.now(), {
        method: 'POST',
        body: formData,
        cache: 'no-store',
        headers: {
            'Cache-Control': 'no-cache',
            'Pragma': 'no-cache'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Show success notification
            if (typeof Ajax !== 'undefined' && Ajax.showNotification) {
                Ajax.showNotification(data.message, 'success');
            } else {
                alert(data.message);
            }

            // Update count
            const remainingCount = data.count;
            const countElement = document.querySelector('small.text-muted strong');
            if (countElement) {
                countElement.textContent = remainingCount;
            }

            // Find and remove the entire column for this product
            const table = document.querySelector('.compare-table');
            if (table) {
                // Find the column index by finding the header with the product ID
                const headers = table.querySelectorAll('thead tr:first-child th');
                let columnIndex = -1;
                
                headers.forEach((header, index) => {
                    const btn = header.querySelector('button');
                    if (btn && (btn.getAttribute('onclick') && btn.getAttribute('onclick').includes(productId.toString()))) {
                        columnIndex = index;
                    }
                });

                if (columnIndex > 0) { // > 0 because first column is "Thông Tin"
                    // Remove all cells in this column from all rows
                    const rows = table.querySelectorAll('tr');
                    rows.forEach(row => {
                        const cell = row.children[columnIndex];
                        if (cell) {
                            cell.style.transition = 'opacity 0.3s, transform 0.3s';
                            cell.style.opacity = '0';
                            cell.style.transform = 'translateX(-20px)';
                            setTimeout(() => {
                                cell.remove();
                            }, 300);
                        }
                    });
                }
            }

            // If no products left or only 1 left, reload to show appropriate UI
            if (remainingCount === 0) {
                setTimeout(() => {
                    // Use replace to avoid cache - add random to force reload
                    location.replace('compare.php?t=' + Date.now() + '&nocache=' + Math.random());
                }, 500);
            } else if (remainingCount === 1) {
                // Reload to show single product UI - with cache busting
                setTimeout(() => {
                    location.replace('compare.php?t=' + Date.now() + '&nocache=' + Math.random());
                }, 500);
            } else {
                // Re-highlight differences after removing column
                setTimeout(() => {
                    if (typeof highlightCompareDifferences === 'function') {
                        highlightCompareDifferences();
                    }
                }, 400);
            }

            // Update compare count badge in header
            if (typeof updateCompareCount === 'function') {
                updateCompareCount();
            }
        } else {
            // Restore button if exists
            if (button && originalHtml) {
                button.disabled = false;
                button.innerHTML = originalHtml;
            }

            if (typeof Ajax !== 'undefined' && Ajax.showNotification) {
                Ajax.showNotification(data.message, 'error');
            } else {
                alert(data.message);
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        
        // Restore button if exists
        if (button && originalHtml) {
            button.disabled = false;
            button.innerHTML = originalHtml;
        }

        if (typeof Ajax !== 'undefined' && Ajax.showNotification) {
            Ajax.showNotification('Có lỗi xảy ra, vui lòng thử lại', 'error');
        } else {
            alert('Có lỗi xảy ra');
        }
    });
}

function clearCompare(event) {
    // Allow event to be optional for backward compatibility
    if (event && event.preventDefault) {
        event.preventDefault();
    }
    
    if (!confirm('Xóa tất cả sản phẩm khỏi danh sách so sánh?')) return;

    const formData = new FormData();
    formData.append('action', 'clear');

    // Show loading state
    const button = (event && event.target) ? event.target : document.querySelector('button[onclick*="clearCompare"]');
    const originalHtml = button ? button.innerHTML : '';
    if (button) {
        button.disabled = true;
        button.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Đang xóa...';
    }

    fetch('ajax/compare.php?t=' + Date.now(), {
        method: 'POST',
        body: formData,
        cache: 'no-store',
        headers: {
            'Cache-Control': 'no-cache',
            'Pragma': 'no-cache'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            if (typeof Ajax !== 'undefined' && Ajax.showNotification) {
                Ajax.showNotification(data.message, 'success');
            } else {
                alert(data.message);
            }

            // Fade out table and redirect
            const tableContainer = document.querySelector('.card.shadow-sm');
            if (tableContainer) {
                tableContainer.style.transition = 'opacity 0.3s';
                tableContainer.style.opacity = '0';
            }

            setTimeout(() => {
                // Use replace to avoid cache and prevent back button issues
                location.replace('compare.php?t=' + Date.now() + '&nocache=' + Math.random());
            }, 300);
        } else {
            // Restore button if exists
            if (button && originalHtml) {
                button.disabled = false;
                button.innerHTML = originalHtml;
            }

            if (typeof Ajax !== 'undefined' && Ajax.showNotification) {
                Ajax.showNotification(data.message, 'error');
            } else {
                alert(data.message);
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        
        // Restore button if exists
        if (button && originalHtml) {
            button.disabled = false;
            button.innerHTML = originalHtml;
        }

        if (typeof Ajax !== 'undefined' && Ajax.showNotification) {
            Ajax.showNotification('Có lỗi xảy ra, vui lòng thử lại', 'error');
        } else {
            alert('Có lỗi xảy ra');
        }
    });
}

// Force reload if page was cached (check if URL has timestamp)
document.addEventListener('DOMContentLoaded', function() {
    // If no timestamp in URL, add one and reload to prevent cache
    if (!window.location.search.includes('t=')) {
        const separator = window.location.search ? '&' : '?';
        window.location.replace(window.location.pathname + window.location.search + separator + 't=' + Date.now());
        return;
    }
    
    highlightCompareDifferences();
    
    // Auto-refresh compare page when products are added from other pages
    // Check for changes every 2 seconds (only if page is visible)
    let lastCount = <?php echo count($products); ?>;
    let productIds = <?php echo json_encode(array_column($products, 'id')); ?>;
    let isPageVisible = true;
    
    // Pause checking when page is hidden
    document.addEventListener('visibilitychange', function() {
        isPageVisible = !document.hidden;
    });
    
    setInterval(function() {
        // Only check if page is visible
        if (!isPageVisible) return;
        
        fetch('ajax/compare.php?action=get_list&t=' + Date.now(), {
            cache: 'no-store',
            headers: {
                'Cache-Control': 'no-cache',
                'Pragma': 'no-cache'
            }
        })
        .then(response => {
            if (!response.ok) throw new Error('Network error');
            return response.json();
        })
        .then(data => {
            if (data.success) {
                const currentCount = data.count || 0;
                const currentIds = (data.products && Array.isArray(data.products)) 
                    ? data.products.map(p => parseInt(p.id)).sort() 
                    : [];
                const lastIds = productIds.map(id => parseInt(id)).sort();
                
                // Check if count changed or product IDs changed
                const idsChanged = JSON.stringify(currentIds) !== JSON.stringify(lastIds);
                
                if (currentCount !== lastCount || idsChanged) {
                    // Something changed, reload page with timestamp to avoid cache
                    location.replace('compare.php?t=' + Date.now() + '&nocache=' + Math.random());
                }
            }
        })
        .catch(error => {
            // Silently fail - don't spam console
            if (error.message !== 'Network error') {
                console.error('Error checking compare list:', error);
            }
        });
    }, 2000); // Check every 2 seconds
});

function highlightCompareDifferences() {
    // Highlighting is already done in PHP, this function is for dynamic updates if needed
    const bestCells = document.querySelectorAll('.highlight-best');
    const worstCells = document.querySelectorAll('.highlight-worst');
    
    // Add tooltip to highlight cells
    bestCells.forEach(cell => {
        cell.setAttribute('title', 'Tốt nhất');
    });
    
    worstCells.forEach(cell => {
        cell.setAttribute('title', 'Cần lưu ý');
    });
}
</script>

<?php include 'includes/footer.php'; ?>

