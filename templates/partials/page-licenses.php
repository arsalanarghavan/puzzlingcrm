<?php
/**
 * Licenses Management Page
 * @package PuzzlingCRM
 */

if (!defined('ABSPATH')) exit;
if (!current_user_can('manage_options')) {
    wp_die(__('شما اجازه دسترسی به این صفحه را ندارید.', 'puzzlingcrm'));
}

$licenses = PuzzlingCRM_License_Manager::get_all_licenses();
?>

<div class="wrap">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="wp-heading-inline">
            <i class="ri-key-line me-2"></i>مدیریت لایسنس‌ها
        </h1>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addLicenseModal">
            <i class="ri-add-line me-1"></i>افزودن لایسنس جدید
        </button>
    </div>

    <?php if (empty($licenses)): ?>
        <div class="card custom-card">
            <div class="card-body text-center py-5">
                <i class="ri-key-line fs-1 text-muted mb-3 d-block"></i>
                <p class="text-muted mb-0">هنوز لایسنس‌ای ثبت نشده است.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="row" id="licenses-grid">
            <?php foreach ($licenses as $license): 
                $card_color_class = 'license-card-' . $license['card_color'];
                $remaining_percentage = $license['remaining_percentage'];
                $remaining_days = $license['remaining_days'];
                $expiry_date = $license['expiry_date'] ? date_i18n('Y/m/d', strtotime($license['expiry_date'])) : 'نامحدود';
            ?>
                <div class="col-md-4 col-lg-3 mb-4">
                    <div class="card custom-card license-card <?php echo esc_attr($card_color_class); ?>" data-license-id="<?php echo esc_attr($license['id']); ?>">
                        <div class="card-body">
                            <?php if (!empty($license['logo_url'])): ?>
                                <div class="text-center mb-3">
                                    <img src="<?php echo esc_url($license['logo_url']); ?>" alt="<?php echo esc_attr($license['project_name']); ?>" class="license-logo" style="max-width: 80px; max-height: 80px; object-fit: contain;">
                                </div>
                            <?php else: ?>
                                <div class="text-center mb-3">
                                    <div class="license-logo-placeholder">
                                        <i class="ri-building-line fs-1"></i>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <h5 class="card-title fw-medium mb-2"><?php echo esc_html($license['project_name']); ?></h5>
                            <p class="text-muted small mb-2">
                                <i class="ri-global-line me-1"></i><?php echo esc_html($license['domain']); ?>
                            </p>
                            
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="small text-muted">باقی‌مانده:</span>
                                    <span class="fw-semibold"><?php echo esc_html(number_format($remaining_percentage, 1)); ?>%</span>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-<?php echo esc_attr($license['card_color']); ?>" 
                                         role="progressbar" 
                                         style="width: <?php echo esc_attr($remaining_percentage); ?>%"
                                         aria-valuenow="<?php echo esc_attr($remaining_percentage); ?>" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100"></div>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="small text-muted">تاریخ انقضا:</span>
                                <span class="small fw-medium"><?php echo esc_html($expiry_date); ?></span>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="small text-muted">روزهای باقی‌مانده:</span>
                                <?php if (empty($license['expiry_date'])): ?>
                                    <span class="badge bg-black" style="background-color: #000000 !important; color: #ffd700; border: 1px solid #ffd700;">VIP</span>
                                <?php else: ?>
                                    <span class="badge bg-<?php echo esc_attr($license['card_color']); ?>"><?php echo esc_html($remaining_days); ?> روز</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="badge bg-<?php echo esc_attr($license['status'] === 'active' ? 'success' : ($license['status'] === 'expired' ? 'danger' : 'secondary')); ?>">
                                    <?php 
                                    $status_labels = [
                                        'active' => 'فعال',
                                        'inactive' => 'غیرفعال',
                                        'expired' => 'منقضی شده',
                                        'cancelled' => 'لغو شده'
                                    ];
                                    echo esc_html($status_labels[$license['status']] ?? $license['status']);
                                    ?>
                                </span>
                                
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-link text-muted p-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="ri-more-2-fill fs-5"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <a class="dropdown-item renew-license-btn" href="javascript:void(0);" data-license-id="<?php echo esc_attr($license['id']); ?>">
                                                <i class="ri-refresh-line me-2"></i>تمدید
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item cancel-license-btn" href="javascript:void(0);" data-license-id="<?php echo esc_attr($license['id']); ?>">
                                                <i class="ri-close-circle-line me-2"></i>لغو لایسنس
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <a class="dropdown-item text-danger delete-license-btn" href="javascript:void(0);" data-license-id="<?php echo esc_attr($license['id']); ?>">
                                                <i class="ri-delete-bin-line me-2"></i>حذف
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Add License Modal -->
<div class="modal fade" id="addLicenseModal" tabindex="-1" aria-labelledby="addLicenseModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addLicenseModalLabel">افزودن لایسنس جدید</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="add-license-form">
                <div class="modal-body">
                    <?php wp_nonce_field('puzzlingcrm-ajax-nonce', 'nonce'); ?>
                    <div class="mb-3">
                        <label for="project_name" class="form-label">نام مجموعه <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="project_name" name="project_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="domain" class="form-label">دامنه <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="domain" name="domain" placeholder="example.com" required>
                    </div>
                    <div class="mb-3">
                        <div class="alert alert-info">
                            <i class="ri-information-line me-2"></i>
                            <strong>کلید لایسنس به صورت خودکار تولید می‌شود.</strong>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="start_date" class="form-label">تاریخ شروع</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo esc_attr(date('Y-m-d')); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="expiry_date" class="form-label">تاریخ انقضا</label>
                            <input type="date" class="form-control" id="expiry_date" name="expiry_date">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="logo_url" class="form-label">آدرس لوگو</label>
                        <input type="url" class="form-control" id="logo_url" name="logo_url" placeholder="https://example.com/logo.png">
                    </div>
                    <div class="mb-3">
                        <label for="status" class="form-label">وضعیت</label>
                        <select class="form-select" id="status" name="status">
                            <option value="active">فعال</option>
                            <option value="inactive">غیرفعال</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                    <button type="submit" class="btn btn-primary">افزودن</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Renew License Modal -->
<div class="modal fade" id="renewLicenseModal" tabindex="-1" aria-labelledby="renewLicenseModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="renewLicenseModalLabel">تمدید لایسنس</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="renew-license-form">
                <div class="modal-body">
                    <?php wp_nonce_field('puzzlingcrm-ajax-nonce', 'nonce'); ?>
                    <input type="hidden" id="renew_license_id" name="id">
                    <div class="mb-3">
                        <label for="renew_expiry_date" class="form-label">تاریخ انقضای جدید <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="renew_expiry_date" name="expiry_date" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                    <button type="submit" class="btn btn-primary">تمدید</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function() {
    'use strict';
    
    try {
    // Wait for both jQuery and DOM
    function initLicenseForm() {
        // Check for jQuery with multiple fallbacks
        var $ = null;
        if (typeof jQuery !== 'undefined') {
            $ = jQuery;
        } else if (typeof window.jQuery !== 'undefined') {
            $ = window.jQuery;
        } else if (typeof window.$ !== 'undefined') {
            $ = window.$;
        }
        
        if (!$) {
            console.error('jQuery is not loaded! Retrying...');
            // Only retry a limited number of times
            if (typeof initLicenseForm.retryCount === 'undefined') {
                initLicenseForm.retryCount = 0;
            }
            initLicenseForm.retryCount++;
            if (initLicenseForm.retryCount < 10) {
                setTimeout(initLicenseForm, 500);
            } else {
                console.error('jQuery failed to load after 10 attempts. Please refresh the page.');
                alert('خطا در بارگذاری jQuery. لطفاً صفحه را refresh کنید.');
            }
            return;
        }
            
            $(document).ready(function() {
                try {
                    // Check if puzzlingcrm_ajax_obj is defined
                    if (typeof puzzlingcrm_ajax_obj === 'undefined') {
                        console.error('puzzlingcrm_ajax_obj is not defined!');
                        alert('خطا: تنظیمات AJAX یافت نشد. لطفاً صفحه را refresh کنید.');
                        return;
                    }
                    
                    console.log('License form handler initializing...');
                    
                    // Wait for modal to be added to DOM, then bind event
                    function bindFormEvents() {
                        var $form = $('#add-license-form');
                        if ($form.length === 0) {
                            console.log('Form not found, retrying...');
                            setTimeout(bindFormEvents, 200);
                            return;
                        }
                        
                        console.log('Form found, binding events...');
                        
                        // Remove any existing handlers first
                        $form.off('submit.licenseForm');
                        
                        // Also bind to button click as fallback
                        $form.find('button[type="submit"]').off('click.licenseForm');
                        
                        // Add license form - direct binding with namespace
                        $form.on('submit.licenseForm', function(e) {
                            console.log('Form submit event triggered!');
                            e.preventDefault();
                            e.stopPropagation();
                            e.stopImmediatePropagation();
                            
                            var $formEl = $(this);
                            var $btn = $formEl.find('button[type="submit"]');
                            if ($btn.length === 0) {
                                console.error('Submit button not found!');
                                return false;
                            }
                            var originalText = $btn.html();
                            
                            // Validate required fields
                            var projectName = $('#project_name').val().trim();
                            var domain = $('#domain').val().trim();
                            
                            if (!projectName || !domain) {
                                alert('لطفاً تمام فیلدهای الزامی را پر کنید.');
                                return false;
                            }
                            
                            $btn.prop('disabled', true).html('<i class="ri-loader-4-line ri-spin me-1"></i>در حال ارسال...');
                            
                            var formData = {
                                action: 'puzzlingcrm_add_license',
                                nonce: puzzlingcrm_ajax_obj.nonce,
                                project_name: projectName,
                                domain: domain,
                                start_date: $('#start_date').val() || '',
                                expiry_date: $('#expiry_date').val() || '',
                                logo_url: $('#logo_url').val() || '',
                                status: $('#status').val() || 'active'
                            };
                            
                            console.log('Sending AJAX request');
                            console.log('AJAX URL:', puzzlingcrm_ajax_obj.ajax_url);
                            console.log('AJAX Nonce:', puzzlingcrm_ajax_obj.nonce);
                            
                            $.ajax({
                                url: puzzlingcrm_ajax_obj.ajax_url,
                                type: 'POST',
                                data: formData,
                                dataType: 'json',
                                timeout: 30000,
                                beforeSend: function() {
                                    console.log('Sending AJAX request...');
                                },
                                success: function(response) {
                                    console.log('AJAX Success Response:', response);
                                    $btn.prop('disabled', false).html(originalText);
                                    
                                    if (response && response.success) {
                                        // Close modal using Bootstrap 5 method
                                        var modalElement = document.getElementById('addLicenseModal');
                                        if (modalElement) {
                                            var modal = bootstrap.Modal.getInstance(modalElement);
                                            if (modal) {
                                                modal.hide();
                                            } else {
                                                $('#addLicenseModal').modal('hide');
                                            }
                                        }
                                        $formEl[0].reset();
                                        setTimeout(function() {
                                            location.reload();
                                        }, 500);
                                    } else {
                                        var errorMsg = (response && response.data && response.data.message) ? response.data.message : 'خطا در افزودن لایسنس';
                                        alert(errorMsg);
                                        console.error('License add error:', response);
                                    }
                                },
                                error: function(xhr, status, error) {
                                    console.error('AJAX Error Details:');
                                    console.error('- Status:', status);
                                    console.error('- Error:', error);
                                    console.error('- Response Code:', xhr.status);
                                    console.error('- Response Text:', xhr.responseText);
                                    console.error('- Ready State:', xhr.readyState);
                                    
                                    $btn.prop('disabled', false).html(originalText);
                                    
                                    var errorMsg = 'خطا در ارتباط با سرور';
                                    try {
                                        if (xhr.responseText) {
                                            var response = JSON.parse(xhr.responseText);
                                            if (response && response.data && response.data.message) {
                                                errorMsg = response.data.message;
                                            }
                                        }
                                    } catch(e) {
                                        if (xhr.status === 0) {
                                            errorMsg = 'خطا در اتصال به سرور. لطفاً اتصال اینترنت خود را بررسی کنید.';
                                        } else if (xhr.status === 403) {
                                            errorMsg = 'دسترسی غیرمجاز. لطفاً صفحه را refresh کنید.';
                                        } else if (xhr.status === 500) {
                                            errorMsg = 'خطای سرور. لطفاً با مدیر سیستم تماس بگیرید.';
                                        } else if (status === 'timeout') {
                                            errorMsg = 'زمان اتصال به سرور به پایان رسید. لطفاً دوباره تلاش کنید.';
                                        } else {
                                            errorMsg = error || 'خطای نامشخص (کد: ' + xhr.status + ')';
                                        }
                                    }
                                    alert(errorMsg);
                                }
                            });
                            
                            return false;
                        });
                        
                        // Also bind to button click as fallback
                        $form.find('button[type="submit"]').on('click.licenseForm', function(e) {
                            console.log('Submit button clicked!');
                            var $btn = $(this);
                            var $formEl = $btn.closest('form');
                            
                            // Trigger form submit if not already triggered
                            if (!$formEl.data('submitting')) {
                                $formEl.data('submitting', true);
                                $formEl.trigger('submit.licenseForm');
                                setTimeout(function() {
                                    $formEl.removeData('submitting');
                                }, 1000);
                            }
                        });
                    }
                    
                    // Bind events immediately and also when modal is shown
                    bindFormEvents();
                    $(document).on('shown.bs.modal', '#addLicenseModal', function() {
                        console.log('Modal shown, rebinding events...');
                        setTimeout(bindFormEvents, 100);
                    });
                
                    // Renew license
                    $(document).off('click', '.renew-license-btn').on('click', '.renew-license-btn', function() {
                        var licenseId = $(this).data('license-id');
                        $('#renew_license_id').val(licenseId);
                        $('#renewLicenseModal').modal('show');
                    });
                    
                    $(document).off('submit', '#renew-license-form').on('submit', '#renew-license-form', function(e) {
                        e.preventDefault();
                        
                        var formData = {
                            action: 'puzzlingcrm_renew_license',
                            nonce: puzzlingcrm_ajax_obj.nonce,
                            id: $('#renew_license_id').val(),
                            expiry_date: $('#renew_expiry_date').val()
                        };
                        
                        $.ajax({
                            url: puzzlingcrm_ajax_obj.ajax_url,
                            type: 'POST',
                            data: formData,
                            success: function(response) {
                                if (response.success) {
                                    location.reload();
                                } else {
                                    alert(response.data.message || 'خطا در تمدید لایسنس');
                                }
                            },
                            error: function() {
                                alert('خطا در ارتباط با سرور');
                            }
                        });
                    });
                    
                    // Cancel license
                    $(document).off('click', '.cancel-license-btn').on('click', '.cancel-license-btn', function() {
                        if (!confirm('آیا از لغو این لایسنس مطمئن هستید؟')) {
                            return;
                        }
                        
                        var licenseId = $(this).data('license-id');
                        
                        $.ajax({
                            url: puzzlingcrm_ajax_obj.ajax_url,
                            type: 'POST',
                            data: {
                                action: 'puzzlingcrm_cancel_license',
                                nonce: puzzlingcrm_ajax_obj.nonce,
                                id: licenseId
                            },
                            success: function(response) {
                                if (response.success) {
                                    location.reload();
                                } else {
                                    alert(response.data.message || 'خطا در لغو لایسنس');
                                }
                            },
                            error: function() {
                                alert('خطا در ارتباط با سرور');
                            }
                        });
                    });
                    
                    // Delete license
                    $(document).off('click', '.delete-license-btn').on('click', '.delete-license-btn', function() {
                        if (!confirm('آیا از حذف این لایسنس مطمئن هستید؟ این عمل قابل بازگشت نیست.')) {
                            return;
                        }
                        
                        var licenseId = $(this).data('license-id');
                        
                        $.ajax({
                            url: puzzlingcrm_ajax_obj.ajax_url,
                            type: 'POST',
                            data: {
                                action: 'puzzlingcrm_delete_license',
                                nonce: puzzlingcrm_ajax_obj.nonce,
                                id: licenseId
                            },
                            success: function(response) {
                                if (response.success) {
                                    location.reload();
                                } else {
                                    alert(response.data.message || 'خطا در حذف لایسنس');
                                }
                            },
                            error: function() {
                                alert('خطا در ارتباط با سرور');
                            }
                        });
                    });
        } catch(err) {
            console.error('Error in license handlers:', err);
        }
        }); // Close $(document).ready
    }
    
    // Start initialization
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initLicenseForm);
    } else {
        initLicenseForm();
    }
    } catch(err) {
        console.error('Error initializing license form:', err);
    }
})();
</script>

