<?php
/**
 * PuzzlingCRM Ticket AJAX Handler
 *
 * @package PuzzlingCRM
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class PuzzlingCRM_Ticket_Ajax_Handler {

    public function __construct() {
        add_action('wp_ajax_puzzling_new_ticket', [$this, 'ajax_new_ticket']);
        add_action('wp_ajax_puzzling_ticket_reply', [$this, 'ajax_ticket_reply']);
        add_action('wp_ajax_puzzling_convert_ticket_to_task', [$this, 'ajax_convert_ticket_to_task']);
        add_action('wp_ajax_puzzling_submit_ticket_rating', [$this, 'ajax_submit_ticket_rating']);
        add_action('wp_ajax_nopriv_puzzling_submit_ticket_rating', [$this, 'ajax_submit_ticket_rating']);
        add_action('wp_ajax_puzzling_get_canned_response', [$this, 'ajax_get_canned_response']);
    }

    private function notify_all_admins($title, $args) {
        $admins = get_users(['role__in' => ['administrator', 'system_manager'], 'fields' => 'ID']);
        foreach ($admins as $admin_id) {
            $notification_args = array_merge($args, ['user_id' => $admin_id]);
            PuzzlingCRM_Logger::add($title, $notification_args);
        }
    }

    public function ajax_new_ticket() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        if ( ! is_user_logged_in() ) {
            wp_send_json_error(['message' => 'برای ارسال تیکت باید وارد شوید.']);
        }
    
        $title = sanitize_text_field($_POST['ticket_title']);
        $content = wp_kses_post($_POST['ticket_content']);
        $department_id = intval($_POST['department']);
        $priority_id = intval($_POST['ticket_priority']);
        
        if (empty($title) || empty($content) || empty($department_id) || empty($priority_id)) {
            wp_send_json_error(['message' => 'عنوان، دپارتمان، اولویت و متن تیکت الزامی هستند.']);
        }
    
        $ticket_id = wp_insert_post([
            'post_title' => $title,
            'post_content' => $content,
            'post_type' => 'ticket',
            'post_status' => 'publish',
            'post_author' => get_current_user_id(),
        ]);
        
        if (!is_wp_error($ticket_id)) {
            wp_set_object_terms($ticket_id, 'open', 'ticket_status');
            wp_set_object_terms($ticket_id, $department_id, 'organizational_position');
            wp_set_object_terms($ticket_id, $priority_id, 'ticket_priority');
    
            if (!empty($_FILES['ticket_attachments'])) {
                $attachment_ids = [];
                $files = $_FILES['ticket_attachments'];
                $allowed_mime_types = ['image/jpeg', 'image/png', 'application/pdf', 'application/zip', 'application/x-rar-compressed'];
                
                foreach ($files['name'] as $key => $value) {
                    if ($files['name'][$key]) {
                        if ($files['size'][$key] > 5 * 1024 * 1024) { 
                            continue;
                        }
                        if (!in_array($files['type'][$key], $allowed_mime_types)) {
                            continue;
                        }

                        $_FILES = ["ticket_attachment_single" => [
                            'name' => $files['name'][$key],
                            'type' => $files['type'][$key],
                            'tmp_name' => $files['tmp_name'][$key],
                            'error' => $files['error'][$key],
                            'size' => $files['size'][$key]
                        ]];
                        $attachment_id = media_handle_upload("ticket_attachment_single", $ticket_id);
                        if (!is_wp_error($attachment_id)) {
                            $attachment_ids[] = $attachment_id;
                        }
                    }
                }
                if (!empty($attachment_ids)) {
                    update_post_meta($ticket_id, '_ticket_attachments', $attachment_ids);
                }
            }
    
            $users_in_dept = get_users(['tax_query' => [['taxonomy' => 'organizational_position', 'field' => 'term_id', 'terms' => $department_id]]]);
            foreach($users_in_dept as $user) {
                PuzzlingCRM_Logger::add('تیکت جدید در دپارتمان شما', ['content' => sprintf("تیکت جدیدی با موضوع '%s' ثبت شد.", $title), 'type' => 'notification', 'object_id' => $ticket_id, 'user_id' => $user->ID]);
            }

            wp_send_json_success(['message' => 'تیکت شما با موفقیت ثبت شد.', 'reload' => true]);
        } else {
            wp_send_json_error(['message' => 'خطا در ثبت تیکت.']);
        }
    }
    
    public function ajax_ticket_reply() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        if ( ! is_user_logged_in() ) {
            wp_send_json_error(['message' => 'برای پاسخ به تیکت باید وارد شوید.']);
        }
    
        $ticket_id = intval($_POST['ticket_id']);
        $comment_content = wp_kses_post($_POST['comment']);
        $ticket = get_post($ticket_id);
        $current_user = wp_get_current_user();
    
        if ( !puzzling_can_user_view_ticket($ticket_id, $current_user->ID) || empty($comment_content) ) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز یا متن پاسخ خالی است.']);
        }
    
        $comment_id = wp_insert_comment([
            'comment_post_ID' => $ticket_id,
            'comment_author' => $current_user->display_name,
            'comment_author_email' => $current_user->user_email,
            'comment_content' => $comment_content,
            'user_id' => $current_user->ID,
            'comment_approved' => 1
        ]);
    
        if (is_wp_error($comment_id)) {
            wp_send_json_error(['message' => 'خطا در ثبت پاسخ.']);
        }
    
        if (!empty($_FILES['reply_attachments'])) {
            $attachment_ids = [];
            $files = $_FILES['reply_attachments'];
            $allowed_mime_types = ['image/jpeg', 'image/png', 'application/pdf', 'application/zip', 'application/x-rar-compressed'];

            foreach ($files['name'] as $key => $value) {
                if ($files['name'][$key]) {
                    if ($files['size'][$key] > 5 * 1024 * 1024 || !in_array($files['type'][$key], $allowed_mime_types)) {
                        continue;
                    }
                    $_FILES = ["reply_attachment_single" => ['name' => $files['name'][$key],'type' => $files['type'][$key],'tmp_name' => $files['tmp_name'][$key],'error' => $files['error'][$key],'size' => $files['size'][$key]]];
                    $attachment_id = media_handle_upload("reply_attachment_single", 0);
                    if (!is_wp_error($attachment_id)) {
                        $attachment_ids[] = $attachment_id;
                    }
                }
            }
            if (!empty($attachment_ids)) {
                add_comment_meta($comment_id, '_reply_attachments', $attachment_ids);
            }
        }
    
        $is_staff_reply = current_user_can('edit_others_posts');
    
        if ( $is_staff_reply ) {
            if (isset($_POST['ticket_status'])) wp_set_object_terms($ticket_id, sanitize_key($_POST['ticket_status']), 'ticket_status');
            if (isset($_POST['department'])) wp_set_object_terms($ticket_id, intval($_POST['department']), 'organizational_position');
            if (isset($_POST['assigned_to'])) update_post_meta($ticket_id, '_assigned_to', intval($_POST['assigned_to']));
            if (isset($_POST['ticket_priority'])) wp_set_object_terms($ticket_id, intval($_POST['ticket_priority']), 'ticket_priority');

            PuzzlingCRM_Logger::add(__('پاسخ به تیکت شما', 'puzzlingcrm'), ['content' => sprintf(__("پشتیبانی به تیکت شما با موضوع '%s' پاسخ داد.", 'puzzlingcrm'), $ticket->post_title), 'type' => 'notification', 'user_id' => $ticket->post_author, 'object_id' => $ticket_id]);
        } else {
            wp_set_object_terms( $ticket_id, 'in-progress', 'ticket_status' );
            $assigned_to_id = get_post_meta($ticket_id, '_assigned_to', true);
            if ($assigned_to_id) {
                PuzzlingCRM_Logger::add(__('پاسخ مشتری به تیکت', 'puzzlingcrm'), ['content' => sprintf(__("مشتری به تیکت '%s' پاسخ داد.", 'puzzlingcrm'), $ticket->post_title), 'type' => 'notification', 'object_id' => $ticket_id, 'user_id' => $assigned_to_id]);
            } else {
                $this->notify_all_admins(__('پاسخ مشتری به تیکت', 'puzzlingcrm'), ['content' => sprintf(__("مشتری به تیکت '%s' پاسخ داد.", 'puzzlingcrm'), $ticket->post_title), 'type' => 'notification', 'object_id' => $ticket_id]);
            }
        }
        
        wp_send_json_success(['message' => 'پاسخ شما با موفقیت ثبت شد.', 'reload' => true]);
    }

    public function ajax_convert_ticket_to_task() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.']);
        }
    
        $ticket_id = intval($_POST['ticket_id']);
        $ticket = get_post($ticket_id);
    
        if (!$ticket || $ticket->post_type !== 'ticket') {
            wp_send_json_error(['message' => 'تیکت یافت نشد.']);
        }
    
        $existing_task = get_posts(['post_type' => 'task', 'meta_key' => '_created_from_ticket', 'meta_value' => $ticket_id, 'posts_per_page' => 1]);
        if (!empty($existing_task)) {
            wp_send_json_error(['message' => 'یک وظیفه از قبل برای این تیکت ایجاد شده است.']);
        }
    
        $task_id = wp_insert_post([
            'post_title' => '[تیکت] ' . $ticket->post_title,
            'post_content' => 'این وظیفه از تیکت شماره ' . $ticket_id . " ایجاد شده است.\n\n" . $ticket->post_content,
            'post_type' => 'task',
            'post_status' => 'publish',
            'post_author' => get_current_user_id(),
        ]);
    
        if (is_wp_error($task_id)) {
            wp_send_json_error(['message' => 'خطا در ایجاد وظیفه.']);
        }
    
        update_post_meta($task_id, '_created_from_ticket', $ticket_id);
        wp_set_object_terms($task_id, 'to-do', 'task_status');
    
        wp_insert_comment([
            'comment_post_ID' => $ticket_id,
            'comment_content' => 'این تیکت به وظیفه شماره ' . $task_id . ' تبدیل شد.',
            'user_id' => get_current_user_id(),
            'comment_author' => 'سیستم',
            'comment_approved' => 1,
        ]);
    
        wp_send_json_success(['message' => 'تیکت با موفقیت به وظیفه تبدیل شد.', 'reload' => true]);
    }

    public function ajax_get_canned_response() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        if (!current_user_can('edit_posts') || !isset($_POST['response_id'])) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.']);
        }
    
        $response_id = intval($_POST['response_id']);
        $response = get_post($response_id);
    
        if ($response && $response->post_type === 'pzl_canned_response') {
            wp_send_json_success(['content' => $response->post_content]);
        } else {
            wp_send_json_error(['message' => 'پاسخ یافت نشد.']);
        }
    }

    public function ajax_submit_ticket_rating() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        
        $ticket_id = isset($_POST['ticket_id']) ? intval($_POST['ticket_id']) : 0;
        $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
        
        if (empty($ticket_id) || empty($rating) || $rating < 1 || $rating > 5) {
            wp_send_json_error(['message' => 'اطلاعات نامعتبر است.']);
        }

        $ticket = get_post($ticket_id);
        if (!$ticket) {
            wp_send_json_error(['message' => 'تیکت یافت نشد.']);
        }

        // Security check for non-logged in users via token
        if (!is_user_logged_in()) {
            $token = isset($_POST['token']) ? sanitize_key($_POST['token']) : '';
            $csat_token = get_post_meta($ticket_id, '_csat_token', true);
            if (empty($token) || $token !== $csat_token) {
                 wp_send_json_error(['message' => 'دسترسی غیرمجاز.']);
            }
        } else {
            // Security check for logged in users
            if ($ticket->post_author != get_current_user_id() && !current_user_can('manage_options')) {
                wp_send_json_error(['message' => 'شما اجازه امتیاز دادن به این تیکت را ندارید.']);
            }
        }
        
        // Prevent duplicate rating
        if (get_post_meta($ticket_id, '_ticket_rating', true)) {
            wp_send_json_error(['message' => 'شما قبلاً به این تیکت امتیاز داده‌اید.']);
        }

        update_post_meta($ticket_id, '_ticket_rating', $rating);

        wp_send_json_success(['message' => 'امتیاز شما با موفقیت ثبت شد. از بازخورد شما سپاسگزاریم!']);
    }
}