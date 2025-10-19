<?php
/**
 * Attachment AJAX Handler
 * @package PuzzlingCRM
 */

if (!defined('ABSPATH')) exit;

class PuzzlingCRM_Attachment_Ajax_Handler {

    public function __construct() {
        add_action('wp_ajax_puzzling_upload_task_attachment', [$this, 'upload_task_attachment']);
        add_action('wp_ajax_puzzling_delete_task_attachment', [$this, 'delete_task_attachment']);
        add_action('wp_ajax_puzzling_get_task_attachments', [$this, 'get_task_attachments']);
    }

    /**
     * Upload Task Attachment
     */
    public function upload_task_attachment() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.']);
        }

        $task_id = isset($_POST['task_id']) ? intval($_POST['task_id']) : 0;
        
        if (!$task_id) {
            wp_send_json_error(['message' => 'شناسه وظیفه نامعتبر است.']);
        }

        if (empty($_FILES['file'])) {
            wp_send_json_error(['message' => 'فایلی انتخاب نشده است.']);
        }

        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        $file = $_FILES['file'];
        
        // Validate file
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'application/zip', 
                          'application/x-rar-compressed', 'application/msword', 
                          'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        
        if (!in_array($file['type'], $allowed_types)) {
            wp_send_json_error(['message' => 'فرمت فایل مجاز نیست.']);
        }

        // Max 10MB
        if ($file['size'] > 10 * 1024 * 1024) {
            wp_send_json_error(['message' => 'حجم فایل بیش از حد مجاز است (حداکثر 10MB).']);
        }

        $attachment_id = media_handle_upload('file', $task_id);
        
        if (is_wp_error($attachment_id)) {
            wp_send_json_error(['message' => 'خطا در آپلود فایل: ' . $attachment_id->get_error_message()]);
        }

        // Add to task attachments meta
        $attachments = get_post_meta($task_id, '_task_attachments', true) ?: [];
        $attachments[] = $attachment_id;
        update_post_meta($task_id, '_task_attachments', $attachments);

        // Get file info
        $file_url = wp_get_attachment_url($attachment_id);
        $file_name = basename(get_attached_file($attachment_id));
        $file_type = wp_check_filetype($file_name);

        wp_send_json_success([
            'message' => 'فایل با موفقیت آپلود شد.',
            'attachment' => [
                'id' => $attachment_id,
                'url' => $file_url,
                'name' => $file_name,
                'type' => $file_type['ext']
            ]
        ]);
    }

    /**
     * Delete Task Attachment
     */
    public function delete_task_attachment() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        
        if (!current_user_can('delete_posts')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.']);
        }

        $task_id = isset($_POST['task_id']) ? intval($_POST['task_id']) : 0;
        $attachment_id = isset($_POST['attachment_id']) ? intval($_POST['attachment_id']) : 0;
        
        if (!$task_id || !$attachment_id) {
            wp_send_json_error(['message' => 'اطلاعات ناقص است.']);
        }

        // Remove from meta
        $attachments = get_post_meta($task_id, '_task_attachments', true) ?: [];
        $attachments = array_filter($attachments, function($id) use ($attachment_id) {
            return $id != $attachment_id;
        });
        update_post_meta($task_id, '_task_attachments', array_values($attachments));

        // Delete file
        wp_delete_attachment($attachment_id, true);

        wp_send_json_success(['message' => 'فایل حذف شد.']);
    }

    /**
     * Get Task Attachments
     */
    public function get_task_attachments() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        
        $task_id = isset($_POST['task_id']) ? intval($_POST['task_id']) : 0;
        
        if (!$task_id) {
            wp_send_json_error(['message' => 'شناسه وظیفه نامعتبر است.']);
        }

        $attachment_ids = get_post_meta($task_id, '_task_attachments', true) ?: [];
        $attachments = [];
        
        foreach ($attachment_ids as $attachment_id) {
            $file_url = wp_get_attachment_url($attachment_id);
            $file_name = basename(get_attached_file($attachment_id));
            $file_type = wp_check_filetype($file_name);
            $file_size = filesize(get_attached_file($attachment_id));
            
            $attachments[] = [
                'id' => $attachment_id,
                'url' => $file_url,
                'name' => $file_name,
                'type' => $file_type['ext'],
                'size' => size_format($file_size)
            ];
        }

        wp_send_json_success(['attachments' => $attachments]);
    }
}

