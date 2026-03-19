<?php
require_once 'includes/auth.php';

$page_title = 'Thêm sản phẩm';

// Form được xử lý bằng AJAX

// Lấy danh sách danh mục
$categories = $conn->query("SELECT * FROM categories ORDER BY name");

include 'includes/header.php';
?>

<!-- Page Header -->
<div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 pb-4 mb-6 border-b border-gray-200">
    <h1 class="text-2xl font-semibold text-gray-800 flex items-center">
        <i class="fas fa-plus mr-3 text-primary"></i> Thêm sản phẩm mới
    </h1>
    <div class="flex items-center">
        <a href="products.php" class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 font-semibold rounded-lg hover:bg-gray-200 transition-colors">
            <i class="fas fa-arrow-left mr-2"></i> Quay lại
        </a>
    </div>
</div>

<!-- Main Content Grid -->
<div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
    <!-- Form Section -->
    <div class="lg:col-span-8">
        <div class="bg-white rounded-2xl border border-gray-200 shadow-md overflow-hidden">
            <div class="bg-gradient-to-r from-secondary to-secondary-light px-6 py-4">
                <h2 class="text-lg font-semibold text-primary uppercase tracking-wide">
                    <i class="fas fa-info-circle mr-2"></i> Thông tin sản phẩm
                </h2>
            </div>
            <div class="p-6">
                <form data-ajax="true" data-action="add_product" data-ajax-url="ajax-handler.php" class="space-y-6">
                    <!-- Tên sản phẩm -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Tên sản phẩm <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               name="name" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-colors" 
                               required>
                    </div>
                    
                    <!-- Danh mục -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Danh mục <span class="text-red-500">*</span>
                        </label>
                        <select name="category_id" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-colors" 
                                required>
                            <option value="">Chọn danh mục</option>
                            <?php while ($cat = $categories->fetch_assoc()): ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <!-- Giá -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Giá gốc <span class="text-red-500">*</span>
                            </label>
                            <div class="flex">
                                <input type="number" 
                                       id="price" 
                                       name="price" 
                                       class="flex-1 px-4 py-2 border border-gray-300 rounded-l-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-colors" 
                                       step="1000" 
                                       required 
                                       placeholder="0">
                                <span class="px-4 py-2 bg-gray-100 border border-l-0 border-gray-300 rounded-r-lg text-gray-700 font-medium">đ</span>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Giảm giá (%)</label>
                            <div class="flex">
                                <input type="number" 
                                       id="discount_percent" 
                                       class="flex-1 px-4 py-2 border border-gray-300 rounded-l-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-colors" 
                                       min="0" 
                                       max="100" 
                                       step="1" 
                                       placeholder="0">
                                <span class="px-4 py-2 bg-gray-100 border border-l-0 border-gray-300 text-gray-700 font-medium">%</span>
                                <button class="px-4 py-2 bg-gradient-to-r from-primary to-primary-light text-secondary font-semibold rounded-r-lg hover:from-primary-dark hover:to-primary transition-all" 
                                        type="button" 
                                        id="apply-discount">
                                    <i class="fas fa-calculator"></i>
                                </button>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Giá khuyến mãi</label>
                            <div class="flex">
                                <input type="number" 
                                       id="sale_price" 
                                       name="sale_price" 
                                       class="flex-1 px-4 py-2 border border-gray-300 rounded-l-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-colors" 
                                       step="1000" 
                                       placeholder="0">
                                <span class="px-4 py-2 bg-gray-100 border border-l-0 border-gray-300 rounded-r-lg text-gray-700 font-medium">đ</span>
                            </div>
                            <small class="text-gray-500 text-xs mt-1 block">Để trống nếu không giảm giá</small>
                        </div>
                    </div>
                    
                    <!-- Tồn kho -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Số lượng tồn kho <span class="text-red-500">*</span>
                        </label>
                        <input type="number" 
                               name="stock" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-colors" 
                               value="0" 
                               required>
                    </div>
                    
                    <!-- Mô tả -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Mô tả</label>
                        <textarea name="description" 
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-colors" 
                                  rows="4"></textarea>
                    </div>
                    
                    <!-- Thông số kỹ thuật -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Thông số kỹ thuật</label>
                        <textarea name="specifications" 
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-colors" 
                                  rows="4" 
                                  placeholder="Màn hình:6.7 inch, Camera:48MP, Pin:5000mAh"></textarea>
                        <small class="text-gray-500 text-xs mt-1 block">Định dạng: Tên:Giá trị, Tên:Giá trị (phân cách bằng dấu phẩy)</small>
                    </div>
                    
                    <!-- Ảnh sản phẩm -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Ảnh sản phẩm <span class="text-red-500">*</span>
                        </label>
                        <input type="file" 
                               id="imageFiles" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-colors file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20" 
                               accept="image/*" 
                               multiple 
                               required>
                        <small class="text-gray-500 text-xs mt-1 block">
                            Chọn nhiều ảnh (JPG, PNG, GIF, WEBP - Tối đa 5MB/ảnh). <strong>Ảnh đầu tiên sẽ là ảnh chính.</strong>
                        </small>

                        <!-- Preview ảnh -->
                        <div id="imagePreview" class="mt-4">
                            <div id="previewContainer" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4"></div>
                        </div>

                        <!-- Hidden inputs -->
                        <input type="hidden" name="image" id="imageUrl" value="">
                        <input type="hidden" name="images" id="imagesUrl" value="">
                    </div>
                    
                    <!-- Sản phẩm nổi bật -->
                    <div class="flex items-center">
                        <input type="checkbox" 
                               name="is_featured" 
                               class="w-5 h-5 text-primary border-gray-300 rounded focus:ring-primary focus:ring-2" 
                               id="is_featured">
                        <label class="ml-3 text-sm font-medium text-gray-700 cursor-pointer" for="is_featured">
                            Sản phẩm nổi bật
                        </label>
                    </div>
                    
                    <!-- Trạng thái -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Trạng thái</label>
                        <select name="status" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-colors">
                            <option value="active">Hoạt động</option>
                            <option value="inactive">Ẩn</option>
                        </select>
                    </div>
                    
                    <!-- Submit Button -->
                    <div>
                        <button type="submit" 
                                class="w-full px-6 py-3 bg-gradient-to-r from-primary to-primary-light text-secondary font-semibold rounded-lg hover:from-primary-dark hover:to-primary transition-all shadow-md hover:shadow-lg">
                            <i class="fas fa-save mr-2"></i> Lưu sản phẩm
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Help Sidebar -->
    <div class="lg:col-span-4">
        <div class="bg-white rounded-2xl border border-gray-200 shadow-md overflow-hidden">
            <div class="bg-gradient-to-r from-secondary to-secondary-light px-6 py-4">
                <h2 class="text-lg font-semibold text-primary uppercase tracking-wide">
                    <i class="fas fa-question-circle mr-2"></i> Hướng dẫn
                </h2>
            </div>
            <div class="p-6 space-y-4">
                <div class="p-4 bg-primary/10 rounded-xl border-l-4 border-primary">
                    <h6 class="text-primary font-semibold mb-2 flex items-center">
                        <i class="fas fa-cog mr-2"></i> Thông số kỹ thuật
                    </h6>
                    <p class="text-sm text-gray-700 mb-2">
                        Nhập theo định dạng: <code class="px-2 py-1 bg-primary/20 text-primary rounded text-xs font-mono">Tên:Giá trị</code>, phân cách bằng dấu phẩy
                    </p>
                    <p class="text-sm text-gray-700 mb-0">
                        Ví dụ:<br>
                        <code class="px-2 py-1 bg-primary/20 text-primary rounded text-xs font-mono">Màn hình:6.7 inch, Camera:48MP, Pin:5000mAh</code>
                    </p>
                </div>

                <div class="p-4 bg-blue-50 rounded-xl border-l-4 border-blue-500">
                    <h6 class="text-blue-600 font-semibold mb-2 flex items-center">
                        <i class="fas fa-image mr-2"></i> Hình ảnh
                    </h6>
                    <p class="text-sm text-gray-700 mb-2">
                        Chọn file ảnh từ máy tính để upload tự động lên server
                    </p>
                    <ul class="text-sm text-gray-700 space-y-1 list-disc list-inside">
                        <li>Định dạng: JPG, PNG, GIF, WEBP</li>
                        <li>Kích thước tối đa: 5MB</li>
                        <li>Ảnh sẽ tự động resize nếu quá lớn</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Mảng lưu tất cả ảnh đã upload
let allImages = [];

// Upload nhiều ảnh
document.getElementById('imageFiles').addEventListener('change', function(e) {
    const files = e.target.files;
    if (files.length === 0) return;

    const previewContainer = document.getElementById('previewContainer');
    let uploadedCount = 0;
    let totalFiles = files.length;

    for (let i = 0; i < files.length; i++) {
        const file = files[i];

        if (!file.type.startsWith('image/')) {
            alert(`File ${file.name} không phải là ảnh!`);
            totalFiles--;
            continue;
        }

        if (file.size > 5 * 1024 * 1024) {
            alert(`File ${file.name} quá lớn! Tối đa 5MB`);
            totalFiles--;
            continue;
        }

        const formData = new FormData();
        formData.append('image', file);

        fetch('upload-image.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(text => {
            uploadedCount++;
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    allImages.push(data.url);
                    updateHiddenInputs();
                    renderPreviews();
                } else {
                    alert(`Lỗi upload ${file.name}: ${data.message}`);
                }
            } catch (e) {
                alert(`Lỗi upload ${file.name}`);
            }

            if (uploadedCount === totalFiles && totalFiles > 0) {
                alert(`Đã upload ${allImages.length} ảnh!`);
            }
        })
        .catch(error => {
            uploadedCount++;
            alert(`Lỗi: ${error}`);
        });
    }
});

// Cập nhật hidden inputs
function updateHiddenInputs() {
    // Ảnh đầu tiên là ảnh chính
    document.getElementById('imageUrl').value = allImages[0] || '';
    // Tất cả ảnh lưu vào images
    document.getElementById('imagesUrl').value = JSON.stringify(allImages);
}

// Render previews
function renderPreviews() {
    const container = document.getElementById('previewContainer');
    container.innerHTML = '';

    allImages.forEach((url, index) => {
        const div = document.createElement('div');
        div.className = 'relative';
        div.innerHTML = `
            <div class="relative w-full aspect-square bg-gray-100 rounded-xl overflow-hidden">
                <img src="../${url}" 
                     class="w-full h-full object-cover" 
                     onerror="this.src='https://via.placeholder.com/120?text=No+Image'">
                <button type="button" 
                        class="absolute top-2 right-2 w-8 h-8 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors flex items-center justify-center shadow-md" 
                        onclick="removeImage(${index})"
                        title="Xóa ảnh">
                    <i class="fas fa-times text-xs"></i>
                </button>
                ${index === 0 ? '<span class="absolute bottom-2 left-2 px-2 py-1 bg-amber-500 text-white text-xs font-semibold rounded-lg shadow-md">Ảnh chính</span>' : ''}
            </div>
        `;
        container.appendChild(div);
    });

    updateHiddenInputs();
}

// Xóa ảnh
function removeImage(index) {
    allImages.splice(index, 1);
    updateHiddenInputs();
    renderPreviews();
}

// Tính toán khuyến mãi
document.getElementById('apply-discount').addEventListener('click', function() {
    const price = parseFloat(document.getElementById('price').value);
    const discountPercent = parseFloat(document.getElementById('discount_percent').value);
    
    if (!price || price <= 0) {
        alert('Vui lòng nhập giá gốc trước');
        return;
    }
    
    if (!discountPercent || discountPercent < 0 || discountPercent > 100) {
        alert('Vui lòng nhập % khuyến mãi từ 0 đến 100');
        return;
    }
    
    const salePrice = Math.round(price * (1 - discountPercent / 100));
    const savings = Math.round(price - salePrice);
    
    document.getElementById('sale_price').value = salePrice;
    alert(`Giá khuyến mãi: ${salePrice.toLocaleString('vi-VN')}đ\nTiền tiết kiệm: ${savings.toLocaleString('vi-VN')}đ`);
});

// Cho phép nhập trực tiếp vào sale_price
document.getElementById('sale_price').addEventListener('focus', function() {
    this.classList.add('bg-yellow-50');
});

document.getElementById('sale_price').addEventListener('blur', function() {
    this.classList.remove('bg-yellow-50');
});
</script>

<?php include 'includes/footer.php'; ?>

