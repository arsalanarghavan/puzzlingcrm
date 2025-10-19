<?php
/**
 * Client Dashboard Template (Xintra Style)
 * @package PuzzlingCRM
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$current_user = wp_get_current_user();
$user_id = $current_user->ID;

// Get client's projects
$user_projects = get_posts([
    'post_type' => 'project',
    'posts_per_page' => -1,
    'author' => $user_id,
    'post_status' => 'publish'
]);

// Get client's contracts
$user_contracts = get_posts([
    'post_type' => 'contract',
    'posts_per_page' => -1,
    'author' => $user_id,
]);

// Get client's tickets
$user_tickets = get_posts([
    'post_type' => 'ticket',
    'posts_per_page' => -1,
    'author' => $user_id,
]);

$open_tickets = 0;
foreach ($user_tickets as $ticket) {
    $status = get_the_terms($ticket->ID, 'ticket_status');
    if ($status && !is_wp_error($status) && $status[0]->slug !== 'closed') {
        $open_tickets++;
    }
}
?>

<!-- Stats Cards Row -->
<div class="row">
    <div class="col-xxl-4 col-xl-6 col-lg-6 col-md-6">
        <div class="card custom-card overflow-hidden">
            <div class="card-body">
                <div class="d-flex align-items-top justify-content-between">
                    <div>
                        <span class="avatar avatar-md avatar-rounded bg-primary">
                            <i class="ri-folder-2-line fs-18"></i>
                        </span>
                    </div>
                    <div class="flex-fill ms-3">
                        <div>
                            <p class="text-muted mb-0">پروژه‌های من</p>
                            <h4 class="fw-semibold mt-1"><?php echo esc_html(count($user_projects)); ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xxl-4 col-xl-6 col-lg-6 col-md-6">
        <div class="card custom-card overflow-hidden">
            <div class="card-body">
                <div class="d-flex align-items-top justify-content-between">
                    <div>
                        <span class="avatar avatar-md avatar-rounded bg-secondary">
                            <i class="ri-file-text-line fs-18"></i>
                        </span>
                    </div>
                    <div class="flex-fill ms-3">
                        <div>
                            <p class="text-muted mb-0">قراردادها</p>
                            <h4 class="fw-semibold mt-1"><?php echo esc_html(count($user_contracts)); ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xxl-4 col-xl-6 col-lg-6 col-md-6">
        <div class="card custom-card overflow-hidden">
            <div class="card-body">
                <div class="d-flex align-items-top justify-content-between">
                    <div>
                        <span class="avatar avatar-md avatar-rounded bg-warning">
                            <i class="ri-customer-service-2-line fs-18"></i>
                        </span>
                    </div>
                    <div class="flex-fill ms-3">
                        <div>
                            <p class="text-muted mb-0">تیکت‌های باز</p>
                            <h4 class="fw-semibold mt-1"><?php echo esc_html($open_tickets); ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Projects -->
<div class="row">
    <div class="col-xl-6">
        <div class="card custom-card">
            <div class="card-header justify-content-between">
                <div class="card-title">
                    <i class="ri-folder-2-line me-2"></i>
                    پروژه‌های من
                </div>
                <a href="<?php echo esc_url(home_url('/dashboard/projects')); ?>" class="btn btn-sm btn-primary-light">
                    مشاهده همه <i class="ri-arrow-left-s-line align-middle"></i>
                </a>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php if (!empty($user_projects)): ?>
                        <?php foreach (array_slice($user_projects, 0, 5) as $project): ?>
                            <li class="list-group-item">
                                <div class="d-flex align-items-center">
                                    <span class="avatar avatar-sm bg-primary-transparent">
                                        <i class="ri-folder-2-line"></i>
                                    </span>
                                    <div class="ms-2 flex-fill">
                                        <p class="fw-semibold mb-0"><?php echo esc_html($project->post_title); ?></p>
                                        <p class="fs-12 text-muted mb-0">
                                            <i class="ri-time-line me-1"></i>
                                            <?php echo esc_html(human_time_diff(strtotime($project->post_date), current_time('timestamp'))); ?> پیش
                                        </p>
                                    </div>
                                    <a href="<?php echo esc_url(home_url('/dashboard/projects/' . $project->ID)); ?>" class="btn btn-sm btn-icon btn-light">
                                        <i class="ri-arrow-left-s-line"></i>
                                    </a>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="list-group-item text-center text-muted py-5">
                            <i class="ri-folder-2-line fs-3 mb-2 d-block opacity-3"></i>
                            پروژه‌ای وجود ندارد
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
    
    <!-- Recent Tickets -->
    <div class="col-xl-6">
        <div class="card custom-card">
            <div class="card-header justify-content-between">
                <div class="card-title">
                    <i class="ri-customer-service-2-line me-2"></i>
                    تیکت‌های من
                </div>
                <a href="<?php echo esc_url(home_url('/dashboard/tickets')); ?>" class="btn btn-sm btn-warning-light">
                    مشاهده همه <i class="ri-arrow-left-s-line align-middle"></i>
                </a>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php if (!empty($user_tickets)): ?>
                        <?php foreach (array_slice($user_tickets, 0, 5) as $ticket): ?>
                            <?php
                            $status = get_the_terms($ticket->ID, 'ticket_status');
                            $status_name = $status && !is_wp_error($status) ? $status[0]->name : 'نامشخص';
                            $status_slug = $status && !is_wp_error($status) ? $status[0]->slug : '';
                            
                            $badge_class = 'bg-secondary';
                            if ($status_slug === 'open') $badge_class = 'bg-success';
                            elseif ($status_slug === 'in-progress') $badge_class = 'bg-primary';
                            elseif ($status_slug === 'closed') $badge_class = 'bg-secondary';
                            ?>
                            <li class="list-group-item">
                                <div class="d-flex align-items-center">
                                    <span class="avatar avatar-sm bg-warning-transparent">
                                        <i class="ri-customer-service-2-line"></i>
                                    </span>
                                    <div class="ms-2 flex-fill">
                                        <p class="fw-semibold mb-0"><?php echo esc_html($ticket->post_title); ?></p>
                                        <p class="fs-12 mb-0">
                                            <span class="badge <?php echo $badge_class; ?> badge-sm"><?php echo esc_html($status_name); ?></span>
                                        </p>
                                    </div>
                                    <div class="text-muted fs-12">
                                        <?php echo esc_html(human_time_diff(strtotime($ticket->post_date), current_time('timestamp'))); ?> پیش
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="list-group-item text-center text-muted py-5">
                            <i class="ri-customer-service-2-line fs-3 mb-2 d-block opacity-3"></i>
                            تیکتی وجود ندارد
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Welcome Message -->
<div class="row">
    <div class="col-xl-12">
        <div class="card custom-card">
            <div class="card-body">
                <div class="alert alert-primary d-flex align-items-center" role="alert">
                    <i class="ri-information-line fs-4 me-3"></i>
                    <div>
                        <strong>خوش آمدید <?php echo esc_html($current_user->display_name); ?>!</strong>
                        <p class="mb-0 mt-1">از منوی سمت راست می‌توانید به پروژه‌ها، قراردادها، تیکت‌ها و سایر بخش‌ها دسترسی داشته باشید.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
