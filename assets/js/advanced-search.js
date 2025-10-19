/**
 * Advanced Search with Elasticsearch
 * Provides real-time search with suggestions and filters
 */

class PuzzlingCRM_Advanced_Search {
    constructor() {
        this.searchInput = null;
        this.searchResults = null;
        this.searchFilters = null;
        this.currentQuery = '';
        this.currentType = 'all';
        this.currentPage = 1;
        this.isSearching = false;
        this.searchTimeout = null;
        this.suggestionTimeout = null;
        
        this.init();
    }
    
    init() {
        this.createSearchInterface();
        this.bindEvents();
        this.loadFilters();
    }
    
    createSearchInterface() {
        // Create search container if it doesn't exist
        if (!document.getElementById('puzzling-advanced-search')) {
            const searchContainer = document.createElement('div');
            searchContainer.id = 'puzzling-advanced-search';
            searchContainer.className = 'puzzling-advanced-search';
            searchContainer.innerHTML = `
                <div class="search-header">
                    <div class="search-input-container">
                        <input type="text" id="search-input" placeholder="جستجوی پیشرفته..." autocomplete="off">
                        <button type="button" id="search-clear" class="search-clear-btn">
                            <i class="ri-close-line"></i>
                        </button>
                        <div class="search-suggestions" id="search-suggestions"></div>
                    </div>
                    <div class="search-type-selector">
                        <select id="search-type">
                            <option value="all">همه موارد</option>
                            <option value="project">پروژه‌ها</option>
                            <option value="task">وظایف</option>
                            <option value="contract">قراردادها</option>
                            <option value="lead">سرنخ‌ها</option>
                            <option value="ticket">تیکت‌ها</option>
                            <option value="user">کاربران</option>
                        </select>
                    </div>
                    <button type="button" id="search-filters-toggle" class="filters-toggle-btn">
                        <i class="ri-filter-3-line"></i>
                        فیلترها
                    </button>
                </div>
                <div class="search-filters" id="search-filters" style="display: none;">
                    <div class="filters-content">
                        <div class="filter-group">
                            <label>وضعیت:</label>
                            <select id="filter-status" multiple>
                                <option value="active">فعال</option>
                                <option value="completed">تکمیل شده</option>
                                <option value="pending">در انتظار</option>
                                <option value="cancelled">لغو شده</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>اولویت:</label>
                            <select id="filter-priority" multiple>
                                <option value="high">بالا</option>
                                <option value="medium">متوسط</option>
                                <option value="low">پایین</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>تاریخ از:</label>
                            <input type="date" id="filter-date-from">
                        </div>
                        <div class="filter-group">
                            <label>تاریخ تا:</label>
                            <input type="date" id="filter-date-to">
                        </div>
                        <div class="filter-actions">
                            <button type="button" id="apply-filters" class="btn-primary">اعمال فیلتر</button>
                            <button type="button" id="clear-filters" class="btn-secondary">پاک کردن</button>
                        </div>
                    </div>
                </div>
                <div class="search-results" id="search-results">
                    <div class="search-loading" style="display: none;">
                        <div class="loading-spinner"></div>
                        <span>در حال جستجو...</span>
                    </div>
                    <div class="search-empty" style="display: none;">
                        <i class="ri-search-line"></i>
                        <p>نتیجه‌ای یافت نشد</p>
                    </div>
                    <div class="search-results-list" id="search-results-list"></div>
                    <div class="search-pagination" id="search-pagination"></div>
                </div>
            `;
            
            // Insert after the first h1 or at the beginning of the content
            const targetElement = document.querySelector('h1') || document.querySelector('.wrap');
            if (targetElement) {
                targetElement.parentNode.insertBefore(searchContainer, targetElement.nextSibling);
            } else {
                document.body.appendChild(searchContainer);
            }
        }
        
        this.searchInput = document.getElementById('search-input');
        this.searchResults = document.getElementById('search-results');
        this.searchFilters = document.getElementById('search-filters');
    }
    
    bindEvents() {
        // Search input events
        this.searchInput.addEventListener('input', (e) => {
            this.handleSearchInput(e.target.value);
        });
        
        this.searchInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.performSearch();
            } else if (e.key === 'Escape') {
                this.clearSearch();
            }
        });
        
        // Search type change
        document.getElementById('search-type').addEventListener('change', (e) => {
            this.currentType = e.target.value;
            this.performSearch();
        });
        
        // Clear search
        document.getElementById('search-clear').addEventListener('click', () => {
            this.clearSearch();
        });
        
        // Filters toggle
        document.getElementById('search-filters-toggle').addEventListener('click', () => {
            this.toggleFilters();
        });
        
        // Apply filters
        document.getElementById('apply-filters').addEventListener('click', () => {
            this.applyFilters();
        });
        
        // Clear filters
        document.getElementById('clear-filters').addEventListener('click', () => {
            this.clearFilters();
        });
        
        // Click outside to close suggestions
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.search-input-container')) {
                this.hideSuggestions();
            }
        });
    }
    
    handleSearchInput(query) {
        this.currentQuery = query;
        
        // Clear previous timeout
        if (this.suggestionTimeout) {
            clearTimeout(this.suggestionTimeout);
        }
        
        if (query.length < 2) {
            this.hideSuggestions();
            return;
        }
        
        // Debounce suggestions
        this.suggestionTimeout = setTimeout(() => {
            this.getSuggestions(query);
        }, 300);
    }
    
    getSuggestions(query) {
        jQuery.ajax({
            url: puzzlingcrm_ajax_obj.ajax_url,
            type: 'POST',
            data: {
                action: 'puzzling_elasticsearch_suggest',
                nonce: puzzlingcrm_ajax_obj.nonce,
                query: query,
                type: this.currentType
            },
            success: (response) => {
                if (response.success) {
                    this.showSuggestions(response.data);
                }
            }
        });
    }
    
    showSuggestions(suggestions) {
        const suggestionsContainer = document.getElementById('search-suggestions');
        
        if (suggestions.length === 0) {
            this.hideSuggestions();
            return;
        }
        
        suggestionsContainer.innerHTML = suggestions.map(suggestion => `
            <div class="suggestion-item" data-text="${suggestion.text}">
                <i class="ri-search-line"></i>
                <span>${suggestion.text}</span>
            </div>
        `).join('');
        
        suggestionsContainer.style.display = 'block';
        
        // Bind suggestion click events
        suggestionsContainer.querySelectorAll('.suggestion-item').forEach(item => {
            item.addEventListener('click', () => {
                this.searchInput.value = item.dataset.text;
                this.hideSuggestions();
                this.performSearch();
            });
        });
    }
    
    hideSuggestions() {
        document.getElementById('search-suggestions').style.display = 'none';
    }
    
    performSearch() {
        if (this.isSearching || this.currentQuery.length < 2) {
            return;
        }
        
        this.isSearching = true;
        this.showLoading();
        this.hideSuggestions();
        
        const filters = this.getActiveFilters();
        
        jQuery.ajax({
            url: puzzlingcrm_ajax_obj.ajax_url,
            type: 'POST',
            data: {
                action: 'puzzling_elasticsearch_search',
                nonce: puzzlingcrm_ajax_obj.nonce,
                query: this.currentQuery,
                type: this.currentType,
                page: this.currentPage,
                per_page: 20,
                filters: filters
            },
            success: (response) => {
                this.isSearching = false;
                this.hideLoading();
                
                if (response.success) {
                    this.displayResults(response.data);
                } else {
                    this.showError('خطا در جستجو: ' + response.data);
                }
            },
            error: () => {
                this.isSearching = false;
                this.hideLoading();
                this.showError('خطا در ارتباط با سرور');
            }
        });
    }
    
    displayResults(data) {
        const resultsList = document.getElementById('search-results-list');
        const pagination = document.getElementById('search-pagination');
        
        if (data.hits.length === 0) {
            this.showEmpty();
            return;
        }
        
        this.hideEmpty();
        
        // Display results
        resultsList.innerHTML = data.hits.map(hit => this.formatSearchResult(hit)).join('');
        
        // Display pagination
        this.displayPagination(data.total, data.hits.length);
        
        // Bind result click events
        this.bindResultEvents();
    }
    
    formatSearchResult(hit) {
        const typeLabels = {
            'project': 'پروژه',
            'task': 'وظیفه',
            'contract': 'قرارداد',
            'lead': 'سرنخ',
            'ticket': 'تیکت',
            'user': 'کاربر'
        };
        
        const typeLabel = typeLabels[hit.type] || hit.type;
        const score = Math.round(hit.score * 100);
        
        let highlightHtml = '';
        if (hit.highlight) {
            if (hit.highlight.title) {
                highlightHtml += `<div class="highlight-title">${hit.highlight.title[0]}</div>`;
            }
            if (hit.highlight.content) {
                highlightHtml += `<div class="highlight-content">${hit.highlight.content[0]}</div>`;
            }
        }
        
        return `
            <div class="search-result-item" data-type="${hit.type}" data-id="${hit.id}">
                <div class="result-header">
                    <div class="result-type">${typeLabel}</div>
                    <div class="result-score">${score}%</div>
                </div>
                <div class="result-title">${hit.title}</div>
                ${highlightHtml}
                <div class="result-meta">
                    <span class="result-date">${this.formatDate(hit.created_date)}</span>
                    ${hit.status ? `<span class="result-status">${hit.status}</span>` : ''}
                    ${hit.priority ? `<span class="result-priority">${hit.priority}</span>` : ''}
                </div>
                <div class="result-actions">
                    <button class="btn-view" data-type="${hit.type}" data-id="${hit.id}">مشاهده</button>
                </div>
            </div>
        `;
    }
    
    displayPagination(total, currentCount) {
        const pagination = document.getElementById('search-pagination');
        const totalPages = Math.ceil(total / 20);
        
        if (totalPages <= 1) {
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
        const endPage = Math.min(totalPages, this.currentPage + 2);
        
        for (let i = startPage; i <= endPage; i++) {
            const activeClass = i === this.currentPage ? 'active' : '';
            paginationHtml += `<button class="page-btn ${activeClass}" data-page="${i}">${i}</button>`;
        }
        
        // Next button
        if (this.currentPage < totalPages) {
            paginationHtml += `<button class="page-btn" data-page="${this.currentPage + 1}">بعدی</button>`;
        }
        
        paginationHtml += '</div>';
        pagination.innerHTML = paginationHtml;
        
        // Bind pagination events
        pagination.querySelectorAll('.page-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                this.currentPage = parseInt(btn.dataset.page);
                this.performSearch();
            });
        });
    }
    
    bindResultEvents() {
        document.querySelectorAll('.btn-view').forEach(btn => {
            btn.addEventListener('click', () => {
                const type = btn.dataset.type;
                const id = btn.dataset.id;
                this.viewResult(type, id);
            });
        });
    }
    
    viewResult(type, id) {
        // Redirect to the appropriate page based on type
        const urls = {
            'project': `admin.php?page=puzzling-projects&action=edit&id=${id}`,
            'task': `admin.php?page=puzzling-tasks&action=edit&id=${id}`,
            'contract': `admin.php?page=puzzling-contracts&action=edit&id=${id}`,
            'lead': `admin.php?page=puzzling-leads&action=edit&id=${id}`,
            'ticket': `admin.php?page=puzzling-tickets&action=edit&id=${id}`,
            'user': `user-edit.php?user_id=${id}`
        };
        
        if (urls[type]) {
            window.location.href = urls[type];
        }
    }
    
    getActiveFilters() {
        const filters = {};
        
        const statusFilter = document.getElementById('filter-status');
        if (statusFilter && statusFilter.value) {
            filters.status = statusFilter.value;
        }
        
        const priorityFilter = document.getElementById('filter-priority');
        if (priorityFilter && priorityFilter.value) {
            filters.priority = priorityFilter.value;
        }
        
        const dateFrom = document.getElementById('filter-date-from').value;
        if (dateFrom) {
            filters.date_from = dateFrom;
        }
        
        const dateTo = document.getElementById('filter-date-to').value;
        if (dateTo) {
            filters.date_to = dateTo;
        }
        
        return filters;
    }
    
    applyFilters() {
        this.currentPage = 1;
        this.performSearch();
    }
    
    clearFilters() {
        document.getElementById('filter-status').value = '';
        document.getElementById('filter-priority').value = '';
        document.getElementById('filter-date-from').value = '';
        document.getElementById('filter-date-to').value = '';
        this.applyFilters();
    }
    
    toggleFilters() {
        const filters = document.getElementById('search-filters');
        const toggle = document.getElementById('search-filters-toggle');
        
        if (filters.style.display === 'none') {
            filters.style.display = 'block';
            toggle.classList.add('active');
        } else {
            filters.style.display = 'none';
            toggle.classList.remove('active');
        }
    }
    
    clearSearch() {
        this.searchInput.value = '';
        this.currentQuery = '';
        this.currentPage = 1;
        this.hideSuggestions();
        this.hideResults();
    }
    
    showLoading() {
        document.querySelector('.search-loading').style.display = 'flex';
    }
    
    hideLoading() {
        document.querySelector('.search-loading').style.display = 'none';
    }
    
    showEmpty() {
        document.querySelector('.search-empty').style.display = 'block';
        document.getElementById('search-results-list').innerHTML = '';
        document.getElementById('search-pagination').innerHTML = '';
    }
    
    hideEmpty() {
        document.querySelector('.search-empty').style.display = 'none';
    }
    
    hideResults() {
        document.getElementById('search-results-list').innerHTML = '';
        document.getElementById('search-pagination').innerHTML = '';
        this.hideEmpty();
    }
    
    showError(message) {
        // You can implement a toast notification here
        console.error('Search error:', message);
    }
    
    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('fa-IR');
    }
    
    loadFilters() {
        // Load dynamic filter options based on current data
        // This would typically make AJAX calls to get available statuses, priorities, etc.
    }
}

// Initialize when DOM is ready
jQuery(document).ready(() => {
    if (typeof puzzlingcrm_ajax_obj !== 'undefined') {
        window.puzzlingAdvancedSearch = new PuzzlingCRM_Advanced_Search();
    }
});
