jQuery(document).ready(function($) {
    // --- Payment Row Management (from original file) ---
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
                security: form.find('#security').val(),
                title: title,
                priority: form.find('#task_priority').val(),
                due_date: form.find('#task_due_date').val()
            },
            beforeSend: function() {
                form.find('button[type="submit"]').text('در حال افزودن...').prop('disabled', true);
            },
            success: function(response) {
                if (response.success) {
                    // Reload the page to see the new task. A better way would be to append the new task via JS.
                    location.reload();
                } else {
                    alert('خطا: ' + response.data.message);
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

    // 2. Update Task Status (Mark as Done/Undone)
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
                security: $('#puzzling-add-task-form #security').val(), // Use the nonce from the form
                task_id: taskId,
                is_done: isDone
            },
            beforeSend: function() {
                taskItem.css('opacity', '0.5');
            },
            success: function(response) {
                if(response.success) {
                    // Move the task item to the correct list
                    if (isDone) {
                        taskItem.addClass('status-done');
                        $('#done-tasks-list').prepend(taskItem);
                    } else {
                        taskItem.removeClass('status-done');
                        $('#active-tasks-list').prepend(taskItem);
                    }
                } else {
                    alert('خطا در به‌روزرسانی تسک.');
                    // Revert checkbox state
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
});