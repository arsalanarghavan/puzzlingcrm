<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! current_user_can('manage_options') ) return;

$action = isset($_GET['action']) ? sanitize_key($_GET['action']) : 'list';
$contract_id_to_edit = isset($_GET['contract_id']) ? intval($_GET['contract_id']) : 0;
?>
<div class="pzl-dashboard-section">
    <h3><i class="ri-file-text-line"></i> مدیریت قراردادها</h3>
    
    <div class="pzl-dashboard-tabs">
        <a href="<?php echo remove_query_arg(['action', 'contract_id']); ?>" class="pzl-tab <?php echo $action === 'list' ? 'active' : ''; ?>"> <i class="ri-list-check"></i> لیست قراردادها</a>
        <a href="<?php echo add_query_arg('action', 'new'); ?>" class="pzl-tab <?php echo $action === 'new' ? 'active' : ''; ?>"> <i class="ri-add-line"></i> قرارداد جدید</a>
    </div>

    <div class="pzl-dashboard-tab-content">
    <?php if (($action === 'edit' && $contract_id_to_edit > 0) || $action === 'new'): ?>
        <div class="pzl-card">
            <?php 
            if ($contract_id_to_edit > 0) {
                global $puzzling_contract;
                $puzzling_contract = get_post($contract_id_to_edit);
            }
            include PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/common/contract-form.php'; 
            ?>
        </div>
        
        <?php if ($action === 'edit' && $contract_id_to_edit > 0): 
            $related_projects = get_posts([
                'post_type' => 'project',
                'posts_per_page' => -1,
                'meta_key' => '_contract_id',
                'meta_value' => $contract_id_to_edit
            ]);
        ?>
        <div class="pzl-card">
            <h4><i class="ri-folder-2-line"></i> پروژه‌های مرتبط با این قرارداد</h4>
            <?php if (!empty($related_projects)): ?>
                <ul class="pzl-activity-list">
                    <?php foreach($related_projects as $project): ?>
                        <li>
                            <a href="<?php echo esc_url(add_query_arg(['view' => 'projects', 'action' => 'edit', 'project_id' => $project->ID], get_permalink())); ?>">
                                <?php echo esc_html($project->post_title); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>هیچ پروژه‌ای به این قرارداد متصل نیست.</p>
            <?php endif; ?>
            <hr>
            <h5><i class="ri-add-circle-line"></i> افزودن پروژه جدید به این قرارداد</h5>
            <form class="pzl-form pzl-ajax-form" data-action="puzzling_add_project_to_contract">
                <?php wp_nonce_field('puzzlingcrm-ajax-nonce', 'security'); ?>
                <input type="hidden" name="contract_id" value="<?php echo esc_attr($contract_id_to_edit); ?>">
                <div class="pzl-form-row" style="align-items: flex-end;">
                    <div class="form-group" style="flex: 2;">
                        <label for="new_project_title">عنوان پروژه جدید:</label>
                        <input type="text" name="project_title" id="new_project_title" required>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="pzl-button">افزودن پروژه</button>
                    </div>
                </div>
            </form>
        </div>
        <?php endif; ?>

    <?php else: // List View ?>
        <!-- Search & Filter -->
        <div class="card custom-card mb-3">
            <div class="card-body">
                <form method="get" class="row g-3">
                    <input type="hidden" name="view" value="contracts">
                    <div class="col-md-3">
                        <label class="form-label">جستجو</label>
                        <input type="text" name="s" class="form-control" placeholder="شماره یا عنوان قرارداد..." 
                               value="<?php echo isset($_GET['s']) ? esc_attr($_GET['s']) : ''; ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">مشتری</label>
                        <select name="customer_filter" class="form-select">
                            <option value="">همه مشتریان</option>
                            <?php
                            $all_customers = get_users(['role__in' => ['customer', 'subscriber', 'client']]);
                            $current_customer = isset($_GET['customer_filter']) ? intval($_GET['customer_filter']) : 0;
                            foreach ($all_customers as $customer):
                            ?>
                            <option value="<?php echo $customer->ID; ?>" <?php selected($current_customer, $customer->ID); ?>>
                                <?php echo esc_html($customer->display_name); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">وضعیت پرداخت</label>
                        <select name="payment_status" class="form-select">
                            <option value="">همه</option>
                            <option value="paid" <?php selected($_GET['payment_status'] ?? '', 'paid'); ?>>پرداخت شده</option>
                            <option value="pending" <?php selected($_GET['payment_status'] ?? '', 'pending'); ?>>در انتظار</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="ri-search-line me-1"></i>فیلتر
                        </button>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <a href="?view=contracts" class="btn btn-secondary w-100">
                            <i class="ri-refresh-line me-1"></i>پاک کردن
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="pzl-card">
            <?php
            $args = [
                'post_type' => 'contract',
                'posts_per_page' => 20,
                'paged' => get_query_var('paged') ? get_query_var('paged') : 1,
            ];
            
            // Apply filters
            if (!empty($_GET['s'])) {
                $args['s'] = sanitize_text_field($_GET['s']);
            }
            if (!empty($_GET['customer_filter'])) {
                $args['author'] = intval($_GET['customer_filter']);
            }
            
            $contracts_query = new WP_Query($args);
            ?>
            <h4><i class="ri-archive-line"></i> لیست قراردادهای ثبت شده</h4>
            <?php if ($contracts_query->have_posts()): ?>
                <table class="pzl-table">
                    <thead>
                        <tr>
                            <th><i class="ri-hashtag me-1"></i>شماره قرارداد</th>
                            <th><i class="ri-user-line me-1"></i>مشتری</th>
                            <th><i class="ri-money-dollar-circle-line me-1"></i>مبلغ کل</th>
                            <th><i class="ri-checkbox-circle-line me-1"></i>مبلغ پرداخت شده</th>
                            <th><i class="ri-information-line me-1"></i>وضعیت</th>
                            <th><i class="ri-settings-3-line me-1"></i>عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($contracts_query->have_posts()): $contracts_query->the_post(); 
                            $contract_id = get_the_ID();
                            $author_id = get_the_author_meta('ID');
                            $customer = $author_id ? get_userdata($author_id) : null;
                            $customer_name = ($customer && $customer->exists()) ? $customer->display_name : 'مشتری حذف شده';

                            $installments = get_post_meta($contract_id, '_installments', true);
                            if (!is_array($installments)) {
                                $installments = [];
                            }
                            
                            $total_amount = get_post_meta($contract_id, '_total_amount', true);
                            $total_amount_int = (int) preg_replace('/[^\d]/', '', $total_amount);
                            $paid_amount = 0;
                            $pending_amount = 0;
                            $cancelled_amount = 0;
                            $has_pending = false;
                            $installment_count = count($installments);
                            $paid_count = 0;
                            $pending_count = 0;

                            // Calculate amounts and status
                            foreach($installments as $inst) {
                                $inst_amount = (int) preg_replace('/[^\d]/', '', ($inst['amount'] ?? 0));
                                $inst_status = $inst['status'] ?? 'pending';
                                
                                if ($inst_status === 'paid') {
                                    $paid_amount += $inst_amount;
                                    $paid_count++;
                                } elseif ($inst_status === 'cancelled') {
                                    $cancelled_amount += $inst_amount;
                                } else {
                                    $pending_amount += $inst_amount;
                                    $has_pending = true;
                                    $pending_count++;
                                }
                            }

                            // If total amount is empty, calculate from installments
                            if (empty($total_amount_int) && $installment_count > 0) {
                                $total_amount_int = $paid_amount + $pending_amount + $cancelled_amount;
                            }

                            // Determine status
                            $contract_status = get_post_meta($contract_id, '_contract_status', true);
                            if ($contract_status === 'cancelled') {
                                $status_text = 'لغو شده';
                                $status_class = 'status-cancelled';
                            } elseif ($total_amount_int > 0 && $paid_amount >= $total_amount_int) {
                                $status_text = 'تکمیل پرداخت';
                                $status_class = 'status-paid';
                            } elseif ($paid_amount > 0) {
                                $status_text = 'در حال پرداخت';
                                $status_class = 'status-pending';
                            } else {
                                $status_text = 'در انتظار پرداخت';
                                $status_class = 'status-pending';
                            }

                            // Get dates
                            $start_date = get_post_meta($contract_id, '_project_start_date', true);
                            $start_date_display = $start_date ? puzzling_gregorian_to_jalali($start_date) : '-';
                            
                            // Apply payment status filter
                            $payment_status_filter = isset($_GET['payment_status']) ? sanitize_key($_GET['payment_status']) : '';
                            if ($payment_status_filter === 'paid' && $status_class !== 'status-paid') {
                                continue; // Skip if filter is "paid" but contract is not paid
                            }
                            if ($payment_status_filter === 'pending' && $status_class === 'status-paid') {
                                continue; // Skip if filter is "pending" but contract is paid
                            }

                            $edit_url = add_query_arg(['view' => 'contracts', 'action' => 'edit', 'contract_id' => $contract_id]);
                            
                            // Calculate percentage
                            $payment_percentage = $total_amount_int > 0 ? round(($paid_amount / $total_amount_int) * 100) : 0;
                        ?>
                        <tr>
                            <td>
                                <strong>#<?php echo esc_html(get_post_meta($contract_id, '_contract_number', true) ?: $contract_id); ?></strong>
                                <?php if ($contract_status === 'cancelled'): ?>
                                    <span class="badge bg-danger ms-2">لغو شده</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div><?php echo esc_html($customer_name); ?></div>
                                <?php if ($customer && $customer->exists()): ?>
                                    <small class="text-muted"><?php echo esc_html($customer->user_email); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="fw-semibold"><?php echo esc_html(number_format($total_amount_int)); ?> تومان</div>
                                <?php if ($installment_count > 0): ?>
                                    <small class="text-muted"><?php echo $installment_count; ?> قسط</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="fw-semibold text-success"><?php echo esc_html(number_format($paid_amount)); ?> تومان</div>
                                <small class="text-muted"><?php echo $payment_percentage; ?>%</small>
                                <?php if ($pending_amount > 0): ?>
                                    <div class="text-warning small"><?php echo esc_html(number_format($pending_amount)); ?> تومان باقیمانده</div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="pzl-status <?php echo esc_attr($status_class); ?>"><?php echo esc_html($status_text); ?></span>
                                <?php if ($installment_count > 0): ?>
                                    <div class="mt-1">
                                        <small class="text-muted">
                                            <?php echo $paid_count; ?> پرداخت شده / <?php echo $pending_count; ?> در انتظار
                                        </small>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    <a href="<?php echo esc_url($edit_url); ?>" class="btn btn-primary btn-sm">
                                        <i class="ri-edit-line me-1"></i>ویرایش
                                    </a>
                                </div>
                                <?php if ($start_date_display !== '-'): ?>
                                    <div class="mt-1">
                                        <small class="text-muted">
                                            <i class="ri-calendar-line"></i> <?php echo esc_html($start_date_display); ?>
                                        </small>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <div class="pagination">
                    <?php
                    echo paginate_links([
                        'base' => add_query_arg('paged', '%#%'),
                        'total' => $contracts_query->max_num_pages,
                        'current' => max( 1, get_query_var('paged') ? get_query_var('paged') : 1 ),
                        'format' => '?paged=%#%',
                    ]);
                    ?>
                </div>
                <?php wp_reset_postdata(); ?>
            <?php else: ?>
                <div class="alert alert-info text-center py-5">
                    <i class="ri-inbox-line fs-1 d-block mb-3"></i>
                    <h5>هیچ قراردادی یافت نشد</h5>
                    <p class="text-muted mb-0">
                        <?php if (!empty($_GET['s']) || !empty($_GET['customer_filter']) || !empty($_GET['payment_status'])): ?>
                            با فیلترهای فعلی هیچ قراردادی یافت نشد. لطفاً فیلترها را تغییر دهید.
                        <?php else: ?>
                            هنوز هیچ قراردادی ثبت نشده است. برای ایجاد قرارداد جدید روی تب "قرارداد جدید" کلیک کنید.
                        <?php endif; ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    </div>
</div>