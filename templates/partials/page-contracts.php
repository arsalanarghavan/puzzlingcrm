<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! current_user_can('manage_options') ) return;

$contracts_query = new WP_Query([
    'post_type' => 'contract',
    'posts_per_page' => 20,
    'paged' => get_query_var('paged') ? get_query_var('paged') : 1,
]);
?>
<div class="pzl-dashboard-section">
    <h3><span class="dashicons dashicons-media-text"></span> <?php esc_html_e('Manage Contracts', 'puzzlingcrm'); ?></h3>
    
    <div class="pzl-form-container" style="margin-bottom: 30px;">
        <?php include PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/common/contract-form.php'; ?>
    </div>

    <h4><span class="dashicons dashicons-list-view"></span> <?php esc_html_e('Existing Contracts', 'puzzlingcrm'); ?></h4>
    <?php if ($contracts_query->have_posts()): ?>
        <table class="pzl-table">
            <thead>
                <tr>
                    <th><?php esc_html_e('Project', 'puzzlingcrm'); ?></th>
                    <th><?php esc_html_e('Customer', 'puzzlingcrm'); ?></th>
                    <th><?php esc_html_e('Total Amount', 'puzzlingcrm'); ?></th>
                    <th><?php esc_html_e('Paid Amount', 'puzzlingcrm'); ?></th>
                    <th><?php esc_html_e('Status', 'puzzlingcrm'); ?></th>
                    <th><?php esc_html_e('Actions', 'puzzlingcrm'); ?></th>
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
                            $total_amount += (int)$inst['amount'];
                            if ($inst['status'] === 'paid') {
                                $paid_amount += (int)$inst['amount'];
                            } else {
                                $has_pending = true;
                            }
                        }
                    }
                    $status_text = ($paid_amount >= $total_amount && !$has_pending) ? __('Paid', 'puzzlingcrm') : __('In Progress', 'puzzlingcrm');
                    $status_class = ($paid_amount >= $total_amount && !$has_pending) ? 'status-paid' : 'status-pending';

                    $edit_url = add_query_arg(['view' => 'contracts', 'action' => 'edit', 'contract_id' => $contract_id], puzzling_get_dashboard_url());
                ?>
                <tr>
                    <td><strong><?php echo esc_html(get_the_title($project_id)); ?></strong></td>
                    <td><?php echo esc_html($customer->display_name); ?></td>
                    <td><?php echo esc_html(number_format($total_amount)); ?></td>
                    <td><?php echo esc_html(number_format($paid_amount)); ?></td>
                    <td><span class="pzl-status <?php echo esc_attr($status_class); ?>"><?php echo esc_html($status_text); ?></span></td>
                    <td><a href="<?php echo esc_url($edit_url); ?>" class="pzl-button pzl-button-secondary"><?php esc_html_e('Edit', 'puzzlingcrm'); ?></a></td>
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
        <p><?php esc_html_e('No contracts found.', 'puzzlingcrm'); ?></p>
    <?php endif; ?>
</div>