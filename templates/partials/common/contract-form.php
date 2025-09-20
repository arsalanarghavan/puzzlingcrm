<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! current_user_can('manage_options') ) return;

// We pass the contract object to the template if we are editing
$contract_to_edit = isset($puzzling_contract) ? $puzzling_contract : null;
$project_id = $contract_to_edit ? get_post_meta($contract_to_edit->ID, '_project_id', true) : 0;
$project_to_edit = $project_id ? get_post($project_id) : null;
?>
<div class="pzl-form-container">
    <h3><i class="fas fa-plus-circle"></i> <?php echo $contract_to_edit ? 'ویرایش قرارداد' : 'ایجاد قرارداد و پروژه جدید'; ?></h3>
    <form id="manage-contract-form" class="pzl-ajax-form" data-action="puzzling_manage_contract" method="post" enctype="multipart/form-data">
        <?php wp_nonce_field('puzzlingcrm-ajax-nonce', 'security'); ?>
        <input type="hidden" name="contract_id" value="<?php echo $contract_to_edit ? esc_attr($contract_to_edit->ID) : '0'; ?>">

        <h4><i class="fas fa-user-tie"></i> اطلاعات مشتری و پروژه</h4>
        <div class="pzl-form-row">
            <div class="form-group">
                <label for="customer_id">مشتری:</label>
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
                <label for="project_title">عنوان پروژه:</label>
                <input type="text" id="project_title" name="project_title" value="<?php echo $project_to_edit ? esc_attr($project_to_edit->post_title) : ''; ?>" required>
            </div>
        </div>
        <div class="form-group">
            <label for="project_logo">لوگوی پروژه (تصویر شاخص):</label>
            <input type="file" id="project_logo" name="project_logo" accept="image/*">
            <?php if ($project_to_edit && has_post_thumbnail($project_id)) { echo '<div style="margin-top:10px;">' . get_the_post_thumbnail($project_id, 'thumbnail') . '</div>'; } ?>
        </div>

        <hr>
        <h4><i class="fas fa-file-signature"></i> جزئیات قرارداد</h4>
        <div class="pzl-form-row">
            <div class="form-group">
                <label for="_project_contract_person">شخص طرف قرارداد:</label>
                <input type="text" id="_project_contract_person" name="_project_contract_person" value="<?php echo $contract_to_edit ? esc_attr(get_post_meta($contract_to_edit->ID, '_project_contract_person', true)) : ''; ?>">
            </div>
             <div class="form-group">
                <label for="_project_subscription_model">مدل اشتراک:</label>
                <select name="_project_subscription_model" id="_project_subscription_model">
                    <?php
                    $models = ['یکبار پرداخت' => 'onetime', 'اشتراکی' => 'subscription'];
                    $current_model = $contract_to_edit ? get_post_meta($contract_to_edit->ID, '_project_subscription_model', true) : 'onetime';
                    foreach ($models as $label => $value) {
                        echo '<option value="' . esc_attr($value) . '" ' . selected($current_model, $value, false) . '>' . esc_html($label) . '</option>';
                    }
                    ?>
                </select>
            </div>
            <div class="form-group">
                 <label for="_project_contract_duration">مدت قرارداد:</label>
                <select name="_project_contract_duration" id="_project_contract_duration">
                     <?php
                    $durations = ['یک ماهه' => '1-month', 'سه ماهه' => '3-months', 'شش ماهه' => '6-months', 'یک ساله' => '12-months'];
                    $current_duration = $contract_to_edit ? get_post_meta($contract_to_edit->ID, '_project_contract_duration', true) : '1-month';
                    foreach ($durations as $label => $value) {
                        echo '<option value="' . esc_attr($value) . '" ' . selected($current_duration, $value, false) . '>' . esc_html($label) . '</option>';
                    }
                    ?>
                </select>
            </div>
        </div>
         <div class="pzl-form-row">
            <div class="form-group">
                <label for="_project_start_date">تاریخ شروع قرارداد:</label>
                <input type="date" id="_project_start_date" name="_project_start_date" value="<?php echo $contract_to_edit ? esc_attr(get_post_meta($contract_to_edit->ID, '_project_start_date', true)) : ''; ?>">
            </div>
             <div class="form-group">
                <label for="_project_end_date">تاریخ پایان قرارداد:</label>
                <input type="date" id="_project_end_date" name="_project_end_date" value="<?php echo $contract_to_edit ? esc_attr(get_post_meta($contract_to_edit->ID, '_project_end_date', true)) : ''; ?>">
            </div>
        </div>

        <hr>
        <h4><i class="fas fa-calculator"></i> محاسبه‌گر اقساط</h4>
        <div class="form-row" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;">
            <div class="form-group"><label for="total_amount">مبلغ کل قرارداد (تومان)</label><input type="text" id="total_amount" placeholder="مثال: 30000000"></div>
            <div class="form-group"><label for="total_installments">تعداد کل اقساط</label><input type="number" id="total_installments" placeholder="مثال: 6"></div>
            <div class="form-group"><label for="installment_interval">فاصله بین اقساط (روز)</label><input type="number" id="installment_interval" placeholder="مثال: 30"></div>
            <div class="form-group"><label for="start_date">تاریخ اولین قسط</label><input type="date" id="start_date"></div>
        </div>
        <button type="button" id="calculate-installments" class="pzl-button">محاسبه و تولید اقساط</button>
        <hr>
        <h4><i class="fas fa-tasks"></i> لیست اقساط</h4>
        <div id="payment-rows-container">
            <?php
            if ($contract_to_edit) {
                $installments = get_post_meta($contract_to_edit->ID, '_installments', true);
                if (!empty($installments)) {
                    foreach ($installments as $index => $inst) {
                        ?>
                        <div class="payment-row form-group" style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                            <input type="number" name="payment_amount[]" placeholder="مبلغ (تومان)" value="<?php echo esc_attr($inst['amount']); ?>" required>
                            <input type="date" name="payment_due_date[]" value="<?php echo esc_attr($inst['due_date']); ?>" required>
                            <select name="payment_status[]">
                                <option value="pending" <?php selected($inst['status'], 'pending'); ?>>در انتظار پرداخت</option>
                                <option value="paid" <?php selected($inst['status'], 'paid'); ?>>پرداخت شده</option>
                            </select>
                            <button type="button" class="pzl-button pzl-button-sm remove-payment-row" style="background: #dc3545 !important;">حذف</button>
                        </div>
                        <?php
                    }
                }
            }
            ?>
        </div>
        <button type="button" id="add-payment-row" class="pzl-button" style="align-self: flex-start;">افزودن قسط دستی</button>
        <hr style="margin: 20px 0;">
        <button type="submit" name="submit_contract" class="pzl-button" style="font-size: 16px;"><?php echo $contract_to_edit ? 'ذخیره تغییرات قرارداد' : 'ایجاد قرارداد و پروژه'; ?></button>
    </form>
</div>