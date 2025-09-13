// Add this to the __construct method:
// add_action( 'init', [ $this, 'handle_edit_contract_form' ] );

public function handle_edit_contract_form() {
    if ( ! isset( $_POST['submit_edit_contract'] ) || ! isset( $_POST['_wpnonce'] ) || ! isset( $_POST['contract_id'] ) ) {
        return;
    }

    $contract_id = intval($_POST['contract_id']);
    if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'puzzling_edit_contract_' . $contract_id ) ) {
        $this->redirect_with_notice('security_failed');
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        $this->redirect_with_notice('permission_denied');
    }

    $payment_amounts = isset($_POST['payment_amount']) ? (array) $_POST['payment_amount'] : [];
    $payment_due_dates = isset($_POST['payment_due_date']) ? (array) $_POST['payment_due_date'] : [];
    $payment_statuses = isset($_POST['payment_status']) ? (array) $_POST['payment_status'] : [];

    if ( empty($payment_amounts) || count($payment_amounts) !== count($payment_due_dates) ) {
        $this->redirect_with_notice('contract_error_data_invalid');
    }

    $installments = [];
    for ($i = 0; $i < count($payment_amounts); $i++) {
        if ( !empty($payment_amounts[$i]) && !empty($payment_due_dates[$i]) ) {
             $installments[] = [
                'amount'   => sanitize_text_field($payment_amounts[$i]),
                'due_date' => sanitize_text_field($payment_due_dates[$i]),
                'status'   => sanitize_text_field($payment_statuses[$i] ?? 'pending'),
                'ref_id'   => '', // This should be preserved from the old data
            ];
        }
    }
    
    if(empty($installments)){
         $this->redirect_with_notice('contract_error_no_installments');
    }
    
    update_post_meta($contract_id, '_installments', $installments);
    
    wp_redirect(add_query_arg('puzzling_notice', 'contract_updated_success', wp_get_referer()));
    exit;
}