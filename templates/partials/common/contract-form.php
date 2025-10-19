<?php
/**
 * Contract Form Template (Xintra Style)
 * @package PuzzlingCRM
 */

if (!defined('ABSPATH')) exit;
if (!current_user_can('manage_options')) return;

$contract_to_edit = isset($puzzling_contract) ? $puzzling_contract : null;
$is_cancelled = $contract_to_edit ? get_post_meta($contract_to_edit->ID, '_contract_status', true) === 'cancelled' : false;

$start_date_jalali_value = $contract_to_edit ? esc_attr(puzzling_gregorian_to_jalali(get_post_meta($contract_to_edit->ID, '_project_start_date', true))) : '';
$total_amount_value = $contract_to_edit ? esc_attr(get_post_meta($contract_to_edit->ID, '_total_amount', true)) : '';
$total_installments_value = $contract_to_edit ? esc_attr(get_post_meta($contract_to_edit->ID, '_total_installments', true)) : '1';
?>

<!-- Header with Actions -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">
        <i class="ri-file-text-line me-2"></i>
        <?php echo $contract_to_edit ? 'ویرایش قرارداد' : 'ایجاد قرارداد جدید'; ?>
    </h4>
    <?php if ($contract_to_edit): ?>
    <div class="btn-group">
        <?php if (!$is_cancelled): ?>
        <button type="button" id="cancel-contract-btn" class="btn btn-warning btn-sm">
            <i class="ri-close-circle-line me-1"></i>لغو قرارداد
        </button>
        <?php endif; ?>
        <button type="button" id="delete-contract-btn" class="btn btn-danger btn-sm"
                data-contract-id="<?php echo esc_attr($contract_to_edit->ID); ?>"
                data-nonce="<?php echo wp_create_nonce('puzzling_delete_contract_' . $contract_to_edit->ID); ?>">
            <i class="ri-delete-bin-line me-1"></i>حذف دائمی
        </button>
    </div>
    <?php endif; ?>
</div>

<!-- Cancellation Alert -->
<?php if ($is_cancelled):
    $cancellation_reason = get_post_meta($contract_to_edit->ID, '_cancellation_reason', true);
    $cancellation_date = get_post_meta($contract_to_edit->ID, '_cancellation_date', true);
?>
<div class="alert alert-danger d-flex align-items-start" role="alert">
    <i class="ri-error-warning-line fs-5 me-3 mt-1"></i>
    <div>
        <h5 class="alert-heading">این قرارداد لغو شده است</h5>
        <p class="mb-1"><strong>تاریخ لغو:</strong> <?php echo jdate('Y/m/d', strtotime($cancellation_date)); ?></p>
        <p class="mb-0"><strong>دلیل:</strong> <?php echo esc_html($cancellation_reason); ?></p>
    </div>
</div>
<?php endif; ?>

<!-- Main Form -->
<form id="manage-contract-form" class="pzl-ajax-form" data-action="puzzling_manage_contract" method="post" enctype="multipart/form-data" <?php if ($is_cancelled) echo 'style="opacity: 0.6; pointer-events: none;"'; ?>>
    <?php wp_nonce_field('puzzlingcrm-ajax-nonce', 'security'); ?>
    <input type="hidden" name="contract_id" value="<?php echo $contract_to_edit ? esc_attr($contract_to_edit->ID) : '0'; ?>">

    <!-- Basic Information Card -->
    <div class="card custom-card mb-4">
        <div class="card-header">
            <div class="card-title">
                <i class="ri-information-line me-2"></i>اطلاعات پایه
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="contract_number" class="form-label">شماره قرارداد</label>
                    <input type="text" id="contract_number" name="contract_number" class="form-control ltr-input" 
                           value="<?php echo $contract_to_edit ? esc_attr(get_post_meta($contract_to_edit->ID, '_contract_number', true)) : 'پس از انتخاب مشتری تولید می‌شود'; ?>" 
                           readonly>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="_project_start_date" class="form-label">تاریخ شروع قرارداد <span class="text-danger">*</span></label>
                    <input type="text" id="_project_start_date" name="_project_start_date" class="form-control pzl-jalali-date-picker" 
                           value="<?php echo $start_date_jalali_value; ?>" required>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="customer_id" class="form-label">مشتری <span class="text-danger">*</span></label>
                    <select name="customer_id" id="customer_id" class="form-select" required <?php echo $contract_to_edit ? 'disabled' : ''; ?>>
                        <option value="">-- انتخاب مشتری --</option>
                        <?php
                        $customers = get_users(['role__in' => ['customer', 'subscriber'], 'orderby' => 'display_name']);
                        $selected_customer = $contract_to_edit ? $contract_to_edit->post_author : 0;
                        foreach ($customers as $customer) {
                            echo '<option value="' . esc_attr($customer->ID) . '" ' . selected($selected_customer, $customer->ID, false) . '>' . esc_html($customer->display_name) . '</option>';
                        }
                        ?>
                    </select>
                    <?php if ($contract_to_edit): ?>
                    <input type="hidden" name="customer_id" value="<?php echo esc_attr($selected_customer); ?>">
                    <?php endif; ?>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="_project_contract_duration" class="form-label">مدت قرارداد</label>
                    <select name="_project_contract_duration" id="_project_contract_duration" class="form-select">
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

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="contract_title" class="form-label">عنوان قرارداد (اختیاری)</label>
                    <input type="text" id="contract_title" name="contract_title" class="form-control" 
                           value="<?php echo $contract_to_edit ? esc_attr($contract_to_edit->post_title) : ''; ?>" 
                           placeholder="مثال: قرارداد پشتیبانی سالانه">
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="_project_subscription_model" class="form-label">مدل اشتراک</label>
                    <select name="_project_subscription_model" id="_project_subscription_model" class="form-select">
                        <?php
                        $models = ['یکبار پرداخت' => 'onetime', 'اشتراکی' => 'subscription'];
                        $current_model = $contract_to_edit ? get_post_meta($contract_to_edit->ID, '_project_subscription_model', true) : 'onetime';
                        foreach ($models as $label => $value) {
                            echo '<option value="' . esc_attr($value) . '" ' . selected($current_model, $value, false) . '>' . esc_html($label) . '</option>';
                        }
                        ?>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <input type="hidden" id="_project_end_date" name="_project_end_date" value="<?php echo $contract_to_edit ? esc_attr(get_post_meta($contract_to_edit->ID, '_project_end_date', true)) : ''; ?>">

    <!-- Add Services from Product (Only for Edit) -->
    <?php if ($contract_to_edit): ?>
    <div class="card custom-card mb-4">
        <div class="card-header">
            <div class="card-title">
                <i class="ri-magic-line me-2"></i>افزودن خدمات از محصول
            </div>
        </div>
        <div class="card-body">
            <p class="text-muted mb-3">
                <i class="ri-information-line me-2"></i>
                یک محصول اشتراکی یا گروهی را انتخاب کنید تا پروژه‌های مرتبط با خدمات آن به صورت خودکار به این قرارداد اضافه شوند.
            </p>
            <div class="row g-3">
                <div class="col-md-8">
                    <label for="product_id_for_automation" class="form-label">انتخاب محصول</label>
                    <select id="product_id_for_automation" class="form-select">
                        <option value="">-- انتخاب کنید --</option>
                        <?php
                        if (function_exists('wc_get_products')) {
                            $products = wc_get_products(['type' => ['subscription', 'grouped', 'bundle'], 'limit' => -1, 'status' => 'publish']);
                            foreach ($products as $product) {
                                echo '<option value="' . esc_attr($product->get_id()) . '">' . esc_html($product->get_name()) . '</option>';
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">&nbsp;</label>
                    <button type="button" id="add-services-from-product" class="btn btn-primary w-100">
                        <i class="ri-add-circle-line me-1"></i>افزودن خدمات محصول
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Payment Calculator Card -->
    <div class="card custom-card mb-4">
        <div class="card-header">
            <div class="card-title">
                <i class="ri-calculator-line me-2"></i>محاسبه‌گر و لیست اقساط
            </div>
        </div>
        <div class="card-body">
            <!-- Calculator -->
            <div class="alert alert-primary-transparent">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label for="total_amount" class="form-label fw-semibold">مبلغ کل (تومان)</label>
                        <input type="text" id="total_amount" name="total_amount" class="form-control item-price" 
                               value="<?php echo esc_attr($total_amount_value); ?>" placeholder="30,000,000">
                    </div>
                    <div class="col-md-2">
                        <label for="total_installments" class="form-label fw-semibold">تعداد اقساط</label>
                        <input type="number" id="total_installments" name="total_installments" class="form-control" 
                               placeholder="6" value="<?php echo esc_attr($total_installments_value); ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="installment_interval" class="form-label fw-semibold">فاصله (روز)</label>
                        <input type="number" id="installment_interval" class="form-control" placeholder="30" value="30">
                    </div>
                    <div class="col-md-3">
                        <label for="start_date" class="form-label fw-semibold">تاریخ اولین قسط</label>
                        <input type="text" id="start_date" class="form-control pzl-jalali-date-picker" value="<?php echo $start_date_jalali_value; ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="button" id="calculate-installments" class="btn btn-success w-100">
                            <i class="ri-calculator-line me-1"></i>محاسبه
                        </button>
                    </div>
                </div>
            </div>

            <!-- Payment Rows -->
            <div id="payment-rows-container" class="mt-4">
                <?php if ($contract_to_edit): 
                    $installments = get_post_meta($contract_to_edit->ID, '_installments', true);
                    if (!empty($installments) && is_array($installments)): 
                        foreach ($installments as $index => $inst):
                ?>
                <div class="payment-row d-flex gap-2 align-items-center mb-2">
                    <input type="text" name="payment_amount[]" class="form-control item-price" placeholder="مبلغ (تومان)" value="<?php echo esc_attr($inst['amount']); ?>" required style="flex: 2;">
                    <input type="text" name="payment_due_date[]" class="form-control pzl-jalali-date-picker" value="<?php echo esc_attr(puzzling_gregorian_to_jalali($inst['due_date'])); ?>" required style="flex: 2;">
                    <select name="payment_status[]" class="form-select" style="flex: 1;">
                        <option value="pending" <?php selected($inst['status'] ?? 'pending', 'pending'); ?>>در انتظار</option>
                        <option value="paid" <?php selected($inst['status'] ?? 'pending', 'paid'); ?>>پرداخت شده</option>
                        <option value="cancelled" <?php selected($inst['status'] ?? 'pending', 'cancelled'); ?>>لغو شده</option>
                    </select>
                    <button type="button" class="btn btn-danger-light btn-sm btn-icon remove-payment-row">
                        <i class="ri-delete-bin-line"></i>
                    </button>
                </div>
                <?php 
                        endforeach;
                    endif;
                endif; 
                ?>
            </div>

            <!-- Add Payment Row Button -->
            <button type="button" id="add-payment-row" class="btn btn-secondary-light btn-sm mt-3">
                <i class="ri-add-line me-1"></i>افزودن قسط دستی
            </button>
        </div>
    </div>

    <!-- Submit Button -->
    <div class="text-center">
        <button type="submit" name="submit_contract" class="btn btn-primary btn-lg">
            <i class="ri-save-line me-2"></i>
            <?php echo $contract_to_edit ? 'ذخیره تغییرات قرارداد' : 'ایجاد قرارداد'; ?>
        </button>
    </div>
</form>
