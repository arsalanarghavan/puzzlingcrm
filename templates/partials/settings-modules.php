<?php
/**
 * Modules Settings Tab – Enable/disable dashboard modules (for future use).
 *
 * @package PuzzlingCRM
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! current_user_can( 'manage_options' ) ) {
	return;
}

$settings = class_exists( 'PuzzlingCRM_Settings_Handler' ) ? PuzzlingCRM_Settings_Handler::get_all_settings() : array();

$module_dashboard_enabled  = isset( $settings['module_dashboard_enabled'] ) ? (string) $settings['module_dashboard_enabled'] : '1';
$module_projects_enabled   = isset( $settings['module_projects_enabled'] ) ? (string) $settings['module_projects_enabled'] : '1';
$module_crm_enabled        = isset( $settings['module_crm_enabled'] ) ? (string) $settings['module_crm_enabled'] : '1';
$module_accounting_enabled = isset( $settings['module_accounting_enabled'] ) ? (string) $settings['module_accounting_enabled'] : '1';
?>

<div class="pzl-form-container">
	<h4><i class="ri-apps-line"></i> ماژول‌های داشبورد</h4>
	<p class="description">در آینده می‌توانید هر ماژول را جداگانه روشن یا خاموش کنید. در حال حاضر همه ماژول‌ها فعال هستند.</p>

	<form method="post" class="pzl-form" style="margin-top: 20px;">
		<?php wp_nonce_field( 'puzzling_save_settings_nonce', 'security' ); ?>
		<input type="hidden" name="puzzling_action" value="save_puzzling_settings" />

		<table class="pzl-settings-table" style="width:100%; max-width: 520px;">
			<tbody>
				<tr>
					<td><label for="module_dashboard_enabled">داشبورد پیشفرض</label></td>
					<td>
						<input type="hidden" name="puzzling_settings[module_dashboard_enabled]" value="0">
						<label class="pzl-switch">
							<input type="checkbox" id="module_dashboard_enabled" name="puzzling_settings[module_dashboard_enabled]" value="1" <?php checked( $module_dashboard_enabled, '1' ); ?>>
							<span class="pzl-slider"></span>
						</label>
					</td>
				</tr>
				<tr>
					<td><label for="module_projects_enabled">مدیریت پروژه</label></td>
					<td>
						<input type="hidden" name="puzzling_settings[module_projects_enabled]" value="0">
						<label class="pzl-switch">
							<input type="checkbox" id="module_projects_enabled" name="puzzling_settings[module_projects_enabled]" value="1" <?php checked( $module_projects_enabled, '1' ); ?>>
							<span class="pzl-slider"></span>
						</label>
					</td>
				</tr>
				<tr>
					<td><label for="module_crm_enabled">ارتباط با مشتری (CRM)</label></td>
					<td>
						<input type="hidden" name="puzzling_settings[module_crm_enabled]" value="0">
						<label class="pzl-switch">
							<input type="checkbox" id="module_crm_enabled" name="puzzling_settings[module_crm_enabled]" value="1" <?php checked( $module_crm_enabled, '1' ); ?>>
							<span class="pzl-slider"></span>
						</label>
					</td>
				</tr>
				<tr>
					<td><label for="module_accounting_enabled">حسابداری</label></td>
					<td>
						<input type="hidden" name="puzzling_settings[module_accounting_enabled]" value="0">
						<label class="pzl-switch">
							<input type="checkbox" id="module_accounting_enabled" name="puzzling_settings[module_accounting_enabled]" value="1" <?php checked( $module_accounting_enabled, '1' ); ?>>
							<span class="pzl-slider"></span>
						</label>
					</td>
				</tr>
			</tbody>
		</table>

		<p class="description" style="margin-top:12px;">با غیرفعال کردن یک ماژول، منوی مربوط به آن در سایدبار نمایش داده نمی‌شود و دسترسی به صفحات آن محدود می‌شود.</p>

		<div class="form-submit" style="margin-top: 20px;">
			<button type="submit" class="pzl-button" data-puzzling-skip-global-handler="true">ذخیره تنظیمات</button>
		</div>
	</form>
</div>
