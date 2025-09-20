jQuery(document).ready(function($) {
    // --- Global Variables ---
    var puzzling_ajax_nonce = puzzlingcrm_ajax_obj.nonce;
    var puzzling_lang = puzzlingcrm_ajax_obj.lang;
    var currentTaskId = null;
    var searchTimer; // Timer for live search delay

    /**
     * SweetAlert Integration for Notifications - V2 (Corrected Redirect Logic)
     * A helper function to show consistent pop-up messages.
     * @param {string} title The title of the alert.
     * @param {string} text The main message of the alert.
     * @param {string} icon 'success', 'error', 'warning', 'info'.
     * @param {boolean} reloadPage If true, the page will reload to a clean URL.
     */
    function showPuzzlingAlert(title, text, icon, reloadPage = false) {
        if (typeof Swal === 'undefined') {
            alert(title + "\n" + text);
            if (reloadPage) {
                window.location.reload();
            }
            return;
        }

        Swal.fire({
            title: title,
            text: text,
            icon: icon,
            confirmButtonText: puzzling_lang.ok_button || 'باشه',
            timer: reloadPage ? 2000 : 4000,
            timerProgressBar: true
        }).then(() => {
            if (reloadPage) {
                // **FIXED REDIRECT LOGIC**
                // Create a URL object from the current location
                let currentUrl = new URL(window.location.href);
                
                // Get the search parameters
                let params = currentUrl.searchParams;
                
                // List of parameters to remove after an action
                let paramsToRemove = ['puzzling_notice', '_wpnonce', 'deleted', 'updated'];
                
                // Remove the unwanted parameters
                paramsToRemove.forEach(param => params.delete(param));
                
                // Re-assign the cleaned search parameters to the URL
                currentUrl.search = params.toString();
                
                // Redirect to the clean URL
                window.location.href = currentUrl.toString();
            }
        });
    }

    /**
     * Generic AJAX Form Submission Handler
     * Intercepts form submissions with '.pzl-ajax-form', sends data via AJAX, and shows a notice.
     * @param {jQuery} form The form element being submitted.
     */
    function handleAjaxFormSubmit(form) {
        var submitButton = form.find('button[type="submit"]');
        var originalButtonHtml = submitButton.html();
        var formData = new FormData(form[0]);
        
        var action = form.data('action') || form.find('input[name="action"]').val();
        formData.append('action', action);
        
        var nonce = form.find('input[name="security"]').val();
        formData.append('security', nonce);

        form.find('.wp-editor-area').each(function() {
            var editorId = $(this).attr('id');
            if (typeof tinymce !== 'undefined' && tinymce.get(editorId)) {
                formData.set($(this).attr('name'), tinymce.get(editorId).getContent());
            }
        });

        // Ensure disabled fields are included in submission
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
                } else {
                    showPuzzlingAlert(puzzling_lang.error_title, response.data.message, 'error');
                }
            },
            error: function() {
                showPuzzlingAlert(puzzling_lang.error_title, puzzling_lang.server_error, 'error');
            },
            complete: function(xhr) {
                 var response = xhr.responseJSON;
                if (!(response && response.success && response.data.reload)) {
                    submitButton.html(originalButtonHtml).prop('disabled', false);
                }
            }
        });
    }

    // Bind the generic handler to all forms with the 'pzl-ajax-form' class
    $('body').on('submit', 'form.pzl-ajax-form', function(e) {
        e.preventDefault();
        handleAjaxFormSubmit($(this));
    });

    // --- Live User Search ---
    $('#user-live-search-input').on('keyup', function() {
        var searchInput = $(this);
        var query = searchInput.val();
        var resultsContainer = $('#pzl-users-table-body');

        clearTimeout(searchTimer); // Clear previous timer

        searchTimer = setTimeout(function() {
            $.ajax({
                url: puzzlingcrm_ajax_obj.ajax_url,
                type: 'POST',
                data: {
                    action: 'puzzling_search_users',
                    security: puzzling_ajax_nonce,
                    query: query
                },
                beforeSend: function() {
                    resultsContainer.html('<tr><td colspan="5"><div class="pzl-loader" style="margin: 20px auto; width: 30px; height: 30px;"></div></td></tr>');
                },
                success: function(response) {
                    if (response.success) {
                        resultsContainer.html(response.data.html);
                    } else {
                        resultsContainer.html('<tr><td colspan="5">خطا در جستجو.</td></tr>');
                    }
                },
                error: function() {
                    resultsContainer.html('<tr><td colspan="5">خطای سرور.</td></tr>');
                }
            });
        }, 500); // Wait 500ms after user stops typing
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
        var startDate = new Date(startDateStr);
        
        $('#payment-rows-container').html('');

        for (var i = 0; i < totalInstallments; i++) {
            var dueDate = new Date(startDate);
            dueDate.setDate(startDate.getDate() + (i * intervalDays));
            var inputDate = dueDate.getFullYear() + '-' + ('0' + (dueDate.getMonth() + 1)).slice(-2) + '-' + ('0' + dueDate.getDate()).slice(-2);
            
            addInstallmentRow(installmentAmount, inputDate, 'pending');
        }
    });

    // --- Manual Installment Management ---
    function addInstallmentRow(amount = '', date = '', status = 'pending') {
        var newRow = `
            <div class="payment-row form-group" style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                <input type="number" name="payment_amount[]" placeholder="مبلغ (تومان)" value="${amount}" style="flex-grow: 1;" required>
                <input type="date" name="payment_due_date[]" value="${date}" required>
                <select name="payment_status[]">
                    <option value="pending" ${status === 'pending' ? 'selected' : ''}>در انتظار پرداخت</option>
                    <option value="paid" ${status === 'paid' ? 'selected' : ''}>پرداخت شده</option>
                </select>
                <button type="button" class="pzl-button pzl-button-sm remove-payment-row" style="background: #dc3545 !important;">حذف</button>
            </div>
        `;
        $('#payment-rows-container').append(newRow);
    }

    $('#add-payment-row').on('click', function() { addInstallmentRow(); });
    $('#payment-rows-container').on('click', '.remove-payment-row', function() { $(this).closest('.payment-row').remove(); });

    // --- Kanban Board: Drag and Drop ---
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
                    url: puzzlingcrm_ajax_obj.ajax_url, type: 'POST',
                    data: { action: 'puzzling_update_task_status', security: puzzling_ajax_nonce, task_id: taskId, new_status_slug: newStatusSlug },
                    success: function(response) {
                        if (!response.success) {
                            showPuzzlingAlert(puzzling_lang.error_title, response.data.message, 'error');
                            $(this).sortable('cancel');
                        }
                    },
                    error: function() {
                        showPuzzlingAlert(puzzling_lang.error_title, puzzling_lang.server_error, 'error');
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

    $('body').on('click', '.pzl-task-card, .open-task-modal', function(e) {
        if ($(e.target).is('select, a, button') || $(e.target).closest('a, button').length) {
            return;
        }
        e.preventDefault();
        openTaskModal($(this).data('task-id'));
    });
    
    $('body').on('click', '#pzl-close-modal-btn, #pzl-task-modal-backdrop', function(e) {
        if ($(e.target).is('#pzl-close-modal-btn') || $(e.target).is('#pzl-task-modal-backdrop')) { closeTaskModal(); }
    });
    
    const urlParamsInstance = new URLSearchParams(window.location.search);
    const openTaskIdFromUrl = urlParamsInstance.get('open_task_id');
    if (openTaskIdFromUrl) {
        openTaskModal(openTaskIdFromUrl);
    }
    
    // --- Task Modal Status Changer ---
    $('body').on('change', '#pzl-task-status-changer', function() {
        var select = $(this);
        var taskId = select.data('task-id');
        var newStatusSlug = select.val();

        select.prop('disabled', true);

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
                if (response.success) {
                    showPuzzlingAlert('موفق', 'وضعیت وظیفه با موفقیت به‌روزرسانی شد.', 'success', true);
                } else {
                    showPuzzlingAlert(puzzling_lang.error_title, response.data.message, 'error');
                }
            },
            error: function() {
                showPuzzlingAlert(puzzling_lang.error_title, puzzling_lang.server_error, 'error');
            },
            complete: function() {
                select.prop('disabled', false);
            }
        });
    });

    // --- Calendar and Gantt Initialization (Localized) ---
    var calendarEl = document.getElementById('pzl-task-calendar');
    if (calendarEl && typeof FullCalendar !== 'undefined') {
        var calendar = new FullCalendar.Calendar(calendarEl, {
            locale: 'fa',
            direction: 'rtl',
            firstDay: 6, // 6 for Saturday
            buttonText: { today: 'امروز', month: 'ماه', week: 'هفته', list: 'لیست' },
            headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,listWeek' },
            events: function(fetchInfo, successCallback, failureCallback) {
                $.ajax({
                    url: puzzlingcrm_ajax_obj.ajax_url,
                    type: 'POST',
                    data: { action: 'get_tasks_for_views', security: puzzling_ajax_nonce },
                    success: function(response) {
                        if (response.success) successCallback(response.data.calendar_events);
                        else failureCallback('Error fetching tasks');
                    },
                    error: function() { failureCallback('Server error'); }
                });
            },
            eventClick: function(info) {
                info.jsEvent.preventDefault();
                if (info.event.id) { openTaskModal(info.event.id); }
            }
        });
        calendar.render();
    }

    var ganttEl = document.getElementById('pzl-task-gantt');
    if (ganttEl && typeof gantt !== 'undefined') {
        gantt.i18n.setLocale({
            date: {
                month_full: ["فروردین", "اردیبهشت", "خرداد", "تیر", "مرداد", "شهریور", "مهر", "آبان", "آذر", "دی", "بهمن", "اسفند"],
                month_short: ["فرو", "اردی", "خرد", "تیر", "مرد", "شهری", "مهر", "آبا", "آذر", "دی", "بهم", "اسف"],
                day_full: ["یکشنبه", "دوشنبه", "سه‌شنبه", "چهارشنبه", "پنجشنبه", "جمعه", "شنبه"],
                day_short: ["ی", "د", "س", "چ", "پ", "ج", "ش"]
            },
            labels: {
                new_task: "وظیفه جدید", icon_save: "ذخیره", icon_cancel: "انصراف", icon_details: "جزئیات", icon_edit: "ویرایش",
                icon_delete: "حذف", confirm_closing: "", confirm_deleting: "وظیفه حذف خواهد شد، آیا مطمئن هستید؟",
                section_description: "توضیحات", section_time: "بازه زمانی", section_type: "نوع", column_text: "نام وظیفه",
                column_start_date: "تاریخ شروع", column_duration: "مدت", column_add: "", link: "پیوند",
                confirm_link_deleting: "حذف خواهد شد، آیا مطمئن هستید؟", link_start: " (شروع)", link_end: " (پایان)",
                type_task: "وظیفه", type_project: "پروژه", type_milestone: "مایل‌استون", minutes: "دقیقه", hours: "ساعت",
                days: "روز", weeks: "هفته", months: "ماه", years: "سال"
            }
        });
        gantt.config.rtl = true;
        gantt.config.date_format = "%Y-%m-%d";
        gantt.config.order_branch = true;
        gantt.config.order_branch_free = true;
        gantt.init(ganttEl);
        
        $.ajax({
            url: puzzlingcrm_ajax_obj.ajax_url,
            type: 'POST',
            data: { action: 'get_tasks_for_views', security: puzzling_ajax_nonce },
            success: function(response) {
                if (response.success) { gantt.parse(response.data.gantt_tasks); }
            }
        });
    }

    // --- Quick Add Task Controls ---
    $('.pzl-task-board-container').on('click', '.add-card-btn', function() { $(this).hide(); $(this).siblings('.add-card-form').slideDown(200).find('textarea').focus(); });
    $('.pzl-task-board-container').on('click', '.cancel-add-card', function() { var form = $(this).closest('.add-card-form'); form.slideUp(200); form.siblings('.add-card-btn').show(); form.find('textarea').val(''); });
    function submitQuickAddTask(form) { var textarea = form.find('textarea'); var title = textarea.val().trim(); if (!title) { textarea.focus(); return; } var column = form.closest('.pzl-task-column'); var statusSlug = column.data('status-slug'); var taskList = column.find('.pzl-task-list'); var projectId = new URLSearchParams(window.location.search).get('project_filter') || 0; var staffId = new URLSearchParams(window.location.search).get('staff_filter') || 0; if (!projectId || !staffId) { showPuzzlingAlert(puzzling_lang.info_title, 'برای استفاده از افزودن سریع، لطفاً ابتدا برد را بر اساس پروژه و کارمند فیلتر کنید.', 'info'); return; } $.ajax({ url: puzzlingcrm_ajax_obj.ajax_url, type: 'POST', data: { action: 'puzzling_quick_add_task', security: puzzling_ajax_nonce, title: title, status_slug: statusSlug, project_id: projectId, assigned_to: staffId }, beforeSend: function() { form.find('.submit-add-card').prop('disabled', true).text('...'); }, success: function(response) { if (response.success) { taskList.append(response.data.task_html); textarea.val('').focus(); } else { showPuzzlingAlert(puzzling_lang.error_title, response.data.message || 'خطای ناشناخته', 'error'); } }, error: function() { showPuzzlingAlert(puzzling_lang.error_title, 'یک خطای ارتباطی رخ داد.', 'error'); }, complete: function() { form.find('.submit-add-card').prop('disabled', false).text('افزودن'); } }); }
    $('.pzl-task-board-container').on('click', '.submit-add-card', function() { submitQuickAddTask($(this).closest('.add-card-form')); });
    $('.pzl-task-board-container').on('keydown', '.add-card-form textarea', function(e) { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); submitQuickAddTask($(this).closest('.add-card-form')); } });
    
    // --- Modal Tabs & Content ---
    $('body').on('click', '.pzl-modal-tab-link', function(e){ e.preventDefault(); var tabId = $(this).data('tab'); $('.pzl-modal-tab-link').removeClass('active'); $(this).addClass('active'); $('.pzl-modal-tab-content').hide(); $('#' + tabId).show(); });
    $('body').on('click', '#pzl-task-description-viewer', function() { $(this).hide(); $('#pzl-task-description-editor').show(); });
    $('body').on('click', '#pzl-cancel-edit-content', function() { $('#pzl-task-description-editor').hide(); $('#pzl-task-description-viewer').show(); });
    $('body').on('click', '#pzl-save-task-content', function() { var newContent = $('#pzl-task-content-input').val(); var button = $(this); button.text('در حال ذخیره...').prop('disabled', true); $.ajax({ url: puzzlingcrm_ajax_obj.ajax_url, type: 'POST', data: { action: 'puzzling_save_task_content', security: puzzling_ajax_nonce, task_id: currentTaskId, content: newContent }, success: function(response) { if (response.success) { var viewer = $('#pzl-task-description-viewer'); viewer.html(response.data.new_content_html || '<p class="pzl-no-content">توضیحاتی ثبت نشده.</p>'); $('#pzl-task-description-editor').hide(); viewer.show(); } else { showPuzzlingAlert(puzzling_lang.error_title, 'خطا در ذخیره‌سازی.', 'error'); } }, error: function() { showPuzzlingAlert(puzzling_lang.error_title, puzzling_lang.server_error, 'error'); }, complete: function() { button.text('ذخیره').prop('disabled', false); } }); });
    $('body').on('click', '#pzl-submit-comment', function() { var commentText = $('#pzl-new-comment-text').val(); if (!commentText.trim()) return; var button = $(this); button.text('در حال ارسال...').prop('disabled', true); $.ajax({ url: puzzlingcrm_ajax_obj.ajax_url, type: 'POST', data: { action: 'puzzling_add_task_comment', security: puzzling_ajax_nonce, task_id: currentTaskId, comment_text: commentText }, success: function(response) { if (response.success) { $('.pzl-comment-list').append(response.data.comment_html); $('#pzl-new-comment-text').val(''); } else { showPuzzlingAlert(puzzling_lang.error_title, 'خطا در ثبت نظر.', 'error'); } }, error: function() { showPuzzlingAlert(puzzling_lang.error_title, puzzling_lang.server_error, 'error'); }, complete: function() { button.text('ارسال نظر').prop('disabled', false); } }); });
    
    // --- Checklist, Time Log, etc. ---
    $('body').on('submit', '#pzl-add-checklist-item-form', function(e){ e.preventDefault(); var input = $(this).find('input[type="text"]'); var text = input.val().trim(); if(!text) return; $.ajax({ url: puzzlingcrm_ajax_obj.ajax_url, type: 'POST', data: { action: 'puzzling_manage_checklist', security: puzzling_ajax_nonce, task_id: currentTaskId, sub_action: 'add', text: text }, success: function(response){ if(response.success){ openTaskModal(currentTaskId); } } }); });
    $('body').on('change', '.pzl-checklist-item input[type="checkbox"]', function(){ var itemId = $(this).closest('.pzl-checklist-item').data('item-id'); $.post(puzzlingcrm_ajax_obj.ajax_url, { action: 'puzzling_manage_checklist', security: puzzling_ajax_nonce, task_id: currentTaskId, sub_action: 'toggle', item_id: itemId }); });
    $('body').on('click', '.pzl-delete-checklist-item', function(){ if(!confirm('آیا از حذف این آیتم مطمئن هستید؟')) return; var item = $(this).closest('.pzl-checklist-item'); var itemId = item.data('item-id'); item.css('opacity', '0.5'); $.ajax({ url: puzzlingcrm_ajax_obj.ajax_url, type: 'POST', data: { action: 'puzzling_manage_checklist', security: puzzling_ajax_nonce, task_id: currentTaskId, sub_action: 'delete', item_id: itemId }, success: function(response){ if(response.success) item.remove(); else item.css('opacity', '1'); } }); });
    $('body').on('submit', '#pzl-log-time-form', function(e){ e.preventDefault(); var form = $(this); var hours = form.find('input[name="hours"]').val(); var description = form.find('input[name="description"]').val(); $.ajax({ url: puzzlingcrm_ajax_obj.ajax_url, type: 'POST', data: { action: 'puzzling_log_time', security: puzzling_ajax_nonce, task_id: currentTaskId, hours: hours, description: description }, success: function(response){ if(response.success){ openTaskModal(currentTaskId); } else { showPuzzlingAlert(puzzling_lang.error_title, response.data.message, 'error'); } } }); });
    
    // --- Project Deletion ---
    $('.pzl-projects-manager-wrapper').on('click', '.delete-project', function(e) { e.preventDefault(); var link = $(this); var projectId = link.data('project-id'); var nonce = link.data('nonce'); Swal.fire({ title: puzzling_lang.confirm_title, text: puzzling_lang.confirm_delete_project, icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#3085d6', confirmButtonText: puzzling_lang.confirm_button, cancelButtonText: puzzling_lang.cancel_button }).then((result) => { if (result.isConfirmed) { var projectCard = link.closest('.pzl-project-card-item'); $.ajax({ url: puzzlingcrm_ajax_obj.ajax_url, type: 'POST', data: { action: 'puzzling_delete_project', security: puzzling_ajax_nonce, _wpnonce: nonce, project_id: projectId }, beforeSend: function() { projectCard.css('opacity', '0.5'); }, success: function(response) { if (response.success) { projectCard.slideUp(function() { $(this).remove(); }); showPuzzlingAlert(puzzling_lang.success_title, response.data.message, 'success'); } else { showPuzzlingAlert(puzzling_lang.error_title, response.data.message || 'خطای ناشناخته', 'error'); projectCard.css('opacity', '1'); } }, error: function() { showPuzzlingAlert(puzzling_lang.error_title, 'یک خطای ناشناخته رخ داد.', 'error'); projectCard.css('opacity', '1'); } }); } }); });
    
    // --- Notification Center ---
    function fetchNotifications() { $.ajax({ url: puzzlingcrm_ajax_obj.ajax_url, type: 'POST', data: { action: 'puzzling_get_notifications', security: puzzling_ajax_nonce }, success: function(response) { if (response.success) { $('.pzl-notification-panel ul').html(response.data.html); var count = parseInt(response.data.count); if (count > 0) { $('.pzl-notification-count').text(count).show(); } else { $('.pzl-notification-count').hide(); } } } }); }
    $('.pzl-notification-bell').on('click', function(e) { e.stopPropagation(); $('.pzl-notification-panel').toggle(); });
    $(document).on('click', function() { $('.pzl-notification-panel').hide(); });
    $('.pzl-notification-panel').on('click', function(e) { e.stopPropagation(); });
    $('.pzl-notification-panel').on('click', 'li.pzl-unread', function() { var item = $(this); var id = item.data('id'); item.removeClass('pzl-unread').addClass('pzl-read'); var countEl = $('.pzl-notification-count'); var currentCount = parseInt(countEl.text()) - 1; if (currentCount > 0) { countEl.text(currentCount); } else { countEl.hide(); } $.post(puzzlingcrm_ajax_obj.ajax_url, { action: 'puzzling_mark_notification_read', security: puzzling_ajax_nonce, id: id }); });
    fetchNotifications(); setInterval(fetchNotifications, 120000);

    // --- Workflow Status Management ---
    if ($('#status-sortable-list').length) { $('#status-sortable-list').sortable({ axis: 'y', placeholder: 'ui-state-highlight', stop: function(event, ui) { var order = $(this).sortable('toArray', { attribute: 'data-term-id' }); $.post(puzzlingcrm_ajax_obj.ajax_url, { action: 'puzzling_save_status_order', security: puzzling_ajax_nonce, order: order }); } }).disableSelection(); }
    $('#add-new-status-form').on('submit', function(e) { e.preventDefault(); var form = $(this); var newName = $('#new-status-name').val().trim(); if (!newName) return; $.ajax({ url: puzzlingcrm_ajax_obj.ajax_url, type: 'POST', data: { action: 'puzzling_add_new_status', security: puzzling_ajax_nonce, name: newName }, success: function(response) { if (response.success) { var newStatusHTML = '<li data-term-id="' + response.data.term_id + '"><i class="fas fa-grip-vertical"></i> ' + response.data.name + ' <span class="delete-status-btn" data-term-id="' + response.data.term_id + '">&times;</span></li>'; $('#status-sortable-list').append(newStatusHTML); form.trigger('reset'); } else { showPuzzlingAlert(puzzling_lang.error_title, response.data.message, 'error'); } } }); });
    $('#workflow-status-manager').on('click', '.delete-status-btn', function() { if (!confirm('آیا از حذف این وضعیت مطمئن هستید؟ وظایف به ستون "To Do" منتقل خواهند شد.')) return; var btn = $(this); var termId = btn.data('term-id'); var listItem = btn.closest('li'); $.ajax({ url: puzzlingcrm_ajax_obj.ajax_url, type: 'POST', data: { action: 'puzzling_delete_status', security: puzzling_ajax_nonce, term_id: termId }, success: function(response) { if (response.success) { listItem.remove(); } else { showPuzzlingAlert(puzzling_lang.error_title, response.data.message, 'error'); } } }); });
    
    // --- Automation from Product in Contract Edit Page ---
    $('#add-services-from-product').on('click', function() { var button = $(this); var productId = $('#product_id_for_automation').val(); var contractId = $('input[name="contract_id"]').val(); if (!productId) { showPuzzlingAlert(puzzling_lang.info_title, 'لطفاً ابتدا یک محصول را انتخاب کنید.', 'info'); return; } button.text('در حال پردازش...').prop('disabled', true); $.ajax({ url: puzzlingcrm_ajax_obj.ajax_url, type: 'POST', data: { action: 'puzzling_add_services_from_product', security: puzzlingcrm_ajax_obj.nonce, contract_id: contractId, product_id: productId }, success: function(response) { if (response.success) { showPuzzlingAlert(puzzling_lang.success_title, response.data.message, 'success', true); } else { showPuzzlingAlert(puzzling_lang.error_title, response.data.message, 'error'); } }, error: function() { showPuzzlingAlert(puzzling_lang.error_title, puzzling_lang.server_error, 'error'); }, complete: function() { button.text('افزودن خدمات محصول').prop('disabled', false); } }); });
});