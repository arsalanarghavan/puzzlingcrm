<?php
/**
 * Template for System Manager to Manage Projects
 * @package PuzzlingCRM
 */
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! current_user_can('manage_options') ) return;

// Determine if we are editing or creating a new project
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
                <textarea id="project_content" name="project_content" rows="8"><?php echo $project_to_edit ? esc_textarea($project_to_edit->post_content) : ''; ?></textarea>
            </div>
            <div class="form-group">
                <label for="customer_id">تخصیص به مشتری:</label>
                <select name="customer_id" id="customer_id" required>
                    <option value="">-- انتخاب مشتری --</option>
                    <?php
                    $customers = get_users(['role' => 'customer']);
                    foreach ($customers as $customer) {
                        $selected = $project_to_edit && $project_to_edit->post_author == $customer->ID ? 'selected' : '';
                        echo '<option value="' . esc_attr($customer->ID) . '" ' . $selected . '>' . esc_html($customer->display_name) . ' (' . esc_html($customer->user_email) . ')</option>';
                    }
                    ?>
                </select>
            </div>
            <button type="submit" class="pzl-button pzl-button-primary"><?php echo $project_id > 0 ? 'ذخیره تغییرات' : 'ایجاد پروژه'; ?></button>
        </form>

    <?php else: ?>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3><span class="dashicons dashicons-portfolio"></span> لیست پروژه‌ها</h3>
            <a href="<?php echo add_query_arg(['action' => 'new']); ?>" class="pzl-button pzl-button-primary">ایجاد پروژه جدید</a>
        </div>
        
        <?php
        $projects_query = new WP_Query([
            'post_type' => 'project',
            'posts_per_page' => 20,
            'paged' => get_query_var('paged') ? get_query_var('paged') : 1,
        ]);

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
                        $customer = get_userdata(get_the_author_meta('ID'));
                        $edit_url = add_query_arg(['action' => 'edit', 'project_id' => get_the_ID()]);

                        // Check for existing contract
                        $contract = get_posts(['post_type' => 'contract', 'meta_key' => '_project_id', 'meta_value' => get_the_ID(), 'posts_per_page' => 1]);
                        $contract_status = !empty($contract) ? '<span style="color: green;">دارد</span>' : '<span style="color: red;">ندارد</span>';
                    ?>
                    <tr>
                        <td><strong><?php the_title(); ?></strong></td>
                        <td><?php echo esc_html($customer->display_name); ?></td>
                        <td><?php echo get_the_date('Y/m/d'); ?></td>
                        <td><?php echo $contract_status; ?></td>
                        <td><a href="<?php echo esc_url($edit_url); ?>" class="pzl-button pzl-button-secondary">ویرایش</a></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php 
            // Pagination
            echo paginate_links(['total' => $projects_query->max_num_pages]);
            wp_reset_postdata(); 
            ?>
        <?php else: ?>
            <p>هیچ پروژه‌ای یافت نشد. برای شروع، یک پروژه جدید ایجاد کنید.</p>
        <?php endif; ?>
    <?php endif; ?>
</div>