/**
 * PuzzlingCRM Main Scripts - V3.4 (FINAL FIX)
 * This version corrects AJAX nonce handling for all contract actions (Create, Delete, Cancel).
 */

// Define this function in the global scope so other scripts can access it.
function showPuzzlingAlert(title, text, icon, reloadPage = false) {
    if (typeof Swal === 'undefined') {
        alert(title + "\n" + text);
        if (reloadPage) { window.location.reload(); }
        return;
    }
    Swal.fire({
        title: title,
        text: text,
        icon: icon,
        confirmButtonText: puzzlingcrm_ajax_obj.lang.ok_button || 'باشه',
        timer: reloadPage ? 2500 : 4000,
        timerProgressBar: true
    }).then(() => {
        if (reloadPage) {
            let currentUrl = new URL(window.location.href);
            let params = currentUrl.searchParams;
            ['puzzling_notice', '_wpnonce', 'deleted', 'updated', 'open_task_id'].forEach(param => params.delete(param));
            currentUrl.search = params.toString();
            window.location.href = currentUrl.toString();
        }
    });
}


jQuery(document).ready(function($) {
    // --- Global Variables ---
    var puzzling_ajax_nonce = puzzlingcrm_ajax_obj.nonce;
    var puzzling_lang = puzzlingcrm_ajax_obj.lang;
    var currentTaskId = null;

    // --- Helper Functions ---
    function formatNumber(n) {
        if (!n) return '';
        return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }

    function unformatNumber(n) {
        if (typeof n !== 'string') { n = String(n || ''); }
        return parseInt(n.replace(/,/g, ''), 10) || 0;
    }

    /**
     * Generic AJAX Form Submission Handler
     */
    function handleAjaxFormSubmit(form) {
        var submitButton = form.find('button[type="submit"]');
        var originalButtonHtml = submitButton.html();
        var formData = new FormData(form[0]);
        var action = form.data('action') || form.find('input[name="action"]').val();
        
        formData.set('action', action);

        form.find('.item-price, .item-discount, #total_amount').each(function() {
            var inputName = $(this).attr('name');
            if(inputName){
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
                    showPuzzlingAlert(puzzling_lang.success_title || 'موفق', response.data.message, 'success', response.data.reload || false);
                    if (response.data.redirect_url) {
                        setTimeout(function() { window.location.href = response.data.redirect_url; }, 1500);
                    }
                } else {
                    let errorCode = (response.data && response.data.error_code) ? ' (کد: ' + response.data.error_code + ')' : '';
                    let errorMessage = (response.data && response.data.message) ? response.data.message : puzzling_lang.server_error;
                    showPuzzlingAlert((puzzling_lang.error_title || 'خطا') + errorCode, errorMessage, 'error');
                }
            },
            error: function() { 
                showPuzzlingAlert(puzzling_lang.error_title || 'خطا', 'یک خطای ناشناخته در ارتباط با سرور رخ داد.', 'error');
            },
            complete: function(xhr) {
                var response = xhr.responseJSON;
                if (!(response && response.success && (response.data.reload || response.data.redirect_url))) {
                    submitButton.html(originalButtonHtml).prop('disabled', false);
                }
            }
        });
    }

    $('body').on('submit', 'form.pzl-ajax-form', function(e) {
        e.preventDefault();
        handleAjaxFormSubmit($(this));
    });

    // --- Contract Form Logic ---
    if ($('#manage-contract-form').length) {
        // Apply number formatting on keyup
        $('body').on('keyup', '.item-price, #total_amount', function() {
             var cursorPosition = this.selectionStart;
            var originalLength = this.value.length;
            var unformatted = unformatNumber($(this).val());
            var formatted = formatNumber(unformatted);
            $(this).val(formatted);
            var newLength = this.value.length;
            this.setSelectionRange(cursorPosition + (newLength - originalLength), cursorPosition + (newLength - originalLength));
        });

        function updateContractNumber() {
            const contractIdField = $('input[name="contract_id"]');
            if (contractIdField.val() === '0' || $('#contract_number').val() === 'با انتخاب مشتری و تاریخ تولید می‌شود') {
                const customerId = $('#customer_id').val();
                const startDate = $('#_project_start_date').val();
                if (customerId && startDate) {
                    const parts = startDate.split('/');
                    if(parts.length === 3) {
                        const year = parts[0].slice(-2);
                        const month = ('0' + parts[1]).slice(-2);
                        const day = ('0' + parts[2]).slice(-2);
                        $('#contract_number').val(`puz-${year}${month}${day}-${customerId}`);
                    }
                }
            }
        }
        $('#customer_id, #_project_start_date').on('change', updateContractNumber);
    }

    // --- Contract Actions (Cancel & Delete) - CORRECTED ---
    $('#cancel-contract-btn').on('click', function() {
        const contractId = $('input[name="contract_id"]').val();
        const nonce = $('#security').val(); // **FIX**: Get the correct nonce from the form field

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
                    security: nonce, // **FIX**: Send the correct nonce
                    contract_id: contractId,
                    reason: result.value || 'دلیلی ذکر نشده است.'
                }).done(function(response) {
                    if (response.success) { showPuzzlingAlert('موفق', response.data.message, 'success', true); }
                    else { showPuzzlingAlert('خطا', response.data.message, 'error'); }
                }).fail(function() {
                    showPuzzlingAlert('خطا', 'یک خطای ناشناخته در ارتباط با سرور رخ داد.', 'error');
                });
            }
        });
    });

    $('#delete-contract-btn').on('click', function() {
        const contractId = $(this).data('contract-id');
        const specificNonce = $(this).data('nonce'); // Action-specific nonce
        const generalNonce = $('#security').val(); // General form nonce

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
                    nonce: specificNonce,     // **FIX**: For wp_verify_nonce
                    security: generalNonce      // **FIX**: For check_ajax_referer
                }).done(function(response) {
                     if (response.success) {
                        showPuzzlingAlert('موفقیت', response.data.message, 'success');
                        setTimeout(function() {
                            // Redirect to the main contracts list page
                            let contractsUrl = new URL(window.location.href);
                            contractsUrl.searchParams.set('view', 'contracts');
                            contractsUrl.searchParams.delete('action');
                            contractsUrl.searchParams.delete('contract_id');
                            window.location.href = contractsUrl.toString();
                        }, 1500);
                    } else {
                        showPuzzlingAlert('خطا', response.data.message, 'error');
                    }
                }).fail(function(){
                     showPuzzlingAlert('خطا', 'یک خطای پیش‌بینی نشده در ارتباط با سرور رخ داد.', 'error');
                });
            }
        });
    });
    
    // --- Add services from product ---
    $('body').on('click', '#add-services-from-product', function() {
        var button = $(this);
        var contractId = $('input[name="contract_id"]').val();
        var productId = $('#product_id_for_automation').val();

        if (!productId) {
            showPuzzlingAlert('خطا', 'لطفاً ابتدا یک محصول را انتخاب کنید.', 'error');
            return;
        }

        $.ajax({
            url: puzzlingcrm_ajax_obj.ajax_url,
            type: 'POST',
            data: {
                action: 'puzzling_add_services_from_product',
                security: puzzling_ajax_nonce,
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


    // --- Intelligent Installment Calculation ---
    $('#calculate-installments').on('click', function() {
        var totalAmount = unformatNumber($('#total_amount').val());
        var totalInstallments = parseInt($('#total_installments').val(), 10);
        var intervalDays = parseInt($('#installment_interval').val(), 10);
        var startDateStr = $('#start_date').val();

        if (!totalAmount || !totalInstallments || isNaN(intervalDays) || !startDateStr) {
            showPuzzlingAlert(puzzling_lang.error_title || 'خطا', 'لطفاً تمام فیلدهای محاسبه‌گر را پر کنید.', 'error');
            return;
        }
        
        if (typeof persianDate === 'undefined') {
             showPuzzlingAlert(puzzling_lang.error_title || 'خطا', 'کتابخانه تقویم بارگذاری نشده است.', 'error');
             return;
        }

        let installmentAmount = Math.floor(totalAmount / totalInstallments);
        let remainder = totalAmount - (installmentAmount * totalInstallments);

        $('#payment-rows-container').empty();
        
        let dateParts = startDateStr.split('/').map(Number);
        let currentDate = new persianDate(dateParts);

        for (let i = 0; i < totalInstallments; i++) {
            let currentInstallmentAmount = installmentAmount;
            if (i === 0) {
                currentInstallmentAmount += remainder;
            }
            
            let installmentDate = (i === 0) ? currentDate.clone() : currentDate.add('days', intervalDays);
            
            let formattedDate = installmentDate.format('YYYY/MM/DD');
            addInstallmentRow(formatNumber(currentInstallmentAmount), formattedDate, 'pending');
        }
    });

    function addInstallmentRow(amount = '', date = '', status = 'pending') {
        var newRow = `<div class="payment-row form-group" style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
            <input type="text" name="payment_amount[]" class="item-price" placeholder="مبلغ (تومان)" value="${amount}" style="flex-grow: 1;" required>
            <input type="text" name="payment_due_date[]" class="pzl-jalali-date-picker" value="${date}" required>
            <select name="payment_status[]">
                <option value="pending" ${status === 'pending' ? 'selected' : ''}>در انتظار پرداخت</option>
                <option value="paid" ${status === 'paid' ? 'selected' : ''}>پرداخت شده</option>
                <option value="cancelled" ${status === 'cancelled' ? 'selected' : ''}>لغو شده</option>
            </select>
            <button type="button" class="pzl-button pzl-button-sm remove-payment-row" style="background: #dc3545 !important;">حذف</button>
        </div>`;
        $('#payment-rows-container').append(newRow);
        
        $('.pzl-jalali-date-picker:not(.pwt-datepicker-input-element)').persianDatepicker({
            format: 'YYYY/MM/DD',
            autoClose: true
        });
    }
    $('#add-payment-row').on('click', function() { addInstallmentRow(); });
    $('#payment-rows-container').on('click', '.remove-payment-row', function() { $(this).closest('.payment-row').remove(); });
    
    // --- Kanban Board: Drag and Drop ---
    if ($('#pzl-task-board, .pzl-swimlane-board').length) {
        $('.pzl-task-list').sortable({
            connectWith: '.pzl-task-list',
            placeholder: 'pzl-task-card-placeholder',
            start: function(event, ui) { ui.placeholder.height(ui.item.outerHeight()); $('body').addClass('is-dragging'); },
            stop: function(event, ui) {
                $('body').removeClass('is-dragging');
                var taskCard = ui.item;
                var taskId = taskCard.data('task-id');
                var newStatusSlug = taskCard.closest('.pzl-task-column').data('status-slug');
                taskCard.css('opacity', '0.5');
                $.ajax({
                    url: puzzlingcrm_ajax_obj.ajax_url, type: 'POST',
                    data: { action: 'puzzling_update_task_status', security: puzzling_ajax_nonce, task_id: taskId, new_status_slug: newStatusSlug },
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
                    complete: function() { taskCard.css('opacity', '1'); }
                });
            }
        }).disableSelection();
    }
    
    // --- Task Modal ---
    function openTaskModal(taskId) {
        if (!taskId) return;
        currentTaskId = taskId;
        $('#pzl-task-modal-backdrop, #pzl-task-modal-wrap').fadeIn(200);
        $('#pzl-task-modal-body').html('<div class="pzl-loader"></div>');
        $.post(puzzlingcrm_ajax_obj.ajax_url, { action: 'puzzling_get_task_details', security: puzzling_ajax_nonce, task_id: taskId })
        .done(function(response) {
            if (response.success) { 
                $('#pzl-task-modal-body').html(response.data.html); 
            } else { 
                $('#pzl-task-modal-body').html('<p style="color:red;">' + response.data.message + '</p>'); 
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
        if ( !$(e.target).hasClass('open-task-modal') && ($(e.target).is('a, button, select') || $(e.target).closest('a, button, select').length) ) {
            return;
        }
        e.preventDefault();
        var taskId = $(this).data('task-id') || $(this).closest('[data-task-id]').data('task-id');
        openTaskModal(taskId);
    });
    
    $('body').on('click', '#pzl-close-modal-btn, #pzl-task-modal-backdrop', function(e) {
        if ($(e.target).is('#pzl-close-modal-btn') || $(e.target).is('#pzl-task-modal-backdrop')) { closeTaskModal(); }
    });
    
    const urlParamsInstance = new URLSearchParams(window.location.search);
    const openTaskIdFromUrl = urlParamsInstance.get('open_task_id');
    if (openTaskIdFromUrl) {
        openTaskModal(openTaskIdFromUrl);
    }
    
    // --- Notifications ---
    function fetchNotifications() {
        $.post(puzzlingcrm_ajax_obj.ajax_url, { action: 'puzzling_get_notifications', security: puzzling_ajax_nonce })
        .done(function(response) {
            if (response.success) {
                $('.pzl-notification-panel ul').html(response.data.html);
                var count = parseInt(response.data.count);
                if (count > 0) {
                    $('.pzl-notification-count').text(count).show();
                } else {
                    $('.pzl-notification-count').hide();
                }
            }
        });
    }
    $('.pzl-notification-bell').on('click', function(e) { e.stopPropagation(); $('.pzl-notification-panel').toggle(); });
    $(document).on('click', function() { $('.pzl-notification-panel').hide(); });
    $('.pzl-notification-panel').on('click', function(e) { e.stopPropagation(); });
    $('.pzl-notification-panel').on('click', 'li.pzl-unread', function() {
        var item = $(this);
        var id = item.data('id');
        item.removeClass('pzl-unread').addClass('pzl-read');
        var countEl = $('.pzl-notification-count');
        var currentCount = parseInt(countEl.text()) - 1;
        if (currentCount > 0) {
            countEl.text(currentCount);
        } else {
            countEl.hide();
        }
        $.post(puzzlingcrm_ajax_obj.ajax_url, { action: 'puzzling_mark_notification_read', security: puzzling_ajax_nonce, id: id });
    });
    if($('.pzl-notification-bell').length) {
        fetchNotifications();
        setInterval(fetchNotifications, 120000);
    }

    // --- Canned Response Selector ---
    $('body').on('change', '#canned_response_selector', function() {
        var responseId = $(this).val();
        if (!responseId) return;
        var editor = tinymce.get('comment');
        if (!editor) return;
        editor.setContent('<p><i class="fas fa-spinner fa-spin"></i> در حال بارگذاری...</p>');
        $.post(puzzlingcrm_ajax_obj.ajax_url, { action: 'puzzling_get_canned_response', security: puzzling_ajax_nonce, response_id: responseId })
        .done(function(response) {
            if (response.success) { editor.setContent(response.data.content); }
            else { editor.setContent('<p style="color:red;">' + response.data.message + '</p>'); }
        }).fail(function(){
             editor.setContent('<p style="color:red;">خطای سرور.</p>');
        });
    });

    // Initial datepicker setup
    $('.pzl-jalali-date-picker').persianDatepicker({
        format: 'YYYY/MM/DD',
        autoClose: true
    });

}); // End of jQuery(document).ready