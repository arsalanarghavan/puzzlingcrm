/**
 * Email Sender Script
 * @package PuzzlingCRM
 */

jQuery(document).ready(function($) {
    
    /**
     * Send Contract Email
     */
    $(document).on('click', '.send-contract-email', function(e) {
        e.preventDefault();
        
        const contractId = $(this).data('contract-id');
        
        Swal.fire({
            title: 'ارسال قرارداد',
            html: `
                <div class="text-start">
                    <label class="form-label">ایمیل مشتری:</label>
                    <input type="email" id="contract-email" class="form-control" placeholder="email@example.com">
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'ارسال',
            cancelButtonText: 'انصراف',
            preConfirm: () => {
                const email = $('#contract-email').val();
                if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                    Swal.showValidationMessage('لطفاً یک ایمیل معتبر وارد کنید');
                    return false;
                }
                return email;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                sendContractEmail(contractId, result.value);
            }
        });
    });
    
    /**
     * Send Invoice Email
     */
    $(document).on('click', '.send-invoice-email', function(e) {
        e.preventDefault();
        
        const invoiceId = $(this).data('invoice-id');
        
        Swal.fire({
            title: 'ارسال پیش‌فاکتور',
            html: `
                <div class="text-start">
                    <label class="form-label">ایمیل مشتری:</label>
                    <input type="email" id="invoice-email" class="form-control" placeholder="email@example.com">
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'ارسال',
            cancelButtonText: 'انصراف',
            preConfirm: () => {
                const email = $('#invoice-email').val();
                if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                    Swal.showValidationMessage('لطفاً یک ایمیل معتبر وارد کنید');
                    return false;
                }
                return email;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                sendInvoiceEmail(invoiceId, result.value);
            }
        });
    });
    
    /**
     * Send Contract Email AJAX
     */
    function sendContractEmail(contractId, email) {
        Swal.fire({
            title: 'در حال ارسال...',
            html: '<div class="spinner-border text-primary"></div>',
            showConfirmButton: false,
            allowOutsideClick: false
        });
        
        $.ajax({
            url: puzzlingcrm_ajax_obj.ajax_url,
            type: 'POST',
            data: {
                action: 'puzzling_send_contract_email',
                security: puzzlingcrm_ajax_obj.nonce,
                contract_id: contractId,
                email: email
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'ارسال شد!',
                        text: 'قرارداد به ایمیل مشتری ارسال شد',
                        showConfirmButton: false,
                        timer: 2000
                    });
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
                    text: 'خطا در ارتباط با سرور'
                });
            }
        });
    }
    
    /**
     * Send Invoice Email AJAX
     */
    function sendInvoiceEmail(invoiceId, email) {
        Swal.fire({
            title: 'در حال ارسال...',
            html: '<div class="spinner-border text-primary"></div>',
            showConfirmButton: false,
            allowOutsideClick: false
        });
        
        $.ajax({
            url: puzzlingcrm_ajax_obj.ajax_url,
            type: 'POST',
            data: {
                action: 'puzzling_send_invoice_email',
                security: puzzlingcrm_ajax_obj.nonce,
                invoice_id: invoiceId,
                email: email
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'ارسال شد!',
                        text: 'پیش‌فاکتور به ایمیل مشتری ارسال شد',
                        showConfirmButton: false,
                        timer: 2000
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

