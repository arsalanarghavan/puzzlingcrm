<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
?>
<h1><span class="dashicons dashicons-admin-users"></span> مدیریت مشتریان</h1>

<?php if ($action === 'add'): ?>
    <h2>افزودن مشتری جدید</h2>
    <p>با تکمیل فرم زیر، یک حساب کاربری جدید با نقش "مشتری" در وردپرس ایجاد می‌شود.</p>
    <form method="post" action="?page=pzl-customers">
        <?php // Add nonce and fields for creating a new user (customer role) ?>
        <p>فرم افزودن مشتری در اینجا قرار خواهد گرفت.</p>
        <button type="submit" class="button button-primary">ایجاد مشتری</button>
        <a href="?page=pzl-customers" class="button">انصراف</a>
    </form>
<?php else: ?>
    <a href="?page=pzl-customers&action=add" class="page-title-action">افزودن مشتری</a>
    <p>لیست تمام کاربرانی که نقش "مشتری" را دارند.</p>
    <?php
    $customers = get_users(['role' => 'customer']);
    // WP_List_Table should be used here for a professional look.
    // For now, a simple table:
    ?>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>نام نمایشی</th>
                <th>ایمیل</th>
                <th>تاریخ ثبت‌نام</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($customers as $customer): ?>
                <tr>
                    <td><?php echo esc_html($customer->display_name); ?></td>
                    <td><?php echo esc_html($customer->user_email); ?></td>
                    <td><?php echo esc_html(date_i18n('Y/m/d', strtotime($customer->user_registered))); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>