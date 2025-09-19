<?php
/**
 * Team Member Dashboard Template - Fully Completed & Upgraded
 * Includes My Projects list, AJAX task form, search, and pagination.
 * @package PuzzlingCRM
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

$current_user = wp_get_current_user();
$user_id = $current_user->ID;

// --- Query for Team Member's Projects ---
$tasks_args = [
    'post_type' => 'task',
    'posts_per_page' => -1,
    'meta_key' => '_assigned_to',
    'meta_value' => $user_id,
    'fields' => 'ids', // Only get post IDs for efficiency
];
$assigned_task_ids = get_posts($tasks_args);

$project_ids = [];
if (!empty($assigned_task_ids)) {
    foreach ($assigned_task_ids as $task_id) {
        $project_id = get_post_meta($task_id, '_project_id', true);
        if ($project_id) {
            $project_ids[] = $project_id;
        }
    }
}
$project_ids = array_unique($project_ids); // Get unique project IDs

// Sanitize GET parameters for pagination, search, and project filter.
$paged = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
$search_query = isset($_GET['task_search']) ? sanitize_text_field($_GET['task_search']) : '';
$project_filter = isset($_GET['project_filter']) ? intval($_GET['project_filter']) : 0;
$tasks_per_page = 15;

?>

<div class="pzl-dashboard-section">
    <h3><i class="fas fa-briefcase" style="vertical-align: middle;"></i> <?php esc_html_e('پروژه‌های من', 'puzzlingcrm'); ?></h3>
    <?php if (!empty($project_ids)): 
        $project_query = new WP_Query(['post_type' => 'project', 'post__in' => $project_ids, 'posts_per_page' => -1]);
    ?>
        <div class="pzl-projects-grid">
        <?php while($project_query->have_posts()): $project_query->the_post(); 
            $filter_link = add_query_arg('project_filter', get_the_ID());
            $is_active_filter = ($project_filter === get_the_ID());
        ?>
            <a href="<?php echo esc_url($filter_link); ?>" class="project-card <?php echo $is_active_filter ? 'active' : ''; ?>">
                <?php the_title(); ?>
            </a>
        <?php endwhile; wp_reset_postdata(); ?>
        <?php if ($project_filter): ?>
            <a href="<?php echo esc_url(remove_query_arg('project_filter')); ?>" class="clear-filter-link">&times; <?php esc_html_e('حذف فیلتر', 'puzzlingcrm'); ?></a>
        <?php endif; ?>
        </div>
    <?php else: ?>
        <p><?php esc_html_e('در حال حاضر شما به هیچ پروژه‌ای اختصاص داده نشده‌اید.', 'puzzlingcrm'); ?></p>
    <?php endif; ?>
    <hr>
    <h3><i class="fas fa-list-ul" style="vertical-align: middle;"></i> <?php esc_html_e('وظایف من', 'puzzlingcrm'); ?></h3>

    <div class="add-task-form-container">
        <h4><i class="fas fa-plus-circle"></i> <?php esc_html_e('افزودن وظیفه جدید', 'puzzlingcrm'); ?></h4>
        <form id="puzzling-add-task-form" class="pzl-ajax-form" data-action="puzzling_add_task">
            <?php wp_nonce_field('puzzling_add_task_nonce', 'security'); ?>
            <div class="form-row">
                <input type="text" name="title" placeholder="<?php esc_attr_e('عنوان وظیفه...', 'puzzlingcrm'); ?>" required>
                
                <select name="project_id" required>
                    <option value="">-- <?php esc_html_e('انتخاب پروژه', 'puzzlingcrm'); ?> --</option>
                    <?php
                    $all_projects = get_posts(['post_type' => 'project', 'numberposts' => -1, 'post_status' => 'publish']);
                    foreach ($all_projects as $project) {
                        echo '<option value="' . esc_attr($project->ID) . '">' . esc_html($project->post_title) . '</option>';
                    }
                    ?>
                </select>

                <?php if (current_user_can('assign_tasks')) : ?>
                <select name="assigned_to" required>
                    <option value="">-- <?php esc_html_e('تخصیص به', 'puzzlingcrm'); ?> --</option>
                    <?php
                    $team_members = get_users(['role__in' => ['team_member', 'system_manager', 'administrator']]);
                    foreach ($team_members as $member) {
                        echo '<option value="' . esc_attr($member->ID) . '"' . selected($user_id, $member->ID, false) . '>' . esc_html($member->display_name) . '</option>';
                    }
                    ?>
                </select>
                <?php endif; ?>
                
                <select name="priority" required title="<?php esc_attr_e('اولویت وظیفه', 'puzzlingcrm'); ?>">
                    <?php
                    $priorities = get_terms(['taxonomy' => 'task_priority', 'hide_empty' => false]);
                    foreach ($priorities as $priority) {
                        echo '<option value="' . esc_attr($priority->term_id) . '">' . esc_html($priority->name) . '</option>';
                    }
                    ?>
                </select>
                
                <input type="date" name="due_date" title="<?php esc_attr_e('ددلاین وظیفه', 'puzzlingcrm'); ?>">

                <button type="submit" class="pzl-button"><?php esc_html_e('افزودن', 'puzzlingcrm'); ?></button>
            </div>
        </form>
    </div>

    <div class="task-lists">
        <h4><i class="fas fa-tasks"></i> <?php esc_html_e('وظایف فعال', 'puzzlingcrm'); ?></h4>

        <div class="pzl-search-form">
            <form method="get">
                <?php if ($project_filter): // Keep the project filter active during search ?>
                    <input type="hidden" name="project_filter" value="<?php echo esc_attr($project_filter); ?>">
                <?php endif; ?>
                <input type="search" name="task_search" placeholder="<?php esc_attr_e('جستجوی عنوان وظیفه...', 'puzzlingcrm'); ?>" value="<?php echo esc_attr($search_query); ?>">
                <button type="submit" class="pzl-button"><?php esc_html_e('جستجو', 'puzzlingcrm'); ?></button>
            </form>
        </div>

        <ul id="active-tasks-list" class="task-list">
            <?php
            $active_tasks_args = [
                'post_type' => 'task',
                'posts_per_page' => $tasks_per_page,
                'paged' => $paged,
                's' => $search_query,
                'meta_query' => [
                    'relation' => 'AND',
                    [
                        'key' => '_assigned_to',
                        'value' => $user_id,
                    ],
                ],
                'tax_query' => [
                    [
                        'taxonomy' => 'task_status',
                        'field' => 'slug',
                        'terms' => 'done',
                        'operator' => 'NOT IN'
                    ]
                ],
            ];
            // If a project is selected, filter tasks by that project
            if ($project_filter > 0) {
                $active_tasks_args['meta_query'][] = [
                    'key' => '_project_id',
                    'value' => $project_filter,
                ];
            }
            $active_tasks_query = new WP_Query($active_tasks_args);

            if (!$active_tasks_query->have_posts()) {
                echo '<li class="no-tasks-message">' . esc_html__('هیچ وظیفه فعالی یافت نشد.', 'puzzlingcrm') . '</li>';
            } else {
                while($active_tasks_query->have_posts()) {
                    $active_tasks_query->the_post();
                    echo puzzling_render_task_card(get_post());
                }
            }
            ?>
        </ul>
        <div class="pagination">
            <?php
            echo paginate_links([
                'base' => add_query_arg('paged', '%#%'),
                'total' => $active_tasks_query->max_num_pages,
                'current' => max( 1, $paged ),
                'format' => '?paged=%#%',
                'prev_text' => __('&laquo; قبلی', 'puzzlingcrm'),
                'next_text' => __('بعدی &raquo;', 'puzzlingcrm'),
            ]);
            wp_reset_postdata();
            ?>
        </div>
        
        <hr>

        <h4><i class="fas fa-check-circle"></i> <?php esc_html_e('وظایف اخیراً تکمیل شده', 'puzzlingcrm'); ?></h4>
        <ul id="done-tasks-list" class="task-list">
             <?php
            $done_tasks = get_posts([
                'post_type' => 'task',
                'posts_per_page' => 5,
                'meta_key' => '_assigned_to',
                'meta_value' => $user_id,
                'tax_query' => [['taxonomy' => 'task_status', 'field' => 'slug', 'terms' => 'done']],
            ]);

            if (empty($done_tasks)) {
                echo '<li class="no-tasks-message">' . esc_html__('شما هنوز هیچ وظیفه‌ای را تکمیل نکرده‌اید.', 'puzzlingcrm') . '</li>';
            } else {
                foreach ($done_tasks as $task) { echo puzzling_render_task_card($task); }
            }
            ?>
        </ul>
    </div>
</div>
<style>
/* Add these styles to your main CSS file (puzzlingcrm-styles.css) */
.pzl-projects-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 20px;
}
.project-card {
    background-color: #f0f0f0;
    border: 1px solid #ddd;
    border-radius: 20px;
    padding: 8px 15px;
    text-decoration: none;
    color: #333;
    font-size: 14px;
    transition: all 0.2s ease-in-out;
}
.project-card:hover {
    background-color: #e0e0e0;
    border-color: #ccc;
}
.project-card.active {
    background-color: var(--pzl-primary-color, #F0192A);
    color: #fff;
    border-color: var(--pzl-primary-color, #F0192A);
    font-weight: bold;
}
.clear-filter-link {
    color: var(--pzl-primary-color, #F0192A);
    text-decoration: none;
    margin-left: 10px;
    font-size: 14px;
    align-self: center;
}
</style>