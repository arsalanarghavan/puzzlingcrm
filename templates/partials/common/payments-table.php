<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$is_manager_view = current_user_can('manage_options');
$query_args = [
    'post_type' => 'contract',
    'posts_per_page' => -1,
    'post_status' => 'publish',
];
if(!$is_manager_view){
    $query_args['author'] = get_current_user_id();
}
$contracts = get_posts($query_args);
?>
<?php if (empty($contracts)) : ?>
    <p><?php esc_html_e('No payment plans have been registered.', 'puzzlingcrm'); ?></p>
<?php else : ?>
<table class="pzl-table">
    <thead>
        <tr>
            <?php if($is_manager_view) echo '<th>' . esc_html__('Customer', 'puzzlingcrm') . '</th>'; ?>
            <th><?php esc_html_e('Project', 'puzzlingcrm'); ?></th>
            <th><?php esc_html_e('Installment Amount (Toman)', 'puzzlingcrm'); ?></th>
            <th><?php esc_html_e('Due Date', 'puzzlingcrm'); ?></th>
            <th><?php esc_html_e('Status', 'puzzlingcrm'); ?></th>
            <th><?php esc_html_e('Actions', 'puzzlingcrm'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php
        foreach ($contracts as $contract) {
            $project_id = get_post_meta($contract->ID, '_project_id', true);
            $project_title = get_the_title($project_id);
            $customer = get_userdata($contract->post_author);
            $installments = get_post_meta($contract->ID, '_installments', true);

            if (is_array($installments)) {
                foreach ($installments as $index => $installment) {
                    $status = $installment['status'] ?? 'pending';
                    $status_text = ($status === 'paid') ? __('Paid', 'puzzlingcrm') : __('Pending', 'puzzlingcrm');
                    $status_class = ($status === 'paid') ? 'status-paid' : 'status-pending';
                    
                    echo '<tr>';
                    if ($is_manager_view) {
                        echo '<td>' . esc_html($customer->display_name) . '</td>';
                    }
                    echo '<td>' . esc_html($project_title) . '</td>';
                    echo '<td>' . esc_html(number_format($installment['amount'])) . '</td>';
                    echo '<td>' . esc_html(date_i18n('Y/m/d', strtotime($installment['due_date']))) . '</td>';
                    echo '<td><span class="pzl-status ' . esc_attr($status_class) . '">' . esc_html($status_text) . '</span></td>';
                    echo '<td>';
                    if ($status === 'pending' && !$is_manager_view) {
                        $nonce = wp_create_nonce('pay_installment_' . $contract->ID . '_' . $index);
                        $payment_url = add_query_arg([
                            'puzzling_action' => 'pay_installment',
                            'contract_id' => $contract->ID,
                            'installment_index' => $index,
                            '_wpnonce' => $nonce,
                        ], puzzling_get_dashboard_url());

                        echo '<a href="' . esc_url($payment_url) . '" class="pzl-button pzl-button-primary">' . esc_html__('Online Payment', 'puzzlingcrm') . '</a>';
                    } else {
                        echo '<span>—</span>';
                    }
                    echo '</td>';
                    echo '</tr>';
                }
            }
        }
        ?>
    </tbody>
</table>
<?php endif; ?>