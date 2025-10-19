<?php
/**
 * Template for System/Finance Manager to view all payment installments
 * @package PuzzlingCRM
 */
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! current_user_can('manage_options') ) return;

// --- Filtering ---
$paged = get_query_var('paged') ? get_query_var('paged') : 1;
$project_filter = isset($_GET['project_filter']) ? intval($_GET['project_filter']) : 0;
$customer_filter = isset($_GET['customer_filter']) ? intval($_GET['customer_filter']) : 0;
$status_filter = isset($_GET['status_filter']) ? sanitize_key($_GET['status_filter']) : '';

$all_customers = get_users(['role__in' => ['customer', 'subscriber'], 'orderby' => 'display_name']);
$all_projects = get_posts(['post_type' => 'project', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC']);

// --- Data Fetching ---
$contracts_query_args = [
    'post_type' => 'contract',
    'posts_per_page' => -1, // Get all contracts to process installments
];
if ($customer_filter > 0) {
    $contracts_query_args['author'] = $customer_filter;
}
if ($project_filter > 0) {
    $contracts_query_args['meta_query'] = [
        [
            'key' => '_project_id',
            'value' => $project_filter,
            'compare' => '=',
        ]
    ];
}

$all_contracts = get_posts($contracts_query_args);
$all_installments = [];

foreach ($all_contracts as $contract) {
    $installments_data = get_post_meta($contract->ID, '_installments', true);
    if (is_array($installments_data)) {
        $project_id = get_post_meta($contract->ID, '_project_id', true);
        $customer = get_userdata($contract->post_author);

        foreach ($installments_data as $index => $inst) {
            // Apply status filter
            if ($status_filter && ($inst['status'] ?? 'pending') !== $status_filter) {
                continue;
            }

            $all_installments[] = [
                'project_title' => get_the_title($project_id),
                'project_id' => $project_id,
                'customer_name' => $customer ? $customer->display_name : 'کاربر حذف شده',
                'customer_id' => $contract->post_author,
                'amount' => $inst['amount'],
                'due_date' => $inst['due_date'],
                'status' => $inst['status'] ?? 'pending',
                'contract_id' => $contract->ID,
                'installment_index' => $index,
            ];
        }
    }
}

// --- Pagination Logic ---
$total_items = count($all_installments);
$items_per_page = 20;
$total_pages = ceil($total_items / $items_per_page);
$offset = ($paged - 1) * $items_per_page;
$paginated_installments = array_slice($all_installments, $offset, $items_per_page);

?>
<div class="pzl-card">
    <div class="pzl-card-header">
        <h3><i class="ri-file-line-invoice-dollar"></i> لیست تمام اقساط و پرداخت‌ها</h3>
    </div>

    <form method="get" class="pzl-form">
        <input type="hidden" name="view" value="invoices">
        <div class="pzl-form-row" style="align-items: flex-end;">
            <div class="form-group">
                <label for="project_filter">پروژه</label>
                <select name="project_filter" id="project_filter">
                    <option value="">همه پروژه‌ها</option>
                    <?php foreach ($all_projects as $project) {
                        echo '<option value="' . esc_attr($project->ID) . '" ' . selected($project_filter, $project->ID, false) . '>' . esc_html($project->post_title) . '</option>';
                    } ?>
                </select>
            </div>
            <div class="form-group">
                <label for="customer_filter">مشتری</label>
                <select name="customer_filter" id="customer_filter">
                    <option value="">همه مشتریان</option>
                     <?php foreach ($all_customers as $customer) {
                        echo '<option value="' . esc_attr($customer->ID) . '" ' . selected($customer_filter, $customer->ID, false) . '>' . esc_html($customer->display_name) . '</option>';
                    } ?>
                </select>
            </div>
            <div class="form-group">
                <label for="status_filter">وضعیت</label>
                <select name="status_filter" id="status_filter">
                    <option value="">همه وضعیت‌ها</option>
                    <option value="paid" <?php selected($status_filter, 'paid'); ?>>پرداخت شده</option>
                    <option value="pending" <?php selected($status_filter, 'pending'); ?>>در انتظار پرداخت</option>
                </select>
            </div>
            <div class="form-group">
                <button type="submit" class="pzl-button">فیلتر</button>
            </div>
        </div>
    </form>

    <table class="pzl-table" style="margin-top: 20px;">
        <thead>
            <tr>
                <th>پروژه</th>
                <th>مشتری</th>
                <th>مبلغ قسط (تومان)</th>
                <th>تاریخ سررسید</th>
                <th>وضعیت</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($paginated_installments)): ?>
                <?php foreach ($paginated_installments as $installment):
                    $status_text = ($installment['status'] === 'paid') ? 'پرداخت شده' : 'در انتظار پرداخت';
                    $status_class = ($installment['status'] === 'paid') ? 'status-paid' : 'status-pending';
                ?>
                <tr>
                    <td><?php echo esc_html($installment['project_title']); ?></td>
                    <td><?php echo esc_html($installment['customer_name']); ?></td>
                    <td><?php echo esc_html(number_format($installment['amount'])); ?></td>
                    <td><?php echo esc_html(date_i18n('Y/m/d', strtotime($installment['due_date']))); ?></td>
                    <td><span class="pzl-status <?php echo esc_attr($status_class); ?>"><?php echo $status_text; ?></span></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5">هیچ قسطی با این مشخصات یافت نشد.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="pagination">
        <?php
        echo paginate_links([
            'base' => add_query_arg('paged', '%#%'),
            'total' => $total_pages,
            'current' => max(1, $paged),
            'format' => '?paged=%#%',
        ]);
        ?>
    </div>
</div>