/**
 * Import/Export Functionality
 * @package PuzzlingCRM
 */

jQuery(document).ready(function($) {
    
    /**
     * Export Button Click
     */
    $(document).on('click', '.btn-export', function(e) {
        e.preventDefault();
        
        const exportType = $(this).data('export-type');
        const action = 'puzzling_export_' + exportType;
        
        Swal.fire({
            title: 'در حال آماده‌سازی...',
            html: '<div class="spinner-border text-success"></div>',
            showConfirmButton: false,
            allowOutsideClick: false
        });
        
        $.ajax({
            url: puzzlingcrm_ajax_obj.ajax_url,
            type: 'POST',
            data: {
                action: action,
                security: puzzlingcrm_ajax_obj.nonce
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'آماده شد!',
                        html: response.data.message + '<br><a href="' + response.data.file_url + '" class="btn btn-success mt-3" download><i class="ri-download-line me-1"></i>دانلود فایل</a>',
                        showConfirmButton: true
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'خطا',
                        text: response.data.message
                    });
                }
            }
        });
    });
    
    /**
     * Import Button Click
     */
    $(document).on('click', '.btn-import', function(e) {
        e.preventDefault();
        
        const importType = $(this).data('import-type');
        
        Swal.fire({
            title: 'وارد کردن ' + importType,
            html: `
                <div class="text-start">
                    <label class="form-label">فایل CSV:</label>
                    <input type="file" id="import-file" class="form-control" accept=".csv">
                    <small class="text-muted d-block mt-2">
                        فرمت: نام، ایمیل، تلفن، منبع
                    </small>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'وارد کردن',
            cancelButtonText: 'انصراف',
            preConfirm: () => {
                const fileInput = document.getElementById('import-file');
                if (!fileInput.files.length) {
                    Swal.showValidationMessage('لطفاً یک فایل انتخاب کنید');
                    return false;
                }
                return fileInput.files[0];
            }
        }).then((result) => {
            if (result.isConfirmed) {
                importData(importType, result.value);
            }
        });
    });
    
    /**
     * Import Data Function
     */
    function importData(type, file) {
        const formData = new FormData();
        formData.append('action', 'puzzling_import_' + type);
        formData.append('security', puzzlingcrm_ajax_obj.nonce);
        formData.append('import_file', file);
        
        Swal.fire({
            title: 'در حال وارد کردن...',
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
                    let errorHtml = '';
                    if (response.data.errors && response.data.errors.length > 0) {
                        errorHtml = '<div class="alert alert-warning mt-3 text-start"><strong>خطاها:</strong><ul class="mb-0">';
                        response.data.errors.forEach(err => {
                            errorHtml += '<li>' + err + '</li>';
                        });
                        errorHtml += '</ul></div>';
                    }
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'موفق!',
                        html: response.data.message + errorHtml,
                        confirmButtonText: 'باشه'
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'خطا',
                        text: response.data.message
                    });
                }
            }
        });
    }
});

