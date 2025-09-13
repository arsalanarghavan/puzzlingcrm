<?php
/**
 * Template for the new contract form.
 * Can be included in any part of the dashboard.
 *
 * @package PuzzlingCRM
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Security check: Ensure this is only visible to users who can manage contracts.
if ( ! current_user_can('manage_options') ) { // You can define a more specific capability.
    echo '<p>شما دسترسی لازم برای مشاهده این فرم را ندارید.</p>';
    return;
}
?>

<div class="pzl-form-container">
    <h3><span class="dashicons dashicons-plus-alt"></span> ایجاد قرارداد و قسط‌بندی جدید</h3>
    <p>برای یکی از پروژه‌های تعریف شده، یک قرارداد جدید با برنامه پرداخت اقساط ایجاد کنید.</p>
    
    <form id="create-contract-form" method="post">
        <?php wp_nonce_field('puzzling_create_contract'); ?>

        <div class="form-group" style="margin-bottom: 20px;">
            <label for="project_id" style="display: block; margin-bottom: 5px; font-weight: bold;">پروژه مورد نظر را انتخاب کنید:</label>
            <select name="project_id" id="project_id" style="width: 100%; padding: 8px;" required>
                <option value="">-- انتخاب پروژه --</option>
                <?php
                // Get all published projects
                $projects = get_posts(['post_type' => 'project', 'numberposts' => -1, 'post_status' => 'publish']);
                foreach ($projects as $project) {
                    // Check if a contract already exists for this project to avoid duplicates
                    $existing_contract = get_posts([
                        'post_type' => 'contract',
                        'meta_key' => '_project_id',
                        'meta_value' => $project->ID,
                        'posts_per_page' => 1
                    ]);
                    // Only show projects that don't have a contract yet
                    if (empty($existing_contract)) {
                        echo '<option value="' . esc_attr($project->ID) . '">' . esc_html($project->post_title) . '</option>';
                    }
                }
                ?>
            </select>
        </div>

        <h4>تعریف اقساط</h4>
        <div id="payment-rows-container">
            <div class="payment-row form-group" style="display: flex; align-items-center; gap: 10px; margin-bottom: 10px;">
                <input type="number" name="payment_amount[]" placeholder="مبلغ (تومان)" style="flex-grow: 1; padding: 8px;" required>
                <input type="date" name="payment_due_date[]" style="padding: 8px;" required>
            </div>
        </div>

        <button type="button" id="add-payment-row" class="pzl-button pzl-button-secondary">افزودن قسط</button>
        <hr style="margin: 20px 0;">
        <button type="submit" name="submit_contract" class="pzl-button pzl-button-primary" style="font-size: 16px;">ایجاد و ثبت قرارداد</button>
    </form>
</div>