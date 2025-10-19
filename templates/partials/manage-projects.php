<?php
/**
 * Template for System Manager to Manage Projects (with Search, Filter, Edit, Delete) - Card View
 * @package PuzzlingCRM
 */
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! current_user_can('manage_options') ) return;

// Determine action
$action = isset($_GET['action']) ? sanitize_key($_GET['action']) : 'list';
$project_id = isset($_GET['project_id']) ? intval($_GET['project_id']) : 0;
$project_to_edit = ($project_id > 0) ? get_post($project_id) : null;

// Robust check to ensure default project statuses exist.
$project_statuses = get_terms(['taxonomy' => 'project_status', 'hide_empty' => false]);
if (empty($project_statuses)) {
    PuzzlingCRM_CPT_Manager::create_default_terms();
    $project_statuses = get_terms(['taxonomy' => 'project_status', 'hide_empty' => false]);
}
?>

<div class="pzl-projects-manager-wrapper">

    <?php if ($action === 'new' || ($action === 'edit' && $project_to_edit)): ?>
        <div class="card custom-card">
            <div class="card-header justify-content-between">
                <div class="card-title">
                    <i class="ri-edit-line me-2"></i>
                    <?php echo $project_to_edit ? 'ویرایش پروژه' : 'ایجاد پروژه جدید'; ?>
                </div>
                <a href="<?php echo esc_url(remove_query_arg(['action', 'project_id'])); ?>" class="btn btn-secondary btn-sm">
                    <i class="ri-arrow-right-line"></i> بازگشت
                </a>
            </div>
            <div class="card-body">

            <form method="post" class="pzl-form pzl-ajax-form" id="pzl-project-form" data-action="puzzling_manage_project" enctype="multipart/form-data">
                <?php wp_nonce_field('puzzlingcrm-ajax-nonce', 'security'); ?>
                <input type="hidden" name="project_id" value="<?php echo esc_attr($project_id); ?>">

                <div class="form-group">
                    <label for="project_title">عنوان پروژه (ضروری):</label>
                    <input type="text" id="project_title" name="project_title" value="<?php echo $project_to_edit ? esc_attr($project_to_edit->post_title) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="contract_id">اتصال به قرارداد (ضروری):</label>
                    <select name="contract_id" id="contract_id" required>
                        <option value="">-- انتخاب قرارداد --</option>
                        <?php
                        $contracts = get_posts(['post_type' => 'contract', 'posts_per_page' => -1, 'orderby' => 'ID', 'order' => 'DESC']);
                        $current_contract_id = $project_to_edit ? get_post_meta($project_id, '_contract_id', true) : 0;
                        foreach ($contracts as $contract) {
                            $customer = get_userdata($contract->post_author);
                            $label = sprintf('#%d - %s (%s)', $contract->ID, $contract->post_title, $customer->display_name);
                            echo '<option value="' . esc_attr($contract->ID) . '" ' . selected($current_contract_id, $contract->ID, false) . '>' . esc_html($label) . '</option>';
                        }
                        ?>
                    </select>
                    <p class="description">پروژه اطلاعات مشتری، تاریخ‌ها و سایر جزئیات را از این قرارداد به ارث می‌برد.</p>
                </div>

                <div class="form-group">
                    <label for="project_status">وضعیت پروژه:</label>
                    <select name="project_status" id="project_status" required>
                        <?php
                        $current_status = $project_to_edit ? wp_get_post_terms($project_id, 'project_status', ['fields' => 'ids']) : [];
                        $current_status_id = !empty($current_status) ? $current_status[0] : 0;
                        foreach ($project_statuses as $status) {
                            echo '<option value="' . esc_attr($status->term_id) . '" ' . selected($current_status_id, $status->term_id, false) . '>' . esc_html($status->name) . '</option>';
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="project_content">توضیحات پروژه:</label>
                    <?php wp_editor($project_to_edit ? $project_to_edit->post_content : '', 'project_content', ['textarea_rows' => 8]); ?>
                </div>
                 
                <div class="form-group">
                    <label for="project_logo">لوگوی پروژه (تصویر شاخص):</label>
                    <input type="file" id="project_logo" name="project_logo" accept="image/*">
                    <?php if (has_post_thumbnail($project_id)) { echo '<div style="margin-top:10px;">' . get_the_post_thumbnail($project_id, 'thumbnail') . '</div>'; } ?>
                </div>

                 <div class="form-submit">
                    <button type="submit" class="btn btn-primary">
                        <i class="ri-save-line"></i>
                        <?php echo $project_to_edit ? 'ذخیره تغییرات' : 'ایجاد پروژه'; ?>
                    </button>
                </div>
            </form>
            </div>
        </div>

    <?php else: // 'list' view ?>
        <div class="pzl-card">
            <div class="card-header justify-content-between">
                <div></div>
                <a href="<?php echo add_query_arg(['action' => 'new']); ?>" class="btn btn-primary btn-sm">
                    <i class="ri-add-line"></i> ایجاد پروژه جدید
                </a>
            </div>
            <div class="card-body">
            
            <form method="get" class="pzl-form">
                <input type="hidden" name="view" value="projects">
                <div class="pzl-form-row" style="align-items: flex-end; gap: 10px;">
                    <div class="form-group" style="flex: 2; margin-bottom: 0;"><input type="text" name="s" placeholder="جستجوی عنوان پروژه..." value="<?php echo isset($_GET['s']) ? esc_attr($_GET['s']) : ''; ?>"></div>
                    <div class="form-group" style="flex: 1; margin-bottom: 0;"><select name="customer_filter"><option value="">همه مشتریان</option><?php $all_customers = get_users(['role__in' => ['customer', 'subscriber'], 'orderby' => 'display_name']); $current_customer = isset($_GET['customer_filter']) ? intval($_GET['customer_filter']) : 0; foreach ($all_customers as $customer) { echo '<option value="' . esc_attr($customer->ID) . '" ' . selected($current_customer, $customer->ID, false) . '>' . esc_html($customer->display_name) . '</option>'; } ?></select></div>
                    <div class="form-group" style="flex: 1; margin-bottom: 0;"><select name="status_filter"><option value="">همه وضعیت‌ها</option><?php $current_status = isset($_GET['status_filter']) ? sanitize_key($_GET['status_filter']) : ''; foreach ($project_statuses as $status) { echo '<option value="' . esc_attr($status->slug) . '" ' . selected($current_status, $status->slug, false) . '>' . esc_html($status->name) . '</option>'; } ?></select></div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="ri-filter-3-line"></i> فیلتر
                        </button>
                    </div>
                </div>
            </form>
            </div>
            <div class="card-body p-0">

            <?php
            $base_page_url = get_permalink();
            $paged = get_query_var('paged') ? get_query_var('paged') : 1;
            $args = ['post_type' => 'project', 'posts_per_page' => 12, 'paged' => $paged];
            if (!empty($_GET['s'])) $args['s'] = sanitize_text_field($_GET['s']);
            if (!empty($_GET['customer_filter'])) $args['author'] = intval($_GET['customer_filter']);
            if (!empty($_GET['status_filter'])) $args['tax_query'] = [['taxonomy' => 'project_status', 'field' => 'slug', 'terms' => sanitize_key($_GET['status_filter'])]];
            $projects_query = new WP_Query($args);

            if ($projects_query->have_posts()): ?>
                <div class="pzl-projects-grid-view">
                    <?php while($projects_query->have_posts()): $projects_query->the_post();
                        $project_id = get_the_ID();
                        $customer = get_userdata(get_the_author_meta('ID'));
                        $edit_url = add_query_arg(['action' => 'edit', 'project_id' => $project_id]);
                        $contract_id = get_post_meta($project_id, '_contract_id', true);
                        
                        $contract_url = 'https://puzzlingco.ir/panel/?endp=inf_menu_3&contract_id=' . $contract_id;
                        
                        // Fetch data from contract
                        $model_val = $contract_id ? get_post_meta($contract_id, '_project_subscription_model', true) : '';
                        $model_map = ['onetime' => 'یکبار پرداخت', 'subscription' => 'اشتراکی'];
                        $model_text = $model_map[$model_val] ?? '---';

                        $duration_val = $contract_id ? get_post_meta($contract_id, '_project_contract_duration', true) : '';
                        $duration_map = ['1-month' => 'یک ماهه', '3-months' => 'سه ماهه', '6-months' => 'شش ماهه', '12-months' => 'یک ساله'];
                        $duration_text = $duration_map[$duration_val] ?? '---';
                        
                        $end_date = $contract_id ? get_post_meta($contract_id, '_project_end_date', true) : '';

                        $status_terms = get_the_terms($project_id, 'project_status');
                        $status_name = !empty($status_terms) ? esc_html($status_terms[0]->name) : '---';
                        $status_slug = !empty($status_terms) ? esc_attr($status_terms[0]->slug) : 'default';
                    ?>
                    <div class="pzl-project-card-item">
                        <div class="pzl-project-card-header-flex">
                            <div class="pzl-project-card-logo"><?php if (has_post_thumbnail()) { the_post_thumbnail('thumbnail'); } else { echo '<div class="pzl-logo-placeholder">' . esc_html(mb_substr(get_the_title(), 0, 1)) . '</div>'; } ?></div>
                            <div class="pzl-project-card-title-group"><h4 class="pzl-project-card-title"><?php the_title(); ?></h4><span class="pzl-project-card-customer"><?php echo esc_html($customer->display_name); ?></span></div>
                        </div>
                        <div class="pzl-project-card-details-grid">
                            <div><i class="fas fa-toggle-on"></i> وضعیت: <span class="pzl-status-badge status-<?php echo $status_slug; ?>"><?php echo $status_name; ?></span></div>
                            <div><i class="fas fa-sync-alt"></i> مدل: <strong><?php echo esc_html($model_text); ?></strong></div>
                            <div><i class="fas fa-calendar-alt"></i> مدت: <strong><?php echo esc_html($duration_text); ?></strong></div>
                            <div><i class="fas fa-hourglass-end"></i> پایان: <strong><?php echo $end_date ? jdate('Y/m/d', strtotime($end_date)) : '---'; ?></strong></div>
                        </div>
                        <div class="pzl-project-card-actions">
                            <a href="<?php echo esc_url($edit_url); ?>" class="btn btn-primary-light btn-sm">
                                <i class="ri-edit-line"></i> ویرایش
                            </a>
                            <?php if ($contract_id): ?>
                            <a href="<?php echo esc_url($contract_url); ?>" class="btn btn-info-light btn-sm" target="_blank">
                                <i class="ri-file-text-line"></i> قرارداد
                            </a>
                            <?php endif; ?>
                            <button class="delete-project btn btn-danger-light btn-sm" data-project-id="<?php echo esc_attr($project_id); ?>" data-nonce="<?php echo esc_attr(wp_create_nonce('puzzling_delete_project_' . $project_id)); ?>">
                                <i class="ri-delete-bin-line"></i> حذف
                            </button>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
                <div class="pagination"><?php echo paginate_links(['total' => $projects_query->max_num_pages, 'current' => $paged, 'format' => '?paged=%#%']); ?></div>
                <?php wp_reset_postdata(); ?>
            <?php else: ?>
                 <div class="pzl-empty-state"><i class="fas fa-exclamation-circle"></i><h4>پروژه‌ای یافت نشد</h4><p>هیچ پروژه‌ای با این مشخصات یافت نشد. می‌توانید یک پروژه جدید ایجاد کنید.</p></div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<style>
/* Add these styles to your main CSS file (puzzlingcrm-styles.css) */
.pzl-project-card-details-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 13px; color: #333; margin: 15px 0; flex-grow: 1; align-content: start; }
.pzl-project-card-details-grid div { display: flex; align-items: center; gap: 6px; }
.pzl-project-card-details-grid .fas { color: var(--pzl-primary-color); }
</style>