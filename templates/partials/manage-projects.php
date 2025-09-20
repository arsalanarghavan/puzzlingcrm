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
?>

<div class="pzl-projects-manager-wrapper">

    <?php if ($action === 'edit' || $action === 'new'): ?>
        <div class="pzl-card">
            <div class="pzl-card-header">
                <h3><?php echo $project_id > 0 ? 'ویرایش پروژه' : 'ایجاد پروژه جدید'; ?></h3>
                <a href="<?php echo remove_query_arg(['action', 'project_id']); ?>" class="pzl-button">&larr; بازگشت به لیست پروژه‌ها</a>
            </div>

            <form method="post" class="pzl-form pzl-ajax-form" id="pzl-project-form" data-action="puzzling_manage_project" enctype="multipart/form-data">
                <?php wp_nonce_field('puzzlingcrm-ajax-nonce', 'security'); ?>
                <input type="hidden" name="project_id" value="<?php echo esc_attr($project_id); ?>">

                <div class="pzl-form-row">
                    <div class="form-group" style="flex: 2;">
                        <label for="project_title">عنوان پروژه:</label>
                        <input type="text" id="project_title" name="project_title" value="<?php echo $project_to_edit ? esc_attr($project_to_edit->post_title) : ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="customer_id">تخصیص به مشتری:</label>
                        <select name="customer_id" id="customer_id" required>
                            <option value="">-- انتخاب مشتری --</option>
                            <?php
                            $customers = get_users(['role__in' => ['customer', 'subscriber'], 'orderby' => 'display_name']);
                            foreach ($customers as $customer) {
                                $selected = $project_to_edit && $project_to_edit->post_author == $customer->ID ? 'selected' : '';
                                echo '<option value="' . esc_attr($customer->ID) . '" ' . $selected . '>' . esc_html($customer->display_name) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                </div>
                
                <hr>
                <h4>جزئیات و قرارداد</h4>

                <div class="pzl-form-row">
                    <div class="form-group">
                        <label for="project_status">وضعیت پروژه:</label>
                        <select name="project_status" id="project_status" required>
                            <?php
                            $statuses = get_terms(['taxonomy' => 'project_status', 'hide_empty' => false]);
                            $current_status = $project_to_edit ? wp_get_post_terms($project_id, 'project_status', ['fields' => 'ids']) : [];
                            $current_status_id = !empty($current_status) ? $current_status[0] : 0;
                            foreach ($statuses as $status) {
                                echo '<option value="' . esc_attr($status->term_id) . '" ' . selected($current_status_id, $status->term_id, false) . '>' . esc_html($status->name) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                     <div class="form-group">
                        <label for="_project_contract_person">شخص طرف قرارداد:</label>
                        <input type="text" id="_project_contract_person" name="_project_contract_person" value="<?php echo $project_to_edit ? esc_attr(get_post_meta($project_to_edit->ID, '_project_contract_person', true)) : ''; ?>">
                    </div>
                </div>

                 <div class="pzl-form-row">
                    <div class="form-group">
                        <label for="_project_subscription_model">مدل اشتراک:</label>
                        <select name="_project_subscription_model" id="_project_subscription_model">
                            <?php
                            $models = ['یکبار پرداخت' => 'onetime', 'اشتراکی' => 'subscription'];
                            $current_model = $project_to_edit ? get_post_meta($project_id, '_project_subscription_model', true) : 'onetime';
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
                            $current_duration = $project_to_edit ? get_post_meta($project_id, '_project_contract_duration', true) : '1-month';
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
                        <input type="date" id="_project_start_date" name="_project_start_date" value="<?php echo $project_to_edit ? esc_attr(get_post_meta($project_to_edit->ID, '_project_start_date', true)) : ''; ?>">
                    </div>
                     <div class="form-group">
                        <label for="_project_end_date">تاریخ پایان قرارداد:</label>
                        <input type="date" id="_project_end_date" name="_project_end_date" value="<?php echo $project_to_edit ? esc_attr(get_post_meta($project_to_edit->ID, '_project_end_date', true)) : ''; ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="project_content">توضیحات پروژه:</label>
                    <?php wp_editor($project_to_edit ? $project_to_edit->post_content : '', 'project_content', ['textarea_rows' => 10]); ?>
                </div>
                 
                <div class="pzl-form-row">
                    <div class="form-group half-width">
                        <label for="project_logo">لوگوی پروژه (تصویر شاخص):</label>
                        <input type="file" id="project_logo" name="project_logo" accept="image/*">
                        <?php if (has_post_thumbnail($project_id)) { echo '<div style="margin-top:10px;">' . get_the_post_thumbnail($project_id, 'thumbnail') . '</div>'; } ?>
                    </div>
                    <div class="form-group half-width">
                        <label for="project_files">فایل‌های پیوست:</label>
                        <input type="file" id="project_files" name="project_files[]" multiple>
                        <p class="description">می‌توانید چندین فایل را به صورت همزمان انتخاب کنید. فایل‌های قبلی حذف نخواهند شد.</p>
                    </div>
                </div>
                 <div class="form-submit">
                    <button type="submit" class="pzl-button"><?php echo $project_id > 0 ? 'ذخیره تغییرات' : 'ایجاد پروژه'; ?></button>
                </div>
            </form>
        </div>

    <?php else: // 'list' view ?>
        <div class="pzl-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
                <h3><i class="fas fa-briefcase"></i> لیست پروژه‌ها</h3>
                <a href="<?php echo add_query_arg(['action' => 'new']); ?>" class="pzl-button">ایجاد پروژه جدید</a>
            </div>
            
            <form method="get" class="pzl-form">
                <input type="hidden" name="view" value="projects">
                <div class="pzl-form-row" style="align-items: flex-end;">
                    <div class="form-group" style="flex: 2;">
                        <label>جستجو</label>
                        <input type="text" name="s" placeholder="جستجوی عنوان پروژه..." value="<?php echo isset($_GET['s']) ? esc_attr($_GET['s']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>مشتری</label>
                        <select name="customer_filter">
                            <option value="">همه مشتریان</option>
                            <?php
                            $all_customers = get_users(['role__in' => ['customer', 'subscriber'], 'orderby' => 'display_name']);
                            $current_customer = isset($_GET['customer_filter']) ? intval($_GET['customer_filter']) : 0;
                            foreach ($all_customers as $customer) {
                                echo '<option value="' . esc_attr($customer->ID) . '" ' . selected($current_customer, $customer->ID, false) . '>' . esc_html($customer->display_name) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                     <div class="form-group">
                        <label>وضعیت پروژه</label>
                        <select name="status_filter">
                            <option value="">همه وضعیت‌ها</option>
                             <?php
                            $all_statuses = get_terms(['taxonomy' => 'project_status', 'hide_empty' => false]);
                            $current_status = isset($_GET['status_filter']) ? sanitize_key($_GET['status_filter']) : '';
                            foreach ($all_statuses as $status) {
                                echo '<option value="' . esc_attr($status->slug) . '" ' . selected($current_status, $status->slug, false) . '>' . esc_html($status->name) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="pzl-button">فیلتر</button>
                    </div>
                </div>
            </form>

            <?php
            // Save the current page's URL before starting the custom loop
            $base_page_url = get_permalink();

            $paged = get_query_var('paged') ? get_query_var('paged') : 1;
            $args = [
                'post_type' => 'project',
                'posts_per_page' => 12, // تعداد آیتم‌ها برای نمایش کارتی
                'paged' => $paged,
            ];
            if (!empty($_GET['s'])) {
                $args['s'] = sanitize_text_field($_GET['s']);
            }
            if (!empty($_GET['customer_filter'])) {
                $args['author'] = intval($_GET['customer_filter']);
            }
            if (!empty($_GET['status_filter'])) {
                 $args['tax_query'] = [
                    ['taxonomy' => 'project_status', 'field' => 'slug', 'terms' => sanitize_key($_GET['status_filter'])]
                ];
            }
            $projects_query = new WP_Query($args);

            if ($projects_query->have_posts()): ?>
                <div class="pzl-projects-grid-view">
                    <?php while($projects_query->have_posts()): $projects_query->the_post();
                        $project_id = get_the_ID();
                        $customer = get_userdata(get_the_author_meta('ID'));
                        $edit_url = add_query_arg(['action' => 'edit', 'project_id' => $project_id]);
                        
                        $status_terms = get_the_terms($project_id, 'project_status');
                        $status_name = !empty($status_terms) ? esc_html($status_terms[0]->name) : '---';
                        $status_slug = !empty($status_terms) ? esc_attr($status_terms[0]->slug) : 'default';
                        
                        $end_date = get_post_meta($project_id, '_project_end_date', true);
                    ?>
                    <div class="pzl-project-card-item">
                        <div class="pzl-project-card-header-flex">
                            <div class="pzl-project-card-logo">
                                <?php if (has_post_thumbnail()) : ?>
                                    <?php the_post_thumbnail('thumbnail'); ?>
                                <?php else: ?>
                                    <div class="pzl-logo-placeholder"><?php echo esc_html(mb_substr(get_the_title(), 0, 1)); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="pzl-project-card-title-group">
                                <h4 class="pzl-project-card-title"><?php the_title(); ?></h4>
                                <span class="pzl-project-card-customer"><?php echo esc_html($customer->display_name); ?></span>
                            </div>
                        </div>

                        <div class="pzl-project-card-meta">
                             <span class="pzl-status-badge status-<?php echo $status_slug; ?>"><?php echo $status_name; ?></span>
                            <?php if ($end_date): ?>
                            <span class="pzl-project-card-date">پایان: <?php echo esc_html(date_i18n('Y/m/d', strtotime($end_date))); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="pzl-project-card-actions">
                            <a href="<?php echo esc_url($edit_url); ?>" class="pzl-button pzl-button-sm">ویرایش</a>
                             <a href="#" class="delete-project pzl-button pzl-button-sm"
                               data-project-id="<?php echo esc_attr($project_id); ?>"
                               data-nonce="<?php echo esc_attr(wp_create_nonce('puzzling_delete_project_' . $project_id)); ?>" style="background-color: #dc3545 !important;">حذف</a>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
                <div class="pagination">
                    <?php
                    echo paginate_links([
                        'total' => $projects_query->max_num_pages,
                        'current' => $paged,
                        'format' => '?paged=%#%',
                    ]);
                    ?>
                </div>
                <?php wp_reset_postdata(); ?>
            <?php else: ?>
                 <div class="pzl-empty-state">
                    <i class="fas fa-exclamation-circle"></i>
                    <h4>پروژه‌ای یافت نشد</h4>
                    <p>هیچ پروژه‌ای با این مشخصات یافت نشد. می‌توانید یک پروژه جدید ایجاد کنید.</p>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<style>
/* Add these styles to your main CSS file (puzzlingcrm-styles.css) */
.pzl-project-card-item { padding: 20px; display: flex; flex-direction: column; }
.pzl-project-card-header-flex { display: flex; align-items: center; gap: 15px; margin-bottom: 15px; }
.pzl-project-card-logo img, .pzl-logo-placeholder { width: 50px; height: 50px; border-radius: 50%; object-fit: cover; }
.pzl-logo-placeholder { background-color: var(--pzl-primary-color); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 24px; font-weight: bold; }
.pzl-project-card-title-group { flex: 1; }
.pzl-project-card-title { font-size: 16px; margin: 0; }
.pzl-project-card-customer { font-size: 13px; color: #6c757d; }
.pzl-project-card-meta { display: flex; justify-content: space-between; align-items: center; font-size: 13px; margin-bottom: 20px; flex-grow: 1; }
.pzl-project-card-actions { margin-top: auto; }
</style>