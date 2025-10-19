<?php
/**
 * Template for Client to view their contracts - Card View.
 * @package PuzzlingCRM
 */
if (!defined('ABSPATH')) exit;

$contracts = get_posts([
    'post_type' => 'contract',
    'author' => get_current_user_id(),
    'posts_per_page' => -1,
]);
?>
<div class="pzl-dashboard-section">
    <h3><i class="ri-file-text-line"></i> قراردادهای شما</h3>

    <?php if (empty($contracts)): ?>
        <div class="pzl-empty-state">
            <i class="ri-error-warning-line"></i>
            <h4>قراردادی یافت نشد</h4>
            <p>شما در حال حاضر هیچ قرارداد فعالی ندارید.</p>
        </div>
    <?php else: ?>
        <div class="pzl-projects-grid-view">
            <?php foreach($contracts as $contract): 
                $contract_id = $contract->ID;
                $end_date = get_post_meta($contract_id, '_project_end_date', true);
                $model_val = get_post_meta($contract_id, '_project_subscription_model', true);
                $model_map = ['onetime' => 'یکبار پرداخت', 'subscription' => 'اشتراکی'];
                $model_text = $model_map[$model_val] ?? '---';
                
                $related_projects = get_posts([
                    'post_type' => 'project',
                    'posts_per_page' => -1,
                    'meta_key' => '_contract_id',
                    'meta_value' => $contract_id
                ]);
            ?>
            <div class="pzl-project-card-item">
                <div class="pzl-project-card-header-flex">
                    <div class="pzl-project-card-logo">
                         <div class="pzl-logo-placeholder" style="background-color: #6c757d;"><i class="ri-file-text-line"></i></div>
                    </div>
                    <div class="pzl-project-card-title-group">
                        <h4 class="pzl-project-card-title"><?php echo esc_html($contract->post_title); ?></h4>
                        <span class="pzl-project-card-customer">شماره قرارداد: #<?php echo esc_html($contract_id); ?></span>
                    </div>
                </div>
                 <div class="pzl-project-card-details-grid" style="align-content: flex-start; flex-grow: 0;">
                    <div><i class="ri-refresh-line"></i> مدل: <strong><?php echo esc_html($model_text); ?></strong></div>
                    <div><i class="ri-hourglass-line"></i> پایان: <strong><?php echo $end_date ? jdate('Y/m/d', strtotime($end_date)) : '---'; ?></strong></div>
                </div>
                
                <div class="pzl-contract-projects-list">
                    <h5>پروژه‌های این قرارداد:</h5>
                    <?php if(!empty($related_projects)): ?>
                        <ul>
                            <?php foreach($related_projects as $project): 
                                $project_view_url = add_query_arg(['view' => 'projects', 'project_id' => $project->ID]);
                            ?>
                            <li><a href="<?php echo esc_url($project_view_url); ?>"><?php echo esc_html($project->post_title); ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p>هنوز پروژه‌ای برای این قرارداد تعریف نشده است.</p>
                    <?php endif; ?>
                </div>

            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<style>
.pzl-contract-projects-list { margin-top: auto; padding-top: 15px; border-top: 1px solid var(--pzl-border-color); }
.pzl-contract-projects-list h5 { font-size: 14px; margin-bottom: 10px; }
.pzl-contract-projects-list ul { list-style: none; padding: 0; margin: 0; }
.pzl-contract-projects-list li a { text-decoration: none; }
</style>