jQuery(document).ready(function($) {
    var puzzling_ajax_nonce = puzzlingcrm_ajax_obj.nonce;
    var puzzling_lang = puzzlingcrm_ajax_obj.lang;
    var currentTaskId = null; // To keep track of the open task in modal

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
            data: form.serialize() + '&action=puzzling_add_task&security=' + puzzling_ajax_nonce,
            beforeSend: function() {
                form.find('button[type="submit"]').text('در حال افزودن...').prop('disabled', true);
            },
            success: function(response) {
                if (response.success) {
                    // Add the new card to the first column ('to-do')
                    $('.pzl-task-column[data-status-slug="to-do"] .pzl-task-list').append(response.data.task_html);
                    form.trigger('reset'); // Reset the form
                    // Optionally, switch to the board view if not already there
                    // window.location.href = removeURLParameter(window.location.href, 'tab');
                } else {
                    alert('خطا: ' + (response.data.message || 'خطای ناشناخته'));
                }
            },
            error: function() {
                alert('یک خطای ناشناخته در ارتباط با سرور رخ داد.');
            },
            complete: function() {
                form.find('button[type="submit"]').text('افزودن وظیفه').prop('disabled', false);
            }
        });
    });

    // --- Kanban Board: Drag and Drop ---
    $('.pzl-task-list').sortable({
        connectWith: '.pzl-task-list',
        placeholder: 'pzl-task-card-placeholder',
        start: function(event, ui) {
            ui.placeholder.height(ui.item.outerHeight());
        },
        stop: function(event, ui) {
            var taskCard = ui.item;
            var taskId = taskCard.data('task-id');
            var newStatusSlug = taskCard.closest('.pzl-task-column').data('status-slug');

            taskCard.css('opacity', '0.5'); // Visual feedback

            $.ajax({
                url: puzzlingcrm_ajax_obj.ajax_url,
                type: 'POST',
                data: {
                    action: 'puzzling_update_task_status',
                    security: puzzling_ajax_nonce,
                    task_id: taskId,
                    new_status_slug: newStatusSlug
                },
                success: function(response) {
                    if (!response.success) {
                        alert('خطا در به‌روزرسانی وضعیت. لطفا صفحه را رفرش کنید.');
                        // Revert the change visually if API call fails
                        $(this).sortable('cancel');
                    }
                },
                error: function() {
                     alert('خطای ارتباط با سرور.');
                     $(this).sortable('cancel');
                },
                complete: function() {
                     taskCard.css('opacity', '1');
                }
            });
        }
    }).disableSelection();
    
    // --- Task Modal ---

    function openTaskModal(taskId) {
        currentTaskId = taskId;
        $('#pzl-task-modal-backdrop, #pzl-task-modal-wrap').fadeIn(200);
        $('#pzl-task-modal-body').html('<div class="pzl-loader"></div>');

        $.ajax({
            url: puzzlingcrm_ajax_obj.ajax_url,
            type: 'POST',
            data: {
                action: 'puzzling_get_task_details',
                security: puzzling_ajax_nonce,
                task_id: taskId
            },
            success: function(response) {
                if (response.success) {
                    $('#pzl-task-modal-body').html(response.data.html);
                } else {
                    $('#pzl-task-modal-body').html('<p>خطا در بارگذاری اطلاعات وظیفه.</p>');
                }
            },
            error: function() {
                 $('#pzl-task-modal-body').html('<p>خطای ارتباط با سرور.</p>');
            }
        });
    }

    function closeTaskModal() {
        currentTaskId = null;
        $('#pzl-task-modal-backdrop, #pzl-task-modal-wrap').fadeOut(200);
        $('#pzl-task-modal-body').html('');
    }

    // Open Modal on card click
    $('#pzl-task-board, .pzl-table').on('click', '.pzl-task-card, .open-task-modal', function(e) {
        e.preventDefault();
        var taskId = $(this).data('task-id');
        openTaskModal(taskId);
    });

    // Close Modal
    $('#pzl-close-modal-btn, #pzl-task-modal-backdrop').on('click', closeTaskModal);

    // Edit/Save Task Description in Modal
    $('body').on('click', '#pzl-task-description-viewer', function() {
        $(this).hide();
        $('#pzl-task-description-editor').show();
    });
     $('body').on('click', '#pzl-cancel-edit-content', function() {
        $('#pzl-task-description-editor').hide();
        $('#pzl-task-description-viewer').show();
    });
    $('body').on('click', '#pzl-save-task-content', function() {
        var newContent = $('#pzl-task-content-input').val();
        var button = $(this);
        button.text('در حال ذخیره...').prop('disabled', true);

        $.ajax({
            url: puzzlingcrm_ajax_obj.ajax_url,
            type: 'POST',
            data: {
                action: 'puzzling_save_task_content',
                security: puzzling_ajax_nonce,
                task_id: currentTaskId,
                content: newContent
            },
            success: function(response) {
                if (response.success) {
                    var viewer = $('#pzl-task-description-viewer');
                    viewer.html(response.data.new_content_html || '<p class="pzl-no-content">توضیحاتی برای این وظیفه ثبت نشده است. برای افزودن کلیک کنید.</p>');
                    $('#pzl-task-description-editor').hide();
                    viewer.show();
                } else {
                    alert('خطا در ذخیره‌سازی.');
                }
            },
             error: function() {
                alert('خطای ارتباط با سرور.');
             },
             complete: function() {
                button.text('ذخیره').prop('disabled', false);
             }
        });
    });

    // Add Comment
    $('body').on('click', '#pzl-submit-comment', function() {
        var commentText = $('#pzl-new-comment-text').val();
        if (!commentText.trim()) return;
        var button = $(this);
        button.text('در حال ارسال...').prop('disabled', true);
        
        $.ajax({
             url: puzzlingcrm_ajax_obj.ajax_url,
             type: 'POST',
             data: {
                action: 'puzzling_add_task_comment',
                security: puzzling_ajax_nonce,
                task_id: currentTaskId,
                comment_text: commentText
             },
             success: function(response) {
                if (response.success) {
                    $('.pzl-comment-list').append(response.data.comment_html);
                    $('#pzl-new-comment-text').val('');
                } else {
                    alert('خطا در ثبت نظر.');
                }
             },
             error: function() {
                alert('خطای ارتباط با سرور.');
             },
             complete: function() {
                button.text('ارسال نظر').prop('disabled', false);
             }
        });
    });


    // --- Project Deletion ---
    $('#projects-table').on('click', '.delete-project', function(e) {
        e.preventDefault();
        
        if ( !confirm(puzzling_lang.confirm_delete_project) ) return;

        var link = $(this);
        var projectRow = link.closest('tr');
        var projectId = link.data('project-id');
        var nonce = link.data('nonce');

        $.ajax({
            url: puzzlingcrm_ajax_obj.ajax_url,
            type: 'POST',
            data: {
                action: 'puzzling_delete_project',
                security: puzzling_ajax_nonce, // Main AJAX nonce
                _wpnonce: nonce, // Specific action nonce
                project_id: projectId
            },
            beforeSend: function() { projectRow.css('opacity', '0.5'); },
            success: function(response) {
                if(response.success) {
                    projectRow.slideUp(function() { $(this).remove(); });
                } else {
                    alert('خطا: ' + (response.data.message || 'خطای ناشناخته'));
                    projectRow.css('opacity', '1');
                }
            },
            error: function() {
                alert('یک خطای ناشناخته در ارتباط با سرور رخ داد.');
                projectRow.css('opacity', '1');
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