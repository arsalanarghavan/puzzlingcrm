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
        <ul class="pzl-project-cards-list">
            <?php while ($projects_query->have_posts()) : $projects_query->the_post(); ?>
                <li class="pzl-project-card">
                    <a href="<?php the_permalink(); ?>">
                        <i class="fas fa-folder-open"></i>
                        <h4 class="project-title"><?php the_title(); ?></h4>
                    </a>
                    <div class="project-excerpt">
                        <?php the_excerpt(); ?>
                    </div>
                </li>
            <?php endwhile; ?>
        </ul>
        <?php wp_reset_postdata(); ?>
    <?php else : ?>
        <div class="pzl-empty-state">
            <i class="fas fa-exclamation-circle"></i>
            <h4>پروژه‌ای یافت نشد</h4>
            <p>بعد از تعریف پروژه توسط تیم پازلینگ، در این بخش برای شما نمایش داده خواهد شد.</p>
        </div>
    <?php endif; ?>
</div>