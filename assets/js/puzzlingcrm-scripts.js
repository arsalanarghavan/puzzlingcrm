jQuery(document).ready(function($) {
    // --- **NEW: Store nonce globally for all AJAX requests** ---
    var puzzling_ajax_nonce = puzzlingcrm_ajax_obj.nonce;

    // --- Payment Row Management ---
    $('#add-payment-row').on('click', function() {
        var rowHtml = `
            <div class="payment-row form-group" style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                <input type="number" name="payment_amount[]" placeholder="مبلغ (تومان)" style="flex-grow: 1; padding: 8px;" required>
                <input type="date" name="payment_due_date[]" style="padding: 8px;" required>
                <button type="button" class="button remove-payment-row">حذف</button>
            </div>
        `;
        $('#payment-rows-container').append(rowHtml);
    });

    $('#payment-rows-container').on('click', '.remove-payment-row', function() {
        $(this).closest('.payment-row').remove();
    });

    // --- AJAX for Task Management ---
    
    // 1. Add New Task
    $('#puzzling-add-task-form').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        var titleInput = form.find('#task_title');
        var title = titleInput.val();
        
        if ( !title.trim() ) {
            alert('لطفاً عنوان تسک را وارد کنید.');
            return;
        }

        $.ajax({
            url: puzzlingcrm_ajax_obj.ajax_url,
            type: 'POST',
            data: {
                action: 'puzzling_add_task',
                security: puzzling_ajax_nonce, // <-- **FIXED: Using global nonce**
                title: title,
                priority: form.find('#task_priority').val(),
                due_date: form.find('#task_due_date').val(),
                project_id: form.find('#task_project').val(), // <-- Added project ID
                assigned_to: form.find('select[name="assigned_to"]').val() || '' // <-- Added assigned_to
            },
            beforeSend: function() {
                form.find('button[type="submit"]').text('در حال افزودن...').prop('disabled', true);
            },
            success: function(response) {
                if (response.success) {
                    $('#active-tasks-list').prepend(response.data.task_html);
                    $('.no-tasks-message').hide();
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

    // 2. Update Task Status
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
                security: puzzling_ajax_nonce, // <-- **FIXED: Using global nonce**
                task_id: taskId,
                is_done: isDone
            },
            beforeSend: function() {
                taskItem.css('opacity', '0.5');
            },
            success: function(response) {
                if(response.success) {
                    if (isDone) {
                        taskItem.addClass('status-done');
                        $('#done-tasks-list').prepend(taskItem);
                    } else {
                        taskItem.removeClass('status-done');
                        $('#active-tasks-list').prepend(taskItem);
                    }
                } else {
                    alert('خطا در به‌روزرسانی تسک.');
                    checkbox.prop('checked', !isDone);
                }
            },
            error: function() {
                alert('یک خطای ناشناخته در ارتباط با سرور رخ داد.');
                checkbox.prop('checked', !isDone);
            },
            complete: function() {
                taskItem.css('opacity', '1');
            }
        });
    });

    // 3. Delete Task
    $('.task-list').on('click', '.delete-task', function(e) {
        e.preventDefault();
        
        if ( ! confirm('آیا از حذف این تسک مطمئن هستید؟') ) {
            return;
        }

        var link = $(this);
        var taskItem = link.closest('.task-item');
        var taskId = taskItem.data('task-id');

        $.ajax({
            url: puzzlingcrm_ajax_obj.ajax_url,
            type: 'POST',
            data: {
                action: 'puzzling_delete_task',
                security: puzzling_ajax_nonce, // <-- **FIXED: Using global nonce**
                task_id: taskId
            },
            beforeSend: function() {
                taskItem.css('opacity', '0.5');
            },
            success: function(response) {
                if(response.success) {
                    taskItem.slideUp(function() {
                        $(this).remove();
                    });
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
});