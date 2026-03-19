/**
 * Social Login & Phone OTP
 */

(function() {
    'use strict';
    
    let otpTimer = null;
    let otpExpiry = null;
    
    // Phone OTP Modal
    function showPhoneOTPModal() {
        $('#phoneOTPModal').modal('show');
    }
    
    // Send OTP
    function sendOTP() {
        const phone = $('#phoneNumber').val().trim();
        
        if (!phone) {
            showError('Vui lòng nhập số điện thoại');
            return;
        }
        
        // Validate phone format
        if (!/^(0|\+84)[0-9]{9}$/.test(phone)) {
            showError('Số điện thoại không hợp lệ');
            return;
        }
        
        const $btn = $('#sendOTPBtn');
        $btn.addClass('btn-loading').prop('disabled', true);
        
        $.post('ajax/phone-otp.php', {
            action: 'send_otp',
            phone: phone
        }, function(response) {
            $btn.removeClass('btn-loading').prop('disabled', false);
            
            if (response.success) {
                showSuccess(response.message);
                $('#phoneStep').hide();
                $('#otpStep').show();
                
                // Start timer
                startOTPTimer();
                
                // Focus first OTP input
                $('.otp-input').first().focus();
            } else {
                showError(response.message);
            }
        }).fail(function() {
            $btn.removeClass('btn-loading').prop('disabled', false);
            showError('Có lỗi xảy ra. Vui lòng thử lại.');
        });
    }
    
    // Verify OTP
    function verifyOTP() {
        const phone = $('#phoneNumber').val().trim();
        const otp = $('.otp-input').map(function() {
            return $(this).val();
        }).get().join('');
        
        if (otp.length !== 6) {
            showError('Vui lòng nhập đầy đủ mã OTP');
            return;
        }
        
        const $btn = $('#verifyOTPBtn');
        $btn.addClass('btn-loading').prop('disabled', true);
        
        $.post('ajax/phone-otp.php', {
            action: 'verify_otp',
            phone: phone,
            otp: otp
        }, function(response) {
            $btn.removeClass('btn-loading').prop('disabled', false);
            
            if (response.success) {
                if (response.action === 'login') {
                    // Login successful
                    showSuccess(response.message);
                    setTimeout(() => {
                        window.location.href = 'index.php';
                    }, 1000);
                } else if (response.action === 'register') {
                    // Show registration form
                    $('#otpStep').hide();
                    $('#registerStep').show();
                    $('#registerPhone').val(response.phone);
                }
            } else {
                showError(response.message);
            }
        }).fail(function() {
            $btn.removeClass('btn-loading').prop('disabled', false);
            showError('Có lỗi xảy ra. Vui lòng thử lại.');
        });
    }
    
    // Register with phone
    function registerWithPhone() {
        const phone = $('#registerPhone').val();
        const fullName = $('#registerFullName').val().trim();
        const email = $('#registerEmail').val().trim();
        
        if (!fullName) {
            showError('Vui lòng nhập họ tên');
            return;
        }
        
        if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            showError('Email không hợp lệ');
            return;
        }
        
        const $btn = $('#registerBtn');
        $btn.addClass('btn-loading').prop('disabled', true);
        
        $.post('ajax/phone-otp.php', {
            action: 'register_with_phone',
            phone: phone,
            full_name: fullName,
            email: email
        }, function(response) {
            $btn.removeClass('btn-loading').prop('disabled', false);
            
            if (response.success) {
                showSuccess(response.message);
                setTimeout(() => {
                    window.location.href = 'index.php?welcome=1';
                }, 1000);
            } else {
                showError(response.message);
            }
        }).fail(function() {
            $btn.removeClass('btn-loading').prop('disabled', false);
            showError('Có lỗi xảy ra. Vui lòng thử lại.');
        });
    }
    
    // Start OTP timer
    function startOTPTimer() {
        const expiryMinutes = 5;
        otpExpiry = new Date(Date.now() + expiryMinutes * 60 * 1000);
        
        updateTimer();
        otpTimer = setInterval(updateTimer, 1000);
    }
    
    // Update timer display
    function updateTimer() {
        const now = new Date();
        const diff = otpExpiry - now;
        
        if (diff <= 0) {
            clearInterval(otpTimer);
            $('#otpTimer').text('Mã OTP đã hết hạn').addClass('expired');
            $('#resendOTPBtn').prop('disabled', false);
            return;
        }
        
        const minutes = Math.floor(diff / 60000);
        const seconds = Math.floor((diff % 60000) / 1000);
        
        $('#otpTimer').text(`Mã có hiệu lực trong ${minutes}:${seconds.toString().padStart(2, '0')}`);
    }
    
    // Show success message
    function showSuccess(message) {
        $('#successMessage').text(message).addClass('show');
        setTimeout(() => {
            $('#successMessage').removeClass('show');
        }, 5000);
    }
    
    // Show error message
    function showError(message) {
        $('#errorMessage').text(message).addClass('show');
        setTimeout(() => {
            $('#errorMessage').removeClass('show');
        }, 5000);
    }
    
    // OTP input auto-focus
    function setupOTPInputs() {
        $('.otp-input').on('input', function() {
            const $this = $(this);
            const val = $this.val();
            
            // Only allow digits
            if (!/^\d*$/.test(val)) {
                $this.val('');
                return;
            }
            
            // Move to next input
            if (val.length === 1) {
                $this.next('.otp-input').focus();
            }
        });
        
        $('.otp-input').on('keydown', function(e) {
            // Backspace - move to previous input
            if (e.key === 'Backspace' && !$(this).val()) {
                $(this).prev('.otp-input').focus();
            }
        });
    }
    
    // Initialize
    $(document).ready(function() {
        // Phone OTP button
        $('#phoneLoginBtn').on('click', showPhoneOTPModal);
        
        // Send OTP
        $('#sendOTPBtn').on('click', sendOTP);
        
        // Verify OTP
        $('#verifyOTPBtn').on('click', verifyOTP);
        
        // Resend OTP
        $('#resendOTPBtn').on('click', function() {
            $(this).prop('disabled', true);
            sendOTP();
        });
        
        // Register
        $('#registerBtn').on('click', registerWithPhone);
        
        // Setup OTP inputs
        setupOTPInputs();
        
        // Reset modal on close
        $('#phoneOTPModal').on('hidden.bs.modal', function() {
            $('#phoneStep').show();
            $('#otpStep, #registerStep').hide();
            $('#phoneNumber').val('');
            $('.otp-input').val('');
            $('#successMessage, #errorMessage').removeClass('show');
            if (otpTimer) {
                clearInterval(otpTimer);
            }
        });
    });
})();

