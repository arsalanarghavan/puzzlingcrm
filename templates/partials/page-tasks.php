<?php
if (!defined('ABSPATH')) exit;

// Get active tab
$active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'list';
?>
<div class="pzl-dashboard-section">
    <h3><i class="fas fa-tasks"></i> مدیریت وظایف</h3>

    <div class="pzl-dashboard-tabs">
        <a href="<?php echo remove_query_arg('tab'); ?>" class="pzl-tab <?php echo $active_tab === 'list' ? 'active' : ''; ?>"> <i class="fas fa-list-ul"></i> لیست وظایف</a>
        <a href="<?php echo add_query_arg('tab', 'new'); ?>" class="pzl-tab <?php echo $active_tab === 'new' ? 'active' : ''; ?>"> <i class="fas fa-plus"></i> افزودن وظیفه جدید</a>
    </div>

    <div class="pzl-dashboard-tab-content">
    <?php if ($active_tab === 'new'): ?>
        <div class="pzl-card">
            <?php
            // Fetch data for the form
            $staff_roles = ['system_manager', 'finance_manager', 'team_member', 'administrator'];
            $all_staff = get_users(['role__in' => $staff_roles]);
            $all_projects = get_posts(['post_type' => 'project', 'numberposts' => -1]);
            $priorities = get_terms(['taxonomy' => 'task_priority', 'hide_empty' => false]);
            ?>
            <div class="pzl-card-header">
                <h3><i class="fas fa-plus-circle"></i> افزودن وظیفه جدید</h3>
            </div>
            <form id="puzzling-add-task-form" class="pzl-form">
                <div class="pzl-form-row">
                    <div class="form-group" style="flex: 2;">
                        <label for="title">عنوان وظیفه</label>
                        <input type="text" name="title" placeholder="مثال: طراحی صفحه اصلی" required>
                    </div>
                    <div class="form-group">
                        <label for="project_id">برای پروژه</label>
                        <select name="project_id" required>
                            <option value="">-- انتخاب پروژه --</option>
                            <?php foreach ($all_projects as $project) { echo '<option value="' . esc_attr($project->ID) . '">' . esc_html($project->post_title) . '</option>'; } ?>
                        </select>
                    </div>
                </div>
                <div class="pzl-form-row">
                    <div class="form-group">
                        <label for="assigned_to">تخصیص به</label>
                        <select name="assigned_to" required>
                            <option value="">-- انتخاب کارمند --</option>
                            <?php foreach ($all_staff as $member) { echo '<option value="' . esc_attr($member->ID) . '">' . esc_html($member->display_name) . '</option>'; } ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="priority">اولویت</label>
                        <select name="priority" required>
                            <?php foreach ($priorities as $priority) { echo '<option value="' . esc_attr($priority->term_id) . '">' . esc_html($priority->name) . '</option>'; } ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="due_date">ددلاین</label>
                        <input type="date" name="due_date">
                    </div>
                </div>
                <div class="form-submit">
                    <button type="submit" class="pzl-button">افزودن وظیفه</button>
                </div>
            </form>
        </div>
    <?php else: // List View (Default) ?>
        <div class="pzl-card">
            <?php
            // Get filter parameters for the list view
            $paged = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
            $project_filter = isset($_GET['project_id']) ? intval($_GET['project_id']) : 0;
            $staff_filter = isset($_GET['staff_id']) ? intval($_GET['staff_id']) : 0;
            $status_filter = isset($_GET['status']) ? sanitize_key($_GET['status']) : '';
            $staff_roles = ['system_manager', 'finance_manager', 'team_member', 'administrator'];
            $all_staff = get_users(['role__in' => $staff_roles]);
            $all_projects = get_posts(['post_type' => 'project', 'numberposts' => -1]);
            $all_statuses = get_terms(['taxonomy' => 'task_status', 'hide_empty' => false]);
            
            $args = [
                'post_type' => 'task', 'posts_per_page' => 20, 'paged' => $paged,
                'meta_query' => ['relation' => 'AND'], 'tax_query' => ['relation' => 'AND'],
            ];
            if ($project_filter > 0) { $args['meta_query'][] = ['key' => '_project_id', 'value' => $project_filter]; }
            if ($staff_filter > 0) { $args['meta_query'][] = ['key' => '_assigned_to', 'value' => $staff_filter]; }
            if (!empty($status_filter)) { $args['tax_query'][] = ['taxonomy' => 'task_status', 'field' => 'slug', 'terms' => $status_filter]; }
            $tasks_query = new WP_Query($args);
            ?>
            <div class="pzl-card-header">
                <h3><i class="fas fa-list-ul"></i> لیست وظایف سیستم</h3>
            </div>
            <form method="get" class="pzl-form">
                <input type="hidden" name="view" value="tasks">
                <div class="pzl-form-row" style="align-items: flex-end;">
                    <div class="form-group">
                        <label>پروژه</label>
                        <select name="project_id">
                            <option value="">همه پروژه‌ها</option>
                            <?php foreach ($all_projects as $project): ?>
                                <option value="<?php echo $project->ID; ?>" <?php selected($project_filter, $project->ID); ?>><?php echo esc_html($project->post_title); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>کارمند</label>
                        <select name="staff_id">
                            <option value="">همه کارکنان</option>
                            <?php foreach ($all_staff as $staff): ?>
                                <option value="<?php echo $staff->ID; ?>" <?php selected($staff_filter, $staff->ID); ?>><?php echo esc_html($staff->display_name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>وضعیت</label>
                        <select name="status">
                            <option value="">همه وضعیت‌ها</option>
                            <?php foreach ($all_statuses as $status): ?>
                                <option value="<?php echo esc_attr($status->slug); ?>" <?php selected($status_filter, $status->slug); ?>><?php echo esc_html($status->name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="pzl-button">فیلتر</button>
                    </div>
                </div>
            </form>

            <table class="pzl-table" style="margin-top: 20px;">
                <thead><tr><th>عنوان وظیفه</th><th>پروژه</th><th>تخصیص به</th><th>وضعیت</th><th>ددلاین</th></tr></thead>
                <tbody>
                    <?php if ($tasks_query->have_posts()): while($tasks_query->have_posts()): $tasks_query->the_post();
                        $project_id = get_post_meta(get_the_ID(), '_project_id', true);
                        $assigned_id = get_post_meta(get_the_ID(), '_assigned_to', true);
                        $status_terms = get_the_terms(get_the_ID(), 'task_status');
                    ?>
                        <tr>
                            <td><?php the_title(); ?></td>
                            <td><?php echo $project_id ? get_the_title($project_id) : '---'; ?></td>
                            <td><?php echo $assigned_id ? get_the_author_meta('display_name', $assigned_id) : '---'; ?></td>
                            <td><?php echo !empty($status_terms) ? esc_html($status_terms[0]->name) : '---'; ?></td>
                            <td><?php echo get_post_meta(get_the_ID(), '_due_date', true); ?></td>
                        </tr>
                    <?php endwhile; else: ?>
                        <tr><td colspan="5">هیچ وظیفه‌ای با این فیلترها یافت نشد.</td></tr>
                    <?php endif; wp_reset_postdata(); ?>
                </tbody>
            </table>
            <div class="pagination">
                <?php echo paginate_links(['total' => $tasks_query->max_num_pages, 'current' => $paged]); ?>
            </div>
        </div>
    <?php endif; ?>
    </div>
</div>