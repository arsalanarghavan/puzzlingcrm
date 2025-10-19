/**
 * Smart Reminders & Notifications
 * Intelligent reminder management system
 */

class PuzzlingCRM_Smart_Reminders {
    constructor() {
        this.container = null;
        this.reminders = [];
        this.currentPage = 1;
        this.isLoading = false;
        this.filters = {
            status: 'all',
            priority: 'all',
            object_type: ''
        };
        
        this.init();
    }
    
    init() {
        this.createRemindersInterface();
        this.bindEvents();
        this.loadReminders();
    }
    
    createRemindersInterface() {
        if (!document.getElementById('puzzling-smart-reminders')) {
            const remindersContainer = document.createElement('div');
            remindersContainer.id = 'puzzling-smart-reminders';
            remindersContainer.className = 'puzzling-smart-reminders';
            remindersContainer.innerHTML = `
                <div class="reminders-header">
                    <h3>یادآوری‌های هوشمند</h3>
                    <div class="reminders-controls">
                        <button class="btn-create-reminder" id="create-reminder">
                            <i class="ri-add-line"></i>
                            یادآوری جدید
                        </button>
                        <button class="btn-filters" id="toggle-reminder-filters">
                            <i class="ri-filter-3-line"></i>
                            فیلترها
                        </button>
                    </div>
                </div>
                
                <div class="reminders-filters" id="reminders-filters" style="display: none;">
                    <div class="filters-grid">
                        <div class="filter-group">
                            <label>وضعیت:</label>
                            <select id="filter-status">
                                <option value="all">همه</option>
                                <option value="pending">در انتظار</option>
                                <option value="sent">ارسال شده</option>
                                <option value="completed">تکمیل شده</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>اولویت:</label>
                            <select id="filter-priority">
                                <option value="all">همه</option>
                                <option value="low">پایین</option>
                                <option value="medium">متوسط</option>
                                <option value="high">بالا</option>
                                <option value="urgent">فوری</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>نوع:</label>
                            <select id="filter-object-type">
                                <option value="">همه انواع</option>
                                <option value="project">پروژه‌ها</option>
                                <option value="task">وظایف</option>
                                <option value="contract">قراردادها</option>
                                <option value="lead">سرنخ‌ها</option>
                                <option value="ticket">تیکت‌ها</option>
                            </select>
                        </div>
                        <div class="filter-actions">
                            <button class="btn-apply-filters" id="apply-reminder-filters">اعمال فیلتر</button>
                            <button class="btn-clear-filters" id="clear-reminder-filters">پاک کردن</button>
                        </div>
                    </div>
                </div>
                
                <div class="reminders-content">
                    <div class="reminders-loading" id="reminders-loading" style="display: none;">
                        <div class="loading-spinner"></div>
                        <span>در حال بارگذاری...</span>
                    </div>
                    
                    <div class="reminders-empty" id="reminders-empty" style="display: none;">
                        <i class="ri-notification-3-line"></i>
                        <p>یادآوری‌ای یافت نشد</p>
                    </div>
                    
                    <div class="reminders-list" id="reminders-list"></div>
                    
                    <div class="reminders-pagination" id="reminders-pagination"></div>
                </div>
                
                <!-- Create/Edit Reminder Modal -->
                <div class="reminder-modal" id="reminder-modal" style="display: none;">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h4 id="modal-title">ایجاد یادآوری جدید</h4>
                            <button class="modal-close" id="close-modal">
                                <i class="ri-close-line"></i>
                            </button>
                        </div>
                        <div class="modal-body">
                            <form id="reminder-form">
                                <input type="hidden" id="reminder-id" value="">
                                
                                <div class="form-group">
                                    <label for="reminder-title">عنوان یادآوری *</label>
                                    <input type="text" id="reminder-title" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="reminder-description">توضیحات</label>
                                    <textarea id="reminder-description" rows="3"></textarea>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="reminder-date">تاریخ *</label>
                                        <input type="date" id="reminder-date" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="reminder-time">زمان *</label>
                                        <input type="time" id="reminder-time" required>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="reminder-priority">اولویت</label>
                                        <select id="reminder-priority">
                                            <option value="low">پایین</option>
                                            <option value="medium" selected>متوسط</option>
                                            <option value="high">بالا</option>
                                            <option value="urgent">فوری</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="reminder-recurring">تکرار</label>
                                        <select id="reminder-recurring">
                                            <option value="none">بدون تکرار</option>
                                            <option value="daily">روزانه</option>
                                            <option value="weekly">هفتگی</option>
                                            <option value="monthly">ماهانه</option>
                                            <option value="yearly">سالانه</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="form-group" id="recurring-interval-group" style="display: none;">
                                    <label for="reminder-interval">فاصله تکرار</label>
                                    <input type="number" id="reminder-interval" min="1" value="1">
                                </div>
                                
                                <div class="form-group">
                                    <label>کانال‌های اعلان</label>
                                    <div class="channels-grid">
                                        <label class="channel-option">
                                            <input type="checkbox" name="channels" value="in_app" checked>
                                            <span>درون برنامه</span>
                                        </label>
                                        <label class="channel-option">
                                            <input type="checkbox" name="channels" value="email">
                                            <span>ایمیل</span>
                                        </label>
                                        <label class="channel-option">
                                            <input type="checkbox" name="channels" value="sms">
                                            <span>SMS</span>
                                        </label>
                                        <label class="channel-option">
                                            <input type="checkbox" name="channels" value="push">
                                            <span>Push</span>
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="reminder-object-type">مرتبط با</label>
                                        <select id="reminder-object-type">
                                            <option value="">انتخاب کنید</option>
                                            <option value="project">پروژه</option>
                                            <option value="task">وظیفه</option>
                                            <option value="contract">قرارداد</option>
                                            <option value="lead">سرنخ</option>
                                            <option value="ticket">تیکت</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="reminder-object-id">شناسه</label>
                                        <input type="number" id="reminder-object-id" placeholder="شناسه آیتم">
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button class="btn-cancel" id="cancel-reminder">انصراف</button>
                            <button class="btn-save" id="save-reminder">ذخیره</button>
                        </div>
                    </div>
                </div>
                
                <!-- Snooze Modal -->
                <div class="snooze-modal" id="snooze-modal" style="display: none;">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h4>به تعویق انداختن یادآوری</h4>
                            <button class="modal-close" id="close-snooze-modal">
                                <i class="ri-close-line"></i>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label>برای چه مدت به تعویق بیندازیم؟</label>
                                <div class="snooze-options">
                                    <button class="snooze-btn" data-minutes="15">15 دقیقه</button>
                                    <button class="snooze-btn" data-minutes="30">30 دقیقه</button>
                                    <button class="snooze-btn" data-minutes="60">1 ساعت</button>
                                    <button class="snooze-btn" data-minutes="120">2 ساعت</button>
                                    <button class="snooze-btn" data-minutes="240">4 ساعت</button>
                                    <button class="snooze-btn" data-minutes="480">8 ساعت</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Insert into page
            const targetElement = document.querySelector('.wrap') || document.querySelector('main') || document.body;
            targetElement.appendChild(remindersContainer);
        }
        
        this.container = document.getElementById('puzzling-smart-reminders');
    }
    
    bindEvents() {
        // Create reminder
        document.getElementById('create-reminder').addEventListener('click', () => {
            this.showCreateModal();
        });
        
        // Toggle filters
        document.getElementById('toggle-reminder-filters').addEventListener('click', () => {
            this.toggleFilters();
        });
        
        // Apply filters
        document.getElementById('apply-reminder-filters').addEventListener('click', () => {
            this.applyFilters();
        });
        
        // Clear filters
        document.getElementById('clear-reminder-filters').addEventListener('click', () => {
            this.clearFilters();
        });
        
        // Modal events
        document.getElementById('close-modal').addEventListener('click', () => {
            this.hideModal();
        });
        
        document.getElementById('cancel-reminder').addEventListener('click', () => {
            this.hideModal();
        });
        
        document.getElementById('save-reminder').addEventListener('click', () => {
            this.saveReminder();
        });
        
        // Snooze modal events
        document.getElementById('close-snooze-modal').addEventListener('click', () => {
            this.hideSnoozeModal();
        });
        
        // Recurring change
        document.getElementById('reminder-recurring').addEventListener('change', (e) => {
            const intervalGroup = document.getElementById('recurring-interval-group');
            intervalGroup.style.display = e.target.value === 'none' ? 'none' : 'block';
        });
        
        // Click outside to close modals
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('reminder-modal')) {
                this.hideModal();
            }
            if (e.target.classList.contains('snooze-modal')) {
                this.hideSnoozeModal();
            }
        });
    }
    
    loadReminders() {
        if (this.isLoading) return;
        
        this.isLoading = true;
        this.showLoading();
        
        jQuery.ajax({
            url: puzzlingcrm_ajax_obj.ajax_url,
            type: 'GET',
            data: {
                action: 'puzzling_get_reminders',
                nonce: puzzlingcrm_ajax_obj.nonce,
                ...this.filters,
                page: this.currentPage,
                per_page: 20
            },
            success: (response) => {
                this.isLoading = false;
                this.hideLoading();
                
                if (response.success) {
                    this.displayReminders(response.data.reminders);
                    this.updatePagination(response.data);
                } else {
                    this.showError('خطا در بارگذاری یادآوری‌ها');
                }
            },
            error: () => {
                this.isLoading = false;
                this.hideLoading();
                this.showError('خطا در ارتباط با سرور');
            }
        });
    }
    
    displayReminders(reminders) {
        const remindersList = document.getElementById('reminders-list');
        
        if (reminders.length === 0) {
            this.showEmpty();
            return;
        }
        
        this.hideEmpty();
        
        remindersList.innerHTML = reminders.map(reminder => this.createReminderElement(reminder)).join('');
        
        // Bind reminder action events
        this.bindReminderEvents();
    }
    
    createReminderElement(reminder) {
        const priorityClass = `priority-${reminder.priority}`;
        const statusClass = `status-${reminder.status}`;
        
        return `
            <div class="reminder-item ${priorityClass} ${statusClass}" data-reminder-id="${reminder.id}">
                <div class="reminder-header">
                    <div class="reminder-title">${reminder.title}</div>
                    <div class="reminder-actions">
                        ${reminder.status === 'pending' ? `
                            <button class="btn-snooze" data-id="${reminder.id}">
                                <i class="ri-time-line"></i>
                            </button>
                            <button class="btn-complete" data-id="${reminder.id}">
                                <i class="ri-check-line"></i>
                            </button>
                        ` : ''}
                        <button class="btn-edit" data-id="${reminder.id}">
                            <i class="ri-edit-line"></i>
                        </button>
                        <button class="btn-delete" data-id="${reminder.id}">
                            <i class="ri-delete-bin-line"></i>
                        </button>
                    </div>
                </div>
                
                <div class="reminder-content">
                    ${reminder.description ? `<div class="reminder-description">${reminder.description}</div>` : ''}
                    
                    <div class="reminder-meta">
                        <div class="reminder-datetime">
                            <i class="ri-calendar-line"></i>
                            ${reminder.formatted_datetime}
                        </div>
                        <div class="reminder-priority">
                            <span class="priority-badge">${reminder.priority_label}</span>
                        </div>
                        <div class="reminder-channels">
                            ${reminder.channels.map(channel => `
                                <span class="channel-badge channel-${channel}">${this.getChannelLabel(channel)}</span>
                            `).join('')}
                        </div>
                    </div>
                    
                    ${reminder.object_title ? `
                        <div class="reminder-object">
                            <i class="ri-link"></i>
                            <a href="${this.getObjectUrl(reminder.object_type, reminder.object_id)}" target="_blank">
                                ${reminder.object_title}
                            </a>
                        </div>
                    ` : ''}
                    
                    <div class="reminder-status">
                        <span class="status-badge">${this.getStatusLabel(reminder.status)}</span>
                        ${reminder.snooze_count > 0 ? `<span class="snooze-count">${reminder.snooze_count} بار به تعویق افتاده</span>` : ''}
                    </div>
                </div>
            </div>
        `;
    }
    
    bindReminderEvents() {
        // Snooze buttons
        document.querySelectorAll('.btn-snooze').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const reminderId = e.currentTarget.dataset.id;
                this.showSnoozeModal(reminderId);
            });
        });
        
        // Complete buttons
        document.querySelectorAll('.btn-complete').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const reminderId = e.currentTarget.dataset.id;
                this.completeReminder(reminderId);
            });
        });
        
        // Edit buttons
        document.querySelectorAll('.btn-edit').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const reminderId = e.currentTarget.dataset.id;
                this.editReminder(reminderId);
            });
        });
        
        // Delete buttons
        document.querySelectorAll('.btn-delete').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const reminderId = e.currentTarget.dataset.id;
                this.deleteReminder(reminderId);
            });
        });
        
        // Snooze options
        document.querySelectorAll('.snooze-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const minutes = e.currentTarget.dataset.minutes;
                this.snoozeReminder(this.currentSnoozeId, minutes);
            });
        });
    }
    
    showCreateModal() {
        document.getElementById('modal-title').textContent = 'ایجاد یادآوری جدید';
        document.getElementById('reminder-form').reset();
        document.getElementById('reminder-id').value = '';
        document.getElementById('reminder-modal').style.display = 'flex';
        
        // Set default time to next hour
        const nextHour = new Date();
        nextHour.setHours(nextHour.getHours() + 1);
        document.getElementById('reminder-date').value = nextHour.toISOString().split('T')[0];
        document.getElementById('reminder-time').value = nextHour.toTimeString().slice(0, 5);
    }
    
    editReminder(reminderId) {
        const reminder = this.reminders.find(r => r.id == reminderId);
        if (!reminder) return;
        
        document.getElementById('modal-title').textContent = 'ویرایش یادآوری';
        document.getElementById('reminder-id').value = reminder.id;
        document.getElementById('reminder-title').value = reminder.title;
        document.getElementById('reminder-description').value = reminder.description;
        
        const reminderDate = new Date(reminder.reminder_datetime);
        document.getElementById('reminder-date').value = reminderDate.toISOString().split('T')[0];
        document.getElementById('reminder-time').value = reminderDate.toTimeString().slice(0, 5);
        
        document.getElementById('reminder-priority').value = reminder.priority;
        document.getElementById('reminder-recurring').value = reminder.recurring;
        document.getElementById('reminder-interval').value = reminder.recurring_interval;
        
        // Set channels
        document.querySelectorAll('input[name="channels"]').forEach(input => {
            input.checked = reminder.channels.includes(input.value);
        });
        
        document.getElementById('reminder-object-type').value = reminder.object_type;
        document.getElementById('reminder-object-id').value = reminder.object_id;
        
        document.getElementById('reminder-modal').style.display = 'flex';
    }
    
    saveReminder() {
        const form = document.getElementById('reminder-form');
        const formData = new FormData(form);
        
        const data = {
            title: formData.get('reminder-title') || document.getElementById('reminder-title').value,
            description: document.getElementById('reminder-description').value,
            reminder_date: document.getElementById('reminder-date').value,
            reminder_time: document.getElementById('reminder-time').value,
            priority: document.getElementById('reminder-priority').value,
            channels: Array.from(document.querySelectorAll('input[name="channels"]:checked')).map(cb => cb.value),
            object_type: document.getElementById('reminder-object-type').value,
            object_id: document.getElementById('reminder-object-id').value,
            recurring: document.getElementById('reminder-recurring').value,
            recurring_interval: document.getElementById('reminder-interval').value
        };
        
        const reminderId = document.getElementById('reminder-id').value;
        const action = reminderId ? 'puzzling_update_reminder' : 'puzzling_create_reminder';
        
        if (reminderId) {
            data.reminder_id = reminderId;
        }
        
        jQuery.ajax({
            url: puzzlingcrm_ajax_obj.ajax_url,
            type: 'POST',
            data: {
                action: action,
                nonce: puzzlingcrm_ajax_obj.nonce,
                ...data
            },
            success: (response) => {
                if (response.success) {
                    this.hideModal();
                    this.loadReminders();
                    this.showSuccess(response.data.message);
                } else {
                    this.showError(response.data);
                }
            },
            error: () => {
                this.showError('خطا در ذخیره یادآوری');
            }
        });
    }
    
    completeReminder(reminderId) {
        if (!confirm('آیا مطمئن هستید که این یادآوری تکمیل شده است؟')) return;
        
        jQuery.ajax({
            url: puzzlingcrm_ajax_obj.ajax_url,
            type: 'POST',
            data: {
                action: 'puzzling_mark_reminder_completed',
                nonce: puzzlingcrm_ajax_obj.nonce,
                reminder_id: reminderId
            },
            success: (response) => {
                if (response.success) {
                    this.loadReminders();
                    this.showSuccess(response.data.message);
                } else {
                    this.showError(response.data);
                }
            }
        });
    }
    
    deleteReminder(reminderId) {
        if (!confirm('آیا مطمئن هستید که می‌خواهید این یادآوری را حذف کنید؟')) return;
        
        jQuery.ajax({
            url: puzzlingcrm_ajax_obj.ajax_url,
            type: 'POST',
            data: {
                action: 'puzzling_delete_reminder',
                nonce: puzzlingcrm_ajax_obj.nonce,
                reminder_id: reminderId
            },
            success: (response) => {
                if (response.success) {
                    this.loadReminders();
                    this.showSuccess(response.data.message);
                } else {
                    this.showError(response.data);
                }
            }
        });
    }
    
    showSnoozeModal(reminderId) {
        this.currentSnoozeId = reminderId;
        document.getElementById('snooze-modal').style.display = 'flex';
    }
    
    snoozeReminder(reminderId, minutes) {
        jQuery.ajax({
            url: puzzlingcrm_ajax_obj.ajax_url,
            type: 'POST',
            data: {
                action: 'puzzling_snooze_reminder',
                nonce: puzzlingcrm_ajax_obj.nonce,
                reminder_id: reminderId,
                snooze_minutes: minutes
            },
            success: (response) => {
                if (response.success) {
                    this.hideSnoozeModal();
                    this.loadReminders();
                    this.showSuccess(response.data.message);
                } else {
                    this.showError(response.data);
                }
            }
        });
    }
    
    applyFilters() {
        this.filters = {
            status: document.getElementById('filter-status').value,
            priority: document.getElementById('filter-priority').value,
            object_type: document.getElementById('filter-object-type').value
        };
        
        this.currentPage = 1;
        this.loadReminders();
    }
    
    clearFilters() {
        document.getElementById('filter-status').value = 'all';
        document.getElementById('filter-priority').value = 'all';
        document.getElementById('filter-object-type').value = '';
        
        this.filters = {
            status: 'all',
            priority: 'all',
            object_type: ''
        };
        
        this.currentPage = 1;
        this.loadReminders();
    }
    
    toggleFilters() {
        const filters = document.getElementById('reminders-filters');
        const toggle = document.getElementById('toggle-reminder-filters');
        
        if (filters.style.display === 'none') {
            filters.style.display = 'block';
            toggle.classList.add('active');
        } else {
            filters.style.display = 'none';
            toggle.classList.remove('active');
        }
    }
    
    hideModal() {
        document.getElementById('reminder-modal').style.display = 'none';
    }
    
    hideSnoozeModal() {
        document.getElementById('snooze-modal').style.display = 'none';
    }
    
    showLoading() {
        document.getElementById('reminders-loading').style.display = 'flex';
    }
    
    hideLoading() {
        document.getElementById('reminders-loading').style.display = 'none';
    }
    
    showEmpty() {
        document.getElementById('reminders-empty').style.display = 'block';
    }
    
    hideEmpty() {
        document.getElementById('reminders-empty').style.display = 'none';
    }
    
    updatePagination(data) {
        const pagination = document.getElementById('reminders-pagination');
        
        if (data.total_pages <= 1) {
            pagination.innerHTML = '';
            return;
        }
        
        let paginationHtml = '<div class="pagination">';
        
        // Previous button
        if (this.currentPage > 1) {
            paginationHtml += `<button class="page-btn" data-page="${this.currentPage - 1}">قبلی</button>`;
        }
        
        // Page numbers
        const startPage = Math.max(1, this.currentPage - 2);
        const endPage = Math.min(data.total_pages, this.currentPage + 2);
        
        for (let i = startPage; i <= endPage; i++) {
            const activeClass = i === this.currentPage ? 'active' : '';
            paginationHtml += `<button class="page-btn ${activeClass}" data-page="${i}">${i}</button>`;
        }
        
        // Next button
        if (this.currentPage < data.total_pages) {
            paginationHtml += `<button class="page-btn" data-page="${this.currentPage + 1}">بعدی</button>`;
        }
        
        paginationHtml += '</div>';
        pagination.innerHTML = paginationHtml;
        
        // Bind pagination events
        pagination.querySelectorAll('.page-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                this.currentPage = parseInt(btn.dataset.page);
                this.loadReminders();
            });
        });
    }
    
    getChannelLabel(channel) {
        const labels = {
            'in_app': 'درون برنامه',
            'email': 'ایمیل',
            'sms': 'SMS',
            'push': 'Push'
        };
        
        return labels[channel] || channel;
    }
    
    getStatusLabel(status) {
        const labels = {
            'pending': 'در انتظار',
            'sent': 'ارسال شده',
            'completed': 'تکمیل شده'
        };
        
        return labels[status] || status;
    }
    
    getObjectUrl(objectType, objectId) {
        const urls = {
            'project': `admin.php?page=puzzling-projects&action=edit&id=${objectId}`,
            'task': `admin.php?page=puzzling-tasks&action=edit&id=${objectId}`,
            'contract': `admin.php?page=puzzling-contracts&action=edit&id=${objectId}`,
            'lead': `admin.php?page=puzzling-leads&action=edit&id=${objectId}`,
            'ticket': `admin.php?page=puzzling-tickets&action=edit&id=${objectId}`
        };
        
        return urls[objectType] || '#';
    }
    
    showSuccess(message) {
        // You can implement a toast notification here
        console.log('Success:', message);
    }
    
    showError(message) {
        // You can implement a toast notification here
        console.error('Error:', message);
    }
}

// Initialize when DOM is ready
jQuery(document).ready(() => {
    if (typeof puzzlingcrm_ajax_obj !== 'undefined') {
        window.puzzlingSmartReminders = new PuzzlingCRM_Smart_Reminders();
    }
});
