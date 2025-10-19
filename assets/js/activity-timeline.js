/**
 * Activity Timeline
 * Displays user activities in a timeline format
 */

class PuzzlingCRM_Activity_Timeline {
    constructor() {
        this.container = null;
        this.activities = [];
        this.currentPage = 1;
        this.isLoading = false;
        this.filters = {
            user_id: 0,
            object_type: '',
            object_id: 0,
            action: '',
            date_from: '',
            date_to: ''
        };
        
        this.init();
    }
    
    init() {
        this.createTimelineInterface();
        this.bindEvents();
        this.loadActivities();
    }
    
    createTimelineInterface() {
        if (!document.getElementById('puzzling-activity-timeline')) {
            const timelineContainer = document.createElement('div');
            timelineContainer.id = 'puzzling-activity-timeline';
            timelineContainer.className = 'puzzling-activity-timeline';
            timelineContainer.innerHTML = `
                <div class="timeline-header">
                    <h3>خط زمانی فعالیت‌ها</h3>
                    <div class="timeline-controls">
                        <button class="btn-refresh" id="refresh-timeline">
                            <i class="ri-refresh-line"></i>
                            به‌روزرسانی
                        </button>
                        <button class="btn-filters" id="toggle-filters">
                            <i class="ri-filter-3-line"></i>
                            فیلترها
                        </button>
                    </div>
                </div>
                
                <div class="timeline-filters" id="timeline-filters" style="display: none;">
                    <div class="filters-grid">
                        <div class="filter-group">
                            <label>کاربر:</label>
                            <select id="filter-user">
                                <option value="0">همه کاربران</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>نوع فعالیت:</label>
                            <select id="filter-object-type">
                                <option value="">همه انواع</option>
                                <option value="project">پروژه‌ها</option>
                                <option value="task">وظایف</option>
                                <option value="contract">قراردادها</option>
                                <option value="lead">سرنخ‌ها</option>
                                <option value="ticket">تیکت‌ها</option>
                                <option value="user">کاربران</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>عمل:</label>
                            <select id="filter-action">
                                <option value="">همه اعمال</option>
                                <option value="created">ایجاد</option>
                                <option value="updated">به‌روزرسانی</option>
                                <option value="deleted">حذف</option>
                                <option value="commented">دیدگاه</option>
                                <option value="status_changed">تغییر وضعیت</option>
                                <option value="login">ورود</option>
                                <option value="logout">خروج</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>از تاریخ:</label>
                            <input type="date" id="filter-date-from">
                        </div>
                        <div class="filter-group">
                            <label>تا تاریخ:</label>
                            <input type="date" id="filter-date-to">
                        </div>
                        <div class="filter-actions">
                            <button class="btn-apply-filters" id="apply-timeline-filters">اعمال فیلتر</button>
                            <button class="btn-clear-filters" id="clear-timeline-filters">پاک کردن</button>
                        </div>
                    </div>
                </div>
                
                <div class="timeline-content">
                    <div class="timeline-loading" id="timeline-loading" style="display: none;">
                        <div class="loading-spinner"></div>
                        <span>در حال بارگذاری...</span>
                    </div>
                    
                    <div class="timeline-empty" id="timeline-empty" style="display: none;">
                        <i class="ri-time-line"></i>
                        <p>فعالیتی یافت نشد</p>
                    </div>
                    
                    <div class="timeline-list" id="timeline-list"></div>
                    
                    <div class="timeline-pagination" id="timeline-pagination"></div>
                </div>
            `;
            
            // Insert into page
            const targetElement = document.querySelector('.wrap') || document.querySelector('main') || document.body;
            targetElement.appendChild(timelineContainer);
        }
        
        this.container = document.getElementById('puzzling-activity-timeline');
    }
    
    bindEvents() {
        // Refresh timeline
        document.getElementById('refresh-timeline').addEventListener('click', () => {
            this.refreshTimeline();
        });
        
        // Toggle filters
        document.getElementById('toggle-filters').addEventListener('click', () => {
            this.toggleFilters();
        });
        
        // Apply filters
        document.getElementById('apply-timeline-filters').addEventListener('click', () => {
            this.applyFilters();
        });
        
        // Clear filters
        document.getElementById('clear-timeline-filters').addEventListener('click', () => {
            this.clearFilters();
        });
        
        // Load more activities on scroll
        window.addEventListener('scroll', () => {
            this.handleScroll();
        });
    }
    
    loadActivities() {
        if (this.isLoading) return;
        
        this.isLoading = true;
        this.showLoading();
        
        jQuery.ajax({
            url: puzzlingcrm_ajax_obj.ajax_url,
            type: 'GET',
            data: {
                action: 'puzzling_get_activities',
                nonce: puzzlingcrm_ajax_obj.nonce,
                ...this.filters,
                page: this.currentPage,
                per_page: 20
            },
            success: (response) => {
                this.isLoading = false;
                this.hideLoading();
                
                if (response.success) {
                    this.displayActivities(response.data.activities);
                    this.updatePagination(response.data);
                } else {
                    this.showError('خطا در بارگذاری فعالیت‌ها');
                }
            },
            error: () => {
                this.isLoading = false;
                this.hideLoading();
                this.showError('خطا در ارتباط با سرور');
            }
        });
    }
    
    displayActivities(activities) {
        const timelineList = document.getElementById('timeline-list');
        
        if (activities.length === 0) {
            this.showEmpty();
            return;
        }
        
        this.hideEmpty();
        
        // Append new activities
        activities.forEach(activity => {
            const activityElement = this.createActivityElement(activity);
            timelineList.appendChild(activityElement);
        });
        
        this.activities = [...this.activities, ...activities];
    }
    
    createActivityElement(activity) {
        const activityDiv = document.createElement('div');
        activityDiv.className = 'timeline-item';
        activityDiv.innerHTML = `
            <div class="timeline-marker">
                <div class="timeline-icon" style="background-color: ${activity.color}">
                    <i class="${activity.icon}"></i>
                </div>
            </div>
            <div class="timeline-content">
                <div class="activity-header">
                    <div class="user-info">
                        <img src="${activity.user_avatar}" alt="${activity.user_name}" class="user-avatar">
                        <div class="user-details">
                            <span class="user-name">${activity.user_name}</span>
                            <span class="activity-time">${activity.time_ago}</span>
                        </div>
                    </div>
                    <div class="activity-meta">
                        <span class="object-type">${this.getObjectTypeLabel(activity.object_type)}</span>
                        <span class="action-type">${this.getActionLabel(activity.action)}</span>
                    </div>
                </div>
                <div class="activity-description">
                    ${activity.description}
                </div>
                ${this.createMetadataElement(activity.metadata)}
            </div>
        `;
        
        return activityDiv;
    }
    
    createMetadataElement(metadata) {
        if (!metadata || Object.keys(metadata).length === 0) {
            return '';
        }
        
        let metadataHtml = '<div class="activity-metadata">';
        
        if (metadata.post_title) {
            metadataHtml += `<div class="meta-item"><strong>عنوان:</strong> ${metadata.post_title}</div>`;
        }
        
        if (metadata.user_email) {
            metadataHtml += `<div class="meta-item"><strong>ایمیل:</strong> ${metadata.user_email}</div>`;
        }
        
        if (metadata.old_status && metadata.new_status) {
            metadataHtml += `<div class="meta-item"><strong>تغییر وضعیت:</strong> ${metadata.old_status} → ${metadata.new_status}</div>`;
        }
        
        if (metadata.comment_content) {
            metadataHtml += `<div class="meta-item"><strong>دیدگاه:</strong> ${metadata.comment_content}</div>`;
        }
        
        metadataHtml += '</div>';
        
        return metadataHtml;
    }
    
    updatePagination(data) {
        const pagination = document.getElementById('timeline-pagination');
        
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
                this.clearTimeline();
                this.loadActivities();
            });
        });
    }
    
    applyFilters() {
        this.filters = {
            user_id: parseInt(document.getElementById('filter-user').value) || 0,
            object_type: document.getElementById('filter-object-type').value,
            object_id: 0,
            action: document.getElementById('filter-action').value,
            date_from: document.getElementById('filter-date-from').value,
            date_to: document.getElementById('filter-date-to').value
        };
        
        this.currentPage = 1;
        this.clearTimeline();
        this.loadActivities();
    }
    
    clearFilters() {
        document.getElementById('filter-user').value = '0';
        document.getElementById('filter-object-type').value = '';
        document.getElementById('filter-action').value = '';
        document.getElementById('filter-date-from').value = '';
        document.getElementById('filter-date-to').value = '';
        
        this.filters = {
            user_id: 0,
            object_type: '',
            object_id: 0,
            action: '',
            date_from: '',
            date_to: ''
        };
        
        this.currentPage = 1;
        this.clearTimeline();
        this.loadActivities();
    }
    
    refreshTimeline() {
        this.currentPage = 1;
        this.clearTimeline();
        this.loadActivities();
    }
    
    clearTimeline() {
        document.getElementById('timeline-list').innerHTML = '';
        document.getElementById('timeline-pagination').innerHTML = '';
        this.activities = [];
    }
    
    toggleFilters() {
        const filters = document.getElementById('timeline-filters');
        const toggle = document.getElementById('toggle-filters');
        
        if (filters.style.display === 'none') {
            filters.style.display = 'block';
            toggle.classList.add('active');
        } else {
            filters.style.display = 'none';
            toggle.classList.remove('active');
        }
    }
    
    handleScroll() {
        const timeline = document.getElementById('timeline-list');
        if (!timeline) return;
        
        const rect = timeline.getBoundingClientRect();
        const isVisible = rect.top < window.innerHeight && rect.bottom > 0;
        
        if (isVisible && !this.isLoading) {
            // Load more activities if near bottom
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            const windowHeight = window.innerHeight;
            const documentHeight = document.documentElement.scrollHeight;
            
            if (scrollTop + windowHeight >= documentHeight - 100) {
                this.loadMoreActivities();
            }
        }
    }
    
    loadMoreActivities() {
        this.currentPage++;
        this.loadActivities();
    }
    
    showLoading() {
        document.getElementById('timeline-loading').style.display = 'flex';
    }
    
    hideLoading() {
        document.getElementById('timeline-loading').style.display = 'none';
    }
    
    showEmpty() {
        document.getElementById('timeline-empty').style.display = 'block';
    }
    
    hideEmpty() {
        document.getElementById('timeline-empty').style.display = 'none';
    }
    
    showError(message) {
        // You can implement a toast notification here
        console.error('Timeline error:', message);
    }
    
    getObjectTypeLabel(objectType) {
        const labels = {
            'project': 'پروژه',
            'task': 'وظیفه',
            'contract': 'قرارداد',
            'lead': 'سرنخ',
            'ticket': 'تیکت',
            'user': 'کاربر'
        };
        
        return labels[objectType] || objectType;
    }
    
    getActionLabel(action) {
        const labels = {
            'created': 'ایجاد',
            'updated': 'به‌روزرسانی',
            'deleted': 'حذف',
            'commented': 'دیدگاه',
            'status_changed': 'تغییر وضعیت',
            'login': 'ورود',
            'logout': 'خروج',
            'registered': 'ثبت نام'
        };
        
        return labels[action] || action;
    }
    
    loadUsers() {
        // Load users for filter dropdown
        jQuery.ajax({
            url: puzzlingcrm_ajax_obj.ajax_url,
            type: 'GET',
            data: {
                action: 'puzzling_get_users',
                nonce: puzzlingcrm_ajax_obj.nonce
            },
            success: (response) => {
                if (response.success) {
                    const userSelect = document.getElementById('filter-user');
                    userSelect.innerHTML = '<option value="0">همه کاربران</option>';
                    
                    response.data.forEach(user => {
                        const option = document.createElement('option');
                        option.value = user.ID;
                        option.textContent = user.display_name;
                        userSelect.appendChild(option);
                    });
                }
            }
        });
    }
}

// Initialize when DOM is ready
jQuery(document).ready(() => {
    if (typeof puzzlingcrm_ajax_obj !== 'undefined') {
        window.puzzlingActivityTimeline = new PuzzlingCRM_Activity_Timeline();
    }
});
