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

// WP_Query arguments
$args = [
    'post_type' => 'project',
    'author' => $current_user->ID, // Only get projects for the logged-in user
    'posts_per_page' => -1,
    'post_status' => 'publish',
];

$projects_query = new WP_Query($args);
?>

<div class="puzzling-projects-list">
    <h3><span class="dashicons dashicons-portfolio"></span> لیست پروژه‌های شما</h3>
    
    <?php if ($projects_query->have_posts()) : ?>
        <ul>
            <?php while ($projects_query->have_posts()) : $projects_query->the_post(); ?>
                <li>
                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                    <div class="project-excerpt">
                        <?php the_excerpt(); ?>
                    </div>
                </li>
            <?php endwhile; ?>
        </ul>
        <?php wp_reset_postdata(); ?>
    <?php else : ?>
        <p>در حال حاضر هیچ پروژه‌ای برای شما تعریف نشده است.</p>
    <?php endif; ?>
</div>
<style>
.puzzling-projects-list ul { list-style: none; padding: 0; }
.puzzling-projects-list li { background: #f9f9f9; border: 1px solid #ddd; padding: 15px; margin-bottom: 10px; border-radius: 4px; }
.puzzling-projects-list li a { font-weight: bold; text-decoration: none; color: var(--secondary-color, #1D1E29); }
</style>