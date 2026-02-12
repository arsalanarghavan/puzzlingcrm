<?php
/**
 * PuzzlingCRM User AJAX Handler
 *
 * @package PuzzlingCRM
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class PuzzlingCRM_User_Ajax_Handler {

    public function __construct() {
        add_action('wp_ajax_puzzling_manage_user', [$this, 'ajax_manage_user']);
        add_action('wp_ajax_puzzling_delete_user', [$this, 'ajax_delete_user']);
        add_action('wp_ajax_puzzling_update_my_profile', [$this, 'ajax_update_my_profile']);
        add_action('wp_ajax_puzzling_search_users', [$this, 'ajax_search_users']);
        add_action('wp_ajax_puzzlingcrm_get_users', [$this, 'ajax_get_users']);
    }

    /**
     * Get list of users for React dashboard (JSON).
     */
    public function ajax_get_users() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.']);
        }

        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $role_filter = isset($_POST['role']) ? sanitize_key($_POST['role']) : '';

        $args = [
            'orderby' => 'display_name',
            'order' => 'ASC',
        ];
        if (!empty($role_filter)) {
            if ($role_filter === 'staff') {
                $args['role__in'] = ['system_manager', 'finance_manager', 'team_member', 'administrator'];
            } else {
                $args['role'] = $role_filter;
            }
        }
        if (!empty($search)) {
            $args['search'] = '*' . $search . '*';
            $args['search_columns'] = ['user_login', 'user_email', 'user_nicename', 'display_name'];
            $args['meta_query'] = [
                'relation' => 'OR',
                ['key' => 'pzl_mobile_phone', 'value' => $search, 'compare' => 'LIKE'],
                ['key' => 'pzl_national_id', 'value' => $search, 'compare' => 'LIKE'],
                ['key' => 'first_name', 'value' => $search, 'compare' => 'LIKE'],
                ['key' => 'last_name', 'value' => $search, 'compare' => 'LIKE'],
            ];
        } else {
            unset($args['meta_query']);
        }

        $users = get_users($args);
        $user_counts = count_users();

        $items = [];
        foreach ($users as $user) {
            $phone = get_user_meta($user->ID, 'pzl_mobile_phone', true);
            $role_slug = !empty($user->roles) ? $user->roles[0] : '';
            $role_name = !empty($user->roles) && isset(wp_roles()->roles[$user->roles[0]])
                ? wp_roles()->roles[$user->roles[0]]['name'] : '---';
            $avatar_url = get_avatar_url($user->ID, ['size' => 40]);

            $can_delete = (get_current_user_id() != $user->ID && $user->ID != 1);
            $department_name = '---';
            $job_title_name = '---';
            if ($role_filter === 'staff' || in_array($role_slug, ['system_manager', 'finance_manager', 'team_member', 'administrator'], true)) {
                $positions = wp_get_object_terms($user->ID, 'organizational_position');
                if (!is_wp_error($positions) && !empty($positions)) {
                    $pos = $positions[0];
                    if ((int) $pos->parent === 0) {
                        $department_name = $pos->name;
                    } else {
                        $job_title_name = $pos->name;
                        $parent_dept = get_term($pos->parent, 'organizational_position');
                        if (!is_wp_error($parent_dept) && $parent_dept) {
                            $department_name = $parent_dept->name;
                        }
                    }
                }
            }
            $items[] = [
                'id' => $user->ID,
                'display_name' => $user->display_name,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->user_email,
                'phone' => $phone ?: '',
                'role_slug' => $role_slug,
                'role_name' => $role_name,
                'department_name' => $department_name,
                'job_title_name' => $job_title_name,
                'registered' => $user->user_registered,
                'registered_jalali' => function_exists('jdate') ? jdate('Y/m/d', strtotime($user->user_registered)) : date('Y/m/d', strtotime($user->user_registered)),
                'avatar_url' => $avatar_url,
                'delete_nonce' => $can_delete ? wp_create_nonce('puzzling_delete_user_' . $user->ID) : '',
                'can_delete' => $can_delete,
            ];
        }

        $stats = [
            'total' => $user_counts['total_users'],
            'customers' => $user_counts['avail_roles']['customer'] ?? 0,
            'staff' => 0,
        ];
        foreach (['system_manager', 'finance_manager', 'team_member', 'administrator'] as $r) {
            $stats['staff'] += $user_counts['avail_roles'][$r] ?? 0;
        }

        $roles = [];
        foreach (get_editable_roles() as $slug => $role_data) {
            $roles[] = ['slug' => $slug, 'name' => $role_data['name']];
        }

        $payload = [
            'users' => $items,
            'stats' => $stats,
            'roles' => $roles,
        ];
        if ( $role_filter === 'staff' ) {
            $all_positions = get_terms( [ 'taxonomy' => 'organizational_position', 'hide_empty' => false, 'orderby' => 'name' ] );
            $departments = [];
            $job_titles = [];
            if ( $all_positions && ! is_wp_error( $all_positions ) ) {
                foreach ( $all_positions as $t ) {
                    if ( (int) $t->parent === 0 ) {
                        $departments[] = [ 'id' => $t->term_id, 'name' => $t->name ];
                    } else {
                        $job_titles[] = [ 'id' => $t->term_id, 'name' => $t->name, 'parent' => $t->parent ];
                    }
                }
            }
            $payload['departments'] = $departments;
            $payload['job_titles'] = $job_titles;
        }

        wp_send_json_success( $payload );
    }

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
            $term_to_set = $job_title_id > 0 ? $job_title_id : ($department_id > 0 ? $department_id : 0);
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
                $output_html .= '<button class="pzl-button pzl-button-sm send-sms-btn" data-user-id="' . esc_attr($user->ID) . '" data-user-name="' . esc_attr($user->display_name) . '"><i class="ri-message-3-line"></i></button>';
                if ( get_current_user_id() != $user->ID && $user->ID != 1 ) {
                    $output_html .= ' <button class="pzl-button pzl-button-sm delete-user-btn" data-user-id="'. esc_attr($user->ID) .'" data-nonce="'. wp_create_nonce('puzzling_delete_user_' . $user->ID) .'" style="background-color: #dc3545 !important;">حذف</button>';
                }
                $output_html .= '</td>';
                $output_html .= '</tr>';
            }
        }

        wp_send_json_success(['html' => $output_html]);
    }
}