<?php
/**
 * Template for listing projects for the current team member.
 *
 * @package PuzzlingCRM
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$current_user = wp_get_current_user();
if ($current_user->ID === 0) {
    echo '<p>برای مشاهده پروژه‌ها، لطفاً ابتدا وارد شوید.</p>';
    return;
}

// --- Query for Team Member's Projects ---
$tasks_args = [
    'post_type' => 'task',
    'posts_per_page' => -1,
    'meta_key' => '_assigned_to',
    'meta_value' => $current_user->ID,
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

$projects_query = null;
if (!empty($project_ids)) {
    $projects_query = new WP_Query([
        'post_type' => 'project',
        'post__in' => $project_ids,
        'posts_per_page' => -1,
        'post_status' => 'publish',
    ]);
}
?>

<div class="puzzling-projects-list">
    <h3><i class="fas fa-briefcase"></i> لیست پروژه‌های شما</h3>
    
    <?php if ($projects_query && $projects_query->have_posts()) : ?>
        <div class="pzl-projects-grid-view">
            <?php while ($projects_query->have_posts()) : $projects_query->the_post(); 
                $project_category = get_post_meta(get_the_ID(), '_project_category', true) ?: '---';
                $customer = get_userdata(get_the_author_meta('ID'));
            ?>
                <div class="pzl-project-card-item">
                     <div class="pzl-project-card-header-flex">
                        <div class="pzl-project-card-logo">
                            <?php if (has_post_thumbnail()) : ?>
                                <?php the_post_thumbnail('thumbnail'); ?>
                            <?php else: ?>
                                <div class="pzl-logo-placeholder"><?php echo esc_html(mb_substr(get_the_title(), 0, 1)); ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="pzl-project-card-title-group">
                            <h4 class="pzl-project-card-title"><?php the_title(); ?></h4>
                            <span class="pzl-project-card-customer"><?php echo esc_html($customer->display_name); ?></span>
                        </div>
                    </div>
                    <div class="project-excerpt">
                        <?php the_excerpt(); ?>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
        <?php wp_reset_postdata(); ?>
    <?php else : ?>
        <div class="pzl-empty-state">
            <i class="fas fa-exclamation-circle"></i>
            <h4>پروژه‌ای یافت نشد</h4>
            <p>در حال حاضر شما در هیچ پروژه‌ای وظیفه‌ای ندارید.</p>
        </div>
    <?php endif; ?>
</div>