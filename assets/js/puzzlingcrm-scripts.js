jQuery(document).ready(function($) {
    var puzzling_ajax_nonce = puzzlingcrm_ajax_obj.nonce;
    var puzzling_lang = puzzlingcrm_ajax_obj.lang;
    var currentTaskId = null;

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
    $('#puzzling-add-task-form').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        var formData = new FormData(this);
        
        formData.append('action', 'puzzling_add_task');
        formData.append('security', puzzling_ajax_nonce);

        $.ajax({
            url: puzzlingcrm_ajax_obj.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                form.find('button[type="submit"]').text('در حال افزودن...').prop('disabled', true);
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    var dashboardUrl = new URL(window.location.href);
                    dashboardUrl.searchParams.set('view', 'tasks');
                    dashboardUrl.searchParams.set('tab', 'board');
                    dashboardUrl.searchParams.delete('action');
                    window.location.href = dashboardUrl.href;
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
    if ($('#pzl-task-board').length) {
        $('#pzl-task-board .pzl-task-list').sortable({
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
                        security: puzzling_ajax_nonce,
                        task_id: taskId,
                        new_status_slug: newStatusSlug
                    },
                    success: function(response) {
                        if (!response.success) {
                            alert('خطا در به‌روزرسانی وضعیت.');
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
    }
    
    // --- Quick Add Task Controls ---
    $('.pzl-kanban-board').on('click', '.add-card-btn', function() {
        $(this).hide();
        $(this).siblings('.add-card-form').slideDown(200).find('textarea').focus();
    });

    $('.pzl-kanban-board').on('click', '.cancel-add-card', function() {
        var form = $(this).closest('.add-card-form');
        form.slideUp(200);
        form.siblings('.add-card-btn').show();
        form.find('textarea').val('');
    });

    function submitQuickAddTask(form) {
        var textarea = form.find('textarea');
        var title = textarea.val().trim();
        if (!title) { textarea.focus(); return; }

        var column = form.closest('.pzl-task-column');
        var statusSlug = column.data('status-slug');
        var taskList = column.find('.pzl-task-list');

        var projectId = $('#kanban-project-filter').val();
        var staffId = $('#kanban-staff-filter').val();

        if (!projectId || !staffId) {
            alert('برای استفاده از افزودن سریع، لطفاً ابتدا برد را بر اساس پروژه و کارمند فیلتر کنید.');
            return;
        }

        $.ajax({
            url: puzzlingcrm_ajax_obj.ajax_url,
            type: 'POST',
            data: {
                action: 'puzzling_quick_add_task', security: puzzling_ajax_nonce,
                title: title, status_slug: statusSlug, project_id: projectId, assigned_to: staffId
            },
            beforeSend: function() { form.find('.submit-add-card').prop('disabled', true).text('...'); },
            success: function(response) {
                if (response.success) {
                    taskList.append(response.data.task_html);
                    textarea.val('').focus();
                } else {
                    alert('خطا: ' + (response.data.message || 'خطای ناشناخته'));
                }
            },
            error: function() { alert('یک خطای ارتباطی رخ داد.'); },
            complete: function() { form.find('.submit-add-card').prop('disabled', false).text('افزودن'); }
        });
    }

    $('.pzl-kanban-board').on('click', '.submit-add-card', function() { submitQuickAddTask($(this).closest('.add-card-form')); });
    $('.pzl-kanban-board').on('keydown', '.add-card-form textarea', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); submitQuickAddTask($(this).closest('.add-card-form')); }
    });

    // --- Task Modal ---
    function openTaskModal(taskId) {
        if (!taskId) return;
        currentTaskId = taskId;
        $('#pzl-task-modal-backdrop, #pzl-task-modal-wrap').fadeIn(200);
        $('#pzl-task-modal-body').html('<div class="pzl-loader"></div>');

        $.ajax({
            url: puzzlingcrm_ajax_obj.ajax_url, type: 'POST',
            data: { action: 'puzzling_get_task_details', security: puzzling_ajax_nonce, task_id: taskId },
            success: function(response) {
                if (response.success) { $('#pzl-task-modal-body').html(response.data.html); } 
                else { $('#pzl-task-modal-body').html('<p>خطا در بارگذاری اطلاعات وظیفه.</p>'); }
            },
            error: function() { $('#pzl-task-modal-body').html('<p>خطای ارتباط با سرور.</p>'); }
        });
    }

    function closeTaskModal() {
        currentTaskId = null;
        $('#pzl-task-modal-backdrop, #pzl-task-modal-wrap').fadeOut(200);
        $('#pzl-task-modal-body').html('');
    }

    // Open Modal on card click
    $('#pzl-task-manager-page').on('click', '.pzl-task-card, .open-task-modal', function(e) {
        e.preventDefault();
        openTaskModal($(this).data('task-id'));
    });

    // Close Modal
    $('body').on('click', '#pzl-close-modal-btn, #pzl-task-modal-backdrop', function(e) {
        if ($(e.target).is('#pzl-close-modal-btn') || $(e.target).is('#pzl-task-modal-backdrop')) { closeTaskModal(); }
    });
    
    // **NEW**: Handle opening task directly from URL
    const urlParams = new URLSearchParams(window.location.search);
    const openTaskId = urlParams.get('open_task_id');
    if (openTaskId) {
        openTaskModal(openTaskId);
    }

    // Modal Tabs
    $('body').on('click', '.pzl-modal-tab-link', function(e){
        e.preventDefault();
        var tabId = $(this).data('tab');
        $('.pzl-modal-tab-link').removeClass('active');
        $(this).addClass('active');
        $('.pzl-modal-tab-content').hide();
        $('#' + tabId).show();
    });
    
    // Edit/Save Task Description in Modal
    $('body').on('click', '#pzl-task-description-viewer', function() { $(this).hide(); $('#pzl-task-description-editor').show(); });
    $('body').on('click', '#pzl-cancel-edit-content', function() { $('#pzl-task-description-editor').hide(); $('#pzl-task-description-viewer').show(); });
    $('body').on('click', '#pzl-save-task-content', function() {
        var newContent = $('#pzl-task-content-input').val();
        var button = $(this);
        button.text('در حال ذخیره...').prop('disabled', true);

        $.ajax({
            url: puzzlingcrm_ajax_obj.ajax_url, type: 'POST',
            data: { action: 'puzzling_save_task_content', security: puzzling_ajax_nonce, task_id: currentTaskId, content: newContent },
            success: function(response) {
                if (response.success) {
                    var viewer = $('#pzl-task-description-viewer');
                    viewer.html(response.data.new_content_html || '<p class="pzl-no-content">توضیحاتی ثبت نشده.</p>');
                    $('#pzl-task-description-editor').hide();
                    viewer.show();
                } else { alert('خطا در ذخیره‌سازی.'); }
            },
            error: function() { alert('خطای ارتباط با سرور.'); },
            complete: function() { button.text('ذخیره').prop('disabled', false); }
        });
    });

    // Add Comment
    $('body').on('click', '#pzl-submit-comment', function() {
        var commentText = $('#pzl-new-comment-text').val();
        if (!commentText.trim()) return;
        var button = $(this);
        button.text('در حال ارسال...').prop('disabled', true);
        
        $.ajax({
             url: puzzlingcrm_ajax_obj.ajax_url, type: 'POST',
             data: { action: 'puzzling_add_task_comment', security: puzzling_ajax_nonce, task_id: currentTaskId, comment_text: commentText },
             success: function(response) {
                if (response.success) {
                    $('.pzl-comment-list').append(response.data.comment_html);
                    $('#pzl-new-comment-text').val('');
                } else { alert('خطا در ثبت نظر.'); }
             },
             error: function() { alert('خطای ارتباط با سرور.'); },
             complete: function() { button.text('ارسال نظر').prop('disabled', false); }
        });
    });

    // --- Checklist Management ---
    $('body').on('submit', '#pzl-add-checklist-item-form', function(e){
        e.preventDefault();
        var input = $(this).find('input[type="text"]');
        var text = input.val().trim();
        if(!text) return;

        $.ajax({
            url: puzzlingcrm_ajax_obj.ajax_url, type: 'POST',
            data: { action: 'puzzling_manage_checklist', security: puzzling_ajax_nonce, task_id: currentTaskId, sub_action: 'add', text: text },
            success: function(response) {
                if(response.success){
                    location.reload(); // Simple refresh to show updated list
                }
            }
        });
    });

    $('body').on('change', '.pzl-checklist-item input[type="checkbox"]', function(){
        var itemId = $(this).closest('.pzl-checklist-item').data('item-id');
        $.post(puzzlingcrm_ajax_obj.ajax_url, { action: 'puzzling_manage_checklist', security: puzzling_ajax_nonce, task_id: currentTaskId, sub_action: 'toggle', item_id: itemId });
    });

    $('body').on('click', '.pzl-delete-checklist-item', function(){
        if(!confirm('آیا از حذف این آیتم مطمئن هستید؟')) return;
        var item = $(this).closest('.pzl-checklist-item');
        var itemId = item.data('item-id');
        item.css('opacity', '0.5');

        $.ajax({
            url: puzzlingcrm_ajax_obj.ajax_url, type: 'POST',
            data: { action: 'puzzling_manage_checklist', security: puzzling_ajax_nonce, task_id: currentTaskId, sub_action: 'delete', item_id: itemId },
            success: function(response){ if(response.success) item.remove(); else item.css('opacity', '1'); }
        });
    });

    // --- Time Logging ---
    $('body').on('submit', '#pzl-log-time-form', function(e){
        e.preventDefault();
        var form = $(this);
        var hours = form.find('input[name="hours"]').val();
        var description = form.find('input[name="description"]').val();

        $.ajax({
            url: puzzlingcrm_ajax_obj.ajax_url, type: 'POST',
            data: { action: 'puzzling_log_time', security: puzzling_ajax_nonce, task_id: currentTaskId, hours: hours, description: description },
            success: function(response){
                if(response.success){
                     location.reload(); // Simple refresh to show updated list
                } else {
                    alert('خطا: ' + response.data.message);
                }
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
            url: puzzlingcrm_ajax_obj.ajax_url, type: 'POST',
            data: { action: 'puzzling_delete_project', security: puzzling_ajax_nonce, _wpnonce: nonce, project_id: projectId },
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
                alert('یک خطای ناشناخته رخ داد.');
                projectRow.css('opacity', '1');
            }
        });
    });

    // --- Notification Center ---
    function fetchNotifications() {
        $.ajax({
            url: puzzlingcrm_ajax_obj.ajax_url, type: 'POST',
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
        if (currentCount > 0) { countEl.text(currentCount); } 
        else { countEl.hide(); }
        $.post(puzzlingcrm_ajax_obj.ajax_url, { action: 'puzzling_mark_notification_read', security: puzzling_ajax_nonce, id: id });
    });

    fetchNotifications();
    setInterval(fetchNotifications, 120000);

    // --- Workflow Status Management ---
    if ($('#status-sortable-list').length) {
        $('#status-sortable-list').sortable({
            axis: 'y', placeholder: 'ui-state-highlight',
            stop: function(event, ui) {
                var order = $(this).sortable('toArray', { attribute: 'data-term-id' });
                $.post(puzzlingcrm_ajax_obj.ajax_url, { action: 'puzzling_save_status_order', security: puzzling_ajax_nonce, order: order });
            }
        }).disableSelection();
    }

    $('#add-new-status-form').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        var newName = $('#new-status-name').val().trim();
        if (!newName) return;

        $.ajax({
            url: puzzlingcrm_ajax_obj.ajax_url, type: 'POST',
            data: { action: 'puzzling_add_new_status', security: puzzling_ajax_nonce, name: newName },
            success: function(response) {
                if (response.success) {
                    var newStatusHTML = '<li data-term-id="' + response.data.term_id + '"><i class="fas fa-grip-vertical"></i> ' + response.data.name + ' <span class="delete-status-btn" data-term-id="' + response.data.term_id + '">&times;</span></li>';
                    $('#status-sortable-list').append(newStatusHTML);
                    form.trigger('reset');
                } else { alert('خطا: ' + response.data.message); }
            }
        });
    });

    $('#workflow-status-manager').on('click', '.delete-status-btn', function() {
        if (!confirm('آیا از حذف این وضعیت مطمئن هستید؟ وظایف به ستون "To Do" منتقل خواهند شد.')) return;
        var btn = $(this);
        var termId = btn.data('term-id');
        var listItem = btn.closest('li');

        $.ajax({
            url: puzzlingcrm_ajax_obj.ajax_url, type: 'POST',
            data: { action: 'puzzling_delete_status', security: puzzling_ajax_nonce, term_id: termId },
            success: function(response) {
                if (response.success) { listItem.remove(); } 
                else { alert('خطا: ' + response.data.message); }
            }
        });
    });
});