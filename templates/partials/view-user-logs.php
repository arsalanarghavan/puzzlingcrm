<?php
/**
 * User logs (puzzlingcrm_user_logs table) with filters and AJAX load.
 * @package PuzzlingCRM
 */
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! current_user_can( 'manage_options' ) ) return;

$date_from   = isset( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : '';
$date_to     = isset( $_GET['date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) : '';
$user_id     = isset( $_GET['user_id'] ) ? absint( $_GET['user_id'] ) : '';
$action_type = isset( $_GET['action_type'] ) ? sanitize_text_field( wp_unslash( $_GET['action_type'] ) ) : '';
$base_url    = remove_query_arg( array( 'date_from', 'date_to', 'user_id', 'action_type', 'paged' ) );
?>
<h4><i class="ri-user-line-clock"></i> لاگ اقدامات کاربر</h4>
<p class="description">کلیک دکمه، ارسال فرم، فراخوانی AJAX و مشاهده صفحه.</p>

<form method="get" class="pzl-form pzl-log-filters" style="margin: 1em 0;">
    <input type="hidden" name="view" value="<?php echo esc_attr( isset( $_GET['view'] ) ? sanitize_key( $_GET['view'] ) : 'logs' ); ?>" />
    <input type="hidden" name="log_tab" value="user" />
    <div class="form-row" style="display:flex; flex-wrap:wrap; gap:10px; align-items:flex-end;">
        <div>
            <label for="ul-date_from">از تاریخ</label>
            <input type="date" id="ul-date_from" name="date_from" value="<?php echo esc_attr( $date_from ); ?>" class="ltr-input" />
        </div>
        <div>
            <label for="ul-date_to">تا تاریخ</label>
            <input type="date" id="ul-date_to" name="date_to" value="<?php echo esc_attr( $date_to ); ?>" class="ltr-input" />
        </div>
        <div>
            <label for="ul-user_id">کاربر</label>
            <select id="ul-user_id" name="user_id">
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
            <label for="ul-action_type">نوع اقدام</label>
            <select id="ul-action_type" name="action_type">
                <option value="">همه</option>
                <option value="button_click" <?php selected( $action_type, 'button_click' ); ?>>کلیک دکمه</option>
                <option value="form_submit" <?php selected( $action_type, 'form_submit' ); ?>>ارسال فرم</option>
                <option value="ajax_call" <?php selected( $action_type, 'ajax_call' ); ?>>فراخوانی AJAX</option>
                <option value="page_view" <?php selected( $action_type, 'page_view' ); ?>>مشاهده صفحه</option>
            </select>
        </div>
        <div>
            <button type="submit" class="btn btn-primary">اعمال فیلتر</button>
        </div>
    </div>
</form>

<div id="pzl-user-logs-container">
    <table class="pzl-table" id="pzl-user-logs-table">
        <thead>
            <tr>
                <th>کاربر</th>
                <th>نوع اقدام</th>
                <th>توضیح</th>
                <th>هدف</th>
                <th>تاریخ</th>
            </tr>
        </thead>
        <tbody id="pzl-user-logs-tbody">
            <?php
            $args = array(
                'date_from'   => $date_from,
                'date_to'     => $date_to,
                'user_id'     => $user_id,
                'action_type' => $action_type,
                'limit'       => 50,
                'offset'      => 0,
            );
            $logs  = PuzzlingCRM_Logger::get_user_logs( $args );
            $total = PuzzlingCRM_Logger::get_user_logs_count( $args );
            if ( ! empty( $logs ) ) {
                foreach ( $logs as $log ) {
                    $user = get_userdata( $log->user_id );
                    $name = $user ? $user->display_name : '#' . $log->user_id;
                    $target = ( $log->target_type ? $log->target_type . ( $log->target_id ? ' #' . $log->target_id : '' ) : '—' );
                    echo '<tr><td>' . esc_html( $name ) . '</td><td>' . esc_html( $log->action_type ) . '</td><td>' . esc_html( $log->action_description ) . '</td><td>' . esc_html( $target ) . '</td><td>' . esc_html( $log->created_at ) . '</td></tr>';
                }
            } else {
                echo '<tr><td colspan="5">هیچ لاگی برای نمایش وجود ندارد.</td></tr>';
            }
            ?>
        </tbody>
    </table>
    <?php if ( $total > 50 ) : ?>
    <p class="description">نمایش ۵۰ از <?php echo (int) $total; ?> رکورد. برای مشاهده بیشتر از فیلتر تاریخ استفاده کنید.</p>
    <?php endif; ?>
</div>
