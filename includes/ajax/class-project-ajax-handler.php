<?php
/**
 * PuzzlingCRM Project & Contract AJAX Handler
 *
 * @package PuzzlingCRM
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class PuzzlingCRM_Project_Ajax_Handler {

    public function __construct() {
        add_action('wp_ajax_puzzling_manage_project', [$this, 'ajax_manage_project']);
        add_action('wp_ajax_puzzling_delete_project', [$this, 'ajax_delete_project']);
        add_action('wp_ajax_puzzling_manage_contract', [$this, 'ajax_manage_contract']);
        add_action('wp_ajax_puzzling_cancel_contract', [$this, 'ajax_cancel_contract']);
        add_action('wp_ajax_puzzling_add_project_to_contract', [$this, 'ajax_add_project_to_contract']);
        add_action('wp_ajax_puzzling_add_services_from_product', [$this, 'ajax_add_services_from_product']);
        add_action('wp_ajax_puzzling_get_projects_for_customer', [$this, 'ajax_get_projects_for_customer']);
        add_action('wp_ajax_puzzling_delete_contract', [$this, 'ajax_delete_contract']);
        add_action('wp_ajax_puzzlingcrm_get_contracts', [$this, 'ajax_get_contracts']);
        add_action('wp_ajax_puzzlingcrm_get_contract', [$this, 'ajax_get_contract']);
        add_action('wp_ajax_puzzlingcrm_get_projects', [$this, 'ajax_get_projects']);
        add_action('wp_ajax_puzzlingcrm_get_project', [$this, 'ajax_get_project']);
        add_action('wp_ajax_puzzlingcrm_get_project_templates', [$this, 'ajax_get_project_templates']);
        add_action('wp_ajax_puzzlingcrm_get_project_assignees', [ $this, 'ajax_get_project_assignees' ]);
        add_action('wp_ajax_puzzlingcrm_get_product_projects_preview', [ $this, 'ajax_get_product_projects_preview' ]);
        add_action('wp_ajax_puzzlingcrm_get_assignable_employees', [ $this, 'ajax_get_assignable_employees' ]);
    }

    /**
     * Returns project titles that would be created from a WC product (Simple=1, Grouped=N children).
     */
    public function ajax_get_product_projects_preview() {
        $this->load_dependencies();
        if ( ! check_ajax_referer( 'puzzlingcrm-ajax-nonce', 'security', false ) ) {
            wp_send_json_error( [ 'message' => __( 'درخواست نامعتبر.', 'puzzlingcrm' ) ] );
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'دسترسی غیرمجاز.', 'puzzlingcrm' ) ] );
        }
        $product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
        if ( ! $product_id || ! function_exists( 'wc_get_product' ) ) {
            wp_send_json_success( [ 'titles' => [] ] );
        }
        $product = wc_get_product( $product_id );
        if ( ! $product ) {
            wp_send_json_success( [ 'titles' => [] ] );
        }
        $child_ids = $product->is_type( 'grouped' ) ? $product->get_children() : [ $product->get_id() ];
        $titles = [];
        foreach ( $child_ids as $cid ) {
            $child = wc_get_product( $cid );
            if ( $child ) {
                $titles[] = $child->get_name();
            }
        }
        wp_send_json_success( [ 'titles' => $titles ] );
    }

    /**
     * Returns users who can be assigned projects (system_manager, team_member, administrator).
     */
    public function ajax_get_project_assignees() {
        $this->load_dependencies();
        if ( ! check_ajax_referer( 'puzzlingcrm-ajax-nonce', 'security', false ) ) {
            wp_send_json_error( [ 'message' => __( 'درخواست نامعتبر.', 'puzzlingcrm' ) ] );
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'دسترسی غیرمجاز.', 'puzzlingcrm' ) ] );
        }
        $search = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';
        $args = [
            'role__in' => [ 'system_manager', 'team_member', 'administrator' ],
            'orderby'  => 'display_name',
            'order'    => 'ASC',
        ];
        if ( ! empty( $search ) ) {
            $args['search']         = '*' . $search . '*';
            $args['search_columns'] = [ 'user_login', 'user_email', 'display_name', 'user_nicename' ];
        }
        $users = get_users( $args );
        $items = array_map( function ( $u ) {
            return [ 'id' => $u->ID, 'display_name' => $u->display_name ];
        }, $users );
        wp_send_json_success( [ 'users' => $items ] );
    }

    /**
     * Get assignable employees with optional department filter and workload sort.
     */
    public function ajax_get_assignable_employees() {
        $this->load_dependencies();
        if ( ! check_ajax_referer( 'puzzlingcrm-ajax-nonce', 'security', false ) ) {
            wp_send_json_error( [ 'message' => __( 'درخواست نامعتبر.', 'puzzlingcrm' ) ] );
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'دسترسی غیرمجاز.', 'puzzlingcrm' ) ] );
        }
        $department_id = isset( $_POST['department_id'] ) ? absint( $_POST['department_id'] ) : 0;
        $role_filter   = isset( $_POST['role'] ) ? sanitize_key( $_POST['role'] ) : '';
        $sort_by_load  = isset( $_POST['sort_by_load'] ) && $_POST['sort_by_load'] === '1';

        $args = [
            'role__in' => [ 'system_manager', 'team_member', 'administrator' ],
            'orderby'  => 'display_name',
            'order'    => 'ASC',
        ];
        if ( $role_filter && in_array( $role_filter, [ 'system_manager', 'team_member', 'administrator' ], true ) ) {
            $args['role'] = $role_filter;
            unset( $args['role__in'] );
        }
        if ( $department_id > 0 ) {
            $args['tax_query'] = [
                [
                    'taxonomy' => 'organizational_position',
                    'field'    => 'term_id',
                    'terms'    => $department_id,
                ],
            ];
        }
        $users = get_users( $args );

        if ( $sort_by_load && ! empty( $users ) ) {
            $counts = [];
            foreach ( $users as $u ) {
                $projects = get_posts( [
                    'post_type'      => 'project',
                    'posts_per_page' => -1,
                    'fields'         => 'ids',
                    'meta_query'     => [
                        [ 'key' => '_assigned_to', 'value' => $u->ID, 'compare' => '=' ],
                    ],
                ] );
                $tasks = get_posts( [
                    'post_type'      => 'task',
                    'posts_per_page' => -1,
                    'fields'         => 'ids',
                    'meta_query'     => [ [ 'key' => '_assigned_to', 'value' => $u->ID, 'compare' => '=' ] ],
                ] );
                $counts[ $u->ID ] = count( $projects ) + count( $tasks );
            }
            usort( $users, function ( $a, $b ) use ( $counts ) {
                $ca = $counts[ $a->ID ] ?? 0;
                $cb = $counts[ $b->ID ] ?? 0;
                return $ca <=> $cb;
            } );
        }

        $items = array_map( function ( $u ) {
            return [ 'id' => $u->ID, 'display_name' => $u->display_name ];
        }, $users );
        wp_send_json_success( [ 'users' => $items ] );
    }

    /**
     * Get list of contracts for React dashboard (JSON).
     */
    public function ajax_get_contracts() {
        $this->load_dependencies();
        if (!check_ajax_referer('puzzlingcrm-ajax-nonce', 'security', false)) {
            wp_send_json_error(['message' => 'خطای امنیتی.']);
        }
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.']);
        }

        $search = isset($_POST['s']) ? sanitize_text_field($_POST['s']) : '';
        $customer_filter = isset($_POST['customer_filter']) ? intval($_POST['customer_filter']) : 0;
        $payment_status_filter = isset($_POST['payment_status']) ? sanitize_key($_POST['payment_status']) : '';
        $paged = isset($_POST['paged']) ? max(1, intval($_POST['paged'])) : 1;

        $args = [
            'post_type' => 'contract',
            'posts_per_page' => 20,
            'paged' => $paged,
        ];
        if (!empty($search)) {
            $args['s'] = $search;
        }
        if ($customer_filter > 0) {
            $args['author'] = $customer_filter;
        }

        $query = new WP_Query($args);
        $customers = get_users(['role__in' => ['customer', 'subscriber', 'client'], 'orderby' => 'display_name']);
        $customers_list = [];
        foreach ($customers as $c) {
            $customers_list[] = ['id' => $c->ID, 'display_name' => $c->display_name];
        }

        $items = [];
        foreach ($query->posts as $post) {
            $contract_id = $post->ID;
            $author_id = (int) $post->post_author;
            $customer = $author_id ? get_userdata($author_id) : null;
            $customer_name = ($customer && $customer->exists()) ? $customer->display_name : 'مشتری حذف شده';
            $customer_email = ($customer && $customer->exists()) ? $customer->user_email : '';

            $installments = get_post_meta($contract_id, '_installments', true);
            if (!is_array($installments)) $installments = [];

            $total_amount = get_post_meta($contract_id, '_total_amount', true);
            $total_amount_int = (int) preg_replace('/[^\d]/', '', $total_amount);
            $paid_amount = 0;
            $pending_amount = 0;
            $paid_count = 0;
            $pending_count = 0;
            foreach ($installments as $inst) {
                $inst_amount = (int) preg_replace('/[^\d]/', '', ($inst['amount'] ?? 0));
                $inst_status = $inst['status'] ?? 'pending';
                if ($inst_status === 'paid') {
                    $paid_amount += $inst_amount;
                    $paid_count++;
                } elseif ($inst_status !== 'cancelled') {
                    $pending_amount += $inst_amount;
                    $pending_count++;
                }
            }
            if (empty($total_amount_int) && count($installments) > 0) {
                $total_amount_int = $paid_amount + $pending_amount;
            }

            $contract_status = get_post_meta($contract_id, '_contract_status', true);
            $status_class = 'pending';
            if ($contract_status === 'cancelled') {
                $status_text = 'لغو شده';
                $status_class = 'cancelled';
            } elseif ($total_amount_int > 0 && $paid_amount >= $total_amount_int) {
                $status_text = 'تکمیل پرداخت';
                $status_class = 'paid';
            } elseif ($paid_amount > 0) {
                $status_text = 'در حال پرداخت';
                $status_class = 'pending';
            } else {
                $status_text = 'در انتظار پرداخت';
                $status_class = 'pending';
            }

            if ($payment_status_filter === 'paid' && $status_class !== 'paid') continue;
            if ($payment_status_filter === 'pending' && $status_class === 'paid') continue;

            $start_date = get_post_meta($contract_id, '_project_start_date', true);
            $start_date_jalali = $start_date && function_exists('puzzling_gregorian_to_jalali')
                ? puzzling_gregorian_to_jalali($start_date) : '-';
            $payment_percentage = $total_amount_int > 0 ? round(($paid_amount / $total_amount_int) * 100) : 0;
            $contract_number = get_post_meta($contract_id, '_contract_number', true) ?: (string) $contract_id;

            $items[] = [
                'id' => $contract_id,
                'contract_number' => $contract_number,
                'title' => $post->post_title,
                'customer_id' => $author_id,
                'customer_name' => $customer_name,
                'customer_email' => $customer_email,
                'total_amount' => $total_amount_int,
                'paid_amount' => $paid_amount,
                'pending_amount' => $pending_amount,
                'installment_count' => count($installments),
                'paid_count' => $paid_count,
                'pending_count' => $pending_count,
                'status_class' => $status_class,
                'status_text' => $status_text,
                'start_date' => $start_date,
                'start_date_jalali' => $start_date_jalali,
                'payment_percentage' => $payment_percentage,
                'is_cancelled' => $contract_status === 'cancelled',
                'delete_nonce' => wp_create_nonce('puzzling_delete_contract_' . $contract_id),
            ];
        }

        wp_send_json_success([
            'contracts' => $items,
            'customers' => $customers_list,
            'total_pages' => $query->max_num_pages,
            'current_page' => $paged,
        ]);
    }

    /**
     * Get single contract for edit.
     */
    public function ajax_get_contract() {
        $this->load_dependencies();
        if (!check_ajax_referer('puzzlingcrm-ajax-nonce', 'security', false)) {
            wp_send_json_error(['message' => 'خطای امنیتی.']);
        }
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.']);
        }
        $contract_id = isset($_POST['contract_id']) ? intval($_POST['contract_id']) : 0;
        $post = get_post($contract_id);
        if (!$post || $post->post_type !== 'contract') {
            wp_send_json_error(['message' => 'قرارداد یافت نشد.']);
        }

        $installments = get_post_meta($contract_id, '_installments', true);
        if (!is_array($installments)) $installments = [];
        $start_date = get_post_meta($contract_id, '_project_start_date', true);
        $start_jalali = $start_date && function_exists('puzzling_gregorian_to_jalali')
            ? puzzling_gregorian_to_jalali($start_date) : '';

        $inst_data = [];
        foreach ($installments as $i) {
            $due = $i['due_date'] ?? '';
            $due_jalali = $due && function_exists('puzzling_gregorian_to_jalali')
                ? puzzling_gregorian_to_jalali($due) : $due;
            $inst_data[] = [
                'amount' => $i['amount'] ?? '',
                'due_date' => $due_jalali,
                'due_date_gregorian' => $due,
                'status' => $i['status'] ?? 'pending',
            ];
        }

        $related_projects = get_posts([
            'post_type' => 'project',
            'posts_per_page' => -1,
            'meta_key' => '_contract_id',
            'meta_value' => $contract_id,
        ]);

        $customers = get_users(['role__in' => ['customer', 'subscriber'], 'orderby' => 'display_name']);
        $customers_list = [];
        foreach ($customers as $c) {
            $customers_list[] = ['id' => $c->ID, 'display_name' => $c->display_name];
        }

        $durations = [
            ['value' => '1-month', 'label' => 'یک ماهه'],
            ['value' => '3-months', 'label' => 'سه ماهه'],
            ['value' => '6-months', 'label' => 'شش ماهه'],
            ['value' => '12-months', 'label' => 'یک ساله'],
        ];
        $models = [
            ['value' => 'onetime', 'label' => 'یکبار پرداخت'],
            ['value' => 'subscription', 'label' => 'اشتراکی'],
        ];

        wp_send_json_success([
            'contract' => [
                'id' => $post->ID,
                'title' => $post->post_title,
                'customer_id' => (int) $post->post_author,
                'contract_number' => get_post_meta($contract_id, '_contract_number', true),
                'start_date' => $start_date,
                'start_date_jalali' => $start_jalali,
                'total_amount' => get_post_meta($contract_id, '_total_amount', true),
                'total_installments' => get_post_meta($contract_id, '_total_installments', true) ?: 1,
                'duration' => get_post_meta($contract_id, '_project_contract_duration', true) ?: '1-month',
                'subscription_model' => get_post_meta($contract_id, '_project_subscription_model', true) ?: 'onetime',
                'installments' => $inst_data,
                'is_cancelled' => get_post_meta($contract_id, '_contract_status', true) === 'cancelled',
                'delete_nonce' => wp_create_nonce('puzzling_delete_contract_' . $contract_id),
            ],
            'related_projects' => array_map(function ($p) {
                return ['id' => $p->ID, 'title' => $p->post_title];
            }, $related_projects),
            'customers' => $customers_list,
            'durations' => $durations,
            'models' => $models,
        ]);
    }

    public function ajax_get_projects() {
        $this->load_dependencies();
        if (!check_ajax_referer('puzzlingcrm-ajax-nonce', 'security', false)) {
            wp_send_json_error(['message' => 'خطای امنیتی.']);
        }
        $current_user_id = get_current_user_id();
        $is_manager = current_user_can('manage_options');
        $is_team_member = in_array('team_member', (array) wp_get_current_user()->roles);

        $search = isset($_POST['s']) ? sanitize_text_field($_POST['s']) : '';
        $contract_filter = isset($_POST['contract_id']) ? intval($_POST['contract_id']) : 0;
        $status_filter = isset($_POST['status_filter']) ? intval($_POST['status_filter']) : 0;
        $paged = isset($_POST['paged']) ? max(1, intval($_POST['paged'])) : 1;

        $args = [
            'post_type' => 'project',
            'posts_per_page' => 20,
            'paged' => $paged,
            'post_status' => 'publish',
        ];
        if ($search) $args['s'] = $search;
        if ($contract_filter > 0) {
            $args['meta_query'] = [['key' => '_contract_id', 'value' => $contract_filter]];
        }
        if ($status_filter > 0) {
            $args['tax_query'] = [['taxonomy' => 'project_status', 'field' => 'term_id', 'terms' => $status_filter]];
        }
        if (!$is_manager && !$is_team_member) {
            $args['author'] = $current_user_id;
        }

        $query = new WP_Query($args);
        $items = [];
        foreach ($query->posts as $post) {
            $contract_id = get_post_meta($post->ID, '_contract_id', true);
            $contract = $contract_id ? get_post($contract_id) : null;
            $customer_name = $contract && $contract->post_author ? get_the_author_meta('display_name', $contract->post_author) : '---';
            $status_terms = get_the_terms($post->ID, 'project_status');
            $status_name = !empty($status_terms) ? $status_terms[0]->name : '---';
            $can_delete = $is_manager || $is_team_member;
            $items[] = [
                'id' => $post->ID,
                'title' => $post->post_title,
                'contract_id' => (int) $contract_id,
                'customer_name' => $customer_name,
                'status_name' => $status_name,
                'status_id' => !empty($status_terms) ? $status_terms[0]->term_id : 0,
                'delete_nonce' => $can_delete ? wp_create_nonce('puzzling_delete_project_' . $post->ID) : '',
            ];
        }

        $contracts = get_posts(['post_type' => 'contract', 'posts_per_page' => -1, 'orderby' => 'ID', 'order' => 'DESC']);
        $contracts_list = [];
        foreach ($contracts as $c) {
            $cust = get_userdata($c->post_author);
            $contracts_list[] = ['id' => $c->ID, 'title' => $c->post_title, 'customer_name' => $cust ? $cust->display_name : '---'];
        }
        $statuses = get_terms(['taxonomy' => 'project_status', 'hide_empty' => false]);
        $statuses_list = array_map(function ($t) {
            return ['id' => $t->term_id, 'name' => $t->name];
        }, is_array($statuses) ? $statuses : []);

        $managers = [];
        if ($is_manager || $is_team_member) {
            $managers_raw = get_users(['role__in' => ['system_manager', 'administrator'], 'orderby' => 'display_name']);
            $managers = array_map(function ($u) {
                return ['id' => $u->ID, 'display_name' => $u->display_name];
            }, $managers_raw);
        }

        wp_send_json_success([
            'projects' => $items,
            'contracts' => $contracts_list,
            'statuses' => $statuses_list,
            'managers' => $managers,
            'total_pages' => $query->max_num_pages,
            'current_page' => $paged,
        ]);
    }

    public function ajax_get_project() {
        $this->load_dependencies();
        if (!check_ajax_referer('puzzlingcrm-ajax-nonce', 'security', false)) {
            wp_send_json_error(['message' => 'خطای امنیتی.']);
        }
        $current_user_id = get_current_user_id();
        $user_roles = (array) wp_get_current_user()->roles;
        $is_manager = current_user_can('manage_options');
        $is_team_member = in_array('team_member', $user_roles, true);
        $is_customer = in_array('customer', $user_roles, true);
        if (!$is_manager && !$is_team_member && !$is_customer) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.']);
        }

        $project_id = isset($_POST['project_id']) ? intval($_POST['project_id']) : 0;
        $post = get_post($project_id);
        if (!$post || $post->post_type !== 'project') {
            wp_send_json_error(['message' => 'پروژه یافت نشد.']);
        }

        $has_access = $is_manager;
        if (!$has_access && $is_team_member) {
            $assigned_members = get_post_meta($project_id, '_assigned_team_members', true);
            if (is_array($assigned_members) && in_array($current_user_id, $assigned_members, true)) {
                $has_access = true;
            }
            if (!$has_access) {
                $user_tasks = get_posts([
                    'post_type' => 'task',
                    'posts_per_page' => 1,
                    'meta_query' => [
                        ['key' => '_project_id', 'value' => $project_id, 'compare' => '='],
                        ['key' => '_assigned_to', 'value' => $current_user_id, 'compare' => '='],
                    ],
                ]);
                if (!empty($user_tasks)) {
                    $has_access = true;
                }
            }
        }
        if (!$has_access && $is_customer && (int) $post->post_author === $current_user_id) {
            $has_access = true;
        }
        if (!$has_access) {
            wp_send_json_error(['message' => 'شما دسترسی لازم برای مشاهده این پروژه را ندارید.']);
        }

        $status_terms = get_the_terms($project_id, 'project_status');
        $start_date = get_post_meta($project_id, '_project_start_date', true);
        $end_date = get_post_meta($project_id, '_project_end_date', true);
        $contract_id = (int) get_post_meta($project_id, '_contract_id', true);
        $project_manager_id = (int) get_post_meta($project_id, '_project_manager', true);
        $assigned_members = get_post_meta($project_id, '_assigned_team_members', true);
        $assigned_members = is_array($assigned_members) ? $assigned_members : [];
        $priority = get_post_meta($project_id, '_project_priority', true) ?: 'medium';

        $contract = $contract_id ? get_post($contract_id) : null;
        $customer_id = $contract && $contract->post_author ? $contract->post_author : $post->post_author;
        $customer = $customer_id ? get_userdata($customer_id) : null;
        $manager = $project_manager_id ? get_userdata($project_manager_id) : null;

        $project_tasks_args = [
            'post_type' => 'task',
            'posts_per_page' => -1,
            'meta_query' => [['key' => '_project_id', 'value' => $project_id, 'compare' => '=']],
            'orderby' => 'post_date',
            'order' => 'DESC',
        ];
        if (!$is_manager) {
            $project_tasks_args['meta_query'][] = ['key' => '_assigned_to', 'value' => $current_user_id, 'compare' => '='];
            $project_tasks_args['meta_query']['relation'] = 'AND';
        }
        $project_tasks = get_posts($project_tasks_args);
        $total_tasks = count($project_tasks);
        $completed_tasks = 0;
        $tasks_list = [];
        foreach ($project_tasks as $task_post) {
            $task_status_terms = wp_get_post_terms($task_post->ID, 'task_status');
            $is_done = !empty($task_status_terms) && $task_status_terms[0]->slug === 'done';
            if ($is_done) {
                $completed_tasks++;
            }
            $tasks_list[] = [
                'id' => $task_post->ID,
                'title' => $task_post->post_title,
                'status_slug' => !empty($task_status_terms) ? $task_status_terms[0]->slug : '',
                'status_name' => !empty($task_status_terms) ? $task_status_terms[0]->name : '',
            ];
        }
        $completion_percentage = $total_tasks > 0 ? round(($completed_tasks / $total_tasks) * 100) : 0;

        $managers = get_users(['role__in' => ['system_manager', 'administrator'], 'orderby' => 'display_name']);
        $contracts = get_posts(['post_type' => 'contract', 'posts_per_page' => -1]);
        $statuses = get_terms(['taxonomy' => 'project_status', 'hide_empty' => false]);
        $assigned_members_data = [];
        foreach ($assigned_members as $uid) {
            $u = get_userdata($uid);
            if ($u) {
                $assigned_members_data[] = ['id' => $u->ID, 'display_name' => $u->display_name];
            }
        }

        wp_send_json_success([
            'project' => [
                'id' => $post->ID,
                'title' => $post->post_title,
                'content' => $post->post_content,
                'contract_id' => $contract_id,
                'project_manager' => $project_manager_id,
                'project_status' => !empty($status_terms) ? $status_terms[0]->term_id : 0,
                'status_name' => !empty($status_terms) ? $status_terms[0]->name : '---',
                'start_date' => $start_date,
                'end_date' => $end_date,
                'priority' => $priority,
                'delete_nonce' => $is_manager ? wp_create_nonce('puzzling_delete_project_' . $project_id) : '',
                'customer_id' => $customer_id,
                'customer_name' => $customer ? $customer->display_name : '---',
                'manager_name' => $manager ? $manager->display_name : '---',
                'assigned_members' => $assigned_members_data,
                'tasks' => $tasks_list,
                'total_tasks' => $total_tasks,
                'completed_tasks' => $completed_tasks,
                'completion_percentage' => $completion_percentage,
            ],
            'managers' => array_map(function ($u) {
                return ['id' => $u->ID, 'display_name' => $u->display_name];
            }, $managers),
            'contracts' => array_map(function ($c) {
                $cust = get_userdata($c->post_author);
                return ['id' => $c->ID, 'title' => $c->post_title, 'customer_name' => $cust ? $cust->display_name : '---'];
            }, $contracts),
            'statuses' => array_map(function ($t) {
                return ['id' => $t->term_id, 'name' => $t->name];
            }, is_array($statuses) ? $statuses : []),
        ]);
    }

    /**
     * Defensive dependency loader for AJAX context.
     * This function ensures all required files are loaded before any AJAX action is executed.
     */
    private function load_dependencies() {
        if (defined('PUZZLINGCRM_PLUGIN_DIR')) {
            // Use require_once to prevent multiple inclusions
            require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/class-error-codes.php';
            require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/class-logger.php';
            require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/puzzling-functions.php';
            if (file_exists(PUZZLINGCRM_PLUGIN_DIR . 'includes/lib/jdf.php')) {
                 require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/lib/jdf.php';
            }
        }
    }

    private function clean_buffer_and_send_error($code, $extra_data = []) {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        if (class_exists('PuzzlingCRM_Error_Codes')) {
            PuzzlingCRM_Error_Codes::send_error($code, $extra_data);
        } else {
            // Fallback if the error class itself couldn't be loaded
            wp_send_json_error(['message' => 'یک خطای مهم رخ داد. کلاس خطا یافت نشد.'], 500);
        }
    }

    public function ajax_manage_project() {
        $this->load_dependencies();
        ob_start();
        
        if (!check_ajax_referer('puzzlingcrm-ajax-nonce', 'security', false)) {
            $this->clean_buffer_and_send_error(PuzzlingCRM_Error_Codes::PRJ_ERR_NONCE_FAILED);
        }
        if (!current_user_can('manage_options')) {
            $this->clean_buffer_and_send_error(PuzzlingCRM_Error_Codes::PRJ_ERR_ACCESS_DENIED);
        }

        $project_id = isset($_POST['project_id']) ? intval($_POST['project_id']) : 0;
        $contract_id = isset($_POST['contract_id']) ? intval($_POST['contract_id']) : 0;
        $project_title = sanitize_text_field($_POST['project_title']);
        $project_content = wp_kses_post($_POST['project_content']);
        $project_status_id = intval($_POST['project_status']);

        if (empty($project_title) || empty($contract_id)) {
            $this->clean_buffer_and_send_error(PuzzlingCRM_Error_Codes::PRJ_ERR_MISSING_PROJECT_DATA);
        }

        $contract = get_post($contract_id);
        if (!$contract) {
            $this->clean_buffer_and_send_error(PuzzlingCRM_Error_Codes::PRJ_ERR_CONTRACT_INVALID);
        }
        $customer_id = $contract->post_author;

        $post_data = [
            'post_title' => $project_title,
            'post_content' => $project_content,
            'post_author' => $customer_id,
            'post_status' => 'publish',
            'post_type' => 'project',
        ];

        if ($project_id > 0) {
            $post_data['ID'] = $project_id;
            $result = wp_update_post($post_data, true);
            $message = 'پروژه با موفقیت به‌روزرسانی شد.';
        } else {
            $result = wp_insert_post($post_data, true);
            $message = 'پروژه جدید با موفقیت ایجاد شد.';
        }

        if (is_wp_error($result)) {
            $this->clean_buffer_and_send_error(PuzzlingCRM_Error_Codes::PRJ_ERR_PROJECT_SAVE_FAILED, ['wp_error_message' => $result->get_error_message()]);
        }

        $the_project_id = is_int($result) ? $result : $project_id;
        
        update_post_meta($the_project_id, '_contract_id', $contract_id);
        wp_set_object_terms($the_project_id, $project_status_id, 'project_status');
        
        // Project Manager
        if (isset($_POST['project_manager'])) {
            $project_manager_id = intval($_POST['project_manager']);
            if ($project_manager_id > 0) {
                update_post_meta($the_project_id, '_project_manager', $project_manager_id);
            } else {
                delete_post_meta($the_project_id, '_project_manager');
            }
        }
        
        // Project Dates
        if (isset($_POST['project_start_date']) && !empty($_POST['project_start_date'])) {
            $start_date_jalali = sanitize_text_field($_POST['project_start_date']);
            if (preg_match('/^\d{4}-\d{1,2}-\d{1,2}$/', trim($start_date_jalali))) {
                $start_date_gregorian = trim($start_date_jalali);
            } elseif (function_exists('puzzling_jalali_to_gregorian')) {
                $start_date_gregorian = puzzling_jalali_to_gregorian($start_date_jalali);
            } elseif (function_exists('jalali_to_gregorian')) {
                $date_parts = explode('/', $start_date_jalali);
                if (count($date_parts) == 3) {
                    $start_date_gregorian = jalali_to_gregorian($date_parts[0], $date_parts[1], $date_parts[2]);
                    $start_date_gregorian = sprintf('%04d-%02d-%02d', $start_date_gregorian[0], $start_date_gregorian[1], $start_date_gregorian[2]);
                } else {
                    $start_date_gregorian = '';
                }
            } else {
                $start_date_gregorian = $start_date_jalali; // Fallback
            }
            if ($start_date_gregorian) {
                update_post_meta($the_project_id, '_project_start_date', $start_date_gregorian);
            }
        }
        
        if (isset($_POST['project_end_date']) && !empty($_POST['project_end_date'])) {
            $end_date_jalali = sanitize_text_field($_POST['project_end_date']);
            if (preg_match('/^\d{4}-\d{1,2}-\d{1,2}$/', trim($end_date_jalali))) {
                $end_date_gregorian = trim($end_date_jalali);
            } elseif (function_exists('puzzling_jalali_to_gregorian')) {
                $end_date_gregorian = puzzling_jalali_to_gregorian($end_date_jalali);
            } elseif (function_exists('jalali_to_gregorian')) {
                $date_parts = explode('/', $end_date_jalali);
                if (count($date_parts) == 3) {
                    $end_date_gregorian = jalali_to_gregorian($date_parts[0], $date_parts[1], $date_parts[2]);
                    $end_date_gregorian = sprintf('%04d-%02d-%02d', $end_date_gregorian[0], $end_date_gregorian[1], $end_date_gregorian[2]);
                } else {
                    $end_date_gregorian = '';
                }
            } else {
                $end_date_gregorian = $end_date_jalali; // Fallback
            }
            if ($end_date_gregorian) {
                update_post_meta($the_project_id, '_project_end_date', $end_date_gregorian);
            }
        }
        
        // Project Priority
        if (isset($_POST['project_priority'])) {
            $priority = sanitize_key($_POST['project_priority']);
            update_post_meta($the_project_id, '_project_priority', $priority);
        }
        
        // Assigned Team Members
        if (isset($_POST['assigned_team_members']) && is_array($_POST['assigned_team_members'])) {
            $assigned_members = array_map('intval', $_POST['assigned_team_members']);
            $assigned_members = array_filter($assigned_members);
            update_post_meta($the_project_id, '_assigned_team_members', $assigned_members);
        } else {
            delete_post_meta($the_project_id, '_assigned_team_members');
        }
        
        // Project Tags
        if (isset($_POST['project_tags']) && !empty($_POST['project_tags'])) {
            $tags_string = sanitize_text_field($_POST['project_tags']);
            // Split by Persian comma (،) or English comma
            $tags = preg_split('/[،,]+/', $tags_string);
            $tags = array_map('trim', $tags);
            $tags = array_filter($tags);
            
            // Check if project_tag taxonomy exists, if not create it
            if (!taxonomy_exists('project_tag')) {
                register_taxonomy('project_tag', 'project', [
                    'label' => __('برچسب‌های پروژه', 'puzzlingcrm'),
                    'hierarchical' => false,
                    'public' => false,
                    'show_ui' => true,
                    'show_admin_column' => true,
                ]);
            }
            
            if (!empty($tags)) {
                wp_set_object_terms($the_project_id, $tags, 'project_tag');
            } else {
                wp_set_object_terms($the_project_id, [], 'project_tag');
            }
        } else {
            wp_set_object_terms($the_project_id, [], 'project_tag');
        }
        
        // Project Logo
        if (isset($_FILES['project_logo']) && $_FILES['project_logo']['error'] == 0) {
            $attachment_id = media_handle_upload('project_logo', $the_project_id);
            if (!is_wp_error($attachment_id)) {
                set_post_thumbnail($the_project_id, $attachment_id);
            }
        }
        
        PuzzlingCRM_Logger::add('پروژه مدیریت شد', ['action' => $project_id > 0 ? 'به‌روزرسانی' : 'ایجاد', 'project_id' => $the_project_id], 'success');

        ob_end_clean();
        wp_send_json_success(['message' => $message, 'reload' => true]);
    }

    public function ajax_delete_project() {
        $this->load_dependencies();
        ob_start();

        if (!current_user_can('delete_posts')) {
            $this->clean_buffer_and_send_error(PuzzlingCRM_Error_Codes::PRJ_ERR_ACCESS_DENIED);
        }
        if (!check_ajax_referer('puzzlingcrm-ajax-nonce', 'security', false)) {
            $this->clean_buffer_and_send_error(PuzzlingCRM_Error_Codes::PRJ_ERR_NONCE_FAILED);
        }
        if (!isset($_POST['project_id']) || !isset($_POST['nonce'])) {
            $this->clean_buffer_and_send_error(PuzzlingCRM_Error_Codes::PRJ_ERR_MISSING_PROJECT_DATA);
        }
        
        $project_id = intval($_POST['project_id']);
        if (!wp_verify_nonce($_POST['nonce'], 'puzzling_delete_project_' . $project_id)) {
            $this->clean_buffer_and_send_error(PuzzlingCRM_Error_Codes::PRJ_ERR_NONCE_FAILED, ['specific_nonce' => 'failed']);
        }
        
        $project = get_post($project_id);
        if (!$project || $project->post_type !== 'project') {
            $this->clean_buffer_and_send_error(PuzzlingCRM_Error_Codes::PRJ_ERR_PROJECT_NOT_FOUND, ['project_id' => $project_id]);
        }
        
        $project_title = $project->post_title;
        if (wp_delete_post($project_id, true)) {
            PuzzlingCRM_Logger::add('پروژه حذف شد', ['content' => "پروژه '{$project_title}' حذف شد.", 'type' => 'log']);
            ob_end_clean(); 
            wp_send_json_success(['message' => 'پروژه با موفقیت حذف شد.']);
        } else {
            $this->clean_buffer_and_send_error(PuzzlingCRM_Error_Codes::PRJ_ERR_PROJECT_DELETE_FAILED, ['project_id' => $project_id]);
        }
    }

    public function ajax_manage_contract() {
        $this->load_dependencies();
        ob_start();

        if (!check_ajax_referer('puzzlingcrm-ajax-nonce', 'security', false)) {
            $this->clean_buffer_and_send_error(PuzzlingCRM_Error_Codes::PRJ_ERR_NONCE_FAILED);
        }
        if (!current_user_can('manage_options')) {
            $this->clean_buffer_and_send_error(PuzzlingCRM_Error_Codes::PRJ_ERR_ACCESS_DENIED);
        }

        $contract_id = isset($_POST['contract_id']) ? intval($_POST['contract_id']) : 0;
        $customer_id = isset($_POST['customer_id']) ? intval($_POST['customer_id']) : 0;
        $start_date_jalali = isset($_POST['_project_start_date']) ? sanitize_text_field($_POST['_project_start_date']) : '';
        $contract_title = isset($_POST['contract_title']) && !empty($_POST['contract_title']) ? sanitize_text_field($_POST['contract_title']) : '';
        $total_amount = isset($_POST['total_amount']) ? sanitize_text_field($_POST['total_amount']) : '';
        $total_installments = isset($_POST['total_installments']) ? intval($_POST['total_installments']) : 1;

        if (empty($customer_id) || empty($start_date_jalali)) {
            $this->clean_buffer_and_send_error(PuzzlingCRM_Error_Codes::PRJ_ERR_MISSING_CONTRACT_DATA);
        }

        $customer_data = get_userdata($customer_id);
        if (!$customer_data) {
            $this->clean_buffer_and_send_error(PuzzlingCRM_Error_Codes::PRJ_ERR_INVALID_CUSTOMER, ['customer_id' => $customer_id]);
        }
        
        $customer_display_name = $customer_data->display_name ?? 'مشتری نامشخص';
        if (empty($contract_title)) {
            $contract_title = 'قرارداد برای ' . $customer_display_name;
        }

        // Convert and validate start date with error logging
        // Accept Gregorian Y-m-d from React date inputs
        if (preg_match('/^\d{4}-\d{1,2}-\d{1,2}$/', trim($start_date_jalali))) {
            $start_date_gregorian = trim($start_date_jalali);
        } else {
            $start_date_gregorian = puzzling_jalali_to_gregorian($start_date_jalali, true);
        }
        $start_timestamp = strtotime($start_date_gregorian);

        if (empty($start_date_gregorian) || $start_timestamp === false) {
            error_log('PuzzlingCRM Contract: Invalid start date conversion - Input: ' . $start_date_jalali . ', Output: ' . $start_date_gregorian);
            $this->clean_buffer_and_send_error(PuzzlingCRM_Error_Codes::PRJ_ERR_INVALID_START_DATE, ['input_date' => $start_date_jalali]);
        }
        
        $post_data = [
            'post_title' => $contract_title, 'post_author' => $customer_id, 'post_status' => 'publish', 'post_type' => 'contract',
        ];

        if ($contract_id > 0) {
            $post_data['ID'] = $contract_id;
            $result = wp_update_post($post_data, true);
            $message = 'قرارداد با موفقیت به‌روزرسانی شد.';
        } else {
            $result = wp_insert_post($post_data, true);
            $message = 'قرارداد جدید با موفقیت ایجاد شد.';
        }

        if (is_wp_error($result)) {
            $this->clean_buffer_and_send_error(PuzzlingCRM_Error_Codes::PRJ_ERR_CONTRACT_SAVE_FAILED, ['wp_error_message' => $result->get_error_message()]);
        }

        $the_contract_id = is_int($result) ? $result : $contract_id;

        update_post_meta($the_contract_id, '_total_amount', preg_replace('/[^\d]/', '', $total_amount));
        update_post_meta($the_contract_id, '_total_installments', $total_installments);

        // Generate or update contract number
        // For new contracts, always generate
        // For existing contracts, only update if not already set or if explicitly provided
        $existing_contract_number = get_post_meta($the_contract_id, '_contract_number', true);
        
        if ($contract_id == 0) {
            // New contract - always generate number
            if (function_exists('jdate')) {
                $contract_number = 'puz-' . jdate('ymd', $start_timestamp, '', 'en') . '-' . $customer_id;
            } else {
                // Fallback: use current date
                $date_parts = explode('-', $start_date_gregorian);
                $contract_number = 'puz-' . substr($date_parts[0], 2) . $date_parts[1] . $date_parts[2] . '-' . $customer_id;
            }
            update_post_meta($the_contract_id, '_contract_number', $contract_number);
        } else {
            // Existing contract - only update if number is missing or explicitly provided
            if (empty($existing_contract_number)) {
                // Generate if missing
                if (function_exists('jdate')) {
                    $contract_number = 'puz-' . jdate('ymd', $start_timestamp, '', 'en') . '-' . $customer_id;
                } else {
                    $date_parts = explode('-', $start_date_gregorian);
                    $contract_number = 'puz-' . substr($date_parts[0], 2) . $date_parts[1] . $date_parts[2] . '-' . $customer_id;
                }
                update_post_meta($the_contract_id, '_contract_number', $contract_number);
            }
            // If contract_number is provided in POST, update it (for manual override)
            if (isset($_POST['contract_number']) && !empty($_POST['contract_number'])) {
                $new_contract_number = sanitize_text_field($_POST['contract_number']);
                if (!empty($new_contract_number)) {
                    update_post_meta($the_contract_id, '_contract_number', $new_contract_number);
                }
            }
        }

        update_post_meta($the_contract_id, '_project_start_date', $start_date_gregorian);
        
        $duration = isset($_POST['_project_contract_duration']) ? sanitize_key($_POST['_project_contract_duration']) : '1-month';
        update_post_meta($the_contract_id, '_project_contract_duration', $duration);
        $end_date = date('Y-m-d', strtotime($start_date_gregorian . ' +' . str_replace('-', ' ', $duration)));
        update_post_meta($the_contract_id, '_project_end_date', $end_date);
        update_post_meta($the_contract_id, '_project_subscription_model', sanitize_key($_POST['_project_subscription_model']));

        // Process installments with comprehensive validation
        $installments = [];
        $installment_errors = [];
        
        if (isset($_POST['payment_amount']) && is_array($_POST['payment_amount'])) {
            $payment_amounts = $_POST['payment_amount'];
            $payment_due_dates = isset($_POST['payment_due_date']) && is_array($_POST['payment_due_date']) ? $_POST['payment_due_date'] : [];
            $payment_statuses = isset($_POST['payment_status']) && is_array($_POST['payment_status']) ? $_POST['payment_status'] : [];
            
            $installment_count = count($payment_amounts);
            
            // Validate that all arrays have the same length
            if (count($payment_due_dates) !== $installment_count || count($payment_statuses) !== $installment_count) {
                error_log('PuzzlingCRM Contract: Mismatched installment arrays - amounts: ' . $installment_count . ', dates: ' . count($payment_due_dates) . ', statuses: ' . count($payment_statuses));
            }
            
            for ($i = 0; $i < $installment_count; $i++) {
                $amount_raw = isset($payment_amounts[$i]) ? trim($payment_amounts[$i]) : '';
                $jalali_date = isset($payment_due_dates[$i]) ? trim($payment_due_dates[$i]) : '';
                $status = isset($payment_statuses[$i]) ? trim($payment_statuses[$i]) : 'pending';
                
                // Skip empty rows
                if (empty($amount_raw) && empty($jalali_date)) {
                    continue;
                }
                
                // Validate amount
                if (empty($amount_raw)) {
                    $installment_errors[] = 'قسط شماره ' . ($i + 1) . ': مبلغ وارد نشده است.';
                    continue;
                }
                
                $amount_clean = preg_replace('/[^\d]/', '', $amount_raw);
                if (empty($amount_clean) || intval($amount_clean) <= 0) {
                    $installment_errors[] = 'قسط شماره ' . ($i + 1) . ': مبلغ نامعتبر است.';
                    continue;
                }
                
                // Validate date
                if (empty($jalali_date)) {
                    $installment_errors[] = 'قسط شماره ' . ($i + 1) . ': تاریخ سررسید وارد نشده است.';
                    continue;
                }
                
                // Convert date with error logging (accept Y-m-d from React)
                if (preg_match('/^\d{4}-\d{1,2}-\d{1,2}$/', trim($jalali_date))) {
                    $due_date_gregorian = trim($jalali_date);
                } else {
                    $due_date_gregorian = puzzling_jalali_to_gregorian($jalali_date, true);
                }
                
                if (empty($due_date_gregorian) || strtotime($due_date_gregorian) === false) {
                    error_log("PuzzlingCRM Contract: Invalid installment date - Index: $i, Jalali: $jalali_date");
                    $installment_errors[] = 'قسط شماره ' . ($i + 1) . ': تاریخ سررسید نامعتبر است (' . sanitize_text_field($jalali_date) . ').';
                    continue;
                }
                
                // Validate status
                $valid_statuses = ['pending', 'paid', 'cancelled'];
                if (!in_array($status, $valid_statuses)) {
                    $status = 'pending'; // Default to pending if invalid
                }
                
                // All validations passed, add installment
                $installments[] = [
                    'amount' => $amount_clean,
                    'due_date' => $due_date_gregorian,
                    'status' => $status,
                ];
            }
        }
        
        // Handle installment validation errors
        if (!empty($installment_errors)) {
            if ($contract_id == 0) {
                // For new contracts, fail if there are errors
                $error_message = 'خطا در ثبت اقساط:\n' . implode('\n', array_slice($installment_errors, 0, 5));
                if (count($installment_errors) > 5) {
                    $error_message .= '\nو ' . (count($installment_errors) - 5) . ' خطای دیگر...';
                }
                $this->clean_buffer_and_send_error(PuzzlingCRM_Error_Codes::PRJ_ERR_MISSING_CONTRACT_DATA, ['installment_errors' => $installment_errors, 'message' => $error_message]);
            } else {
                // For existing contracts, log but continue
                error_log('PuzzlingCRM Contract: Installment validation errors (edit mode): ' . implode(', ', $installment_errors));
            }
        }
        
        // Update installments meta
        update_post_meta($the_contract_id, '_installments', $installments);
        
        // If creating new contract and no valid installments, warn but don't fail (allow contracts without installments)
        if ($contract_id == 0 && empty($installments)) {
            error_log('PuzzlingCRM Contract: No valid installments for new contract ID: ' . $the_contract_id);
        }
        
        // Validate total amount matches installments (if installments exist)
        if (!empty($installments)) {
            $calculated_total = 0;
            foreach ($installments as $inst) {
                $calculated_total += intval($inst['amount']);
            }
            $stored_total = intval(preg_replace('/[^\d]/', '', $total_amount));
            
            // If totals don't match, update the stored total to match installments
            if ($calculated_total > 0 && abs($calculated_total - $stored_total) > 100) {
                // Difference is more than 100, update total
                update_post_meta($the_contract_id, '_total_amount', $calculated_total);
                error_log('PuzzlingCRM Contract: Total amount updated to match installments - Old: ' . $stored_total . ', New: ' . $calculated_total);
            }
        }
        
        PuzzlingCRM_Logger::add('قرارداد مدیریت شد', [
            'action' => $contract_id > 0 ? 'به‌روزرسانی' : 'ایجاد', 
            'contract_id' => $the_contract_id,
            'installments_count' => count($installments),
            'total_amount' => get_post_meta($the_contract_id, '_total_amount', true)
        ], 'success');
        
        // When creating a new contract, optionally create projects from WC product or from template
        if ( $contract_id == 0 ) {
            $product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
            $project_assignments = isset( $_POST['project_assignments'] ) && is_array( $_POST['project_assignments'] )
                ? array_map( 'intval', $_POST['project_assignments'] ) : [];

            if ( $product_id > 0 && function_exists( 'wc_get_product' ) ) {
                update_post_meta( $the_contract_id, '_wc_product_id', $product_id );
                $product = wc_get_product( $product_id );
                $active_status = get_term_by( 'slug', 'active', 'project_status' );
                if ( $product ) {
                    $child_ids = $product->is_type( 'grouped' ) ? $product->get_children() : [ $product->get_id() ];
                    $idx = 0;
                    foreach ( $child_ids as $child_id ) {
                        $child = wc_get_product( $child_id );
                        if ( ! $child ) continue;
                        $project_id = wp_insert_post( [
                            'post_title'   => $child->get_name(),
                            'post_author'  => (int) get_post( $the_contract_id )->post_author,
                            'post_status'  => 'publish',
                            'post_type'    => 'project',
                        ], true );
                        if ( ! is_wp_error( $project_id ) ) {
                            update_post_meta( $project_id, '_contract_id', $the_contract_id );
                            $dept_id = (int) get_post_meta( $child_id, '_puzzling_default_department_id', true );
                            if ( $dept_id > 0 ) {
                                update_post_meta( $project_id, '_department_id', $dept_id );
                            }
                            $assigned = isset( $project_assignments[ $idx ] ) ? (int) $project_assignments[ $idx ] : 0;
                            if ( $assigned > 0 ) {
                                update_post_meta( $project_id, '_assigned_to', $assigned );
                            }
                            if ( $active_status ) {
                                wp_set_object_terms( $project_id, $active_status->term_id, 'project_status' );
                            }
                            $idx++;
                        }
                    }
                }
            } else {
                $project_template_id = isset( $_POST['project_template_id'] ) ? absint( $_POST['project_template_id'] ) : 0;
                if ( $project_template_id > 0 ) {
                    $project_id = $this->create_project_from_template_for_contract( $project_template_id, $the_contract_id );
                    if ( is_int( $project_id ) ) {
                        $this->create_tasks_from_template_for_project( $project_id, $project_template_id );
                    }
                }
            }
        }

        ob_end_clean();
        wp_send_json_success([
            'message' => $message, 
            'reload' => true,
            'contract_id' => $the_contract_id,
            'contract_number' => get_post_meta($the_contract_id, '_contract_number', true)
        ]);
    }
    
    public function ajax_delete_contract() {
        $this->load_dependencies();
        ob_start();

        if (!check_ajax_referer('puzzlingcrm-ajax-nonce', 'security', false)) {
            $this->clean_buffer_and_send_error(PuzzlingCRM_Error_Codes::PRJ_ERR_NONCE_FAILED);
        }
        if (!current_user_can('manage_options')) {
            $this->clean_buffer_and_send_error(PuzzlingCRM_Error_Codes::PRJ_ERR_ACCESS_DENIED);
        }
        if (!isset($_POST['contract_id']) || !isset($_POST['nonce'])) {
            $this->clean_buffer_and_send_error(PuzzlingCRM_Error_Codes::PRJ_ERR_MISSING_CONTRACT_DATA);
        }
        
        $contract_id = intval($_POST['contract_id']);
        if (!wp_verify_nonce($_POST['nonce'], 'puzzling_delete_contract_' . $contract_id)) {
            $this->clean_buffer_and_send_error(PuzzlingCRM_Error_Codes::PRJ_ERR_NONCE_FAILED, ['specific_nonce' => 'failed']);
        }
        
        $contract = get_post($contract_id);
        if (!$contract || $contract->post_type !== 'contract') {
            $this->clean_buffer_and_send_error(PuzzlingCRM_Error_Codes::PRJ_ERR_CONTRACT_NOT_FOUND, ['contract_id' => $contract_id]);
        }
        
        $contract_title = $contract->post_title;
        
        $related_items_query = new WP_Query(['post_type' => ['project', 'pro_invoice'], 'posts_per_page' => -1, 'meta_key' => '_contract_id', 'meta_value' => $contract_id]);
        if ($related_items_query->have_posts()) {
            foreach ($related_items_query->posts as $related_post) {
                wp_delete_post($related_post->ID, true); 
            }
        }
        
        if (wp_delete_post($contract_id, true)) {
            PuzzlingCRM_Logger::add('قرارداد حذف شد', ['content' => "قرارداد '{$contract_title}' حذف شد.", 'type' => 'log']);
            ob_end_clean();
            wp_send_json_success(['message' => 'قرارداد و تمام داده‌های مرتبط با آن با موفقیت حذف شدند.', 'reload' => true]);
        } else {
            $this->clean_buffer_and_send_error(PuzzlingCRM_Error_Codes::PRJ_ERR_CONTRACT_DELETE_FAILED, ['contract_id' => $contract_id]);
        }
    }

    public function ajax_cancel_contract() {
        $this->load_dependencies();
        ob_start();

        if (!check_ajax_referer('puzzlingcrm-ajax-nonce', 'security', false)) {
            $this->clean_buffer_and_send_error(PuzzlingCRM_Error_Codes::PRJ_ERR_NONCE_FAILED);
        }
        if (!current_user_can('manage_options')) {
            $this->clean_buffer_and_send_error(PuzzlingCRM_Error_Codes::PRJ_ERR_ACCESS_DENIED);
        }

        $contract_id = isset($_POST['contract_id']) ? intval($_POST['contract_id']) : 0;
        $reason = isset($_POST['reason']) ? sanitize_textarea_field($_POST['reason']) : 'دلیلی ذکر نشده است.';

        $post = get_post($contract_id);
        if ($contract_id > 0 && $post && $post->post_type === 'contract') {
            update_post_meta($contract_id, '_contract_status', 'cancelled');
            update_post_meta($contract_id, '_cancellation_reason', $reason);
            update_post_meta($contract_id, '_cancellation_date', current_time('mysql'));
            
            PuzzlingCRM_Logger::add('قرارداد لغو شد', ['contract_id' => $contract_id, 'reason' => $reason], 'warning');
            
            ob_end_clean();
            wp_send_json_success(['message' => 'قرارداد با موفقیت لغو شد.', 'reload' => true]);
        } else {
            $this->clean_buffer_and_send_error(PuzzlingCRM_Error_Codes::PRJ_ERR_CANCEL_CONTRACT_FAILED, ['contract_id' => $contract_id]);
        }
    }

    public function ajax_add_project_to_contract() {
        $this->load_dependencies();
        ob_start();

        if (!check_ajax_referer('puzzlingcrm-ajax-nonce', 'security', false)) {
            $this->clean_buffer_and_send_error(PuzzlingCRM_Error_Codes::PRJ_ERR_NONCE_FAILED);
        }
        if (!current_user_can('manage_options')) {
            $this->clean_buffer_and_send_error(PuzzlingCRM_Error_Codes::PRJ_ERR_ACCESS_DENIED);
        }

        $contract_id = isset($_POST['contract_id']) ? intval($_POST['contract_id']) : 0;
        $project_title = isset($_POST['project_title']) ? sanitize_text_field($_POST['project_title']) : '';
        
        if (empty($contract_id) || empty($project_title)) {
            $this->clean_buffer_and_send_error(PuzzlingCRM_Error_Codes::PRJ_ERR_MISSING_PROJECT_DATA);
        }

        $contract = get_post($contract_id);
        if (!$contract || $contract->post_type !== 'contract') {
             $this->clean_buffer_and_send_error(PuzzlingCRM_Error_Codes::PRJ_ERR_CONTRACT_INVALID, ['contract_id' => $contract_id]);
        }

        $project_id = wp_insert_post([
            'post_title' => $project_title,
            'post_author' => $contract->post_author,
            'post_status' => 'publish',
            'post_type' => 'project'
        ], true);

        if (is_wp_error($project_id)) {
            $this->clean_buffer_and_send_error(PuzzlingCRM_Error_Codes::PRJ_ERR_PROJECT_SAVE_FAILED, ['wp_error_message' => $project_id->get_error_message()]);
        }

        update_post_meta($project_id, '_contract_id', $contract_id);
        
        $active_status = get_term_by('slug', 'active', 'project_status');
        if ($active_status) {
            wp_set_object_terms($project_id, $active_status->term_id, 'project_status');
        }

        PuzzlingCRM_Logger::add('پروژه به قرارداد اضافه شد', ['project_id' => $project_id, 'contract_id' => $contract_id], 'success');
        
        ob_end_clean();
        wp_send_json_success(['message' => 'پروژه جدید با موفقیت به قرارداد اضافه شد.', 'reload' => true]);
    }
    
    public function ajax_add_services_from_product() {
        $this->load_dependencies();
        ob_start();

        if (!check_ajax_referer('puzzlingcrm-ajax-nonce', 'security', false)) {
            $this->clean_buffer_and_send_error(PuzzlingCRM_Error_Codes::PRJ_ERR_NONCE_FAILED);
        }
        if (!current_user_can('manage_options')) {
            $this->clean_buffer_and_send_error(PuzzlingCRM_Error_Codes::PRJ_ERR_ACCESS_DENIED);
        }

        if (!function_exists('wc_get_product')) {
            $this->clean_buffer_and_send_error(PuzzlingCRM_Error_Codes::PRJ_ERR_PRODUCT_WOC_INACTIVE);
        }

        $contract_id = isset($_POST['contract_id']) ? intval($_POST['contract_id']) : 0;
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        
        if (empty($contract_id) || empty($product_id)) {
            $this->clean_buffer_and_send_error(PuzzlingCRM_Error_Codes::PRJ_ERR_MISSING_PRODUCT_DATA, ['contract_id' => $contract_id, 'product_id' => $product_id]);
        }

        $contract = get_post($contract_id);
        $product = wc_get_product($product_id);

        if (!$contract || !$product) {
            $this->clean_buffer_and_send_error(PuzzlingCRM_Error_Codes::PRJ_ERR_PRODUCT_NOT_FOUND, ['contract_found' => (bool)$contract, 'product_found' => (bool)$product]);
        }
        
        $created_projects_count = 0;
        $child_products = $product->is_type('grouped') ? $product->get_children() : [$product->get_id()];
        
        foreach($child_products as $child_product_id) {
            $child_product = wc_get_product($child_product_id);
            if (!$child_product) continue;
            
            $project_id = wp_insert_post(['post_title' => $child_product->get_name(), 'post_author' => $contract->post_author, 'post_status' => 'publish', 'post_type' => 'project'], true);
            
            if (!is_wp_error($project_id)) {
                update_post_meta($project_id, '_contract_id', $contract_id);
                $active_status = get_term_by('slug', 'active', 'project_status');
                if ($active_status) {
                    wp_set_object_terms($project_id, $active_status->term_id, 'project_status');
                }
                $created_projects_count++;
            }
        }

        if($created_projects_count > 0) {
            $message = $created_projects_count . ' پروژه با موفقیت از محصول ایجاد و به این قرارداد متصل شد.';
            PuzzlingCRM_Logger::add('موفقیت در افزودن خدمات', ['content' => $message], 'log');
            ob_end_clean();
            wp_send_json_success(['message' => $message, 'reload' => true]);
        } else {
            $this->clean_buffer_and_send_error(PuzzlingCRM_Error_Codes::PRJ_ERR_PRODUCT_PROJECT_FAILED, ['product_id' => $product_id, 'contract_id' => $contract_id]);
        }
    }

    public function ajax_get_projects_for_customer() {
        $this->load_dependencies();
        ob_start();

        if (!check_ajax_referer('puzzlingcrm-ajax-nonce', 'security', false)) {
            $this->clean_buffer_and_send_error(PuzzlingCRM_Error_Codes::PRJ_ERR_NONCE_FAILED);
        }
        if (!current_user_can('manage_options') || !isset($_POST['customer_id'])) {
            $this->clean_buffer_and_send_error(PuzzlingCRM_Error_Codes::PRJ_ERR_ACCESS_DENIED);
        }

        $customer_id = intval($_POST['customer_id']);
        $projects_posts = get_posts(['post_type' => 'project', 'author' => $customer_id, 'posts_per_page' => -1]);
        
        $projects_data = [];
        if ($projects_posts) {
            foreach ($projects_posts as $project) {
                $projects_data[] = ['id' => $project->ID, 'title' => $project->post_title];
            }
        }
        
        ob_end_clean();
        wp_send_json_success($projects_data);
    }

    /**
     * Get project templates (pzl_project_template) for dashboard contract form.
     */
    public function ajax_get_project_templates() {
        $this->load_dependencies();
        ob_start();
        if ( ! check_ajax_referer( 'puzzlingcrm-ajax-nonce', 'security', false ) ) {
            wp_send_json_error( [ 'message' => 'خطای امنیتی.' ] );
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => 'دسترسی غیرمجاز.' ] );
        }
        $templates = get_posts( [
            'post_type'      => 'pzl_project_template',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
            'post_status'    => 'publish',
        ] );
        $list = [];
        foreach ( $templates as $t ) {
            $list[] = [ 'id' => $t->ID, 'title' => $t->post_title ];
        }
        ob_end_clean();
        wp_send_json_success( [ 'templates' => $list ] );
    }

    /**
     * Create a project linked to a contract from a project template (dashboard flow).
     *
     * @param int $template_id pzl_project_template post ID.
     * @param int $contract_id Contract post ID.
     * @return int|WP_Error Project ID or error.
     */
    private function create_project_from_template_for_contract( $template_id, $contract_id ) {
        $template = get_post( $template_id );
        if ( ! $template || $template->post_type !== 'pzl_project_template' ) {
            return new WP_Error( 'invalid_template', 'قالب پروژه نامعتبر است.' );
        }
        $contract = get_post( $contract_id );
        if ( ! $contract || $contract->post_type !== 'contract' ) {
            return new WP_Error( 'invalid_contract', 'قرارداد نامعتبر است.' );
        }
        $project_title = $template->post_title;
        $project_id = wp_insert_post( [
            'post_title'   => $project_title,
            'post_author'  => $contract->post_author,
            'post_status'  => 'publish',
            'post_type'    => 'project',
        ], true );
        if ( is_wp_error( $project_id ) ) {
            return $project_id;
        }
        update_post_meta( $project_id, '_contract_id', $contract_id );
        $active_status = get_term_by( 'slug', 'active', 'project_status' );
        if ( $active_status ) {
            wp_set_object_terms( $project_id, $active_status->term_id, 'project_status' );
        }
        if ( class_exists( 'PuzzlingCRM_Logger' ) ) {
            PuzzlingCRM_Logger::add( 'پروژه از قالب ایجاد شد', [ 'project_id' => $project_id, 'contract_id' => $contract_id, 'template_id' => $template_id ], 'success' );
        }
        return $project_id;
    }

    /**
     * Create tasks for a project from template's _default_tasks. Supports optional per-employee: if a task has assigned_role, one task per user with that role is created.
     *
     * @param int $project_id  Project post ID.
     * @param int $template_id pzl_project_template post ID.
     */
    private function create_tasks_from_template_for_project( $project_id, $template_id ) {
        $default_tasks = get_post_meta( $template_id, '_default_tasks', true );
        if ( empty( $default_tasks ) || ! is_array( $default_tasks ) ) {
            return;
        }
        $default_status_slug = function_exists( 'puzzling_get_default_task_status_slug' ) ? puzzling_get_default_task_status_slug() : 'to-do';
        $default_status = get_term_by( 'slug', $default_status_slug, 'task_status' );
        $default_term_id = $default_status ? $default_status->term_id : 0;

        foreach ( $default_tasks as $task_data ) {
            $title = isset( $task_data['title'] ) ? sanitize_text_field( $task_data['title'] ) : '';
            if ( $title === '' ) {
                continue;
            }
            $content = isset( $task_data['content'] ) ? wp_kses_post( $task_data['content'] ) : '';
            $assigned_role = isset( $task_data['assigned_role'] ) ? sanitize_key( $task_data['assigned_role'] ) : '';

            if ( $assigned_role !== '' ) {
                $users_with_role = get_users( [ 'role' => $assigned_role, 'fields' => 'ID' ] );
                if ( empty( $users_with_role ) ) {
                    $task_id = wp_insert_post( [
                        'post_title'   => $title,
                        'post_content' => $content,
                        'post_type'    => 'task',
                        'post_status'  => 'publish',
                    ], true );
                    if ( ! is_wp_error( $task_id ) ) {
                        update_post_meta( $task_id, '_project_id', $project_id );
                        if ( $default_term_id ) {
                            wp_set_object_terms( $task_id, $default_term_id, 'task_status' );
                        }
                    }
                    continue;
                }
                foreach ( $users_with_role as $user_id ) {
                    $task_id = wp_insert_post( [
                        'post_title'   => $title,
                        'post_content' => $content,
                        'post_type'    => 'task',
                        'post_status'  => 'publish',
                    ], true );
                    if ( ! is_wp_error( $task_id ) ) {
                        update_post_meta( $task_id, '_project_id', $project_id );
                        update_post_meta( $task_id, '_assigned_to', $user_id );
                        if ( $default_term_id ) {
                            wp_set_object_terms( $task_id, $default_term_id, 'task_status' );
                        }
                    }
                }
            } else {
                $task_id = wp_insert_post( [
                    'post_title'   => $title,
                    'post_content' => $content,
                    'post_type'    => 'task',
                    'post_status'  => 'publish',
                ], true );
                if ( ! is_wp_error( $task_id ) ) {
                    update_post_meta( $task_id, '_project_id', $project_id );
                    if ( $default_term_id ) {
                        wp_set_object_terms( $task_id, $default_term_id, 'task_status' );
                    }
                }
            }
        }
        if ( class_exists( 'PuzzlingCRM_Logger' ) ) {
            PuzzlingCRM_Logger::add( 'تسک‌ها از قالب ایجاد شدند', [ 'project_id' => $project_id, 'template_id' => $template_id ], 'success' );
        }
    }
}