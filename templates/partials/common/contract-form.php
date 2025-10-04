<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! current_user_can('manage_options') ) return;

$contract_to_edit = isset($puzzling_contract) ? $puzzling_contract : null;
?>
<div class="pzl-form-container">
    <h3><i class="fas fa-file-signature"></i> <?php echo $contract_to_edit ? 'ویرایش قرارداد' : 'ایجاد قرارداد جدید'; ?></h3>
    <form id="manage-contract-form" class="pzl-ajax-form" data-action="puzzling_manage_contract" method="post" enctype="multipart/form-data">
        <?php wp_nonce_field('puzzlingcrm-ajax-nonce', 'security'); ?>
        <input type="hidden" name="contract_id" value="<?php echo $contract_to_edit ? esc_attr($contract_to_edit->ID) : '0'; ?>">

        <h4><i class="fas fa-user-tie"></i> اطلاعات مشتری و قرارداد</h4>
        <div class="pzl-form-row">
            <div class="form-group">
                <label for="customer_id">مشتری (ضروری):</label>
                <select name="customer_id" id="customer_id" required <?php echo $contract_to_edit ? 'disabled' : ''; ?>>
                    <option value="">-- انتخاب مشتری --</option>
                    <?php
                    $customers = get_users(['role__in' => ['customer', 'subscriber'], 'orderby' => 'display_name']);
                    $selected_customer = $contract_to_edit ? $contract_to_edit->post_author : 0;
                    foreach ($customers as $customer) {
                        echo '<option value="' . esc_attr($customer->ID) . '" ' . selected($selected_customer, $customer->ID, false) . '>' . esc_html($customer->display_name) . '</option>';
                    }
                    ?>
                </select>
                 <?php if ($contract_to_edit) : ?>
                    <input type="hidden" name="customer_id" value="<?php echo esc_attr($selected_customer); ?>">
                    <p class="description">مشتری پس از ایجاد قرارداد قابل تغییر نیست.</p>
                <?php endif; ?>
            </div>
             <div class="form-group">
                <label for="contract_title">عنوان قرارداد (اختیاری):</label>
                <input type="text" id="contract_title" name="contract_title" value="<?php echo $contract_to_edit ? esc_attr($contract_to_edit->post_title) : ''; ?>" placeholder="مثال: قرارداد پشتیبانی سالانه">
            </div>
        </div>
        
         <div class="pzl-form-row">
            <div class="form-group"><label for="_project_subscription_model">مدل اشتراک:</label><select name="_project_subscription_model" id="_project_subscription_model"><?php $models = ['یکبار پرداخت' => 'onetime', 'اشتراکی' => 'subscription']; $current_model = $contract_to_edit ? get_post_meta($contract_to_edit->ID, '_project_subscription_model', true) : 'onetime'; foreach ($models as $label => $value) { echo '<option value="' . esc_attr($value) . '" ' . selected($current_model, $value, false) . '>' . esc_html($label) . '</option>'; } ?></select></div>
            <div class="form-group"><label for="_project_contract_duration">مدت قرارداد:</label><select name="_project_contract_duration" id="_project_contract_duration"><?php $durations = ['یک ماهه' => '1-month', 'سه ماهه' => '3-months', 'شش ماهه' => '6-months', 'یک ساله' => '12-months']; $current_duration = $contract_to_edit ? get_post_meta($contract_to_edit->ID, '_project_contract_duration', true) : '1-month'; foreach ($durations as $label => $value) { echo '<option value="' . esc_attr($value) . '" ' . selected($current_duration, $value, false) . '>' . esc_html($label) . '</option>'; } ?></select></div>
        </div>

         <div class="pzl-form-row">
            <div class="form-group"><label for="_project_start_date">تاریخ شروع:</label><input type="text" id="_project_start_date" name="_project_start_date" value="<?php echo $contract_to_edit ? esc_attr(puzzling_gregorian_to_jalali(get_post_meta($contract_to_edit->ID, '_project_start_date', true))) : ''; ?>" class="pzl-jalali-date-picker"></div>
            <div class="form-group"><label for="_project_end_date">تاریخ پایان:</label><input type="text" id="_project_end_date" name="_project_end_date" value="<?php echo $contract_to_edit ? esc_attr(puzzling_gregorian_to_jalali(get_post_meta($contract_to_edit->ID, '_project_end_date', true))) : ''; ?>" class="pzl-jalali-date-picker" readonly></div>
        </div>

        <hr>
        <h4><i class="fas fa-calculator"></i> اقساط قرارداد</h4>
        <div id="payment-rows-container">
            <?php if ($contract_to_edit) { $installments = get_post_meta($contract_to_edit->ID, '_installments', true); if (!empty($installments) && is_array($installments)) { foreach ($installments as $index => $inst) { ?><div class="payment-row form-group" style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;"><input type="number" name="payment_amount[]" placeholder="مبلغ (تومان)" value="<?php echo esc_attr($inst['amount']); ?>" required><input type="text" name="payment_due_date[]" value="<?php echo esc_attr(puzzling_gregorian_to_jalali($inst['due_date'])); ?>" class="pzl-jalali-date-picker" required><select name="payment_status[]"><option value="pending" <?php selected($inst['status'] ?? 'pending', 'pending'); ?>>در انتظار</option><option value="paid" <?php selected($inst['status'] ?? 'pending', 'paid'); ?>>پرداخت شده</option></select><button type="button" class="pzl-button pzl-button-sm remove-payment-row" style="background: #dc3545 !important;">حذف</button></div><?php } } } ?>
        </div>
        <button type="button" id="add-payment-row" class="pzl-button" style="align-self: flex-start;">افزودن قسط دستی</button>
        
        <hr style="margin: 20px 0;">
        <button type="submit" name="submit_contract" class="pzl-button" style="font-size: 16px;"><?php echo $contract_to_edit ? 'ذخیره تغییرات قرارداد' : 'ایجاد قرارداد'; ?></button>
    </form>
    
    <?php if ($contract_to_edit) : ?>
    <div id="pzl-automation-from-product" style="margin-top: 30px; border-top: 1px solid #dee2e6; padding-top: 20px;">
        <h4><i class="fas fa-magic"></i> افزودن خدمات از محصول</h4>
        <p class="description">یک محصول اشتراکی یا گروهی را انتخاب کنید تا پروژه‌های مرتبط با خدمات آن به صورت خودکار به این قرارداد اضافه شوند.</p>
        <div class="pzl-form-row" style="align-items: flex-end;">
            <div class="form-group" style="flex: 2;">
                <label for="product_id_for_automation">انتخاب محصول:</label>
                <select id="product_id_for_automation">
                    <option value="">-- انتخاب کنید --</option>
                    <?php
                    // Query for subscription, grouped, and bundled products
                    $products = wc_get_products(['type' => ['subscription', 'grouped', 'bundle'], 'limit' => -1, 'status' => 'publish']);
                    foreach ($products as $product) {
                        echo '<option value="' . esc_attr($product->get_id()) . '">' . esc_html($product->get_name()) . '</option>';
                    }
                    ?>
                </select>
            </div>
            <div class="form-group">
                 <button type="button" id="add-services-from-product" class="pzl-button">افزودن خدمات محصول</button>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>