<?php
/**
 * Template for listing tickets for the current user (client or admin).
 * Now with tabbing for a cleaner interface.
 * @package PuzzlingCRM
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$current_user_id = get_current_user_id();
$is_manager = current_user_can('manage_options');
$base_url = remove_query_arg(['puzzling_notice', 'action', 'ticket_id']);
$active_tab = isset($_GET['action']) ? sanitize_key($_GET['action']) : 'list';


// Handle single ticket view if an ID is provided
$ticket_id_to_view = isset($_GET['ticket_id']) ? intval($_GET['ticket_id']) : 0;
if ($ticket_id_to_view > 0) {
    include PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/single-ticket.php';
    return; // Stop further execution to only show the single ticket view
}
?>

<div class="pzl-dashboard-section">
    <h3><i class="fas fa-life-ring"></i> پشتیبانی</h3>

    <div class="pzl-dashboard-tabs">
        <a href="<?php echo esc_url($base_url); ?>" class="pzl-tab <?php echo $active_tab === 'list' ? 'active' : ''; ?>"><i class="fas fa-list-ul"></i> لیست تیکت‌ها</a>
        <a href="<?php echo esc_url(add_query_arg('action', 'new', $base_url)); ?>" class="pzl-tab <?php echo $active_tab === 'new' ? 'active' : ''; ?>"><i class="fas fa-plus-circle"></i> ارسال تیکت جدید</a>
    </div>

    <div class="pzl-dashboard-tab-content">
    <?php if ($active_tab === 'new'): ?>
        <div class="pzl-card">
            <div class="pzl-card-header">
                <h3><i class="fas fa-plus-circle"></i> ارسال تیکت جدید</h3>
            </div>
            <form id="puzzling-new-ticket-form" method="post" class="pzl-form" action="<?php echo esc_url( remove_query_arg(['ticket_id', 'puzzling_notice']) ); ?>">
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
                <div class="form-submit">
                    <button type="submit" class="pzl-button">ارسال تیکت</button>
                </div>
            </form>
        </div>
    <?php else: // List view ?>
        <div class="pzl-card">
            <?php
            $paged = get_query_var('paged') ? get_query_var('paged') : 1;
            $args = [
                'post_type' => 'ticket', 'posts_per_page' => 10, 'post_status' => 'publish',
                'paged' => $paged, 'orderby' => 'modified', 'order' => 'DESC',
            ];
            if (!$is_manager) {
                $args['author'] = $current_user_id;
            }
            $tickets_query = new WP_Query($args);
            ?>
            <div class="pzl-card-header">
                 <h3><i class="fas fa-list-ul"></i> لیست تیکت‌ها</h3>
            </div>
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
                            $view_url = add_query_arg(['ticket_id' => $ticket_id], $base_url);
                        ?>
                            <tr>
                                <td><a href="<?php echo esc_url($view_url); ?>"><?php the_title(); ?></a></td>
                                <?php if ($is_manager): ?>
                                    <td><?php echo esc_html(get_the_author()); ?></td>
                                <?php endif; ?>
                                <td><?php echo esc_html(get_the_modified_date('Y/m/d H:i')); ?></td>
                                <td><span class="pzl-status-badge status-<?php echo $status_slug; ?>"><?php echo $status_name; ?></span></td>
                                <td><a href="<?php echo esc_url($view_url); ?>" class="pzl-button pzl-button-sm">مشاهده</a></td>
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
                        'format' => '?paged=%#%', 'prev_text' => '« قبلی', 'next_text' => 'بعدی »',
                    ]);
                    ?>
                </div>
                <?php wp_reset_postdata(); ?>
            <?php else : ?>
                <p>هیچ تیکتی یافت نشد.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    </div>
</div>

<style>
.pzl-status-badge { display: inline-block; padding: 4px 12px; border-radius: 15px; font-size: 12px; color: #fff; text-align: center; min-width: 90px;}
.status-open { background-color: #0073aa; }
.status-in-progress { background-color: var(--pzl-warning-color, #ffc107); color: #333; }
.status-answered { background-color: var(--pzl-success-color, #28a745); }
.status-closed { background-color: #777; }
</style>