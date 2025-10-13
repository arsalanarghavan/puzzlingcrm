<?php
/**
 * Shortcode for displaying and managing leads.
 * Only accessible to administrators and system managers.
 *
 * @package PuzzlingCRM
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Security Check
if ( ! ( current_user_can('manage_options') || current_user_can('system_manager') ) ) {
    echo '<p>' . __( 'شما دسترسی لازم برای مشاهده این بخش را ندارید.', 'puzzlingcrm' ) . '</p>';
    return;
}

$paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
$args = array(
    'post_type'      => 'pzl_lead',
    'posts_per_page' => 20,
    'paged'          => $paged,
    'post_status'    => 'publish',
    'orderby'        => 'date',
    'order'          => 'DESC',
);
$leads_query = new WP_Query($args);
?>
<div class="pzl-dashboard-section">
    <h3><i class="fas fa-users"></i> مدیریت سرنخ‌ها (لیدها)</h3>
    
    <div class="pzl-card">
        <table class="pzl-table">
            <thead>
                <tr>
                    <th>نام و نام خانوادگی</th>
                    <th>موبایل</th>
                    <th>کسب و کار</th>
                    <th>تاریخ ثبت</th>
                    <th>وضعیت</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($leads_query->have_posts()) : ?>
                    <?php while ($leads_query->have_posts()) : $leads_query->the_post(); 
                        $lead_id = get_the_ID();
                        $first_name = get_post_meta($lead_id, '_first_name', true);
                        $last_name = get_post_meta($lead_id, '_last_name', true);
                        $mobile = get_post_meta($lead_id, '_mobile', true);
                        $business_name = get_post_meta($lead_id, '_business_name', true);
                        $statuses = wp_get_post_terms($lead_id, 'lead_status');
                        $status_name = !empty($statuses) ? $statuses[0]->name : 'تعیین نشده';
                    ?>
                        <tr>
                            <td data-label="نام"><?php echo esc_html($first_name . ' ' . $last_name); ?></td>
                            <td data-label="موبایل" class="ltr-text"><?php echo esc_html($mobile); ?></td>
                            <td data-label="کسب و کار"><?php echo esc_html($business_name); ?></td>
                            <td data-label="تاریخ"><?php echo get_the_date('Y/m/d'); ?></td>
                            <td data-label="وضعیت"><?php echo esc_html($status_name); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="5">هیچ سرنخی یافت نشد.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php
        // Pagination
        $big = 999999999;
        echo paginate_links( array(
            'base'    => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
            'format'  => '?paged=%#%',
            'current' => max( 1, get_query_var('paged') ),
            'total'   => $leads_query->max_num_pages,
            'prev_text' => '« قبلی',
            'next_text' => 'بعدی »',
        ) );
        wp_reset_postdata();
    ?>
</div>