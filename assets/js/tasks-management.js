/**
 * PuzzlingCRM Tasks Management
 * Handles Kanban, Calendar, Timeline, and Task Modals
 */

(function($) {
    'use strict';

    let taskCalendar = null;
    let taskGantt = null;

    $(document).ready(function() {
        initTaskManagement();
    });

    function initTaskManagement() {
        // Initialize based on active view
        if ($('.pzl-kanban-board').length) {
            initKanbanBoard();
        }
        
        if ($('#task-calendar').length) {
            initTaskCalendar();
        }
        
        if ($('#task-timeline').length) {
            initTaskTimeline();
        }
        
        // Event handlers
        initTaskEventHandlers();
    }

    /**
     * Initialize Kanban Board with Drag & Drop
     */
    function initKanbanBoard() {
        $('.pzl-task-list').sortable({
            connectWith: '.pzl-task-list',
            placeholder: 'pzl-task-placeholder',
            cursor: 'move',
            opacity: 0.8,
            revert: 150,
            tolerance: 'pointer',
            update: function(event, ui) {
                const taskId = ui.item.data('task-id');
                const newStatus = ui.item.parent().data('status');
                
                if (taskId && newStatus) {
                    updateTaskStatus(taskId, newStatus);
                }
            }
        });

        // Make task items clickable
        $(document).on('click', '.pzl-task-item', function(e) {
            if (!$(e.target).is('button, a')) {
                const taskId = $(this).data('task-id');
                openTaskModal(taskId);
            }
        });
    }

    /**
     * Update task status via AJAX
     */
    function updateTaskStatus(taskId, newStatus) {
        $.ajax({
            url: puzzlingcrm_ajax_obj.ajax_url,
            type: 'POST',
            data: {
                action: 'puzzling_update_task_status',
                security: puzzlingcrm_ajax_obj.nonce,
                task_id: taskId,
                status: newStatus
            },
            success: function(response) {
                if (response.success) {
                    showToast('success', 'وضعیت وظیفه به‌روزرسانی شد');
                } else {
                    showToast('error', response.data.message || 'خطا در به‌روزرسانی');
                    location.reload(); // Reload to revert
                }
            },
            error: function() {
                showToast('error', 'خطا در ارتباط با سرور');
                location.reload();
            }
        });
    }

    /**
     * Initialize FullCalendar
     */
    function initTaskCalendar() {
        if (typeof FullCalendar === 'undefined') {
            $('#task-calendar').html('<div class="alert alert-warning">کتابخانه FullCalendar لود نشده است.</div>');
            return;
        }

        const calendarEl = document.getElementById('task-calendar');
        
        taskCalendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            direction: 'rtl',
            firstDay: 6, // شنبه = 6 (0=یکشنبه تا 6=شنبه)
            weekNumbers: false,
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            buttonText: {
                today: 'امروز',
                month: 'ماه',
                week: 'هفته',
                day: 'روز'
            },
            locale: 'fa',
            dayHeaderFormat: { weekday: 'long' },
            weekends: [5, 6], // جمعه و شنبه
            events: function(info, successCallback, failureCallback) {
                loadTasksForCalendar(info, successCallback, failureCallback);
            },
            eventClick: function(info) {
                info.jsEvent.preventDefault();
                const taskId = info.event.id;
                openTaskModal(taskId);
            },
            height: 'auto'
        });

        taskCalendar.render();
    }

    /**
     * Load tasks for calendar
     */
    function loadTasksForCalendar(info, successCallback, failureCallback) {
        $.ajax({
            url: puzzlingcrm_ajax_obj.ajax_url,
            type: 'POST',
            data: {
                action: 'puzzling_get_tasks_calendar',
                security: puzzlingcrm_ajax_obj.nonce,
                start: info.startStr,
                end: info.endStr
            },
            success: function(response) {
                if (response.success && response.data.events) {
                    successCallback(response.data.events);
                } else {
                    failureCallback();
                }
            },
            error: function() {
                failureCallback();
            }
        });
    }

    /**
     * Initialize DHTMLX Gantt Timeline
     */
    function initTaskTimeline() {
        if (typeof gantt === 'undefined') {
            $('#task-timeline').html('<div class="alert alert-warning">کتابخانه DHTMLX Gantt لود نشده است.</div>');
            return;
        }

        gantt.config.date_format = "%Y-%m-%d";
        gantt.config.rtl = true;
        gantt.config.readonly = false;
        
        gantt.init("task-timeline");
        
        // Load tasks
        loadTasksForGantt();
    }

    /**
     * Load tasks for Gantt
     */
    function loadTasksForGantt() {
        $.ajax({
            url: puzzlingcrm_ajax_obj.ajax_url,
            type: 'POST',
            data: {
                action: 'puzzling_get_tasks_gantt',
                security: puzzlingcrm_ajax_obj.nonce
            },
            success: function(response) {
                if (response.success && response.data.tasks) {
                    gantt.parse({data: response.data.tasks});
                }
            }
        });
    }

    /**
     * Event Handlers
     */
    function initTaskEventHandlers() {
        // View/Edit buttons in list view
        $(document).on('click', '.btn-task-view, .btn-task-edit', function(e) {
            e.preventDefault();
            const taskId = $(this).data('task-id');
            openTaskModal(taskId);
        });

        // New task button
        $(document).on('click', '#btn-new-task, [data-bs-target="#newTaskModal"]', function(e) {
            e.preventDefault();
            openNewTaskModal();
        });
        
        // Create from template button
        $(document).on('click', '#btn-create-from-template', function(e) {
            e.preventDefault();
            openTemplatesModal();
        });
        
        // Task status change in modal
        $(document).on('change', '.task-status-select', function() {
            const taskId = $(this).data('task-id');
            const newStatus = $(this).val();
            updateTaskStatus(taskId, newStatus);
        });
        
        // Add comment button
        $(document).on('click', '.btn-add-comment', function() {
            const taskId = $(this).data('task-id');
            const comment = $('#new-comment-' + taskId).val();
            
            if (!comment.trim()) {
                showToast('error', 'لطفاً نظر خود را بنویسید');
                return;
            }
            
            addTaskComment(taskId, comment);
        });
        
        // Add checklist item
        $(document).on('click', '.btn-add-checklist', function() {
            const taskId = $(this).data('task-id');
            const item = $('#new-checklist-item-' + taskId).val();
            
            if (!item.trim()) {
                showToast('error', 'لطفاً متن آیتم را وارد کنید');
                return;
            }
            
            addChecklistItem(taskId, item);
        });
        
        // Upload attachment button
        $(document).on('click', '#upload-attachment-btn', function() {
            $('#task-attachment-input').click();
        });
        
        // File selected
        $(document).on('change', '#task-attachment-input', function() {
            const file = this.files[0];
            if (!file) return;
            
            const taskId = $('input[name="task_id"]').val() || $('.task-status-select').data('task-id');
            
            uploadTaskAttachment(taskId, file);
        });
        
        // Delete attachment
        $(document).on('click', '.delete-attachment', function() {
            const attachmentId = $(this).data('attachment-id');
            const taskId = $('.task-status-select').data('task-id');
            
            Swal.fire({
                title: 'حذف فایل؟',
                text: 'آیا مطمئن هستید؟',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'بله، حذف کن',
                cancelButtonText: 'انصراف'
            }).then((result) => {
                if (result.isConfirmed) {
                    deleteTaskAttachment(taskId, attachmentId);
                }
            });
        });
    }

    /**
     * Open Task Modal
     */
    function openTaskModal(taskId) {
        // Load task details via AJAX
        $.ajax({
            url: puzzlingcrm_ajax_obj.ajax_url,
            type: 'POST',
            data: {
                action: 'puzzling_get_task_details',
                security: puzzlingcrm_ajax_obj.nonce,
                task_id: taskId
            },
            success: function(response) {
                if (response.success && response.data.html) {
                    showTaskModal(response.data.html);
                } else {
                    showToast('error', 'خطا در بارگذاری اطلاعات وظیفه');
                }
            },
            error: function() {
                showToast('error', 'خطا در ارتباط با سرور');
            }
        });
    }

    /**
     * Show Task Modal
     */
    function showTaskModal(html) {
        // Remove existing modal
        $('#taskDetailModal').remove();
        
        // Create modal
        const modalHtml = `
            <div class="modal fade" id="taskDetailModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        ${html}
                    </div>
                </div>
            </div>
        `;
        
        $('body').append(modalHtml);
        
        const modal = new bootstrap.Modal(document.getElementById('taskDetailModal'));
        modal.show();
    }

    /**
     * Open New Task Modal
     */
    function openNewTaskModal() {
        Swal.fire({
            title: 'افزودن وظیفه جدید',
            html: `
                <div class="text-start">
                    <div class="mb-3">
                        <label class="form-label">عنوان وظیفه</label>
                        <input type="text" id="new-task-title" class="form-control" placeholder="عنوان...">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">توضیحات</label>
                        <textarea id="new-task-description" class="form-control" rows="3"></textarea>
                    </div>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'ایجاد وظیفه',
            cancelButtonText: 'انصراف',
            customClass: {
                confirmButton: 'btn btn-primary',
                cancelButton: 'btn btn-secondary'
            },
            preConfirm: () => {
                const title = $('#new-task-title').val();
                const description = $('#new-task-description').val();
                
                if (!title) {
                    Swal.showValidationMessage('عنوان الزامی است');
                    return false;
                }
                
                return {title, description};
            }
        }).then((result) => {
            if (result.isConfirmed) {
                createNewTask(result.value);
            }
        });
    }

    /**
     * Create new task
     */
    function createNewTask(data) {
        $.ajax({
            url: puzzlingcrm_ajax_obj.ajax_url,
            type: 'POST',
            data: {
                action: 'puzzling_create_task',
                security: puzzlingcrm_ajax_obj.nonce,
                title: data.title,
                description: data.description
            },
            success: function(response) {
                if (response.success) {
                    showToast('success', 'وظیفه ایجاد شد');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast('error', response.data.message || 'خطا در ایجاد وظیفه');
                }
            },
            error: function() {
                showToast('error', 'خطا در ارتباط با سرور');
            }
        });
    }

    /**
     * Open Templates Modal
     */
    function openTemplatesModal() {
        // Load templates via AJAX
        $.ajax({
            url: puzzlingcrm_ajax_obj.ajax_url,
            type: 'POST',
            data: {
                action: 'puzzling_get_templates',
                security: puzzlingcrm_ajax_obj.nonce
            },
            success: function(response) {
                if (response.success && response.data.templates) {
                    showTemplatesModal(response.data.templates);
                } else {
                    showToast('error', 'خطا در بارگذاری قالب‌ها');
                }
            },
            error: function() {
                showToast('error', 'خطا در ارتباط با سرور');
            }
        });
    }

    /**
     * Show Templates Modal
     */
    function showTemplatesModal(templates) {
        let templatesHtml = '';
        
        // Group by category
        const categories = {};
        templates.forEach(template => {
            const cat = template.category || 'سایر';
            if (!categories[cat]) {
                categories[cat] = [];
            }
            categories[cat].push(template);
        });
        
        for (const [category, items] of Object.entries(categories)) {
            templatesHtml += `
                <div class="template-category mb-4">
                    <h6 class="text-primary mb-3"><i class="ri-folder-line me-2"></i>${category}</h6>
                    <div class="row g-3">
            `;
            
            items.forEach(template => {
                const recurringBadge = template.is_recurring ? 
                    `<span class="badge bg-warning-transparent me-2"><i class="ri-repeat-line"></i> ${getRecurringText(template.recurring_type)}</span>` : '';
                
                templatesHtml += `
                    <div class="col-md-6">
                        <div class="card custom-card template-card" data-template-id="${template.id}">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="mb-0">${template.title}</h6>
                                    ${recurringBadge}
                                </div>
                                <p class="text-muted fs-12 mb-2">
                                    <i class="ri-task-line me-1"></i>${template.tasks_count} وظیفه
                                </p>
                                <button class="btn btn-sm btn-primary w-100 btn-use-template" data-template-id="${template.id}">
                                    <i class="ri-add-circle-line me-1"></i>استفاده از این قالب
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            templatesHtml += `
                    </div>
                </div>
            `;
        }
        
        Swal.fire({
            title: 'انتخاب قالب وظیفه',
            html: `
                <div class="text-start" style="max-height: 500px; overflow-y: auto;">
                    ${templatesHtml}
                </div>
            `,
            width: '800px',
            showConfirmButton: false,
            showCancelButton: true,
            cancelButtonText: 'بستن',
            customClass: {
                cancelButton: 'btn btn-secondary'
            }
        });
        
        // Event handler for use template buttons
        $(document).on('click', '.btn-use-template', function() {
            const templateId = $(this).data('template-id');
            Swal.close();
            createFromTemplate(templateId);
        });
    }

    /**
     * Get Recurring Text
     */
    function getRecurringText(type) {
        const texts = {
            'daily': 'روزانه',
            'weekly': 'هفتگی',
            'monthly': 'ماهیانه'
        };
        return texts[type] || type;
    }

    /**
     * Create Tasks from Template
     */
    function createFromTemplate(templateId) {
        Swal.fire({
            title: 'ایجاد وظایف از قالب',
            html: `
                <div class="text-start">
                    <div class="mb-3">
                        <label class="form-label">تاریخ شروع</label>
                        <input type="date" id="template-start-date" class="form-control" value="${new Date().toISOString().split('T')[0]}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">پروژه (اختیاری)</label>
                        <select id="template-project" class="form-select">
                            <option value="0">بدون پروژه</option>
                        </select>
                    </div>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'ایجاد وظایف',
            cancelButtonText: 'انصراف',
            customClass: {
                confirmButton: 'btn btn-primary',
                cancelButton: 'btn btn-secondary'
            },
            didOpen: () => {
                // Load projects for dropdown
                loadProjectsForDropdown();
            },
            preConfirm: () => {
                const startDate = $('#template-start-date').val();
                const projectId = $('#template-project').val();
                
                if (!startDate) {
                    Swal.showValidationMessage('تاریخ شروع الزامی است');
                    return false;
                }
                
                return {startDate, projectId};
            }
        }).then((result) => {
            if (result.isConfirmed) {
                executeCreateFromTemplate(templateId, result.value);
            }
        });
    }

    /**
     * Load Projects for Dropdown
     */
    function loadProjectsForDropdown() {
        // این باید از AJAX projects رو بگیره
        // فعلاً ساده می‌ذاریم
    }

    /**
     * Execute Create from Template
     */
    function executeCreateFromTemplate(templateId, data) {
        Swal.fire({
            title: 'در حال ایجاد...',
            text: 'لطفاً صبر کنید',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        $.ajax({
            url: puzzlingcrm_ajax_obj.ajax_url,
            type: 'POST',
            data: {
                action: 'puzzling_create_from_template',
                security: puzzlingcrm_ajax_obj.nonce,
                template_id: templateId,
                project_id: data.projectId,
                start_date: data.startDate
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'موفق!',
                        text: response.data.message,
                        confirmButtonText: 'باشه'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'خطا',
                        text: response.data.message || 'خطا در ایجاد وظایف'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'خطا',
                    text: 'خطا در ارتباط با سرور'
                });
            }
        });
    }

    /**
     * Add Task Comment
     */
    function addTaskComment(taskId, comment) {
        $.ajax({
            url: puzzlingcrm_ajax_obj.ajax_url,
            type: 'POST',
            data: {
                action: 'puzzling_add_task_comment',
                security: puzzlingcrm_ajax_obj.nonce,
                task_id: taskId,
                comment: comment
            },
            success: function(response) {
                if (response.success) {
                    showToast('success', 'نظر با موفقیت ثبت شد');
                    $('#new-comment-' + taskId).val('');
                    // Reload modal
                    openTaskModal(taskId);
                } else {
                    showToast('error', response.data.message || 'خطا در ثبت نظر');
                }
            },
            error: function() {
                showToast('error', 'خطا در ارتباط با سرور');
            }
        });
    }

    /**
     * Add Checklist Item
     */
    function addChecklistItem(taskId, item) {
        $.ajax({
            url: puzzlingcrm_ajax_obj.ajax_url,
            type: 'POST',
            data: {
                action: 'puzzling_manage_checklist',
                security: puzzlingcrm_ajax_obj.nonce,
                task_id: taskId,
                action_type: 'add',
                item_text: item
            },
            success: function(response) {
                if (response.success) {
                    showToast('success', 'آیتم اضافه شد');
                    $('#new-checklist-item-' + taskId).val('');
                    // Reload modal
                    openTaskModal(taskId);
                } else {
                    showToast('error', response.data.message || 'خطا در افزودن آیتم');
                }
            },
            error: function() {
                showToast('error', 'خطا در ارتباط با سرور');
            }
        });
    }

    /**
     * Upload Task Attachment
     */
    function uploadTaskAttachment(taskId, file) {
        const formData = new FormData();
        formData.append('action', 'puzzling_upload_task_attachment');
        formData.append('security', puzzlingcrm_ajax_obj.nonce);
        formData.append('task_id', taskId);
        formData.append('file', file);
        
        Swal.fire({
            title: 'در حال آپلود...',
            html: '<div class="spinner-border text-primary"></div>',
            showConfirmButton: false,
            allowOutsideClick: false
        });
        
        $.ajax({
            url: puzzlingcrm_ajax_obj.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'موفق!',
                        text: 'فایل آپلود شد',
                        showConfirmButton: false,
                        timer: 1500
                    });
                    
                    // Reload modal
                    setTimeout(function() {
                        openTaskModal(taskId);
                    }, 1500);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'خطا',
                        text: response.data.message
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'خطا',
                    text: 'خطا در آپلود فایل'
                });
            }
        });
    }

    /**
     * Delete Task Attachment
     */
    function deleteTaskAttachment(taskId, attachmentId) {
        $.ajax({
            url: puzzlingcrm_ajax_obj.ajax_url,
            type: 'POST',
            data: {
                action: 'puzzling_delete_task_attachment',
                security: puzzlingcrm_ajax_obj.nonce,
                task_id: taskId,
                attachment_id: attachmentId
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'حذف شد',
                        showConfirmButton: false,
                        timer: 1000
                    });
                    
                    // Reload modal
                    setTimeout(function() {
                        openTaskModal(taskId);
                    }, 1000);
                } else {
                    showToast('error', response.data.message);
                }
            }
        });
    }

    /**
     * Show Toast Notification
     */
    function showToast(type, message) {
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true
        });

        Toast.fire({
            icon: type,
            title: message
        });
    }

})(jQuery);

