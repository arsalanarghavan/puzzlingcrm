<?php
/**
 * Task Details Modal Template (Xintra Style)
 * @package PuzzlingCRM
 */

if (!defined('ABSPATH')) exit;

$task_id = isset($task_id) ? $task_id : get_the_ID();
$task = get_post($task_id);

if (!$task) {
    echo '<div class="alert alert-danger">وظیفه یافت نشد</div>';
    return;
}

// Get task metadata
$project_id = get_post_meta($task_id, '_project_id', true);
$assigned_to = get_post_meta($task_id, '_assigned_to', true);
$due_date = get_post_meta($task_id, '_due_date', true);
$start_date = get_post_meta($task_id, '_start_date', true);
$story_points = get_post_meta($task_id, '_story_points', true);

// Get taxonomies
$status_terms = get_the_terms($task_id, 'task_status');
$priority_terms = get_the_terms($task_id, 'task_priority');
$label_terms = get_the_terms($task_id, 'task_label');

$status_name = $status_terms && !is_wp_error($status_terms) ? $status_terms[0]->name : 'نامشخص';
$status_slug = $status_terms && !is_wp_error($status_terms) ? $status_terms[0]->slug : '';
$priority_name = $priority_terms && !is_wp_error($priority_terms) ? $priority_terms[0]->name : 'متوسط';

// Get comments
$comments = get_comments(['post_id' => $task_id, 'status' => 'approve', 'order' => 'ASC']);

// Get checklist
$checklist = get_post_meta($task_id, '_task_checklist', true);
if (!is_array($checklist)) $checklist = [];

// Get activity log
$activity_log = get_post_meta($task_id, '_task_activity_log', true);
if (!is_array($activity_log)) $activity_log = [];

// Get linked tasks
$linked_tasks = get_post_meta($task_id, '_linked_tasks', true);
if (!is_array($linked_tasks)) $linked_tasks = [];
?>

<div class="modal-header">
    <h6 class="modal-title"><?php echo esc_html($task->post_title); ?></h6>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>

<div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
    <div class="row">
        <!-- Main Content -->
        <div class="col-lg-8">
            <!-- Tabs -->
            <ul class="nav nav-tabs mb-3" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" data-bs-toggle="tab" href="#task-details">
                        <i class="ri-file-list-line me-1"></i>جزئیات وظایف
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#task-linked">
                        <i class="ri-links-line me-1"></i>پیوند شده
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#task-checklist">
                        <i class="ri-checkbox-line me-1"></i>چک‌لیست
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#task-time">
                        <i class="ri-time-line me-1"></i>زمان
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#task-attachments">
                        <i class="ri-attachment-2 me-1"></i>پیوست‌ها
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#task-history">
                        <i class="ri-history-line me-1"></i>تاریخچه
                    </a>
                </li>
            </ul>

            <!-- Tab Content -->
            <div class="tab-content">
                <!-- Details Tab -->
                <div class="tab-pane fade show active" id="task-details">
                    <div class="card custom-card mb-3">
                        <div class="card-header">
                            <div class="card-title">توضیحات</div>
                        </div>
                        <div class="card-body">
                            <?php if ($task->post_content): ?>
                                <div class="task-description"><?php echo wp_kses_post($task->post_content); ?></div>
                            <?php else: ?>
                                <p class="text-muted">
                                    <i class="ri-information-line me-2"></i>
                                    توضیحاتی برای این وظیفه ثبت نشده است. برای افزودن کلیک کنید.
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Comments -->
                    <div class="card custom-card">
                        <div class="card-header">
                            <div class="card-title">فعالیت و نظرات</div>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($comments)): ?>
                                <?php foreach ($comments as $comment): ?>
                                <div class="d-flex mb-3">
                                    <div class="me-3">
                                        <?php echo get_avatar($comment->user_id, 40, '', '', ['class' => 'rounded-circle']); ?>
                                    </div>
                                    <div class="flex-fill">
                                        <div class="d-flex justify-content-between align-items-start mb-1">
                                            <strong><?php echo esc_html($comment->comment_author); ?></strong>
                                            <small class="text-muted"><?php echo human_time_diff(strtotime($comment->comment_date), current_time('timestamp')) . ' پیش'; ?></small>
                                        </div>
                                        <p class="mb-0"><?php echo wp_kses_post($comment->comment_content); ?></p>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-muted mb-3">
                                    <i class="ri-chat-3-line me-2"></i>
                                    هنوز نظری ثبت نشده است.
                                </p>
                            <?php endif; ?>
                            
                            <!-- Add Comment Form -->
                            <div class="mt-3">
                                <textarea class="form-control" id="new-comment-<?php echo $task_id; ?>" rows="3" placeholder="نظر خود را بنویسید... (@ برای منشن کردن)"></textarea>
                                <button class="btn btn-primary btn-sm mt-2 btn-add-comment" data-task-id="<?php echo $task_id; ?>">
                                    <i class="ri-send-plane-line me-1"></i>ارسال نظر
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Linked Tasks Tab -->
                <div class="tab-pane fade" id="task-linked">
                    <div class="card custom-card">
                        <div class="card-body">
                            <?php if (!empty($linked_tasks)): ?>
                                <div class="list-group">
                                    <?php foreach ($linked_tasks as $linked_id): 
                                        $linked_task = get_post($linked_id);
                                        if ($linked_task):
                                    ?>
                                    <div class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span><i class="ri-link me-2"></i><?php echo esc_html($linked_task->post_title); ?></span>
                                            <button class="btn btn-sm btn-icon btn-danger-light">
                                                <i class="ri-close-line"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <?php 
                                        endif;
                                    endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted text-center py-3">
                                    <i class="ri-link-unlink-m fs-3 d-block mb-2 opacity-3"></i>
                                    هیچ وظیفه پیوند شده‌ای وجود ندارد.
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Checklist Tab -->
                <div class="tab-pane fade" id="task-checklist">
                    <div class="card custom-card">
                        <div class="card-header">
                            <div class="card-title">چک‌لیست</div>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($checklist)): ?>
                                <div class="checklist-items">
                                    <?php foreach ($checklist as $index => $item): ?>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="check-<?php echo $index; ?>" <?php checked($item['checked'], true); ?>>
                                        <label class="form-check-label" for="check-<?php echo $index; ?>">
                                            <?php echo esc_html($item['text']); ?>
                                        </label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted">
                                    <i class="ri-checkbox-blank-line me-2"></i>
                                    هیچ آیتمی در چک‌لیست وجود ندارد.
                                </p>
                            <?php endif; ?>
                            
                            <!-- Add Checklist Item -->
                            <div class="input-group mt-3">
                                <input type="text" class="form-control" id="new-checklist-item-<?php echo $task_id; ?>" placeholder="افزودن آیتم جدید...">
                                <button class="btn btn-primary btn-add-checklist" data-task-id="<?php echo $task_id; ?>">
                                    <i class="ri-add-line"></i>افزودن
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Time Tab -->
                <div class="tab-pane fade" id="task-time">
                    <div class="card custom-card">
                        <div class="card-body">
                            <p class="text-muted text-center py-3">
                                <i class="ri-time-line fs-3 d-block mb-2 opacity-3"></i>
                                قابلیت ثبت زمان به زودی...
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Attachments Tab -->
                <div class="tab-pane fade" id="task-attachments">
                    <div class="card custom-card">
                        <div class="card-body">
                            <p class="text-muted text-center py-3">
                                <i class="ri-attachment-2 fs-3 d-block mb-2 opacity-3"></i>
                                هیچ پیوستی وجود ندارد.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- History Tab -->
                <div class="tab-pane fade" id="task-history">
                    <div class="card custom-card">
                        <div class="card-body">
                            <?php if (!empty($activity_log)): ?>
                                <div class="timeline">
                                    <?php foreach (array_slice($activity_log, 0, 10) as $log): ?>
                                    <div class="timeline-item mb-3">
                                        <div class="d-flex">
                                            <div class="me-3">
                                                <span class="avatar avatar-sm avatar-rounded bg-primary-transparent">
                                                    <i class="ri-user-line"></i>
                                                </span>
                                            </div>
                                            <div class="flex-fill">
                                                <p class="mb-1">
                                                    <strong><?php echo esc_html($log['user_name'] ?? 'کاربر'); ?></strong>
                                                    <?php echo esc_html($log['text']); ?>
                                                </p>
                                                <small class="text-muted">
                                                    <i class="ri-time-line me-1"></i>
                                                    <?php echo jdate('Y/m/d H:i', strtotime($log['time'])); ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted text-center py-3">
                                    <i class="ri-history-line fs-3 d-block mb-2 opacity-3"></i>
                                    هیچ فعالیتی ثبت نشده است.
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <div class="card custom-card mb-3">
                <div class="card-header">
                    <div class="card-title"><?php echo esc_html($task->post_title); ?></div>
                </div>
                <div class="card-body">
                    <?php if ($project_id): ?>
                        <p class="mb-2">
                            <i class="ri-folder-line me-2 text-primary"></i>
                            در پروژه: <strong><?php echo esc_html(get_the_title($project_id)); ?></strong>
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">جزئیات</div>
                </div>
                <div class="card-body">
                    <!-- Status -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">وضعیت:</label>
                        <select class="form-select form-select-sm task-status-select" data-task-id="<?php echo $task_id; ?>">
                            <?php
                            $all_statuses = get_terms(['taxonomy' => 'task_status', 'hide_empty' => false]);
                            foreach ($all_statuses as $status) {
                                echo '<option value="' . esc_attr($status->slug) . '" ' . selected($status_slug, $status->slug, false) . '>' . esc_html($status->name) . '</option>';
                            }
                            ?>
                        </select>
                    </div>

                    <!-- Assignee -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">مسئول:</label>
                        <?php if ($assigned_to): 
                            $user = get_userdata($assigned_to);
                        ?>
                        <div class="d-flex align-items-center">
                            <?php echo get_avatar($assigned_to, 32, '', '', ['class' => 'rounded-circle me-2']); ?>
                            <span><?php echo esc_html($user->display_name); ?></span>
                        </div>
                        <?php else: ?>
                        <p class="text-muted mb-0">---</p>
                        <?php endif; ?>
                    </div>

                    <!-- Due Date -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">ددلاین:</label>
                        <p class="mb-0">
                            <?php if ($due_date): ?>
                                <i class="ri-calendar-line me-1 text-danger"></i>
                                <?php echo jdate('Y/m/d', strtotime($due_date)); ?>
                            <?php else: ?>
                                <span class="text-muted">---</span>
                            <?php endif; ?>
                        </p>
                    </div>

                    <!-- Priority -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">اولویت:</label>
                        <p class="mb-0"><?php echo esc_html($priority_name); ?></p>
                    </div>

                    <!-- Story Points -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">امتیاز داستان:</label>
                        <p class="mb-0"><?php echo $story_points ? esc_html($story_points) : '---'; ?></p>
                    </div>

                    <!-- Labels -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">برچسب‌ها:</label>
                        <div>
                            <?php if ($label_terms && !is_wp_error($label_terms)): 
                                foreach ($label_terms as $label):
                            ?>
                                <span class="badge bg-primary-transparent me-1"><?php echo esc_html($label->name); ?></span>
                            <?php 
                                endforeach;
                            else: 
                            ?>
                                <span class="text-muted">---</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="card custom-card mt-3">
                <div class="card-body">
                    <button class="btn btn-secondary btn-sm w-100 mb-2">
                        <i class="ri-save-line me-1"></i>ذخیره به عنوان قالب
                    </button>
                    <button class="btn btn-danger-light btn-sm w-100">
                        <i class="ri-delete-bin-line me-1"></i>حذف وظیفه
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.task-description {
    line-height: 1.8;
}

.timeline-item:last-child {
    border-bottom: 0;
}
</style>
