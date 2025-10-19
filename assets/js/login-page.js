/**
 * PuzzlingCRM Login Page Scripts
 * Handles SMS OTP and password login
 */

(function($) {
    'use strict';

    let otpTimer = null;
    let otpTimeRemaining = 300; // 5 minutes in seconds

    $(document).ready(function() {
        initLoginPage();
    });

    function initLoginPage() {
        // Tab switching
        $('.puzzling-tab-btn').on('click', function() {
            const tab = $(this).data('tab');
            switchTab(tab);
        });

        // Send OTP
        $('#puzzling-send-otp-btn').on('click', function(e) {
            e.preventDefault();
            sendOTP();
        });

        // Verify OTP
        $('#puzzling-otp-form').on('submit', function(e) {
            e.preventDefault();
            verifyOTP();
        });

        // Resend OTP
        $('#puzzling-resend-otp-btn').on('click', function(e) {
            e.preventDefault();
            sendOTP();
        });

        // Change phone number
        $('#puzzling-change-phone-btn').on('click', function(e) {
            e.preventDefault();
            goToStep(1);
            $('#otp_code').val('');
            stopTimer();
        });

        // Password login
        $('#puzzling-password-form').on('submit', function(e) {
            e.preventDefault();
            loginWithPassword();
        });

        // Toggle password visibility
        $('.puzzling-toggle-password').on('click', function() {
            togglePasswordVisibility($(this));
        });

        // Auto-format phone number input
        $('#phone_number').on('input', function() {
            formatPhoneNumber($(this));
        });

        // Auto-format OTP input
        $('#otp_code').on('input', function() {
            formatOTPCode($(this));
        });

        // Enter key handling for OTP input
        $('#otp_code').on('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                $('#puzzling-verify-otp-btn').click();
            }
        });
    }

    function switchTab(tab) {
        $('.puzzling-tab-btn').removeClass('active');
        $(`.puzzling-tab-btn[data-tab="${tab}"]`).addClass('active');

        $('.puzzling-login-form-container').hide();
        if (tab === 'sms') {
            $('#sms-login-form').show();
        } else {
            $('#password-login-form').show();
        }

        // Reset forms
        resetForms();
    }

    function sendOTP() {
        const $btn = $('#puzzling-send-otp-btn, #puzzling-resend-otp-btn');
        const phoneNumber = $('#phone_number').val().trim();

        if (!phoneNumber) {
            showError('لطفاً شماره موبایل خود را وارد کنید.');
            return;
        }

        if (!validatePhoneNumber(phoneNumber)) {
            showError('فرمت شماره موبایل صحیح نیست.');
            return;
        }

        // Disable button and show loading
        $btn.prop('disabled', true).addClass('loading');
        const originalText = $btn.html();
        $btn.html('<i class="fas fa-spinner fa-spin"></i> در حال ارسال...');

        $.ajax({
            url: puzzlingcrm_ajax_obj.ajax_url,
            type: 'POST',
            data: {
                action: 'puzzling_send_login_otp',
                security: puzzlingcrm_ajax_obj.nonce,
                phone_number: phoneNumber
            },
            success: function(response) {
                if (response.success) {
                    showSuccess(response.data.message);
                    goToStep(2);
                    startTimer(response.data.expires_in || 300);
                    $('#otp_code').focus();
                } else {
                    showError(response.data.message || 'خطا در ارسال کد تایید');
                }
            },
            error: function() {
                showError('خطا در ارتباط با سرور. لطفاً مجدداً تلاش کنید.');
            },
            complete: function() {
                $btn.prop('disabled', false).removeClass('loading');
                $btn.html(originalText);
            }
        });
    }

    function verifyOTP() {
        const $btn = $('#puzzling-verify-otp-btn');
        const phoneNumber = $('#phone_number').val().trim();
        const otpCode = $('#otp_code').val().trim();

        if (!otpCode) {
            showError('لطفاً کد تایید را وارد کنید.');
            return;
        }

        if (otpCode.length !== 6) {
            showError('کد تایید باید 6 رقم باشد.');
            return;
        }

        // Disable button and show loading
        $btn.prop('disabled', true).addClass('loading');
        const originalText = $btn.html();
        $btn.html('<i class="fas fa-spinner fa-spin"></i> در حال ورود...');

        $.ajax({
            url: puzzlingcrm_ajax_obj.ajax_url,
            type: 'POST',
            data: {
                action: 'puzzling_verify_login_otp',
                security: puzzlingcrm_ajax_obj.nonce,
                phone_number: phoneNumber,
                otp_code: otpCode
            },
            success: function(response) {
                if (response.success) {
                    showSuccess(response.data.message);
                    stopTimer();
                    
                    // Redirect after 1 second
                    setTimeout(function() {
                        window.location.href = response.data.redirect_url;
                    }, 1000);
                } else {
                    showError(response.data.message || 'کد تایید اشتباه است');
                    $('#otp_code').val('').focus();
                }
            },
            error: function() {
                showError('خطا در ارتباط با سرور. لطفاً مجدداً تلاش کنید.');
            },
            complete: function() {
                $btn.prop('disabled', false).removeClass('loading');
                $btn.html(originalText);
            }
        });
    }

    function loginWithPassword() {
        const $btn = $('#puzzling-password-form button[type="submit"]');
        const username = $('#username').val().trim();
        const password = $('#password').val();
        const remember = $('#puzzling-password-form input[name="remember"]').is(':checked');

        if (!username || !password) {
            showError('لطفاً تمام فیلدها را پر کنید.');
            return;
        }

        // Disable button and show loading
        $btn.prop('disabled', true).addClass('loading');
        const originalText = $btn.html();
        $btn.html('<i class="fas fa-spinner fa-spin"></i> در حال ورود...');

        $.ajax({
            url: puzzlingcrm_ajax_obj.ajax_url,
            type: 'POST',
            data: {
                action: 'puzzling_login_with_password',
                security: puzzlingcrm_ajax_obj.nonce,
                username: username,
                password: password,
                remember: remember
            },
            success: function(response) {
                if (response.success) {
                    showSuccess(response.data.message);
                    
                    // Redirect after 1 second
                    setTimeout(function() {
                        window.location.href = response.data.redirect_url;
                    }, 1000);
                } else {
                    showError(response.data.message || 'نام کاربری یا رمز عبور اشتباه است');
                }
            },
            error: function() {
                showError('خطا در ارتباط با سرور. لطفاً مجدداً تلاش کنید.');
            },
            complete: function() {
                $btn.prop('disabled', false).removeClass('loading');
                $btn.html(originalText);
            }
        });
    }

    function goToStep(step) {
        $('.puzzling-form-step').removeClass('active');
        $(`.puzzling-form-step[data-step="${step}"]`).addClass('active');
    }

    function startTimer(seconds) {
        otpTimeRemaining = seconds;
        updateTimerDisplay();
        
        if (otpTimer) {
            clearInterval(otpTimer);
        }

        otpTimer = setInterval(function() {
            otpTimeRemaining--;
            updateTimerDisplay();

            if (otpTimeRemaining <= 0) {
                stopTimer();
                showWarning('کد تایید منقضی شده است. لطفاً کد جدید درخواست کنید.');
                $('#puzzling-resend-otp-btn').show();
            }
        }, 1000);

        $('#puzzling-resend-otp-btn').hide();
    }

    function stopTimer() {
        if (otpTimer) {
            clearInterval(otpTimer);
            otpTimer = null;
        }
    }

    function updateTimerDisplay() {
        const minutes = Math.floor(otpTimeRemaining / 60);
        const seconds = otpTimeRemaining % 60;
        const display = `${minutes}:${seconds.toString().padStart(2, '0')}`;
        
        // Convert to Persian numerals if needed
        const persianDisplay = convertToPersianNumerals(display);
        $('#puzzling-timer').text(persianDisplay);

        if (otpTimeRemaining <= 30) {
            $('#puzzling-timer').addClass('warning');
        } else {
            $('#puzzling-timer').removeClass('warning');
        }
    }

    function validatePhoneNumber(phone) {
        // Support both English and Persian numerals
        const pattern = /^(09|۰۹)[0-9۰-۹]{9}$/;
        return pattern.test(phone);
    }

    function formatPhoneNumber($input) {
        let value = $input.val();
        // Convert Persian/Arabic numerals to English
        value = convertToEnglishNumerals(value);
        // Remove non-numeric characters
        value = value.replace(/[^0-9]/g, '');
        // Limit to 11 digits
        value = value.substring(0, 11);
        $input.val(value);
    }

    function formatOTPCode($input) {
        let value = $input.val();
        // Convert Persian/Arabic numerals to English
        value = convertToEnglishNumerals(value);
        // Remove non-numeric characters
        value = value.replace(/[^0-9]/g, '');
        // Limit to 6 digits
        value = value.substring(0, 6);
        $input.val(value);
    }

    function togglePasswordVisibility($btn) {
        const targetId = $btn.data('target');
        const $input = $(`#${targetId}`);
        const $icon = $btn.find('i');

        if ($input.attr('type') === 'password') {
            $input.attr('type', 'text');
            $icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            $input.attr('type', 'password');
            $icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    }

    function resetForms() {
        $('#puzzling-otp-form')[0].reset();
        $('#puzzling-password-form')[0].reset();
        goToStep(1);
        stopTimer();
    }

    function convertToEnglishNumerals(str) {
        const persianNumbers = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        const arabicNumbers = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        
        for (let i = 0; i < 10; i++) {
            str = str.replace(new RegExp(persianNumbers[i], 'g'), i);
            str = str.replace(new RegExp(arabicNumbers[i], 'g'), i);
        }
        
        return str;
    }

    function convertToPersianNumerals(str) {
        const persianNumbers = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        return str.replace(/[0-9]/g, function(w) {
            return persianNumbers[+w];
        });
    }

    function showSuccess(message) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'success',
                title: 'موفق',
                text: message,
                confirmButtonText: 'باشه',
                timer: 3000
            });
        } else {
            alert(message);
        }
    }

    function showError(message) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'خطا',
                text: message,
                confirmButtonText: 'باشه'
            });
        } else {
            alert(message);
        }
    }

    function showWarning(message) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'warning',
                title: 'توجه',
                text: message,
                confirmButtonText: 'باشه'
            });
        } else {
            alert(message);
        }
    }

})(jQuery);

