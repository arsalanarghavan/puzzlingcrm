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
        
        // Make sure the form can handle file uploads
        add_action( 'user_edit_form_tag', function(){ echo 'enctype="multipart/form-data"'; });
    }

    /**
     * Defines all the custom fields for the user profile.
     */
    private function define_profile_fields() {
        $this->profile_fields = [
            'identity_info' => [ 'title' => 'اطلاعات هویتی', 'fields' => [
                'father_name' => ['label' => 'نام پدر', 'type' => 'text'],
                'birth_date' => ['label' => 'تاریخ تولد', 'type' => 'date'],
                'national_id' => ['label' => 'کد ملی', 'type' => 'text'],
                'id_number' => ['label' => 'شماره شناسنامه', 'type' => 'text'],
                'id_issue_place' => ['label' => 'محل صدور', 'type' => 'text'],
                'marital_status' => ['label' => 'وضعیت تأهل', 'type' => 'select', 'options' => ['' => 'انتخاب کنید', 'single' => 'مجرد', 'married' => 'متاهل']],
                'children_count' => ['label' => 'تعداد فرزندان', 'type' => 'number'],
            ]],
            'contact_info' => [ 'title' => 'اطلاعات تماس و ارتباطی', 'fields' => [
                'mobile_phone' => ['label' => 'شماره موبایل', 'type' => 'tel'],
                'landline_phone' => ['label' => 'تلفن ثابت', 'type' => 'tel'],
                'address' => ['label' => 'آدرس محل سکونت', 'type' => 'textarea'],
                'emergency_contact_1_name' => ['label' => 'نام مخاطب اضطراری ۱', 'type' => 'text'],
                'emergency_contact_1_phone' => ['label' => 'شماره مخاطب اضطراری ۱', 'type' => 'tel'],
                'emergency_contact_2_name' => ['label' => 'نام مخاطب اضطراری ۲', 'type' => 'text'],
                'emergency_contact_2_phone' => ['label' => 'شماره مخاطب اضطراری ۲', 'type' => 'tel'],
            ]],
            'job_info' => [ 'title' => 'اطلاعات شغلی / سازمانی', 'fields' => [
                'personnel_code' => ['label' => 'کد پرسنلی', 'type' => 'text'],
                'hire_date' => ['label' => 'تاریخ استخدام', 'type' => 'date'],
                'contract_type' => ['label' => 'نوع قرارداد', 'type' => 'select', 'options' => ['' => 'انتخاب کنید', 'permanent' => 'رسمی', 'contractual' => 'پیمانی', 'project' => 'پروژه‌ای']],
                'job_status' => ['label' => 'وضعیت شغلی', 'type' => 'select', 'options' => ['' => 'انتخاب کنید', 'active' => 'فعال', 'on_leave' => 'مرخصی', 'mission' => 'ماموریت']],
            ]],
            'financial_info' => [ 'title' => 'اطلاعات مالی و حقوقی', 'fields' => [
                'bank_account_number' => ['label' => 'شماره حساب بانکی', 'type' => 'text'],
                'bank_name' => ['label' => 'نام بانک', 'type' => 'text'], 'iban' => ['label' => 'شماره شبا', 'type' => 'text'],
                'salary_details' => ['label' => 'حقوق و مزایا', 'type' => 'textarea'], 'deductions' => ['label' => 'کسورات', 'type' => 'textarea'],
            ]],
            'insurance_legal_info' => [ 'title' => 'اطلاعات بیمه و قانونی', 'fields' => [
                'insurance_number' => ['label' => 'شماره بیمه', 'type' => 'text'], 'tax_file_number' => ['label' => 'شماره پرونده مالیاتی', 'type' => 'text'],
                'insurance_history' => ['label' => 'سوابق بیمه‌ای', 'type' => 'textarea'],
            ]],
            'professional_history' => [ 'title' => 'سوابق حرفه‌ای و آموزشی', 'fields' => [
                'education' => ['label' => 'تحصیلات', 'type' => 'textarea'], 'training_courses' => ['label' => 'دوره‌های آموزشی', 'type' => 'textarea'],
                'skills_certificates' => ['label' => 'مهارت‌ها و گواهینامه‌ها', 'type' => 'textarea'], 'previous_jobs' => ['label' => 'سوابق کاری قبلی', 'type' => 'textarea'],
            ]],
            'admin_info' => [ 'title' => 'اطلاعات داخلی و اداری', 'fields' => [
                'personnel_card_id' => ['label' => 'شناسه ورود', 'type' => 'text'], 'system_access' => ['label' => 'دسترسی‌های سیستمی', 'type' => 'textarea'],
                'delivered_equipment' => ['label' => 'ابزارهای کاری تحویل‌شده', 'type' => 'textarea'],
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
                    <p class="description">یک تصویر مربعی آپلود کنید.</p>
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
                $meta_key = 'pzl_' . $field_key;
                if (isset($_POST[$meta_key])) {
                    update_user_meta($user_id, $meta_key, sanitize_text_field($_POST[$meta_key]));
                }
            }
        }
        
        // Handle profile picture upload
        if (!empty($_FILES['pzl_profile_picture']['name'])) {
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');

            $attachment_id = media_handle_upload('pzl_profile_picture', 0);
            if (is_wp_error($attachment_id)) {
                // Handle error
            } else {
                update_user_meta($user_id, 'pzl_profile_picture_id', $attachment_id);
            }
        }
    }
}