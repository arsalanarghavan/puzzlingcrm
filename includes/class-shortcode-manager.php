<?php
class CSM_Shortcode_Handler {

    public function __construct() {
        add_shortcode( 'csm_dashboard', [ $this, 'render_dashboard' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
    }

    public function enqueue_assets() {
        // Enqueue styles and scripts only when the shortcode is present
        // A better approach would be to enqueue only on the specific page
        wp_enqueue_style( 'csm-admin-styles', CSM_PLUGIN_URL . 'assets/css/admin-styles.css' );
        wp_enqueue_script( 'csm-admin-scripts', CSM_PLUGIN_URL . 'assets/js/admin-scripts.js', ['jquery'], '1.0.0', true );
    }

    public function render_dashboard() {
        // Only allow admins to see this
        if ( ! current_user_can( 'manage_options' ) ) {
            return '<p>شما دسترسی لازم برای مشاهده این بخش را ندارید.</p>';
        }

        ob_start();
        ?>
        <div class="csm-dashboard-wrapper">
            <h2>مدیریت پرداخت‌های سفارشی مشتری</h2>
            <form id="csm-form" method="post">
                <?php wp_nonce_field( 'csm_process_form_nonce', 'csm_nonce' ); ?>
                <input type="hidden" name="action" value="csm_process_form">

                <div class="form-group">
                    <label for="user_id">کاربر مورد نظر را انتخاب کنید:</label>
                    <select name="user_id" id="user_id" required>
                        <option value="">یک کاربر را انتخاب کنید</option>
                        <?php
                        $users = get_users( ['role__in' => ['customer']] );
                        foreach ( $users as $user ) {
                            echo '<option value="' . esc_attr( $user->ID ) . '">' . esc_html( $user->user_email ) . ' (' . esc_html( $user->display_name ) . ')</option>';
                        }
                        ?>
                    </select>
                </div>

                <hr>

                <h4>ایجاد برنامه‌ی پرداخت جدید:</h4>
                <div id="payment-rows-container">
                    </div>
                
                <button type="button" id="add-payment-row" class="button">+ افزودن ردیف پرداخت</button>
                <hr>
                <button type="submit" class="button button-primary">ایجاد و ارسال لینک پرداخت</button>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
}