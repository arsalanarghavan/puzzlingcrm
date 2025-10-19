<?php
/**
 * Import/Export AJAX Handler
 * @package PuzzlingCRM
 */

if (!defined('ABSPATH')) exit;

class PuzzlingCRM_Import_Export_Ajax_Handler {

    public function __construct() {
        add_action('wp_ajax_puzzling_export_leads', [$this, 'export_leads']);
        add_action('wp_ajax_puzzling_export_customers', [$this, 'export_customers']);
        add_action('wp_ajax_puzzling_export_projects', [$this, 'export_projects']);
        add_action('wp_ajax_puzzling_import_leads', [$this, 'import_leads']);
        add_action('wp_ajax_puzzling_import_customers', [$this, 'import_customers']);
    }

    /**
     * Export Leads to CSV
     */
    public function export_leads() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.']);
        }

        $leads = get_posts([
            'post_type' => 'pzl_lead',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ]);

        $csv_data = [];
        $csv_data[] = ['ID', 'نام', 'ایمیل', 'تلفن', 'وضعیت', 'منبع', 'تاریخ'];

        foreach ($leads as $lead) {
            $status_terms = wp_get_post_terms($lead->ID, 'lead_status');
            $source_terms = wp_get_post_terms($lead->ID, 'lead_source');
            
            $csv_data[] = [
                $lead->ID,
                $lead->post_title,
                get_post_meta($lead->ID, '_lead_email', true),
                get_post_meta($lead->ID, '_lead_phone', true),
                !empty($status_terms) ? $status_terms[0]->name : '',
                !empty($source_terms) ? $source_terms[0]->name : '',
                get_the_date('Y-m-d', $lead)
            ];
        }

        // Create CSV
        $filename = 'leads-export-' . date('Y-m-d') . '.csv';
        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['path'] . '/' . $filename;

        $fp = fopen($file_path, 'w');
        fprintf($fp, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
        
        foreach ($csv_data as $row) {
            fputcsv($fp, $row);
        }
        
        fclose($fp);

        wp_send_json_success([
            'message' => count($leads) . ' سرنخ صادر شد.',
            'file_url' => $upload_dir['url'] . '/' . $filename,
            'filename' => $filename
        ]);
    }

    /**
     * Export Customers to CSV
     */
    public function export_customers() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.']);
        }

        $customers = get_users(['role__in' => ['customer', 'client', 'subscriber']]);

        $csv_data = [];
        $csv_data[] = ['ID', 'نام', 'ایمیل', 'تلفن', 'تاریخ عضویت'];

        foreach ($customers as $customer) {
            $csv_data[] = [
                $customer->ID,
                $customer->display_name,
                $customer->user_email,
                get_user_meta($customer->ID, 'mobile_phone', true),
                date('Y-m-d', strtotime($customer->user_registered))
            ];
        }

        // Create CSV
        $filename = 'customers-export-' . date('Y-m-d') . '.csv';
        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['path'] . '/' . $filename;

        $fp = fopen($file_path, 'w');
        fprintf($fp, chr(0xEF).chr(0xBB).chr(0xBF));
        
        foreach ($csv_data as $row) {
            fputcsv($fp, $row);
        }
        
        fclose($fp);

        wp_send_json_success([
            'message' => count($customers) . ' مشتری صادر شد.',
            'file_url' => $upload_dir['url'] . '/' . $filename,
            'filename' => $filename
        ]);
    }

    /**
     * Export Projects to CSV
     */
    public function export_projects() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.']);
        }

        $projects = get_posts([
            'post_type' => 'project',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ]);

        $csv_data = [];
        $csv_data[] = ['ID', 'عنوان', 'مشتری', 'وضعیت', 'تاریخ شروع'];

        foreach ($projects as $project) {
            $customer_id = get_post_meta($project->ID, '_customer_id', true);
            $customer = get_user_by('id', $customer_id);
            $status_terms = wp_get_post_terms($project->ID, 'project_status');
            
            $csv_data[] = [
                $project->ID,
                $project->post_title,
                $customer ? $customer->display_name : '',
                !empty($status_terms) ? $status_terms[0]->name : '',
                get_the_date('Y-m-d', $project)
            ];
        }

        // Create CSV
        $filename = 'projects-export-' . date('Y-m-d') . '.csv';
        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['path'] . '/' . $filename;

        $fp = fopen($file_path, 'w');
        fprintf($fp, chr(0xEF).chr(0xBB).chr(0xBF));
        
        foreach ($csv_data as $row) {
            fputcsv($fp, $row);
        }
        
        fclose($fp);

        wp_send_json_success([
            'message' => count($projects) . ' پروژه صادر شد.',
            'file_url' => $upload_dir['url'] . '/' . $filename,
            'filename' => $filename
        ]);
    }

    /**
     * Import Leads from CSV
     */
    public function import_leads() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.']);
        }

        if (empty($_FILES['import_file'])) {
            wp_send_json_error(['message' => 'فایلی انتخاب نشده است.']);
        }

        $file = $_FILES['import_file']['tmp_name'];
        
        if (!file_exists($file)) {
            wp_send_json_error(['message' => 'فایل یافت نشد.']);
        }

        $handle = fopen($file, 'r');
        if (!$handle) {
            wp_send_json_error(['message' => 'خطا در خواندن فایل.']);
        }

        $imported = 0;
        $errors = [];
        
        // Skip header
        fgetcsv($handle);
        
        while (($data = fgetcsv($handle)) !== FALSE) {
            if (count($data) < 3) continue; // Need at least name, email, phone
            
            $name = sanitize_text_field($data[0]);
            $email = sanitize_email($data[1]);
            $phone = sanitize_text_field($data[2]);
            $source = isset($data[3]) ? sanitize_text_field($data[3]) : '';
            
            if (empty($name)) {
                $errors[] = 'ردیف ' . ($imported + 1) . ': نام خالی است';
                continue;
            }

            $post_id = wp_insert_post([
                'post_type' => 'pzl_lead',
                'post_title' => $name,
                'post_status' => 'publish'
            ]);

            if (!is_wp_error($post_id)) {
                update_post_meta($post_id, '_lead_email', $email);
                update_post_meta($post_id, '_lead_phone', $phone);
                
                if ($source) {
                    wp_set_post_terms($post_id, [$source], 'lead_source');
                }
                
                $imported++;
            } else {
                $errors[] = 'خطا در ایجاد: ' . $name;
            }
        }
        
        fclose($handle);

        wp_send_json_success([
            'message' => $imported . ' سرنخ وارد شد.',
            'imported' => $imported,
            'errors' => $errors
        ]);
    }

    /**
     * Import Customers from CSV
     */
    public function import_customers() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.']);
        }

        if (empty($_FILES['import_file'])) {
            wp_send_json_error(['message' => 'فایلی انتخاب نشده است.']);
        }

        // Implementation similar to import_leads
        wp_send_json_success(['message' => 'قابلیت وارد کردن مشتریان به زودی اضافه خواهد شد.']);
    }
}

