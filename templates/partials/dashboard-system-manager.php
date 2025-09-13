<?php
/**
 * System Manager Dashboard Template
 *
 * @package PuzzlingCRM
 */

$active_tab = isset($_GET['view']) ? sanitize_key($_GET['view']) : 'contracts';
?>

<div class="pzl-dashboard-tabs">
    <a href="?view=contracts" class="pzl-tab <?php echo $active_tab === 'contracts' ? 'active' : ''; ?>">
        <span class="dashicons dashicons-media-text"></span> مدیریت قراردادها
    </a>
    <a href="?view=settings" class="pzl-tab <?php echo $active_tab === 'settings' ? 'active' : ''; ?>">
        <span class="dashicons dashicons-admin-settings"></span> تنظیمات
    </a>
</div>

<div class="pzl-dashboard-tab-content">
    <?php
    if ($active_tab === 'settings') {
        // Include the settings page template
        include PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/dashboard-settings.php';
    } else {
        // The default view is contracts
        ?>
        <div class="pzl-form-container">
            <h3><span class="dashicons dashicons-plus-alt"></span> ایجاد قرارداد و قسط‌بندی جدید</h3>
            <form id="create-contract-form" method="post">
                <?php wp_nonce_field('puzzling_create_contract'); ?>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label for="project_id" style="display: block; margin-bottom: 5px; font-weight: bold;">پروژه مورد نظر را انتخاب کنید:</label>
                    <select name="project_id" id="project_id" style="width: 100%; padding: 8px;" required>
                        <option value="">-- انتخاب پروژه --</option>
                        <?php
                        $projects = get_posts(['post_type' => 'project', 'numberposts' => -1, 'post_status' => 'publish']);
                        foreach ($projects as $project) {
                            $existing_contract = get_posts([
                                'post_type' => 'contract',
                                'meta_key' => '_project_id',
                                'meta_value' => $project->ID
                            ]);
                            if (empty($existing_contract)) {
                                echo '<option value="' . esc_attr($project->ID) . '">' . esc_html($project->post_title) . '</option>';
                            }
                        }
                        ?>
                    </select>
                </div>

                <h4>تعریف اقساط</h4>
                <div id="payment-rows-container">
                    <div class="payment-row form-group" style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                        <input type="number" name="payment_amount[]" placeholder="مبلغ (تومان)" style="flex-grow: 1; padding: 8px;" required>
                        <input type="date" name="payment_due_date[]" style="padding: 8px;" required>
                    </div>
                </div>

                <button type="button" id="add-payment-row" class="pzl-button pzl-button-secondary">افزودن قسط</button>
                <hr style="margin: 20px 0;">
                <button type="submit" name="submit_contract" class="pzl-button pzl-button-primary" style="font-size: 16px;">ایجاد و ثبت قرارداد</button>
            </form>
        </div>
        <?php
    }
    ?>
</div>

<style>
.pzl-dashboard-tabs { border-bottom: 2px solid #e0e0e0; margin-bottom: 20px; display: flex; }
.pzl-tab { padding: 10px 20px; text-decoration: none; color: #555; border-bottom: 2px solid transparent; margin-bottom: -2px; }
.pzl-tab.active { color: var(--primary-color, #F0192A); border-bottom-color: var(--primary-color, #F0192A); font-weight: bold; }
.pzl-tab .dashicons { vertical-align: middle; margin-left: 5px; }
</style>