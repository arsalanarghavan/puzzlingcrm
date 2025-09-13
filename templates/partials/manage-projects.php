<?php
/**
 * Template for System Manager to Manage Projects (with Search, Filter, Edit, Delete)
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
        <h3><?php echo $project_id > 0 ? 'ویرایش پروژه' : 'ایجاد پروژه جدید'; ?></h3>
        <a href="<?php echo remove_query_arg(['action', 'project_id']); ?>">&larr; بازگشت به لیست پروژه‌ها</a>

        <form method="post" class="pzl-form-container" style="margin-top: 20px;">
            <?php wp_nonce_field('puzzling_manage_project'); ?>
            <input type="hidden" name="puzzling_action" value="manage_project">
            <input type="hidden" name="project_id" value="<?php echo esc_attr($project_id); ?>">

            <div class="form-group">
                <label for="project_title">عنوان پروژه:</label>
                <input type="text" id="project_title" name="project_title" value="<?php echo $project_to_edit ? esc_attr($project_to_edit->post_title) : ''; ?>" required>
            </div>
            <div class="form-group">
                <label for="project_content">توضیحات پروژه:</label>
                <?php wp_editor($project_to_edit ? $project_to_edit->post_content : '', 'project_content', ['textarea_rows' => 10]); ?>
            </div>
            <div class="form-group">
                <label for="customer_id">تخصیص به مشتری:</label>
                <select name="customer_id" id="customer_id" required>
                    <option value="">-- انتخاب مشتری --</option>
                    <?php
                    $customers = get_users(['role' => 'customer', 'orderby' => 'display_name']);
                    foreach ($customers as $customer) {
                        $selected = $project_to_edit && $project_to_edit->post_author == $customer->ID ? 'selected' : '';
                        echo '<option value="' . esc_attr($customer->ID) . '" ' . $selected . '>' . esc_html($customer->display_name) . '</option>';
                    }
                    ?>
                </select>
            </div>
            <button type="submit" class="pzl-button pzl-button-primary"><?php echo $project_id > 0 ? 'ذخیره تغییرات' : 'ایجاد پروژه'; ?></button>
        </form>

    <?php else: // 'list' view ?>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
            <h3><span class="dashicons dashicons-portfolio"></span> لیست پروژه‌ها</h3>
            <a href="<?php echo add_query_arg(['action' => 'new']); ?>" class="pzl-button pzl-button-primary">ایجاد پروژه جدید</a>
        </div>
        
        <form method="get" class="pzl-form-container" style="margin-bottom: 20px;">
            <input type="hidden" name="view" value="projects">
            <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                <input type="text" name="s" placeholder="جستجوی عنوان پروژه..." value="<?php echo isset($_GET['s']) ? esc_attr($_GET['s']) : ''; ?>">
                <select name="customer_filter">
                    <option value="">همه مشتریان</option>
                    <?php
                    $all_customers = get_users(['role' => 'customer', 'orderby' => 'display_name']);
                    $current_customer = isset($_GET['customer_filter']) ? intval($_GET['customer_filter']) : 0;
                    foreach ($all_customers as $customer) {
                        echo '<option value="' . esc_attr($customer->ID) . '" ' . selected($current_customer, $customer->ID, false) . '>' . esc_html($customer->display_name) . '</option>';
                    }
                    ?>
                </select>
                <button type="submit" class="pzl-button pzl-button-secondary">فیلتر</button>
            </div>
        </form>

        <?php
        $paged = get_query_var('paged') ? get_query_var('paged') : 1;
        $args = [
            'post_type' => 'project',
            'posts_per_page' => 15,
            'paged' => $paged,
        ];
        if (!empty($_GET['s'])) {
            $args['s'] = sanitize_text_field($_GET['s']);
        }
        if (!empty($_GET['customer_filter'])) {
            $args['author'] = intval($_GET['customer_filter']);
        }
        $projects_query = new WP_Query($args);

        if ($projects_query->have_posts()): ?>
            <table class="pzl-table">
                <thead>
                    <tr>
                        <th>عنوان پروژه</th>
                        <th>مشتری</th>
                        <th>تاریخ ایجاد</th>
                        <th>قرارداد</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($projects_query->have_posts()): $projects_query->the_post(); 
                        $project_id = get_the_ID();
                        $customer = get_userdata(get_the_author_meta('ID'));
                        $edit_url = add_query_arg(['action' => 'edit', 'project_id' => $project_id]);
                        $contract = get_posts(['post_type' => 'contract', 'meta_key' => '_project_id', 'meta_value' => $project_id, 'posts_per_page' => 1]);
                        $contract_status = !empty($contract) ? '<span class="pzl-status status-paid">دارد</span>' : '<span class="pzl-status status-pending">ندارد</span>';
                    ?>
                    <tr>
                        <td><strong><?php the_title(); ?></strong></td>
                        <td><?php echo esc_html($customer->display_name); ?></td>
                        <td><?php echo get_the_date('Y/m/d'); ?></td>
                        <td><?php echo $contract_status; ?></td>
                        <td>
                            <a href="<?php echo esc_url($edit_url); ?>" class="pzl-button pzl-button-secondary">ویرایش</a>
                            <form method="post" onsubmit="return confirm('آیا از حذف این پروژه مطمئن هستید؟');" style="display: inline;">
                                 <input type="hidden" name="puzzling_action" value="delete_project">
                                 <input type="hidden" name="item_id" value="<?php echo esc_attr($project_id); ?>">
                                 <?php wp_nonce_field('puzzling_delete_project_' . $project_id, '_wpnonce'); ?>
                                 <button type="submit" class="pzl-button" style="background-color: #dc3545; color: white;">حذف</button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php 
            echo paginate_links(['total' => $projects_query->max_num_pages, 'current' => $paged]);
            wp_reset_postdata(); 
            ?>
        <?php else: ?>
            <p>هیچ پروژه‌ای با این مشخصات یافت نشد.</p>
        <?php endif; ?>
    <?php endif; ?>
</div>