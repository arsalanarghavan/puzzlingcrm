<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! current_user_can('manage_options') ) return;
?>
<div class="pzl-form-container">
    <h3><span class="dashicons dashicons-plus-alt"></span> ایجاد قرارداد و قسط‌بندی جدید</h3>
    <form id="create-contract-form" method="post">
        <?php wp_nonce_field('puzzling_create_contract'); ?>
        <div class="form-group">
            <label for="project_id">پروژه مورد نظر را انتخاب کنید:</label>
            <select name="project_id" id="project_id" required>
                <option value="">-- انتخاب پروژه --</option>
                <?php
                $projects = get_posts(['post_type' => 'project', 'numberposts' => -1, 'post_status' => 'publish']);
                foreach ($projects as $project) {
                    $existing_contract = get_posts(['post_type' => 'contract','meta_key' => '_project_id','meta_value' => $project->ID]);
                    if (empty($existing_contract)) {
                        echo '<option value="' . esc_attr($project->ID) . '">' . esc_html($project->post_title) . '</option>';
                    }
                }
                ?>
            </select>
        </div>

        <hr>
        <h4>محاسبه‌گر اقساط</h4>
        <div class="form-row" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;">
            <div class="form-group">
                <label for="total_amount">مبلغ کل قرارداد (تومان)</label>
                <input type="text" id="total_amount" placeholder="مثال: 30000000">
            </div>
            <div class="form-group">
                <label for="total_installments">تعداد کل اقساط</label>
                <input type="number" id="total_installments" placeholder="مثال: 6">
            </div>
            <div class="form-group">
                <label for="installment_interval">فاصله بین اقساط (روز)</label>
                <input type="number" id="installment_interval" placeholder="مثال: 30 برای ماهانه">
            </div>
            <div class="form-group">
                <label for="start_date">تاریخ اولین قسط</label>
                <input type="date" id="start_date">
            </div>
        </div>
        <button type="button" id="calculate-installments" class="pzl-button pzl-button-secondary">محاسبه و تولید اقساط</button>

        <hr>
        <h4>لیست اقساط تولید شده</h4>
        <div id="installments-preview-container">
            <p class="description">پس از وارد کردن اطلاعات بالا و کلیک بر روی دکمه محاسبه، لیست اقساط در این قسمت نمایش داده می‌شود.</p>
        </div>
        
        <div id="payment-rows-container" style="display: none;">
            </div>

        <hr style="margin: 20px 0;">
        <button type="submit" name="submit_contract" class="pzl-button pzl-button-primary" style="font-size: 16px;">ایجاد و ثبت قرارداد</button>
    </form>
</div>