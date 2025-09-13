jQuery(document).ready(function($) {
    // Get nonce from localized script object for all AJAX requests
    var puzzling_ajax_nonce = puzzlingcrm_ajax_obj.nonce;
    var puzzling_lang = puzzlingcrm_ajax_obj.lang;

    // --- Intelligent Installment Calculation ---
    $('#calculate-installments').on('click', function() {
        var totalAmount = parseFloat($('#total_amount').val().replace(/,/g, ''));
        var totalInstallments = parseInt($('#total_installments').val());
        var intervalDays = parseInt($('#installment_interval').val());
        var startDateStr = $('#start_date').val();

        if (isNaN(totalAmount) || isNaN(totalInstallments) || isNaN(intervalDays) || !startDateStr) {
            alert('لطفاً تمام فیلدهای محاسبه‌گر اقساط را به درستی پر کنید.');
            return;
        }

        var installmentAmount = Math.round(totalAmount / totalInstallments);
        var startDate = new Date(startDateStr);
        
        var previewContainer = $('#installments-preview-container');
        var hiddenContainer = $('#payment-rows-container');
        
        previewContainer.html('<table class="pzl-table"><thead><tr><th>#</th><th>مبلغ قسط (تومان)</th><th>تاریخ سررسید</th></tr></thead><tbody></tbody></table>');
        hiddenContainer.html('');

        var tableBody = previewContainer.find('tbody');

        for (var i = 0; i < totalInstallments; i++) {
            var dueDate = new Date(startDate);
            dueDate.setDate(startDate.getDate() + (i * intervalDays));
            
            var displayDate = dueDate.toLocaleDateString('fa-IR', { year: 'numeric', month: '2-digit', day: '2-digit' });
            var inputDate = dueDate.getFullYear() + '-' + ('0' + (dueDate.getMonth() + 1)).slice(-2) + '-' + ('0' + dueDate.getDate()).slice(-2);
            var formattedAmount = installmentAmount.toLocaleString('en-US');

            tableBody.append(`<tr><td>${i + 1}</td><td>${formattedAmount}</td><td>${displayDate}</td></tr>`);
            hiddenContainer.append(`
                <input type="hidden" name="payment_amount[]" value="${installmentAmount}">
                <input type="hidden" name="payment_due_date[]" value="${inputDate}">
            `);
        }
    });

    // --- AJAX for Task Management ---
    
    // Add New Task
    $('#puzzling-add-task-form').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        var titleInput = form.find('input[name="title"]');
        var title = titleInput.val();
        
        if (!title.trim()) {
            alert('لطفاً عنوان تسک را وارد کنید.');
            return;
        }

        $.ajax({
            url: puzzlingcrm_ajax_obj.ajax_url,
            type: 'POST',
            data: {
                action: 'puzzling_add_task',
                security: puzzling_ajax_nonce,
                title: title,
                priority: form.find('select[name="priority"]').val(),
                due_date: form.find('input[name="due_date"]').val(),
                project_id: form.find('select[name="project_id"]').val(),
                assigned_to: form.find('select[name="assigned_to"]').val() || ''
            },
            beforeSend: function() {
                form.find('button[type="submit"]').text('در حال افزودن...').prop('disabled', true);
            },
            success: function(response) {
                if (response.success) {
                    $('#active-tasks-list').prepend(response.data.task_html);
                    $('#active-tasks-list .no-tasks-message').remove();
                } else {
                    alert('خطا: ' + (response.data.message || 'خطای ناشناخته'));
                }
            },
            error: function() {
                alert('یک خطای ناشناخته در ارتباط با سرور رخ داد.');
            },
            complete: function() {
                form.find('button[type="submit"]').text('افزودن').prop('disabled', false);
                titleInput.val('');
            }
        });
    });

    // Update Task Status
    $('.task-list').on('change', '.task-checkbox', function() {
        var checkbox = $(this);
        var taskItem = checkbox.closest('.task-item');
        var taskId = taskItem.data('task-id');
        var isDone = checkbox.is(':checked');

        $.ajax({
            url: puzzlingcrm_ajax_obj.ajax_url,
            type: 'POST',
            data: {
                action: 'puzzling_update_task_status',
                security: puzzling_ajax_nonce,
                task_id: taskId,
                is_done: isDone
            },
            beforeSend: function() { taskItem.css('opacity', '0.5'); },
            success: function(response) {
                if(response.success) {
                    var targetList = isDone ? '#done-tasks-list' : '#active-tasks-list';
                    taskItem.toggleClass('status-done', isDone);
                    $(targetList).prepend(taskItem);
                } else {
                    alert('خطا در به‌روزرسانی تسک.');
                    checkbox.prop('checked', !isDone);
                }
            },
            error: function() {
                alert('یک خطای ناشناخته در ارتباط با سرور رخ داد.');
                checkbox.prop('checked', !isDone);
            },
            complete: function() { taskItem.css('opacity', '1'); }
        });
    });

    // Delete Task
    $('.task-list').on('click', '.delete-task', function(e) {
        e.preventDefault();
        
        // Use the improved confirmation message from localized object
        if ( !confirm(puzzling_lang.confirm_delete) ) return;

        var link = $(this);
        var taskItem = link.closest('.task-item');
        var taskId = taskItem.data('task-id');

        $.ajax({
            url: puzzlingcrm_ajax_obj.ajax_url,
            type: 'POST',
            data: {
                action: 'puzzling_delete_task',
                security: puzzling_ajax_nonce,
                task_id: taskId
            },
            beforeSend: function() { taskItem.css('opacity', '0.5'); },
            success: function(response) {
                if(response.success) {
                    taskItem.slideUp(function() { $(this).remove(); });
                } else {
                    alert('خطا: ' + (response.data.message || 'خطای ناشناخته'));
                    taskItem.css('opacity', '1');
                }
            },
            error: function() {
                alert('یک خطای ناشناخته در ارتباط با سرور رخ داد.');
                taskItem.css('opacity', '1');
            }
        });
    });

    // --- Notification Center ---
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

    // Toggle notification panel
    $('.pzl-notification-bell').on('click', function(e) {
        e.stopPropagation();
        $('.pzl-notification-panel').toggle();
    });

    // Hide panel when clicking outside
    $(document).on('click', function() {
        $('.pzl-notification-panel').hide();
    });

    // Stop propagation to prevent closing when clicking inside panel
    $('.pzl-notification-panel').on('click', function(e) {
        e.stopPropagation();
    });

    // Mark notification as read
    $('.pzl-notification-panel').on('click', 'li.pzl-unread', function() {
        var notificationItem = $(this);
        var notificationId = notificationItem.data('id');
        
        notificationItem.removeClass('pzl-unread').addClass('pzl-read');
        var countEl = $('.pzl-notification-count');
        var currentCount = parseInt(countEl.text()) - 1;
        if (currentCount > 0) {
            countEl.text(currentCount);
        } else {
            countEl.hide();
        }

        $.post(puzzlingcrm_ajax_obj.ajax_url, {
            action: 'puzzling_mark_notification_read',
            security: puzzling_ajax_nonce,
            id: notificationId
        });
    });

    // Initial fetch and periodic check every 2 minutes
    fetchNotifications();
    setInterval(fetchNotifications, 120000);
});