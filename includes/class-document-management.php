<?php
/**
 * Document Management System Handler
 * Comprehensive document management with version control
 *
 * @package PuzzlingCRM
 * @subpackage PuzzlingCRM/includes
 * @author     Arsalan Arghavan
 */

class PuzzlingCRM_Document_Management {
    
    private $table_name;
    private $versions_table;
    private $permissions_table;
    private $upload_dir;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'puzzling_documents';
        $this->versions_table = $wpdb->prefix . 'puzzling_document_versions';
        $this->permissions_table = $wpdb->prefix . 'puzzling_document_permissions';
        
        $this->upload_dir = wp_upload_dir()['basedir'] . '/puzzlingcrm-documents/';
        
        add_action('wp_ajax_puzzling_upload_document', [$this, 'upload_document']);
        add_action('wp_ajax_puzzling_get_documents', [$this, 'get_documents']);
        add_action('wp_ajax_puzzling_get_document', [$this, 'get_document']);
        add_action('wp_ajax_puzzling_update_document', [$this, 'update_document']);
        add_action('wp_ajax_puzzling_delete_document', [$this, 'delete_document']);
        add_action('wp_ajax_puzzling_share_document', [$this, 'share_document']);
        add_action('wp_ajax_puzzling_download_document', [$this, 'download_document']);
        add_action('wp_ajax_puzzling_search_documents', [$this, 'search_documents']);
        
        // Create upload directory
        $this->create_upload_directory();
    }
    
    public function upload_document() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $project_id = intval($_POST['project_id'] ?? 0);
        $task_id = intval($_POST['task_id'] ?? 0);
        $category = sanitize_text_field($_POST['category'] ?? 'general');
        $description = sanitize_textarea_field($_POST['description'] ?? '');
        $is_private = isset($_POST['is_private']) ? (bool) $_POST['is_private'] : false;
        $tags = $_POST['tags'] ?? [];
        
        if (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error('خطا در آپلود فایل');
        }
        
        $file = $_FILES['document'];
        $allowed_types = $this->get_allowed_file_types();
        $file_type = wp_check_filetype($file['name']);
        
        if (!in_array($file_type['type'], $allowed_types)) {
            wp_send_json_error('نوع فایل مجاز نیست');
        }
        
        $max_size = 50 * 1024 * 1024; // 50MB
        if ($file['size'] > $max_size) {
            wp_send_json_error('حجم فایل بیش از حد مجاز است');
        }
        
        $file_name = sanitize_file_name($file['name']);
        $file_extension = $file_type['ext'];
        $file_name_without_ext = pathinfo($file_name, PATHINFO_FILENAME);
        $unique_name = $file_name_without_ext . '_' . time() . '_' . wp_generate_password(8, false) . '.' . $file_extension;
        
        $upload_path = $this->upload_dir . $unique_name;
        
        if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
            wp_send_json_error('خطا در ذخیره فایل');
        }
        
        $document_id = $this->create_document([
            'user_id' => $user_id,
            'project_id' => $project_id,
            'task_id' => $task_id,
            'original_name' => $file_name,
            'file_name' => $unique_name,
            'file_path' => $upload_path,
            'file_size' => $file['size'],
            'file_type' => $file_type['type'],
            'file_extension' => $file_extension,
            'category' => $category,
            'description' => $description,
            'is_private' => $is_private,
            'tags' => $tags,
            'version' => 1
        ]);
        
        if ($document_id) {
            wp_send_json_success([
                'document_id' => $document_id,
                'message' => 'سند با موفقیت آپلود شد'
            ]);
        } else {
            wp_send_json_error('خطا در ذخیره اطلاعات سند');
        }
    }
    
    public function get_documents() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $project_id = intval($_GET['project_id'] ?? 0);
        $task_id = intval($_GET['task_id'] ?? 0);
        $category = sanitize_text_field($_GET['category'] ?? '');
        $search = sanitize_text_field($_GET['search'] ?? '');
        $page = intval($_GET['page'] ?? 1);
        $per_page = intval($_GET['per_page'] ?? 20);
        
        $documents = $this->query_documents([
            'user_id' => $user_id,
            'project_id' => $project_id,
            'task_id' => $task_id,
            'category' => $category,
            'search' => $search,
            'page' => $page,
            'per_page' => $per_page
        ]);
        
        wp_send_json_success($documents);
    }
    
    public function get_document() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'nonce');
        
        $document_id = intval($_GET['document_id'] ?? 0);
        $user_id = get_current_user_id();
        
        $document = $this->get_document_by_id($document_id);
        if (!$document) {
            wp_send_json_error('سند یافت نشد');
        }
        
        if (!$this->can_access_document($document, $user_id)) {
            wp_die('Insufficient permissions', 'Forbidden', ['response' => 403]);
        }
        
        $formatted_document = $this->format_document($document);
        wp_send_json_success($formatted_document);
    }
    
    public function update_document() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'nonce');
        
        $document_id = intval($_POST['document_id'] ?? 0);
        $user_id = get_current_user_id();
        
        $document = $this->get_document_by_id($document_id);
        if (!$document) {
            wp_send_json_error('سند یافت نشد');
        }
        
        if (!$this->can_edit_document($document, $user_id)) {
            wp_die('Insufficient permissions', 'Forbidden', ['response' => 403]);
        }
        
        $update_data = [];
        
        if (isset($_POST['description'])) {
            $update_data['description'] = sanitize_textarea_field($_POST['description']);
        }
        
        if (isset($_POST['category'])) {
            $update_data['category'] = sanitize_text_field($_POST['category']);
        }
        
        if (isset($_POST['tags'])) {
            $update_data['tags'] = json_encode($_POST['tags']);
        }
        
        if (isset($_POST['is_private'])) {
            $update_data['is_private'] = (bool) $_POST['is_private'];
        }
        
        $result = $this->update_document_data($document_id, $update_data);
        
        if ($result) {
            wp_send_json_success(['message' => 'سند با موفقیت به‌روزرسانی شد']);
        } else {
            wp_send_json_error('خطا در به‌روزرسانی سند');
        }
    }
    
    public function delete_document() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'nonce');
        
        $document_id = intval($_POST['document_id'] ?? 0);
        $user_id = get_current_user_id();
        
        $document = $this->get_document_by_id($document_id);
        if (!$document) {
            wp_send_json_error('سند یافت نشد');
        }
        
        if (!$this->can_delete_document($document, $user_id)) {
            wp_die('Insufficient permissions', 'Forbidden', ['response' => 403]);
        }
        
        // Delete file
        if (file_exists($document->file_path)) {
            unlink($document->file_path);
        }
        
        // Delete document
        $result = $this->delete_document_data($document_id);
        
        if ($result) {
            wp_send_json_success(['message' => 'سند با موفقیت حذف شد']);
        } else {
            wp_send_json_error('خطا در حذف سند');
        }
    }
    
    public function share_document() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'nonce');
        
        $document_id = intval($_POST['document_id'] ?? 0);
        $user_id = get_current_user_id();
        $share_with = $_POST['share_with'] ?? [];
        $permission_level = sanitize_text_field($_POST['permission_level'] ?? 'view');
        
        $document = $this->get_document_by_id($document_id);
        if (!$document) {
            wp_send_json_error('سند یافت نشد');
        }
        
        if (!$this->can_share_document($document, $user_id)) {
            wp_die('Insufficient permissions', 'Forbidden', ['response' => 403]);
        }
        
        $shared_count = 0;
        foreach ($share_with as $target_user_id) {
            $result = $this->create_document_permission([
                'document_id' => $document_id,
                'user_id' => intval($target_user_id),
                'permission_level' => $permission_level,
                'shared_by' => $user_id
            ]);
            
            if ($result) {
                $shared_count++;
            }
        }
        
        if ($shared_count > 0) {
            wp_send_json_success([
                'shared_count' => $shared_count,
                'message' => "سند با {$shared_count} کاربر به اشتراک گذاشته شد"
            ]);
        } else {
            wp_send_json_error('خطا در به اشتراک گذاری سند');
        }
    }
    
    public function download_document() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'nonce');
        
        $document_id = intval($_GET['document_id'] ?? 0);
        $user_id = get_current_user_id();
        
        $document = $this->get_document_by_id($document_id);
        if (!$document) {
            wp_die('سند یافت نشد', 'Not Found', ['response' => 404]);
        }
        
        if (!$this->can_access_document($document, $user_id)) {
            wp_die('Insufficient permissions', 'Forbidden', ['response' => 403]);
        }
        
        if (!file_exists($document->file_path)) {
            wp_die('فایل یافت نشد', 'Not Found', ['response' => 404]);
        }
        
        // Update download count
        $this->update_document_data($document_id, [
            'download_count' => $document->download_count + 1
        ]);
        
        // Set headers for download
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $document->original_name . '"');
        header('Content-Length: ' . filesize($document->file_path));
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        
        readfile($document->file_path);
        exit;
    }
    
    public function search_documents() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $query = sanitize_text_field($_GET['query'] ?? '');
        $filters = $_GET['filters'] ?? [];
        
        $documents = $this->search_documents_by_query($user_id, $query, $filters);
        
        wp_send_json_success($documents);
    }
    
    private function create_upload_directory() {
        if (!file_exists($this->upload_dir)) {
            wp_mkdir_p($this->upload_dir);
        }
        
        // Create .htaccess for security
        $htaccess_content = "Options -Indexes\n";
        $htaccess_content .= "deny from all\n";
        $htaccess_content .= "<Files ~ \"\\.(pdf|doc|docx|xls|xlsx|ppt|pptx|txt|jpg|jpeg|png|gif)$\">\n";
        $htaccess_content .= "    allow from all\n";
        $htaccess_content .= "</Files>\n";
        
        file_put_contents($this->upload_dir . '.htaccess', $htaccess_content);
    }
    
    private function get_allowed_file_types() {
        return [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'text/plain',
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp'
        ];
    }
    
    private function create_document($data) {
        global $wpdb;
        
        $result = $wpdb->insert(
            $this->table_name,
            [
                'user_id' => $data['user_id'],
                'project_id' => $data['project_id'],
                'task_id' => $data['task_id'],
                'original_name' => $data['original_name'],
                'file_name' => $data['file_name'],
                'file_path' => $data['file_path'],
                'file_size' => $data['file_size'],
                'file_type' => $data['file_type'],
                'file_extension' => $data['file_extension'],
                'category' => $data['category'],
                'description' => $data['description'],
                'is_private' => $data['is_private'] ? 1 : 0,
                'tags' => json_encode($data['tags']),
                'version' => $data['version'],
                'download_count' => 0,
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ],
            ['%d', '%d', '%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%d', '%s', '%d', '%d', '%s', '%s']
        );
        
        return $result ? $wpdb->insert_id : false;
    }
    
    private function create_document_permission($data) {
        global $wpdb;
        
        $result = $wpdb->insert(
            $this->permissions_table,
            [
                'document_id' => $data['document_id'],
                'user_id' => $data['user_id'],
                'permission_level' => $data['permission_level'],
                'shared_by' => $data['shared_by'],
                'created_at' => current_time('mysql')
            ],
            ['%d', '%d', '%s', '%d', '%s']
        );
        
        return $result ? $wpdb->insert_id : false;
    }
    
    private function query_documents($args = []) {
        global $wpdb;
        
        $defaults = [
            'user_id' => 0,
            'project_id' => 0,
            'task_id' => 0,
            'category' => '',
            'search' => '',
            'page' => 1,
            'per_page' => 20
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        $where_conditions = ['1=1'];
        $where_values = [];
        
        if ($args['user_id'] > 0) {
            $where_conditions[] = '(user_id = %d OR id IN (SELECT document_id FROM ' . $this->permissions_table . ' WHERE user_id = %d))';
            $where_values[] = $args['user_id'];
            $where_values[] = $args['user_id'];
        }
        
        if ($args['project_id'] > 0) {
            $where_conditions[] = 'project_id = %d';
            $where_values[] = $args['project_id'];
        }
        
        if ($args['task_id'] > 0) {
            $where_conditions[] = 'task_id = %d';
            $where_values[] = $args['task_id'];
        }
        
        if (!empty($args['category'])) {
            $where_conditions[] = 'category = %s';
            $where_values[] = $args['category'];
        }
        
        if (!empty($args['search'])) {
            $where_conditions[] = '(original_name LIKE %s OR description LIKE %s)';
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        // Get total count
        $count_query = "SELECT COUNT(*) FROM {$this->table_name} WHERE {$where_clause}";
        if (!empty($where_values)) {
            $count_query = $wpdb->prepare($count_query, $where_values);
        }
        $total = $wpdb->get_var($count_query);
        
        // Get documents
        $offset = ($args['page'] - 1) * $args['per_page'];
        $query = "SELECT * FROM {$this->table_name} WHERE {$where_clause} ORDER BY created_at DESC LIMIT %d OFFSET %d";
        
        $query_values = array_merge($where_values, [$args['per_page'], $offset]);
        $query = $wpdb->prepare($query, $query_values);
        
        $documents = $wpdb->get_results($query);
        
        // Format documents
        $formatted_documents = [];
        foreach ($documents as $document) {
            $formatted_documents[] = $this->format_document($document);
        }
        
        return [
            'documents' => $formatted_documents,
            'total' => intval($total),
            'page' => $args['page'],
            'per_page' => $args['per_page'],
            'total_pages' => ceil($total / $args['per_page'])
        ];
    }
    
    private function format_document($document) {
        $user = get_user_by('ID', $document->user_id);
        $project = $document->project_id ? get_post($document->project_id) : null;
        $task = $document->task_id ? get_post($document->task_id) : null;
        
        return [
            'id' => $document->id,
            'user_id' => $document->user_id,
            'user_name' => $user ? $user->display_name : 'کاربر ناشناس',
            'project_id' => $document->project_id,
            'project_title' => $project ? $project->post_title : '',
            'task_id' => $document->task_id,
            'task_title' => $task ? $task->post_title : '',
            'original_name' => $document->original_name,
            'file_name' => $document->file_name,
            'file_size' => $document->file_size,
            'formatted_file_size' => $this->format_file_size($document->file_size),
            'file_type' => $document->file_type,
            'file_extension' => $document->file_extension,
            'category' => $document->category,
            'category_label' => $this->get_category_label($document->category),
            'description' => $document->description,
            'is_private' => (bool) $document->is_private,
            'tags' => json_decode($document->tags, true) ?: [],
            'version' => $document->version,
            'download_count' => $document->download_count,
            'created_at' => $document->created_at,
            'updated_at' => $document->updated_at,
            'formatted_created_at' => $this->format_datetime($document->created_at),
            'formatted_updated_at' => $this->format_datetime($document->updated_at),
            'time_ago' => $this->get_time_ago($document->created_at),
            'file_icon' => $this->get_file_icon($document->file_extension),
            'can_edit' => $this->can_edit_document($document, get_current_user_id()),
            'can_delete' => $this->can_delete_document($document, get_current_user_id()),
            'can_share' => $this->can_share_document($document, get_current_user_id())
        ];
    }
    
    private function get_document_by_id($document_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            $document_id
        ));
    }
    
    private function can_access_document($document, $user_id) {
        // Owner can always access
        if ($document->user_id == $user_id) {
            return true;
        }
        
        // Check if user has permission
        global $wpdb;
        $permission = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->permissions_table} 
             WHERE document_id = %d AND user_id = %d",
            $document->id,
            $user_id
        ));
        
        return $permission !== null;
    }
    
    private function can_edit_document($document, $user_id) {
        // Owner can always edit
        if ($document->user_id == $user_id) {
            return true;
        }
        
        // Check if user has edit permission
        global $wpdb;
        $permission = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->permissions_table} 
             WHERE document_id = %d AND user_id = %d 
             AND permission_level IN ('edit', 'admin')",
            $document->id,
            $user_id
        ));
        
        return $permission !== null;
    }
    
    private function can_delete_document($document, $user_id) {
        // Only owner can delete
        return $document->user_id == $user_id;
    }
    
    private function can_share_document($document, $user_id) {
        // Owner can always share
        if ($document->user_id == $user_id) {
            return true;
        }
        
        // Check if user has admin permission
        global $wpdb;
        $permission = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->permissions_table} 
             WHERE document_id = %d AND user_id = %d 
             AND permission_level = 'admin'",
            $document->id,
            $user_id
        ));
        
        return $permission !== null;
    }
    
    private function get_category_label($category) {
        $labels = [
            'general' => 'عمومی',
            'contract' => 'قرارداد',
            'proposal' => 'پیشنهاد',
            'report' => 'گزارش',
            'presentation' => 'ارائه',
            'image' => 'تصویر',
            'other' => 'سایر'
        ];
        
        return $labels[$category] ?? $category;
    }
    
    private function format_file_size($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
    
    private function format_datetime($datetime) {
        return date('Y/m/d H:i', strtotime($datetime));
    }
    
    private function get_time_ago($datetime) {
        $time = time() - strtotime($datetime);
        
        if ($time < 60) {
            return 'همین الان';
        } elseif ($time < 3600) {
            return floor($time / 60) . ' دقیقه پیش';
        } elseif ($time < 86400) {
            return floor($time / 3600) . ' ساعت پیش';
        } else {
            return floor($time / 86400) . ' روز پیش';
        }
    }
    
    private function get_file_icon($extension) {
        $icons = [
            'pdf' => 'ri-file-pdf-line',
            'doc' => 'ri-file-word-line',
            'docx' => 'ri-file-word-line',
            'xls' => 'ri-file-excel-line',
            'xlsx' => 'ri-file-excel-line',
            'ppt' => 'ri-file-ppt-line',
            'pptx' => 'ri-file-ppt-line',
            'txt' => 'ri-file-text-line',
            'jpg' => 'ri-image-line',
            'jpeg' => 'ri-image-line',
            'png' => 'ri-image-line',
            'gif' => 'ri-image-line',
            'webp' => 'ri-image-line'
        ];
        
        return $icons[$extension] ?? 'ri-file-line';
    }
    
    private function update_document_data($document_id, $data) {
        global $wpdb;
        
        $format = [];
        foreach ($data as $key => $value) {
            if (in_array($key, ['user_id', 'project_id', 'task_id', 'file_size', 'is_private', 'version', 'download_count'])) {
                $format[] = '%d';
            } else {
                $format[] = '%s';
            }
        }
        
        return $wpdb->update(
            $this->table_name,
            $data,
            ['id' => $document_id],
            $format,
            ['%d']
        );
    }
    
    private function delete_document_data($document_id) {
        global $wpdb;
        
        return $wpdb->delete(
            $this->table_name,
            ['id' => $document_id],
            ['%d']
        );
    }
    
    private function search_documents_by_query($user_id, $query, $filters) {
        // Simple search implementation
        return $this->query_documents([
            'user_id' => $user_id,
            'search' => $query,
            'per_page' => 50
        ]);
    }
    
    public static function create_document_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Documents table
        $documents_table = $wpdb->prefix . 'puzzling_documents';
        $documents_sql = "CREATE TABLE $documents_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            user_id int(11) NOT NULL,
            project_id int(11) DEFAULT 0,
            task_id int(11) DEFAULT 0,
            original_name varchar(255) NOT NULL,
            file_name varchar(255) NOT NULL,
            file_path varchar(500) NOT NULL,
            file_size int(11) NOT NULL,
            file_type varchar(100) NOT NULL,
            file_extension varchar(10) NOT NULL,
            category varchar(50) DEFAULT 'general',
            description text,
            is_private tinyint(1) DEFAULT 0,
            tags text,
            version int(11) DEFAULT 1,
            download_count int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY project_id (project_id),
            KEY task_id (task_id),
            KEY category (category),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // Document versions table
        $versions_table = $wpdb->prefix . 'puzzling_document_versions';
        $versions_sql = "CREATE TABLE $versions_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            document_id int(11) NOT NULL,
            file_name varchar(255) NOT NULL,
            file_path varchar(500) NOT NULL,
            file_size int(11) NOT NULL,
            version_number int(11) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY document_id (document_id),
            KEY version_number (version_number)
        ) $charset_collate;";
        
        // Document permissions table
        $permissions_table = $wpdb->prefix . 'puzzling_document_permissions';
        $permissions_sql = "CREATE TABLE $permissions_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            document_id int(11) NOT NULL,
            user_id int(11) NOT NULL,
            permission_level varchar(20) NOT NULL,
            expires_at datetime NULL,
            shared_by int(11) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY document_id (document_id),
            KEY user_id (user_id),
            KEY permission_level (permission_level)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($documents_sql);
        dbDelta($versions_sql);
        dbDelta($permissions_sql);
    }
}
