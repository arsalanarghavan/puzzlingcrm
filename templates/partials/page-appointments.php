<?php
/**
 * Appointments Management with Calendar View
 * @package PuzzlingCRM
 */
if (!defined('ABSPATH')) exit;
if (!current_user_can('manage_options') && !current_user_can('edit_posts')) return;

// Ensure default appointment statuses exist
if (!term_exists('pending', 'appointment_status')) {
    PuzzlingCRM_CPT_Manager::create_default_terms();
}

$action = isset($_GET['action']) ? sanitize_key($_GET['action']) : 'calendar';
$appt_id = isset($_GET['appt_id']) ? intval($_GET['appt_id']) : 0;
$item_to_edit = ($appt_id > 0) ? get_post($appt_id) : null;
?>

<!-- Tabs -->
<ul class="nav nav-tabs nav-tabs-header mb-4" role="tablist">
    <li class="nav-item">
        <a class="nav-link <?php echo $action === 'calendar' ? 'active' : ''; ?>" 
           href="?view=appointments&action=calendar">
            <i class="ri-calendar-line me-1"></i>نمای تقویم
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo $action === 'list' ? 'active' : ''; ?>" 
           href="?view=appointments&action=list">
            <i class="ri-list-check me-1"></i>نمای لیست
        </a>
    </li>
    <?php if ($action === 'edit' || $action === 'new'): ?>
    <li class="nav-item">
        <a class="nav-link active">
            <i class="ri-edit-line me-1"></i><?php echo $item_to_edit ? 'ویرایش' : 'ایجاد جدید'; ?>
        </a>
    </li>
    <?php endif; ?>
</ul>

<?php if ($action === 'edit' || $action === 'new'): 
    $customers = get_users(['role__in' => ['customer', 'subscriber', 'client'], 'orderby' => 'display_name']);
?>
    <!-- Edit/Create Form -->
    <div class="card custom-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div class="card-title">
                <i class="ri-calendar-line me-2"></i><?php echo $item_to_edit ? 'ویرایش قرار ملاقات' : 'ایجاد قرار ملاقات جدید'; ?>
            </div>
            <a href="?view=appointments&action=calendar" class="btn btn-secondary btn-sm">
                <i class="ri-arrow-right-line me-1"></i>بازگشت
            </a>
        </div>
        <div class="card-body">
            <form method="post" class="pzl-form pzl-ajax-form" data-action="puzzling_manage_appointment">
                <input type="hidden" name="appointment_id" value="<?php echo esc_attr($appt_id); ?>">
                <?php wp_nonce_field('puzzlingcrm-ajax-nonce', 'security'); ?>
                
                <?php
                $selected_customer = $item_to_edit ? $item_to_edit->post_author : '';
                $datetime_str = $item_to_edit ? get_post_meta($item_to_edit->ID, '_appointment_datetime', true) : '';
                $date_val = $datetime_str ? date('Y/m/d', strtotime($datetime_str)) : '';
                $time_val = $datetime_str ? date('H:i', strtotime($datetime_str)) : '';
                ?>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">مشتری *</label>
                        <select name="customer_id" class="form-select" required>
                            <option value="">-- انتخاب مشتری --</option>
                            <?php foreach($customers as $c): ?>
                            <option value="<?php echo $c->ID; ?>" <?php selected($selected_customer, $c->ID); ?>>
                                <?php echo esc_html($c->display_name); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">موضوع *</label>
                        <input type="text" name="title" class="form-control" 
                               value="<?php echo $item_to_edit ? esc_attr($item_to_edit->post_title) : ''; ?>" required>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">تاریخ *</label>
                        <input type="text" name="date" class="form-control pzl-jalali-date-picker" 
                               value="<?php echo esc_attr($date_val); ?>" required>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">ساعت *</label>
                        <input type="time" name="time" class="form-control" 
                               value="<?php echo esc_attr($time_val); ?>" required>
                    </div>
                    
                    <div class="col-md-12">
                        <label class="form-label fw-semibold">وضعیت *</label>
                        <select name="status" class="form-select" required>
                            <?php
                            $statuses = get_terms(['taxonomy' => 'appointment_status', 'hide_empty' => false]);
                            $current_status_terms = $item_to_edit ? wp_get_post_terms($appt_id, 'appointment_status') : [];
                            $current_status_slug = !empty($current_status_terms) ? $current_status_terms[0]->slug : 'pending';
                            foreach ($statuses as $status): ?>
                            <option value="<?php echo esc_attr($status->slug); ?>" <?php selected($current_status_slug, $status->slug); ?>>
                                <?php echo esc_html($status->name); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-12">
                        <label class="form-label fw-semibold">یادداشت‌ها</label>
                        <textarea name="notes" class="form-control" rows="4"><?php echo $item_to_edit ? esc_textarea($item_to_edit->post_content) : ''; ?></textarea>
                    </div>
                    
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary btn-wave">
                            <i class="ri-save-line me-1"></i><?php echo $item_to_edit ? 'ذخیره تغییرات' : 'ایجاد قرار ملاقات'; ?>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

<?php elseif ($action === 'calendar'): ?>
    <!-- Calendar View -->
    <div class="card custom-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div class="card-title">
                <i class="ri-calendar-line me-2 text-primary"></i>تقویم قرار ملاقات‌ها
            </div>
            <a href="?view=appointments&action=new" class="btn btn-primary btn-sm">
                <i class="ri-add-line me-1"></i>قرار ملاقات جدید
            </a>
        </div>
        <div class="card-body">
            <div id="appointments-calendar"></div>
        </div>
    </div>
    
    <script src="<?php echo PUZZLINGCRM_PLUGIN_URL; ?>assets/js/appointments-calendar.js?v=<?php echo PUZZLINGCRM_VERSION; ?>"></script>

<?php else: // List View ?>
    <!-- List View -->
    <div class="card custom-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div class="card-title">
                <i class="ri-list-check me-2 text-primary"></i>لیست قرار ملاقات‌ها
            </div>
            <div class="btn-group">
                <a href="?view=appointments&action=new" class="btn btn-primary btn-sm">
                    <i class="ri-add-line me-1"></i>قرار جدید
                </a>
                <a href="?view=appointments&action=calendar" class="btn btn-info btn-sm">
                    <i class="ri-calendar-line me-1"></i>نمای تقویم
                </a>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover text-nowrap">
                    <thead>
                        <tr>
                            <th>موضوع</th>
                            <th>مشتری</th>
                            <th>تاریخ و ساعت</th>
                            <th>وضعیت</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $appointments = get_posts([
                            'post_type' => 'appointment',
                            'posts_per_page' => 20,
                            'post_status' => 'publish',
                            'orderby' => 'meta_value',
                            'meta_key' => '_appointment_datetime',
                            'order' => 'ASC'
                        ]);
                        
                        if (!empty($appointments)):
                            foreach ($appointments as $appointment):
                                $customer = get_user_by('id', $appointment->post_author);
                                $datetime = get_post_meta($appointment->ID, '_appointment_datetime', true);
                                $status_terms = wp_get_post_terms($appointment->ID, 'appointment_status');
                                $status = !empty($status_terms) ? $status_terms[0]->name : 'نامشخص';
                                $status_slug = !empty($status_terms) ? $status_terms[0]->slug : 'pending';
                                
                                $status_colors = [
                                    'pending' => 'warning',
                                    'confirmed' => 'success',
                                    'cancelled' => 'danger',
                                    'completed' => 'info'
                                ];
                                $color = $status_colors[$status_slug] ?? 'secondary';
                        ?>
                        <tr>
                            <td>
                                <span class="fw-semibold"><?php echo esc_html($appointment->post_title); ?></span>
                            </td>
                            <td>
                                <?php if ($customer): ?>
                                    <div class="d-flex align-items-center">
                                        <?php echo get_avatar($customer->ID, 24, '', '', ['class' => 'rounded-circle me-1']); ?>
                                        <?php echo esc_html($customer->display_name); ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted">---</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($datetime): ?>
                                    <i class="ri-calendar-line text-primary me-1"></i>
                                    <?php echo date_i18n('Y/m/d H:i', strtotime($datetime)); ?>
                                <?php else: ?>
                                    <span class="text-muted">---</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $color; ?>-transparent">
                                    <?php echo esc_html($status); ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="?view=appointments&action=edit&appt_id=<?php echo $appointment->ID; ?>" 
                                       class="btn btn-primary">
                                        <i class="ri-edit-line"></i>
                                    </a>
                                    <button type="button" class="btn btn-danger delete-appointment" 
                                            data-appointment-id="<?php echo $appointment->ID; ?>">
                                        <i class="ri-delete-bin-line"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach;
                        else: ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                <i class="ri-calendar-line fs-40 d-block mb-2 opacity-3"></i>
                                هیچ قرار ملاقاتی ثبت نشده است
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>
