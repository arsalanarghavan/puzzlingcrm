jQuery(document).ready(function($) {
    var puzzling_ajax_nonce = puzzlingcrm_ajax_obj.nonce;
    var searchTimer;

    // --- Live User Search ---
    $('#user-live-search-input').on('keyup', function() {
        var searchInput = $(this);
        var query = searchInput.val();
        var resultsContainer = $('#pzl-users-table-body');
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function() {
            $.ajax({
                url: puzzlingcrm_ajax_obj.ajax_url,
                type: 'POST',
                data: { action: 'puzzling_search_users', security: puzzling_ajax_nonce, query: query },
                beforeSend: function() { resultsContainer.html('<tr><td colspan="5"><div class="pzl-loader" style="margin: 20px auto; width: 30px; height: 30px;"></div></td></tr>'); },
                success: function(response) { if (response.success) { resultsContainer.html(response.data.html); } else { resultsContainer.html('<tr><td colspan="5">خطا در جستجو.</td></tr>'); } },
                error: function() { resultsContainer.html('<tr><td colspan="5">خطای سرور.</td></tr>'); }
            });
        }, 500);
    });
    
    // --- User Deletion ---
    $('body').on('click', '.delete-user-btn', function(e) {
        e.preventDefault();
        var button = $(this);
        var userId = button.data('user-id');
        var nonce = button.data('nonce');
        Swal.fire({
            title: 'آیا مطمئن هستید؟', text: "این عمل غیرقابل بازگشت است و تمام اطلاعات کاربر حذف خواهد شد.", icon: 'warning',
            showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#3085d6', confirmButtonText: 'بله، حذف کن!', cancelButtonText: 'انصراف'
        }).then((result) => {
            if (result.isConfirmed) {
                var userRow = button.closest('tr');
                $.ajax({
                    url: puzzlingcrm_ajax_obj.ajax_url, type: 'POST',
                    data: { action: 'puzzling_delete_user', security: puzzling_ajax_nonce, user_id: userId, nonce: nonce },
                    beforeSend: function() { userRow.css('opacity', '0.5'); },
                    success: function(response) {
                        if (response.success) { userRow.fadeOut(400, function() { $(this).remove(); }); showPuzzlingAlert('موفقیت‌آمیز', response.data.message, 'success'); }
                        else { showPuzzlingAlert('خطا', response.data.message, 'error'); userRow.css('opacity', '1'); }
                    },
                    error: function() { showPuzzlingAlert('خطا', 'یک خطای ناشناخته در سرور رخ داد.', 'error'); userRow.css('opacity', '1'); }
                });
            }
        });
    });
});