<?php
session_start();
require_once '../config/config.php';

// Kiểm tra admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$page_title = 'Dashboard & Thống Kê';

// Lấy thống kê tổng quan
$stats = [];

// Tổng doanh thu
$revenue_query = "SELECT SUM(total_amount) as total FROM orders WHERE status != 'cancelled'";
$stats['total_revenue'] = $conn->query($revenue_query)->fetch_assoc()['total'] ?? 0;

// Tổng đơn hàng
$orders_query = "SELECT COUNT(*) as total FROM orders";
$stats['total_orders'] = $conn->query($orders_query)->fetch_assoc()['total'] ?? 0;

// Tổng khách hàng
$customers_query = "SELECT COUNT(*) as total FROM users WHERE role = 'customer'";
$stats['total_customers'] = $conn->query($customers_query)->fetch_assoc()['total'] ?? 0;

// Tổng sản phẩm
$products_query = "SELECT COUNT(*) as total FROM products WHERE status = 'active'";
$stats['total_products'] = $conn->query($products_query)->fetch_assoc()['total'] ?? 0;

// Doanh thu 7 ngày gần đây
$revenue_7days = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $query = "SELECT COALESCE(SUM(total_amount), 0) as revenue 
              FROM orders 
              WHERE DATE(created_at) = '$date' AND status != 'cancelled'";
    $revenue = $conn->query($query)->fetch_assoc()['revenue'];
    $revenue_7days[] = [
        'date' => date('d/m', strtotime($date)),
        'revenue' => floatval($revenue)
    ];
}

// Top 5 sản phẩm bán chạy
$top_products_query = "SELECT p.name, SUM(od.quantity) as total_sold, SUM(od.subtotal) as revenue
                       FROM order_details od
                       JOIN products p ON od.product_id = p.id
                       JOIN orders o ON od.order_id = o.id
                       WHERE o.status != 'cancelled'
                       GROUP BY p.id
                       ORDER BY total_sold DESC
                       LIMIT 5";
$top_products_result = $conn->query($top_products_query);
$top_products = [];
while ($row = $top_products_result->fetch_assoc()) {
    $top_products[] = $row;
}

// Đơn hàng theo trạng thái
$order_status_query = "SELECT status, COUNT(*) as count FROM orders GROUP BY status";
$order_status_result = $conn->query($order_status_query);
$order_status = [];
while ($row = $order_status_result->fetch_assoc()) {
    $order_status[] = $row;
}

// Khách hàng mới 30 ngày
$new_customers_30days = [];
for ($i = 29; $i >= 0; $i -= 5) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $query = "SELECT COUNT(*) as count 
              FROM users 
              WHERE role = 'customer' AND DATE(created_at) = '$date'";
    $count = $conn->query($query)->fetch_assoc()['count'];
    $new_customers_30days[] = [
        'date' => date('d/m', strtotime($date)),
        'count' => intval($count)
    ];
}

include 'includes/header.php';
?>

<!-- Page Header -->
<div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 pb-4 mb-6 border-b border-gray-200">
    <h1 class="text-2xl font-semibold text-gray-800 flex items-center">
        <i class="fas fa-chart-line mr-3 text-primary"></i> Dashboard & Thống Kê
    </h1>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-gradient-to-br from-secondary to-secondary-light rounded-2xl p-6 text-white shadow-lg">
        <div class="flex justify-between items-center">
            <div class="flex-1">
                <p class="text-sm opacity-75 mb-2">Tổng Doanh Thu</p>
                <h3 class="text-2xl font-bold text-primary"><?php echo number_format($stats['total_revenue']); ?>đ</h3>
            </div>
            <div class="w-16 h-16 bg-primary/20 rounded-full flex items-center justify-center">
                <i class="fas fa-dollar-sign text-primary text-2xl"></i>
            </div>
        </div>
    </div>

    <div class="bg-gradient-to-br from-secondary to-secondary-light rounded-2xl p-6 text-white shadow-lg">
        <div class="flex justify-between items-center">
            <div class="flex-1">
                <p class="text-sm opacity-75 mb-2">Tổng Đơn Hàng</p>
                <h3 class="text-2xl font-bold text-green-400"><?php echo number_format($stats['total_orders']); ?></h3>
            </div>
            <div class="w-16 h-16 bg-green-500/20 rounded-full flex items-center justify-center">
                <i class="fas fa-shopping-cart text-green-400 text-2xl"></i>
            </div>
        </div>
    </div>

    <div class="bg-gradient-to-br from-secondary to-secondary-light rounded-2xl p-6 text-white shadow-lg">
        <div class="flex justify-between items-center">
            <div class="flex-1">
                <p class="text-sm opacity-75 mb-2">Khách Hàng</p>
                <h3 class="text-2xl font-bold text-blue-400"><?php echo number_format($stats['total_customers']); ?></h3>
            </div>
            <div class="w-16 h-16 bg-blue-500/20 rounded-full flex items-center justify-center">
                <i class="fas fa-users text-blue-400 text-2xl"></i>
            </div>
        </div>
    </div>

    <div class="bg-gradient-to-br from-secondary to-secondary-light rounded-2xl p-6 text-white shadow-lg">
        <div class="flex justify-between items-center">
            <div class="flex-1">
                <p class="text-sm opacity-75 mb-2">Sản Phẩm</p>
                <h3 class="text-2xl font-bold text-amber-400"><?php echo number_format($stats['total_products']); ?></h3>
            </div>
            <div class="w-16 h-16 bg-amber-500/20 rounded-full flex items-center justify-center">
                <i class="fas fa-box text-amber-400 text-2xl"></i>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row 1 -->
<div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mb-6">
    <div class="lg:col-span-8">
        <div class="bg-white rounded-2xl border border-gray-200 shadow-md overflow-hidden">
            <div class="bg-gradient-to-r from-secondary to-secondary-light px-6 py-4">
                <h2 class="text-lg font-semibold text-primary uppercase tracking-wide flex items-center">
                    <i class="fas fa-chart-area mr-2"></i> Doanh Thu 7 Ngày Gần Đây
                </h2>
            </div>
            <div class="p-6">
                <canvas id="revenueChart" height="80"></canvas>
            </div>
        </div>
    </div>

    <div class="lg:col-span-4">
        <div class="bg-white rounded-2xl border border-gray-200 shadow-md overflow-hidden">
            <div class="bg-gradient-to-r from-secondary to-secondary-light px-6 py-4">
                <h2 class="text-lg font-semibold text-primary uppercase tracking-wide flex items-center">
                    <i class="fas fa-chart-pie mr-2"></i> Đơn Hàng Theo Trạng Thái
                </h2>
            </div>
            <div class="p-6">
                <canvas id="orderStatusChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row 2 -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <div>
        <div class="bg-white rounded-2xl border border-gray-200 shadow-md overflow-hidden">
            <div class="bg-gradient-to-r from-secondary to-secondary-light px-6 py-4">
                <h2 class="text-lg font-semibold text-primary uppercase tracking-wide flex items-center">
                    <i class="fas fa-trophy mr-2"></i> Top 5 Sản Phẩm Bán Chạy
                </h2>
            </div>
            <div class="p-6">
                <canvas id="topProductsChart" height="120"></canvas>
            </div>
        </div>
    </div>

    <div>
        <div class="bg-white rounded-2xl border border-gray-200 shadow-md overflow-hidden">
            <div class="bg-gradient-to-r from-secondary to-secondary-light px-6 py-4">
                <h2 class="text-lg font-semibold text-primary uppercase tracking-wide flex items-center">
                    <i class="fas fa-user-plus mr-2"></i> Khách Hàng Mới (30 Ngày)
                </h2>
            </div>
            <div class="p-6">
                <canvas id="newCustomersChart" height="120"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
// Revenue Chart - Gold theme
const revenueData = <?php echo json_encode($revenue_7days); ?>;
new Chart(document.getElementById('revenueChart'), {
    type: 'line',
    data: {
        labels: revenueData.map(d => d.date),
        datasets: [{
            label: 'Doanh thu (đ)',
            data: revenueData.map(d => d.revenue),
            borderColor: '#c9a050',
            backgroundColor: 'rgba(201, 160, 80, 0.15)',
            tension: 0.4,
            fill: true,
            pointBackgroundColor: '#c9a050',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: 5
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: true }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return new Intl.NumberFormat('vi-VN').format(value) + 'đ';
                    }
                }
            }
        }
    }
});

// Order Status Chart
const orderStatusData = <?php echo json_encode($order_status); ?>;
const statusLabels = {
    'pending': 'Chờ xử lý',
    'processing': 'Đang xử lý',
    'shipped': 'Đang giao',
    'delivered': 'Đã giao',
    'cancelled': 'Đã hủy'
};
const statusColors = {
    'pending': '#ffc107',
    'processing': '#17a2b8',
    'shipped': '#007bff',
    'delivered': '#28a745',
    'cancelled': '#dc3545'
};

new Chart(document.getElementById('orderStatusChart'), {
    type: 'doughnut',
    data: {
        labels: orderStatusData.map(d => statusLabels[d.status] || d.status),
        datasets: [{
            data: orderStatusData.map(d => d.count),
            backgroundColor: orderStatusData.map(d => statusColors[d.status] || '#6c757d')
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom' }
        }
    }
});

// Top Products Chart - Gold gradient
const topProductsData = <?php echo json_encode($top_products); ?>;
new Chart(document.getElementById('topProductsChart'), {
    type: 'bar',
    data: {
        labels: topProductsData.map(p => p.name.substring(0, 20) + '...'),
        datasets: [{
            label: 'Số lượng bán',
            data: topProductsData.map(p => p.total_sold),
            backgroundColor: ['#c9a050', '#dbb970', '#b08d3e', '#e8c97a', '#9a7a30'],
            borderRadius: 6
        }]
    },
    options: {
        responsive: true,
        indexAxis: 'y',
        plugins: {
            legend: { display: false }
        },
        scales: {
            x: { beginAtZero: true }
        }
    }
});

// New Customers Chart - Blue theme
const newCustomersData = <?php echo json_encode($new_customers_30days); ?>;
new Chart(document.getElementById('newCustomersChart'), {
    type: 'bar',
    data: {
        labels: newCustomersData.map(d => d.date),
        datasets: [{
            label: 'Khách hàng mới',
            data: newCustomersData.map(d => d.count),
            backgroundColor: '#3b82f6',
            borderRadius: 6
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { stepSize: 1 }
            }
        }
    }
});
</script>

<?php include 'includes/footer.php'; ?>

