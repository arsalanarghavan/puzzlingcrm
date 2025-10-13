jQuery(document).ready(function($) {
    // --- SMS Modal Logic ---
    $('body').on('click', '.send-sms-btn', function() {
        var userId = $(this).data('user-id');
        var userName = $(this).data('user-name');
    
        $('#sms-modal-user-name').text(userName);
        $('#sms-modal-user-id').val(userId);
    
        $('#pzl-sms-modal-backdrop, #pzl-sms-modal-wrap').fadeIn(200);
        $('#sms_message').focus();
    });

    function closeSmsModal() {
        $('#pzl-sms-modal-backdrop, #pzl-sms-modal-wrap').fadeOut(200);
        $('#pzl-send-sms-form')[0].reset();
    }

    $('body').on('click', '#pzl-close-sms-modal-btn, #pzl-sms-modal-backdrop', function(e) {
        if ($(e.target).is('#pzl-close-sms-modal-btn') || $(e.target).is('#pzl-sms-modal-backdrop')) {
            closeSmsModal();
        }
    });

    $('#pzl-send-sms-form').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        var submitButton = form.find('button[type="submit"]');
        var originalButtonHtml = submitButton.html();
        var message = $('#sms_message').val();
        var userId = $('#sms-modal-user-id').val();

        $.ajax({
            url: puzzlingcrm_ajax_obj.ajax_url,
            type: 'POST',
            data: {
                action: 'puzzling_send_custom_sms',
                security: puzzlingcrm_ajax_obj.nonce,
                user_id: userId,
                message: message
            },
            beforeSend: function() {
                submitButton.html('<i class="fas fa-spinner fa-spin"></i> در حال ارسال...').prop('disabled', true);
            },
            success: function(response) {
                if (response.success) {
                    showPuzzlingAlert('موفق', response.data.message, 'success');
                    closeSmsModal();
                } else {
                    showPuzzlingAlert('خطا', response.data.message, 'error');
                }
            },
            error: function() {
                showPuzzlingAlert('خطا', 'خطای سرور.', 'error');
            },
            complete: function() {
                submitButton.html(originalButtonHtml).prop('disabled', false);
            }
        });
    });
});