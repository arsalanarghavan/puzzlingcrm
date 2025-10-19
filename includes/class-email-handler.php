<?php
/**
 * Email Handler - Send Contracts, Invoices, Notifications
 * @package PuzzlingCRM
 */

if (!defined('ABSPATH')) exit;

class PuzzlingCRM_Email_Handler {

    /**
     * Send Contract Email
     */
    public static function send_contract_email($contract_id, $customer_email = null) {
        $contract = get_post($contract_id);
        if (!$contract) {
            return false;
        }

        // Get customer
        $customer_id = get_post_meta($contract_id, '_customer_id', true);
        if (!$customer_email && $customer_id) {
            $customer = get_user_by('id', $customer_id);
            $customer_email = $customer ? $customer->user_email : null;
        }

        if (!$customer_email) {
            return false;
        }

        // Generate PDF
        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/class-pdf-generator.php';
        $pdf = new PuzzlingCRM_PDF_Generator();
        $pdf_result = $pdf->generate_contract_pdf($contract_id);

        if (!$pdf_result) {
            return false;
        }

        // Email content
        $subject = 'قرارداد همکاری - ' . get_option('blogname');
        $message = self::get_contract_email_template($contract, $pdf_result['url']);

        // Send email
        $headers = ['Content-Type: text/html; charset=UTF-8'];
        
        // Attach PDF
        $attachments = [$pdf_result['path']];

        $sent = wp_mail($customer_email, $subject, $message, $headers, $attachments);

        return $sent;
    }

    /**
     * Send Invoice Email
     */
    public static function send_invoice_email($invoice_id, $customer_email = null) {
        $invoice = get_post($invoice_id);
        if (!$invoice) {
            return false;
        }

        // Get customer
        if (!$customer_email) {
            $customer = get_user_by('id', $invoice->post_author);
            $customer_email = $customer ? $customer->user_email : null;
        }

        if (!$customer_email) {
            return false;
        }

        // Generate PDF
        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/class-pdf-generator.php';
        $pdf = new PuzzlingCRM_PDF_Generator();
        $pdf_result = $pdf->generate_invoice_pdf($invoice_id);

        if (!$pdf_result) {
            return false;
        }

        // Email content
        $invoice_number = get_post_meta($invoice_id, '_pro_invoice_number', true);
        $subject = 'پیش‌فاکتور شماره ' . $invoice_number . ' - ' . get_option('blogname');
        $message = self::get_invoice_email_template($invoice, $pdf_result['url'], $invoice_number);

        // Send email
        $headers = ['Content-Type: text/html; charset=UTF-8'];
        $attachments = [$pdf_result['path']];

        $sent = wp_mail($customer_email, $subject, $message, $headers, $attachments);

        return $sent;
    }

    /**
     * Contract Email Template
     */
    private static function get_contract_email_template($contract, $pdf_url) {
        $company_name = get_option('blogname');
        
        ob_start();
        ?>
        <!DOCTYPE html>
        <html dir="rtl" lang="fa">
        <head>
            <meta charset="UTF-8">
        </head>
        <body style="font-family: Tahoma, Arial, sans-serif; direction: rtl; text-align: right; background: #f5f5f5; padding: 20px;">
            <div style="max-width: 600px; margin: 0 auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                <div style="background: linear-gradient(135deg, #845adf 0%, #9575de 100%); color: #fff; padding: 30px; text-align: center;">
                    <h1 style="margin: 0; font-size: 24px;">قرارداد همکاری</h1>
                    <p style="margin: 10px 0 0 0; opacity: 0.9;"><?php echo esc_html($company_name); ?></p>
                </div>
                
                <div style="padding: 30px;">
                    <p style="font-size: 16px; line-height: 1.8; color: #333;">
                        با سلام و احترام،
                    </p>
                    
                    <p style="font-size: 14px; line-height: 1.8; color: #555;">
                        قرارداد همکاری شما با عنوان <strong><?php echo esc_html($contract->post_title); ?></strong> آماده شده است.
                    </p>
                    
                    <p style="font-size: 14px; line-height: 1.8; color: #555;">
                        لطفاً فایل پیوست را مطالعه فرمایید و در صورت نیاز به توضیحات بیشتر با ما تماس بگیرید.
                    </p>
                    
                    <div style="text-align: center; margin: 30px 0;">
                        <a href="<?php echo esc_url($pdf_url); ?>" 
                           style="display: inline-block; background: #28a745; color: #fff; padding: 12px 30px; border-radius: 6px; text-decoration: none; font-weight: bold;">
                            دانلود فایل PDF قرارداد
                        </a>
                    </div>
                    
                    <hr style="border: none; border-top: 1px solid #eee; margin: 30px 0;">
                    
                    <p style="font-size: 12px; color: #999; text-align: center;">
                        این ایمیل به صورت خودکار از سیستم <?php echo esc_html($company_name); ?> ارسال شده است.
                    </p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    /**
     * Invoice Email Template
     */
    private static function get_invoice_email_template($invoice, $pdf_url, $invoice_number) {
        $company_name = get_option('blogname');
        
        ob_start();
        ?>
        <!DOCTYPE html>
        <html dir="rtl" lang="fa">
        <head>
            <meta charset="UTF-8">
        </head>
        <body style="font-family: Tahoma, Arial, sans-serif; direction: rtl; text-align: right; background: #f5f5f5; padding: 20px;">
            <div style="max-width: 600px; margin: 0 auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                <div style="background: linear-gradient(135deg, #17a2b8 0%, #138496 100%); color: #fff; padding: 30px; text-align: center;">
                    <h1 style="margin: 0; font-size: 24px;">پیش‌فاکتور</h1>
                    <p style="margin: 10px 0 0 0; opacity: 0.9;">شماره: <?php echo esc_html($invoice_number); ?></p>
                </div>
                
                <div style="padding: 30px;">
                    <p style="font-size: 16px; line-height: 1.8; color: #333;">
                        با سلام،
                    </p>
                    
                    <p style="font-size: 14px; line-height: 1.8; color: #555;">
                        پیش‌فاکتور خدمات شما آماده شده است. لطفاً فایل پیوست را مشاهده فرمایید.
                    </p>
                    
                    <div style="text-align: center; margin: 30px 0;">
                        <a href="<?php echo esc_url($pdf_url); ?>" 
                           style="display: inline-block; background: #845adf; color: #fff; padding: 12px 30px; border-radius: 6px; text-decoration: none; font-weight: bold;">
                            دانلود پیش‌فاکتور PDF
                        </a>
                    </div>
                    
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 6px; border-right: 4px solid #845adf; margin: 20px 0;">
                        <p style="margin: 0; font-size: 13px; color: #555;">
                            <strong>توجه:</strong> این پیش‌فاکتور صرفاً جهت اطلاع بوده و به منزله صدور فاکتور رسمی نیست.
                        </p>
                    </div>
                    
                    <hr style="border: none; border-top: 1px solid #eee; margin: 30px 0;">
                    
                    <p style="font-size: 12px; color: #999; text-align: center;">
                        <?php echo esc_html($company_name); ?> | این ایمیل به صورت خودکار ارسال شده است
                    </p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
}

