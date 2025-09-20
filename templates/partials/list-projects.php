<?php
/**
 * Template for listing projects for the current user.
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

$args = [
    'post_type' => 'project',
    'author' => $current_user->ID,
    'posts_per_page' => -1,
    'post_status' => 'publish',
];

$projects_query = new WP_Query($args);
?>

<div class="puzzling-projects-list">
    <h3><i class="fas fa-briefcase"></i> لیست پروژه‌های شما</h3>
    
    <?php if ($projects_query->have_posts()) : ?>
        <div class="pzl-projects-grid-view">
            <?php while ($projects_query->have_posts()) : $projects_query->the_post(); 
                $project_category = get_post_meta(get_the_ID(), '_project_category', true) ?: '---';
            ?>
                <a href="<?php the_permalink(); ?>" class="pzl-project-card-item" style="text-decoration: none; color: inherit;">
                    <div class="pzl-project-card-logo">
                        <?php if (has_post_thumbnail()) : ?>
                            <?php the_post_thumbnail('thumbnail'); ?>
                        <?php else: ?>
                            <i class="fas fa-folder-open"></i>
                        <?php endif; ?>
                    </div>
                    <div class="pzl-project-card-details">
                        <h4 class="pzl-project-card-title"><?php the_title(); ?></h4>
                        <div class="pzl-project-card-meta">
                            <span class="pzl-project-card-category"><?php echo esc_html($project_category); ?></span>
                        </div>
                        <div class="project-excerpt">
                            <?php the_excerpt(); ?>
                        </div>
                    </div>
                </a>
            <?php endwhile; ?>
        </div>
        <?php wp_reset_postdata(); ?>
    <?php else : ?>
        <div class="pzl-empty-state">
            <i class="fas fa-exclamation-circle"></i>
            <h4>پروژه‌ای یافت نشد</h4>
            <p>بعد از تعریف پروژه توسط تیم پازلینگ، در این بخش برای شما نمایش داده خواهد شد.</p>
        </div>
    <?php endif; ?>
</div>