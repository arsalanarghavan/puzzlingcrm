<?php
/**
 * Template for displaying a single ticket and its replies (comments).
 * @package PuzzlingCRM
 */
if ( ! defined( 'ABSPATH' ) ) exit;

global $post;

$current_user = wp_get_current_user();
$is_manager = current_user_can('manage_options');

// Security Check: Get the post and ensure the current user has permission to view it.
$ticket = get_post($ticket_id_to_view);
if ( !$ticket || $ticket->post_type !== 'ticket' || (!$is_manager && $ticket->post_author != $current_user->ID) ) {
    echo '<p>شما دسترسی لازم برای مشاهده این تیکت را ندارید یا تیکت مورد نظر یافت نشد.</p>';
    return;
}

$post = $ticket;
setup_postdata($post);

$status_terms = get_the_terms($ticket->ID, 'ticket_status');
$status_name = !empty($status_terms) ? esc_html($status_terms[0]->name) : 'نامشخص';
$status_slug = !empty($status_terms) ? esc_attr($status_terms[0]->slug) : 'default';
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
            <input type="hidden" name="redirect_to" value="<?php echo esc_url(get_permalink()); ?>">
            <?php wp_nonce_field('puzzling_ticket_reply_nonce', '_wpnonce_ticket_reply'); ?>
            <textarea name="comment" rows="7" required placeholder="پاسخ خود را اینجا بنویسید..."></textarea>
            
            <?php if ($is_manager): ?>
            <div class="form-group-inline">
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
        $ticket_author_id = get_post_field('post_author', $comment->comment_post_ID);
        $is_client_reply = $comment->user_id == $ticket_author_id;
        $reply_class = $is_client_reply ? 'client-reply' : 'admin-reply';
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