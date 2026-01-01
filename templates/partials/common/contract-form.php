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
        <button type="button" class="btn btn-success btn-sm generate-contract-pdf" 
                data-contract-id="<?php echo esc_attr($contract_to_edit->ID); ?>">
            <i class="ri-file-pdf-line me-1"></i>دریافت PDF
        </button>
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
                    <label for="contract_number" class="form-label">
                        <i class="ri-hashtag me-1"></i>شماره قرارداد
                    </label>
                    <input type="text" id="contract_number" name="contract_number" class="form-control ltr-input" 
                           value="<?php echo $contract_to_edit ? esc_attr(get_post_meta($contract_to_edit->ID, '_contract_number', true)) : 'پس از انتخاب مشتری تولید می‌شود'; ?>" 
                           readonly>
                    <small class="form-text text-muted">
                        <i class="ri-information-line me-1"></i>شماره قرارداد به صورت خودکار پس از انتخاب مشتری و تاریخ تولید می‌شود.
                    </small>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="_project_start_date" class="form-label">
                        <i class="ri-calendar-line me-1"></i>تاریخ شروع قرارداد <span class="text-danger">*</span>
                    </label>
                    <input type="text" id="_project_start_date" name="_project_start_date" class="form-control pzl-jalali-date-picker" 
                           value="<?php echo $start_date_jalali_value; ?>" required
                           placeholder="1404/01/01">
                    <small class="form-text text-muted">
                        <i class="ri-information-line me-1"></i>فرمت تاریخ: YYYY/MM/DD (مثال: 1404/01/01)
                    </small>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="customer_id" class="form-label">
                        <i class="ri-user-line me-1"></i>مشتری <span class="text-danger">*</span>
                    </label>
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
                    <small class="form-text text-muted">
                        <i class="ri-lock-line me-1"></i>مشتری در حالت ویرایش قابل تغییر نیست.
                    </small>
                    <?php else: ?>
                    <small class="form-text text-muted">
                        <i class="ri-information-line me-1"></i>مشتری قرارداد را انتخاب کنید.
                    </small>
                    <?php endif; ?>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="_project_contract_duration" class="form-label">
                        <i class="ri-time-line me-1"></i>مدت قرارداد
                    </label>
                    <select name="_project_contract_duration" id="_project_contract_duration" class="form-select">
                        <?php
                        $durations = ['یک ماهه' => '1-month', 'سه ماهه' => '3-months', 'شش ماهه' => '6-months', 'یک ساله' => '12-months'];
                        $current_duration = $contract_to_edit ? get_post_meta($contract_to_edit->ID, '_project_contract_duration', true) : '1-month';
                        foreach ($durations as $label => $value) {
                            echo '<option value="' . esc_attr($value) . '" ' . selected($current_duration, $value, false) . '>' . esc_html($label) . '</option>';
                        }
                        ?>
                    </select>
                    <small class="form-text text-muted">
                        <i class="ri-information-line me-1"></i>مدت زمان قرارداد برای محاسبه تاریخ پایان استفاده می‌شود.
                    </small>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="contract_title" class="form-label">
                        <i class="ri-file-text-line me-1"></i>عنوان قرارداد (اختیاری)
                    </label>
                    <input type="text" id="contract_title" name="contract_title" class="form-control" 
                           value="<?php echo $contract_to_edit ? esc_attr($contract_to_edit->post_title) : ''; ?>" 
                           placeholder="مثال: قرارداد پشتیبانی سالانه">
                    <small class="form-text text-muted">
                        <i class="ri-information-line me-1"></i>در صورت خالی بودن، عنوان به صورت خودکار تولید می‌شود.
                    </small>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="_project_subscription_model" class="form-label">
                        <i class="ri-repeat-line me-1"></i>مدل اشتراک
                    </label>
                    <select name="_project_subscription_model" id="_project_subscription_model" class="form-select">
                        <?php
                        $models = ['یکبار پرداخت' => 'onetime', 'اشتراکی' => 'subscription'];
                        $current_model = $contract_to_edit ? get_post_meta($contract_to_edit->ID, '_project_subscription_model', true) : 'onetime';
                        foreach ($models as $label => $value) {
                            echo '<option value="' . esc_attr($value) . '" ' . selected($current_model, $value, false) . '>' . esc_html($label) . '</option>';
                        }
                        ?>
                    </select>
                    <small class="form-text text-muted">
                        <i class="ri-information-line me-1"></i>نوع پرداخت قرارداد را مشخص کنید.
                    </small>
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
            <!-- Help Text -->
            <div class="alert alert-info d-flex align-items-start mb-4">
                <i class="ri-information-line fs-5 me-3 mt-1"></i>
                <div>
                    <strong>راهنمای استفاده از محاسبه‌گر اقساط:</strong>
                    <ul class="mb-0 mt-2">
                        <li>مبلغ کل قرارداد و تعداد اقساط را وارد کنید</li>
                        <li>فاصله بین اقساط را به روز مشخص کنید (مثال: 30 برای ماهانه)</li>
                        <li>تاریخ اولین قسط را انتخاب کنید</li>
                        <li>روی دکمه "محاسبه" کلیک کنید تا اقساط به صورت خودکار تولید شوند</li>
                        <li>می‌توانید اقساط تولید شده را ویرایش یا حذف کنید</li>
                        <li>همچنین می‌توانید اقساط را به صورت دستی اضافه کنید</li>
                    </ul>
                </div>
            </div>
            
            <!-- Calculator -->
            <div class="alert alert-primary-transparent">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label for="total_amount" class="form-label fw-semibold">
                            <i class="ri-money-dollar-circle-line me-1"></i>مبلغ کل (تومان)
                        </label>
                        <input type="text" id="total_amount" name="total_amount" class="form-control item-price" 
                               value="<?php echo esc_attr($total_amount_value); ?>" placeholder="30,000,000">
                    </div>
                    <div class="col-md-2">
                        <label for="total_installments" class="form-label fw-semibold">
                            <i class="ri-numbers-line me-1"></i>تعداد اقساط
                        </label>
                        <input type="number" id="total_installments" name="total_installments" class="form-control" 
                               placeholder="6" value="<?php echo esc_attr($total_installments_value); ?>" min="1">
                    </div>
                    <div class="col-md-2">
                        <label for="installment_interval" class="form-label fw-semibold">
                            <i class="ri-calendar-check-line me-1"></i>فاصله (روز)
                        </label>
                        <input type="number" id="installment_interval" class="form-control" placeholder="30" value="30" min="1">
                    </div>
                    <div class="col-md-3">
                        <label for="start_date" class="form-label fw-semibold">
                            <i class="ri-calendar-line me-1"></i>تاریخ اولین قسط
                        </label>
                        <input type="text" id="start_date" class="form-control pzl-jalali-date-picker" 
                               value="<?php echo $start_date_jalali_value; ?>" placeholder="1404/01/01">
                    </div>
                    <div class="col-md-2">
                        <button type="button" id="calculate-installments" class="btn btn-success w-100">
                            <i class="ri-calculator-line me-1"></i>محاسبه
                        </button>
                    </div>
                </div>
            </div>

            <!-- Payment Rows Header -->
            <div class="d-flex justify-content-between align-items-center mt-4 mb-3">
                <h5 class="mb-0">
                    <i class="ri-list-check me-2"></i>لیست اقساط
                </h5>
                <button type="button" id="add-payment-row" class="btn btn-secondary-light btn-sm">
                    <i class="ri-add-line me-1"></i>افزودن قسط دستی
                </button>
            </div>
            
            <!-- Payment Rows -->
            <div id="payment-rows-container" class="mt-3">
                <?php if ($contract_to_edit): 
                    $installments = get_post_meta($contract_to_edit->ID, '_installments', true);
                    if (!empty($installments) && is_array($installments)): 
                        foreach ($installments as $index => $inst):
                ?>
                <div class="payment-row d-flex gap-2 align-items-center mb-2">
                    <div class="flex-shrink-0" style="width: 30px; text-align: center; color: #6c757d;">
                        <strong>#<?php echo $index + 1; ?></strong>
                    </div>
                    <input type="text" name="payment_amount[]" class="form-control item-price" 
                           placeholder="مبلغ (تومان)" value="<?php echo esc_attr($inst['amount']); ?>" required style="flex: 2;">
                    <input type="text" name="payment_due_date[]" class="form-control pzl-jalali-date-picker" 
                           value="<?php echo esc_attr(puzzling_gregorian_to_jalali($inst['due_date'])); ?>" 
                           required style="flex: 2;" placeholder="1404/01/01">
                    <select name="payment_status[]" class="form-select" style="flex: 1;">
                        <option value="pending" <?php selected($inst['status'] ?? 'pending', 'pending'); ?>>در انتظار</option>
                        <option value="paid" <?php selected($inst['status'] ?? 'pending', 'paid'); ?>>پرداخت شده</option>
                        <option value="cancelled" <?php selected($inst['status'] ?? 'pending', 'cancelled'); ?>>لغو شده</option>
                    </select>
                    <button type="button" class="btn btn-danger-light btn-sm btn-icon remove-payment-row" title="حذف قسط">
                        <i class="ri-delete-bin-line"></i>
                    </button>
                </div>
                <?php 
                        endforeach;
                    else:
                ?>
                <div class="alert alert-warning">
                    <i class="ri-information-line me-2"></i>هیچ قسطی برای این قرارداد ثبت نشده است. می‌توانید از محاسبه‌گر استفاده کنید یا به صورت دستی اضافه کنید.
                </div>
                <?php
                    endif;
                endif; 
                ?>
            </div>
            
            <?php if (!$contract_to_edit): ?>
            <div class="alert alert-warning mt-3">
                <i class="ri-information-line me-2"></i>هیچ قسطی ثبت نشده است. لطفاً از محاسبه‌گر استفاده کنید یا به صورت دستی اقساط را اضافه کنید.
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Submit Button -->
    <div class="text-center mt-4 mb-4">
        <button type="submit" name="submit_contract" class="btn btn-primary btn-lg px-5">
            <i class="ri-save-line me-2"></i>
            <?php echo $contract_to_edit ? 'ذخیره تغییرات قرارداد' : 'ایجاد قرارداد'; ?>
        </button>
        <div class="mt-3">
            <small class="text-muted">
                <i class="ri-information-line me-1"></i>
                قبل از ثبت، مطمئن شوید تمام اطلاعات به درستی وارد شده‌اند.
            </small>
        </div>
    </div>
</form>
