jQuery(document).ready(function($) {
    $('#add-payment-row').on('click', function() {
        var rowHtml = `
            <div class="payment-row form-group">
                <label>مبلغ (به تومان):</label>
                <input type="number" name="payment_amount[]" placeholder="مثلاً: 50000" required>
                <button type="button" class="button remove-payment-row">حذف</button>
            </div>
        `;
        $('#payment-rows-container').append(rowHtml);
    });

    // Delegated event for removing rows
    $('#payment-rows-container').on('click', '.remove-payment-row', function() {
        $(this).closest('.payment-row').remove();
    });
});