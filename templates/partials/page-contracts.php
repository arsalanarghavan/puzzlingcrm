<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! current_user_can('manage_options') ) return;

$action = isset($_GET['action']) ? sanitize_key($_GET['action']) : 'list';
$contract_id_to_edit = isset($_GET['contract_id']) ? intval($_GET['contract_id']) : 0;
?>
<div class="pzl-dashboard-section">
    <h3><i class="fas fa-file-signature"></i> مدیریت قراردادها</h3>
    
    <div class="pzl-dashboard-tabs">
        <a href="<?php echo remove_query_arg(['action', 'contract_id']); ?>" class="pzl-tab <?php echo $action === 'list' ? 'active' : ''; ?>"> <i class="fas fa-list-ul"></i> لیست قراردادها</a>
        <a href="<?php echo add_query_arg('action', 'new'); ?>" class="pzl-tab <?php echo $action === 'new' ? 'active' : ''; ?>"> <i class="fas fa-plus"></i> قرارداد جدید</a>
    </div>

    <div class="pzl-dashboard-tab-content">
    <?php if ($action === 'edit' && $contract_id_to_edit > 0): ?>
        <div class="pzl-card">
            <?php 
            global $puzzling_contract_id;
            $puzzling_contract_id = $contract_id_to_edit;
            include PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/form-edit-contract.php'; 
            ?>
        </div>
    <?php elseif ($action === 'new'): ?>
        <div class="pzl-card">
            <?php include PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/common/contract-form.php'; ?>
        </div>
    <?php else: // List View ?>
        <div class="pzl-card">
            <?php
            $contracts_query = new WP_Query([
                'post_type' => 'contract',
                'posts_per_page' => 20,
                'paged' => get_query_var('paged') ? get_query_var('paged') : 1,
            ]);
            ?>
            <h4><i class="fas fa-archive"></i> لیست قراردادهای ثبت شده</h4>
            <?php if ($contracts_query->have_posts()): ?>
                <table class="pzl-table">
                    <thead>
                        <tr>
                            <th>پروژه</th>
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
                            $project_id = get_post_meta($contract_id, '_project_id', true);
                            $customer = get_userdata(get_the_author_meta('ID'));
                            $installments = get_post_meta($contract_id, '_installments', true);
                            
                            $total_amount = 0;
                            $paid_amount = 0;
                            $has_pending = false;

                            if (is_array($installments)) {
                                foreach($installments as $inst) {
                                    $total_amount += (int)($inst['amount'] ?? 0);
                                    if (($inst['status'] ?? 'pending') === 'paid') {
                                        $paid_amount += (int)($inst['amount'] ?? 0);
                                    } else {
                                        $has_pending = true;
                                    }
                                }
                            }
                            $status_text = (!$has_pending && $total_amount > 0) ? 'تکمیل پرداخت' : 'در حال پرداخت';
                            $status_class = (!$has_pending && $total_amount > 0) ? 'status-paid' : 'status-pending';

                            $edit_url = add_query_arg(['view' => 'contracts', 'action' => 'edit', 'contract_id' => $contract_id]);
                        ?>
                        <tr>
                            <td><strong><?php echo esc_html(get_the_title($project_id)); ?></strong></td>
                            <td><?php echo esc_html($customer->display_name); ?></td>
                            <td><?php echo esc_html(number_format($total_amount)); ?> تومان</td>
                            <td><?php echo esc_html(number_format($paid_amount)); ?> تومان</td>
                            <td><span class="pzl-status <?php echo esc_attr($status_class); ?>"><?php echo esc_html($status_text); ?></span></td>
                            <td><a href="<?php echo esc_url($edit_url); ?>" class="pzl-button pzl-button-sm">ویرایش / مشاهده اقساط</a></td>
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