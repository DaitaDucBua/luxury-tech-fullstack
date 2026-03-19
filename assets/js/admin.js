$(document).ready(function() {
    // Auto dismiss alerts after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
    
    // AJAX actions for admin
    $('[data-ajax-action]').on('click', function(e) {
            e.preventDefault();
        const element = $(this);
        const action = element.data('ajax-action');
        const confirmMessage = element.data('confirm');

        // Confirm if needed
        if (confirmMessage && !confirm(confirmMessage)) {
            return;
        }

        const data = {
            action: action,
            id: element.data('id')
        };

        // Set loading text based on action
        let loadingText = 'Đang xử lý...';
        if (action.includes('delete')) loadingText = 'Đang xóa...';
        else if (action.includes('approve')) loadingText = 'Đang duyệt...';
        else if (action.includes('reject')) loadingText = 'Đang từ chối...';

        Ajax.ajaxRequest('ajax-handler.php', 'POST', data, {
            button: element,
            loadingText: loadingText,
            successCallback: function(response) {
                // Handle different actions
                if (action.includes('delete')) {
                    // Remove row from table
                    element.closest('tr').fadeOut(300, function() {
                        $(this).remove();
                        // Update table if empty
                        if ($('.table tbody tr').length === 0) {
                            location.reload();
                        }
                    });
                } else if (action.includes('approve') || action.includes('reject')) {
                    // Update status badge in place without reload
                    const row = element.closest('tr');
                    const statusBadge = row.find('.status-badge .badge');

                    if (action.includes('approve')) {
                        statusBadge.removeClass('bg-warning bg-danger').addClass('bg-success');
                        statusBadge.text('Đã duyệt');
                    } else if (action.includes('reject')) {
                        statusBadge.removeClass('bg-warning bg-success').addClass('bg-danger');
                        statusBadge.text('Đã từ chối');
                    }

                    // Disable the clicked button
                    element.prop('disabled', true);

                    // Show success message
                    showAlert('success', response.message || 'Cập nhật thành công!');
                }
            }
        });
    });
    
    // Format currency inputs
    $('input[type="number"][step="1000"]').on('blur', function() {
        const value = $(this).val();
        if (value) {
            $(this).val(Math.round(value / 1000) * 1000);
        }
    });
    
    // Preview image on input change
    $('input[name="image"]').on('blur', function() {
        const url = $(this).val();
        const preview = $(this).siblings('img');
        if (url && preview.length) {
            preview.attr('src', '../' + url);
        }
    });
    
    // Auto-generate slug from name
    $('input[name="name"]').on('blur', function() {
        const name = $(this).val();
        const slug = name.toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');
        
        const slugInput = $('input[name="slug"]');
        if (slugInput.length && !slugInput.val()) {
            slugInput.val(slug);
        }
    });
    
    // Table row hover effect
    $('.table tbody tr').hover(
        function() {
            $(this).addClass('table-active');
        },
        function() {
            $(this).removeClass('table-active');
        }
    );
    
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Helper function to show alerts
    function showAlert(type, message) {
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const alert = $(`
            <div class="alert ${alertClass} alert-dismissible fade show position-fixed"
                 style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;" role="alert">
                <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `);

        $('body').append(alert);

        setTimeout(() => {
            alert.fadeOut(300, function() { $(this).remove(); });
        }, 3000);
    }
});

