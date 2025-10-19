/**
 * PDF Generator Client-side Script
 * @package PuzzlingCRM
 */

jQuery(document).ready(function($) {
    
    /**
     * Generate Contract PDF
     */
    $(document).on('click', '.generate-contract-pdf', function(e) {
        e.preventDefault();
        
        const $btn = $(this);
        const contractId = $btn.data('contract-id');
        
        if (!contractId) {
            Swal.fire({
                icon: 'error',
                title: 'خطا',
                text: 'شناسه قرارداد یافت نشد.'
            });
            return;
        }
        
        // Show loading
        Swal.fire({
            title: 'در حال ایجاد PDF...',
            html: 'لطفاً صبر کنید<br><div class="spinner-border text-primary mt-3"></div>',
            allowOutsideClick: false,
            showConfirmButton: false
        });
        
        $.ajax({
            url: puzzlingcrm_ajax_obj.ajax_url,
            type: 'POST',
            data: {
                action: 'puzzling_generate_contract_pdf',
                security: puzzlingcrm_ajax_obj.nonce,
                contract_id: contractId
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'موفق!',
                        html: 'فایل PDF ایجاد شد.<br><a href="' + response.data.pdf_url + '" target="_blank" class="btn btn-primary mt-3"><i class="ri-download-line me-1"></i>دانلود PDF</a>',
                        showConfirmButton: true,
                        confirmButtonText: 'بستن'
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'خطا',
                        text: response.data.message || 'خطا در ایجاد PDF'
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
    });
    
    /**
     * Generate Invoice PDF
     */
    $(document).on('click', '.generate-invoice-pdf', function(e) {
        e.preventDefault();
        
        const $btn = $(this);
        const invoiceId = $btn.data('invoice-id');
        
        if (!invoiceId) {
            Swal.fire({
                icon: 'error',
                title: 'خطا',
                text: 'شناسه پیش‌فاکتور یافت نشد.'
            });
            return;
        }
        
        Swal.fire({
            title: 'در حال ایجاد PDF...',
            html: 'لطفاً صبر کنید<br><div class="spinner-border text-primary mt-3"></div>',
            allowOutsideClick: false,
            showConfirmButton: false
        });
        
        $.ajax({
            url: puzzlingcrm_ajax_obj.ajax_url,
            type: 'POST',
            data: {
                action: 'puzzling_generate_invoice_pdf',
                security: puzzlingcrm_ajax_obj.nonce,
                invoice_id: invoiceId
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'موفق!',
                        html: 'فایل PDF ایجاد شد.<br><a href="' + response.data.pdf_url + '" target="_blank" class="btn btn-primary mt-3"><i class="ri-download-line me-1"></i>دانلود PDF</a>',
                        showConfirmButton: true,
                        confirmButtonText: 'بستن'
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'خطا',
                        text: response.data.message || 'خطا در ایجاد PDF'
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
    });
});

