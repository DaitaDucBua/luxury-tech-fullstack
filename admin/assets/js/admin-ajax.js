/**
 * Admin AJAX Functions
 */

$(document).ready(function() {
    
    
    function showToast(message, type) {
        var bgColor = type === 'success' ? '#28a745' : '#dc3545';
        var icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
        var toast = $('<div class="admin-toast" style="position: fixed; top: 20px; right: 20px; padding: 15px 25px; background: ' + bgColor + '; color: white; border-radius: 8px; z-index: 9999; box-shadow: 0 4px 15px rgba(0,0,0,0.2); animation: slideIn 0.3s ease;"><i class="fas ' + icon + ' me-2"></i>' + message + '</div>');
        $('body').append(toast);
        setTimeout(function() {
            toast.fadeOut(300, function() { $(this).remove(); });
        }, 3000);
    }

    
    $(document).on('click', '.delete-product', function(e) {
        e.preventDefault();
        if (!confirm('Bạn có chắc muốn xóa sản phẩm này?')) return;
        
        var btn = $(this);
        var id = btn.data('id');
        var row = btn.closest('tr');
        
        btn.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);
        
        $.post('ajax-handler.php', { action: 'delete_product', id: id }, function(response) {
            if (response.success) {
                row.fadeOut(300, function() { $(this).remove(); });
                showToast(response.message, 'success');
            } else {
                showToast(response.message, 'error');
                btn.html('<i class="fas fa-trash"></i>').prop('disabled', false);
            }
        }, 'json').fail(function() {
            showToast('Có lỗi xảy ra', 'error');
            btn.html('<i class="fas fa-trash"></i>').prop('disabled', false);
        });
    });

    $(document).on('click', '.delete-category', function(e) {
        e.preventDefault();
        if (!confirm('Bạn có chắc muốn xóa danh mục này?')) return;
        
        var btn = $(this);
        var id = btn.data('id');
        var row = btn.closest('tr');
        
        btn.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);
        
        $.post('ajax-handler.php', { action: 'delete_category', id: id }, function(response) {
            if (response.success) {
                row.fadeOut(300, function() { $(this).remove(); });
                showToast(response.message, 'success');
            } else {
                showToast(response.message, 'error');
                btn.html('<i class="fas fa-trash"></i>').prop('disabled', false);
            }
        }, 'json').fail(function() {
            showToast('Có lỗi xảy ra', 'error');
            btn.html('<i class="fas fa-trash"></i>').prop('disabled', false);
        });
    });

    $(document).on('click', '.delete-user', function(e) {
        e.preventDefault();
        if (!confirm('Bạn có chắc muốn xóa người dùng này?')) return;
        
        var btn = $(this);
        var id = btn.data('id');
        var row = btn.closest('tr');
        
        btn.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);
        
        $.post('ajax-handler.php', { action: 'delete_user', id: id }, function(response) {
            if (response.success) {
                row.fadeOut(300, function() { $(this).remove(); });
                showToast(response.message, 'success');
            } else {
                showToast(response.message, 'error');
                btn.html('<i class="fas fa-trash"></i>').prop('disabled', false);
            }
        }, 'json').fail(function() {
            showToast('Có lỗi xảy ra', 'error');
            btn.html('<i class="fas fa-trash"></i>').prop('disabled', false);
        });
    });

    $(document).on('change', '.update-role', function() {
        var select = $(this);
        var id = select.data('id');
        var role = select.val();
        var originalRole = select.data('original');
        
        select.prop('disabled', true);
        
        $.post('ajax-handler.php', { action: 'update_user_role', id: id, role: role }, function(response) {
            if (response.success) {
                select.data('original', role);
                showToast(response.message, 'success');
                
                var row = $('tr').has('button[data-id="' + id + '"]').first();
                var roleCell = row.find('td').eq(6); // Cột role là cột thứ 7 (index 6)
                
                if (role === 'admin') {
                    roleCell.html('<span class="px-3 py-1 rounded-full text-xs font-semibold bg-gradient-to-r from-primary to-primary-light text-secondary">Admin</span>');
                } else {
                    roleCell.html('<span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">Khách hàng</span>');
                }
                
                setTimeout(function() {
                    location.reload();
                }, 1500);
            } else {
                select.val(originalRole);
                showToast(response.message, 'error');
                select.prop('disabled', false);
            }
        }, 'json').fail(function() {
            select.val(originalRole);
            showToast('Có lỗi xảy ra', 'error');
            select.prop('disabled', false);
        });
    });

    $(document).on('submit', '.update-status-form', function(e) {
        e.preventDefault();

        var form = $(this);
        var orderId = form.find('input[name="order_id"]').val();
        var status = form.find('select[name="status"]').val();
        var btn = form.find('button[type="submit"]');
        var originalText = btn.html();

        btn.html('<i class="fas fa-spinner fa-spin me-1"></i>').prop('disabled', true);

        $.post('ajax-handler.php', { action: 'update_order_status', id: orderId, status: status }, function(response) {
            if (response.success) {
                var badges = {
                    'pending': '<span class="badge bg-warning">Chờ xác nhận</span>',
                    'confirmed': '<span class="badge bg-info">Đã xác nhận</span>',
                    'shipping': '<span class="badge bg-primary">Đang giao</span>',
                    'completed': '<span class="badge bg-success">Hoàn thành</span>',
                    'cancelled': '<span class="badge bg-danger">Đã hủy</span>'
                };
                $('tr[data-order-id="' + orderId + '"] .status-cell').html(badges[status]);

                showToast(response.message, 'success');
                btn.html('<i class="fas fa-check me-1"></i> OK');
                setTimeout(function() { btn.html(originalText).prop('disabled', false); }, 1500);
            } else {
                showToast(response.message, 'error');
                btn.html(originalText).prop('disabled', false);
            }
        }, 'json').fail(function() {
            showToast('Có lỗi xảy ra', 'error');
            btn.html(originalText).prop('disabled', false);
        });
    });

    $(document).on('click', '.approve-review', function(e) {
        e.preventDefault();
        var btn = $(this);
        var id = btn.data('id');
        var row = btn.closest('tr');

        btn.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);

        $.post('ajax-handler.php', { action: 'approve_review', id: id }, function(response) {
            if (response.success) {
                row.find('.status-badge').html('<span class="badge bg-success">Đã duyệt</span>');
                btn.remove();
                showToast(response.message, 'success');
            } else {
                showToast(response.message, 'error');
                btn.html('<i class="fas fa-check"></i>').prop('disabled', false);
            }
        }, 'json');
    });

    $(document).on('click', '.reject-review', function(e) {
        e.preventDefault();
        var btn = $(this);
        var id = btn.data('id');
        var row = btn.closest('tr');

        btn.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);

        $.post('ajax-handler.php', { action: 'reject_review', id: id }, function(response) {
            if (response.success) {
                row.find('.status-badge').html('<span class="badge bg-danger">Từ chối</span>');
                btn.remove();
                showToast(response.message, 'success');
            } else {
                showToast(response.message, 'error');
                btn.html('<i class="fas fa-times"></i>').prop('disabled', false);
            }
        }, 'json');
    });

    $(document).on('click', '.delete-review', function(e) {
        e.preventDefault();
        if (!confirm('Bạn có chắc muốn xóa đánh giá này?')) return;

        var btn = $(this);
        var id = btn.data('id');
        var row = btn.closest('tr');

        btn.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);

        $.post('ajax-handler.php', { action: 'delete_review', id: id }, function(response) {
            if (response.success) {
                row.fadeOut(300, function() { $(this).remove(); });
                showToast(response.message, 'success');
            } else {
                showToast(response.message, 'error');
                btn.html('<i class="fas fa-trash"></i>').prop('disabled', false);
            }
        }, 'json');
    });
});

