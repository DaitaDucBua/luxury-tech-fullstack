// ========================================
// AJAX FRAMEWORK - Core AJAX functionality
// ========================================
class AjaxFramework {
    constructor() {
        this.loadingStates = new Map();
        this.init();
    }

    init() {
        // Bind AJAX events
        this.bindAjaxEvents();
        // Initialize loading states
        this.initLoadingStates();
    }

    bindAjaxEvents() {
        // Auto-bind forms with data-ajax attribute
        $(document).on('submit', 'form[data-ajax="true"]', (e) => this.handleAjaxForm(e));
        $(document).on('click', '[data-ajax-action]', (e) => this.handleAjaxAction(e));

        // Handle AJAX responses
        $(document).ajaxComplete((event, xhr, settings) => this.handleAjaxComplete(xhr, settings));
        $(document).ajaxError((event, xhr, settings, error) => this.handleAjaxError(xhr, settings, error));
    }

    handleAjaxForm(e) {
        e.preventDefault();
        const form = $(e.target);
        const url = form.data('ajax-url') || form.attr('action') || window.location.href;
        const method = form.attr('method') || 'POST';
        const formData = new FormData(form[0]);

        // Add action if specified and NOT already in form
        if (form.data('action') && !formData.has('action')) {
            console.log('Adding action:', form.data('action'));
            formData.append('action', form.data('action'));
        }

        // Check for file uploads
        const hasFiles = form.find('input[type="file"]').length > 0;

        this.ajaxRequest(url, method, hasFiles ? formData : this.formDataToObject(formData), {
            button: form.find('button[type="submit"]'),
            loadingText: form.data('loading-text') || 'Đang xử lý...',
            successCallback: form.data('success-callback'),
            errorCallback: form.data('error-callback'),
            hasFiles: hasFiles
        });
    }

    handleAjaxAction(e) {
        e.preventDefault();
        const element = $(e.target).closest('[data-ajax-action]');
        const action = element.data('ajax-action');
        const url = element.data('ajax-url') || window.location.href;
        const method = element.data('method') || 'POST';
        const confirmMessage = element.data('confirm');

        // Confirm if needed
        if (confirmMessage && !confirm(confirmMessage)) {
            return;
        }

        const data = { action: action };
        // Get data attributes
        Object.keys(element.data()).forEach(key => {
            if (key.startsWith('param-')) {
                const paramName = key.replace('param-', '');
                data[paramName] = element.data(key);
            }
        });

        this.ajaxRequest(url, method, data, {
            button: element,
            loadingText: element.data('loading-text') || 'Đang xử lý...',
            successCallback: element.data('success-callback'),
            errorCallback: element.data('error-callback')
        });
    }

    ajaxRequest(url, method, data, options = {}) {
        const {
            button,
            loadingText = 'Đang xử lý...',
            successCallback,
            errorCallback,
            showLoading = true,
            showNotification = true,
            hasFiles = false
        } = options;

        // Store original button state BEFORE modifying it
        let originalButtonState = null;
        if (button && button.length) {
            originalButtonState = {
                html: button.html(),
                disabled: button.prop('disabled')
            };
            console.log('Stored original button state HTML:', originalButtonState.html);
            console.log('Stored original button state disabled:', originalButtonState.disabled);
        }

        // Apply loading state AFTER storing original state
        if (button && button.length && showLoading) {
            button.prop('disabled', true);
            button.html(`<span class="spinner-border spinner-border-sm me-2"></span>${loadingText}`);
        }

        // Show global loading if no specific button
        if (!button && showLoading) {
            this.showGlobalLoading();
        }

        const ajaxOptions = {
            url: url,
            method: method,
            dataType: 'json',
            cache: false,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        };

        // Handle FormData for file uploads
        if (hasFiles || data instanceof FormData) {
            ajaxOptions.data = data;
            ajaxOptions.processData = false;
            ajaxOptions.contentType = false;
        } else {
            ajaxOptions.data = data;
        }

        return $.ajax(ajaxOptions)
        .done((response) => {
            console.log('AJAX success response:', response);
            console.log('Button:', button, 'Button length:', button?.length);
            
            if (response.success) {
                if (showNotification && response.message) {
                    this.showNotification(response.message, 'success');
                }
                if (successCallback && typeof window[successCallback] === 'function') {
                    window[successCallback](response, button);
                }
                // Handle redirects
                if (response.redirect) {
                    this.handleRedirect(response.redirect, response.redirect_delay || 0);
                }
                // Handle UI updates
                if (response.update_elements) {
                    this.updateElements(response.update_elements);
                }
                
        // Explicit button reset on success
                if (button && button.length && originalButtonState) {
                    console.log('SUCCESS: Resetting button');
                    console.log('Reset HTML to:', originalButtonState.html);
                    console.log('Reset disabled to:', originalButtonState.disabled);
                    button.prop('disabled', originalButtonState.disabled);
                    button.html(originalButtonState.html || '<i class="fas fa-save me-2"></i>Lưu Thay Đổi');
                }
            } else {
                if (showNotification && response.message) {
                    this.showNotification(response.message, 'error');
                }
                if (errorCallback && typeof window[errorCallback] === 'function') {
                    window[errorCallback](response, button);
                }
            }
        })
        .fail((xhr, status, error) => {
            console.error('AJAX fail:', xhr, status, error);
            let message = 'Có lỗi xảy ra, vui lòng thử lại';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                message = xhr.responseJSON.message;
            }
            if (showNotification) {
                this.showNotification(message, 'error');
            }
        })
        .always(() => {
            console.log('AJAX always - Reset button');
            // Reset button state
            if (button && button.length && originalButtonState) {
                console.log('Resetting button from:', originalButtonState);
                button.prop('disabled', originalButtonState.disabled);
                button.html(originalButtonState.html);
                // Force repaint
                button.css('opacity', '1');
            }
            // Hide global loading
            if (!button) {
                this.hideGlobalLoading();
            }
        });
    }

    formDataToObject(formData) {
        const obj = {};
        for (let [key, value] of formData.entries()) {
            if (obj[key]) {
                if (Array.isArray(obj[key])) {
                    obj[key].push(value);
                } else {
                    obj[key] = [obj[key], value];
                }
            } else {
                obj[key] = value;
            }
        }
        return obj;
    }

    setLoadingState(element, loadingText = 'Đang xử lý...') {
        const $element = $(element);
        const originalHtml = $element.html();
        const originalDisabled = $element.prop('disabled');

        console.log('setLoadingState - Element:', element, 'Map size before:', this.loadingStates.size);
        
        this.loadingStates.set(element, {
            originalHtml,
            originalDisabled
        });

        console.log('setLoadingState - Map size after:', this.loadingStates.size);

        $element.prop('disabled', true);
        $element.html(`<span class="spinner-border spinner-border-sm me-2"></span>${loadingText}`);
    }

    clearLoadingState(element) {
        const $element = $(element);
        const state = this.loadingStates.get(element);

        console.log('clearLoadingState - Element:', element, 'State:', state, 'Map size:', this.loadingStates.size);

        if (state) {
            $element.prop('disabled', state.originalDisabled);
            $element.html(state.originalHtml);
            this.loadingStates.delete(element);
            console.log('clearLoadingState - CLEARED');
        } else {
            console.warn('clearLoadingState - NO STATE FOUND!');
        }
    }

    showGlobalLoading() {
        if (!$('#global-loading').length) {
            $('body').append(`
                <div id="global-loading" class="position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center" style="background: rgba(0,0,0,0.5); z-index: 9999;">
                    <div class="bg-white p-4 rounded shadow">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Đang tải...</span>
                        </div>
                        <div class="mt-2">Đang xử lý...</div>
                    </div>
                </div>
            `);
        }
        $('#global-loading').show();
    }

    hideGlobalLoading() {
        $('#global-loading').fadeOut(() => $(this).remove());
    }

    showNotification(message, type = 'info') {
        // Create notification container if not exists
        if (!$('#notification-container').length) {
            $('body').append(`
                <div id="notification-container" style="
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    z-index: 9999;
                    max-width: 400px;
                    pointer-events: none;
                "></div>
            `);
        }

        const alertClass = type === 'success' ? 'alert-success' :
                          type === 'error' ? 'alert-danger' :
                          type === 'warning' ? 'alert-warning' : 'alert-info';

        const icon = type === 'success' ? 'fa-check-circle' :
                    type === 'error' ? 'fa-exclamation-circle' :
                    type === 'warning' ? 'fa-exclamation-triangle' : 'fa-info-circle';

        const iconColor = type === 'success' ? '#28a745' :
                         type === 'error' ? '#dc3545' :
                         type === 'warning' ? '#ffc107' : '#17a2b8';

        // Generate unique ID for this notification
        const notificationId = 'notification-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);

        const alert = $(`
            <div id="${notificationId}" class="notification-item alert ${alertClass} alert-dismissible fade show shadow-lg"
                 style="
                     margin-bottom: 12px;
                     min-width: 300px;
                     max-width: 400px;
                     border-radius: 12px;
                     border: none;
                     box-shadow: 0 4px 20px rgba(0,0,0,0.15);
                     pointer-events: auto;
                     animation: slideInRight 0.3s ease-out;
                     opacity: 0;
                     transform: translateX(100%);
                 " role="alert">
                <div class="d-flex align-items-start">
                    <div class="flex-shrink-0" style="font-size: 1.5rem; color: ${iconColor}; margin-top: 2px;">
                        <i class="fas ${icon}"></i>
                    </div>
                    <div class="flex-grow-1 ms-2" style="line-height: 1.5;">
                        ${message}
                    </div>
                    <button type="button" class="btn-close ms-2" data-bs-dismiss="alert" aria-label="Close" style="margin-top: 2px;"></button>
                </div>
            </div>
        `);

        $('#notification-container').append(alert);

        // Animate in
        setTimeout(() => {
            alert.css({
                'opacity': '1',
                'transform': 'translateX(0)'
            });
        }, 10);

        // Auto remove after 3 seconds for all notification types
        const autoRemoveDelay = 3000;
        const autoRemoveTimer = setTimeout(() => {
            this.removeNotification(notificationId);
        }, autoRemoveDelay);

        // Store timer for manual removal
        alert.data('autoRemoveTimer', autoRemoveTimer);

        // Handle close button
        alert.find('.btn-close').on('click', () => {
            clearTimeout(autoRemoveTimer);
            this.removeNotification(notificationId);
        });

        // Limit to max 5 notifications
        const notifications = $('#notification-container .notification-item');
        if (notifications.length > 5) {
            notifications.first().each((index, el) => {
                const timer = $(el).data('autoRemoveTimer');
                if (timer) clearTimeout(timer);
                this.removeNotification($(el).attr('id'));
            });
        }
    }

    removeNotification(notificationId) {
        const notification = $('#' + notificationId);
        if (notification.length) {
            notification.css({
                'opacity': '0',
                'transform': 'translateX(100%)',
                'transition': 'all 0.3s ease-out'
            });
            
            setTimeout(() => {
                notification.remove();
                // Remove container if empty
                if ($('#notification-container .notification-item').length === 0) {
                    $('#notification-container').remove();
                }
            }, 300);
        }
    }

    handleRedirect(url, delay = 0) {
        if (delay > 0) {
            setTimeout(() => {
                window.location.href = url;
            }, delay * 1000);
        } else {
            window.location.href = url;
        }
    }

    updateElements(updates) {
        Object.keys(updates).forEach(selector => {
            const $element = $(selector);
            const update = updates[selector];

            if (update.html !== undefined) {
                $element.html(update.html);
            }
            if (update.text !== undefined) {
                $element.text(update.text);
            }
            if (update.value !== undefined) {
                $element.val(update.value);
            }
            if (update.attr) {
                Object.keys(update.attr).forEach(attr => {
                    $element.attr(attr, update.attr[attr]);
                });
            }
            if (update.css) {
                $element.css(update.css);
            }
            if (update.addClass) {
                $element.addClass(update.addClass);
            }
            if (update.removeClass) {
                $element.removeClass(update.removeClass);
            }
            if (update.hide) {
                $element.hide();
            }
            if (update.show) {
                $element.show();
            }
            if (update.fadeIn) {
                $element.fadeIn(update.fadeIn);
            }
            if (update.fadeOut) {
                $element.fadeOut(update.fadeOut);
            }
        });
    }

    handleAjaxComplete(xhr, settings) {
        // Handle common AJAX complete actions
        // Update CSRF tokens if needed
        const newToken = xhr.getResponseHeader('X-CSRF-Token');
        if (newToken) {
            $('input[name="_token"]').val(newToken);
        }
    }

    handleAjaxError(xhr, settings, error) {
        console.error('AJAX Error:', error, xhr, settings);
        // Handle global error scenarios
    }

    initLoadingStates() {
        // Add loading class to body during AJAX
        $(document).ajaxStart(() => $('body').addClass('ajax-loading'));
        $(document).ajaxStop(() => $('body').removeClass('ajax-loading'));
    }
}

// Initialize AJAX Framework globally
try {
    window.Ajax = new AjaxFramework();
    console.log('AJAX Framework initialized successfully');
} catch (error) {
    console.error('AJAX Framework initialization failed:', error);
}

// Global function for backward compatibility
window.showNotification = function(message, type = 'info') {
    if (typeof Ajax !== 'undefined') {
        Ajax.showNotification(message, type);
    } else {
        // Fallback
        alert(message);
    }
};
