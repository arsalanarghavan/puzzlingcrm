<?php
/**
 * PuzzlingCRM User Profile Handler
 *
 * This class adds custom fields to user profiles, including a profile picture.
 *
 * @package PuzzlingCRM
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class PuzzlingCRM_User_Profile {

    /**
     * Holds the definitions for all custom profile fields.
     * @var array
     */
    private $profile_fields = [];

    /**
     * Constructor. Hooks into WordPress actions.
     */
    public function __construct() {
        $this->define_profile_fields();
        add_action( 'show_user_profile', [ $this, 'render_custom_profile_fields' ] );
        add_action( 'edit_user_profile', [ $this, 'render_custom_profile_fields' ] );
        add_action( 'personal_options_update', [ $this, 'save_custom_profile_fields' ] );
        add_action( 'edit_user_profile_update', [ $this, 'save_custom_profile_fields' ] );
        
        // Make the form can handle file uploads
        add_action( 'user_edit_form_tag', function(){ echo 'enctype="multipart/form-data"'; });
        
        // **NEW**: Enqueue scripts for the admin profile page
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
    }

    /**
     * **NEW**: Enqueues scripts and styles for the admin user profile pages.
     */
    public function enqueue_admin_scripts($hook) {
        // Only load on user profile pages
        if ($hook !== 'profile.php' && $hook !== 'user-edit.php') {
            return;
        }

        // Enqueue kamadatepicker assets
        wp_enqueue_script('kamadatepicker-js', PUZZLINGCRM_PLUGIN_URL . 'assets/js/kamadatepicker.min.js', ['jquery'], '1.5.3', true);
        wp_enqueue_style('kamadatepicker-css', PUZZLINGCRM_PLUGIN_URL . 'assets/css/kamadatepicker.min.css', [], '1.5.3');

        // Add inline script to initialize the datepicker on the correct fields
        $script = "
            jQuery(document).ready(function($) {
                kamadatepicker('#pzl_birth_date, #pzl_hire_date', {
                    buttonsColor: 'red',
                    forceFarsiDigits: true,
                    gotoToday: true,
                });
            });
        ";
        wp_add_inline_script('kamadatepicker-js', $script);
    }

    /**
     * Defines all the custom fields for the user profile.
     */
    private function define_profile_fields() {
        $this->profile_fields = [
            'identity_info' => [ 'title' => 'اطلاعات هویتی', 'fields' => [
                'father_name' => ['label' => 'نام پدر', 'type' => 'text'],
                'birth_date' => ['label' => 'تاریخ تولد', 'type' => 'text'],
                'national_id' => ['label' => 'کد ملی', 'type' => 'text'],
                'id_number' => ['label' => 'شماره شناسنامه', 'type' => 'text'],
                'id_issue_place' => ['label' => 'محل صدور', 'type' => 'text'],
                'marital_status' => ['label' => 'وضعیت تأهل', 'type' => 'select', 'options' => ['' => 'انتخاب کنید', 'single' => 'مجرد', 'married' => 'متاهل']],
                'children_count' => ['label' => 'تعداد فرزندان', 'type' => 'number'],
            ]],
            'contact_info' => [ 'title' => 'اطلاعات تماس و ارتباطی', 'fields' => [
                'mobile_phone' => ['label' => 'شماره موبایل', 'type' => 'tel'],
                'landline_phone' => ['label' => 'تلفن ثابت', 'type' => 'tel'],
                'address' => ['label' => 'آدرس محل سکونت', 'type' => 'textarea', 'full_width' => true],
                'emergency_contact_1_name' => ['label' => 'نام مخاطب اضطراری ۱', 'type' => 'text'],
                'emergency_contact_1_phone' => ['label' => 'شماره مخاطب اضطراری ۱', 'type' => 'tel'],
                'emergency_contact_2_name' => ['label' => 'نام مخاطب اضطراری ۲', 'type' => 'text'],
                'emergency_contact_2_phone' => ['label' => 'شماره مخاطب اضطراری ۲', 'type' => 'tel'],
            ]],
            'job_info' => [ 'title' => 'اطلاعات شغلی / سازمانی', 'fields' => [
                'organizational_position' => ['label' => 'جایگاه سازمانی (دپارتمان/عنوان)', 'type' => 'position_select'],
                'personnel_code' => ['label' => 'کد پرسنلی', 'type' => 'text'],
                'direct_manager' => ['label' => 'مدیر مستقیم', 'type' => 'text'],
                'hire_date' => ['label' => 'تاریخ استخدام', 'type' => 'text'],
                'contract_type' => ['label' => 'نوع قرارداد', 'type' => 'select', 'options' => ['' => 'انتخاب کنید', 'permanent' => 'رسمی', 'contractual' => 'پیمانی', 'project' => 'پروژه‌ای']],
                'job_status' => ['label' => 'وضعیت شغلی', 'type' => 'select', 'options' => ['' => 'انتخاب کنید', 'active' => 'فعال', 'on_leave' => 'مرخصی', 'mission' => 'ماموریت']],
            ]],
            'financial_info' => [ 'title' => 'اطلاعات مالی و حقوقی', 'fields' => [
                'bank_account_number' => ['label' => 'شماره حساب', 'type' => 'text'], 'bank_name' => ['label' => 'نام بانک', 'type' => 'text'],
                'iban' => ['label' => 'شماره شبا', 'type' => 'text'], 'salary_details' => ['label' => 'حقوق و مزایا', 'type' => 'textarea'],
                'deductions' => ['label' => 'کسورات', 'type' => 'textarea'],
            ]],
            'insurance_legal_info' => [ 'title' => 'اطلاعات بیمه و قانونی', 'fields' => [
                'insurance_number' => ['label' => 'شماره بیمه', 'type' => 'text'], 'tax_file_number' => ['label' => 'شماره پرونده مالیاتی', 'type' => 'text'],
                'insurance_history' => ['label' => 'سوابق بیمه‌ای', 'type' => 'textarea'],
            ]],
            'professional_history' => [ 'title' => 'سوابق حرفه‌ای و آموزشی', 'fields' => [
                'education' => ['label' => 'تحصیلات', 'type' => 'textarea'], 'training_courses' => ['label' => 'دوره‌ها', 'type' => 'textarea'],
                'skills_certificates' => ['label' => 'مهارت‌ها و گواهینامه‌ها', 'type' => 'textarea'], 'previous_jobs' => ['label' => 'سوابق کاری قبلی', 'type' => 'textarea'],
            ]],
        ];
    }

    public function render_custom_profile_fields($user) {
        if (!current_user_can('edit_user', $user->ID)) return;
        
        echo '<h2>اطلاعات تکمیلی PuzzlingCRM</h2>';
        ?>
        <table class="form-table">
            <tr>
                <th><label for="pzl_profile_picture">عکس پروفایل</label></th>
                <td>
                    <?php echo get_avatar($user->ID, 96); ?>
                    <input type="file" name="pzl_profile_picture" id="pzl_profile_picture" accept="image/*">
                    <p class="description">برای بهترین نمایش، از یک تصویر مربع استفاده کنید.</p>
                </td>
            </tr>
        </table>
        <?php

        foreach ($this->profile_fields as $section) {
            echo '<h3>' . esc_html($section['title']) . '</h3>';
            echo '<table class="form-table">';
            foreach ($section['fields'] as $field_key => $field) {
                $meta_key = 'pzl_' . $field_key;
                $value = get_user_meta($user->ID, $meta_key, true);
                ?>
                <tr>
                    <th><label for="<?php echo esc_attr($meta_key); ?>"><?php echo esc_html($field['label']); ?></label></th>
                    <td>
                        <?php
                        switch ($field['type']) {
                            case 'textarea':
                                echo '<textarea name="' . esc_attr($meta_key) . '" id="' . esc_attr($meta_key) . '" rows="5" cols="30" class="regular-text">' . esc_textarea($value) . '</textarea>';
                                break;
                            case 'select':
                                echo '<select name="' . esc_attr($meta_key) . '" id="' . esc_attr($meta_key) . '">';
                                foreach ($field['options'] as $opt_val => $opt_label) {
                                    echo '<option value="' . esc_attr($opt_val) . '" ' . selected($value, $opt_val, false) . '>' . esc_html($opt_label) . '</option>';
                                }
                                echo '</select>';
                                break;
                           case 'position_select':
                                $current_pos = wp_get_object_terms($user->ID, 'organizational_position', ['fields' => 'ids']);
                                $current_pos_id = !empty($current_pos) ? $current_pos[0] : 0;
                                wp_dropdown_categories([
                                    'taxonomy' => 'organizational_position',
                                    'name' => 'organizational_position',
                                    'selected' => $current_pos_id,
                                    'show_option_none' => '-- بدون جایگاه --',
                                    'hierarchical' => true,
                                    'show_count' => false,
                                    'hide_empty' => false,
                                ]);
                                break;
                            default:
                                echo '<input type="' . esc_attr($field['type']) . '" name="' . esc_attr($meta_key) . '" id="' . esc_attr($meta_key) . '" value="' . esc_attr($value) . '" class="regular-text" />';
                        }
                        ?>
                    </td>
                </tr>
                <?php
            }
            echo '</table>';
        }
    }
    
    public function save_custom_profile_fields($user_id) {
        if (!current_user_can('edit_user', $user_id)) return;

        foreach ($this->profile_fields as $section) {
            foreach ($section['fields'] as $field_key => $field) {
                if ($field['type'] === 'position_select') continue; // Handled separately
                $meta_key = 'pzl_' . $field_key;
                if (isset($_POST[$meta_key])) {
                    update_user_meta($user_id, $meta_key, sanitize_text_field($_POST[$meta_key]));
                }
            }
        }

        // Save organizational position
        if (isset($_POST['organizational_position'])) {
            wp_set_object_terms($user_id, intval($_POST['organizational_position']), 'organizational_position', false);
        }
        
        // Handle profile picture upload
        if (!empty($_FILES['pzl_profile_picture']['name'])) {
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');

            $attachment_id = media_handle_upload('pzl_profile_picture', 0);
            if (!is_wp_error($attachment_id)) {
                update_user_meta($user_id, 'pzl_profile_picture_id', $attachment_id);
            }
        }
    }
}

// Add a filter to use our custom profile picture as the avatar
add_filter('get_avatar_url', function($url, $id_or_email, $args) {
    $user_id = 0;
    if (is_numeric($id_or_email)) {
        $user_id = (int) $id_or_email;
    } elseif (is_object($id_or_email)) {
        if (!empty($id_or_email->user_id)) {
            $user_id = (int) $id_or_email->user_id;
        }
    } else {
        $user = get_user_by('email', $id_or_email);
        $user_id = $user ? $user->ID : 0;
    }

    if ($user_id === 0) return $url;

    $attachment_id = get_user_meta($user_id, 'pzl_profile_picture_id', true);
    if ($attachment_id) {
        $image_url = wp_get_attachment_image_url($attachment_id, 'thumbnail');
        if ($image_url) return $image_url;
    }

    return $url;
}, 10, 3);