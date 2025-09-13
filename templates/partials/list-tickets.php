<?php
/**
 * Template for listing tickets for the current user (client or admin).
 * @package PuzzlingCRM
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$current_user_id = get_current_user_id();
$is_manager = current_user_can('manage_options');

// Handle single ticket view if an ID is provided
$ticket_id_to_view = isset($_GET['ticket_id']) ? intval($_GET['ticket_id']) : 0;
if ($ticket_id_to_view > 0) {
    include PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/single-ticket.php';
    return; // Stop further execution to only show the single ticket view
}

// WP_Query arguments to fetch tickets
$paged = get_query_var('paged') ? get_query_var('paged') : 1;
$args = [
    'post_type' => 'ticket',
    'posts_per_page' => 10,
    'post_status' => 'publish',
    'paged' => $paged,
    'orderby' => 'modified',
    'order' => 'DESC',
];
// If user is not a manager, only get their own tickets
if (!$is_manager) {
    $args['author'] = $current_user_id;
}
$tickets_query = new WP_Query($args);
?>

<div class="pzl-tickets-wrapper">
    
    <div class="pzl-new-ticket-form-container">
        <h4><span class="dashicons dashicons-plus-alt"></span> ارسال تیکت جدید</h4>
        <form id="puzzling-new-ticket-form" method="post" action="<?php echo esc_url( remove_query_arg(['ticket_id', 'puzzling_notice']) ); ?>">
            <?php wp_nonce_field('puzzling_new_ticket_nonce', '_wpnonce'); ?>
            <input type="hidden" name="puzzling_action" value="new_ticket">
            <div class="form-group">
                <label for="ticket_title">موضوع:</label>
                <input type="text" id="ticket_title" name="ticket_title" required>
            </div>
            <div class="form-group">
                <label for="ticket_content">پیام شما:</label>
                <textarea id="ticket_content" name="ticket_content" rows="6" required></textarea>
            </div>
            <button type="submit" class="pzl-button pzl-button-primary">ارسال تیکت</button>
        </form>
    </div>

    <h4 style="margin-top: 30px;"><span class="dashicons dashicons-list-view"></span> لیست تیکت‌ها</h4>
    <?php if ($tickets_query->have_posts()) : ?>
        <table class="pzl-table">
            <thead>
                <tr>
                    <th>موضوع</th>
                    <?php if ($is_manager) echo '<th>مشتری</th>'; ?>
                    <th>آخرین بروزرسانی</th>
                    <th>وضعیت</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php while ($tickets_query->have_posts()) : $tickets_query->the_post(); 
                    $ticket_id = get_the_ID();
                    $status_terms = get_the_terms($ticket_id, 'ticket_status');
                    $status_name = !empty($status_terms) ? esc_html($status_terms[0]->name) : 'نامشخص';
                    $status_slug = !empty($status_terms) ? esc_attr($status_terms[0]->slug) : 'default';
                    $view_url = add_query_arg(['view' => 'tickets', 'ticket_id' => $ticket_id], remove_query_arg('paged'));
                ?>
                    <tr>
                        <td>
                            <a href="<?php echo esc_url($view_url); ?>">
                                <?php the_title(); ?>
                            </a>
                        </td>
                        <?php if ($is_manager): ?>
                            <td><?php echo esc_html(get_the_author()); ?></td>
                        <?php endif; ?>
                        <td><?php echo esc_html(get_the_modified_date('Y/m/d H:i')); ?></td>
                        <td><span class="pzl-status-badge status-<?php echo $status_slug; ?>"><?php echo $status_name; ?></span></td>
                        <td><a href="<?php echo esc_url($view_url); ?>" class="pzl-button pzl-button-secondary">مشاهده</a></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <div class="pagination">
            <?php
            echo paginate_links([
                'base' => add_query_arg('paged', '%#%'),
                'total' => $tickets_query->max_num_pages,
                'current' => max( 1, $paged ),
                'format' => '?paged=%#%',
                'prev_text' => '« قبلی',
                'next_text' => 'بعدی »',
            ]);
            ?>
        </div>
        <?php wp_reset_postdata(); ?>
    <?php else : ?>
        <p>هیچ تیکتی یافت نشد.</p>
    <?php endif; ?>
</div>

<style>
.pzl-new-ticket-form-container { background: #f9f9f9; padding: 20px; border-radius: 8px; border: 1px solid #e0e0e0; }
.pzl-new-ticket-form-container .form-group { margin-bottom: 15px; }
.pzl-new-ticket-form-container label { display: block; margin-bottom: 5px; font-weight: bold; }
.pzl-new-ticket-form-container input[type="text"], .pzl-new-ticket-form-container textarea { width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ccc; }
.pzl-status-badge { display: inline-block; padding: 4px 12px; border-radius: 15px; font-size: 12px; color: #fff; text-align: center; min-width: 90px;}
.status-open { background-color: #0073aa; }
.status-in-progress { background-color: var(--warning-color, #ffc107); color: #333; }
.status-answered { background-color: var(--success-color, #28a745); }
.status-closed { background-color: #777; }
</style>