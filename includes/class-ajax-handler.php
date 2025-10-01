<?php
/**
 * PuzzlingCRM AJAX Handler - V2.9 (Consultation Management Added)
 *
 * Handles all AJAX requests for the plugin.
 *
 * @package PuzzlingCRM
 */

// Make sure to require the necessary WordPress file upload handling functions
require_once(ABSPATH . 'wp-admin/includes/file.php');
require_once(ABSPATH . 'wp-admin/includes/media.php');
require_once(ABSPATH . 'wp-admin/includes/image.php');

class PuzzlingCRM_Ajax_Handler {

    public function __construct() {
        // --- Form Submissions (Refactored to AJAX) ---
        add_action('wp_ajax_puzzling_manage_user', [$this, 'ajax_manage_user']);
        add_action('wp_ajax_puzzling_delete_user', [$this, 'ajax_delete_user']);
        add_action('wp_ajax_puzzling_manage_project', [$this, 'ajax_manage_project']);
        add_action('wp_ajax_puzzling_manage_contract', [$this, 'ajax_manage_contract']);
        add_action('wp_ajax_puzzling_add_project_to_contract', [$this, 'ajax_add_project_to_contract']);
        add_action('wp_ajax_puzzling_update_my_profile', [$this, 'ajax_update_my_profile']);
        add_action('wp_ajax_puzzling_add_services_from_product', [$this, 'ajax_add_services_from_product']);
        add_action('wp_ajax_puzzling_manage_consultation', [$this, 'ajax_manage_consultation']); // NEW
        add_action('wp_ajax_puzzling_convert_consultation_to_project', [$this, 'ajax_convert_consultation_to_project']); // NEW

        // --- Live Search Actions ---
        add_action('wp_ajax_puzzling_search_users', [$this, 'ajax_search_users']);

        // --- Standard Task Actions ---
        add_action('wp_ajax_puzzling_add_task', [$this, 'add_task']);
        add_action('wp_ajax_puzzling_quick_add_task', [$this, 'quick_add_task']);
        add_action('wp_ajax_puzzling_update_task_status', [$this, 'update_task_status']);
        add_action('wp_ajax_puzzling_delete_task', [$this, 'delete_task']);
        add_action('wp_ajax_puzzling_update_task_assignee', [$this, 'ajax_update_task_assignee']);

        // --- Notification Actions ---
        add_action('wp_ajax_puzzling_get_notifications', [$this, 'get_notifications']);
        add_action('wp_ajax_puzzling_mark_notification_read', [$this, 'mark_notification_read']);

        // --- Kanban Board & Modal Actions ---
        add_action('wp_ajax_puzzling_get_task_details', [$this, 'get_task_details']);
        add_action('wp_ajax_puzzling_save_task_content', [$this, 'save_task_content']);
        add_action('wp_ajax_puzzling_add_task_comment', [$this, 'add_task_comment']);
        
        // --- Workflow & Taxonomy Management Actions ---
        add_action('wp_ajax_puzzling_save_status_order', [$this, 'save_status_order']);
        add_action('wp_ajax_puzzling_add_new_status', [$this, 'add_new_status']);
        add_action('wp_ajax_puzzling_delete_status', [$this, 'delete_status']);
        add_action('wp_ajax_puzzling_manage_position', [$this, 'ajax_manage_position']);
        add_action('wp_ajax_puzzling_delete_position', [$this, 'ajax_delete_position']);
        add_action('wp_ajax_puzzling_manage_task_category', [$this, 'ajax_manage_task_category']);
        add_action('wp_ajax_puzzling_delete_task_category', [$this, 'ajax_delete_task_category']);

        // --- Advanced Task Features ---
        add_action('wp_ajax_puzzling_manage_checklist', [$this, 'manage_checklist']);
        add_action('wp_ajax_puzzling_log_time', [$this, 'log_time']);
        add_action('wp_ajax_puzzling_quick_edit_task', [$this, 'quick_edit_task']);

        // --- Advanced Views Data ---
        add_action('wp_ajax_get_tasks_for_views', [$this, 'get_tasks_for_views']);

        // --- Advanced Task Linking ---
        add_action('wp_ajax_puzzling_add_task_link', [$this, 'add_task_link']);
        add_action('wp_ajax_puzzling_remove_task_link', [$this, 'remove_task_link']);
        add_action('wp_ajax_puzzling_search_tasks_for_linking', [$this, 'search_tasks_for_linking']);
        
        // --- Other AJAX Actions ---
        add_action('wp_ajax_puzzling_delete_project', [$this, 'delete_project']);
        add_action('wp_ajax_puzzling_bulk_edit_tasks', [$this, 'bulk_edit_tasks']);
        add_action('wp_ajax_puzzling_save_task_as_template', [$this, 'save_task_as_template']);
        add_action('wp_ajax_puzzling_send_custom_sms', [$this, 'send_custom_sms']);
    }

    /**
     * AJAX handler for creating and updating consultations.
     */
    public function ajax_manage_consultation() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.']);
        }

        $consultation_id = isset($_POST['consultation_id']) ? intval($_POST['consultation_id']) : 0;
        $name = sanitize_text_field($_POST['name']);
        $phone = sanitize_text_field($_POST['phone']);
        $email = sanitize_email($_POST['email']);
        $type = sanitize_key($_POST['type']);
        $date = sanitize_text_field($_POST['date']);
        $time = sanitize_text_field($_POST['time']);
        $status_slug = sanitize_key($_POST['status']);

        if (empty($name) || empty($phone)) {
            wp_send_json_error(['message' => 'نام و شماره تماس الزامی است.']);
        }

        $post_title = $name;
        $datetime = !empty($date) ? puzzling_jalali_to_gregorian($date) . ' ' . $time : '';
        
        $post_data = [
            'post_title' => $post_title,
            'post_status' => 'publish',
            'post_type' => 'pzl_consultation',
        ];

        if ($consultation_id > 0) {
            $post_data['ID'] = $consultation_id;
            $result = wp_update_post($post_data, true);
            $message = 'مشاوره با موفقیت به‌روزرسانی شد.';
        } else {
            $result = wp_insert_post($post_data, true);
            $message = 'مشاوره جدید با موفقیت ثبت شد.';
        }

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => 'خطا در پردازش مشاوره.']);
        }

        $the_consultation_id = is_int($result) ? $result : $consultation_id;
        
        update_post_meta($the_consultation_id, '_consultation_phone', $phone);
        update_post_meta($the_consultation_id, '_consultation_email', $email);
        update_post_meta($the_consultation_id, '_consultation_type', $type);
        update_post_meta($the_consultation_id, '_consultation_datetime', $datetime);
        wp_set_object_terms($the_consultation_id, $status_slug, 'consultation_status');

        wp_send_json_success(['message' => $message, 'reload' => true]);
    }
    
    /**
     * AJAX handler for converting a consultation to a project.
     */
    public function ajax_convert_consultation_to_project() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.']);
        }
        
        $consultation_id = intval($_POST['consultation_id']);
        $consultation = get_post($consultation_id);

        if (!$consultation || $consultation->post_type !== 'pzl_consultation') {
            wp_send_json_error(['message' => 'مشاوره یافت نشد.']);
        }

        $name = $consultation->post_title;
        $email = get_post_meta($consultation_id, '_consultation_email', true);
        $phone = get_post_meta($consultation_id, '_consultation_phone', true);

        // Find or create user
        $customer_id = 0;
        if (!empty($email) && email_exists($email)) {
            $user = get_user_by('email', $email);
            $customer_id = $user->ID;
        } else {
            // Create a new user
            $password = wp_generate_password();
            $customer_id = wp_create_user($email, $password, $email);
            if (is_wp_error($customer_id)) {
                // Fallback if email is invalid, use phone to create a username
                $username = 'user_' . $phone;
                $customer_id = wp_create_user($username, $password, $email);
            }
            if (!is_wp_error($customer_id)) {
                wp_update_user(['ID' => $customer_id, 'display_name' => $name, 'role' => 'customer']);
                update_user_meta($customer_id, 'pzl_mobile_phone', $phone);
            } else {
                 wp_send_json_error(['message' => 'خطا در ایجاد کاربر جدید: ' . $customer_id->get_error_message()]);
            }
        }

        // Create Contract
        $contract_id = wp_insert_post([
            'post_title' => 'قرارداد برای ' . $name,
            'post_author' => $customer_id,
            'post_status' => 'publish',
            'post_type' => 'contract',
        ]);
        
        if (is_wp_error($contract_id)) {
            wp_send_json_error(['message' => 'خطا در ایجاد قرارداد.']);
        }
        
        // Create Project
        $project_id = wp_insert_post([
            'post_title' => 'پروژه جدید برای ' . $name,
            'post_author' => $customer_id,
            'post_status' => 'publish',
            'post_type' => 'project',
        ]);

        if (is_wp_error($project_id)) {
            wp_send_json_error(['message' => 'خطا در ایجاد پروژه.']);
        }
        
        // Link project to contract
        update_post_meta($project_id, '_contract_id', $contract_id);
        
        // Update consultation status
        wp_set_object_terms($consultation_id, 'converted', 'consultation_status');

        $edit_contract_url = add_query_arg(['view' => 'contracts', 'action' => 'edit', 'contract_id' => $contract_id], puzzling_get_dashboard_url());

        wp_send_json_success(['message' => 'مشاوره با موفقیت به پروژه تبدیل شد. در حال انتقال به صفحه قرارداد...', 'redirect_url' => $edit_contract_url]);
    }
    
    // ... (The rest of the class remains the same)

    /**
     * AJAX handler for updating a task's assignee from the modal.
     */
    public function ajax_update_task_assignee() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        if (!current_user_can('edit_tasks') || !isset($_POST['task_id']) || !isset($_POST['assignee_id'])) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.']);
        }

        $task_id = intval($_POST['task_id']);
        $assignee_id = intval($_POST['assignee_id']);
        $new_assignee = get_userdata($assignee_id);

        if (!$new_assignee) {
            wp_send_json_error(['message' => 'کاربر انتخاب شده معتبر نیست.']);
        }

        update_post_meta($task_id, '_assigned_to', $assignee_id);
        $this->_log_task_activity($task_id, sprintf('مسئول وظیفه را به "%s" تغییر داد.', $new_assignee->display_name));
        
        wp_send_json_success(['message' => 'مسئول وظیفه با موفقیت به‌روزرسانی شد.']);
    }
    
    /**
     * AJAX handler for deleting a user.
     */
    public function ajax_delete_user() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');

        if (!current_user_can('delete_users') || !isset($_POST['user_id']) || !isset($_POST['nonce'])) {
            wp_send_json_error(['message' => 'شما دسترسی لازم برای حذف کاربر را ندارید.']);
        }

        $user_id_to_delete = intval($_POST['user_id']);
        $current_user_id = get_current_user_id();

        if (!wp_verify_nonce($_POST['nonce'], 'puzzling_delete_user_' . $user_id_to_delete)) {
            wp_send_json_error(['message' => 'خطای امنیتی. لطفاً صفحه را رفرش کنید.']);
        }

        if ($user_id_to_delete === $current_user_id) {
            wp_send_json_error(['message' => 'شما نمی‌توانید حساب کاربری خود را حذف کنید.']);
        }

        if ($user_id_to_delete === 1) {
            wp_send_json_error(['message' => 'امکان حذف مدیر اصلی سایت وجود ندارد.']);
        }

        if (!current_user_can('delete_user', $user_id_to_delete)) {
            wp_send_json_error(['message' => 'شما اجازه حذف این کاربر را ندارید.']);
        }
        
        if (wp_delete_user($user_id_to_delete)) {
            wp_send_json_success(['message' => 'کاربر با موفقیت حذف شد.']);
        } else {
            wp_send_json_error(['message' => 'خطایی در هنگام حذف کاربر رخ داد.']);
        }
    }


    /**
     * AJAX handler for live searching users in the customer management page.
     */
    public function ajax_search_users() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        if (!current_user_can('manage_options')) {
            wp_send_json_error();
        }

        $search_query = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : '';
        $args = [
            'orderby' => 'display_name', 
            'order' => 'ASC',
            'search' => '*' . esc_attr($search_query) . '*',
            'search_columns' => ['user_login', 'user_email', 'user_nicename', 'display_name'],
            'meta_query' => [
                'relation' => 'OR',
                [
                    'key' => 'pzl_mobile_phone',
                    'value' => $search_query,
                    'compare' => 'LIKE'
                ],
                [
                    'key' => 'pzl_national_id',
                    'value' => $search_query,
                    'compare' => 'LIKE'
                ],
                [
                    'key' => 'first_name',
                    'value' => $search_query,
                    'compare' => 'LIKE'
                ],
                [
                    'key' => 'last_name',
                    'value' => $search_query,
                    'compare' => 'LIKE'
                ]
            ]
        ];
        
        if (empty($search_query)) {
            unset($args['search']);
            unset($args['meta_query']);
        }

        $all_users = get_users($args);
        $output_html = '';

        if (empty($all_users)) {
            $output_html = '<tr><td colspan="5">هیچ کاربری با این مشخصات یافت نشد.</td></tr>';
        } else {
            foreach ($all_users as $user) {
                $edit_url = add_query_arg(['view' => 'customers', 'action' => 'edit', 'user_id' => $user->ID]);
                $role_name = !empty($user->roles) ? esc_html(wp_roles()->roles[$user->roles[0]]['name']) : '---';
                $registered_date = jdate('Y/m/d', strtotime($user->user_registered));
                
                $output_html .= '<tr data-user-row-id="'. esc_attr($user->ID) .'">';
                $output_html .= '<td>' . get_avatar($user->ID, 32) . ' ' . esc_html($user->display_name) . '</td>';
                $output_html .= '<td>' . esc_html($user->user_email) . '</td>';
                $output_html .= '<td>' . $role_name . '</td>';
                $output_html .= '<td>' . $registered_date . '</td>';
                $output_html .= '<td>';
                $output_html .= '<a href="' . esc_url($edit_url) . '" class="pzl-button pzl-button-sm">ویرایش</a> ';
                $output_html .= '<button class="pzl-button pzl-button-sm send-sms-btn" data-user-id="' . esc_attr($user->ID) . '" data-user-name="' . esc_attr($user->display_name) . '"><i class="fas fa-sms"></i></button>';
                if ( get_current_user_id() != $user->ID && $user->ID != 1 ) {
                    $output_html .= ' <button class="pzl-button pzl-button-sm delete-user-btn" data-user-id="'. esc_attr($user->ID) .'" data-nonce="'. wp_create_nonce('puzzling_delete_user_' . $user->ID) .'" style="background-color: #dc3545 !important;">حذف</button>';
                }
                $output_html .= '</td>';
                $output_html .= '</tr>';
            }
        }

        wp_send_json_success(['html' => $output_html]);
    }

    /**
     * AJAX handler for users updating their own profile.
     */
    public function ajax_update_my_profile() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'شما وارد نشده‌اید.']);
        }

        $user_id = get_current_user_id();
        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);
        $password = $_POST['password'];
        $password_confirm = $_POST['password_confirm'];

        $user_data = [
            'ID' => $user_id,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'display_name' => trim($first_name . ' ' . $last_name)
        ];

        if (!empty($password)) {
            if ($password !== $password_confirm) {
                wp_send_json_error(['message' => 'رمزهای عبور وارد شده یکسان نیستند.']);
            }
            $user_data['user_pass'] = $password;
        }

        $result = wp_update_user($user_data);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => 'خطا در به‌روزرسانی پروفایل: ' . $result->get_error_message()]);
        }

        if (isset($_POST['pzl_mobile_phone'])) {
            update_user_meta($user_id, 'pzl_mobile_phone', sanitize_text_field($_POST['pzl_mobile_phone']));
        }

        if (!empty($_FILES['pzl_profile_picture']['name'])) {
            $attachment_id = media_handle_upload('pzl_profile_picture', 0); // 0 means not attached to a post
            if (!is_wp_error($attachment_id)) {
                update_user_meta($user_id, 'pzl_profile_picture_id', $attachment_id);
            }
        }
        
        wp_send_json_success(['message' => 'پروفایل شما با موفقیت به‌روزرسانی شد.', 'reload' => true]);
    }
    
    /**
     * Universal AJAX handler for creating and updating users (staff and customers).
     */
    public function ajax_manage_user() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'شما دسترسی لازم برای مدیریت کاربران را ندارید.']);
        }

        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        
        $email = sanitize_email($_POST['email']);
        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);
        $password = $_POST['password'];
        $role = sanitize_key($_POST['role']);
        
        if (!is_email($email) || empty($last_name) || empty($role)) {
            wp_send_json_error(['message' => 'لطفاً فیلدهای ضروری (نام خانوادگی، ایمیل و نقش) را پر کنید.']);
        }
        if ($user_id === 0 && empty($password)) {
            wp_send_json_error(['message' => 'برای کاربر جدید، وارد کردن رمز عبور ضروری است.']);
        }

        $user_data = [
            'user_email' => $email, 'first_name' => $first_name, 'last_name' => $last_name,
            'display_name' => trim($first_name . ' ' . $last_name), 'role' => $role
        ];
        if (!empty($password)) $user_data['user_pass'] = $password;

        if ($user_id > 0) {
            $user_data['ID'] = $user_id;
            $result = wp_update_user($user_data);
            $message = 'پروفایل با موفقیت به‌روزرسانی شد.';
        } else {
            if (email_exists($email)) {
                wp_send_json_error(['message' => 'کاربری با این ایمیل از قبل وجود دارد.']);
            }
            $user_data['user_login'] = $email;
            $result = wp_insert_user($user_data);
            $message = 'کاربر جدید با موفقیت ایجاد شد.';
        }

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => 'خطا در ذخیره‌سازی اطلاعات کاربر: ' . $result->get_error_message()]);
        } else {
            $the_user_id = is_int($result) ? $result : $user_id;
            
            foreach ($_POST as $key => $value) {
                if (strpos($key, 'pzl_') === 0) {
                    $sanitized_value = sanitize_text_field($value);
                    if ($key === 'pzl_birth_date' || $key === 'pzl_hire_date') {
                        $sanitized_value = puzzling_jalali_to_gregorian($sanitized_value);
                    }
                    update_user_meta($the_user_id, $key, $sanitized_value);
                }
            }
            
            $department_id = isset($_POST['department']) ? intval($_POST['department']) : 0;
            $job_title_id = isset($_POST['job_title']) ? intval($_POST['job_title']) : 0;

            $term_to_set = 0;
            if ($job_title_id > 0) {
                $term_to_set = $job_title_id;
            } elseif ($department_id > 0) {
                $term_to_set = $department_id;
            }

            wp_set_object_terms($the_user_id, $term_to_set, 'organizational_position', false);
            
            if (!empty($_FILES['pzl_profile_picture']['name'])) {
                $attachment_id = media_handle_upload('pzl_profile_picture', $the_user_id);
                if (!is_wp_error($attachment_id)) {
                    update_user_meta($the_user_id, 'pzl_profile_picture_id', $attachment_id);
                }
            }
            
            wp_send_json_success(['message' => $message, 'reload' => true]);
        }
    }

    /**
     * AJAX handler for creating and updating projects.
     */
    public function ajax_manage_project() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.']);
        }

        $project_id = isset($_POST['project_id']) ? intval($_POST['project_id']) : 0;
        $contract_id = isset($_POST['contract_id']) ? intval($_POST['contract_id']) : 0;
        $project_title = sanitize_text_field($_POST['project_title']);
        $project_content = wp_kses_post($_POST['project_content']);
        $project_status_id = intval($_POST['project_status']);

        if (empty($project_title) || empty($contract_id)) {
            wp_send_json_error(['message' => 'عنوان پروژه و انتخاب قرارداد الزامی است.']);
        }

        $contract = get_post($contract_id);
        if (!$contract) {
            wp_send_json_error(['message' => 'قرارداد انتخاب شده معتبر نیست.']);
        }
        $customer_id = $contract->post_author;

        $post_data = [
            'post_title' => $project_title,
            'post_content' => $project_content,
            'post_author' => $customer_id,
            'post_status' => 'publish',
            'post_type' => 'project',
        ];

        if ($project_id > 0) { // Update existing project
            $post_data['ID'] = $project_id;
            $result = wp_update_post($post_data, true);
            $message = 'پروژه با موفقیت به‌روزرسانی شد.';
        } else { // Create new project
            $result = wp_insert_post($post_data, true);
            $message = 'پروژه جدید با موفقیت ایجاد شد.';
        }

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => 'خطا در پردازش پروژه.']);
        }

        $the_project_id = is_int($result) ? $result : $project_id;
        
        update_post_meta($the_project_id, '_contract_id', $contract_id);
        
        wp_set_object_terms($the_project_id, $project_status_id, 'project_status');
        
        if (isset($_FILES['project_logo']) && $_FILES['project_logo']['error'] == 0) {
            $attachment_id = media_handle_upload('project_logo', $the_project_id);
            if (!is_wp_error($attachment_id)) {
                set_post_thumbnail($the_project_id, $attachment_id);
            }
        }
        
        wp_send_json_success(['message' => $message, 'reload' => true]);
    }
    
    /**
     * AJAX handler for creating and updating contracts (without projects).
     */
    public function ajax_manage_contract() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.']);
        }
    
        $contract_id = isset($_POST['contract_id']) ? intval($_POST['contract_id']) : 0;
        $customer_id = intval($_POST['customer_id']);
    
        if (empty($customer_id)) {
            wp_send_json_error(['message' => 'انتخاب مشتری الزامی است.']);
        }
        
        $contract_title = sprintf('قرارداد با %s', get_the_author_meta('display_name', $customer_id));
        if (isset($_POST['contract_title']) && !empty($_POST['contract_title'])) {
            $contract_title = sanitize_text_field($_POST['contract_title']);
        }
        
        $contract_data = [
            'post_title' => $contract_title,
            'post_author' => $customer_id,
            'post_status' => 'publish',
            'post_type' => 'contract',
        ];
    
        $start_date_jalali = sanitize_text_field($_POST['_project_start_date']);
        $start_date_gregorian = puzzling_jalali_to_gregorian($start_date_jalali);
        $duration = sanitize_key($_POST['_project_contract_duration']);
        $end_date_gregorian = '';
    
        if($start_date_gregorian && $duration) {
            $end_date = new DateTime($start_date_gregorian);
            switch ($duration) {
                case '1-month': $end_date->modify('+1 month'); break;
                case '3-months': $end_date->modify('+3 months'); break;
                case '6-months': $end_date->modify('+6 months'); break;
                case '12-months': $end_date->modify('+1 year'); break;
            }
            $end_date_gregorian = $end_date->format('Y-m-d');
        }
    
        $contract_meta = [
            '_project_subscription_model' => sanitize_key($_POST['_project_subscription_model']),
            '_project_contract_duration' => $duration,
            '_project_start_date' => $start_date_gregorian,
            '_project_end_date' => $end_date_gregorian,
        ];
    
        $payment_amounts = isset($_POST['payment_amount']) ? (array) $_POST['payment_amount'] : [];
        $payment_due_dates = isset($_POST['payment_due_date']) ? (array) $_POST['payment_due_date'] : [];
        $payment_statuses = isset($_POST['payment_status']) ? (array) $_POST['payment_status'] : [];
        $installments = [];
        for ($i = 0; $i < count($payment_amounts); $i++) {
            if (!empty($payment_amounts[$i]) && !empty($payment_due_dates[$i])) {
                $installments[] = [
                    'amount' => sanitize_text_field(str_replace(',', '', $payment_amounts[$i])), 
                    'due_date' => puzzling_jalali_to_gregorian(sanitize_text_field($payment_due_dates[$i])), 
                    'status' => sanitize_key($payment_statuses[$i] ?? 'pending'),
                ];
            }
        }
    
        if ($contract_id > 0) {
            $contract_data['ID'] = $contract_id;
            $result = wp_update_post($contract_data, true);
            $message = 'قرارداد با موفقیت به‌روزرسانی شد.';
        } else {
            $result = wp_insert_post($contract_data, true);
            $message = 'قرارداد جدید با موفقیت ایجاد شد.';
        }
    
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => 'خطا در پردازش قرارداد.']);
        }
        
        $the_contract_id = is_int($result) ? $result : $contract_id;
        
        foreach ($contract_meta as $key => $value) {
            update_post_meta($the_contract_id, $key, $value);
        }
        update_post_meta($the_contract_id, '_installments', $installments);
        
        wp_send_json_success(['message' => $message, 'reload' => true]);
    }

    /**
     * AJAX handler to add a new (secondary) project to an existing contract.
     */
    public function ajax_add_project_to_contract() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.']);
        }

        $contract_id = isset($_POST['contract_id']) ? intval($_POST['contract_id']) : 0;
        $project_title = isset($_POST['project_title']) ? sanitize_text_field($_POST['project_title']) : '';
        
        if (empty($contract_id) || empty($project_title)) {
            wp_send_json_error(['message' => 'اطلاعات قرارداد یا عنوان پروژه ناقص است.']);
        }

        $contract = get_post($contract_id);
        if (!$contract || $contract->post_type !== 'contract') {
             wp_send_json_error(['message' => 'قرارداد معتبر نیست.']);
        }

        $project_id = wp_insert_post([
            'post_title' => $project_title,
            'post_author' => $contract->post_author,
            'post_status' => 'publish',
            'post_type' => 'project'
        ], true);

        if (is_wp_error($project_id)) {
            wp_send_json_error(['message' => 'خطا در ایجاد پروژه جدید.']);
        }

        update_post_meta($project_id, '_contract_id', $contract_id);
        
        $active_status = get_term_by('slug', 'active', 'project_status');
        if ($active_status) {
            wp_set_object_terms($project_id, $active_status->term_id, 'project_status');
        }

        wp_send_json_success(['message' => 'پروژه جدید با موفقیت به قرارداد اضافه شد.', 'reload' => true]);
    }
    
    /**
     * AJAX handler to create projects from a WooCommerce product for a contract.
     */
    public function ajax_add_services_from_product() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.']);
        }

        $contract_id = isset($_POST['contract_id']) ? intval($_POST['contract_id']) : 0;
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        
        if (empty($contract_id) || empty($product_id)) {
            wp_send_json_error(['message' => 'اطلاعات قرارداد یا محصول ناقص است.']);
        }

        $contract = get_post($contract_id);
        $product = wc_get_product($product_id);

        if (!$contract || !$product) {
            wp_send_json_error(['message' => 'قرارداد یا محصول یافت نشد.']);
        }

        $created_projects_count = 0;
        $child_products = [];
        
        if ($product->is_type('grouped')) {
            $child_products = $product->get_children();
        } else {
            $child_products[] = $product->get_id();
        }
        
        foreach($child_products as $child_product_id) {
            $child_product = wc_get_product($child_product_id);
            if (!$child_product) continue;
            
            $project_id = wp_insert_post([
                'post_title' => $child_product->get_name(),
                'post_author' => $contract->post_author,
                'post_status' => 'publish',
                'post_type' => 'project'
            ], true);
            
            if (!is_wp_error($project_id)) {
                update_post_meta($project_id, '_contract_id', $contract_id);
                $active_status = get_term_by('slug', 'active', 'project_status');
                if ($active_status) {
                    wp_set_object_terms($project_id, $active_status->term_id, 'project_status');
                }
                $created_projects_count++;
            }
        }

        if($created_projects_count > 0) {
            wp_send_json_success(['message' => $created_projects_count . ' پروژه با موفقیت از محصول ایجاد و به این قرارداد متصل شد.', 'reload' => true]);
        } else {
            wp_send_json_error(['message' => 'هیچ پروژه‌ای از محصول انتخاب شده ایجاد نشد. ممکن است محصول فاقد خدمات قابل تبدیل به پروژه باشد.']);
        }
    }


    /**
     * Logs an activity to a task's metadata.
     */
    private function _log_task_activity($task_id, $activity_text) {
        $activity_log = get_post_meta($task_id, '_task_activity_log', true);
        if (!is_array($activity_log)) {
            $activity_log = [];
        }
        $current_user = wp_get_current_user();
        $new_log_entry = [
            'user_id' => $current_user->ID,
            'user_name' => $current_user->display_name,
            'text' => $activity_text,
            'time' => current_time('mysql'),
        ];
        array_unshift($activity_log, $new_log_entry);
        update_post_meta($task_id, '_task_activity_log', $activity_log);
    }

    private function notify_all_admins($title, $args) {
        $admins = get_users(['role__in' => ['administrator', 'system_manager'], 'fields' => 'ID']);
        foreach ($admins as $admin_id) {
            $notification_args = array_merge($args, ['user_id' => $admin_id]);
            PuzzlingCRM_Logger::add($title, $notification_args);
        }
    }

    /**
     * Executes automation rules based on a trigger.
     */
    private function execute_automations($trigger, $task_id, $trigger_value = null) {
        $settings = PuzzlingCRM_Settings_Handler::get_all_settings();
        $automations = $settings['automations'] ?? [];

        foreach ($automations as $automation) {
            $rule_trigger = $automation['trigger'] ?? '';
            $rule_action = $automation['action'] ?? '';
            $rule_value = $automation['value'] ?? '';
            
            $trigger_condition_met = ($rule_trigger === $trigger);

            if ($trigger_condition_met) {
                switch ($rule_action) {
                    case 'change_status':
                        $term = get_term_by('slug', $rule_value, 'task_status');
                        if ($term) {
                            wp_set_post_terms($task_id, $term->term_id, 'task_status');
                            $this->_log_task_activity($task_id, sprintf('وضعیت به صورت خودکار به "%s" تغییر کرد.', $term->name));
                        }
                        break;
                    case 'assign_user':
                        $user_id = intval($rule_value);
                        if (get_user_by('ID', $user_id)) {
                            update_post_meta($task_id, '_assigned_to', $user_id);
                            $this->_log_task_activity($task_id, sprintf('وظیفه به صورت خودکار به "%s" تخصیص داده شد.', get_the_author_meta('display_name', $user_id)));
                        }
                        break;
                    case 'add_comment':
                        wp_insert_comment(['comment_post_ID' => $task_id, 'comment_content' => $rule_value, 'user_id' => 0, 'comment_author' => 'سیستم اتوماسیون', 'comment_author_email' => 'system@puzzling.com', 'comment_approved' => 1]);
                        $this->_log_task_activity($task_id, 'یک کامنت خودکار توسط سیستم ثبت شد.');
                        break;
                }
            }
        }
    }

    public function add_task() {
		check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
		if (!current_user_can('edit_tasks') || !isset($_POST['title'])) {
			wp_send_json_error(['message' => 'دسترسی غیرمجاز یا اطلاعات ناقص.']);
		}
	
		$settings = PuzzlingCRM_Settings_Handler::get_all_settings();
		$notification_prefs = $settings['notifications']['new_task'] ?? [];
	
		$title = sanitize_text_field($_POST['title']);
		$content = isset($_POST['content']) ? wp_kses_post($_POST['content']) : '';
		$task_category_id = isset($_POST['task_category']) ? intval($_POST['task_category']) : 0;
		$due_date = puzzling_jalali_to_gregorian(sanitize_text_field($_POST['due_date']));
		$project_id = isset($_POST['project_id']) ? intval($_POST['project_id']) : 0;
		$parent_id = isset($_POST['parent_id']) ? intval($_POST['parent_id']) : 0;
		$story_points = isset($_POST['story_points']) ? sanitize_text_field($_POST['story_points']) : '';
		$task_labels = isset($_POST['task_labels']) ? sanitize_text_field($_POST['task_labels']) : '';
		$show_to_customer = isset($_POST['show_to_customer']) ? 1 : 0;
	
		$assigned_to_user = isset($_POST['assigned_to']) ? intval($_POST['assigned_to']) : 0;
		$assigned_to_role = isset($_POST['assigned_role']) ? intval($_POST['assigned_role']) : 0;
	
		if (empty($project_id)) {
			wp_send_json_error(['message' => 'لطفاً یک پروژه را برای تسک انتخاب کنید.']);
		}
		if (empty($assigned_to_user) && empty($assigned_to_role)) {
			wp_send_json_error(['message' => 'لطفاً یک کارمند یا یک نقش مسئول برای تسک انتخاب کنید.']);
		}
	
		$task_id = wp_insert_post([
			'post_title' => $title,
			'post_content' => $content,
			'post_type' => 'task',
			'post_status' => 'publish',
			'post_author' => get_current_user_id(),
			'post_parent' => $parent_id
		]);
	
		if (is_wp_error($task_id)) {
			wp_send_json_error(['message' => 'خطا در ایجاد تسک.']);
		}
	
		update_post_meta($task_id, '_project_id', $project_id);
		update_post_meta($task_id, '_show_to_customer', $show_to_customer);
		if (!empty($due_date)) update_post_meta($task_id, '_due_date', $due_date);
		if (!empty($story_points)) update_post_meta($task_id, '_story_points', $story_points);
	
		wp_set_post_terms($task_id, $task_category_id, 'task_category');
        wp_set_post_terms($task_id, puzzling_get_default_task_status_slug(), 'task_status');
		if (!empty($task_labels)) {
			$labels_array = array_map('trim', explode(',', $task_labels));
			wp_set_post_terms($task_id, $labels_array, 'task_label');
		}
	
		$assigned_user_ids = [];
		if ($assigned_to_user > 0) {
			update_post_meta($task_id, '_assigned_to', $assigned_to_user);
			$assigned_user_ids[] = $assigned_to_user;
		} elseif ($assigned_to_role > 0) {
			update_post_meta($task_id, '_assigned_role', $assigned_to_role);
			$users_with_role = get_users(['tax_query' => [['taxonomy' => 'organizational_position', 'field' => 'term_id', 'terms' => $assigned_to_role]], 'fields' => 'ID']);
			if (!empty($users_with_role)) {
				update_post_meta($task_id, '_assigned_to_multiple', $users_with_role);
				update_post_meta($task_id, '_assigned_to', $users_with_role[0]);
				$assigned_user_ids = $users_with_role;
			}
		}
	
		$this->_log_task_activity($task_id, 'وظیفه را ایجاد کرد.');
	
		if (!empty($_FILES['task_attachments'])) {
            $attachment_ids = [];
            $files = $_FILES['task_attachments'];
            foreach ($files['name'] as $key => $value) {
                if ($files['name'][$key]) {
                    $file = [
                        'name'     => $files['name'][$key],
                        'type'     => $files['type'][$key],
                        'tmp_name' => $files['tmp_name'][$key],
                        'error'    => $files['error'][$key],
                        'size'     => $files['size'][$key]
                    ];
                    $_FILES = ["task_attachment_single" => $file];
                    $attachment_id = media_handle_upload("task_attachment_single", $task_id);
                    if (!is_wp_error($attachment_id)) {
                        $attachment_ids[] = $attachment_id;
                    }
                }
            }
            if(!empty($attachment_ids)) {
                update_post_meta($task_id, '_task_attachments', $attachment_ids);
            }
		}
	
		$project_title = get_the_title($project_id);
		$notification_message_plain = "تسک جدید '{$title}' در پروژه '{$project_title}' به شما تخصیص داده شد.";
		$notification_message_html = "تسک جدید <b>'{$title}'</b> در پروژه <b>'{$project_title}'</b> به شما تخصیص داده شد.";
	
		foreach ($assigned_user_ids as $user_id_to_notify) {
			$user = get_userdata($user_id_to_notify);
			if (!$user) continue;
	
			PuzzlingCRM_Logger::add('تسک جدید به شما محول شد', ['content' => $notification_message_plain, 'type' => 'notification', 'user_id' => $user_id_to_notify, 'object_id' => $task_id]);
	
			if (!empty($notification_prefs['email'])) {
				$this->send_task_assignment_email($user_id_to_notify, $task_id);
			}
	
			if (!empty($notification_prefs['sms'])) {
				$sms_handler = PuzzlingCRM_Cron_Handler::get_sms_handler($settings);
				$phone = get_user_meta($user_id_to_notify, 'pzl_mobile_phone', true);
				if ($sms_handler && !empty($phone)) {
					$sms_handler->send_sms($phone, $notification_message_plain);
				}
			}
	
			if (!empty($notification_prefs['telegram'])) {
				$bot_token = $settings['telegram_bot_token'] ?? '';
				$chat_id = $settings['telegram_chat_id'] ?? '';
				if (!empty($bot_token) && !empty($chat_id)) {
					$telegram_handler = new PuzzlingCRM_Telegram_Handler($bot_token, $chat_id);
					$telegram_handler->send_message("کاربر گرامی {$user->display_name},\n" . $notification_message_html);
				}
			}
		}
	
		wp_send_json_success(['message' => 'تسک با موفقیت اضافه شد و اطلاع‌رسانی‌ها ارسال گردید.', 'reload' => true]);
    }

    public function quick_add_task() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        if (!current_user_can('edit_tasks') || !isset($_POST['title'])) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز یا اطلاعات ناقص.']);
        }

        $title = sanitize_text_field($_POST['title']);
        $status_slug = sanitize_key($_POST['status_slug']);
        $project_id = isset($_POST['project_id']) ? intval($_POST['project_id']) : 0;
        $assigned_to = isset($_POST['assigned_to']) ? intval($_POST['assigned_to']) : 0;
        
        if (empty($project_id) || empty($assigned_to)) {
             wp_send_json_error(['message' => 'برای افزودن سریع، لطفاً ابتدا برد را بر اساس پروژه و کارمند فیلتر کنید.']);
        }

        $task_id = wp_insert_post(['post_title' => $title, 'post_type' => 'task', 'post_status' => 'publish', 'post_author' => get_current_user_id()]);
        if (is_wp_error($task_id)) {
            wp_send_json_error(['message' => 'خطا در ایجاد تسک.']);
        }

        update_post_meta($task_id, '_project_id', $project_id);
        update_post_meta($task_id, '_assigned_to', $assigned_to);
        wp_set_post_terms($task_id, $status_slug, 'task_status');
        
        $medium_priority = get_term_by('slug', 'medium', 'task_priority');
        if ($medium_priority) {
            wp_set_post_terms($task_id, $medium_priority->term_id, 'task_priority');
        }

		$default_cat = get_term_by('slug', 'project-based', 'task_category');
		if ($default_cat) {
			wp_set_object_terms($task_id, $default_cat->term_id, 'task_category');
		}


        $this->_log_task_activity($task_id, 'وظیفه را به صورت سریع ایجاد کرد.');
        $this->send_task_assignment_email($assigned_to, $task_id);
        
        $project_title = get_the_title($project_id);
        PuzzlingCRM_Logger::add('تسک جدید به شما محول شد', ['content' => "تسک '{$title}' در پروژه '{$project_title}' به شما تخصیص داده شد.", 'type' => 'notification', 'user_id' => $assigned_to, 'object_id' => $task_id]);

        $task_html = function_exists('puzzling_render_task_card') ? puzzling_render_task_card(get_post($task_id)) : '';
        wp_send_json_success(['message' => 'تسک سریع با موفقیت اضافه شد.', 'task_html' => $task_html]);
    }
    
    private function send_task_assignment_email($user_id, $task_id) {
        $user = get_userdata($user_id);
        $task = get_post($task_id);
        if (!$user || !$task) return;

        $project_title = get_the_title(get_post_meta($task_id, '_project_id', true));
        $dashboard_url = function_exists('puzzling_get_dashboard_url') ? puzzling_get_dashboard_url() : home_url();
        
        $to = $user->user_email;
        $subject = 'یک تسک جدید به شما تخصیص داده شد: ' . $task->post_title;
        $body = '<p>سلام ' . esc_html($user->display_name) . '،</p>';
        $body .= '<p>یک تسک جدید در پروژه <strong>' . esc_html($project_title) . '</strong> به شما محول شده است:</p>';
        $body .= '<ul><li><strong>عنوان تسک:</strong> ' . esc_html($task->post_title) . '</li></ul>';
        $body .= '<p>برای مشاهده جزئیات به داشبورد مراجعه کنید:</p>';
        $body .= '<p><a href="' . esc_url($dashboard_url) . '">رفتن به داشبورد</a></p>';
        
        wp_mail($to, $subject, $body, ['Content-Type: text/html; charset=UTF-8']);
    }
    
    public function update_task_status() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        if (!current_user_can('edit_tasks') || !isset($_POST['task_id']) || !isset($_POST['new_status_slug'])) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز یا اطلاعات ناقص.']);
        }

        $rules = PuzzlingCRM_Settings_Handler::get_setting('workflow_rules', []);
        $task_id = intval($_POST['task_id']);
        $new_status_slug = sanitize_key($_POST['new_status_slug']);
        $user = wp_get_current_user();

        if (isset($rules[$new_status_slug]) && !empty($rules[$new_status_slug])) {
            if (empty(array_intersect($rules[$new_status_slug], $user->roles))) {
                wp_send_json_error(['message' => 'شما اجازه انتقال وظیفه به این وضعیت را ندارید.']);
                return;
            }
        }

        $old_status_terms = wp_get_post_terms($task_id, 'task_status');
        $old_status_name = !empty($old_status_terms) ? $old_status_terms[0]->name : 'نامشخص';
        $term = get_term_by('slug', $new_status_slug, 'task_status');

        if ($term) {
            wp_set_post_terms($task_id, $term->term_id, 'task_status');
            $this->_log_task_activity($task_id, sprintf('وضعیت وظیفه را از "%s" به "%s" تغییر داد.', $old_status_name, $term->name));
            $this->execute_automations('status_changed', $task_id, $new_status_slug);
            wp_send_json_success(['message' => 'وضعیت تسک به‌روزرسانی شد.']);
        } else {
             wp_send_json_error(['message' => 'وضعیت نامعتبر است.']);
        }
    }

    public function delete_task() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        if (!current_user_can('delete_tasks') || !isset($_POST['task_id'])) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.']);
        }

        $task_id = intval($_POST['task_id']);
        $task = get_post($task_id);
        if (!$task || (!current_user_can('manage_options') && $task->post_author != get_current_user_id())) {
            wp_send_json_error(['message' => 'شما اجازه حذف این تسک را ندارید.']);
        }

        $result = wp_delete_post($task_id, true);
        if ($result) {
            PuzzlingCRM_Logger::add('تسک حذف شد', ['content' => "تسک '{$task->post_title}' توسط " . wp_get_current_user()->display_name . " حذف شد.", 'type' => 'log', 'object_id' => $task_id]);
            wp_send_json_success(['message' => 'تسک با موفقیت حذف شد.']);
        } else {
            wp_send_json_error(['message' => 'خطا در حذف تسک.']);
        }
    }
    
    public function get_task_details() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        if (!isset($_POST['task_id']) || !current_user_can('edit_tasks')) {
            wp_send_json_error(['message' => 'خطای دسترسی.']);
        }

        $task_id = intval($_POST['task_id']);
        $task = get_post($task_id);
        if (!$task) {
            wp_send_json_error(['message' => 'وظیفه یافت نشد.']);
        }
        
        ob_start();
        include PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/modal-task-details.php';
        $html = ob_get_clean();
        
        wp_send_json_success(['html' => $html]);
    }
    
    public function save_task_content() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        if (!isset($_POST['task_id']) || !isset($_POST['content']) || !current_user_can('edit_tasks')) {
            wp_send_json_error(['message' => 'خطای دسترسی.']);
        }
        
        $task_id = intval($_POST['task_id']);
        $content = wp_kses_post($_POST['content']);
        $result = wp_update_post(['ID' => $task_id, 'post_content' => $content], true);
        
        $this->_log_task_activity($task_id, 'توضیحات وظیفه را به‌روزرسانی کرد.');

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => 'خطا در ذخیره‌سازی.']);
        } else {
            wp_send_json_success(['new_content_html' => wpautop($content)]);
        }
    }
    
    public function add_task_comment() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        if (!isset($_POST['task_id']) || !isset($_POST['comment_text']) || !current_user_can('edit_tasks')) {
            wp_send_json_error(['message' => 'خطای دسترسی.']);
        }
        
        $task_id = intval($_POST['task_id']);
        $comment_text = wp_kses_post($_POST['comment_text']);
        $user = wp_get_current_user();
        
        $comment_id = wp_insert_comment(['comment_post_ID' => $task_id, 'comment_author' => $user->display_name, 'comment_author_email' => $user->user_email, 'comment_content' => $comment_text, 'user_id' => $user->ID, 'comment_approved' => 1]);
        if ($comment_id) {
            $this->_log_task_activity($task_id, sprintf('یک نظر جدید ثبت کرد: "%s"', esc_html(wp_trim_words($comment_text, 10))));
            
            preg_match_all('/@(\w+)/', $comment_text, $matches);
            if (!empty($matches[1])) {
                foreach (array_unique($matches[1]) as $login) {
                    if ($mentioned_user = get_user_by('login', $login)) {
                         PuzzlingCRM_Logger::add(sprintf('شما در تسک "%s" منشن شدید', get_the_title($task_id)), ['content' => sprintf('%s شما را در یک نظر منشن کرد.', $user->display_name), 'type' => 'notification', 'user_id' => $mentioned_user->ID, 'object_id' => $task_id]);
                    }
                }
            }
            $this->execute_automations('comment_added', $task_id);

            $comment = get_comment($comment_id);
            ob_start();
            echo '<li class="pzl-comment-item"><div class="pzl-comment-avatar">' . get_avatar($comment->user_id, 32) . '</div><div class="pzl-comment-content"><p><strong>' . esc_html($comment->comment_author) . '</strong>: ' . wp_kses_post(wpautop($comment->comment_content)) . '</p><span class="pzl-comment-date">' . human_time_diff(strtotime($comment->comment_date), current_time('timestamp')) . ' پیش</span></div></li>';
            wp_send_json_success(['comment_html' => ob_get_clean()]);
        } else {
             wp_send_json_error(['message' => 'خطا در ثبت نظر.']);
        }
    }

    public function manage_checklist() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        if (!current_user_can('edit_tasks') || !isset($_POST['task_id']) || !isset($_POST['sub_action'])) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.']);
        }

        $task_id = intval($_POST['task_id']);
        $sub_action = sanitize_key($_POST['sub_action']);
        $checklist = get_post_meta($task_id, '_task_checklist', true) ?: [];

        switch ($sub_action) {
            case 'add':
                $text = sanitize_text_field($_POST['text']);
                if (empty($text)) wp_send_json_error(['message' => 'متن نمی‌تواند خالی باشد.']);
                $checklist['item_' . time()] = ['text' => $text, 'checked' => false];
                $this->_log_task_activity($task_id, sprintf('آیتم چک‌لیست "%s" را اضافه کرد.', $text));
                break;
            case 'toggle':
                $item_id = sanitize_key($_POST['item_id']);
                if (isset($checklist[$item_id])) {
                    $checklist[$item_id]['checked'] = !$checklist[$item_id]['checked'];
                    $log_action = $checklist[$item_id]['checked'] ? 'کامل' : 'ناکامل';
                    $this->_log_task_activity($task_id, sprintf('وضعیت آیتم چک‌لیست "%s" را به %s تغییر داد.', $checklist[$item_id]['text'], $log_action));
                }
                break;
            case 'delete':
                $item_id = sanitize_key($_POST['item_id']);
                if (isset($checklist[$item_id])) {
                    $this->_log_task_activity($task_id, sprintf('آیتم چک‌لیست "%s" را حذف کرد.', $checklist[$item_id]['text']));
                    unset($checklist[$item_id]);
                }
                break;
        }

        update_post_meta($task_id, '_task_checklist', $checklist);
        wp_send_json_success(['checklist' => $checklist]);
    }

    public function log_time() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        if (!current_user_can('edit_tasks') || !isset($_POST['task_id']) || !isset($_POST['hours'])) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.']);
        }

        $task_id = intval($_POST['task_id']);
        $hours = floatval($_POST['hours']);
        $description = sanitize_text_field($_POST['description']);
        if ($hours <= 0) wp_send_json_error(['message' => 'ساعت وارد شده باید بزرگتر از صفر باشد.']);
        
        $time_logs = get_post_meta($task_id, '_task_time_logs', true) ?: [];
        $current_user = wp_get_current_user();
        $new_log = ['user_id' => $current_user->ID, 'user_name' => $current_user->display_name, 'hours' => $hours, 'description' => $description, 'date' => current_time('mysql')];
        $time_logs[] = $new_log;
        update_post_meta($task_id, '_task_time_logs', $time_logs);
        
        $this->_log_task_activity($task_id, sprintf('%.2f ساعت زمان ثبت کرد.', $hours));

        wp_send_json_success(['new_log' => $new_log, 'total_hours' => array_sum(wp_list_pluck($time_logs, 'hours'))]);
    }

    public function save_status_order() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        if (!current_user_can('manage_options') || !isset($_POST['order'])) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.']);
        }
        global $wpdb;
        foreach ($_POST['order'] as $index => $term_id) {
            $wpdb->update($wpdb->terms, ['term_order' => $index + 1], ['term_id' => $term_id]);
        }
        clean_term_cache(array_values($_POST['order']), 'task_status');
        wp_send_json_success(['message' => 'ترتیب وضعیت‌ها ذخیره شد.']);
    }

    public function add_new_status() {
        check_ajax_referer('puzzling_add_new_status_nonce', 'security');
        if (!current_user_can('manage_options') || !isset($_POST['name'])) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.']);
        }

        $name = sanitize_text_field($_POST['name']);
        if (empty($name)) wp_send_json_error(['message' => 'نام وضعیت نمی‌تواند خالی باشد.']);

        $result = wp_insert_term($name, 'task_status');
        if (is_wp_error($result)) wp_send_json_error(['message' => $result->get_error_message()]);

        wp_send_json_success(['message' => 'وضعیت جدید اضافه شد.', 'term_id' => $result['term_id'], 'name' => $name, 'slug' => get_term($result['term_id'])->slug]);
    }

    public function delete_status() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        if (!current_user_can('manage_options') || !isset($_POST['term_id'])) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.']);
        }

        $term_id = intval($_POST['term_id']);
        $default_term = get_term_by('slug', 'to-do', 'task_status');
        if (!$default_term || $default_term->term_id == $term_id) {
            wp_send_json_error(['message' => 'وضعیت پیش‌فرض "To Do" یافت نشد یا در حال حذف آن هستید.']);
        }

        foreach (get_posts(['post_type' => 'task', 'tax_query' => [['taxonomy' => 'task_status','field' => 'term_id','terms' => $term_id]], 'posts_per_page' => -1]) as $task) {
            wp_set_object_terms($task->ID, $default_term->term_id, 'task_status');
        }

        $result = wp_delete_term($term_id, 'task_status');
        if (is_wp_error($result)) wp_send_json_error(['message' => $result->get_error_message()]);

        wp_send_json_success(['message' => 'وضعیت حذف شد.']);
    }

    public function get_notifications() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        
        $user_id = get_current_user_id();
        $args = ['post_type' => 'puzzling_log', 'author' => $user_id, 'posts_per_page' => 10, 'meta_query' => [['key' => '_log_type', 'value' => 'notification']]];
        $notifications = get_posts($args);
        $unread_count = count(get_posts(array_merge($args, ['meta_query' => ['relation' => 'AND', ['key' => '_log_type', 'value' => 'notification'], ['key' => '_is_read', 'value' => '0']]])));

        if (empty($notifications)) {
            wp_send_json_success(['count' => 0, 'html' => '<li class="pzl-no-notifications">هیچ اعلانی وجود ندارد.</li>']);
        }

        $html = '';
        foreach ($notifications as $note) {
            $is_read = get_post_meta($note->ID, '_is_read', true);
            $link = add_query_arg(['view' => 'tasks', 'open_task_id' => get_post_meta($note->ID, '_related_object_id', true)], puzzling_get_dashboard_url());
            $html .= sprintf('<li data-id="%d" class="%s"><a href="%s">%s <small>%s</small></a></li>', esc_attr($note->ID), ($is_read == '1' ? 'pzl-read' : 'pzl-unread'), esc_url($link), esc_html($note->post_title), esc_html(human_time_diff(get_the_time('U', $note->ID), current_time('timestamp')) . ' پیش'));
        }
        
        wp_send_json_success(['count' => $unread_count, 'html' => $html]);
    }

    public function mark_notification_read() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        if (isset($_POST['id'])) {
            $note = get_post(intval($_POST['id']));
            if ($note && $note->post_author == get_current_user_id()) {
                update_post_meta($note->ID, '_is_read', '1');
                wp_send_json_success(['message' => 'خوانده شد.']);
            }
        }
        wp_send_json_error(['message' => 'خطا.']);
    }
    
    public function delete_project() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        if (!current_user_can('delete_posts') || !isset($_POST['project_id']) || !isset($_POST['nonce'])) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.']);
        }
        
        $project_id = intval($_POST['project_id']);
        if (!wp_verify_nonce($_POST['nonce'], 'puzzling_delete_project_' . $project_id)) {
            wp_send_json_error(['message' => 'خطای امنیتی.']);
        }
        
        $project = get_post($project_id);
        if (!$project || $project->post_type !== 'project') {
            wp_send_json_error(['message' => 'پروژه یافت نشد.']);
        }
        
        if (wp_delete_post($project_id, true)) {
            PuzzlingCRM_Logger::add('پروژه حذف شد', ['content' => "پروژه '{$project->post_title}' توسط " . wp_get_current_user()->display_name . " حذف شد.", 'type' => 'log']);
            wp_send_json_success(['message' => 'پروژه با موفقیت حذف شد.']);
        } else {
            wp_send_json_error(['message' => 'خطا در حذف پروژه.']);
        }
    }

    public function quick_edit_task() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        if (!current_user_can('edit_tasks') || !isset($_POST['task_id']) || !isset($_POST['field'])) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.']);
        }
        
        $task_id = intval($_POST['task_id']);
        $field = sanitize_key($_POST['field']);
        $value = $_POST['value'];

        switch ($field) {
            case 'title':
                wp_update_post(['ID' => $task_id, 'post_title' => sanitize_text_field($value)]);
                $this->_log_task_activity($task_id, sprintf('عنوان وظیفه را به "%s" تغییر داد.', sanitize_text_field($value)));
                break;
            case 'due_date':
                update_post_meta($task_id, '_due_date', puzzling_jalali_to_gregorian(sanitize_text_field($value)));
                $this->_log_task_activity($task_id, sprintf('ددلاین را به "%s" تغییر داد.', sanitize_text_field($value)));
                break;
            case 'assignee':
                update_post_meta($task_id, '_assigned_to', intval($value));
                $this->_log_task_activity($task_id, sprintf('مسئول وظیفه را به "%s" تغییر داد.', get_userdata(intval($value))->display_name));
                break;
        }

        $task_html = function_exists('puzzling_render_task_card') ? puzzling_render_task_card(get_post($task_id)) : '';
        wp_send_json_success(['message' => 'وظیفه به‌روزرسانی شد.', 'task_html' => $task_html]);
    }

    public function get_tasks_for_views() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        if (!current_user_can('edit_tasks')) wp_send_json_error();

        $project_filter = isset($_POST['project_filter']) ? intval($_POST['project_filter']) : 0;
        
        $args = ['post_type' => 'task', 'posts_per_page' => -1];
        if($project_filter > 0){
            $args['meta_query'] = [
                [
                    'key' => '_project_id',
                    'value' => $project_filter
                ]
            ];
        }

        $tasks = new WP_Query($args);
        $events = $gantt_data = $gantt_links = [];

        if ($tasks->have_posts()) {
            while ($tasks->have_posts()) {
                $tasks->the_post();
                $due_date = get_post_meta(get_the_ID(), '_due_date', true);
                if($due_date) {
                    $events[] = ['id' => get_the_ID(), 'title' => get_the_title(), 'start' => $due_date, 'allDay' => true];
                    $gantt_data[] = ['id' => get_the_ID(), 'text' => get_the_title(), 'start_date' => get_the_date('Y-m-d'), 'end_date' => $due_date, 'parent' => get_post()->post_parent, 'open' => true];
                }
                if (get_post()->post_parent != 0) {
                    $gantt_links[] = ['id' => 'link_' . get_the_ID(), 'source' => get_post()->post_parent, 'target' => get_the_ID(), 'type' => '0'];
                }
            }
        }
        wp_reset_postdata();

        wp_send_json_success(['calendar_events' => $events, 'gantt_tasks' => ['data' => $gantt_data, 'links' => $gantt_links]]);
    }

    public function search_tasks_for_linking() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        if (!current_user_can('edit_tasks')) wp_send_json_error();

        $tasks = new WP_Query(['post_type' => 'task', 'posts_per_page' => 10, 's' => sanitize_text_field($_POST['search']), 'post__not_in' => [intval($_POST['current_task_id'])]]);
        $results = [];
        if ($tasks->have_posts()) {
            while ($tasks->have_posts()) { $tasks->the_post(); $results[] = ['id' => get_the_ID(), 'text' => '#' . get_the_ID() . ': ' . get_the_title()]; }
        }
        wp_reset_postdata();
        wp_send_json_success($results);
    }

    public function add_task_link() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        if (!current_user_can('edit_tasks')) wp_send_json_error();

        $from_id = intval($_POST['from_task_id']);
        $to_id = intval($_POST['to_task_id']);
        $type = sanitize_key($_POST['link_type']);

        $links = get_post_meta($from_id, '_task_links', true) ?: [];
        $links[] = ['type' => $type, 'task_id' => $to_id];
        update_post_meta($from_id, '_task_links', $links);

        $inverse_map = ['blocks' => 'is_blocked_by', 'is_blocked_by' => 'blocks', 'relates_to' => 'relates_to'];
        $inverse_links = get_post_meta($to_id, '_task_links', true) ?: [];
        $inverse_links[] = ['type' => $inverse_map[$type], 'task_id' => $from_id];
        update_post_meta($to_id, '_task_links', $inverse_links);
        
        $this->_log_task_activity($from_id, sprintf('وظیفه را به #%d با نوع "%s" پیوند داد.', $to_id, $type));
        $this->_log_task_activity($to_id, sprintf('وظیفه به #%d با نوع "%s" پیوند داده شد.', $from_id, $inverse_map[$type]));

        wp_send_json_success();
    }

    public function bulk_edit_tasks() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        if (!current_user_can('edit_tasks') || !isset($_POST['task_ids']) || !is_array($_POST['task_ids'])) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز یا اطلاعات ناقص.']);
        }

        $task_ids = array_map('intval', $_POST['task_ids']);
        $actions = $_POST['bulk_actions'];

        foreach ($task_ids as $task_id) {
            if (!empty($actions['status'])) wp_set_post_terms($task_id, sanitize_key($actions['status']), 'task_status');
            if (!empty($actions['assignee'])) update_post_meta($task_id, intval($actions['assignee']));
            if (!empty($actions['priority'])) wp_set_post_terms($task_id, intval($actions['priority']), 'task_priority');
        }
        
        wp_send_json_success(['message' => 'وظایف با موفقیت به‌روزرسانی شدند.']);
    }
    
    public function save_task_as_template() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        if (!current_user_can('edit_tasks') || !isset($_POST['task_id']) || !isset($_POST['template_name'])) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.']);
        }

        $task_id = intval($_POST['task_id']);
        $template_name = sanitize_text_field($_POST['template_name']);
        $source_task = get_post($task_id);

        if (!$source_task) wp_send_json_error(['message' => 'وظیفه منبع یافت نشد.']);

        $template_id = wp_insert_post(['post_title' => $template_name, 'post_content' => $source_task->post_content, 'post_type' => 'pzl_task_template', 'post_status' => 'publish']);
        if (is_wp_error($template_id)) wp_send_json_error(['message' => 'خطا در ایجاد قالب.']);
        
        $priority = wp_get_post_terms($task_id, 'task_priority');
        if(!is_wp_error($priority) && !empty($priority)) update_post_meta($template_id, '_template_priority', $priority[0]->term_id);
        
        update_post_meta($template_id, '_template_story_points', get_post_meta($task_id, '_story_points', true));
        update_post_meta($template_id, '_template_checklist', get_post_meta($task_id, '_task_checklist', true));

        wp_send_json_success(['message' => 'قالب با موفقیت ذخیره شد.']);
    }

    public function send_custom_sms() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        if (!current_user_can('manage_options') || !isset($_POST['user_id']) || !isset($_POST['message'])) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز یا اطلاعات ناقص.']);
        }

        try {
            $user_id = intval($_POST['user_id']);
            $message = sanitize_textarea_field($_POST['message']);
            $user_phone = get_user_meta($user_id, 'pzl_mobile_phone', true);

            if (empty($user_phone)) {
                wp_send_json_error(['message' => 'شماره موبایل برای این کاربر ثبت نشده است.']);
            }
            if (empty($message)) {
                wp_send_json_error(['message' => 'متن پیام نمی‌تواند خالی باشد.']);
            }
            
            $settings = PuzzlingCRM_Settings_Handler::get_all_settings();
            $sms_handler = PuzzlingCRM_Cron_Handler::get_sms_handler($settings);

            if (!$sms_handler) {
                wp_send_json_error(['message' => 'سرویس پیامک به درستی پیکربندی نشده است. لطفاً به بخش تنظیمات مراجعه کنید.']);
            }

            $success = $sms_handler->send_sms($user_phone, $message);

            if ($success) {
                PuzzlingCRM_Logger::add('ارسال پیامک دستی', ['content' => "یک پیامک دستی به کاربر با شناسه {$user_id} ارسال شد.", 'type' => 'log']);
                wp_send_json_success(['message' => 'پیامک با موفقیت ارسال شد.']);
            } else {
                wp_send_json_error(['message' => 'خطا در ارسال پیامک. لطفاً تنظیمات سرویس پیامک و لاگ‌های سرور را بررسی کنید.']);
            }
        } catch (Exception $e) {
            error_log('PuzzlingCRM SMS Error: ' . $e->getMessage());
            wp_send_json_error(['message' => 'یک خطای سیستمی در هنگام ارسال پیامک رخ داد. جزئیات خطا در لاگ سرور ثبت شد.']);
        }
    }
    
    /**
     * AJAX handler for creating/updating task categories.
     */
    public function ajax_manage_task_category() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'شما دسترسی لازم را ندارید.']);
        }

        $term_id = isset($_POST['term_id']) ? intval($_POST['term_id']) : 0;
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';

        if (empty($name)) {
            wp_send_json_error(['message' => 'نام دسته‌بندی نمی‌تواند خالی باشد.']);
        }

        if ($term_id > 0) {
            $result = wp_update_term($term_id, 'task_category', ['name' => $name]);
            $message = 'دسته‌بندی با موفقیت ویرایش شد.';
        } else {
            if (term_exists($name, 'task_category')) {
                wp_send_json_error(['message' => 'این دسته‌بندی از قبل وجود دارد.']);
            }
            $result = wp_insert_term($name, 'task_category');
            $message = 'دسته‌بندی جدید با موفقیت اضافه شد.';
        }

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        wp_send_json_success(['message' => $message, 'reload' => true]);
    }

    /**
     * AJAX handler for deleting a task category.
     */
    public function ajax_delete_task_category() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'شما دسترسی لازم را ندارید.']);
        }

        $term_id = isset($_POST['term_id']) ? intval($_POST['term_id']) : 0;
        if ($term_id === 0) {
            wp_send_json_error(['message' => 'شناسه نامعتبر است.']);
        }

        $result = wp_delete_term($term_id, 'task_category');

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        wp_send_json_success(['message' => 'دسته‌بندی با موفقیت حذف شد.', 'reload' => true]);
    }
    
    /**
     * AJAX handler for managing organizational positions (hierarchical).
     */
    public function ajax_manage_position() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'شما دسترسی لازم را ندارید.']);
        }
        $term_id = isset($_POST['term_id']) ? intval($_POST['term_id']) : 0;
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        $parent = isset($_POST['parent']) ? intval($_POST['parent']) : 0;

        if (empty($name)) {
            wp_send_json_error(['message' => 'نام جایگاه نمی‌تواند خالی باشد.']);
        }
        
        $args = ['parent' => $parent];

        if ($term_id > 0) {
            $result = wp_update_term($term_id, 'organizational_position', ['name' => $name, 'parent' => $parent]);
            $message = 'جایگاه با موفقیت ویرایش شد.';
        } else {
            if (term_exists($name, 'organizational_position', $parent)) {
                wp_send_json_error(['message' => 'این جایگاه در این سطح از قبل وجود دارد.']);
            }
            $result = wp_insert_term($name, 'organizational_position', $args);
            $message = 'جایگاه جدید با موفقیت اضافه شد.';
        }

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }
        wp_send_json_success(['message' => $message, 'reload' => true]);
    }

    /**
     * AJAX handler for deleting an organizational position.
     */
    public function ajax_delete_position() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'شما دسترسی لازم را ندارید.']);
        }
        $term_id = isset($_POST['term_id']) ? intval($_POST['term_id']) : 0;
        if ($term_id === 0) {
            wp_send_json_error(['message' => 'شناسه نامعتبر است.']);
        }
        $result = wp_delete_term($term_id, 'organizational_position');

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }
        wp_send_json_success(['message' => 'جایگاه با موفقیت حذف شد.', 'reload' => true]);
    }
}