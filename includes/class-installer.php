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