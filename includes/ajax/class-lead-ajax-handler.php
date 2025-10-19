<?php
/**
 * PuzzlingCRM Lead AJAX Handler (Final Patched Version - Hotfix for Edit Status & JS Error)
 * @package PuzzlingCRM
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Ensure the Settings Handler and Logger classes are available.
require_once dirname( __FILE__ ) . '/../class-settings-handler.php';
require_once dirname( __FILE__ ) . '/../class-logger.php';

class PuzzlingCRM_Lead_Ajax_Handler {

    public function __construct() {
        add_action('wp_ajax_puzzling_add_lead', [ $this, 'add_lead' ]);
        add_action('wp_ajax_puzzling_delete_lead', [ $this, 'delete_lead' ]);
        add_action('wp_ajax_puzzling_edit_lead', [ $this, 'edit_lead' ]);
        add_action('wp_ajax_puzzling_change_lead_status', [ $this, 'change_lead_status' ]);
        add_action('wp_ajax_puzzling_add_lead_status', [ $this, 'add_lead_status' ]);
        add_action('wp_ajax_puzzling_delete_lead_status', [ $this, 'delete_lead_status' ]);
    }

    /**
     * Handles adding a new lead.
     */
    public function add_lead() {
        // Log the start of add lead operation
        error_log('PuzzlingCRM: Starting add lead operation');
        
        try {
            // Check nonce
            if (!check_ajax_referer('puzzlingcrm-ajax-nonce', 'security')) {
                error_log('PuzzlingCRM: Invalid nonce in add lead');
                wp_send_json_error(['message' => 'درخواست نامعتبر. لطفاً صفحه را رفرش کنید.']);
            }
            
            // Check user permissions
            if ( ! ( current_user_can( 'manage_options' ) || current_user_can( 'system_manager' ) ) ) {
                error_log('PuzzlingCRM: User does not have permission to add lead. User ID: ' . get_current_user_id());
                wp_send_json_error(['message' => __('شما دسترسی لازم برای انجام این کار را ندارید.', 'puzzlingcrm')]);
            }

            // Validate and sanitize input data
            $first_name = sanitize_text_field($_POST['first_name'] ?? '');
            $last_name = sanitize_text_field($_POST['last_name'] ?? '');
            $mobile = sanitize_text_field($_POST['mobile'] ?? '');
            $business_name = sanitize_text_field($_POST['business_name'] ?? '');
            $gender = sanitize_text_field($_POST['gender'] ?? '');
            $notes = sanitize_textarea_field($_POST['notes'] ?? '');

            error_log("PuzzlingCRM: Lead data - First: {$first_name}, Last: {$last_name}, Mobile: {$mobile}, Business: {$business_name}, Gender: {$gender}");

            // Validate required fields
            if (empty($first_name) || empty($last_name) || empty($mobile)) {
                error_log('PuzzlingCRM: Missing required fields for lead');
                wp_send_json_error(['message' => __('نام، نام خانوادگی و شماره موبایل ضروری هستند.', 'puzzlingcrm')]);
            }

            // Insert lead
            $lead_id = wp_insert_post([
                'post_type' => 'pzl_lead',
                'post_title' => $first_name . ' ' . $last_name,
                'post_content' => $notes,
                'post_status' => 'publish',
            ]);

            if (is_wp_error($lead_id)) {
                error_log('PuzzlingCRM: Error inserting lead: ' . $lead_id->get_error_message());
                wp_send_json_error(['message' => __('خطایی در ثبت سرنخ رخ داد.', 'puzzlingcrm')]);
            }

            error_log("PuzzlingCRM: Lead created successfully with ID: {$lead_id}");
            
            // Update meta data
            update_post_meta($lead_id, '_first_name', $first_name);
            update_post_meta($lead_id, '_last_name', $last_name);
            update_post_meta($lead_id, '_mobile', $mobile);
            update_post_meta($lead_id, '_business_name', $business_name);
            update_post_meta($lead_id, '_gender', $gender);
            
            error_log("PuzzlingCRM: Lead meta data updated for ID: {$lead_id}");
            
            // Set lead status
            $settings = PuzzlingCRM_Settings_Handler::get_all_settings();
            $default_status = !empty($settings['lead_default_status']) ? $settings['lead_default_status'] : null;

            if ($default_status) {
                $status_result = wp_set_object_terms($lead_id, $default_status, 'lead_status');
                error_log("PuzzlingCRM: Set default status '{$default_status}' for lead {$lead_id}. Result: " . print_r($status_result, true));
            } else {
                $first_status = get_terms(['taxonomy' => 'lead_status', 'hide_empty' => false, 'number' => 1, 'orderby' => 'term_id', 'order' => 'ASC']);
                if (!empty($first_status) && !is_wp_error($first_status)) {
                    $status_result = wp_set_object_terms($lead_id, $first_status[0]->slug, 'lead_status');
                    error_log("PuzzlingCRM: Set first available status '{$first_status[0]->slug}' for lead {$lead_id}. Result: " . print_r($status_result, true));
                }
            }
            
            // Send SMS if enabled
            if (!empty($settings['lead_auto_sms_enabled']) && $settings['lead_auto_sms_enabled'] == '1') {
                error_log("PuzzlingCRM: Auto SMS enabled, attempting to send welcome SMS for lead {$lead_id}");
                $this->send_lead_welcome_sms($lead_id, $first_name, $last_name, $mobile, $business_name, $gender, $settings);
            } else {
                error_log("PuzzlingCRM: Auto SMS disabled for lead {$lead_id}");
            }
            
            // Add to system logs
            if (class_exists('PuzzlingCRM_Logger')) {
                PuzzlingCRM_Logger::add('افزودن سرنخ', [
                    'content' => "سرنخ جدید با نام {$first_name} {$last_name} و شماره {$mobile} ثبت شد.",
                    'type' => 'log',
                    'details' => [
                        'lead_id' => $lead_id,
                        'first_name' => $first_name,
                        'last_name' => $last_name,
                        'mobile' => $mobile,
                        'business_name' => $business_name,
                        'gender' => $gender,
                        'user_id' => get_current_user_id()
                    ]
                ]);
            }
            
            wp_send_json_success(['message' => __('سرنخ با موفقیت ثبت شد.', 'puzzlingcrm'), 'reload' => true]);
            
        } catch (Exception $e) {
            error_log('PuzzlingCRM Add Lead Error: ' . $e->getMessage());
            error_log('PuzzlingCRM Add Lead Error Stack Trace: ' . $e->getTraceAsString());
            
            // Add error to system logs
            if (class_exists('PuzzlingCRM_Logger')) {
                PuzzlingCRM_Logger::add('خطای افزودن سرنخ', [
                    'content' => 'خطا در افزودن سرنخ: ' . $e->getMessage(),
                    'type' => 'error',
                    'details' => [
                        'user_id' => get_current_user_id(),
                        'error_file' => $e->getFile(),
                        'error_line' => $e->getLine(),
                        'post_data' => $_POST
                    ]
                ]);
            }
            
            wp_send_json_error(['message' => 'یک خطای سیستمی در هنگام ثبت سرنخ رخ داد.']);
        }
    }

    /**
     * Handles editing an existing lead.
     */
    public function edit_lead() {
        // Log the start of edit lead operation
        error_log('PuzzlingCRM: Starting edit lead operation');
        
        try {
            // Check nonce
            if (!check_ajax_referer('puzzlingcrm-ajax-nonce', 'security')) {
                error_log('PuzzlingCRM: Invalid nonce in edit lead');
                wp_send_json_error(['message' => 'درخواست نامعتبر. لطفاً صفحه را رفرش کنید.']);
            }
            
            // Check user permissions
            if ( ! ( current_user_can( 'manage_options' ) || current_user_can( 'system_manager' ) ) ) {
                error_log('PuzzlingCRM: User does not have permission to edit lead. User ID: ' . get_current_user_id());
                wp_send_json_error(['message' => __('شما دسترسی لازم برای انجام این کار را ندارید.', 'puzzlingcrm')]);
            }

            $lead_id = isset($_POST['lead_id']) ? intval($_POST['lead_id']) : 0;
            if ($lead_id <= 0) {
                error_log('PuzzlingCRM: Invalid lead ID in edit lead: ' . $lead_id);
                wp_send_json_error(['message' => __('شناسه سرنخ نامعتبر است.', 'puzzlingcrm')]);
            }

            error_log("PuzzlingCRM: Editing lead ID: {$lead_id}");

            // Validate and sanitize input data
            $first_name = sanitize_text_field($_POST['first_name'] ?? '');
            $last_name = sanitize_text_field($_POST['last_name'] ?? '');
            $mobile = sanitize_text_field($_POST['mobile'] ?? '');
            $business_name = sanitize_text_field($_POST['business_name'] ?? '');
            $gender = sanitize_text_field($_POST['gender'] ?? '');
            $notes = sanitize_textarea_field($_POST['notes'] ?? '');

            error_log("PuzzlingCRM: Lead edit data - First: {$first_name}, Last: {$last_name}, Mobile: {$mobile}, Business: {$business_name}, Gender: {$gender}");

            // Validate required fields
            if (empty($first_name) || empty($last_name) || empty($mobile)) {
                error_log('PuzzlingCRM: Missing required fields for lead edit');
                wp_send_json_error(['message' => __('نام، نام خانوادگی و شماره موبایل ضروری هستند.', 'puzzlingcrm')]);
            }

            // Update post data
            $post_data = ['ID' => $lead_id, 'post_title' => $first_name . ' ' . $last_name, 'post_content' => $notes];
            $result = wp_update_post($post_data, true);

            if (is_wp_error($result)) {
                error_log('PuzzlingCRM: Error updating lead post: ' . $result->get_error_message());
                wp_send_json_error(['message' => __('خطایی در به‌روزرسانی سرنخ رخ داد.', 'puzzlingcrm')]);
            }

            error_log("PuzzlingCRM: Lead post updated successfully for ID: {$lead_id}");

            // Update meta data
            update_post_meta($lead_id, '_first_name', $first_name);
            update_post_meta($lead_id, '_last_name', $last_name);
            update_post_meta($lead_id, '_mobile', $mobile);
            update_post_meta($lead_id, '_business_name', $business_name);
            update_post_meta($lead_id, '_gender', $gender);

            error_log("PuzzlingCRM: Lead meta data updated for ID: {$lead_id}");

            // Update lead status if provided
            if (isset($_POST['lead_status'])) {
                $status_slug = sanitize_text_field($_POST['lead_status']);
                if (!empty($status_slug)) {
                    $term_result = wp_set_object_terms($lead_id, $status_slug, 'lead_status');
                    if (is_wp_error($term_result)) {
                        error_log('PuzzlingCRM: Error setting lead status: ' . $term_result->get_error_message());
                        wp_send_json_error(['message' => 'خطایی در هنگام به‌روزرسانی وضعیت رخ داد: ' . $term_result->get_error_message()]);
                    }
                    error_log("PuzzlingCRM: Lead status updated to '{$status_slug}' for ID: {$lead_id}");
                }
            }

            // Clear any caches that might affect the display
            wp_cache_delete($lead_id, 'post_meta');
            clean_post_cache($lead_id);

            // Add to system logs
            if (class_exists('PuzzlingCRM_Logger')) {
                PuzzlingCRM_Logger::add('ویرایش سرنخ', [
                    'content' => "سرنخ با نام {$first_name} {$last_name} ویرایش شد.",
                    'type' => 'log',
                    'details' => [
                        'lead_id' => $lead_id,
                        'first_name' => $first_name,
                        'last_name' => $last_name,
                        'mobile' => $mobile,
                        'business_name' => $business_name,
                        'gender' => $gender,
                        'user_id' => get_current_user_id()
                    ]
                ]);
            }

            $redirect_url = admin_url('admin.php?page=puzzling-leads');
            if ( ! empty( $_POST['_wp_http_referer'] ) ) {
                $redirect_url = esc_url_raw( wp_unslash( $_POST['_wp_http_referer'] ) );
            }
            
            wp_send_json_success(['message' => __('تغییرات با موفقیت ذخیره شد.', 'puzzlingcrm'), 'redirect_url' => $redirect_url]);
            
        } catch (Exception $e) {
            error_log('PuzzlingCRM Edit Lead Error: ' . $e->getMessage());
            error_log('PuzzlingCRM Edit Lead Error Stack Trace: ' . $e->getTraceAsString());
            
            // Add error to system logs
            if (class_exists('PuzzlingCRM_Logger')) {
                PuzzlingCRM_Logger::add('خطای ویرایش سرنخ', [
                    'content' => 'خطا در ویرایش سرنخ: ' . $e->getMessage(),
                    'type' => 'error',
                    'details' => [
                        'lead_id' => $lead_id ?? 0,
                        'user_id' => get_current_user_id(),
                        'error_file' => $e->getFile(),
                        'error_line' => $e->getLine(),
                        'post_data' => $_POST
                    ]
                ]);
            }
            
            wp_send_json_error(['message' => 'یک خطای سیستمی در هنگام ویرایش سرنخ رخ داد.']);
        }
    }

    /**
     * Handles changing a lead's status from the list view.
     */
    public function change_lead_status() {
        // Log the start of change lead status operation
        error_log('PuzzlingCRM: Starting change lead status operation');
        
        try {
            // Check nonce
            if (!check_ajax_referer('puzzlingcrm-ajax-nonce', 'security')) {
                error_log('PuzzlingCRM: Invalid nonce in change lead status');
                wp_send_json_error(['message' => 'درخواست نامعتبر. لطفاً صفحه را رفرش کنید.']);
            }
            
            // Check user permissions
            if ( ! ( current_user_can( 'manage_options' ) || current_user_can( 'system_manager' ) ) ) {
                error_log('PuzzlingCRM: User does not have permission to change lead status. User ID: ' . get_current_user_id());
                wp_send_json_error(['message' => __('شما دسترسی لازم برای انجام این کار را ندارید.', 'puzzlingcrm')]);
            }

            $lead_id = isset($_POST['lead_id']) ? intval($_POST['lead_id']) : 0;
            $new_status = isset($_POST['new_status']) ? sanitize_text_field($_POST['new_status']) : '';

            error_log("PuzzlingCRM: Changing lead status - Lead ID: {$lead_id}, New Status: {$new_status}");

            if ($lead_id <= 0 || empty($new_status)) {
                error_log('PuzzlingCRM: Invalid lead ID or status in change lead status');
                wp_send_json_error(['message' => __('اطلاعات ارسالی نامعتبر است.', 'puzzlingcrm')]);
            }

            // Get lead info for logging
            $lead = get_post($lead_id);
            $lead_name = $lead ? $lead->post_title : 'Unknown';
            
            // Get current status before change
            $current_terms = get_the_terms($lead_id, 'lead_status');
            $current_status = !empty($current_terms) && !is_wp_error($current_terms) ? $current_terms[0]->name : 'بدون وضعیت';
            
            // Get new status name
            $new_status_term = get_term_by('slug', $new_status, 'lead_status');
            $new_status_name = $new_status_term ? $new_status_term->name : $new_status;

            error_log("PuzzlingCRM: Changing lead status from '{$current_status}' to '{$new_status_name}' for lead ID: {$lead_id}");

            $result = wp_set_object_terms($lead_id, $new_status, 'lead_status');
            if (is_wp_error($result)) {
                error_log('PuzzlingCRM: Error changing lead status: ' . $result->get_error_message());
                wp_send_json_error(['message' => 'خطا در تغییر وضعیت: ' . $result->get_error_message()]);
            }

            error_log("PuzzlingCRM: Lead status changed successfully for ID: {$lead_id} from '{$current_status}' to '{$new_status_name}'");

            // Clear any caches that might affect the display
            wp_cache_delete($lead_id, 'post_meta');
            clean_post_cache($lead_id);

            // Add to system logs
            if (class_exists('PuzzlingCRM_Logger')) {
                PuzzlingCRM_Logger::add('تغییر وضعیت سرنخ', [
                    'content' => "وضعیت سرنخ {$lead_name} از '{$current_status}' به '{$new_status_name}' تغییر یافت.",
                    'type' => 'log',
                    'details' => [
                        'lead_id' => $lead_id,
                        'lead_name' => $lead_name,
                        'old_status' => $current_status,
                        'new_status' => $new_status_name,
                        'new_status_slug' => $new_status,
                        'user_id' => get_current_user_id()
                    ]
                ]);
            }

            wp_send_json_success(['message' => __('وضعیت با موفقیت به‌روزرسانی شد.', 'puzzlingcrm')]);
            
        } catch (Exception $e) {
            error_log('PuzzlingCRM Change Lead Status Error: ' . $e->getMessage());
            error_log('PuzzlingCRM Change Lead Status Error Stack Trace: ' . $e->getTraceAsString());
            
            // Add error to system logs
            if (class_exists('PuzzlingCRM_Logger')) {
                PuzzlingCRM_Logger::add('خطای تغییر وضعیت سرنخ', [
                    'content' => 'خطا در تغییر وضعیت سرنخ: ' . $e->getMessage(),
                    'type' => 'error',
                    'details' => [
                        'lead_id' => $lead_id ?? 0,
                        'new_status' => $new_status ?? '',
                        'user_id' => get_current_user_id(),
                        'error_file' => $e->getFile(),
                        'error_line' => $e->getLine(),
                        'post_data' => $_POST
                    ]
                ]);
            }
            
            wp_send_json_error(['message' => 'یک خطای سیستمی در هنگام تغییر وضعیت سرنخ رخ داد.']);
        }
    }

    /**
     * Handles deleting a lead.
     */
    public function delete_lead() {
        // Log the start of delete lead operation
        error_log('PuzzlingCRM: Starting delete lead operation');
        
        try {
            // Check nonce
            if (!check_ajax_referer('puzzlingcrm-ajax-nonce', 'security')) {
                error_log('PuzzlingCRM: Invalid nonce in delete lead');
                wp_send_json_error(['message' => 'درخواست نامعتبر. لطفاً صفحه را رفرش کنید.']);
            }
            
            // Check user permissions
            if ( ! ( current_user_can( 'manage_options' ) || current_user_can( 'system_manager' ) ) ) {
                error_log('PuzzlingCRM: User does not have permission to delete lead. User ID: ' . get_current_user_id());
                wp_send_json_error(['message' => __('شما دسترسی لازم برای انجام این کار را ندارید.', 'puzzlingcrm')]);
            }

            $lead_id = isset($_POST['lead_id']) ? intval($_POST['lead_id']) : 0;
            if ($lead_id <= 0) {
                error_log('PuzzlingCRM: Invalid lead ID in delete lead: ' . $lead_id);
                wp_send_json_error(['message' => __('شناسه سرنخ نامعتبر است.', 'puzzlingcrm')]);
            }

            // Get lead info for logging before deletion
            $lead = get_post($lead_id);
            $lead_name = $lead ? $lead->post_title : 'Unknown';
            $first_name = get_post_meta($lead_id, '_first_name', true);
            $last_name = get_post_meta($lead_id, '_last_name', true);
            $mobile = get_post_meta($lead_id, '_mobile', true);

            error_log("PuzzlingCRM: Deleting lead ID: {$lead_id}, Name: {$lead_name}");

            $result = wp_delete_post($lead_id, true);
            if ($result === false) {
                error_log('PuzzlingCRM: Error deleting lead: ' . $lead_id);
                wp_send_json_error(['message' => __('خطایی در حذف سرنخ رخ داد.', 'puzzlingcrm')]);
            }

            error_log("PuzzlingCRM: Lead deleted successfully: {$lead_id}");

            // Add to system logs
            if (class_exists('PuzzlingCRM_Logger')) {
                PuzzlingCRM_Logger::add('حذف سرنخ', [
                    'content' => "سرنخ {$lead_name} ({$first_name} {$last_name}) حذف شد.",
                    'type' => 'log',
                    'details' => [
                        'lead_id' => $lead_id,
                        'lead_name' => $lead_name,
                        'first_name' => $first_name,
                        'last_name' => $last_name,
                        'mobile' => $mobile,
                        'user_id' => get_current_user_id()
                    ]
                ]);
            }

            wp_send_json_success(['message' => __('سرنخ با موفقیت حذف شد.', 'puzzlingcrm'), 'reload' => true]);
            
        } catch (Exception $e) {
            error_log('PuzzlingCRM Delete Lead Error: ' . $e->getMessage());
            error_log('PuzzlingCRM Delete Lead Error Stack Trace: ' . $e->getTraceAsString());
            
            // Add error to system logs
            if (class_exists('PuzzlingCRM_Logger')) {
                PuzzlingCRM_Logger::add('خطای حذف سرنخ', [
                    'content' => 'خطا در حذف سرنخ: ' . $e->getMessage(),
                    'type' => 'error',
                    'details' => [
                        'lead_id' => $lead_id ?? 0,
                        'user_id' => get_current_user_id(),
                        'error_file' => $e->getFile(),
                        'error_line' => $e->getLine(),
                        'post_data' => $_POST
                    ]
                ]);
            }
            
            wp_send_json_error(['message' => 'یک خطای سیستمی در هنگام حذف سرنخ رخ داد.']);
        }
    }

    /**
     * AJAX handler to add a new lead status term from the settings page.
     */
    public function add_lead_status() {
        // Log the start of add lead status operation
        error_log('PuzzlingCRM: Starting add lead status operation');
        
        try {
            // Check nonce
            if (!check_ajax_referer('puzzlingcrm-ajax-nonce', 'security')) {
                error_log('PuzzlingCRM: Invalid nonce in add lead status');
                wp_send_json_error(['message' => 'درخواست نامعتبر. لطفاً صفحه را رفرش کنید.']);
            }
            
            // Check user permissions
            if (!current_user_can('manage_options')) {
                error_log('PuzzlingCRM: User does not have permission to add lead status. User ID: ' . get_current_user_id());
                wp_send_json_error(['message' => 'دسترسی غیرمجاز.']);
            }
            
            $status_name = isset($_POST['status_name']) ? sanitize_text_field(trim($_POST['status_name'])) : '';
            error_log("PuzzlingCRM: Adding lead status: {$status_name}");
            
            if (empty($status_name)) {
                error_log('PuzzlingCRM: Empty status name in add lead status');
                wp_send_json_error(['message' => 'نام وضعیت نمی‌تواند خالی باشد.']);
            }

            if (term_exists($status_name, 'lead_status')) {
                error_log("PuzzlingCRM: Status '{$status_name}' already exists");
                wp_send_json_error(['message' => 'این وضعیت از قبل وجود دارد.']);
            }

            $result = wp_insert_term($status_name, 'lead_status');
            if (is_wp_error($result)) {
                error_log('PuzzlingCRM: Error inserting lead status: ' . $result->get_error_message());
                wp_send_json_error(['message' => $result->get_error_message()]);
            }

            $term = get_term($result['term_id'], 'lead_status');
            error_log("PuzzlingCRM: Lead status added successfully - ID: {$term->term_id}, Name: {$term->name}, Slug: {$term->slug}");

            // Add to system logs
            if (class_exists('PuzzlingCRM_Logger')) {
                PuzzlingCRM_Logger::add('افزودن وضعیت سرنخ', [
                    'content' => "وضعیت جدید '{$status_name}' برای سرنخ‌ها اضافه شد.",
                    'type' => 'log',
                    'details' => [
                        'status_name' => $status_name,
                        'term_id' => $term->term_id,
                        'slug' => $term->slug,
                        'user_id' => get_current_user_id()
                    ]
                ]);
            }

            wp_send_json_success(['term_id' => $term->term_id, 'name' => $term->name, 'slug' => $term->slug]);
            
        } catch (Exception $e) {
            error_log('PuzzlingCRM Add Lead Status Error: ' . $e->getMessage());
            error_log('PuzzlingCRM Add Lead Status Error Stack Trace: ' . $e->getTraceAsString());
            
            // Add error to system logs
            if (class_exists('PuzzlingCRM_Logger')) {
                PuzzlingCRM_Logger::add('خطای افزودن وضعیت سرنخ', [
                    'content' => 'خطا در افزودن وضعیت سرنخ: ' . $e->getMessage(),
                    'type' => 'error',
                    'details' => [
                        'status_name' => $status_name ?? '',
                        'user_id' => get_current_user_id(),
                        'error_file' => $e->getFile(),
                        'error_line' => $e->getLine(),
                        'post_data' => $_POST
                    ]
                ]);
            }
            
            wp_send_json_error(['message' => 'یک خطای سیستمی در هنگام افزودن وضعیت سرنخ رخ داد.']);
        }
    }

    /**
     * AJAX handler to delete a lead status term from the settings page.
     */
    public function delete_lead_status() {
        // Log the start of delete lead status operation
        error_log('PuzzlingCRM: Starting delete lead status operation');
        
        try {
            // Check nonce
            if (!check_ajax_referer('puzzlingcrm-ajax-nonce', 'security')) {
                error_log('PuzzlingCRM: Invalid nonce in delete lead status');
                wp_send_json_error(['message' => 'درخواست نامعتبر. لطفاً صفحه را رفرش کنید.']);
            }
            
            // Check user permissions
            if (!current_user_can('manage_options')) {
                error_log('PuzzlingCRM: User does not have permission to delete lead status. User ID: ' . get_current_user_id());
                wp_send_json_error(['message' => 'دسترسی غیرمجاز.']);
            }
            
            $term_id = isset($_POST['term_id']) ? intval($_POST['term_id']) : 0;
            error_log("PuzzlingCRM: Deleting lead status with term ID: {$term_id}");
            
            if ($term_id <= 0) {
                error_log('PuzzlingCRM: Invalid term ID in delete lead status: ' . $term_id);
                wp_send_json_error(['message' => 'شناسه وضعیت نامعتبر است.']);
            }

            $term = get_term($term_id, 'lead_status');
            if (!$term || is_wp_error($term)) {
                error_log("PuzzlingCRM: Term not found for ID: {$term_id}");
                wp_send_json_error(['message' => 'وضعیت مورد نظر یافت نشد.']);
            }

            $status_name = $term->name;
            error_log("PuzzlingCRM: Deleting lead status: {$status_name} (ID: {$term_id})");

            $result = wp_delete_term($term_id, 'lead_status');
            if (is_wp_error($result)) {
                error_log('PuzzlingCRM: Error deleting lead status: ' . $result->get_error_message());
                wp_send_json_error(['message' => $result->get_error_message()]);
            }

            error_log("PuzzlingCRM: Lead status deleted successfully: {$status_name} (ID: {$term_id})");

            // Add to system logs
            if (class_exists('PuzzlingCRM_Logger')) {
                PuzzlingCRM_Logger::add('حذف وضعیت سرنخ', [
                    'content' => "وضعیت '{$status_name}' برای سرنخ‌ها حذف شد.",
                    'type' => 'log',
                    'details' => [
                        'status_name' => $status_name,
                        'term_id' => $term_id,
                        'slug' => $term->slug,
                        'user_id' => get_current_user_id()
                    ]
                ]);
            }

            wp_send_json_success(['message' => 'وضعیت با موفقیت حذف شد.', 'slug' => $term->slug]);
            
        } catch (Exception $e) {
            error_log('PuzzlingCRM Delete Lead Status Error: ' . $e->getMessage());
            error_log('PuzzlingCRM Delete Lead Status Error Stack Trace: ' . $e->getTraceAsString());
            
            // Add error to system logs
            if (class_exists('PuzzlingCRM_Logger')) {
                PuzzlingCRM_Logger::add('خطای حذف وضعیت سرنخ', [
                    'content' => 'خطا در حذف وضعیت سرنخ: ' . $e->getMessage(),
                    'type' => 'error',
                    'details' => [
                        'term_id' => $term_id ?? 0,
                        'user_id' => get_current_user_id(),
                        'error_file' => $e->getFile(),
                        'error_line' => $e->getLine(),
                        'post_data' => $_POST
                    ]
                ]);
            }
            
            wp_send_json_error(['message' => 'یک خطای سیستمی در هنگام حذف وضعیت سرنخ رخ داد.']);
        }
    }

    /**
     * Sends welcome SMS to new lead based on gender and SMS service
     */
    private function send_lead_welcome_sms($lead_id, $first_name, $last_name, $mobile, $business_name, $gender, $settings) {
        error_log("PuzzlingCRM: Starting SMS send for lead {$lead_id}, gender: {$gender}, mobile: {$mobile}");
        
        try {
            // Get SMS handler
            if (!function_exists('puzzling_get_sms_handler')) {
                require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/puzzling-functions.php';
            }
            
            $sms_handler = puzzling_get_sms_handler($settings);
            if (!$sms_handler) {
                error_log('PuzzlingCRM: SMS handler not configured for lead welcome SMS');
                if (class_exists('PuzzlingCRM_Logger')) {
                    PuzzlingCRM_Logger::add('خطای پیامک سرنخ', [
                        'content' => 'سرویس پیامک پیکربندی نشده است.',
                        'type' => 'error',
                        'details' => [
                            'lead_id' => $lead_id,
                            'mobile' => $mobile,
                            'gender' => $gender
                        ]
                    ]);
                }
                return;
            }
            
            error_log('PuzzlingCRM: SMS handler loaded successfully');

            // Determine message template based on gender and service
            $message = '';
            $params = [];
            $sms_service = $settings['sms_service'] ?? 'melipayamak';
            
            error_log("PuzzlingCRM: SMS service: {$sms_service}, Gender: {$gender}");
            
            if ($sms_service === 'melipayamak') {
                // Use pattern-based SMS for Melipayamak
                if ($gender === 'male' && !empty($settings['lead_pattern_male'])) {
                    $message = $settings['lead_pattern_male'];
                    $params = ['first_name' => $first_name, 'last_name' => $last_name, 'business_name' => $business_name];
                    error_log("PuzzlingCRM: Using Melipayamak male pattern: {$message}");
                } elseif ($gender === 'female' && !empty($settings['lead_pattern_female'])) {
                    $message = $settings['lead_pattern_female'];
                    $params = ['first_name' => $first_name, 'last_name' => $last_name, 'business_name' => $business_name];
                    error_log("PuzzlingCRM: Using Melipayamak female pattern: {$message}");
                } else {
                    error_log("PuzzlingCRM: No Melipayamak pattern found for gender: {$gender}");
                }
            } else {
                // Use text-based SMS for ParsGreen
                if ($gender === 'male' && !empty($settings['parsgreen_lead_msg_male'])) {
                    $message = $settings['parsgreen_lead_msg_male'];
                    error_log("PuzzlingCRM: Using ParsGreen male message: {$message}");
                } elseif ($gender === 'female' && !empty($settings['parsgreen_lead_msg_female'])) {
                    $message = $settings['parsgreen_lead_msg_female'];
                    error_log("PuzzlingCRM: Using ParsGreen female message: {$message}");
                } else {
                    error_log("PuzzlingCRM: No ParsGreen message found for gender: {$gender}");
                }
            }

            if (empty($message)) {
                error_log('PuzzlingCRM: No SMS template found for lead gender: ' . $gender . ', service: ' . $sms_service);
                if (class_exists('PuzzlingCRM_Logger')) {
                    PuzzlingCRM_Logger::add('خطای پیامک سرنخ', [
                        'content' => 'الگوی پیامک برای جنسیت ' . $gender . ' و سرویس ' . $sms_service . ' یافت نشد.',
                        'type' => 'error',
                        'details' => [
                            'lead_id' => $lead_id,
                            'mobile' => $mobile,
                            'gender' => $gender,
                            'sms_service' => $sms_service
                        ]
                    ]);
                }
                return;
            }

            // Replace placeholders in message
            $original_message = $message;
            $full_name = trim($first_name . ' ' . $last_name);
            $message = str_replace('{first_name}', $first_name, $message);
            $message = str_replace('{last_name}', $last_name, $message);
            $message = str_replace('{full_name}', $full_name, $message);
            $message = str_replace('{business_name}', $business_name, $message);
            
            error_log("PuzzlingCRM: Final message after replacement: {$message}");

            // Send SMS
            $result = $sms_handler->send_sms($mobile, $message, $params);
            
            if ($result) {
                error_log('PuzzlingCRM: Welcome SMS sent successfully to lead ID: ' . $lead_id);
                if (class_exists('PuzzlingCRM_Logger')) {
                    PuzzlingCRM_Logger::add('ارسال پیامک سرنخ', [
                        'content' => "پیامک خوشامدگویی برای سرنخ {$first_name} {$last_name} ارسال شد.",
                        'type' => 'log',
                        'details' => [
                            'lead_id' => $lead_id,
                            'mobile' => $mobile,
                            'gender' => $gender,
                            'sms_service' => $sms_service,
                            'message' => $message
                        ]
                    ]);
                }
            } else {
                error_log('PuzzlingCRM: Failed to send welcome SMS to lead ID: ' . $lead_id);
                if (class_exists('PuzzlingCRM_Logger')) {
                    PuzzlingCRM_Logger::add('خطای پیامک سرنخ', [
                        'content' => 'خطا در ارسال پیامک خوشامدگویی برای سرنخ.',
                        'type' => 'error',
                        'details' => [
                            'lead_id' => $lead_id,
                            'mobile' => $mobile,
                            'gender' => $gender,
                            'sms_service' => $sms_service,
                            'message' => $message
                        ]
                    ]);
                }
            }
            
        } catch (Exception $e) {
            error_log('PuzzlingCRM SMS Error: ' . $e->getMessage());
            error_log('PuzzlingCRM SMS Error Stack Trace: ' . $e->getTraceAsString());
            
            if (class_exists('PuzzlingCRM_Logger')) {
                PuzzlingCRM_Logger::add('خطای پیامک سرنخ', [
                    'content' => 'خطا در ارسال پیامک سرنخ: ' . $e->getMessage(),
                    'type' => 'error',
                    'details' => [
                        'lead_id' => $lead_id,
                        'mobile' => $mobile,
                        'gender' => $gender,
                        'error_file' => $e->getFile(),
                        'error_line' => $e->getLine()
                    ]
                ]);
            }
        }
    }
}