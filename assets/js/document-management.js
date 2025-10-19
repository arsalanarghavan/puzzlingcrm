/**
 * Document Management System
 * Comprehensive document management with version control
 */

class PuzzlingCRM_Document_Management {
    constructor() {
        this.container = null;
        this.documents = [];
        this.currentPage = 1;
        this.isLoading = false;
        this.filters = {
            project_id: 0,
            task_id: 0,
            category: '',
            search: ''
        };
        
        this.init();
    }
    
    init() {
        this.createDocumentInterface();
        this.bindEvents();
        this.loadDocuments();
    }
    
    createDocumentInterface() {
        if (!document.getElementById('puzzling-document-management')) {
            const documentContainer = document.createElement('div');
            documentContainer.id = 'puzzling-document-management';
            documentContainer.className = 'puzzling-document-management';
            documentContainer.innerHTML = `
                <div class="document-header">
                    <h3>مدیریت اسناد</h3>
                    <div class="document-controls">
                        <button class="btn-upload-document" id="upload-document">
                            <i class="ri-upload-line"></i>
                            آپلود سند
                        </button>
                        <button class="btn-refresh" id="refresh-documents">
                            <i class="ri-refresh-line"></i>
                            به‌روزرسانی
                        </button>
                    </div>
                </div>
                
                <div class="document-filters">
                    <div class="filter-group">
                        <label>پروژه:</label>
                        <select id="document-project-filter">
                            <option value="0">همه پروژه‌ها</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>دسته‌بندی:</label>
                        <select id="document-category-filter">
                            <option value="">همه دسته‌ها</option>
                            <option value="general">عمومی</option>
                            <option value="contract">قرارداد</option>
                            <option value="proposal">پیشنهاد</option>
                            <option value="report">گزارش</option>
                            <option value="presentation">ارائه</option>
                            <option value="image">تصویر</option>
                            <option value="other">سایر</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>جستجو:</label>
                        <input type="text" id="document-search" placeholder="جستجو در اسناد...">
                    </div>
                    <div class="filter-actions">
                        <button class="btn-apply-filters" id="apply-document-filters">اعمال فیلتر</button>
                        <button class="btn-clear-filters" id="clear-document-filters">پاک کردن</button>
                    </div>
                </div>
                
                <div class="document-content">
                    <div class="document-loading" id="document-loading" style="display: none;">
                        <div class="loading-spinner"></div>
                        <span>در حال بارگذاری...</span>
                    </div>
                    
                    <div class="document-empty" id="document-empty" style="display: none;">
                        <i class="ri-file-line"></i>
                        <p>سندی یافت نشد</p>
                    </div>
                    
                    <div class="documents-grid" id="documents-grid"></div>
                    
                    <div class="document-pagination" id="document-pagination"></div>
                </div>
                
                <!-- Upload Document Modal -->
                <div class="document-modal" id="upload-modal" style="display: none;">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h4>آپلود سند جدید</h4>
                            <button class="modal-close" id="close-upload-modal">
                                <i class="ri-close-line"></i>
                            </button>
                        </div>
                        <div class="modal-body">
                            <form id="upload-form" enctype="multipart/form-data">
                                <div class="form-group">
                                    <label for="upload-file">انتخاب فایل *</label>
                                    <input type="file" id="upload-file" name="document" required>
                                    <div class="file-info" id="file-info" style="display: none;"></div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="upload-project">پروژه:</label>
                                    <select id="upload-project">
                                        <option value="0">انتخاب پروژه</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="upload-category">دسته‌بندی:</label>
                                    <select id="upload-category">
                                        <option value="general">عمومی</option>
                                        <option value="contract">قرارداد</option>
                                        <option value="proposal">پیشنهاد</option>
                                        <option value="report">گزارش</option>
                                        <option value="presentation">ارائه</option>
                                        <option value="image">تصویر</option>
                                        <option value="other">سایر</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="upload-description">توضیحات:</label>
                                    <textarea id="upload-description" rows="3" placeholder="توضیحات سند..."></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label for="upload-tags">برچسب‌ها:</label>
                                    <input type="text" id="upload-tags" placeholder="برچسب‌ها را با کاما جدا کنید">
                                </div>
                                
                                <div class="form-group">
                                    <label class="checkbox-label">
                                        <input type="checkbox" id="upload-private">
                                        <span>سند خصوصی</span>
                                    </label>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button class="btn-cancel" id="cancel-upload">انصراف</button>
                            <button class="btn-upload" id="confirm-upload">آپلود</button>
                        </div>
                    </div>
                </div>
                
                <!-- Document Details Modal -->
                <div class="document-modal" id="document-details-modal" style="display: none;">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h4 id="document-details-title">جزئیات سند</h4>
                            <button class="modal-close" id="close-document-details">
                                <i class="ri-close-line"></i>
                            </button>
                        </div>
                        <div class="modal-body" id="document-details-body">
                            <!-- Document details will be loaded here -->
                        </div>
                        <div class="modal-footer">
                            <button class="btn-download" id="download-document">دانلود</button>
                            <button class="btn-edit" id="edit-document">ویرایش</button>
                            <button class="btn-share" id="share-document">اشتراک‌گذاری</button>
                            <button class="btn-delete" id="delete-document">حذف</button>
                            <button class="btn-close" id="close-document-details-btn">بستن</button>
                        </div>
                    </div>
                </div>
            `;
            
            // Insert into page
            const targetElement = document.querySelector('.wrap') || document.querySelector('main') || document.body;
            targetElement.appendChild(documentContainer);
        }
        
        this.container = document.getElementById('puzzling-document-management');
    }
    
    bindEvents() {
        // Upload document
        document.getElementById('upload-document').addEventListener('click', () => {
            this.showUploadModal();
        });
        
        // Refresh
        document.getElementById('refresh-documents').addEventListener('click', () => {
            this.loadDocuments();
        });
        
        // Filters
        document.getElementById('apply-document-filters').addEventListener('click', () => {
            this.applyFilters();
        });
        
        document.getElementById('clear-document-filters').addEventListener('click', () => {
            this.clearFilters();
        });
        
        // Upload modal
        document.getElementById('close-upload-modal').addEventListener('click', () => {
            this.hideUploadModal();
        });
        
        document.getElementById('cancel-upload').addEventListener('click', () => {
            this.hideUploadModal();
        });
        
        document.getElementById('confirm-upload').addEventListener('click', () => {
            this.uploadDocument();
        });
        
        // File input change
        document.getElementById('upload-file').addEventListener('change', (e) => {
            this.handleFileSelect(e);
        });
        
        // Document details modal
        document.getElementById('close-document-details').addEventListener('click', () => {
            this.hideDocumentDetails();
        });
        
        document.getElementById('close-document-details-btn').addEventListener('click', () => {
            this.hideDocumentDetails();
        });
        
        document.getElementById('download-document').addEventListener('click', () => {
            this.downloadDocument();
        });
        
        document.getElementById('edit-document').addEventListener('click', () => {
            this.editDocument();
        });
        
        document.getElementById('share-document').addEventListener('click', () => {
            this.shareDocument();
        });
        
        document.getElementById('delete-document').addEventListener('click', () => {
            this.deleteDocument();
        });
        
        // Click outside to close modals
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('document-modal')) {
                this.hideUploadModal();
                this.hideDocumentDetails();
            }
        });
    }
    
    loadDocuments() {
        if (this.isLoading) return;
        
        this.isLoading = true;
        this.showLoading();
        
        jQuery.ajax({
            url: puzzlingcrm_ajax_obj.ajax_url,
            type: 'GET',
            data: {
                action: 'puzzling_get_documents',
                nonce: puzzlingcrm_ajax_obj.nonce,
                ...this.filters,
                page: this.currentPage,
                per_page: 20
            },
            success: (response) => {
                this.isLoading = false;
                this.hideLoading();
                
                if (response.success) {
                    this.documents = response.data.documents;
                    this.displayDocuments();
                    this.updatePagination(response.data);
                } else {
                    this.showError('خطا در بارگذاری اسناد');
                }
            },
            error: () => {
                this.isLoading = false;
                this.hideLoading();
                this.showError('خطا در ارتباط با سرور');
            }
        });
    }
    
    displayDocuments() {
        const documentsGrid = document.getElementById('documents-grid');
        
        if (this.documents.length === 0) {
            this.showEmpty();
            return;
        }
        
        this.hideEmpty();
        
        documentsGrid.innerHTML = this.documents.map(document => this.createDocumentElement(document)).join('');
        
        // Bind document events
        this.bindDocumentEvents();
    }
    
    createDocumentElement(document) {
        const categoryClass = `category-${document.category}`;
        const privateClass = document.is_private ? 'private' : '';
        
        return `
            <div class="document-card ${categoryClass} ${privateClass}" data-document-id="${document.id}">
                <div class="document-icon">
                    <i class="${document.file_icon}"></i>
                </div>
                
                <div class="document-info">
                    <div class="document-title" title="${document.original_name}">${document.original_name}</div>
                    <div class="document-meta">
                        <span class="document-size">${document.formatted_file_size}</span>
                        <span class="document-category">${document.category_label}</span>
                        <span class="document-date">${document.time_ago}</span>
                    </div>
                    <div class="document-description">${document.description || 'بدون توضیحات'}</div>
                    <div class="document-tags">
                        ${document.tags.map(tag => `<span class="document-tag">${tag}</span>`).join('')}
                    </div>
                </div>
                
                <div class="document-actions">
                    <button class="btn-download" data-document-id="${document.id}" title="دانلود">
                        <i class="ri-download-line"></i>
                    </button>
                    ${document.can_edit ? `
                        <button class="btn-edit" data-document-id="${document.id}" title="ویرایش">
                            <i class="ri-edit-line"></i>
                        </button>
                    ` : ''}
                    ${document.can_share ? `
                        <button class="btn-share" data-document-id="${document.id}" title="اشتراک‌گذاری">
                            <i class="ri-share-line"></i>
                        </button>
                    ` : ''}
                    ${document.can_delete ? `
                        <button class="btn-delete" data-document-id="${document.id}" title="حذف">
                            <i class="ri-delete-bin-line"></i>
                        </button>
                    ` : ''}
                </div>
            </div>
        `;
    }
    
    bindDocumentEvents() {
        // Document click for details
        document.querySelectorAll('.document-card').forEach(card => {
            card.addEventListener('click', (e) => {
                if (!e.target.closest('.document-actions')) {
                    const documentId = e.currentTarget.dataset.documentId;
                    this.showDocumentDetails(documentId);
                }
            });
        });
        
        // Action buttons
        document.querySelectorAll('.btn-download').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const documentId = e.currentTarget.dataset.documentId;
                this.downloadDocumentById(documentId);
            });
        });
        
        document.querySelectorAll('.btn-edit').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const documentId = e.currentTarget.dataset.documentId;
                this.editDocumentById(documentId);
            });
        });
        
        document.querySelectorAll('.btn-share').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const documentId = e.currentTarget.dataset.documentId;
                this.shareDocumentById(documentId);
            });
        });
        
        document.querySelectorAll('.btn-delete').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const documentId = e.currentTarget.dataset.documentId;
                this.deleteDocumentById(documentId);
            });
        });
    }
    
    showUploadModal() {
        document.getElementById('upload-modal').style.display = 'flex';
        this.loadProjects();
    }
    
    hideUploadModal() {
        document.getElementById('upload-modal').style.display = 'none';
        document.getElementById('upload-form').reset();
        document.getElementById('file-info').style.display = 'none';
    }
    
    handleFileSelect(e) {
        const file = e.target.files[0];
        if (!file) return;
        
        const fileInfo = document.getElementById('file-info');
        const fileSize = this.formatFileSize(file.size);
        const fileType = file.type;
        
        fileInfo.innerHTML = `
            <div class="file-details">
                <strong>${file.name}</strong><br>
                <span>حجم: ${fileSize}</span><br>
                <span>نوع: ${fileType}</span>
            </div>
        `;
        fileInfo.style.display = 'block';
    }
    
    uploadDocument() {
        const form = document.getElementById('upload-form');
        const formData = new FormData(form);
        
        // Add additional data
        formData.append('action', 'puzzling_upload_document');
        formData.append('nonce', puzzlingcrm_ajax_obj.nonce);
        formData.append('project_id', document.getElementById('upload-project').value);
        formData.append('category', document.getElementById('upload-category').value);
        formData.append('description', document.getElementById('upload-description').value);
        formData.append('tags', document.getElementById('upload-tags').value.split(',').map(tag => tag.trim()).filter(tag => tag));
        formData.append('is_private', document.getElementById('upload-private').checked);
        
        // Show upload progress
        const uploadBtn = document.getElementById('confirm-upload');
        const originalText = uploadBtn.textContent;
        uploadBtn.textContent = 'در حال آپلود...';
        uploadBtn.disabled = true;
        
        jQuery.ajax({
            url: puzzlingcrm_ajax_obj.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: (response) => {
                uploadBtn.textContent = originalText;
                uploadBtn.disabled = false;
                
                if (response.success) {
                    this.hideUploadModal();
                    this.loadDocuments();
                    this.showSuccess(response.data.message);
                } else {
                    this.showError(response.data);
                }
            },
            error: () => {
                uploadBtn.textContent = originalText;
                uploadBtn.disabled = false;
                this.showError('خطا در آپلود سند');
            }
        });
    }
    
    showDocumentDetails(documentId) {
        const document = this.documents.find(d => d.id == documentId);
        if (!document) return;
        
        document.getElementById('document-details-title').textContent = document.original_name;
        
        const detailsBody = document.getElementById('document-details-body');
        detailsBody.innerHTML = `
            <div class="document-details">
                <div class="detail-group">
                    <label>نام فایل:</label>
                    <p>${document.original_name}</p>
                </div>
                
                <div class="detail-group">
                    <label>حجم:</label>
                    <p>${document.formatted_file_size}</p>
                </div>
                
                <div class="detail-group">
                    <label>نوع:</label>
                    <p>${document.file_type}</p>
                </div>
                
                <div class="detail-group">
                    <label>دسته‌بندی:</label>
                    <p>${document.category_label}</p>
                </div>
                
                <div class="detail-group">
                    <label>توضیحات:</label>
                    <p>${document.description || 'بدون توضیحات'}</p>
                </div>
                
                <div class="detail-group">
                    <label>برچسب‌ها:</label>
                    <div class="tags-list">
                        ${document.tags.map(tag => `<span class="tag">${tag}</span>`).join('')}
                    </div>
                </div>
                
                <div class="detail-group">
                    <label>تعداد دانلود:</label>
                    <p>${document.download_count}</p>
                </div>
                
                <div class="detail-group">
                    <label>تاریخ ایجاد:</label>
                    <p>${document.formatted_created_at}</p>
                </div>
                
                <div class="detail-group">
                    <label>آخرین به‌روزرسانی:</label>
                    <p>${document.formatted_updated_at}</p>
                </div>
            </div>
        `;
        
        // Store current document ID for actions
        document.getElementById('download-document').dataset.documentId = documentId;
        document.getElementById('edit-document').dataset.documentId = documentId;
        document.getElementById('share-document').dataset.documentId = documentId;
        document.getElementById('delete-document').dataset.documentId = documentId;
        
        document.getElementById('document-details-modal').style.display = 'flex';
    }
    
    hideDocumentDetails() {
        document.getElementById('document-details-modal').style.display = 'none';
    }
    
    downloadDocumentById(documentId) {
        const downloadUrl = `${puzzlingcrm_ajax_obj.ajax_url}?action=puzzling_download_document&document_id=${documentId}&nonce=${puzzlingcrm_ajax_obj.nonce}`;
        window.open(downloadUrl, '_blank');
    }
    
    downloadDocument() {
        const documentId = document.getElementById('download-document').dataset.documentId;
        this.downloadDocumentById(documentId);
    }
    
    editDocumentById(documentId) {
        // Simple edit implementation
        const document = this.documents.find(d => d.id == documentId);
        if (!document) return;
        
        const newDescription = prompt('توضیحات جدید:', document.description);
        if (newDescription !== null) {
            this.updateDocument(documentId, { description: newDescription });
        }
    }
    
    editDocument() {
        const documentId = document.getElementById('edit-document').dataset.documentId;
        this.editDocumentById(documentId);
    }
    
    shareDocumentById(documentId) {
        // Simple share implementation
        const shareUrl = `${window.location.origin}${window.location.pathname}?document_id=${documentId}`;
        navigator.clipboard.writeText(shareUrl).then(() => {
            this.showSuccess('لینک اشتراک‌گذاری کپی شد');
        });
    }
    
    shareDocument() {
        const documentId = document.getElementById('share-document').dataset.documentId;
        this.shareDocumentById(documentId);
    }
    
    deleteDocumentById(documentId) {
        if (!confirm('آیا مطمئن هستید که می‌خواهید این سند را حذف کنید؟')) return;
        
        jQuery.ajax({
            url: puzzlingcrm_ajax_obj.ajax_url,
            type: 'POST',
            data: {
                action: 'puzzling_delete_document',
                nonce: puzzlingcrm_ajax_obj.nonce,
                document_id: documentId
            },
            success: (response) => {
                if (response.success) {
                    this.hideDocumentDetails();
                    this.loadDocuments();
                    this.showSuccess(response.data.message);
                } else {
                    this.showError(response.data);
                }
            }
        });
    }
    
    deleteDocument() {
        const documentId = document.getElementById('delete-document').dataset.documentId;
        this.deleteDocumentById(documentId);
    }
    
    updateDocument(documentId, data) {
        jQuery.ajax({
            url: puzzlingcrm_ajax_obj.ajax_url,
            type: 'POST',
            data: {
                action: 'puzzling_update_document',
                nonce: puzzlingcrm_ajax_obj.nonce,
                document_id: documentId,
                ...data
            },
            success: (response) => {
                if (response.success) {
                    this.loadDocuments();
                    this.showSuccess(response.data.message);
                } else {
                    this.showError(response.data);
                }
            }
        });
    }
    
    applyFilters() {
        this.filters = {
            project_id: document.getElementById('document-project-filter').value,
            task_id: 0,
            category: document.getElementById('document-category-filter').value,
            search: document.getElementById('document-search').value
        };
        
        this.currentPage = 1;
        this.loadDocuments();
    }
    
    clearFilters() {
        document.getElementById('document-project-filter').value = '0';
        document.getElementById('document-category-filter').value = '';
        document.getElementById('document-search').value = '';
        
        this.filters = {
            project_id: 0,
            task_id: 0,
            category: '',
            search: ''
        };
        
        this.currentPage = 1;
        this.loadDocuments();
    }
    
    loadProjects() {
        // Load projects for dropdown
        jQuery.ajax({
            url: puzzlingcrm_ajax_obj.ajax_url,
            type: 'GET',
            data: {
                action: 'puzzling_get_projects',
                nonce: puzzlingcrm_ajax_obj.nonce
            },
            success: (response) => {
                if (response.success) {
                    this.populateProjectSelects(response.data);
                }
            }
        });
    }
    
    populateProjectSelects(projects) {
        const selects = [
            'document-project-filter',
            'upload-project'
        ];
        
        selects.forEach(selectId => {
            const select = document.getElementById(selectId);
            if (select) {
                const currentValue = select.value;
                select.innerHTML = '<option value="0">انتخاب پروژه</option>';
                
                projects.forEach(project => {
                    const option = document.createElement('option');
                    option.value = project.ID;
                    option.textContent = project.post_title;
                    select.appendChild(option);
                });
                
                select.value = currentValue;
            }
        });
    }
    
    formatFileSize(bytes) {
        const units = ['B', 'KB', 'MB', 'GB'];
        const bytes = Math.max(bytes, 0);
        const pow = Math.floor((bytes ? Math.log(bytes) : 0) / Math.log(1024));
        const pow = Math.min(pow, units.length - 1);
        
        const bytes = bytes / Math.pow(1024, pow);
        
        return Math.round(bytes, 2) + ' ' + units[pow];
    }
    
    updatePagination(data) {
        const pagination = document.getElementById('document-pagination');
        
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
                this.loadDocuments();
            });
        });
    }
    
    showLoading() {
        document.getElementById('document-loading').style.display = 'flex';
    }
    
    hideLoading() {
        document.getElementById('document-loading').style.display = 'none';
    }
    
    showEmpty() {
        document.getElementById('document-empty').style.display = 'block';
    }
    
    hideEmpty() {
        document.getElementById('document-empty').style.display = 'none';
    }
    
    showSuccess(message) {
        console.log('Success:', message);
    }
    
    showError(message) {
        console.error('Error:', message);
    }
}

// Initialize when DOM is ready
jQuery(document).ready(() => {
    if (typeof puzzlingcrm_ajax_obj !== 'undefined') {
        window.puzzlingDocumentManagement = new PuzzlingCRM_Document_Management();
    }
});
