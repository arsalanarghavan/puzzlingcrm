<?php
/**
 * Workflow Settings Page Template for System Manager
 * @package PuzzlingCRM
 */

if (!defined('ABSPATH')) exit;
if ( ! current_user_can('manage_options') ) return;

$settings = PuzzlingCRM_Settings_Handler::get_all_settings();
$rules = $settings['workflow_rules'] ?? [];
$statuses = get_terms(['taxonomy' => 'task_status', 'hide_empty' => false, 'orderby' => 'term_order']);
$roles = get_editable_roles();
?>

<div class="pzl-form-container">
    <h4><i class="fas fa-cogs"></i> تنظیمات قوانین گردش کار</h4>
    <p class="description">در این بخش مشخص کنید که چه نقش‌های کاربری اجازه دارند وظایف را به یک وضعیت خاص منتقل کنند. اگر برای یک وضعیت هیچ نقشی انتخاب نشود، همه به آن دسترسی خواهند داشت.</p>
    <form id="puzzling-workflow-settings-form" method="post" class="pzl-form" style="margin-top: 20px;">
        <?php wp_nonce_field('puzzling_save_settings'); ?>
        <input type="hidden" name="puzzling_action" value="save_settings">

        <table class="pzl-table">
            <thead>
                <tr>
                    <th>وضعیت (ستون مقصد)</th>
                    <th>نقش‌های کاربری مجاز</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($statuses as $status): 
                    $allowed_roles = $rules[$status->slug] ?? [];
                ?>
                <tr>
                    <td><strong><?php echo esc_html($status->name); ?></strong></td>
                    <td>
                        <?php foreach($roles as $role_key => $role_details): ?>
                            <label style="margin-right: 15px; font-weight: normal;">
                                <input type="checkbox" 
                                       name="puzzling_settings[workflow_rules][<?php echo esc_attr($status->slug); ?>][]" 
                                       value="<?php echo esc_attr($role_key); ?>"
                                       <?php checked(in_array($role_key, $allowed_roles)); ?>>
                                <?php echo esc_html($role_details['name']); ?>
                            </label>
                        <?php endforeach; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="form-submit">
            <button type="submit" class="pzl-button">ذخیره قوانین گردش کار</button>
        </div>
    </form>
</div>