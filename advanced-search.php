<?php
session_start();
require_once 'config/config.php';

$page_title = 'Tìm Kiếm Nâng Cao';
include 'includes/header.php';
?>

<style>
.search-autocomplete {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    max-height: 400px;
    overflow-y: auto;
    z-index: 1000;
    display: none;
}

.autocomplete-item {
    padding: 10px 15px;
    cursor: pointer;
    border-bottom: 1px solid #f0f0f0;
    display: flex;
    align-items: center;
    gap: 15px;
}

.autocomplete-item:hover {
    background: #f8f9fa;
}

.autocomplete-item img {
    width: 50px;
    height: 50px;
    object-fit: contain;
}

.filter-card {
    position: sticky;
    top: 20px;
}
</style>

<div class="container my-5">
    <div class="row">
        <!-- Sidebar Filters -->
        <div class="col-md-3">
            <div class="card shadow-sm filter-card" data-aos="fade-right">
                <div class="card-header" style="background: linear-gradient(135deg, #2f80ed 0%, #1e6fd9 100%); color: white;">
                    <h5 class="mb-0"><i class="fas fa-filter"></i> Bộ Lọc</h5>
                </div>
                <div class="card-body">
                    <!-- Search Input -->
                    <div class="mb-4 position-relative">
                        <label class="form-label fw-bold">Tìm kiếm</label>
                        <input type="text" id="searchInput" class="form-control" 
                               placeholder="Nhập tên sản phẩm...">
                        <div id="searchAutocomplete" class="search-autocomplete"></div>
                    </div>
                    
                    <!-- Category Filter -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">Danh mục</label>
                        <select id="categoryFilter" class="form-select">
                            <option value="0">Tất cả danh mục</option>
                        </select>
                    </div>
                    
                    <!-- Price Range -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">Khoảng giá</label>
                        <div class="row g-2">
                            <div class="col-6">
                                <input type="number" id="minPrice" class="form-control form-control-sm" placeholder="Từ">
                            </div>
                            <div class="col-6">
                                <input type="number" id="maxPrice" class="form-control form-control-sm" placeholder="Đến">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sort -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">Sắp xếp</label>
                        <select id="sortFilter" class="form-select">
                            <option value="newest">Mới nhất</option>
                            <option value="popular">Phổ biến nhất</option>
                            <option value="price_asc">Giá: Thấp → Cao</option>
                            <option value="price_desc">Giá: Cao → Thấp</option>
                            <option value="name_asc">Tên: A → Z</option>
                            <option value="name_desc">Tên: Z → A</option>
                        </select>
                    </div>
                    
                    <!-- Buttons -->
                    <button id="applyFilters" class="btn btn-primary w-100 mb-2">
                        <i class="fas fa-search"></i> Tìm kiếm
                    </button>
                    <button id="resetFilters" class="btn btn-outline-secondary w-100">
                        <i class="fas fa-redo"></i> Đặt lại
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Products Grid -->
        <div class="col-md-9">
            <!-- Results Header -->
            <div class="d-flex justify-content-between align-items-center mb-4" data-aos="fade-down">
                <div>
                    <h4 id="resultsTitle">Kết quả tìm kiếm</h4>
                    <p class="text-muted mb-0" id="resultsCount">Đang tải...</p>
                </div>
            </div>
            
            <!-- Loading -->
            <div id="loadingSpinner" class="text-center py-5" style="display: none;">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-3">Đang tìm kiếm...</p>
            </div>
            
            <!-- Products Grid -->
            <div id="productsGrid" class="row g-4"></div>
            
            <!-- No Results -->
            <div id="noResults" class="text-center py-5" style="display: none;">
                <i class="fas fa-search fa-4x text-muted mb-3"></i>
                <h5>Không tìm thấy sản phẩm</h5>
                <p class="text-muted">Vui lòng thử lại với từ khóa khác hoặc điều chỉnh bộ lọc</p>
            </div>
            
            <!-- Pagination -->
            <nav id="pagination" class="mt-4" style="display: none;">
                <ul class="pagination justify-content-center"></ul>
            </nav>
        </div>
    </div>
</div>

<script src="assets/js/advanced-search.js"></script>

<?php include 'includes/footer.php'; ?>

