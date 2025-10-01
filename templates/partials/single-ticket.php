<?php
/**
 * Template for displaying a single ticket and its replies (comments).
 * @package PuzzlingCRM
 */
if ( ! defined( 'ABSPATH' ) ) exit;

global $post;

$current_user = wp_get_current_user();
$is_manager = current_user_can('manage_options');
$is_team_member = in_array('team_member', (array)$current_user->roles);


// Security Check: Get the post and ensure the current user has permission to view it.
$ticket = get_post($ticket_id_to_view);
if ( !$ticket || $ticket->post_type !== 'ticket' || (!$is_manager && !$is_team_member && $ticket->post_author != $current_user->ID) ) {
    echo '<p>شما دسترسی لازم برای مشاهده این تیکت را ندارید یا تیکت مورد نظر یافت نشد.</p>';
    return;
}

// Team member access check
if ($is_team_member && !$is_manager) {
    $user_positions = wp_get_object_terms($current_user->ID, 'organizational_position');
    $department_term_ids = [];
    if (!is_wp_error($user_positions) && !empty($user_positions)) {
        foreach ($user_positions as $pos) {
            $department_term_ids[] = ($pos->parent) ? $pos->parent : $pos->term_id;
        }
    }
    $ticket_departments = wp_get_post_terms($ticket->ID, 'organizational_position', ['fields' => 'ids']);
    $assigned_to = get_post_meta($ticket->ID, '_assigned_to', true);

    if (empty(array_intersect($department_term_ids, $ticket_departments)) && $assigned_to != $current_user->ID) {
         echo '<p>شما دسترسی لازم برای مشاهده این تیکت را ندارید.</p>';
         return;
    }
}


$post = $ticket;
setup_postdata($post);

$status_terms = get_the_terms($ticket->ID, 'ticket_status');
$department_terms = get_the_terms($ticket->ID, 'organizational_position');
$assigned_to_id = get_post_meta($ticket->ID, '_assigned_to', true);

$status_name = !empty($status_terms) ? esc_html($status_terms[0]->name) : 'نامشخص';
$status_slug = !empty($status_terms) ? esc_attr($status_terms[0]->slug) : 'default';
$department_name = !empty($department_terms) ? esc_html($department_terms[0]->name) : '---';
$assigned_to_name = $assigned_to_id ? get_the_author_meta('display_name', $assigned_to_id) : '---';


$base_url = remove_query_arg(['ticket_id', 'puzzling_notice']);
?>

<div class="pzl-single-ticket-wrapper">
    <a href="<?php echo esc_url($base_url); ?>" class="back-to-list-link">&larr; بازگشت به لیست تیکت‌ها</a>

    <div class="ticket-header">
        <h2><?php echo esc_html($ticket->post_title); ?></h2>
        <div class="ticket-meta">
            <span>ارسال شده توسط: <?php echo esc_html(get_the_author_meta('display_name', $ticket->post_author)); ?></span>
            <span>در تاریخ: <?php echo jdate('Y/m/d H:i', strtotime(get_the_date('Y/m/d H:i', $ticket))); ?></span>
            <span class="pzl-status-badge status-<?php echo $status_slug; ?>"><?php echo $status_name; ?></span>
            <span><strong>دپارتمان:</strong> <?php echo $department_name; ?></span>
            <span><strong>مسئول:</strong> <?php echo $assigned_to_name; ?></span>
        </div>
    </div>

    <div class="ticket-content">
        <?php echo wp_kses_post(wpautop($ticket->post_content)); ?>
    </div>

    <div class="ticket-replies">
        <h3>پاسخ‌ها</h3>
        <ul class="comment-list">
            <?php
            $comments = get_comments(['post_id' => $ticket->ID, 'status' => 'approve', 'order' => 'ASC']);
            if ($comments) {
                wp_list_comments(['per_page' => -1, 'reverse_top_level' => false, 'callback' => 'puzzling_ticket_comment_template'], $comments);
            } else {
                echo '<li><p>هنوز پاسخی برای این تیکت ثبت نشده است.</p></li>';
            }
            ?>
        </ul>
    </div>
    
    <?php if ($status_slug !== 'closed'): ?>
    <div class="ticket-reply-form">
        <h3>ارسال پاسخ جدید</h3>
        <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
            <input type="hidden" name="action" value="puzzling_ticket_reply">
            <input type="hidden" name="ticket_id" value="<?php echo esc_attr($ticket->ID); ?>">
            <input type="hidden" name="redirect_to" value="<?php echo esc_url(add_query_arg(null, null)); ?>">
            <?php wp_nonce_field('puzzling_ticket_reply_nonce', '_wpnonce_ticket_reply'); ?>
            <textarea name="comment" rows="7" required placeholder="پاسخ خود را اینجا بنویسید..."></textarea>
            
            <?php if ($is_manager || $is_team_member): ?>
            <div class="pzl-form-row">
                <div class="form-group-inline half-width">
                    <label for="ticket_status">تغییر وضعیت به:</label>
                    <select name="ticket_status">
                        <?php
                        $statuses = get_terms(['taxonomy' => 'ticket_status', 'hide_empty' => false]);
                        foreach ($statuses as $status) {
                            echo '<option value="' . esc_attr($status->slug) . '" ' . selected($status_slug, $status->slug, false) . '>' . esc_html($status->name) . '</option>';
                        }
                        ?>
                    </select>
                </div>
                 <div class="form-group-inline half-width">
                    <label for="department">تغییر دپارتمان:</label>
                    <?php
                        wp_dropdown_categories([
                            'taxonomy'         => 'organizational_position',
                            'name'             => 'department',
                            'selected'         => !empty($department_terms) ? $department_terms[0]->term_id : 0,
                            'show_option_none' => __('انتخاب دپارتمان', 'puzzlingcrm'),
                            'hierarchical'     => true,
                            'hide_empty'       => false,
                            'parent'           => 0, // Only show top-level departments
                        ]);
                    ?>
                </div>
            </div>
             <div class="form-group-inline">
                <label for="assigned_to">ارجاع به کارمند:</label>
                <select name="assigned_to">
                    <option value="0">-- هیچکس --</option>
                    <?php
                    $staff_users = get_users(['role__in' => ['team_member', 'system_manager', 'administrator']]);
                    foreach ($staff_users as $staff) {
                        echo '<option value="' . esc_attr($staff->ID) . '" ' . selected($assigned_to_id, $staff->ID, false) . '>' . esc_html($staff->display_name) . '</option>';
                    }
                    ?>
                </select>
            </div>
            <?php endif; ?>

            <button type="submit" class="pzl-button">ارسال پاسخ</button>
        </form>
    </div>
    <?php else: ?>
    <div class="ticket-closed-notice">
        <p><i class="fas fa-lock"></i> این تیکت بسته شده است و امکان ارسال پاسخ جدید وجود ندارد.</p>
    </div>
    <?php endif; ?>
</div>

<?php
if (!function_exists('puzzling_ticket_comment_template')) {
    function puzzling_ticket_comment_template($comment, $args, $depth) {
        $user = get_user_by('id', $comment->user_id);
        $is_staff_reply = $user && (user_can($user, 'manage_options') || in_array('team_member', (array)$user->roles));
        $reply_class = $is_staff_reply ? 'admin-reply' : 'client-reply';
        ?>
        <li <?php comment_class($reply_class); ?> id="comment-<?php comment_ID(); ?>">
            <div class="comment-author">
                <?php echo get_avatar($comment, 48); ?>
                <strong class="author-name"><?php echo get_comment_author(); ?></strong>
                <span class="comment-date"><?php printf('%1$s در %2$s', jdate('Y/m/d', strtotime(get_comment_date())), get_comment_time()); ?></span>
            </div>
            <div class="comment-content">
                <?php comment_text(); ?>
            </div>
        </li>
        <?php
    }
}

wp_reset_postdata();
?>