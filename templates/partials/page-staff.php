<?php
/**
 * Template for System Manager to Manage Staff - FINAL DESIGN & LAYOUT
 * @package PuzzlingCRM
 */
if (!defined('ABSPATH')) exit;
if (!current_user_can('manage_options')) {
    echo '<p>شما دسترسی لازم برای مشاهده این بخش را ندارید.</p>';
    return;
}

$action = isset($_GET['action']) ? sanitize_key($_GET['action']) : 'list';
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$user_to_edit = ($user_id > 0) ? get_user_by('ID', $user_id) : null;

// This defines the structure for our form
$profile_fields = [
    'identity_info' => [ 'title' => 'اطلاعات هویتی', 'fields' => [
        'father_name' => ['label' => 'نام پدر', 'type' => 'text'], 'birth_date' => ['label' => 'تاریخ تولد', 'type' => 'date'],
        'national_id' => ['label' => 'کد ملی', 'type' => 'text'], 'id_number' => ['label' => 'شماره شناسنامه', 'type' => 'text'],
        'id_issue_place' => ['label' => 'محل صدور', 'type' => 'text'],
        'marital_status' => ['label' => 'وضعیت تأهل', 'type' => 'select', 'options' => ['' => 'انتخاب کنید', 'single' => 'مجرد', 'married' => 'متاهل']],
        'children_count' => ['label' => 'تعداد فرزندان', 'type' => 'number'],
    ]],
    'contact_info' => [ 'title' => 'اطلاعات تماس و ارتباطی', 'fields' => [
        'mobile_phone' => ['label' => 'شماره موبایل', 'type' => 'tel'], 'landline_phone' => ['label' => 'تلفن ثابت', 'type' => 'tel'],
        'address' => ['label' => 'آدرس محل سکونت', 'type' => 'textarea', 'full_width' => true],
        'emergency_contact_1_name' => ['label' => 'نام مخاطب اضطراری ۱', 'type' => 'text', 'group' => 'emergency1'],
        'emergency_contact_1_phone' => ['label' => 'شماره مخاطب اضطراری ۱', 'type' => 'tel', 'group' => 'emergency1'],
        'emergency_contact_2_name' => ['label' => 'نام مخاطب اضطراری ۲', 'type' => 'text', 'group' => 'emergency2'],
        'emergency_contact_2_phone' => ['label' => 'شماره مخاطب اضطراری ۲', 'type' => 'tel', 'group' => 'emergency2'],
    ]],
    'job_info' => [ 'title' => 'اطلاعات شغلی / سازمانی', 'fields' => [
        'department' => ['label' => 'دپارتمان', 'type' => 'text'], 'personnel_code' => ['label' => 'کد پرسنلی', 'type' => 'text'],
        'direct_manager' => ['label' => 'مدیر مستقیم', 'type' => 'text'], 'hire_date' => ['label' => 'تاریخ استخدام', 'type' => 'date'],
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
?>
<div class="pzl-dashboard-section">
    <?php if ($action === 'logs'): ?>
        <?php 
        // Load the new template for displaying logs
        include PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/view-staff-logs.php'; 
        ?>
    <?php elseif ($action === 'edit' || $action === 'add'): ?>
        <div class="pzl-card-header">
             <h3><i class="fas fa-user-edit"></i> <?php echo $user_id > 0 ? 'ویرایش کارمند: ' . esc_html($user_to_edit->display_name) : 'افزودن کارمند جدید'; ?></h3>
             <a href="<?php echo remove_query_arg(['action', 'user_id']); ?>" class="pzl-button">&larr; بازگشت به لیست کارکنان</a>
        </div>

        <form method="post" class="pzl-form" id="pzl-staff-form" enctype="multipart/form-data">
            <input type="hidden" name="user_id" value="<?php echo esc_attr($user_id); ?>">
            <?php wp_nonce_field('puzzling_manage_user_nonce', 'security'); ?>
            
            <div class="pzl-card">
                <div class="pzl-profile-main-info">
                    <div class="pzl-profile-avatar-column">
                        <label>عکس پروفایل</label>
                        <div class="pzl-avatar-container">
                            <?php echo get_avatar($user_to_edit ? $user_to_edit->ID : 0, 200); ?>
                        </div>
                        <input type="file" name="pzl_profile_picture" id="pzl_profile_picture" accept="image/*">
                    </div>
                    <div class="pzl-profile-details-column">
                        <h4>اطلاعات اصلی و ورود</h4>
                        <div class="pzl-form-row">
                            <div class="form-group half-width">
                                <label for="first_name">نام (ضروری):</label>
                                <input type="text" id="first_name" name="first_name" value="<?php echo $user_to_edit ? esc_attr($user_to_edit->first_name) : ''; ?>" required>
                            </div>
                            <div class="form-group half-width">
                                <label for="last_name">نام خانوادگی (ضروری):</label>
                                <input type="text" id="last_name" name="last_name" value="<?php echo $user_to_edit ? esc_attr($user_to_edit->last_name) : ''; ?>" required>
                            </div>
                        </div>
                         <div class="pzl-form-row">
                            <div class="form-group half-width">
                                <label for="email">ایمیل (ضروری):</label>
                                <input type="email" id="email" name="email" value="<?php echo $user_to_edit ? esc_attr($user_to_edit->user_email) : ''; ?>" required>
                            </div>
                            <div class="form-group half-width">
                                <label for="password">رمز عبور:</label>
                                <input type="password" id="password" name="password" <?php echo $user_id === 0 ? 'required' : ''; ?>>
                                <?php if ($user_id > 0): ?><p class="description">برای عدم تغییر، خالی بگذارید.</p><?php endif; ?>
                            </div>
                        </div>
                        <div class="pzl-form-row">
                           <div class="form-group full-width">
                                <label for="role">نقش کاربری (سطح دسترسی):</label>
                                <select name="role" id="role" required>
                                    <?php 
                                    $staff_roles = ['system_manager' => 'مدیر سیستم', 'finance_manager' => 'مدیر مالی', 'team_member' => 'عضو تیم', 'customer' => 'مشتری'];
                                    $current_role = $user_to_edit && !empty($user_to_edit->roles) ? $user_to_edit->roles[0] : '';
                                    foreach ($staff_roles as $role_key => $role_name){
                                        echo '<option value="' . esc_attr($role_key) . '" ' . selected($current_role, $role_key, false) . '>' . esc_html($role_name) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php foreach ($profile_fields as $section_key => $section): ?>
                <div class="pzl-card">
                    <h4><?php echo esc_html($section['title']); ?></h4>
                    <div class="pzl-form-row">
                        <?php foreach ($section['fields'] as $field_key => $field): 
                            $meta_key = 'pzl_' . $field_key;
                            $value = $user_to_edit ? get_user_meta($user_to_edit->ID, $meta_key, true) : '';
                            $width_class = !empty($field['full_width']) ? 'full-width' : 'half-width';
                            $group_class = !empty($field['group']) ? ' form-group-grouped' : '';
                        ?>
                            <div class="form-group <?php echo $width_class . $group_class; ?>">
                                <label for="<?php echo esc_attr($meta_key); ?>"><?php echo esc_html($field['label']); ?></label>
                                <?php
                                switch ($field['type']) {
                                    case 'textarea':
                                        echo '<textarea name="' . esc_attr($meta_key) . '" id="' . esc_attr($meta_key) . '" rows="3">' . esc_textarea($value) . '</textarea>';
                                        break;
                                    case 'select':
                                        echo '<select name="' . esc_attr($meta_key) . '" id="' . esc_attr($meta_key) . '">';
                                        foreach ($field['options'] as $opt_val => $opt_label) {
                                            echo '<option value="' . esc_attr($opt_val) . '" ' . selected($value, $opt_val, false) . '>' . esc_html($opt_label) . '</option>';
                                        }
                                        echo '</select>';
                                        break;
                                    default:
                                        echo '<input type="' . esc_attr($field['type']) . '" name="' . esc_attr($meta_key) . '" id="' . esc_attr($meta_key) . '" value="' . esc_attr($value) . '" />';
                                }
                                ?>
                            </div>
                        <?php endforeach; ?>
                         <?php if($section_key === 'job_info'): // Special field for Organizational Position ?>
                            <div class="form-group half-width">
                                <label for="organizational_position">جایگاه سازمانی:</label>
                                <select name="organizational_position" id="organizational_position">
                                    <option value="">-- بدون جایگاه --</option>
                                    <?php
                                    $positions = get_terms(['taxonomy' => 'organizational_position', 'hide_empty' => false]);
                                    $current_pos_id = 0;
                                    if($user_to_edit){
                                        $current_pos = wp_get_object_terms($user_to_edit->ID, 'organizational_position', ['fields' => 'ids']);
                                        $current_pos_id = !empty($current_pos) ? $current_pos[0] : 0;
                                    }
                                    foreach($positions as $pos){
                                        echo '<option value="' . esc_attr($pos->term_id) . '" ' . selected($current_pos_id, $pos->term_id, false) . '>' . esc_html($pos->name) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <div class="form-submit">
                <button type="submit" class="pzl-button">ذخیره تغییرات</button>
            </div>
        </form>

    <?php else: // List View ?>
        <div class="pzl-card-header">
            <h3><i class="fas fa-users-cog"></i> مدیریت کارکنان</h3>
            <a href="<?php echo add_query_arg(['action' => 'add']); ?>" class="pzl-button">افزودن کارمند جدید</a>
        </div>
        <div class="pzl-card">
            <table class="pzl-table">
                <thead><tr><th>نام</th><th>ایمیل</th><th>نقش</th><th>جایگاه سازمانی</th><th>عملیات</th></tr></thead>
                <tbody>
                    <?php 
                    $staff_roles = ['system_manager', 'finance_manager', 'team_member'];
                    foreach(get_users(['role__in' => $staff_roles]) as $staff): 
                    ?>
                        <tr>
                            <td><?php echo get_avatar($staff->ID, 32); ?> <?php echo esc_html($staff->display_name); ?></td>
                            <td><?php echo esc_html($staff->user_email); ?></td>
                            <td><?php echo !empty($staff->roles) ? esc_html(wp_roles()->roles[$staff->roles[0]]['name']) : '---'; ?></td>
                            <td><?php $positions = wp_get_object_terms($staff->ID, 'organizational_position'); echo !is_wp_error($positions) && !empty($positions) ? esc_html($positions[0]->name) : '---'; ?></td>
                            <td>
                                <a href="<?php echo add_query_arg(['action' => 'edit', 'user_id' => $staff->ID]); ?>" class="pzl-button pzl-button-sm">ویرایش پروفایل</a>
                                <a href="<?php echo add_query_arg(['action' => 'logs', 'user_id' => $staff->ID]); ?>" class="pzl-button pzl-button-sm">تاریخچه عملیات</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<style>
/* --- Profile Form Enhancements --- */
.pzl-profile-main-info { display: flex; align-items: stretch; gap: 30px; }
.pzl-profile-avatar-column { flex: 0 0 200px; display: flex; flex-direction: column; }
.pzl-avatar-container { 
    width: 100%;
    background-color: #f0f0f0;
    border-radius: var(--pzl-border-radius);
    overflow: hidden;
    margin-bottom: 15px;
    border: 1px solid var(--pzl-border-color);
    flex-grow: 1; /* Makes it fill the available height */
}
.pzl-avatar-container img { width: 100%; height: 100%; object-fit: cover; }
.pzl-profile-details-column { flex: 1; }
.pzl-form-row { align-items: flex-start; } /* Align items to top */
.form-group.full-width { flex: 1 1 100% !important; }
.form-group-grouped { flex: 1 1 calc(50% - 10px) !important; }

/* --- Style Fix for All Input Types --- */
.pzl-form .form-group input[type="tel"],
.pzl-form .form-group input[type="email"],
.pzl-form .form-group input[type="date"],
.pzl-form .form-group input[type="number"],
.pzl-form .form-group input[type="password"],
.pzl-form .form-group input[type="text"] {
    width: 100%;
}
</style>