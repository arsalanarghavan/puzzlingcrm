<?php
if (!defined('ABSPATH')) exit;

// Get active tab
$active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'board'; // Default to board view
?>
<div class="pzl-dashboard-section">
    <h3><i class="fas fa-tasks"></i> مدیریت وظایف</h3>

    <div class="pzl-dashboard-tabs">
        <a href="<?php echo add_query_arg('tab', 'board', remove_query_arg(['tab', 's', 'project_id', 'staff_id', 'status'])); ?>" class="pzl-tab <?php echo $active_tab === 'board' ? 'active' : ''; ?>"> <i class="fas fa-columns"></i> نمای بُرد</a>
        <a href="<?php echo add_query_arg('tab', 'list', remove_query_arg('tab')); ?>" class="pzl-tab <?php echo $active_tab === 'list' ? 'active' : ''; ?>"> <i class="fas fa-list-ul"></i> نمای لیست</a>
        <a href="<?php echo add_query_arg('tab', 'new'); ?>" class="pzl-tab <?php echo $active_tab === 'new' ? 'active' : ''; ?>"> <i class="fas fa-plus"></i> افزودن وظیفه جدید</a>
        <a href="<?php echo add_query_arg('tab', 'workflow'); ?>" class="pzl-tab <?php echo $active_tab === 'workflow' ? 'active' : ''; ?>"> <i class="fas fa-cogs"></i> مدیریت گردش‌کار</a>
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
            $all_tasks = get_posts(['post_type' => 'task', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC']);
            ?>
            <div class="pzl-card-header">
                <h3><i class="fas fa-plus-circle"></i> افزودن وظیفه جدید</h3>
            </div>
            <form id="puzzling-add-task-form" class="pzl-form" enctype="multipart/form-data">
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
                 <div class="form-group">
                    <label for="task_content">توضیحات وظیفه (اختیاری)</label>
                    <textarea name="content" rows="5" placeholder="جزئیات کامل وظیفه را در اینجا وارد کنید..."></textarea>
                </div>
                <div class="pzl-form-row">
                     <div class="form-group">
                        <label for="parent_id">وظیفه والد (برای ایجاد وظیفه فرعی)</label>
                        <select name="parent_id">
                            <option value="0">-- این یک وظیفه اصلی است --</option>
                            <?php foreach ($all_tasks as $task) { echo '<option value="' . esc_attr($task->ID) . '">' . esc_html($task->post_title) . '</option>'; } ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="time_estimate">تخمین زمان (ساعت)</label>
                        <input type="number" name="time_estimate" min="0" step="0.5" placeholder="مثال: 8">
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
                <div class="form-group">
                    <label for="task_labels">برچسب‌ها</label>
                    <input type="text" name="task_labels" placeholder="برچسب‌ها را با کاما (,) جدا کنید">
                </div>
                 <div class="form-group">
                    <label for="task_attachments">پیوست فایل‌ها</label>
                    <input type="file" name="task_attachments[]" multiple>
                </div>
                <div class="form-submit">
                    <button type="submit" class="pzl-button">افزودن وظیفه</button>
                </div>
            </form>
        </div>
    <?php elseif($active_tab === 'list'): ?>
         <div class="pzl-card">
            <?php
            // ... (The list view code remains the same for now)
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
                <input type="hidden" name="tab" value="list">
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
                            <td><a href="#" class="open-task-modal" data-task-id="<?php echo get_the_ID(); ?>"><?php the_title(); ?></a></td>
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
    <?php elseif ($active_tab === 'workflow'): ?>
        <div class="pzl-card">
            <div class="pzl-card-header">
                <h3><i class="fas fa-cogs"></i> مدیریت وضعیت‌های گردش‌کار</h3>
            </div>
            <p>در این بخش می‌توانید ستون‌های برد کانبان (وضعیت‌های وظایف) را مدیریت کنید. می‌توانید وضعیت‌های جدید اضافه کرده یا ترتیب آن‌ها را تغییر دهید.</p>
            <div id="workflow-status-manager">
                <ul id="status-sortable-list">
                    <?php
                    $statuses = get_terms(['taxonomy' => 'task_status', 'hide_empty' => false, 'orderby' => 'term_order', 'order' => 'ASC']);
                    foreach ($statuses as $status) {
                        echo '<li data-term-id="' . esc_attr($status->term_id) . '"><i class="fas fa-grip-vertical"></i> ' . esc_html($status->name) . ' <span class="delete-status-btn" data-term-id="' . esc_attr($status->term_id) . '">&times;</span></li>';
                    }
                    ?>
                </ul>
                <form id="add-new-status-form" class="pzl-form-inline">
                    <input type="text" id="new-status-name" placeholder="نام وضعیت جدید" required>
                    <button type="submit" class="pzl-button">افزودن وضعیت</button>
                </form>
            </div>
        </div>

    <?php else: // Board View (Default) ?>
        <div class="pzl-task-board-container">
            <?php
            $statuses = get_terms(['taxonomy' => 'task_status', 'hide_empty' => false, 'orderby' => 'term_order', 'order' => 'ASC']);
            if (empty(get_term_by('slug', 'to-do', 'task_status'))) {
                PuzzlingCRM_CPT_Manager::create_default_terms();
                $statuses = get_terms(['taxonomy' => 'task_status', 'hide_empty' => false, 'orderby' => 'term_order', 'order' => 'ASC']);
            }
            ?>
            <div id="pzl-task-board">
                <?php foreach ($statuses as $status): ?>
                    <div class="pzl-task-column" data-status-slug="<?php echo esc_attr($status->slug); ?>">
                        <h4 class="pzl-column-header"><?php echo esc_html($status->name); ?></h4>
                        <div class="pzl-task-list">
                            <?php
                            $tasks_args = [
                                'post_type' => 'task',
                                'posts_per_page' => -1,
                                'post_parent' => 0, // Only show parent tasks
                                'tax_query' => [
                                    [
                                        'taxonomy' => 'task_status',
                                        'field' => 'slug',
                                        'terms' => $status->slug,
                                    ],
                                ],
                                'orderby' => 'menu_order date',
                                'order' => 'ASC',
                            ];
                            $tasks_in_column = get_posts($tasks_args);
                            foreach ($tasks_in_column as $task) {
                                echo puzzling_render_task_card($task);
                            }
                            ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
    </div>
</div>

<div id="pzl-task-modal-backdrop" style="display: none;"></div>
<div id="pzl-task-modal-wrap" style="display: none;">
    <div id="pzl-task-modal-content">
        <button id="pzl-close-modal-btn">&times;</button>
        <div id="pzl-task-modal-body">
            <div class="pzl-loader"></div>
        </div>
    </div>
</div>