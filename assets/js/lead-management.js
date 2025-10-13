jQuery(document).ready(function($) {
    const puzzlingcrm_ajax_obj = window.puzzlingcrm_ajax_obj || {
        ajax_url: '',
        nonce: '',
        lang: {}
    };

    // --- Delete Lead Handler ---
    $(document).on('click', '.pzl-delete-lead-btn', function(e) {
        e.preventDefault();
        const button = $(this);
        const leadRow = button.closest('tr');
        const leadId = leadRow.data('lead-id');
        const nonce = button.data('nonce');

        if (typeof Swal === 'undefined') {
            if (!confirm('آیا از حذف این سرنخ مطمئن هستید؟')) {
                return;
            }
        } else {
            Swal.fire({
                title: 'آیا از حذف این سرنخ مطمئن هستید؟',
                text: "این عمل غیرقابل بازگشت است!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'بله، حذف کن!',
                cancelButtonText: 'انصراف'
            }).then((result) => {
                if (!result.isConfirmed) {
                    return;
                }
            });
        }
        
        leadRow.css('opacity', '0.5');
        $.ajax({
            url: puzzlingcrm_ajax_obj.ajax_url,
            type: 'POST',
            data: {
                action: 'puzzling_delete_lead',
                security: nonce,
                lead_id: leadId
            },
            success: function(response) {
                if (response.success) {
                    // Using the global alert function if it exists
                    if (typeof showPuzzlingAlert === 'function') {
                        showPuzzlingAlert('موفق', response.data.message, 'success', response.data);
                    } else {
                        alert(response.data.message);
                        window.location.reload();
                    }
                } else {
                    if (typeof showPuzzlingAlert === 'function') {
                        showPuzzlingAlert('خطا', response.data.message, 'error');
                    } else {
                        alert(response.data.message);
                    }
                    leadRow.css('opacity', '1');
                }
            },
            error: function() {
                if (typeof showPuzzlingAlert === 'function') {
                    showPuzzlingAlert('خطا', 'یک خطای ناشناخته در ارتباط با سرور رخ داد.', 'error');
                } else {
                    alert('یک خطای ناشناخته در ارتباط با سرور رخ داد.');
                }
                leadRow.css('opacity', '1');
            }
        });
    });
});