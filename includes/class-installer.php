<?php
/**
 * PuzzlingCRM Installer
 *
 * This class handles all the tasks that need to be run when the plugin is activated or deactivated.
 *
 * @package PuzzlingCRM
 */

class PuzzlingCRM_Installer {

    /**
     * Activation hook callback.
     */
    public static function activate() {
        // Call role creation
        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/class-roles-manager.php';
        $roles_manager = new PuzzlingCRM_Roles_Manager();
        $roles_manager->add_custom_roles();

        // Call CPT registration to make them available
        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/class-cpt-manager.php';
        $cpt_manager = new PuzzlingCRM_CPT_Manager();
        $cpt_manager->register_post_types();
        $cpt_manager->register_taxonomies();
        
        // Create default terms for taxonomies
        PuzzlingCRM_CPT_Manager::create_default_terms();

        // Add login page rewrite rules
        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/class-login-page.php';
        PuzzlingCRM_Login_Page::activate();

        // Add dashboard rewrite rules
        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/class-dashboard-router.php';
        PuzzlingCRM_Dashboard_Router::activate();

        // Create database tables for new features
        self::create_database_tables();

        // Flush rewrite rules to make CPT URLs work correctly
        flush_rewrite_rules();

        // HIGHLIGHT: The creation of the dashboard page is now disabled.
        // self::create_dashboard_page();
    }

    /**
     * Create all required database tables
     */
    private static function create_database_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Notifications table
        $table_name = $wpdb->prefix . 'puzzlingcrm_notifications';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            type varchar(50) NOT NULL,
            title varchar(255) NOT NULL,
            message text NOT NULL,
            data text,
            is_read tinyint(1) DEFAULT 0,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY is_read (is_read)
        ) $charset_collate;";
        dbDelta($sql);

        // Activities table
        $table_name = $wpdb->prefix . 'puzzlingcrm_activities';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            action_type varchar(50) NOT NULL,
            entity_type varchar(50) NOT NULL,
            entity_id bigint(20) NOT NULL,
            description text NOT NULL,
            metadata text,
            ip_address varchar(45) NOT NULL,
            user_agent text,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY action_type (action_type),
            KEY entity (entity_type, entity_id),
            KEY created_at (created_at)
        ) $charset_collate;";
        dbDelta($sql);

        // Reminders table
        $table_name = $wpdb->prefix . 'puzzlingcrm_reminders';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            title varchar(255) NOT NULL,
            description text,
            remind_at datetime NOT NULL,
            entity_type varchar(50),
            entity_id bigint(20),
            reminder_type varchar(20) DEFAULT 'manual',
            notification_channels text,
            recurring_pattern varchar(20),
            priority varchar(20) DEFAULT 'normal',
            status varchar(20) DEFAULT 'pending',
            metadata text,
            sent_at datetime,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY remind_at (remind_at),
            KEY status (status)
        ) $charset_collate;";
        dbDelta($sql);

        // Time Entries table
        $table_name = $wpdb->prefix . 'puzzlingcrm_time_entries';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            entity_type varchar(50) NOT NULL,
            entity_id bigint(20) NOT NULL,
            description text,
            start_time datetime NOT NULL,
            end_time datetime,
            duration_minutes decimal(10,2),
            paused_duration int DEFAULT 0,
            paused_at datetime,
            cost decimal(10,2) DEFAULT 0,
            status varchar(20) DEFAULT 'running',
            is_billable tinyint(1) DEFAULT 1,
            hourly_rate decimal(10,2) DEFAULT 0,
            is_manual tinyint(1) DEFAULT 0,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY entity (entity_type, entity_id),
            KEY status (status)
        ) $charset_collate;";
        dbDelta($sql);

        // Documents table
        $table_name = $wpdb->prefix . 'puzzlingcrm_documents';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            entity_type varchar(50),
            entity_id bigint(20),
            folder_id bigint(20) DEFAULT 0,
            title varchar(255) NOT NULL,
            description text,
            file_name varchar(255) NOT NULL,
            file_path varchar(500) NOT NULL,
            file_size bigint(20) NOT NULL,
            file_type varchar(50) NOT NULL,
            file_hash varchar(64) NOT NULL,
            mime_type varchar(100) NOT NULL,
            uploaded_by bigint(20) NOT NULL,
            is_private tinyint(1) DEFAULT 0,
            is_deleted tinyint(1) DEFAULT 0,
            version int DEFAULT 1,
            downloads int DEFAULT 0,
            uploaded_at datetime NOT NULL,
            last_downloaded_at datetime,
            deleted_at datetime,
            PRIMARY KEY  (id),
            KEY entity (entity_type, entity_id),
            KEY uploaded_by (uploaded_by),
            KEY is_deleted (is_deleted)
        ) $charset_collate;";
        dbDelta($sql);

        // Document Folders table
        $table_name = $wpdb->prefix . 'puzzlingcrm_document_folders';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            parent_id bigint(20) DEFAULT 0,
            created_by bigint(20) NOT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY parent_id (parent_id)
        ) $charset_collate;";
        dbDelta($sql);

        // Document Versions table
        $table_name = $wpdb->prefix . 'puzzlingcrm_document_versions';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            document_id bigint(20) NOT NULL,
            version int NOT NULL,
            file_path varchar(500) NOT NULL,
            file_size bigint(20) NOT NULL,
            file_hash varchar(64) NOT NULL,
            uploaded_by bigint(20) NOT NULL,
            uploaded_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY document_id (document_id)
        ) $charset_collate;";
        dbDelta($sql);

        // Document Shares table
        $table_name = $wpdb->prefix . 'puzzlingcrm_document_shares';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            document_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            permissions varchar(20) DEFAULT 'view',
            shared_by bigint(20) NOT NULL,
            shared_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY unique_share (document_id, user_id),
            KEY user_id (user_id)
        ) $charset_collate;";
        dbDelta($sql);

        // Document Tags table
        $table_name = $wpdb->prefix . 'puzzlingcrm_document_tags';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            document_id bigint(20) NOT NULL,
            tag varchar(100) NOT NULL,
            PRIMARY KEY  (id),
            KEY document_id (document_id),
            KEY tag (tag)
        ) $charset_collate;";
        dbDelta($sql);

        // Sessions table
        $table_name = $wpdb->prefix . 'puzzlingcrm_sessions';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            session_token varchar(255) NOT NULL,
            ip_address varchar(45) NOT NULL,
            user_agent text,
            device_info text,
            location text,
            login_time datetime NOT NULL,
            last_activity datetime NOT NULL,
            logout_time datetime,
            is_active tinyint(1) DEFAULT 1,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY is_active (is_active),
            KEY session_token (session_token)
        ) $charset_collate;";
        dbDelta($sql);

        // Chat Messages table
        $table_name = $wpdb->prefix . 'puzzlingcrm_chat_messages';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            sender_id bigint(20) NOT NULL,
            channel_id bigint(20) DEFAULT 0,
            recipient_id bigint(20) DEFAULT 0,
            message text NOT NULL,
            message_type varchar(20) DEFAULT 'text',
            parent_id bigint(20) DEFAULT 0,
            metadata text,
            is_deleted tinyint(1) DEFAULT 0,
            sent_at datetime NOT NULL,
            deleted_at datetime,
            PRIMARY KEY  (id),
            KEY sender_id (sender_id),
            KEY channel_id (channel_id),
            KEY recipient_id (recipient_id),
            KEY sent_at (sent_at)
        ) $charset_collate;";
        dbDelta($sql);

        // Chat Channels table
        $table_name = $wpdb->prefix . 'puzzlingcrm_chat_channels';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text,
            type varchar(20) DEFAULT 'public',
            created_by bigint(20) NOT NULL,
            created_at datetime NOT NULL,
            last_activity datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY type (type)
        ) $charset_collate;";
        dbDelta($sql);

        // Chat Channel Members table
        $table_name = $wpdb->prefix . 'puzzlingcrm_chat_channel_members';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            channel_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            joined_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY unique_member (channel_id, user_id),
            KEY user_id (user_id)
        ) $charset_collate;";
        dbDelta($sql);

        // Chat Read Receipts table
        $table_name = $wpdb->prefix . 'puzzlingcrm_chat_read_receipts';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            message_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            read_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY unique_receipt (message_id, user_id),
            KEY user_id (user_id)
        ) $charset_collate;";
        dbDelta($sql);

        // System logs table
        $table_name = $wpdb->prefix . 'puzzlingcrm_system_logs';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            log_type varchar(50) NOT NULL COMMENT 'error, debug, console, button_error',
            severity varchar(20) DEFAULT 'info' COMMENT 'info, warning, error, critical',
            message text NOT NULL,
            context longtext DEFAULT NULL COMMENT 'JSON data',
            file varchar(500) DEFAULT NULL,
            line int(11) DEFAULT NULL,
            user_id bigint(20) DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY log_type (log_type),
            KEY severity (severity),
            KEY user_id (user_id),
            KEY created_at (created_at),
            KEY ip_address (ip_address(45))
        ) $charset_collate;";
        dbDelta($sql);

        // User logs table
        $table_name = $wpdb->prefix . 'puzzlingcrm_user_logs';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            action_type varchar(100) NOT NULL COMMENT 'button_click, form_submit, ajax_call, page_view',
            action_description varchar(500) NOT NULL,
            target_type varchar(100) DEFAULT NULL,
            target_id bigint(20) DEFAULT NULL,
            metadata longtext DEFAULT NULL COMMENT 'JSON data',
            ip_address varchar(45) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY action_type (action_type),
            KEY target_type (target_type),
            KEY target_id (target_id),
            KEY created_at (created_at),
            KEY ip_address (ip_address(45))
        ) $charset_collate;";
        dbDelta($sql);

        // Visitors table (for visitor statistics)
        $table_name = $wpdb->prefix . 'puzzlingcrm_visitors';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            ip_address varchar(45) NOT NULL,
            user_agent text,
            country varchar(100) DEFAULT NULL,
            country_code varchar(2) DEFAULT NULL,
            browser varchar(100) DEFAULT NULL,
            browser_version varchar(50) DEFAULT NULL,
            os varchar(100) DEFAULT NULL,
            os_version varchar(50) DEFAULT NULL,
            device_type varchar(50) DEFAULT NULL COMMENT 'desktop, mobile, tablet',
            device_model varchar(255) DEFAULT NULL,
            first_visit datetime DEFAULT CURRENT_TIMESTAMP,
            last_visit datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            visit_count int(11) DEFAULT 1,
            is_bot tinyint(1) DEFAULT 0,
            PRIMARY KEY  (id),
            KEY ip_address (ip_address(45)),
            KEY last_visit (last_visit),
            KEY is_bot (is_bot)
        ) $charset_collate;";
        dbDelta($sql);

        // Visits table
        $table_name = $wpdb->prefix . 'puzzlingcrm_visits';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            visitor_id bigint(20) NOT NULL,
            page_url varchar(500) NOT NULL,
            page_title varchar(255) DEFAULT NULL,
            referrer varchar(500) DEFAULT NULL,
            referrer_domain varchar(255) DEFAULT NULL,
            search_engine varchar(50) DEFAULT NULL,
            search_keyword text DEFAULT NULL,
            visit_date datetime DEFAULT CURRENT_TIMESTAMP,
            session_id varchar(100) DEFAULT NULL,
            entity_id bigint(20) DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY visitor_id (visitor_id),
            KEY page_url (page_url(255)),
            KEY visit_date (visit_date),
            KEY session_id (session_id)
        ) $charset_collate;";
        dbDelta($sql);

        // Visitor pages table (aggregated page stats)
        $table_name = $wpdb->prefix . 'puzzlingcrm_visitor_pages';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            page_url varchar(500) NOT NULL,
            page_title varchar(255) DEFAULT NULL,
            visit_count int(11) DEFAULT 0,
            unique_visitors int(11) DEFAULT 0,
            last_visit datetime DEFAULT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY page_url (page_url(255)),
            KEY visit_count (visit_count),
            KEY last_visit (last_visit)
        ) $charset_collate;";
        dbDelta($sql);

        // Accounting module tables
        self::create_accounting_tables();

        // Licenses table
        self::create_license_table();
    }

    /**
     * Create accounting module database tables (Iranian standard).
     */
    private static function create_accounting_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $prefix = $wpdb->prefix . 'puzzlingcrm_';

        // Fiscal years
        $table = $prefix . 'accounting_fiscal_years';
        $sql   = "CREATE TABLE $table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL,
            start_date date NOT NULL,
            end_date date NOT NULL,
            is_active tinyint(1) DEFAULT 1,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY is_active (is_active)
        ) $charset_collate;";
        dbDelta( $sql );

        // Chart of accounts (hierarchical: group, class, ledger, detail)
        $table = $prefix . 'accounting_chart_accounts';
        $sql   = "CREATE TABLE $table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            code varchar(20) NOT NULL,
            title varchar(255) NOT NULL,
            level tinyint(1) NOT NULL COMMENT '1=group, 2=class, 3=ledger, 4=detail',
            parent_id bigint(20) DEFAULT 0,
            account_type varchar(20) NOT NULL COMMENT 'asset, liability, equity, income, expense',
            fiscal_year_id bigint(20) NOT NULL,
            is_system tinyint(1) DEFAULT 0,
            sort_order int(11) DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY code_fiscal (code, fiscal_year_id),
            KEY fiscal_year_id (fiscal_year_id),
            KEY parent_id (parent_id),
            KEY account_type (account_type)
        ) $charset_collate;";
        dbDelta( $sql );

        // Journal entries (voucher header)
        $table = $prefix . 'accounting_journal_entries';
        $sql   = "CREATE TABLE $table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            fiscal_year_id bigint(20) NOT NULL,
            voucher_no varchar(50) NOT NULL,
            voucher_date date NOT NULL,
            description text,
            reference_type varchar(50) DEFAULT NULL,
            reference_id bigint(20) DEFAULT NULL,
            created_by bigint(20) NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            status varchar(20) NOT NULL DEFAULT 'draft' COMMENT 'draft, posted',
            PRIMARY KEY (id),
            KEY fiscal_year_id (fiscal_year_id),
            KEY voucher_date (voucher_date),
            KEY status (status),
            KEY reference (reference_type, reference_id)
        ) $charset_collate;";
        dbDelta( $sql );

        // Journal lines (voucher rows: debit/credit per account)
        $table = $prefix . 'accounting_journal_lines';
        $sql   = "CREATE TABLE $table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            journal_entry_id bigint(20) NOT NULL,
            account_id bigint(20) NOT NULL,
            debit decimal(18,2) NOT NULL DEFAULT 0,
            credit decimal(18,2) NOT NULL DEFAULT 0,
            description text,
            cost_center_id bigint(20) DEFAULT NULL,
            sort_order int(11) DEFAULT 0,
            PRIMARY KEY (id),
            KEY journal_entry_id (journal_entry_id),
            KEY account_id (account_id)
        ) $charset_collate;";
        dbDelta( $sql );

        // Sub-ledger (detail level: contact, project, etc.)
        $table = $prefix . 'accounting_sub_ledger';
        $sql   = "CREATE TABLE $table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            account_id bigint(20) NOT NULL,
            entity_type varchar(50) NOT NULL,
            entity_id bigint(20) NOT NULL,
            name varchar(255) NOT NULL,
            code varchar(50) DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY account_id (account_id),
            KEY entity (entity_type, entity_id)
        ) $charset_collate;";
        dbDelta( $sql );

        // --- Phase 1: Persons (اشخاص) and Goods/Services (کالا و خدمات) ---

        // Person categories (دسته‌بندی اشخاص)
        $table = $prefix . 'accounting_person_categories';
        $sql   = "CREATE TABLE $table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            parent_id bigint(20) DEFAULT 0,
            sort_order int(11) DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY parent_id (parent_id)
        ) $charset_collate;";
        dbDelta( $sql );

        // Persons / counterparties (طرف‌های حساب)
        $table = $prefix . 'accounting_persons';
        $sql   = "CREATE TABLE $table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            code varchar(50) DEFAULT NULL,
            name varchar(255) NOT NULL,
            category_id bigint(20) DEFAULT NULL,
            credit_limit decimal(18,2) DEFAULT NULL,
            national_id varchar(20) DEFAULT NULL COMMENT 'شناسه ملی',
            economic_code varchar(20) DEFAULT NULL COMMENT 'کد اقتصادی',
            registration_no varchar(50) DEFAULT NULL COMMENT 'شماره ثبت',
            phone varchar(50) DEFAULT NULL,
            mobile varchar(50) DEFAULT NULL,
            extra_phones text DEFAULT NULL COMMENT 'JSON or semicolon-separated',
            address text DEFAULT NULL,
            person_type varchar(20) NOT NULL DEFAULT 'both' COMMENT 'customer, supplier, both',
            group_id bigint(20) DEFAULT NULL COMMENT 'گروه برای محدودیت دسترسی',
            image_url varchar(500) DEFAULT NULL,
            note text DEFAULT NULL,
            default_price_list_id bigint(20) DEFAULT NULL,
            is_active tinyint(1) DEFAULT 1,
            created_by bigint(20) DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY category_id (category_id),
            KEY person_type (person_type),
            KEY is_active (is_active),
            KEY name (name(100)),
            KEY code (code)
        ) $charset_collate;";
        dbDelta( $sql );

        // Product categories (دسته‌بندی درختی کالا)
        $table = $prefix . 'accounting_product_categories';
        $sql   = "CREATE TABLE $table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            parent_id bigint(20) DEFAULT 0,
            sort_order int(11) DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY parent_id (parent_id)
        ) $charset_collate;";
        dbDelta( $sql );

        // Units (واحد اصلی و فرعی)
        $table = $prefix . 'accounting_units';
        $sql   = "CREATE TABLE $table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(50) NOT NULL,
            symbol varchar(20) DEFAULT NULL,
            is_main tinyint(1) DEFAULT 1 COMMENT '1=main, 0=sub',
            base_unit_id bigint(20) DEFAULT NULL,
            ratio_to_base decimal(18,4) DEFAULT 1 COMMENT 'e.g. 12 for 1 box = 12 pcs',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY base_unit_id (base_unit_id)
        ) $charset_collate;";
        dbDelta( $sql );
        // Insert default main unit if empty (required for products)
        $count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table" );
        if ( $count === 0 ) {
            $wpdb->insert(
                $table,
                array( 'name' => 'عدد', 'symbol' => 'عد', 'is_main' => 1, 'ratio_to_base' => 1 ),
                array( '%s', '%s', '%d', '%f' )
            );
        }

        // Price lists (لیست قیمت)
        $table = $prefix . 'accounting_price_lists';
        $sql   = "CREATE TABLE $table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text DEFAULT NULL,
            is_default tinyint(1) DEFAULT 0,
            valid_from date DEFAULT NULL,
            valid_to date DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY is_default (is_default)
        ) $charset_collate;";
        dbDelta( $sql );

        // Products / Goods and services (کالا و خدمات)
        $table = $prefix . 'accounting_products';
        $sql   = "CREATE TABLE $table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            code varchar(50) NOT NULL,
            name varchar(255) NOT NULL,
            category_id bigint(20) DEFAULT NULL,
            main_unit_id bigint(20) NOT NULL,
            sub_unit_id bigint(20) DEFAULT NULL,
            sub_unit_ratio decimal(18,4) DEFAULT 1,
            purchase_price decimal(18,2) DEFAULT NULL,
            barcode text DEFAULT NULL COMMENT 'multiple barcodes semicolon-separated',
            inventory_controlled tinyint(1) DEFAULT 0,
            reorder_point decimal(18,2) DEFAULT NULL,
            tax_rate_sales decimal(8,2) DEFAULT NULL,
            tax_rate_purchase decimal(8,2) DEFAULT NULL,
            image_url varchar(500) DEFAULT NULL,
            note text DEFAULT NULL,
            is_active tinyint(1) DEFAULT 1,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY code (code),
            KEY category_id (category_id),
            KEY main_unit_id (main_unit_id),
            KEY is_active (is_active),
            KEY name (name(100))
        ) $charset_collate;";
        dbDelta( $sql );

        // Price list items (قیمت در هر لیست قیمت)
        $table = $prefix . 'accounting_price_list_items';
        $sql   = "CREATE TABLE $table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            price_list_id bigint(20) NOT NULL,
            product_id bigint(20) NOT NULL,
            price decimal(18,2) NOT NULL,
            min_quantity decimal(18,2) DEFAULT 1,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY list_product (price_list_id, product_id),
            KEY price_list_id (price_list_id),
            KEY product_id (product_id)
        ) $charset_collate;";
        dbDelta( $sql );

        // User defaults (شخص/لیست قیمت پیش‌فرض برای فاکتور)
        $table = $prefix . 'accounting_user_defaults';
        $sql   = "CREATE TABLE $table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            default_invoice_person_id bigint(20) DEFAULT NULL,
            default_price_list_id bigint(20) DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_id (user_id)
        ) $charset_collate;";
        dbDelta( $sql );

        // --- Phase 2: Invoices (فاکتور خرید و فروش) ---
        $table = $prefix . 'accounting_invoices';
        $sql   = "CREATE TABLE $table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            fiscal_year_id bigint(20) NOT NULL,
            invoice_no varchar(50) NOT NULL,
            invoice_type varchar(20) NOT NULL DEFAULT 'sales' COMMENT 'proforma, sales, purchase',
            person_id bigint(20) NOT NULL,
            invoice_date date NOT NULL,
            due_date date DEFAULT NULL,
            status varchar(20) NOT NULL DEFAULT 'draft' COMMENT 'draft, confirmed, returned',
            seller_id bigint(20) DEFAULT NULL COMMENT 'فروشنده',
            project_id bigint(20) DEFAULT NULL,
            shipping_cost decimal(18,2) DEFAULT NULL,
            extra_additions decimal(18,2) DEFAULT NULL,
            extra_deductions decimal(18,2) DEFAULT NULL,
            reference_type varchar(50) DEFAULT NULL,
            reference_id bigint(20) DEFAULT NULL,
            created_by bigint(20) NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY fiscal_no (fiscal_year_id, invoice_no),
            KEY person_id (person_id),
            KEY invoice_date (invoice_date),
            KEY status (status),
            KEY reference (reference_type, reference_id)
        ) $charset_collate;";
        dbDelta( $sql );

        $table = $prefix . 'accounting_invoice_lines';
        $sql   = "CREATE TABLE $table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            invoice_id bigint(20) NOT NULL,
            product_id bigint(20) NOT NULL,
            quantity decimal(18,4) NOT NULL DEFAULT 1,
            unit_id bigint(20) DEFAULT NULL,
            unit_price decimal(18,2) NOT NULL DEFAULT 0,
            discount_percent decimal(8,2) DEFAULT NULL,
            discount_amount decimal(18,2) DEFAULT NULL,
            tax_percent decimal(8,2) DEFAULT NULL,
            tax_amount decimal(18,2) DEFAULT NULL,
            description text DEFAULT NULL,
            sort_order int(11) DEFAULT 0,
            PRIMARY KEY (id),
            KEY invoice_id (invoice_id),
            KEY product_id (product_id)
        ) $charset_collate;";
        dbDelta( $sql );

        // --- Phase 3: Cash accounts (صندوق/بانک/تنخواه) and Receipt/Payment (رسید و پرداخت) ---
        $table = $prefix . 'accounting_cash_accounts';
        $sql   = "CREATE TABLE $table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            type varchar(20) NOT NULL DEFAULT 'bank' COMMENT 'bank, cash, petty',
            code varchar(50) DEFAULT NULL,
            description text DEFAULT NULL,
            card_no varchar(50) DEFAULT NULL,
            sheba varchar(34) DEFAULT NULL COMMENT 'شبا',
            chart_account_id bigint(20) DEFAULT NULL COMMENT 'حساب معین در نمودار حساب‌ها',
            is_active tinyint(1) DEFAULT 1,
            sort_order int(11) DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY type (type),
            KEY is_active (is_active)
        ) $charset_collate;";
        dbDelta( $sql );

        $table = $prefix . 'accounting_receipt_vouchers';
        $sql   = "CREATE TABLE $table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            fiscal_year_id bigint(20) NOT NULL,
            voucher_no varchar(50) NOT NULL,
            voucher_date date NOT NULL,
            type varchar(20) NOT NULL COMMENT 'receipt, payment, transfer',
            cash_account_id bigint(20) NOT NULL COMMENT 'حساب صندوق/بانک (برای انتقال: مبدا)',
            transfer_to_cash_account_id bigint(20) DEFAULT NULL COMMENT 'فقط برای انتقال',
            person_id bigint(20) DEFAULT NULL COMMENT 'طرف حساب',
            amount decimal(18,2) NOT NULL DEFAULT 0,
            description text DEFAULT NULL,
            invoice_id bigint(20) DEFAULT NULL COMMENT 'تسویه فاکتور',
            project_id bigint(20) DEFAULT NULL,
            bank_fee decimal(18,2) DEFAULT NULL COMMENT 'کارمزد بانکی',
            journal_entry_id bigint(20) DEFAULT NULL COMMENT 'سند خودکار',
            created_by bigint(20) NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'draft' COMMENT 'draft, posted',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY fiscal_no (fiscal_year_id, voucher_no),
            KEY cash_account_id (cash_account_id),
            KEY person_id (person_id),
            KEY voucher_date (voucher_date),
            KEY type (type),
            KEY status (status),
            KEY invoice_id (invoice_id)
        ) $charset_collate;";
        dbDelta( $sql );

        // --- Phase 4: Cheques (چک دریافتی و پرداختی) ---
        $table = $prefix . 'accounting_checks';
        $sql   = "CREATE TABLE $table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            type varchar(20) NOT NULL COMMENT 'receivable, payable',
            check_no varchar(50) NOT NULL COMMENT 'شماره چک',
            check_date date DEFAULT NULL COMMENT 'تاریخ چک',
            amount decimal(18,2) NOT NULL DEFAULT 0,
            cash_account_id bigint(20) NOT NULL COMMENT 'بانک',
            person_id bigint(20) NOT NULL COMMENT 'طرف حساب',
            due_date date NOT NULL COMMENT 'تاریخ سررسید',
            status varchar(20) NOT NULL DEFAULT 'in_safe' COMMENT 'in_safe, collected, returned, spent',
            receipt_voucher_id bigint(20) DEFAULT NULL COMMENT 'رسید/پرداخت مرتبط',
            description text DEFAULT NULL,
            journal_entry_id bigint(20) DEFAULT NULL COMMENT 'سند عملیات چک',
            created_by bigint(20) NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY type (type),
            KEY cash_account_id (cash_account_id),
            KEY person_id (person_id),
            KEY due_date (due_date),
            KEY status (status),
            KEY receipt_voucher_id (receipt_voucher_id)
        ) $charset_collate;";
        dbDelta( $sql );
    }

    /**
     * Create licenses table
     */
    private static function create_license_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $table_name = $wpdb->prefix . 'puzzlingcrm_licenses';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            project_name varchar(255) NOT NULL,
            domain varchar(255) NOT NULL,
            license_key varchar(255) NOT NULL,
            status varchar(20) DEFAULT 'inactive',
            expiry_date datetime DEFAULT NULL,
            start_date datetime DEFAULT NULL,
            logo_url varchar(500) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY domain (domain),
            UNIQUE KEY license_key (license_key),
            KEY status (status),
            KEY expiry_date (expiry_date)
        ) $charset_collate;";
        dbDelta($sql);
    }
    
    /**
     * Deactivation hook callback.
     * Cleans up roles created by the plugin.
     */
    public static function deactivate() {
        // Call role removal
        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/class-roles-manager.php';
        $roles_manager = new PuzzlingCRM_Roles_Manager();
        $roles_manager->remove_custom_roles();
        
        // Clean up login page rewrite rules
        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/class-login-page.php';
        PuzzlingCRM_Login_Page::deactivate();
        
        // Clean up dashboard rewrite rules
        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/class-dashboard-router.php';
        PuzzlingCRM_Dashboard_Router::deactivate();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Creates the frontend dashboard page if it doesn't exist
     * and stores its ID in the options table.
     * HIGHLIGHT: This function is no longer called on activation.
     */
    private static function create_dashboard_page() {
        $dashboard_page_id = get_option('puzzling_dashboard_page_id', 0);

        // Check if the page exists and is published
        if ( $dashboard_page_id && get_post_status($dashboard_page_id) === 'publish' ) {
            return;
        }

        // Create the page
        $page_id = wp_insert_post([
            'post_title'    => 'PuzzlingCRM Dashboard',
            'post_content'  => '[puzzling_dashboard]',
            'post_status'   => 'publish',
            'post_author'   => 1, // Assign to the main admin
            'post_type'     => 'page',
        ]);

        if ($page_id > 0 && !is_wp_error($page_id)) {
            // Store the page ID so we can retrieve it later reliably
            update_option('puzzling_dashboard_page_id', $page_id);
        }
    }
}