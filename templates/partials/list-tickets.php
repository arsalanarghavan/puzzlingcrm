<?php
/**
 * Template for listing tickets for the current user (client or admin).
 * Now with tabbing for a cleaner interface and department selection.
 * @package PuzzlingCRM
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$current_user = wp_get_current_user();
$current_user_id = $current_user->ID;
$is_manager = current_user_can('manage_options');
$is_team_member = in_array('team_member', (array)$current_user->roles);

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
            <form id="puzzling-new-ticket-form" class="pzl-form pzl-ajax-form" data-action="puzzling_new_ticket" enctype="multipart/form-data">
                <?php wp_nonce_field('puzzlingcrm-ajax-nonce', 'security'); ?>
                <div class="form-group">
                    <label for="ticket_title">موضوع:</label>
                    <input type="text" id="ticket_title" name="ticket_title" required>
                </div>
                 <div class="form-group">
                    <label for="department">دپارتمان:</label>
                    <?php
                    // **MODIFIED**: Shows only parent-level organizational positions as departments
                    wp_dropdown_categories([
                        'taxonomy'         => 'organizational_position',
                        'name'             => 'department',
                        'id'               => 'department',
                        'show_option_none' => __('انتخاب دپارتمان', 'puzzlingcrm'),
                        'hierarchical'     => true,
                        'hide_empty'       => false,
                        'parent'           => 0, // This is the key change
                        'required'         => true,
                    ]);
                    ?>
                </div>
                <div class="form-group">
                    <label for="ticket_content">پیام شما:</label>
                    <textarea id="ticket_content" name="ticket_content" rows="6" required></textarea>
                </div>
                <div class="form-group">
                    <label for="ticket_attachments">پیوست فایل (اختیاری):</label>
                    <input type="file" name="ticket_attachments[]" id="ticket_attachments" multiple>
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

            if ($is_team_member && !$is_manager) {
                // Team members see tickets in their department or assigned to them
                $user_positions = wp_get_object_terms($current_user_id, 'organizational_position');
                $department_term_ids = [];

                if (!is_wp_error($user_positions) && !empty($user_positions)) {
                    foreach ($user_positions as $pos) {
                        $department_term_ids[] = ($pos->parent) ? $pos->parent : $pos->term_id;
                    }
                }
                
                $args['tax_query'] = [
                    'relation' => 'OR',
                    [
                        'taxonomy' => 'organizational_position',
                        'field'    => 'term_id',
                        'terms'    => $department_term_ids,
                    ]
                ];
                $args['meta_query'] = [
                     'relation' => 'OR',
                    [
                        'key' => '_assigned_to',
                        'value' => $current_user_id,
                    ]
                ];

            } elseif (!$is_manager) { // Customer
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
                            <?php if ($is_manager || $is_team_member) echo '<th>مشتری/دپارتمان</th>'; ?>
                            <th>آخرین بروزرسانی</th>
                            <th>وضعیت</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($tickets_query->have_posts()) : $tickets_query->the_post(); 
                            $ticket_id = get_the_ID();
                            $status_terms = get_the_terms($ticket_id, 'ticket_status');
                            $department_terms = get_the_terms($ticket_id, 'organizational_position');

                            $status_name = !empty($status_terms) ? esc_html($status_terms[0]->name) : 'نامشخص';
                            $status_slug = !empty($status_terms) ? esc_attr($status_terms[0]->slug) : 'default';
                            $department_name = !empty($department_terms) ? esc_html($department_terms[0]->name) : '---';
                            
                            $view_url = add_query_arg(['ticket_id' => $ticket_id], $base_url);
                        ?>
                            <tr>
                                <td><a href="<?php echo esc_url($view_url); ?>"><?php the_title(); ?></a></td>
                                <?php if ($is_manager || $is_team_member): ?>
                                    <td><?php echo esc_html(get_the_author()); ?> / <strong><?php echo $department_name; ?></strong></td>
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