<?php
/**
 * Customers Management Page (Xintra Style)
 * @package PuzzlingCRM
 */

if (!defined('ABSPATH')) exit;
if (!current_user_can('manage_options')) return;

$action = isset($_GET['action']) ? sanitize_key($_GET['action']) : 'list';
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$user_to_edit = ($user_id > 0) ? get_user_by('ID', $user_id) : null;

// ========== EDIT/ADD FORM ==========
if ($action === 'edit' || $action === 'add'):
    $phone_number = $user_to_edit ? get_user_meta($user_to_edit->ID, 'pzl_mobile_phone', true) : '';
?>
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">
            <i class="ri-user-settings-line me-2"></i>
            <?php echo $user_id > 0 ? 'ویرایش اطلاعات کاربر' : 'افزودن کاربر جدید'; ?>
        </h4>
        <a href="<?php echo esc_url(remove_query_arg(['action', 'user_id'])); ?>" class="btn btn-secondary btn-sm">
            <i class="ri-arrow-right-line me-1"></i>بازگشت
        </a>
    </div>

    <!-- Form Card -->
    <div class="card custom-card">
        <div class="card-body">
            <form method="post" class="pzl-ajax-form" id="pzl-customer-form" data-action="puzzling_manage_user">
                <input type="hidden" name="user_id" value="<?php echo esc_attr($user_id); ?>">
                <?php wp_nonce_field('puzzlingcrm-ajax-nonce', 'security'); ?>

                <!-- Row 1: نام و نام خانوادگی -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="first_name" class="form-label fw-semibold">
                            <i class="ri-user-line me-1 text-primary"></i>نام
                        </label>
                        <input type="text" id="first_name" name="first_name" class="form-control" 
                               value="<?php echo $user_to_edit ? esc_attr($user_to_edit->first_name) : ''; ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="last_name" class="form-label fw-semibold">
                            <i class="ri-user-line me-1 text-primary"></i>نام خانوادگی <span class="text-danger">*</span>
                        </label>
                        <input type="text" id="last_name" name="last_name" class="form-control" 
                               value="<?php echo $user_to_edit ? esc_attr($user_to_edit->last_name) : ''; ?>" required>
                    </div>
                </div>

                <!-- Row 2: ایمیل و موبایل -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="email" class="form-label fw-semibold">
                            <i class="ri-mail-line me-1 text-info"></i>ایمیل <span class="text-danger">*</span>
                        </label>
                        <input type="email" id="email" name="email" class="form-control ltr-input" 
                               value="<?php echo $user_to_edit ? esc_attr($user_to_edit->user_email) : ''; ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="pzl_mobile_phone" class="form-label fw-semibold">
                            <i class="ri-phone-line me-1 text-success"></i>شماره موبایل
                        </label>
                        <input type="text" id="pzl_mobile_phone" name="pzl_mobile_phone" class="form-control ltr-input" 
                               value="<?php echo esc_attr($phone_number); ?>">
                    </div>
                </div>

                <!-- Row 3: نقش و رمز عبور -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="role" class="form-label fw-semibold">
                            <i class="ri-shield-user-line me-1 text-warning"></i>نقش کاربری <span class="text-danger">*</span>
                        </label>
                        <select name="role" id="role" class="form-select" required>
                            <?php
                            $editable_roles = get_editable_roles();
                            $current_role = $user_to_edit && !empty($user_to_edit->roles) ? $user_to_edit->roles[0] : 'customer';
                            foreach ($editable_roles as $role_key => $role_details) {
                                echo '<option value="' . esc_attr($role_key) . '" ' . selected($current_role, $role_key, false) . '>' . esc_html($role_details['name']) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="password" class="form-label fw-semibold">
                            <i class="ri-lock-password-line me-1 text-secondary"></i>رمز عبور
                        </label>
                        <input type="password" id="password" name="password" class="form-control ltr-input" 
                               <?php echo $user_id === 0 ? 'required' : ''; ?>>
                        <?php if ($user_id > 0): ?>
                        <small class="form-text text-muted">برای عدم تغییر، خالی بگذارید.</small>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-primary btn-lg px-5">
                        <i class="ri-save-line me-2"></i>
                        <?php echo $user_id > 0 ? 'ذخیره تغییرات' : 'ایجاد کاربر'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

<?php else: 
    // ========== LIST VIEW ==========
    
    // Calculate Stats
    $user_counts = count_users();
    $total_users = $user_counts['total_users'];
    $customer_count = $user_counts['avail_roles']['customer'] ?? 0;
    $staff_roles = ['system_manager', 'finance_manager', 'team_member', 'administrator'];
    $staff_count = 0;
    foreach($staff_roles as $role) {
        $staff_count += $user_counts['avail_roles'][$role] ?? 0;
    }
?>
    
    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-xxl-4 col-xl-6 col-lg-6 col-md-6">
            <div class="card custom-card overflow-hidden">
                <div class="card-body">
                    <div class="d-flex align-items-top justify-content-between">
                        <div>
                            <span class="avatar avatar-md avatar-rounded bg-primary">
                                <i class="ri-group-line fs-18"></i>
                            </span>
                        </div>
                        <div class="flex-fill ms-3">
                            <p class="text-muted mb-0">کل کاربران</p>
                            <h4 class="fw-semibold mt-1"><?php echo esc_html($total_users); ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-4 col-xl-6 col-lg-6 col-md-6">
            <div class="card custom-card overflow-hidden">
                <div class="card-body">
                    <div class="d-flex align-items-top justify-content-between">
                        <div>
                            <span class="avatar avatar-md avatar-rounded bg-success">
                                <i class="ri-user-smile-line fs-18"></i>
                            </span>
                        </div>
                        <div class="flex-fill ms-3">
                            <p class="text-muted mb-0">مشتریان</p>
                            <h4 class="fw-semibold mt-1"><?php echo esc_html($customer_count); ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-4 col-xl-6 col-lg-6 col-md-6">
            <div class="card custom-card overflow-hidden">
                <div class="card-body">
                    <div class="d-flex align-items-top justify-content-between">
                        <div>
                            <span class="avatar avatar-md avatar-rounded bg-warning">
                                <i class="ri-user-star-line fs-18"></i>
                            </span>
                        </div>
                        <div class="flex-fill ms-3">
                            <p class="text-muted mb-0">کارمندان</p>
                            <h4 class="fw-semibold mt-1"><?php echo esc_html($staff_count); ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Header with Search and Button -->
    <div class="card custom-card mb-3">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-8">
                    <label class="form-label fw-semibold">جستجوی سریع</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="ri-search-line"></i></span>
                        <input type="search" id="user-live-search-input" class="form-control" 
                               placeholder="جستجو بر اساس نام، ایمیل، موبایل...">
                    </div>
                </div>
                <div class="col-md-4">
                    <a href="<?php echo esc_url(add_query_arg(['action' => 'add'])); ?>" class="btn btn-primary w-100">
                        <i class="ri-user-add-line me-1"></i>افزودن کاربر جدید
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Users Table -->
    <div class="card custom-card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table text-nowrap">
                    <thead>
                        <tr>
                            <th>کاربر</th>
                            <th>ایمیل</th>
                            <th>موبایل</th>
                            <th>نقش</th>
                            <th>تاریخ عضویت</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody id="pzl-users-table-body">
                        <?php
                        $all_users = get_users(['orderby' => 'display_name', 'order' => 'ASC']);
                        if (empty($all_users)):
                        ?>
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <i class="ri-user-3-line fs-3 mb-3 d-block opacity-3"></i>
                                <p class="text-muted">هیچ کاربری یافت نشد</p>
                            </td>
                        </tr>
                        <?php else: 
                            foreach($all_users as $user): 
                                $phone = get_user_meta($user->ID, 'pzl_mobile_phone', true);
                                $role_name = !empty($user->roles) ? wp_roles()->roles[$user->roles[0]]['name'] : '---';
                        ?>
                        <tr data-user-row-id="<?php echo esc_attr($user->ID); ?>">
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <?php echo get_avatar($user->ID, 40, '', '', ['class' => 'rounded-circle']); ?>
                                    <div>
                                        <div class="fw-semibold"><?php echo esc_html($user->display_name); ?></div>
                                        <div class="fs-12 text-muted">ID: <?php echo $user->ID; ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="ltr-input"><?php echo esc_html($user->user_email); ?></td>
                            <td class="ltr-input"><?php echo $phone ? esc_html($phone) : '<span class="text-muted">-</span>'; ?></td>
                            <td><span class="badge bg-primary-transparent"><?php echo esc_html($role_name); ?></span></td>
                            <td><?php echo jdate('Y/m/d', strtotime($user->user_registered)); ?></td>
                            <td>
                                <div class="btn-group">
                                    <a href="<?php echo esc_url(add_query_arg(['action' => 'edit', 'user_id' => $user->ID])); ?>" 
                                       class="btn btn-sm btn-icon btn-primary-light" title="ویرایش">
                                        <i class="ri-edit-line"></i>
                                    </a>
                                    <button class="btn btn-sm btn-icon btn-info-light send-sms-btn" 
                                            data-user-id="<?php echo esc_attr($user->ID); ?>" 
                                            data-user-name="<?php echo esc_attr($user->display_name); ?>" 
                                            title="ارسال پیامک">
                                        <i class="ri-message-3-line"></i>
                                    </button>
                                    <?php if (get_current_user_id() != $user->ID && $user->ID != 1): ?>
                                    <button class="btn btn-sm btn-icon btn-danger-light delete-user-btn" 
                                            data-user-id="<?php echo esc_attr($user->ID); ?>" 
                                            data-nonce="<?php echo wp_create_nonce('puzzling_delete_user_' . $user->ID); ?>" 
                                            title="حذف">
                                        <i class="ri-delete-bin-line"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php 
                            endforeach;
                        endif; 
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php endif; ?>
