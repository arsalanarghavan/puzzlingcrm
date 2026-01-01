/**
 * PuzzlingCRM Main Scripts - V4.9.4 (Stable & Refactored)
 * - REMOVED: Delete lead handler is now in its own file (lead-management.js) to prevent conflicts.
 * - FIXED: Critical JS error from persianDatepicker is resolved by safely checking if the function exists before calling it.
 * - This version should be stable across all admin pages.
 */

// Global function for showing alerts using SweetAlert2
function showPuzzlingAlert(title, text, icon, options = {}) {
    let reloadPage = false;
    let redirectUrl = null;

    if (typeof options === 'boolean') {
        reloadPage = options;
    } else if (typeof options === 'object' && options !== null) {
        reloadPage = options.reload || options.reloadPage || false;
        redirectUrl = options.redirect_url || options.redirectUrl || null;
    }

    const lang = (window.puzzlingcrm_ajax_obj && window.puzzlingcrm_ajax_obj.lang) ? window.puzzlingcrm_ajax_obj.lang : {};
    if (typeof Swal === 'undefined') {
        alert(title + "\n" + text);
        if (redirectUrl) {
            window.location.href = redirectUrl;
        } else if (reloadPage) {
            window.location.reload();
        }
        return;
    }

    Swal.fire({
        title: title,
        text: text,
        icon: icon,
        confirmButtonText: lang.ok_button || 'باشه',
        timer: (reloadPage || redirectUrl) ? 2000 : 3500,
        timerProgressBar: true,
        willClose: () => {
            if (redirectUrl) {
                window.location.href = redirectUrl;
            } else if (reloadPage) {
                window.location.reload();
            }
        }
    });
}


jQuery(document).ready(function($) {
    // --- Global Variables ---
    const puzzlingcrm_ajax_obj = window.puzzlingcrm_ajax_obj || {
        ajax_url: '',
        nonce: '',
        lang: {}
    };
    var currentTaskId = null;

    // --- Helper Functions ---
    function formatNumber(n) {
        if (!n) return '0';
        return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }

    function unformatNumber(n) {
        if (typeof n !== 'string') {
            n = String(n || '');
        }
        return parseInt(n.replace(/,/g, ''), 10) || 0;
    }

    /**
     * Get user-friendly error message from error code
     * @param {string} code - Error code
     * @param {object} data - Additional error data
     * @returns {string} - User-friendly error message
     */
    function getErrorMessage(code, data) {
        const errorMessages = {
            'PRJ_ERR_MISSING_CONTRACT_DATA': 'اطلاعات قرارداد ناقص است. لطفاً تمام فیلدهای اجباری را پر کنید.',
            'PRJ_ERR_INVALID_CUSTOMER': 'مشتری انتخاب شده نامعتبر است.',
            'PRJ_ERR_INVALID_START_DATE': 'تاریخ شروع قرارداد نامعتبر است. لطفاً تاریخ را به فرمت صحیح وارد کنید.',
            'PRJ_ERR_CONTRACT_SAVE_FAILED': 'خطا در ذخیره قرارداد. لطفاً دوباره تلاش کنید.',
            'PRJ_ERR_NONCE_FAILED': 'خطای امنیتی. لطفاً صفحه را رفرش کنید و دوباره تلاش کنید.',
            'PRJ_ERR_ACCESS_DENIED': 'شما دسترسی لازم برای انجام این عملیات را ندارید.'
        };
        
        if (data && data.message) {
            return data.message;
        }
        
        if (data && data.input_date) {
            return 'تاریخ وارد شده نامعتبر است: ' + data.input_date;
        }
        
        return errorMessages[code] || 'یک خطا رخ داد. لطفاً دوباره تلاش کنید.';
    }

    /**
     * Validates a Jalali date string (YYYY/MM/DD format)
     * @param {string} dateStr - The date string to validate
     * @returns {boolean} - True if valid, false otherwise
     */
    function validateJalaliDate(dateStr) {
        if (!dateStr || typeof dateStr !== 'string') {
            return false;
        }
        
        // Remove any whitespace
        dateStr = dateStr.trim();
        
        // Check format: YYYY/MM/DD or YYYY/M/D
        if (!/^\d{4}\/\d{1,2}\/\d{1,2}$/.test(dateStr)) {
            return false;
        }
        
        const parts = dateStr.split('/');
        if (parts.length !== 3) {
            return false;
        }
        
        const year = parseInt(parts[0], 10);
        const month = parseInt(parts[1], 10);
        const day = parseInt(parts[2], 10);
        
        // Validate ranges
        if (year < 1300 || year > 2000 || month < 1 || month > 12 || day < 1 || day > 31) {
            return false;
        }
        
        // Basic validation: check day against max days in Persian months
        const persianMonthDays = [31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29];
        const maxDays = persianMonthDays[month - 1];
        
        if (day > maxDays) {
            return false;
        }
        
        return true;
    }

    /**
     * Initializes datepicker for a specific element or all matching elements
     * @param {jQuery} $element - jQuery element(s) to initialize
     */
    function initializeDatepicker($element) {
        if (!$element || $element.length === 0) {
            return;
        }
        
        // Check if persianDatepicker is available
        if (typeof $.fn.persianDatepicker !== 'function') {
            // If not available, try again after a delay
            setTimeout(function() {
                initializeDatepicker($element);
            }, 500);
            return;
        }
        
        // Remove existing datepicker if any
        $element.each(function() {
            const $this = $(this);
            try {
                if ($this.data('pwt-datepicker') || $this.data('persianDatepicker')) {
                    $this.persianDatepicker('destroy');
                }
            } catch(e) {
                // Ignore destroy errors
            }
        });
        
        // Initialize datepickers
        $element.filter(':not(.pwt-datepicker-input-element)').each(function() {
            const $this = $(this);
            try {
                $this.persianDatepicker({
                    format: 'YYYY/MM/DD',
                    autoClose: true,
                    calendarType: 'persian',
                    observer: true,
                    calendar: {
                        persian: {
                            locale: 'fa'
                        }
                    }
                });
            } catch(e) {
                console.error('Error initializing datepicker for element:', e);
            }
        });
    }

    /**
     * Generic AJAX Form Submission Handler
     */
    function handleAjaxFormSubmit(form) {
        var submitButton = form.find('button[type="submit"]');
        var originalButtonHtml = submitButton.html();
        var action = form.data('action') || form.find('input[name="action"]').val();
        
        // Special validation for contract form
        if (action === 'puzzling_manage_contract') {
            // Validate customer
            var customerId = form.find('#customer_id').val();
            if (!customerId || customerId === '0' || customerId === '') {
                showPuzzlingAlert('خطا', 'لطفاً مشتری را انتخاب کنید.', 'error');
                form.find('#customer_id').focus();
                return false;
            }
            
            // Validate start date
            var startDate = form.find('#_project_start_date').val();
            if (!startDate || !validateJalaliDate(startDate)) {
                showPuzzlingAlert('خطا', 'لطفاً تاریخ شروع قرارداد را به درستی وارد کنید (فرمت: YYYY/MM/DD).', 'error');
                form.find('#_project_start_date').focus();
                return false;
            }
            
            // Validate installments if any exist
            var paymentAmounts = form.find('input[name="payment_amount[]"]');
            var paymentDates = form.find('input[name="payment_due_date[]"]');
            
            if (paymentAmounts.length > 0) {
                var hasErrors = false;
                var errorMessages = [];
                
                paymentAmounts.each(function(index) {
                    var $amountInput = $(this);
                    var $dateInput = paymentDates.eq(index);
                    var amount = unformatNumber($amountInput.val());
                    var date = $dateInput.val();
                    
                    if (!amount || amount <= 0) {
                        hasErrors = true;
                        errorMessages.push('قسط شماره ' + (index + 1) + ': مبلغ نامعتبر است.');
                        $amountInput.addClass('is-invalid');
                    } else {
                        $amountInput.removeClass('is-invalid');
                    }
                    
                    if (!date || !validateJalaliDate(date)) {
                        hasErrors = true;
                        errorMessages.push('قسط شماره ' + (index + 1) + ': تاریخ سررسید نامعتبر است.');
                        $dateInput.addClass('is-invalid');
                    } else {
                        $dateInput.removeClass('is-invalid');
                    }
                });
                
                if (hasErrors) {
                    showPuzzlingAlert('خطا', 'لطفاً خطاهای زیر را برطرف کنید:\n' + errorMessages.join('\n'), 'error');
                    return false;
                }
            }
        }
        
        var formData = new FormData(form[0]);
        formData.set('action', action);

        form.find('.item-price, .item-discount, #total_amount').each(function() {
            var inputName = $(this).attr('name');
            if (inputName) {
                formData.set(inputName, unformatNumber($(this).val()));
            }
        });
        form.find('.wp-editor-area').each(function() {
            var editorId = $(this).attr('id');
            if (typeof tinymce !== 'undefined' && tinymce.get(editorId)) {
                formData.set($(this).attr('name'), tinymce.get(editorId).getContent());
            }
        });
        form.find('select:disabled').each(function() {
            formData.set($(this).attr('name'), $(this).val());
        });

        $.ajax({
            url: puzzlingcrm_ajax_obj.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                submitButton.html('<i class="fas fa-spinner fa-spin"></i> در حال پردازش...').prop('disabled', true);
            },
            success: function(response) {
                if (response.success) {
                    const data = response.data;
                    
                    if (action === 'puzzling_add_lead' && typeof window.closeLeadModal === 'function') {
                        window.closeLeadModal();
                    }
                    
                    // Special handling for contract form
                    if (action === 'puzzling_manage_contract') {
                        var successMsg = data.message || 'قرارداد با موفقیت ذخیره شد.';
                        if (data.contract_number) {
                            successMsg += '\nشماره قرارداد: ' + data.contract_number;
                        }
                        setTimeout(() => {
                            showPuzzlingAlert('موفق', successMsg, 'success', data);
                        }, 250);
                    } else {
                        setTimeout(() => {
                            showPuzzlingAlert('موفق', data.message, 'success', data);
                        }, 250);
                    }
                } else {
                    let errorMessage = 'خطای سرور';
                    
                    if (response && response.data) {
                        if (response.data.message) {
                            errorMessage = response.data.message;
                        } else if (response.data.installment_errors && Array.isArray(response.data.installment_errors)) {
                            errorMessage = 'خطا در ثبت اقساط:\n' + response.data.installment_errors.slice(0, 5).join('\n');
                            if (response.data.installment_errors.length > 5) {
                                errorMessage += '\nو ' + (response.data.installment_errors.length - 5) + ' خطای دیگر...';
                            }
                        } else if (response.data.code) {
                            // Use error code to get user-friendly message
                            errorMessage = getErrorMessage(response.data.code, response.data);
                        }
                    }
                    
                    showPuzzlingAlert(puzzlingcrm_ajax_obj.lang.error_title || 'خطا', errorMessage, 'error');
                    submitButton.html(originalButtonHtml).prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                console.error("PuzzlingCRM AJAX Error:", {
                    status: status,
                    error: error,
                    responseText: xhr.responseText,
                    statusCode: xhr.status
                });
                
                let errorMessage = 'یک خطای ناشناخته در ارتباط با سرور رخ داد.';
                
                // Try to parse error response
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.data && response.data.message) {
                        errorMessage = response.data.message;
                    } else if (response.message) {
                        errorMessage = response.message;
                    }
                } catch (e) {
                    // If JSON parsing fails, use status-based messages
                    if (xhr.status === 0) {
                        errorMessage = 'خطا در اتصال به سرور. لطفاً اتصال اینترنت خود را بررسی کنید.';
                    } else if (xhr.status === 403) {
                        errorMessage = 'دسترسی غیرمجاز. لطفاً صفحه را رفرش کنید.';
                    } else if (xhr.status === 404) {
                        errorMessage = 'صفحه مورد نظر یافت نشد. لطفاً صفحه را رفرش کنید.';
                    } else if (xhr.status === 500) {
                        errorMessage = 'خطای داخلی سرور. لطفاً دوباره تلاش کنید.';
                    } else {
                        errorMessage = `خطای سرور (کد: ${xhr.status}). لطفاً دوباره تلاش کنید.`;
                    }
                }
                
                showPuzzlingAlert(puzzlingcrm_ajax_obj.lang.error_title || 'خطا', errorMessage, 'error');
                submitButton.html(originalButtonHtml).prop('disabled', false);
            }
        });
    }

    // --- Attach form submission handler ---
    $(document).on('submit', 'form.pzl-ajax-form', function(e) {
        e.preventDefault();
        handleAjaxFormSubmit($(this));
    });

    // --- Lead Status Changer in List View ---
    $(document).on('change', '.pzl-lead-status-changer', function() {
        const select = $(this);
        const leadId = select.data('lead-id');
        const newStatus = select.val();
        const nonce = select.data('nonce');
        
        let originalValue = select.attr('data-original-value');
        if (typeof originalValue === 'undefined') {
             originalValue = select.find('option[selected]').val();
             if(typeof originalValue === 'undefined') {
                originalValue = select.val();
             }
             select.attr('data-original-value', originalValue);
        }

        select.prop('disabled', true).css('background-color', '#f0f0f0');

        $.ajax({
            url: puzzlingcrm_ajax_obj.ajax_url,
            type: 'POST',
            data: {
                action: 'puzzling_change_lead_status',
                security: nonce,
                lead_id: leadId,
                new_status: newStatus
            },
            success: function(response) {
                if (response.success) {
                    select.css('background-color', '#d4edda');
                    select.attr('data-original-value', newStatus); 
                } else {
                    showPuzzlingAlert('خطا', response.data.message || 'خطای سرور', 'error');
                    select.val(originalValue);
                    select.css('background-color', '#f8d7da');
                }
            },
            error: function() {
                showPuzzlingAlert('خطا', 'خطای سرور.', 'error');
                select.val(originalValue);
            },
            complete: function() {
                select.prop('disabled', false);
                setTimeout(() => select.css('background-color', ''), 1500);
            }
        });
    });


    // --- All other original functions below ---

    if ($('#manage-contract-form').length) {
        console.log('PuzzlingCRM: Contract form found, initializing datepickers...');
        
        // Initialize datepickers multiple times to ensure they work
        function initContractDatepickers() {
            var formDatepickers = $('#manage-contract-form .pzl-jalali-date-picker');
            console.log('PuzzlingCRM: Found', formDatepickers.length, 'datepicker fields in contract form');
            
            // Try persianDatepicker first
            if (typeof $.fn.persianDatepicker === 'function') {
                console.log('PuzzlingCRM: Using persianDatepicker for contract form');
                formDatepickers.each(function() {
                    var $this = $(this);
                    var fieldId = $this.attr('id') || $this.attr('name');
                    console.log('PuzzlingCRM: Initializing datepicker for', fieldId);
                    
                    try {
                        // Destroy existing if any
                        if ($this.data('pwt-datepicker') || $this.data('persianDatepicker')) {
                            $this.persianDatepicker('destroy');
                        }
                        
                        $this.persianDatepicker({
                            format: 'YYYY/MM/DD',
                            autoClose: true,
                            calendarType: 'persian',
                            observer: true
                        });
                        
                        console.log('PuzzlingCRM: Successfully initialized datepicker for', fieldId);
                    } catch(e) {
                        console.error('PuzzlingCRM: Error initializing datepicker for', fieldId, ':', e);
                    }
                });
            } else {
                console.log('PuzzlingCRM: persianDatepicker not available, custom datepicker should handle it');
            }
        }
        
        // Initialize multiple times
        setTimeout(initContractDatepickers, 50);
        setTimeout(initContractDatepickers, 200);
        setTimeout(initContractDatepickers, 500);
        setTimeout(initContractDatepickers, 1000);
        
        // Format price inputs on keyup
        $('body').on('keyup', '.item-price, #total_amount', function() {
            var cursorPosition = this.selectionStart;
            var originalLength = this.value.length;
            var unformatted = unformatNumber($(this).val());
            var formatted = formatNumber(unformatted);
            $(this).val(formatted);
            var newLength = this.value.length;
            this.setSelectionRange(cursorPosition + (newLength - originalLength), cursorPosition + (newLength - originalLength));
        });

        // Validate date fields on blur
        $('body').on('blur', '.pzl-jalali-date-picker', function() {
            var dateValue = $(this).val();
            if (dateValue && !validateJalaliDate(dateValue)) {
                $(this).addClass('is-invalid');
            } else {
                $(this).removeClass('is-invalid');
            }
        });

        function updateContractNumber() {
            const contractIdField = $('input[name="contract_id"]');
            if (contractIdField.val() === '0' || $('#contract_number').val() === 'پس از انتخاب مشتری تولید می‌شود' || $('#contract_number').val() === 'با انتخاب مشتری و تاریخ تولید می‌شود') {
                const customerId = $('#customer_id').val();
                const startDate = $('#_project_start_date').val();
                if (customerId && startDate && validateJalaliDate(startDate)) {
                    const parts = startDate.split('/');
                    if (parts.length === 3) {
                        const year = parts[0].slice(-2);
                        const month = ('0' + parts[1]).slice(-2);
                        const day = ('0' + parts[2]).slice(-2);
                        $('#contract_number').val(`puz-${year}${month}${day}-${customerId}`);
                    }
                } else {
                    $('#contract_number').val('پس از انتخاب مشتری تولید می‌شود');
                }
            }
        }
        $('#customer_id, #_project_start_date').on('change blur', updateContractNumber);
        
        // Initialize contract number on page load
        updateContractNumber();
    }

    $('#cancel-contract-btn').on('click', function() {
        const contractId = $('input[name="contract_id"]').val();
        const nonce = $('#security').val();

        Swal.fire({
            title: 'لغو قرارداد',
            input: 'textarea',
            inputPlaceholder: 'دلیل لغو قرارداد را اینجا وارد کنید (اختیاری)...',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'تایید و لغو',
            cancelButtonText: 'انصراف',
            confirmButtonColor: '#d33',
        }).then((result) => {
            if (result.isConfirmed) {
                $.post(puzzlingcrm_ajax_obj.ajax_url, {
                    action: 'puzzling_cancel_contract',
                    security: nonce,
                    contract_id: contractId,
                    reason: result.value || 'دلیلی ذکر نشده است.'
                }).done(function(response) {
                    if (response.success) {
                        showPuzzlingAlert('موفق', response.data.message, 'success', true);
                    } else {
                        showPuzzlingAlert('خطا', response.data.message, 'error');
                    }
                }).fail(function() {
                    showPuzzlingAlert('خطا', 'یک خطای ناشناخته در ارتباط با سرور رخ داد.', 'error');
                });
            }
        });
    });

    $('#delete-contract-btn').on('click', function() {
        const contractId = $(this).data('contract-id');
        const nonce = $(this).data('nonce');

        Swal.fire({
            title: 'آیا از حذف کامل مطمئن هستید؟',
            text: "این عمل غیرقابل بازگشت است و تمام داده‌های مرتبط حذف خواهند شد.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'بله، حذف دائمی شود!',
            cancelButtonText: 'انصراف',
            confirmButtonColor: '#d33',
        }).then((result) => {
            if (result.isConfirmed) {
                $.post(puzzlingcrm_ajax_obj.ajax_url, {
                    action: 'puzzling_delete_contract',
                    contract_id: contractId,
                    security: puzzlingcrm_ajax_obj.nonce,
                    nonce: nonce,
                }).done(function(response) {
                    if (response.success) {
                        showPuzzlingAlert('موفقیت', response.data.message, 'success');
                        setTimeout(function() {
                            let contractsUrl = new URL(window.location.href);
                            contractsUrl.searchParams.set('view', 'contracts');
                            contractsUrl.searchParams.delete('action');
                            contractsUrl.searchParams.delete('contract_id');
                            window.location.href = contractsUrl.toString();
                        }, 1500);
                    } else {
                        showPuzzlingAlert('خطا', response.data.message, 'error');
                    }
                }).fail(function() {
                    showPuzzlingAlert('خطا', 'یک خطای پیش‌بینی نشده در ارتباط با سرور رخ داد.', 'error');
                });
            }
        });
    });

    $('body').on('click', '#add-services-from-product', function() {
        var button = $(this);
        var contractId = $('input[name="contract_id"]').val();
        var productId = $('#product_id_for_automation').val();
        var nonce = puzzlingcrm_ajax_obj.nonce;

        if (!productId) {
            showPuzzlingAlert('خطا', 'لطفاً ابتدا یک محصول را انتخاب کنید.', 'error');
            return;
        }

        $.ajax({
            url: puzzlingcrm_ajax_obj.ajax_url,
            type: 'POST',
            data: {
                action: 'puzzling_add_services_from_product',
                security: nonce,
                contract_id: contractId,
                product_id: productId
            },
            beforeSend: function() {
                button.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);
            },
            success: function(response) {
                if (response.success) {
                    showPuzzlingAlert('موفق', response.data.message, 'success', true);
                } else {
                    showPuzzlingAlert('خطا', response.data.message, 'error');
                }
            },
            error: function() {
                showPuzzlingAlert('خطا', 'یک خطای پیش‌بینی نشده رخ داد.', 'error');
            },
            complete: function() {
                button.html('افزودن خدمات محصول').prop('disabled', false);
            }
        });
    });

    $('#calculate-installments').on('click', function() {
        var totalAmount = unformatNumber($('#total_amount').val());
        var totalInstallments = parseInt($('#total_installments').val(), 10);
        var intervalDays = parseInt($('#installment_interval').val(), 10) || 30;
        var startDateStr = $('#start_date').val();

        // Validation
        if (!totalAmount || totalAmount <= 0) {
            showPuzzlingAlert('خطا', 'لطفاً مبلغ کل قرارداد را وارد کنید.', 'error');
            $('#total_amount').focus();
            return;
        }

        if (!totalInstallments || totalInstallments <= 0 || isNaN(totalInstallments)) {
            showPuzzlingAlert('خطا', 'لطفاً تعداد اقساط را وارد کنید.', 'error');
            $('#total_installments').focus();
            return;
        }

        if (!intervalDays || intervalDays <= 0 || isNaN(intervalDays)) {
            showPuzzlingAlert('خطا', 'لطفاً فاصله بین اقساط را وارد کنید.', 'error');
            $('#installment_interval').focus();
            return;
        }

        if (!startDateStr || !validateJalaliDate(startDateStr)) {
            showPuzzlingAlert('خطا', 'لطفاً تاریخ اولین قسط را به درستی وارد کنید (فرمت: YYYY/MM/DD).', 'error');
            $('#start_date').focus();
            return;
        }

        // Use fallback calculation which is more reliable
        performCalculationWithFallback();
    });

    function performCalculation() {
        var totalAmount = unformatNumber($('#total_amount').val());
        var totalInstallments = parseInt($('#total_installments').val(), 10);
        var intervalDays = parseInt($('#installment_interval').val(), 10);
        var startDateStr = $('#start_date').val();

        let installmentAmount = Math.floor(totalAmount / totalInstallments);
        let remainder = totalAmount - (installmentAmount * totalInstallments);

        $('#payment-rows-container').empty();

        let dateParts = startDateStr.split('/').map(Number);
        let DateConstructor = persianDate || PersianDate || window.persianDate || window.PersianDate;
        
        // Create Persian date object with proper constructor
        let currentDate;
        try {
            // Try different constructor patterns
            if (typeof DateConstructor === 'function') {
                try {
                    // Try array constructor first
                    currentDate = new DateConstructor(dateParts);
                } catch (e1) {
                    try {
                        // Try individual parameters constructor
                        currentDate = new DateConstructor(dateParts[0], dateParts[1] - 1, dateParts[2]);
                    } catch (e2) {
                        // Try with month not adjusted
                        currentDate = new DateConstructor(dateParts[0], dateParts[1], dateParts[2]);
                    }
                }
            } else {
                throw new Error('DateConstructor is not a function');
            }
        } catch (e) {
            console.error('Error creating Persian date:', e);
            performCalculationWithFallback();
            return;
        }

        for (let i = 0; i < totalInstallments; i++) {
            let currentInstallmentAmount = installmentAmount;
            if (i === 0) {
                currentInstallmentAmount += remainder;
            }

            let installmentDate = (i === 0) ? currentDate.clone() : currentDate.add('days', intervalDays);

            let formattedDate = installmentDate.format('YYYY/MM/DD');
            addInstallmentRow(formatNumber(currentInstallmentAmount), formattedDate, 'pending');
        }
    }

    function performCalculationWithFallback() {
        var totalAmount = unformatNumber($('#total_amount').val());
        var totalInstallments = parseInt($('#total_installments').val(), 10);
        var intervalDays = parseInt($('#installment_interval').val(), 10);
        var startDateStr = $('#start_date').val();

        let installmentAmount = Math.floor(totalAmount / totalInstallments);
        let remainder = totalAmount - (installmentAmount * totalInstallments);

        $('#payment-rows-container').empty();
        $('#installments-preview-container').hide();

        // Validate and parse start date
        if (!validateJalaliDate(startDateStr)) {
            showPuzzlingAlert('خطا', 'تاریخ شروع نامعتبر است. لطفاً تاریخ را به فرمت YYYY/MM/DD وارد کنید.', 'error');
            return;
        }
        
        let parts = startDateStr.split('/');
        let year = parseInt(parts[0], 10);
        let month = parseInt(parts[1], 10);
        let day = parseInt(parts[2], 10);
        
        // Validate parsed values
        if (isNaN(year) || isNaN(month) || isNaN(day) || year < 1300 || year > 2000 || month < 1 || month > 12 || day < 1 || day > 31) {
            showPuzzlingAlert('خطا', 'تاریخ شروع نامعتبر است.', 'error');
            return;
        }
        

        for (let i = 0; i < totalInstallments; i++) {
            let currentInstallmentAmount = installmentAmount;
            if (i === 0) {
                currentInstallmentAmount += remainder;
            }

            // Calculate the installment date by adding days
            let installmentYear = year;
            let installmentMonth = month;
            let installmentDay = day + (i * intervalDays);

            // Better month/day overflow handling for Persian calendar
            const persianMonthDays = [31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29]; // Persian months
            
            // Add days to the current date
            let totalDaysToAdd = i * intervalDays;
            let currentDay = day;
            let currentMonth = month;
            let currentYear = year;
            
            while (totalDaysToAdd > 0) {
                // Get days in current month (handle leap year for month 12)
                let daysInCurrentMonth = persianMonthDays[currentMonth - 1];
                if (currentMonth === 12) {
                    // Simple leap year check (not perfect but better than nothing)
                    // In a real implementation, you'd use proper leap year calculation
                    daysInCurrentMonth = 30; // Default, could be 29 in non-leap years
                }
                
                let daysCanAdd = daysInCurrentMonth - currentDay + 1;
                
                if (totalDaysToAdd >= daysCanAdd) {
                    totalDaysToAdd -= daysCanAdd;
                    currentDay = 1;
                    currentMonth++;
                    if (currentMonth > 12) {
                        currentMonth = 1;
                        currentYear++;
                    }
                } else {
                    currentDay += totalDaysToAdd;
                    totalDaysToAdd = 0;
                }
            }
            
            installmentYear = currentYear;
            installmentMonth = currentMonth;
            installmentDay = currentDay;

            let formattedDate = installmentYear + '/' + 
                               String(installmentMonth).padStart(2, '0') + '/' + 
                               String(installmentDay).padStart(2, '0');
            
            addInstallmentRow(formatNumber(currentInstallmentAmount), formattedDate, 'pending');
        }
        
        // Show the payment rows container after adding installments
        $('#payment-rows-container').show();
        
        // Show success message
        if (totalInstallments > 0) {
            showPuzzlingAlert('موفق', totalInstallments + ' قسط با موفقیت تولید شد.', 'success');
        }
    }

    function addInstallmentRow(amount = '', date = '', status = 'pending') {
        // Create unique ID for this row's date input
        const rowId = 'payment-date-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
        
        var newRow = $(`
            <div class="payment-row d-flex gap-2 align-items-center mb-2">
                <input type="text" name="payment_amount[]" class="form-control item-price" placeholder="مبلغ (تومان)" value="${amount || ''}" required style="flex: 2;">
                <input type="text" name="payment_due_date[]" id="${rowId}" class="form-control pzl-jalali-date-picker" value="${date || ''}" required style="flex: 2;">
                <select name="payment_status[]" class="form-select" style="flex: 1;">
                    <option value="pending" ${status === 'pending' ? 'selected' : ''}>در انتظار پرداخت</option>
                    <option value="paid" ${status === 'paid' ? 'selected' : ''}>پرداخت شده</option>
                    <option value="cancelled" ${status === 'cancelled' ? 'selected' : ''}>لغو شده</option>
                </select>
                <button type="button" class="btn btn-danger-light btn-sm btn-icon remove-payment-row">
                    <i class="ri-delete-bin-line"></i>
                </button>
            </div>
        `);
        
        $('#payment-rows-container').append(newRow);

        // Initialize datepicker for the new input
        const $dateInput = $('#' + rowId);
        initializeDatepicker($dateInput);
        
        // Add validation on blur
        $dateInput.on('blur', function() {
            const dateValue = $(this).val();
            if (dateValue && !validateJalaliDate(dateValue)) {
                $(this).addClass('is-invalid');
                showPuzzlingAlert('خطا', 'تاریخ وارد شده نامعتبر است. لطفاً تاریخ را به فرمت YYYY/MM/DD وارد کنید.', 'error');
            } else {
                $(this).removeClass('is-invalid');
            }
        });
        
        // Format amount on keyup
        newRow.find('.item-price').on('keyup', function() {
            var cursorPosition = this.selectionStart;
            var originalLength = this.value.length;
            var unformatted = unformatNumber($(this).val());
            var formatted = formatNumber(unformatted);
            $(this).val(formatted);
            var newLength = this.value.length;
            this.setSelectionRange(cursorPosition + (newLength - originalLength), cursorPosition + (newLength - originalLength));
        });
    }
    $('#add-payment-row').on('click', function() {
        addInstallmentRow();
    });
    $('#payment-rows-container').on('click', '.remove-payment-row', function() {
        $(this).closest('.payment-row').remove();
    });

    if ($('#pzl-task-board, .pzl-swimlane-board').length) {
        $('.pzl-task-list').sortable({
            connectWith: '.pzl-task-list',
            placeholder: 'pzl-task-card-placeholder',
            start: function(event, ui) {
                ui.placeholder.height(ui.item.outerHeight());
                $('body').addClass('is-dragging');
            },
            stop: function(event, ui) {
                $('body').removeClass('is-dragging');
                var taskCard = ui.item;
                var taskId = taskCard.data('task-id');
                var newStatusSlug = taskCard.closest('.pzl-task-column').data('status-slug');
                taskCard.css('opacity', '0.5');
                $.ajax({
                    url: puzzlingcrm_ajax_obj.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'puzzling_update_task_status',
                        security: puzzlingcrm_ajax_obj.nonce,
                        task_id: taskId,
                        new_status_slug: newStatusSlug
                    },
                    success: function(response) {
                        if (!response.success) {
                            showPuzzlingAlert('خطا', response.data.message, 'error');
                            $(this).sortable('cancel');
                        }
                    },
                    error: function() {
                        showPuzzlingAlert('خطا', 'خطای ارتباط با سرور.', 'error');
                        $(this).sortable('cancel');
                    },
                    complete: function() {
                        taskCard.css('opacity', '1');
                    }
                });
            }
        }).disableSelection();
    }

    function openTaskModal(taskId) {
        if (!taskId) return;
        currentTaskId = taskId;
        $('#pzl-task-modal-backdrop, #pzl-task-modal-wrap').fadeIn(200);
        $('#pzl-task-modal-body').html('<div class="pzl-loader"></div>');
        $.post(puzzlingcrm_ajax_obj.ajax_url, {
            action: 'puzzling_get_task_details',
            security: puzzlingcrm_ajax_obj.nonce,
            task_id: taskId
        }).done(function(response) {
            if (response.success) {
                $('#pzl-task-modal-body').html(response.data.html);
            } else {
                $('#pzl-task-modal-body').html('<p style="color:red;">' + (response.data.message || 'خطا') + '</p>');
            }
        }).fail(function() {
            $('#pzl-task-modal-body').html('<p>خطای ارتباط با سرور.</p>');
        });
    }

    function closeTaskModal() {
        currentTaskId = null;
        $('#pzl-task-modal-backdrop, #pzl-task-modal-wrap').fadeOut(200);
        $('#pzl-task-modal-body').html('');
    }

    $('body').on('click', '.pzl-task-card, .open-task-modal', function(e) {
        if (!$(e.target).hasClass('open-task-modal') && ($(e.target).is('a, button, select') || $(e.target).closest('a, button, select').length)) {
            return;
        }
        e.preventDefault();
        var taskId = $(this).data('task-id') || $(this).closest('[data-task-id]').data('task-id');
        openTaskModal(taskId);
    });

    $('body').on('click', '#pzl-close-modal-btn, #pzl-task-modal-backdrop', function(e) {
        if ($(e.target).is('#pzl-close-modal-btn') || $(e.target).is('#pzl-task-modal-backdrop')) {
            closeTaskModal();
        }
    });

    const urlParamsInstance = new URLSearchParams(window.location.search);
    const openTaskIdFromUrl = urlParamsInstance.get('open_task_id');
    if (openTaskIdFromUrl) {
        openTaskModal(openTaskIdFromUrl);
    }

    function fetchNotifications() {
        $.post(puzzlingcrm_ajax_obj.ajax_url, {
            action: 'puzzling_get_notifications',
            security: puzzlingcrm_ajax_obj.nonce
        }).done(function(response) {
            if (response.success) {
                $('.pzl-notification-panel ul').html(response.data.html);
                var count = parseInt(response.data.count, 10);
                if (count > 0) {
                    $('.pzl-notification-count').text(count).show();
                } else {
                    $('.pzl-notification-count').hide();
                }
            }
        });
    }
    $('.pzl-notification-bell').on('click', function(e) {
        e.stopPropagation();
        $('.pzl-notification-panel').toggle();
    });
    $(document).on('click', function() {
        $('.pzl-notification-panel').hide();
    });
    $('.pzl-notification-panel').on('click', function(e) {
        e.stopPropagation();
    });
    $('.pzl-notification-panel').on('click', 'li.pzl-unread', function() {
        var item = $(this);
        var id = item.data('id');
        $.post(puzzlingcrm_ajax_obj.ajax_url, {
            action: 'puzzling_mark_notification_read',
            security: puzzlingcrm_ajax_obj.nonce,
            id: id
        }).done(function() {
            item.removeClass('pzl-unread').addClass('pzl-read');
            var countEl = $('.pzl-notification-count');
            var currentCount = parseInt(countEl.text(), 10) - 1;
            if (currentCount > 0) {
                countEl.text(currentCount);
            } else {
                countEl.hide();
            }
        });
    });
    if ($('.pzl-notification-bell').length) {
        fetchNotifications();
        setInterval(fetchNotifications, 120000); // Fetch every 2 minutes
    }

    $('body').on('change', '#canned_response_selector', function() {
        var responseId = $(this).val();
        if (!responseId) return;
        var editor = tinymce.get('comment');
        if (!editor) return;
        editor.setContent('<p><i class="fas fa-spinner fa-spin"></i> در حال بارگذاری...</p>');
        $.post(puzzlingcrm_ajax_obj.ajax_url, {
            action: 'puzzling_get_canned_response',
            security: puzzlingcrm_ajax_obj.nonce,
            response_id: responseId
        }).done(function(response) {
            if (response.success) {
                editor.setContent(response.data.content);
            } else {
                editor.setContent('<p style="color:red;">' + (response.data.message || 'خطا') + '</p>');
            }
        }).fail(function() {
            editor.setContent('<p style="color:red;">خطای سرور.</p>');
        });
    });

    // --- Delete Project Handler ---
    $(document).on('click', '.delete-project', function(e) {
        e.preventDefault();
        const button = $(this);
        const projectId = button.data('project-id');
        const nonce = button.data('nonce');
        const projectRow = button.closest('tr');

        if (!projectId || !nonce) {
            showPuzzlingAlert('خطا', 'اطلاعات پروژه یافت نشد.', 'error');
            return;
        }

        // Confirm deletion
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'آیا مطمئن هستید؟',
                text: 'این عمل قابل بازگشت نیست!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'بله، حذف کن',
                cancelButtonText: 'لغو'
            }).then((result) => {
                if (result.isConfirmed) {
                    performDelete();
                }
            });
        } else {
            if (confirm('آیا مطمئن هستید که می‌خواهید این پروژه را حذف کنید؟')) {
                performDelete();
            }
        }

        function performDelete() {
            projectRow.css('opacity', '0.5');
            $.ajax({
                url: puzzlingcrm_ajax_obj.ajax_url,
                type: 'POST',
                data: {
                    action: 'puzzling_delete_project',
                    security: puzzlingcrm_ajax_obj.nonce,
                    project_id: projectId,
                    nonce: nonce
                },
                success: function(response) {
                    if (response.success) {
                        showPuzzlingAlert('موفق', response.data.message || 'پروژه با موفقیت حذف شد.', 'success', { reload: true });
                    } else {
                        showPuzzlingAlert('خطا', response.data.message || 'خطا در حذف پروژه.', 'error');
                        projectRow.css('opacity', '1');
                    }
                },
                error: function() {
                    showPuzzlingAlert('خطا', 'خطا در ارتباط با سرور.', 'error');
                    projectRow.css('opacity', '1');
                }
            });
        }
    });

    // **CRITICAL FIX**: Initialize datepickers - try multiple methods
    function initAllDatepickers() {
        console.log('PuzzlingCRM: Initializing datepickers...');
        console.log('PuzzlingCRM: persianDatepicker available?', typeof $.fn.persianDatepicker === 'function');
        console.log('PuzzlingCRM: PuzzlingJalaliDatePicker available?', typeof PuzzlingJalaliDatePicker !== 'undefined');
        console.log('PuzzlingCRM: window.PuzzlingJalaliDatePickerInstance available?', typeof window.PuzzlingJalaliDatePickerInstance !== 'undefined');
        
        var datepickerFields = $('.pzl-jalali-date-picker');
        console.log('PuzzlingCRM: Found', datepickerFields.length, 'datepicker fields');
        
        if (datepickerFields.length === 0) {
            console.warn('PuzzlingCRM: No datepicker fields found! Checking form...');
            var form = $('#manage-contract-form');
            if (form.length > 0) {
                console.log('PuzzlingCRM: Form found, checking all inputs...');
                form.find('input[type="text"]').each(function() {
                    var $input = $(this);
                    console.log('PuzzlingCRM: Input found - ID:', $input.attr('id'), 'Name:', $input.attr('name'), 'Classes:', $input.attr('class'));
                });
            }
        }
        
        // Method 1: Try persianDatepicker from CDN (PREFERRED)
        if (typeof $.fn.persianDatepicker === 'function') {
            console.log('PuzzlingCRM: Using persianDatepicker from CDN');
            datepickerFields.each(function() {
                var $this = $(this);
                // Skip if already initialized
                if ($this.data('pwt-datepicker') || $this.data('persianDatepicker') || $this.hasClass('pwt-datepicker-input-element')) {
                    console.log('PuzzlingCRM: Datepicker already initialized for', $this.attr('id') || $this.attr('name'));
                    return;
                }
                try {
                    // Destroy any existing datepicker first
                    if ($this.data('pwt-datepicker')) {
                        $this.persianDatepicker('destroy');
                    }
                    
                    $this.persianDatepicker({
                        format: 'YYYY/MM/DD',
                        autoClose: true,
                        calendarType: 'persian',
                        observer: true,
                        initialValue: false
                    });
                    console.log('PuzzlingCRM: Successfully initialized persianDatepicker for', $this.attr('id') || $this.attr('name'));
                } catch(e) {
                    console.error('PuzzlingCRM: Error initializing persianDatepicker for', $this.attr('id') || $this.attr('name'), ':', e);
                }
            });
            return; // Success, exit early
        } 
        
        // Method 2: Use custom datepicker (puzzling-datepicker.js)
        if (typeof window.PuzzlingJalaliDatePickerInstance !== 'undefined' || typeof PuzzlingJalaliDatePicker !== 'undefined') {
            console.log('PuzzlingCRM: Custom datepicker library detected');
            // The custom datepicker should auto-initialize via its own script
            // But let's verify fields are found
            datepickerFields.each(function() {
                var $this = $(this);
                var fieldId = $this.attr('id') || $this.attr('name') || 'unknown';
                console.log('PuzzlingCRM: Checking custom datepicker for field:', fieldId, 'Active:', $this.data('pzl-datepicker-active'));
                if (!$this.data('pzl-datepicker-active')) {
                    console.warn('PuzzlingCRM: Custom datepicker not active for', fieldId, '- may need manual initialization');
                }
            });
        } 
        
        // Method 3: Fallback - try again later
        if (datepickerFields.length > 0 && typeof $.fn.persianDatepicker !== 'function' && typeof window.PuzzlingJalaliDatePickerInstance === 'undefined') {
            console.warn('PuzzlingCRM: No datepicker library found, retrying in 500ms...');
            setTimeout(initAllDatepickers, 500);
        }
    }
    
    // Initialize immediately when DOM is ready
    initAllDatepickers();
    
    // Also try after delays to catch late-loading scripts
    setTimeout(initAllDatepickers, 100);
    setTimeout(initAllDatepickers, 500);
    setTimeout(initAllDatepickers, 1000);
    setTimeout(initAllDatepickers, 2000);

}); // End of jQuery(document).ready