<?php
/**
 * Canned Responses Management Template
 * @package PuzzlingCRM
 */
if (!defined('ABSPATH')) exit;
if (!current_user_can('manage_options')) return;

$action = isset($_GET['action']) ? sanitize_key($_GET['action']) : 'list';
$response_id = isset($_GET['response_id']) ? intval($_GET['response_id']) : 0;
$response_to_edit = ($response_id > 0) ? get_post($response_id) : null;
?>

<div class="pzl-form-container">
    <h4><i class="ri-chat-quote-line"></i> مدیریت پاسخ‌های آماده</h4>
    <p class="description">پاسخ‌های پرتکرار برای تیکت‌ها را در این بخش تعریف کنید تا سرعت پاسخ‌دهی تیم پشتیبانی افزایش یابد.</p>

    <div class="pzl-positions-manager">
        <div class="pzl-positions-list pzl-card">
            <h5><i class="ri-list-check"></i> لیست پاسخ‌ها</h5>
            <table class="pzl-table">
                <thead>
                    <tr>
                        <th>عنوان پاسخ</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $canned_responses = get_posts(['post_type' => 'pzl_canned_response', 'posts_per_page' => -1, 'post_status' => 'publish']);
                    if (!empty($canned_responses)): 
                        foreach($canned_responses as $response): ?>
                            <tr>
                                <td><?php echo esc_html($response->post_title); ?></td>
                                <td>
                                    <a href="<?php echo esc_url(add_query_arg(['action' => 'edit', 'response_id' => $response->ID])); ?>" class="pzl-button pzl-button-sm">ویرایش</a>
                                    <button class="pzl-button pzl-button-sm delete-canned-response-btn" data-id="<?php echo esc_attr($response->ID); ?>" style="background-color: var(--pzl-danger-color) !important;">حذف</button>
                                </td>
                            </tr>
                        <?php endforeach; 
                    else: ?>
                        <tr>
                            <td colspan="2">هیچ پاسخ آماده‌ای تعریف نشده است.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="pzl-positions-form pzl-card">
            <h5 id="response-form-title"><i class="ri-add-circle-line"></i> <?php echo $response_to_edit ? 'ویرایش پاسخ' : 'افزودن پاسخ جدید'; ?></h5>
            <form id="pzl-canned-response-form" class="pzl-form pzl-ajax-form" data-action="puzzling_manage_canned_response">
                <?php wp_nonce_field('puzzlingcrm-ajax-nonce', 'security'); ?>
                <input type="hidden" name="response_id" value="<?php echo esc_attr($response_id); ?>">
                
                <div class="form-group">
                    <label for="response-title">عنوان پاسخ (برای نمایش در لیست)</label>
                    <input type="text" id="response-title" name="response_title" value="<?php echo $response_to_edit ? esc_attr($response_to_edit->post_title) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label for="response-content">متن کامل پاسخ</label>
                    <?php wp_editor($response_to_edit ? $response_to_edit->post_content : '', 'response_content', ['textarea_name' => 'response_content', 'textarea_rows' => 10]); ?>
                </div>

                <div class="form-submit">
                    <button type="submit" class="pzl-button" data-puzzling-skip-global-handler="true">ذخیره</button>
                    <?php if ($response_to_edit): ?>
                        <a href="<?php echo esc_url(remove_query_arg(['action', 'response_id'])); ?>" class="pzl-button-secondary">انصراف</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('.delete-canned-response-btn').on('click', function() {
        if (!confirm('آیا از حذف این پاسخ آماده مطمئن هستید؟')) {
            return;
        }
        var button = $(this);
        var responseId = button.data('id');
        
        $.ajax({
            url: puzzlingcrm_ajax_obj.ajax_url,
            type: 'POST',
            data: {
                action: 'puzzling_delete_canned_response',
                security: puzzlingcrm_ajax_obj.nonce,
                response_id: responseId
            },
            beforeSend: function() {
                button.closest('tr').css('opacity', '0.5');
            },
            success: function(response) {
                if (response.success) {
                    showPuzzlingAlert('موفق', response.data.message, 'success', true);
                } else {
                    showPuzzlingAlert('خطا', response.data.message, 'error');
                    button.closest('tr').css('opacity', '1');
                }
            },
            error: function() {
                showPuzzlingAlert('خطا', 'خطای سرور رخ داد.', 'error');
                button.closest('tr').css('opacity', '1');
            }
        });
    });
});
</script>