<?php
/**
 * Template for listing projects for the current team member with full details (read-only).
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
    <h3><i class="ri-folder-2-line"></i> لیست پروژه‌های شما</h3>
    
    <?php if ($projects_query && $projects_query->have_posts()) : ?>
        <div class="pzl-projects-grid-view">
            <?php while ($projects_query->have_posts()) : $projects_query->the_post(); 
                $project_id = get_the_ID();
                $customer = get_userdata(get_the_author_meta('ID'));
                $contract_id = get_post_meta($project_id, '_contract_id', true);
                
                // Fetch data from contract for display
                $model_val = $contract_id ? get_post_meta($contract_id, '_project_subscription_model', true) : '';
                $model_map = ['onetime' => 'یکبار پرداخت', 'subscription' => 'اشتراکی'];
                $model_text = $model_map[$model_val] ?? '---';

                $duration_val = $contract_id ? get_post_meta($contract_id, '_project_contract_duration', true) : '';
                $duration_map = ['1-month' => 'یک ماهه', '3-months' => 'سه ماهه', '6-months' => 'شش ماهه', '12-months' => 'یک ساله'];
                $duration_text = $duration_map[$duration_val] ?? '---';
                
                $end_date = $contract_id ? get_post_meta($contract_id, '_project_end_date', true) : '';

                $status_terms = get_the_terms($project_id, 'project_status');
                $status_name = !empty($status_terms) ? esc_html($status_terms[0]->name) : '---';
                $status_slug = !empty($status_terms) ? esc_attr($status_terms[0]->slug) : 'default';
            ?>
                <div class="pzl-project-card-item">
                    <div class="pzl-project-card-header-flex">
                        <div class="pzl-project-card-logo">
                            <?php if (has_post_thumbnail()) { the_post_thumbnail('thumbnail'); } else { echo '<div class="pzl-logo-placeholder">' . esc_html(mb_substr(get_the_title(), 0, 1)) . '</div>'; } ?>
                        </div>
                        <div class="pzl-project-card-title-group">
                            <h4 class="pzl-project-card-title"><?php the_title(); ?></h4>
                            <span class="pzl-project-card-customer"><?php echo esc_html($customer->display_name); ?></span>
                        </div>
                    </div>
                    <div class="pzl-project-card-details-grid">
                        <div><i class="ri-toggle-line"></i> وضعیت: <span class="pzl-status-badge status-<?php echo $status_slug; ?>"><?php echo $status_name; ?></span></div>
                        <div><i class="ri-refresh-line"></i> مدل: <strong><?php echo esc_html($model_text); ?></strong></div>
                        <div><i class="ri-calendar-line"></i> مدت: <strong><?php echo esc_html($duration_text); ?></strong></div>
                        <div><i class="ri-hourglass-line"></i> پایان: <strong><?php echo $end_date ? jdate('Y/m/d', strtotime($end_date)) : '---'; ?></strong></div>
                    </div>
                    <div class="pzl-project-card-actions">
                        <a href="<?php the_permalink(); ?>" class="pzl-button pzl-button-sm">مشاهده وظایف پروژه</a>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
        <?php wp_reset_postdata(); ?>
    <?php else : ?>
        <div class="pzl-empty-state">
            <i class="ri-error-warning-line"></i>
            <h4>پروژه‌ای یافت نشد</h4>
            <p>در حال حاضر شما در هیچ پروژه‌ای وظیفه‌ای ندارید.</p>
        </div>
    <?php endif; ?>
</div>
<style>
/* Add these styles to your main CSS file (puzzlingcrm-styles.css) if they don't exist */
.pzl-project-card-details-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 13px; color: #333; margin: 15px 0; flex-grow: 1; align-content: start; }
.pzl-project-card-details-grid div { display: flex; align-items: center; gap: 6px; }
.pzl-project-card-details-grid .fas { color: var(--pzl-primary-color); }
.pzl-project-card-header-flex { display: flex; align-items: center; gap: 15px; margin-bottom: 15px; }
.pzl-project-card-title-group { text-align: right; }
.pzl-project-card-logo .pzl-logo-placeholder { width: 60px; height: 60px; border-radius: 50%; background-color: var(--pzl-primary-light); color: var(--pzl-secondary-color); display: flex; align-items: center; justify-content: center; font-size: 24px; font-weight: bold; }
</style>