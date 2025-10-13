/**
 * PuzzlingCRM Main Scripts - V4.7 (Fully Patched Version)
 * - Fixes the "processing..." bug for all forms (add, edit, delete).
 * - Ensures correct reload or redirect behavior after successful operations.
 * - Simplifies and unifies the AJAX response handling logic.
 * - Fixes the critical bug where a missing persianDatepicker library would halt all subsequent scripts.
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
                    setTimeout(() => {
                        showPuzzlingAlert('موفق', data.message, 'success', data);
                    }, 250);
                } else {
                    let errorMessage = (response && response.data && response.data.message) ? response.data.message : (puzzlingcrm_ajax_obj.lang.server_error || 'خطای سرور');
                    showPuzzlingAlert(puzzlingcrm_ajax_obj.lang.error_title || 'خطا', errorMessage, 'error');
                    submitButton.html(originalButtonHtml).prop('disabled', false); // Re-enable on failure
                }
            },
            error: function(xhr) {
                console.error("PuzzlingCRM AJAX Error:", xhr.responseText);
                showPuzzlingAlert(puzzlingcrm_ajax_obj.lang.error_title || 'خطا', 'یک خطای ناشناخته در ارتباط با سرور رخ داد.', 'error');
                submitButton.html(originalButtonHtml).prop('disabled', false); // Re-enable on error
            }
        });
    }

    // --- Attach form submission handler ---
    $(document).on('submit', 'form.pzl-ajax-form', function(e) {
        e.preventDefault();
        handleAjaxFormSubmit($(this));
    });

    // --- Delete Lead Handler ---
    $(document).on('click', '.pzl-delete-lead-btn', function(e) {
        e.preventDefault();
        const button = $(this);
        const leadRow = button.closest('tr');
        const leadId = leadRow.data('lead-id');
        const nonce = button.data('nonce');

        Swal.fire({
            title: 'آیا از حذف این سرنخ مطمئن هستید؟',
            text: "این عمل غیرقابل بازگشت است!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'بله، حذف کن!',
            cancelButtonText: 'انصراف'
        }).then((result) => {
            if (result.isConfirmed) {
                leadRow.css('opacity', '0.5');
                $.ajax({
                    url: puzzlingcrm_ajax_obj.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'puzzling_delete_lead',
                        security: nonce,
                        lead_id: leadId
                    },
                    success: function(response) {
                        if (response.success) {
                            showPuzzlingAlert('موفق', response.data.message, 'success', response.data);
                        } else {
                            showPuzzlingAlert('خطا', response.data.message, 'error');
                            leadRow.css('opacity', '1');
                        }
                    },
                    error: function() {
                        showPuzzlingAlert('خطا', 'یک خطای ناشناخته در ارتباط با سرور رخ داد.', 'error');
                        leadRow.css('opacity', '1');
                    }
                });
            }
        });
    });


    // --- All other original functions below ---

    if ($('#manage-contract-form').length) {
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
                    if (parts.length === 3) {
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
                    security: nonce,
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
        var intervalDays = parseInt($('#installment_interval').val(), 10);
        var startDateStr = $('#start_date').val();

        if (!totalAmount || !totalInstallments || isNaN(intervalDays) || !startDateStr) {
            showPuzzlingAlert('خطا', 'لطفاً تمام فیلدهای محاسبه‌گر را پر کنید.', 'error');
            return;
        }

        if (typeof persianDate === 'undefined') {
            showPuzzlingAlert('خطا', 'کتابخانه تقویم بارگذاری نشده است.', 'error');
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

        if ($.fn.persianDatepicker) {
            $('.pzl-jalali-date-picker:not(.pwt-datepicker-input-element)').persianDatepicker({
                format: 'YYYY/MM/DD',
                autoClose: true
            });
        }
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

    // **CRITICAL FIX**: Safely initialize persianDatepicker
    // This prevents a critical error if the datepicker script is not loaded on a page,
    // which would otherwise halt all subsequent JavaScript execution (like the delete button handler).
    if ($('.pzl-jalali-date-picker').length > 0 && typeof $.fn.persianDatepicker === 'function') {
        $('.pzl-jalali-date-picker').persianDatepicker({
            format: 'YYYY/MM/DD',
            autoClose: true
        });
    }

}); // End of jQuery(document).ready