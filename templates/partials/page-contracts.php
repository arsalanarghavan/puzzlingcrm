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
                            <th>شماره قرارداد</th>
                            <th>مشتری</th>
                            <th>مبلغ کل</th>
                            <th>مبلغ پرداخت شده</th>
                            <th>وضعیت</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($contracts_query->have_posts()): $contracts_query->the_post(); 
                            $contract_id = get_the_ID();
                            $customer = get_userdata(get_the_author_meta('ID'));
                            $customer_name = ($customer) ? $customer->display_name : 'مشتری حذف شده'; // FIX: Check if customer exists

                            $installments = get_post_meta($contract_id, '_installments', true);
                            
                            $total_amount = get_post_meta($contract_id, '_total_amount', true);
                            $paid_amount = 0;
                            $has_pending = false;

                            if (is_array($installments)) {
                                foreach($installments as $inst) {
                                    if (isset($inst['status']) && $inst['status'] === 'paid' && isset($inst['amount'])) {
                                        $paid_amount += (int) preg_replace('/[^\d]/', '', $inst['amount']);
                                    } else {
                                        $has_pending = true;
                                    }
                                }
                            }

                            if (empty($total_amount) && is_array($installments)) {
                                $calculated_total = 0;
                                foreach ($installments as $inst) {
                                    $calculated_total += (int)preg_replace('/[^\d]/', '', ($inst['amount'] ?? 0));
                                }
                                $total_amount = $calculated_total;
                            }

                            $status_text = (!$has_pending && (int)$total_amount > 0 && $paid_amount >= (int)$total_amount) ? 'تکمیل پرداخت' : 'در حال پرداخت';
                            $status_class = (!$has_pending && (int)$total_amount > 0 && $paid_amount >= (int)$total_amount) ? 'status-paid' : 'status-pending';

                            $edit_url = add_query_arg(['view' => 'contracts', 'action' => 'edit', 'contract_id' => $contract_id]);
                        ?>
                        <tr>
                            <td><strong>#<?php echo esc_html(get_post_meta($contract_id, '_contract_number', true) ?: $contract_id); ?></strong></td>
                            <td><?php echo esc_html($customer_name); ?></td>
                            <td><?php echo esc_html(number_format((int)$total_amount)); ?> تومان</td>
                            <td><?php echo esc_html(number_format((int)$paid_amount)); ?> تومان</td>
                            <td><span class="pzl-status <?php echo esc_attr($status_class); ?>"><?php echo esc_html($status_text); ?></span></td>
                            <td><a href="<?php echo esc_url($edit_url); ?>" class="pzl-button pzl-button-sm">ویرایش / مشاهده جزئیات</a></td>
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
                <p>هیچ قراردادی یافت نشد.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    </div>
</div>