<?php
/**
 * System logs (puzzlingcrm_system_logs table) with filters, AJAX, and delete all.
 * @package PuzzlingCRM
 */
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! current_user_can( 'manage_options' ) ) return;

$date_from = isset( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : '';
$date_to   = isset( $_GET['date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) : '';
$log_type  = isset( $_GET['log_type'] ) ? sanitize_text_field( wp_unslash( $_GET['log_type'] ) ) : '';
$severity  = isset( $_GET['severity'] ) ? sanitize_text_field( wp_unslash( $_GET['severity'] ) ) : '';
$user_id   = isset( $_GET['user_id'] ) ? absint( $_GET['user_id'] ) : '';
$base_url  = remove_query_arg( array( 'date_from', 'date_to', 'log_type', 'severity', 'user_id', 'paged' ) );
?>
<h4><i class="ri-settings-3-lines"></i> لاگ سیستم</h4>
<p class="description">خطاها، دیباگ، کنسول و خطاهای دکمه.</p>

<form method="get" class="pzl-form pzl-log-filters" style="margin: 1em 0;">
    <input type="hidden" name="view" value="<?php echo esc_attr( isset( $_GET['view'] ) ? sanitize_key( $_GET['view'] ) : 'logs' ); ?>" />
    <input type="hidden" name="log_tab" value="system" />
    <div class="form-row" style="display:flex; flex-wrap:wrap; gap:10px; align-items:flex-end;">
        <div>
            <label for="sl-date_from">از تاریخ</label>
            <input type="date" id="sl-date_from" name="date_from" value="<?php echo esc_attr( $date_from ); ?>" class="ltr-input" />
        </div>
        <div>
            <label for="sl-date_to">تا تاریخ</label>
            <input type="date" id="sl-date_to" name="date_to" value="<?php echo esc_attr( $date_to ); ?>" class="ltr-input" />
        </div>
        <div>
            <label for="sl-log_type">نوع</label>
            <select id="sl-log_type" name="log_type">
                <option value="">همه</option>
                <option value="error" <?php selected( $log_type, 'error' ); ?>>error</option>
                <option value="debug" <?php selected( $log_type, 'debug' ); ?>>debug</option>
                <option value="console" <?php selected( $log_type, 'console' ); ?>>console</option>
                <option value="button_error" <?php selected( $log_type, 'button_error' ); ?>>button_error</option>
            </select>
        </div>
        <div>
            <label for="sl-severity">شدت</label>
            <select id="sl-severity" name="severity">
                <option value="">همه</option>
                <option value="info" <?php selected( $severity, 'info' ); ?>>info</option>
                <option value="warning" <?php selected( $severity, 'warning' ); ?>>warning</option>
                <option value="error" <?php selected( $severity, 'error' ); ?>>error</option>
                <option value="critical" <?php selected( $severity, 'critical' ); ?>>critical</option>
            </select>
        </div>
        <div>
            <label for="sl-user_id">کاربر</label>
            <select id="sl-user_id" name="user_id">
                <option value="">همه</option>
                <?php
                $users = get_users( array( 'orderby' => 'display_name', 'number' => 500 ) );
                foreach ( $users as $u ) {
                    echo '<option value="' . esc_attr( $u->ID ) . '" ' . selected( $user_id, (string) $u->ID, false ) . '>' . esc_html( $u->display_name ) . '</option>';
                }
                ?>
            </select>
        </div>
        <div>
            <button type="submit" class="btn btn-primary">اعمال فیلتر</button>
        </div>
        <div>
            <button type="button" class="btn btn-danger" id="pzl-delete-all-system-logs">پاک کردن همه لاگ‌های سیستم</button>
        </div>
    </div>
</form>

<div id="pzl-system-logs-container">
    <table class="pzl-table" id="pzl-system-logs-table">
        <thead>
            <tr>
                <th>نوع</th>
                <th>شدت</th>
                <th>پیام</th>
                <th>فایل / خط</th>
                <th>کاربر</th>
                <th>تاریخ</th>
            </tr>
        </thead>
        <tbody id="pzl-system-logs-tbody">
            <?php
            $args  = array(
                'date_from' => $date_from,
                'date_to'   => $date_to,
                'log_type'  => $log_type,
                'severity'  => $severity,
                'user_id'   => $user_id,
                'limit'     => 50,
                'offset'    => 0,
            );
            $logs  = PuzzlingCRM_Logger::get_system_logs( $args );
            $total = PuzzlingCRM_Logger::get_system_logs_count( $args );
            if ( ! empty( $logs ) ) {
                foreach ( $logs as $log ) {
                    $user = $log->user_id ? get_userdata( $log->user_id ) : null;
                    $user_name = $user ? $user->display_name : ( $log->user_id ? '#' . $log->user_id : '—' );
                    $file_line = ( $log->file ? esc_html( $log->file ) : '' ) . ( $log->line ? ':' . $log->line : '' );
                    if ( ! $file_line ) {
                        $file_line = '—';
                    }
                    echo '<tr><td>' . esc_html( $log->log_type ) . '</td><td>' . esc_html( $log->severity ) . '</td><td>' . esc_html( wp_trim_words( $log->message, 15 ) ) . '</td><td>' . esc_html( $file_line ) . '</td><td>' . esc_html( $user_name ) . '</td><td>' . esc_html( $log->created_at ) . '</td></tr>';
                }
            } else {
                echo '<tr><td colspan="6">هیچ لاگی برای نمایش وجود ندارد.</td></tr>';
            }
            ?>
        </tbody>
    </table>
    <?php if ( $total > 50 ) : ?>
    <p class="description">نمایش ۵۰ از <?php echo (int) $total; ?> رکورد.</p>
    <?php endif; ?>
</div>

<script>
(function() {
    var deleteBtn = document.getElementById('pzl-delete-all-system-logs');
    if (deleteBtn && typeof jQuery !== 'undefined') {
        deleteBtn.addEventListener('click', function() {
            if (!confirm('همه لاگ‌های سیستم حذف شوند؟')) return;
            jQuery.post(
                typeof puzzlingcrm_ajax_obj !== 'undefined' ? puzzlingcrm_ajax_obj.ajax_url : '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>',
                {
                    action: 'puzzlingcrm_delete_system_logs',
                    security: typeof puzzlingcrm_ajax_obj !== 'undefined' ? puzzlingcrm_ajax_obj.nonce : '<?php echo esc_js( wp_create_nonce( 'puzzlingcrm-ajax-nonce' ) ); ?>'
                },
                function(res) {
                    if (res.success) {
                        location.reload();
                    } else {
                        alert(res.data && res.data.message ? res.data.message : 'خطا');
                    }
                }
            );
        });
    }
})();
</script>
