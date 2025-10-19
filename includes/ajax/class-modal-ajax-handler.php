<?php
/**
 * Modal AJAX Handler - Project, Customer, Contract Details
 * @package PuzzlingCRM
 */

if (!defined('ABSPATH')) exit;

class PuzzlingCRM_Modal_Ajax_Handler {

    public function __construct() {
        add_action('wp_ajax_puzzling_get_project_details', [$this, 'get_project_details']);
        add_action('wp_ajax_puzzling_get_customer_360', [$this, 'get_customer_360']);
        add_action('wp_ajax_puzzling_get_contract_details', [$this, 'get_contract_details']);
    }

    /**
     * Get Project Details for Modal
     */
    public function get_project_details() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        
        $project_id = isset($_POST['project_id']) ? intval($_POST['project_id']) : 0;
        
        if (!$project_id) {
            wp_send_json_error(['message' => 'شناسه پروژه نامعتبر است.']);
        }

        $project = get_post($project_id);
        if (!$project) {
            wp_send_json_error(['message' => 'پروژه یافت نشد.']);
        }

        // Get project tasks
        $tasks = get_posts([
            'post_type' => 'task',
            'posts_per_page' => -1,
            'meta_query' => [
                ['key' => '_project_id', 'value' => $project_id]
            ]
        ]);

        $total_tasks = count($tasks);
        $done_tasks = 0;
        foreach ($tasks as $task) {
            $status_terms = wp_get_post_terms($task->ID, 'task_status');
            if (!empty($status_terms) && $status_terms[0]->slug === 'done') {
                $done_tasks++;
            }
        }

        $progress = $total_tasks > 0 ? ($done_tasks / $total_tasks) * 100 : 0;

        // Get customer
        $customer_id = get_post_meta($project_id, '_customer_id', true);
        $customer = get_user_by('id', $customer_id);

        ob_start();
        ?>
        <div class="modal-header">
            <h5 class="modal-title"><?php echo esc_html($project->post_title); ?></h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <div class="row">
                <div class="col-md-8">
                    <!-- Project Info -->
                    <div class="card custom-card mb-3">
                        <div class="card-header">
                            <div class="card-title">اطلاعات پروژه</div>
                        </div>
                        <div class="card-body">
                            <?php if ($project->post_content): ?>
                                <div class="mb-3"><?php echo wp_kses_post($project->post_content); ?></div>
                            <?php endif; ?>
                            
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <small class="text-muted">مشتری:</small>
                                    <div class="fw-semibold"><?php echo $customer ? esc_html($customer->display_name) : '---'; ?></div>
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted">تاریخ شروع:</small>
                                    <div class="fw-semibold"><?php echo get_the_date('Y/m/d', $project->ID); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tasks List -->
                    <div class="card custom-card">
                        <div class="card-header">
                            <div class="card-title">وظایف (<?php echo $total_tasks; ?>)</div>
                        </div>
                        <div class="card-body">
                            <div class="progress mb-3" style="height: 25px;">
                                <div class="progress-bar bg-success" style="width: <?php echo $progress; ?>%">
                                    <?php echo number_format($progress, 0); ?>%
                                </div>
                            </div>
                            
                            <?php if (!empty($tasks)): ?>
                                <div class="list-group">
                                    <?php foreach (array_slice($tasks, 0, 5) as $task):
                                        $status_terms = wp_get_post_terms($task->ID, 'task_status');
                                        $status = !empty($status_terms) ? $status_terms[0]->name : 'نامشخص';
                                    ?>
                                    <div class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span><?php echo esc_html($task->post_title); ?></span>
                                            <span class="badge bg-primary-transparent"><?php echo $status; ?></span>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                    
                                    <?php if ($total_tasks > 5): ?>
                                    <div class="list-group-item text-center">
                                        <a href="?view=tasks&project=<?php echo $project_id; ?>">مشاهده همه</a>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted text-center">هیچ وظیفه‌ای وجود ندارد</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <!-- Quick Stats -->
                    <div class="card custom-card mb-3">
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <?php if (has_post_thumbnail($project_id)): ?>
                                    <?php echo get_the_post_thumbnail($project_id, 'medium', ['class' => 'rounded']); ?>
                                <?php else: ?>
                                    <div class="avatar avatar-xxl bg-primary-transparent">
                                        <i class="ri-folder-2-line fs-1"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <h5 class="mb-3"><?php echo esc_html($project->post_title); ?></h5>
                            
                            <div class="d-grid gap-2">
                                <a href="?view=projects&action=edit&project_id=<?php echo $project_id; ?>" class="btn btn-primary">
                                    <i class="ri-edit-line me-1"></i>ویرایش پروژه
                                </a>
                                <a href="?view=tasks&project=<?php echo $project_id; ?>" class="btn btn-success">
                                    <i class="ri-task-line me-1"></i>مشاهده وظایف
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Stats Card -->
                    <div class="card custom-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-3 pb-3 border-bottom">
                                <span class="text-muted">کل وظایف</span>
                                <span class="fw-bold"><?php echo $total_tasks; ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-3 pb-3 border-bottom">
                                <span class="text-muted">تکمیل شده</span>
                                <span class="fw-bold text-success"><?php echo $done_tasks; ?></span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">پیشرفت</span>
                                <span class="fw-bold text-primary"><?php echo number_format($progress, 0); ?>%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        
        $html = ob_get_clean();
        wp_send_json_success(['html' => $html]);
    }

    /**
     * Get Customer 360 View
     */
    public function get_customer_360() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        
        $customer_id = isset($_POST['customer_id']) ? intval($_POST['customer_id']) : 0;
        
        if (!$customer_id) {
            wp_send_json_error(['message' => 'شناسه مشتری نامعتبر است.']);
        }

        $customer = get_user_by('id', $customer_id);
        if (!$customer) {
            wp_send_json_error(['message' => 'مشتری یافت نشد.']);
        }

        // Get customer data
        $projects = get_posts(['post_type' => 'project', 'posts_per_page' => -1, 'meta_query' => [['key' => '_customer_id', 'value' => $customer_id]]]);
        $contracts = get_posts(['post_type' => 'contract', 'posts_per_page' => -1, 'meta_query' => [['key' => '_customer_id', 'value' => $customer_id]]]);
        $tickets = get_posts(['post_type' => 'ticket', 'posts_per_page' => -1, 'author' => $customer_id]);

        $total_revenue = 0;
        foreach ($contracts as $contract) {
            $total_revenue += (float) get_post_meta($contract->ID, '_total_amount', true);
        }

        ob_start();
        ?>
        <div class="modal-header bg-primary text-white">
            <h5 class="modal-title">
                <i class="ri-user-line me-2"></i><?php echo esc_html($customer->display_name); ?>
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <div class="row">
                <div class="col-md-8">
                    <!-- Tabs -->
                    <ul class="nav nav-tabs mb-3">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#customer-overview">نمای کلی</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#customer-projects">پروژه‌ها (<?php echo count($projects); ?>)</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#customer-tickets">تیکت‌ها (<?php echo count($tickets); ?>)</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#customer-contracts">قراردادها (<?php echo count($contracts); ?>)</a>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <!-- Overview -->
                        <div class="tab-pane fade show active" id="customer-overview">
                            <div class="card custom-card">
                                <div class="card-body">
                                    <h6 class="mb-3">اطلاعات تماس</h6>
                                    <div class="mb-2"><i class="ri-mail-line me-2"></i><?php echo esc_html($customer->user_email); ?></div>
                                    <div class="mb-2"><i class="ri-phone-line me-2"></i><?php echo esc_html(get_user_meta($customer_id, 'mobile_phone', true) ?: '---'); ?></div>
                                    <div class="mb-2"><i class="ri-calendar-line me-2"></i>عضو از: <?php echo date_i18n('Y/m/d', strtotime($customer->user_registered)); ?></div>
                                </div>
                            </div>
                        </div>

                        <!-- Projects -->
                        <div class="tab-pane fade" id="customer-projects">
                            <?php if (!empty($projects)): ?>
                                <div class="list-group">
                                    <?php foreach ($projects as $project): ?>
                                    <div class="list-group-item">
                                        <div class="fw-semibold"><?php echo esc_html($project->post_title); ?></div>
                                        <small class="text-muted"><?php echo get_the_date('Y/m/d', $project); ?></small>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted text-center">هیچ پروژه‌ای وجود ندارد</p>
                            <?php endif; ?>
                        </div>

                        <!-- Tickets -->
                        <div class="tab-pane fade" id="customer-tickets">
                            <?php if (!empty($tickets)): ?>
                                <div class="list-group">
                                    <?php foreach (array_slice($tickets, 0, 10) as $ticket): ?>
                                    <div class="list-group-item">
                                        <div class="fw-semibold"><?php echo esc_html($ticket->post_title); ?></div>
                                        <small class="text-muted"><?php echo get_the_date('Y/m/d', $ticket); ?></small>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted text-center">هیچ تیکتی وجود ندارد</p>
                            <?php endif; ?>
                        </div>

                        <!-- Contracts -->
                        <div class="tab-pane fade" id="customer-contracts">
                            <?php if (!empty($contracts)): ?>
                                <div class="list-group">
                                    <?php foreach ($contracts as $contract): 
                                        $amount = get_post_meta($contract->ID, '_total_amount', true);
                                    ?>
                                    <div class="list-group-item">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <div class="fw-semibold"><?php echo esc_html($contract->post_title); ?></div>
                                                <small class="text-muted"><?php echo get_the_date('Y/m/d', $contract); ?></small>
                                            </div>
                                            <div class="text-end">
                                                <div class="fw-bold text-success"><?php echo number_format($amount); ?></div>
                                                <small class="text-muted">تومان</small>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted text-center">هیچ قراردادی وجود ندارد</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <!-- Customer Card -->
                    <div class="card custom-card text-center">
                        <div class="card-body">
                            <?php echo get_avatar($customer_id, 100, '', '', ['class' => 'rounded-circle mb-3']); ?>
                            <h5><?php echo esc_html($customer->display_name); ?></h5>
                            <p class="text-muted"><?php echo esc_html($customer->user_email); ?></p>
                            
                            <hr>
                            
                            <div class="d-grid gap-2">
                                <a href="?view=customers&action=edit&user_id=<?php echo $customer_id; ?>" class="btn btn-primary">
                                    <i class="ri-edit-line me-1"></i>ویرایش مشتری
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Stats -->
                    <div class="card custom-card mt-3">
                        <div class="card-body">
                            <div class="mb-3 pb-3 border-bottom">
                                <small class="text-muted d-block">پروژه‌ها</small>
                                <h4 class="mb-0"><?php echo count($projects); ?></h4>
                            </div>
                            <div class="mb-3 pb-3 border-bottom">
                                <small class="text-muted d-block">قراردادها</small>
                                <h4 class="mb-0"><?php echo count($contracts); ?></h4>
                            </div>
                            <div class="mb-3 pb-3 border-bottom">
                                <small class="text-muted d-block">تیکت‌ها</small>
                                <h4 class="mb-0"><?php echo count($tickets); ?></h4>
                            </div>
                            <div>
                                <small class="text-muted d-block">کل درآمد</small>
                                <h4 class="mb-0 text-success"><?php echo number_format($total_revenue / 1000000, 1); ?>M</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        
        $html = ob_get_clean();
        wp_send_json_success(['html' => $html]);
    }

    /**
     * Get Contract Details
     */
    public function get_contract_details() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        
        $contract_id = isset($_POST['contract_id']) ? intval($_POST['contract_id']) : 0;
        
        if (!$contract_id) {
            wp_send_json_error(['message' => 'شناسه قرارداد نامعتبر است.']);
        }

        $contract = get_post($contract_id);
        if (!$contract) {
            wp_send_json_error(['message' => 'قرارداد یافت نشد.']);
        }

        $customer_id = get_post_meta($contract_id, '_customer_id', true);
        $customer = get_user_by('id', $customer_id);
        $total_amount = get_post_meta($contract_id, '_total_amount', true);
        $installments = get_post_meta($contract_id, '_installments', true) ?: [];

        $paid_amount = 0;
        $pending_count = 0;
        foreach ($installments as $inst) {
            if (($inst['status'] ?? 'pending') === 'paid') {
                $paid_amount += (int)($inst['amount'] ?? 0);
            } else {
                $pending_count++;
            }
        }

        ob_start();
        ?>
        <div class="modal-header">
            <h5 class="modal-title"><?php echo esc_html($contract->post_title); ?></h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card custom-card border border-primary">
                        <div class="card-body text-center">
                            <small class="text-muted">کل مبلغ</small>
                            <h4 class="mb-0 text-primary"><?php echo number_format($total_amount); ?></h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card custom-card border border-success">
                        <div class="card-body text-center">
                            <small class="text-muted">پرداخت شده</small>
                            <h4 class="mb-0 text-success"><?php echo number_format($paid_amount); ?></h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card custom-card border border-warning">
                        <div class="card-body text-center">
                            <small class="text-muted">اقساط معوق</small>
                            <h4 class="mb-0 text-warning"><?php echo $pending_count; ?></h4>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card custom-card mb-3">
                <div class="card-header">
                    <div class="card-title">اطلاعات مشتری</div>
                </div>
                <div class="card-body">
                    <?php if ($customer): ?>
                        <div class="d-flex align-items-center mb-2">
                            <?php echo get_avatar($customer->ID, 40, '', '', ['class' => 'rounded-circle me-2']); ?>
                            <div>
                                <div class="fw-semibold"><?php echo esc_html($customer->display_name); ?></div>
                                <small class="text-muted"><?php echo esc_html($customer->user_email); ?></small>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">اقساط</div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>ردیف</th>
                                    <th>مبلغ</th>
                                    <th>سررسید</th>
                                    <th>وضعیت</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($installments)): 
                                    $counter = 1;
                                    foreach ($installments as $inst):
                                        $status = $inst['status'] ?? 'pending';
                                ?>
                                <tr>
                                    <td><?php echo $counter++; ?></td>
                                    <td><?php echo number_format($inst['amount'] ?? 0); ?> تومان</td>
                                    <td><?php echo $inst['due_date'] ?? '---'; ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $status === 'paid' ? 'success' : 'warning'; ?>-transparent">
                                            <?php echo $status === 'paid' ? 'پرداخت شده' : 'در انتظار'; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach;
                                else: ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted">بدون قسط</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-success generate-contract-pdf" data-contract-id="<?php echo $contract_id; ?>">
                <i class="ri-file-pdf-line me-1"></i>دریافت PDF
            </button>
            <a href="?view=contracts&action=edit&contract_id=<?php echo $contract_id; ?>" class="btn btn-primary">
                <i class="ri-edit-line me-1"></i>ویرایش
            </a>
        </div>
        <?php
        
        $html = ob_get_clean();
        wp_send_json_success(['html' => $html]);
    }
}

