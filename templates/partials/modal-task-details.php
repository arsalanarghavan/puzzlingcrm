<?php
/**
 * Template for the Task Details Modal Content - V2.1 (Status Changer)
 * Loaded via AJAX. Includes new Agile fields and actions.
 * @package PuzzlingCRM
 */
if (!defined('ABSPATH')) exit;

global $post;
// It's safer to use the passed $task variable directly instead of relying on global $post
if (isset($task)) {
    $post = $task;
    setup_postdata($post);
}

// --- Task Data ---
$project_id = get_post_meta($task_id, '_project_id', true);
$project_title = $project_id ? get_the_title($project_id) : '---';
$assigned_user_id = get_post_meta($task_id, '_assigned_to', true);
$assignee = get_userdata($assigned_user_id);
$due_date = get_post_meta($task_id, '_due_date', true);
$time_estimate = get_post_meta($task_id, '_time_estimate', true);
$story_points = get_post_meta($task_id, '_story_points', true); // **NEW**
$checklist = get_post_meta($task_id, '_task_checklist', true) ?: [];
$attachments = get_post_meta($task_id, '_task_attachments', true) ?: [];
$time_logs = get_post_meta($task_id, '_task_time_logs', true) ?: [];
$total_logged = array_sum(wp_list_pluck($time_logs, 'hours'));
$activity_log = get_post_meta($task_id, '_task_activity_log', true) ?: [];
$current_status_terms = wp_get_post_terms($task_id, 'task_status');
$current_status_slug = !empty($current_status_terms) ? $current_status_terms[0]->slug : '';
$all_statuses = get_terms(['taxonomy' => 'task_status', 'hide_empty' => false, 'orderby' => 'term_order']);
?>
<div class="pzl-modal-header">
    <h3 id="pzl-modal-title"><?php echo esc_html($task->post_title); ?></h3>
    <div class="pzl-modal-subtitle">
        در پروژه: <a href="#"><?php echo esc_html($project_title); ?></a>
    </div>
</div>

<div class="pzl-modal-main-content">
    <div class="pzl-modal-tabs">
        <a href="#" class="pzl-modal-tab-link active" data-tab="tab-details"><i class="fas fa-info-circle"></i> جزئیات</a>
        <a href="#" class="pzl-modal-tab-link" data-tab="tab-links"><i class="fas fa-link"></i> وظایف پیوند شده</a>
        <a href="#" class="pzl-modal-tab-link" data-tab="tab-checklist"><i class="fas fa-check-square"></i> چک‌لیست</a>
        <a href="#" class="pzl-modal-tab-link" data-tab="tab-time"><i class="fas fa-clock"></i> زمان</a>
        <a href="#" class="pzl-modal-tab-link" data-tab="tab-attachments"><i class="fas fa-paperclip"></i> پیوست‌ها</a>
        <a href="#" class="pzl-modal-tab-link" data-tab="tab-activity"><i class="fas fa-history"></i> تاریخچه</a>
    </div>

    <div id="tab-details" class="pzl-modal-tab-content">
        <h4><i class="fas fa-align-left"></i> توضیحات</h4>
        <div id="pzl-task-description-viewer">
            <?php echo $task->post_content ? wpautop(wp_kses_post($task->post_content)) : '<p class="pzl-no-content">توضیحاتی برای این وظیفه ثبت نشده است. برای افزودن کلیک کنید.</p>'; ?>
        </div>
        <div id="pzl-task-description-editor" style="display: none;">
            <textarea id="pzl-task-content-input" class="pzl-textarea" rows="6"><?php echo esc_textarea($task->post_content); ?></textarea>
            <button id="pzl-save-task-content" class="pzl-button">ذخیره</button>
            <button type="button" id="pzl-cancel-edit-content" class="pzl-button-secondary">انصراف</button>
        </div>
        <hr>
        <h4><i class="fas fa-comments"></i> فعالیت و نظرات</h4>
        <div class="pzl-task-comments">
            <ul class="pzl-comment-list">
                <?php
                $comments = get_comments(['post_id' => $task_id, 'status' => 'approve', 'order' => 'ASC']);
                if ($comments) {
                    foreach($comments as $comment) {
                        echo '<li class="pzl-comment-item"><div class="pzl-comment-avatar">' . get_avatar($comment->user_id, 32) . '</div><div class="pzl-comment-content"><p><strong>' . esc_html($comment->comment_author) . '</strong>: ' . wp_kses_post(wpautop($comment->comment_content)) . '</p><span class="pzl-comment-date">' . human_time_diff(strtotime($comment->comment_date), current_time('timestamp')) . ' پیش</span></div></li>';
                    }
                } else { echo '<p>هنوز نظری ثبت نشده است.</p>'; }
                ?>
            </ul>
        </div>
        <div class="pzl-add-comment-form">
            <textarea id="pzl-new-comment-text" placeholder="نظر خود را بنویسید... (@ برای منشن کردن)" rows="3"></textarea>
            <button id="pzl-submit-comment" class="pzl-button">ارسال نظر</button>
        </div>
    </div>

    <div id="tab-links" class="pzl-modal-tab-content" style="display:none;">
        <h4><i class="fas fa-link"></i> وظایف پیوند شده</h4>
        <div id="task-links-container">
            <?php 
            $linked_tasks = get_post_meta($task_id, '_task_links', true) ?: [];
            if (empty($linked_tasks)) {
                echo '<p>هیچ وظیفه پیوند شده‌ای وجود ندارد.</p>';
            } else {
                echo '<ul class="pzl-linked-task-list">';
                foreach ($linked_tasks as $link) {
                    $linked_post = get_post($link['task_id']);
                    if ($linked_post) {
                        echo '<li>';
                        echo '<strong>' . esc_html($link['type']) . '</strong> ';
                        echo '<a href="#" class="open-task-modal" data-task-id="'.esc_attr($link['task_id']).'">#' . esc_html($link['task_id']) . ': ' . esc_html($linked_post->post_title) . '</a>';
                        echo '</li>';
                    }
                }
                echo '</ul>';
            }
            ?>
        </div>
        <hr>
        <h5>افزودن پیوند جدید</h5>
        <div class="pzl-form-row">
            <div class="form-group">
                <label>نوع پیوند</label>
                <select id="new-link-type">
                    <option value="relates_to">مرتبط است با</option>
                    <option value="blocks">جلوی انجام این را گرفته</option>
                    <option value="is_blocked_by">انجامش توسط این مسدود شده</option>
                </select>
            </div>
            <div class="form-group" style="flex: 2;">
                <label>جستجوی وظیفه</label>
                <select id="task-search-for-linking" style="width: 100%;"></select>
            </div>
        </div>
        <button id="pzl-add-task-link-btn" class="pzl-button">افزودن پیوند</button>
    </div>

    <div id="tab-checklist" class="pzl-modal-tab-content" style="display:none;">
        <h4><i class="fas fa-check-square"></i> چک‌لیست</h4>
        <ul class="pzl-checklist">
            <?php foreach($checklist as $id => $item): ?>
                <li class="pzl-checklist-item" data-item-id="<?php echo esc_attr($id); ?>">
                    <input type="checkbox" id="<?php echo esc_attr($id); ?>" <?php checked($item['checked']); ?>>
                    <label for="<?php echo esc_attr($id); ?>"><?php echo esc_html($item['text']); ?></label>
                    <span class="pzl-delete-checklist-item">&times;</span>
                </li>
            <?php endforeach; ?>
        </ul>
        <form id="pzl-add-checklist-item-form" class="pzl-form-inline">
            <input type="text" placeholder="افزودن آیتم جدید..." required>
            <button type="submit" class="pzl-button">افزودن</button>
        </form>
    </div>

    <div id="tab-time" class="pzl-modal-tab-content" style="display:none;">
        <h4><i class="fas fa-clock"></i> ثبت زمان</h4>
        <div class="pzl-time-summary">
            <span>زمان تخمینی: <strong><?php echo esc_html($time_estimate ?: '0'); ?> ساعت</strong></span>
            <span>زمان ثبت شده: <strong><?php echo esc_html($total_logged); ?> ساعت</strong></span>
        </div>
        <form id="pzl-log-time-form" class="pzl-form">
            <div class="pzl-form-row">
                <div class="form-group"><label>ساعت</label><input type="number" name="hours" step="0.1" required></div>
                <div class="form-group" style="flex:2;"><label>توضیحات</label><input type="text" name="description"></div>
            </div>
            <button type="submit" class="pzl-button">ثبت زمان</button>
        </form>
        <hr>
        <h5><i class="fas fa-history"></i> لاگ‌های زمان</h5>
        <ul class="pzl-time-log-list">
             <?php foreach(array_reverse((array)$time_logs) as $log): ?>
                <li><strong><?php echo esc_html($log['user_name']); ?></strong> <?php echo esc_html($log['hours']); ?> ساعت ثبت کرد <span class="pzl-time-log-desc">(<?php echo esc_html($log['description']); ?>)</span><span class="pzl-time-log-date"><?php echo date_i18n('Y/m/d', strtotime($log['date'])); ?></span></li>
             <?php endforeach; ?>
        </ul>
    </div>
    
    <div id="tab-attachments" class="pzl-modal-tab-content" style="display:none;">
         <h4><i class="fas fa-paperclip"></i> فایل‌های پیوست</h4>
         <ul class="pzl-attachment-list">
         <?php if(!empty($attachments) && is_array($attachments)): foreach($attachments as $att_id): 
            $file_url = wp_get_attachment_url($att_id);
            $file_name = get_the_title($att_id);
         ?>
            <li><a href="<?php echo esc_url($file_url); ?>" target="_blank"><i class="fas fa-file"></i> <?php echo esc_html($file_name); ?></a></li>
         <?php endforeach; else: ?>
            <p>فایلی پیوست نشده است.</p>
         <?php endif; ?>
         </ul>
    </div>

    <div id="tab-activity" class="pzl-modal-tab-content" style="display:none;">
        <h4><i class="fas fa-history"></i> تاریخچه فعالیت</h4>
        <ul class="pzl-activity-log">
        <?php foreach(array_reverse((array)$activity_log) as $log): ?>
            <li><strong><?php echo esc_html($log['user_name']); ?></strong> <?php echo esc_html($log['text']); ?> <span class="pzl-activity-time"><?php echo human_time_diff(strtotime($log['time']), current_time('timestamp')); ?> پیش</span></li>
        <?php endforeach; ?>
        </ul>
    </div>
</div>

<div class="pzl-modal-sidebar">
    <h4>جزئیات</h4>
    <div class="pzl-sidebar-item">
        <strong>وضعیت:</strong>
        <select id="pzl-task-status-changer" data-task-id="<?php echo esc_attr($task_id); ?>">
            <?php foreach($all_statuses as $status): ?>
                <option value="<?php echo esc_attr($status->slug); ?>" <?php selected($current_status_slug, $status->slug); ?>>
                    <?php echo esc_html($status->name); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="pzl-sidebar-item">
        <strong>مسئول:</strong>
        <span><?php echo $assignee ? esc_html($assignee->display_name) : '---'; ?></span>
    </div>
    <div class="pzl-sidebar-item">
        <strong>ددلاین:</strong>
        <span><?php echo $due_date ? esc_html(date_i18n('Y/m/d', strtotime($due_date))) : '---'; ?></span>
    </div>
    <div class="pzl-sidebar-item">
        <strong>اولویت:</strong>
        <span><?php the_terms($task_id, 'task_priority'); ?></span>
    </div>
    <div class="pzl-sidebar-item">
        <strong>امتیاز داستان:</strong>
        <span><?php echo !empty($story_points) ? esc_html($story_points) : '---'; ?></span>
    </div>
     <div class="pzl-sidebar-item">
        <strong>برچسب‌ها:</strong>
        <span><?php the_terms($task_id, 'task_label', '', ', '); ?></span>
    </div>
    <hr>
    <div class="pzl-modal-actions">
        <button id="pzl-save-as-template-btn" class="pzl-button pzl-button-sm">
            <i class="fas fa-clone"></i> ذخیره به عنوان قالب
        </button>
    </div>
</div>
<?php wp_reset_postdata(); ?>