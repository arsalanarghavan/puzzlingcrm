/**
 * PuzzlingCRM Main Scripts - V3.2
 * Core AJAX, UI, and business logic.
 * Datepicker functionality is now in puzzling-datepicker.js
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
        timer: reloadPage ? 2000 : 4000,
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
    var searchTimer;
    var calendar;

    /**
     * Generic AJAX Form Submission Handler
     */
    function handleAjaxFormSubmit(form) {
        var submitButton = form.find('button[type="submit"]');
        var originalButtonHtml = submitButton.html();
        var formData = new FormData(form[0]);
        var action = form.data('action') || form.find('input[name="action"]').val();
        formData.append('action', action);

        // Handle specific form data preparations
        form.find('.item-price, .item-discount').each(function() {
            formData.set($(this).attr('name'), $(this).val().replace(/,/g, ''));
        });
        form.find('.wp-editor-area').each(function() {
            var editorId = $(this).attr('id');
            if (typeof tinymce !== 'undefined' && tinymce.get(editorId)) {
                formData.set($(this).attr('name'), tinymce.get(editorId).getContent());
            }
        });
        form.find('select:disabled').each(function() {
            formData.append($(this).attr('name'), $(this).val());
        });

        $.ajax({
            url: puzzlingcrm_ajax_obj.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                submitButton.html('<i class="fas fa-spinner fa-spin"></i> در حال ذخیره...').prop('disabled', true);
            },
            success: function(response) {
                if (response.success) {
                    showPuzzlingAlert(puzzling_lang.success_title, response.data.message, 'success', response.data.reload || false);
                    if (response.data.redirect_url) {
                        setTimeout(function() { window.location.href = response.data.redirect_url; }, 1500);
                    }
                } else {
                    showPuzzlingAlert(puzzling_lang.error_title, response.data.message, 'error');
                }
            },
            error: function() {
                showPuzzlingAlert(puzzling_lang.error_title, puzzling_lang.server_error, 'error');
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
        function updateContractNumber() {
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
        $('#customer_id, #_project_start_date').on('change', updateContractNumber);
        if ($('input[name="contract_id"]').val() > 0) {
            updateContractNumber();
        }
        $('#cancel-contract-btn').on('click', function() {
            const contractId = $('input[name="contract_id"]').val();
            Swal.fire({
                title: 'لغو قرارداد',
                text: "لطفاً دلیل لغو این قرارداد را وارد کنید:",
                input: 'textarea',
                inputPlaceholder: 'دلیل لغو...',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'تایید و لغو قرارداد',
                cancelButtonText: 'انصراف',
                confirmButtonColor: '#d33',
                preConfirm: (reason) => {
                    if (!reason) { Swal.showValidationMessage('وارد کردن دلیل لغو الزامی است.') }
                    return reason;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: puzzlingcrm_ajax_obj.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'puzzling_cancel_contract',
                            security: puzzling_ajax_nonce,
                            contract_id: contractId,
                            reason: result.value
                        },
                        success: function(response) {
                            if (response.success) { showPuzzlingAlert('موفق', response.data.message, 'success', true); }
                            else { showPuzzlingAlert('خطا', response.data.message, 'error'); }
                        },
                        error: function() { showPuzzlingAlert('خطا', 'خطای سرور.', 'error'); }
                    });
                }
            });
        });
    }

    // Moved outside of the if block to ensure it always binds
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
                showPuzzlingAlert('خطا', 'خطای سرور.', 'error');
            },
            complete: function() {
                button.html('افزودن خدمات محصول').prop('disabled', false);
            }
        });
    });


    // --- Intelligent Installment Calculation ---
    $('#calculate-installments').on('click', function() {
        var totalAmount = parseFloat($('#total_amount').val().replace(/,/g, ''));
        var totalInstallments = parseInt($('#total_installments').val());
        var intervalDays = parseInt($('#installment_interval').val());
        var startDateStr = $('#start_date').val();
        if (isNaN(totalAmount) || isNaN(totalInstallments) || isNaN(intervalDays) || !startDateStr) {
            showPuzzlingAlert(puzzling_lang.error_title, 'لطفاً تمام فیلدهای محاسبه‌گر اقساط را به درستی پر کنید.', 'error');
            return;
        }
        var installmentAmount = Math.round(totalAmount / totalInstallments);
        let parts = startDateStr.split('/');
        let jYear = parseInt(parts[0]);
        let jMonth = parseInt(parts[1]);
        let jDay = parseInt(parts[2]);
        $('#payment-rows-container').html('');
        for (var i = 0; i < totalInstallments; i++) {
            let totalDaysToAdd = jDay + (i * intervalDays);
            let finalYear = jYear;
            let finalMonth = jMonth;
            while(totalDaysToAdd > 30) {
                 totalDaysToAdd -= 30;
                 finalMonth++;
                 if(finalMonth > 12) { finalMonth = 1; finalYear++; }
            }
            let finalDay = totalDaysToAdd;
            var inputDate = finalYear + '/' + ('0' + finalMonth).slice(-2) + '/' + ('0' + finalDay).slice(-2);
            addInstallmentRow(installmentAmount, inputDate, 'pending');
        }
    });

    function addInstallmentRow(amount = '', date = '', status = 'pending') {
        var newRow = `<div class="payment-row form-group" style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;"><input type="number" name="payment_amount[]" placeholder="مبلغ (تومان)" value="${amount}" style="flex-grow: 1;" required><input type="text" name="payment_due_date[]" class="pzl-jalali-date-picker" value="${date}" required><select name="payment_status[]"><option value="pending" ${status === 'pending' ? 'selected' : ''}>در انتظار پرداخت</option><option value="paid" ${status === 'paid' ? 'selected' : ''}>پرداخت شده</option><option value="cancelled" ${status === 'cancelled' ? 'selected' : ''}>لغو شده</option></select><button type="button" class="pzl-button pzl-button-sm remove-payment-row" style="background: #dc3545 !important;">حذف</button></div>`;
        $('#payment-rows-container').append(newRow);
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
                    success: function(response) { if (!response.success) { showPuzzlingAlert(puzzling_lang.error_title, response.data.message, 'error'); $(this).sortable('cancel'); } },
                    error: function() { showPuzzlingAlert(puzzling_lang.error_title, puzzling_lang.server_error, 'error'); $(this).sortable('cancel'); },
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
        $.ajax({
            url: puzzlingcrm_ajax_obj.ajax_url, type: 'POST',
            data: { action: 'puzzling_get_task_details', security: puzzling_ajax_nonce, task_id: taskId },
            success: function(response) { if (response.success) { $('#pzl-task-modal-body').html(response.data.html); } else { $('#pzl-task-modal-body').html('<p>خطا در بارگذاری اطلاعات وظیفه.</p>'); } },
            error: function() { $('#pzl-task-modal-body').html('<p>خطای ارتباط با سرور.</p>'); }
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
        $.ajax({
            url: puzzlingcrm_ajax_obj.ajax_url,
            type: 'POST',
            data: { action: 'puzzling_get_notifications', security: puzzling_ajax_nonce },
            success: function(response) {
                if (response.success) {
                    $('.pzl-notification-panel ul').html(response.data.html);
                    var count = parseInt(response.data.count);
                    if (count > 0) {
                        $('.pzl-notification-count').text(count).show();
                    } else {
                        $('.pzl-notification-count').hide();
                    }
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
    fetchNotifications();
    setInterval(fetchNotifications, 120000);

    // --- Canned Response Selector ---
    $('body').on('change', '#canned_response_selector', function() {
        var responseId = $(this).val();
        if (!responseId) return;
        var editor = tinymce.get('comment');
        if (!editor) return;
        editor.setContent('<p><i class="fas fa-spinner fa-spin"></i> در حال بارگذاری...</p>');
        $.ajax({
            url: puzzlingcrm_ajax_obj.ajax_url,
            type: 'POST',
            data: { action: 'puzzling_get_canned_response', security: puzzling_ajax_nonce, response_id: responseId },
            success: function(response) {
                if (response.success) { editor.setContent(response.data.content); }
                else { editor.setContent('<p style="color:red;">خطا در بارگذاری پاسخ.</p>'); }
            },
            error: function() { editor.setContent('<p style="color:red;">خطای سرور.</p>'); }
        });
    });

}); // End of jQuery(document).ready