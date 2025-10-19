<?php
/**
 * Elasticsearch Handler for Advanced Search
 *
 * @package PuzzlingCRM
 * @subpackage PuzzlingCRM/includes
 * @author     Arsalan Arghavan
 */

class PuzzlingCRM_Elasticsearch_Handler {
    
    private $client;
    private $index_prefix = 'puzzlingcrm_';
    private $host;
    private $port;
    private $username;
    private $password;
    
    public function __construct() {
        $this->init_elasticsearch();
        add_action('wp_ajax_puzzling_elasticsearch_search', [$this, 'handle_search']);
        add_action('wp_ajax_puzzling_elasticsearch_suggest', [$this, 'handle_suggest']);
        add_action('wp_ajax_puzzling_elasticsearch_index', [$this, 'handle_index']);
        add_action('wp_ajax_puzzling_elasticsearch_delete', [$this, 'handle_delete']);
        
        // Auto-index hooks
        add_action('save_post', [$this, 'auto_index_post'], 10, 2);
        add_action('delete_post', [$this, 'auto_delete_post']);
        add_action('user_register', [$this, 'auto_index_user']);
        add_action('profile_update', [$this, 'auto_index_user']);
        add_action('delete_user', [$this, 'auto_delete_user']);
    }
    
    private function init_elasticsearch() {
        $settings = get_option('puzzlingcrm_elasticsearch_settings', []);
        
        $this->host = $settings['host'] ?? 'localhost';
        $this->port = $settings['port'] ?? 9200;
        $this->username = $settings['username'] ?? '';
        $this->password = $settings['password'] ?? '';
        
        // Initialize Elasticsearch client
        $this->init_client();
    }
    
    private function init_client() {
        try {
            $config = [
                'host' => $this->host . ':' . $this->port
            ];
            
            if (!empty($this->username) && !empty($this->password)) {
                $config['username'] = $this->username;
                $config['password'] = $this->password;
            }
            
            // For now, we'll use a simple HTTP client approach
            // In production, you'd use the official Elasticsearch PHP client
            $this->client = $config;
        } catch (Exception $e) {
            error_log('Elasticsearch connection failed: ' . $e->getMessage());
        }
    }
    
    public function handle_search() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'nonce');
        
        $query = sanitize_text_field($_POST['query'] ?? '');
        $type = sanitize_text_field($_POST['type'] ?? 'all');
        $page = intval($_POST['page'] ?? 1);
        $per_page = intval($_POST['per_page'] ?? 20);
        $filters = $_POST['filters'] ?? [];
        
        if (empty($query)) {
            wp_send_json_error('Query is required');
        }
        
        $results = $this->search($query, $type, $page, $per_page, $filters);
        
        if ($results !== false) {
            wp_send_json_success($results);
        } else {
            wp_send_json_error('Search failed');
        }
    }
    
    public function handle_suggest() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'nonce');
        
        $query = sanitize_text_field($_POST['query'] ?? '');
        $type = sanitize_text_field($_POST['type'] ?? 'all');
        
        if (empty($query)) {
            wp_send_json_error('Query is required');
        }
        
        $suggestions = $this->get_suggestions($query, $type);
        
        wp_send_json_success($suggestions);
    }
    
    public function handle_index() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions', 'Forbidden', ['response' => 403]);
        }
        
        $type = sanitize_text_field($_POST['type'] ?? '');
        $id = intval($_POST['id'] ?? 0);
        
        if (empty($type) || empty($id)) {
            wp_send_json_error('Type and ID are required');
        }
        
        $result = $this->index_document($type, $id);
        
        if ($result) {
            wp_send_json_success(['message' => 'Document indexed successfully']);
        } else {
            wp_send_json_error('Failed to index document');
        }
    }
    
    public function handle_delete() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions', 'Forbidden', ['response' => 403]);
        }
        
        $type = sanitize_text_field($_POST['type'] ?? '');
        $id = intval($_POST['id'] ?? 0);
        
        if (empty($type) || empty($id)) {
            wp_send_json_error('Type and ID are required');
        }
        
        $result = $this->delete_document($type, $id);
        
        if ($result) {
            wp_send_json_success(['message' => 'Document deleted successfully']);
        } else {
            wp_send_json_error('Failed to delete document');
        }
    }
    
    public function search($query, $type = 'all', $page = 1, $per_page = 20, $filters = []) {
        $index = $this->get_index_name($type);
        $from = ($page - 1) * $per_page;
        
        $search_body = [
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'multi_match' => [
                                'query' => $query,
                                'fields' => $this->get_search_fields($type),
                                'type' => 'best_fields',
                                'fuzziness' => 'AUTO'
                            ]
                        ]
                    ]
                ]
            ],
            'highlight' => [
                'fields' => $this->get_highlight_fields($type)
            ],
            'from' => $from,
            'size' => $per_page,
            'sort' => [
                '_score' => ['order' => 'desc']
            ]
        ];
        
        // Add filters
        if (!empty($filters)) {
            $filter_queries = [];
            foreach ($filters as $field => $value) {
                if (!empty($value)) {
                    $filter_queries[] = [
                        'term' => [$field => $value]
                    ];
                }
            }
            
            if (!empty($filter_queries)) {
                $search_body['query']['bool']['filter'] = $filter_queries;
            }
        }
        
        $response = $this->make_request('GET', "/{$index}/_search", $search_body);
        
        if ($response && isset($response['hits'])) {
            return $this->format_search_results($response, $type);
        }
        
        return false;
    }
    
    public function get_suggestions($query, $type = 'all') {
        $index = $this->get_index_name($type);
        
        $suggest_body = [
            'suggest' => [
                'suggestion' => [
                    'prefix' => $query,
                    'completion' => [
                        'field' => 'suggest',
                        'size' => 10
                    ]
                ]
            ]
        ];
        
        $response = $this->make_request('GET', "/{$index}/_search", $suggest_body);
        
        if ($response && isset($response['suggest']['suggestion'])) {
            $suggestions = [];
            foreach ($response['suggest']['suggestion'] as $suggestion) {
                if (isset($suggestion['options'])) {
                    foreach ($suggestion['options'] as $option) {
                        $suggestions[] = [
                            'text' => $option['text'],
                            'score' => $option['score']
                        ];
                    }
                }
            }
            return $suggestions;
        }
        
        return [];
    }
    
    public function index_document($type, $id) {
        $index = $this->get_index_name($type);
        $document = $this->prepare_document($type, $id);
        
        if (!$document) {
            return false;
        }
        
        $response = $this->make_request('PUT', "/{$index}/_doc/{$id}", $document);
        
        return $response && !isset($response['error']);
    }
    
    public function delete_document($type, $id) {
        $index = $this->get_index_name($type);
        
        $response = $this->make_request('DELETE', "/{$index}/_doc/{$id}");
        
        return $response && !isset($response['error']);
    }
    
    public function auto_index_post($post_id, $post) {
        if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
            return;
        }
        
        $post_types = ['project', 'task', 'contract', 'lead', 'ticket'];
        
        if (in_array($post->post_type, $post_types)) {
            $this->index_document($post->post_type, $post_id);
        }
    }
    
    public function auto_delete_post($post_id) {
        $post = get_post($post_id);
        if (!$post) return;
        
        $post_types = ['project', 'task', 'contract', 'lead', 'ticket'];
        
        if (in_array($post->post_type, $post_types)) {
            $this->delete_document($post->post_type, $post_id);
        }
    }
    
    public function auto_index_user($user_id) {
        $this->index_document('user', $user_id);
    }
    
    public function auto_delete_user($user_id) {
        $this->delete_document('user', $user_id);
    }
    
    private function prepare_document($type, $id) {
        switch ($type) {
            case 'project':
                return $this->prepare_project_document($id);
            case 'task':
                return $this->prepare_task_document($id);
            case 'contract':
                return $this->prepare_contract_document($id);
            case 'lead':
                return $this->prepare_lead_document($id);
            case 'ticket':
                return $this->prepare_ticket_document($id);
            case 'user':
                return $this->prepare_user_document($id);
            default:
                return false;
        }
    }
    
    private function prepare_project_document($project_id) {
        $project = get_post($project_id);
        if (!$project) return false;
        
        $client_id = get_post_meta($project_id, '_client_id', true);
        $client = $client_id ? get_user_by('ID', $client_id) : null;
        
        $status = wp_get_post_terms($project_id, 'project_status');
        $status_name = !empty($status) ? $status[0]->name : '';
        
        return [
            'id' => $project_id,
            'title' => $project->post_title,
            'content' => $project->post_content,
            'excerpt' => $project->post_excerpt,
            'status' => $status_name,
            'client_name' => $client ? $client->display_name : '',
            'client_email' => $client ? $client->user_email : '',
            'created_date' => $project->post_date,
            'modified_date' => $project->post_modified,
            'type' => 'project',
            'suggest' => [
                'input' => [$project->post_title, $client ? $client->display_name : '']
            ]
        ];
    }
    
    private function prepare_task_document($task_id) {
        $task = get_post($task_id);
        if (!$task) return false;
        
        $assigned_to = get_post_meta($task_id, '_assigned_to', true);
        $assignee = $assigned_to ? get_user_by('ID', $assigned_to) : null;
        
        $project_id = get_post_meta($task_id, '_project_id', true);
        $project = $project_id ? get_post($project_id) : null;
        
        $priority = wp_get_post_terms($task_id, 'task_priority');
        $priority_name = !empty($priority) ? $priority[0]->name : '';
        
        $status = wp_get_post_terms($task_id, 'task_status');
        $status_name = !empty($status) ? $status[0]->name : '';
        
        return [
            'id' => $task_id,
            'title' => $task->post_title,
            'content' => $task->post_content,
            'excerpt' => $task->post_excerpt,
            'status' => $status_name,
            'priority' => $priority_name,
            'assignee_name' => $assignee ? $assignee->display_name : '',
            'assignee_email' => $assignee ? $assignee->user_email : '',
            'project_title' => $project ? $project->post_title : '',
            'due_date' => get_post_meta($task_id, '_due_date', true),
            'created_date' => $task->post_date,
            'modified_date' => $task->post_modified,
            'type' => 'task',
            'suggest' => [
                'input' => [$task->post_title, $assignee ? $assignee->display_name : '', $project ? $project->post_title : '']
            ]
        ];
    }
    
    private function prepare_contract_document($contract_id) {
        $contract = get_post($contract_id);
        if (!$contract) return false;
        
        $client_id = get_post_meta($contract_id, '_client_id', true);
        $client = $client_id ? get_user_by('ID', $client_id) : null;
        
        $project_id = get_post_meta($contract_id, '_project_id', true);
        $project = $project_id ? get_post($project_id) : null;
        
        return [
            'id' => $contract_id,
            'title' => $contract->post_title,
            'content' => $contract->post_content,
            'excerpt' => $contract->post_excerpt,
            'client_name' => $client ? $client->display_name : '',
            'client_email' => $client ? $client->user_email : '',
            'project_title' => $project ? $project->post_title : '',
            'amount' => get_post_meta($contract_id, '_contract_amount', true),
            'start_date' => get_post_meta($contract_id, '_start_date', true),
            'end_date' => get_post_meta($contract_id, '_end_date', true),
            'created_date' => $contract->post_date,
            'modified_date' => $contract->post_modified,
            'type' => 'contract',
            'suggest' => [
                'input' => [$contract->post_title, $client ? $client->display_name : '', $project ? $project->post_title : '']
            ]
        ];
    }
    
    private function prepare_lead_document($lead_id) {
        $lead = get_post($lead_id);
        if (!$lead) return false;
        
        $email = get_post_meta($lead_id, '_lead_email', true);
        $phone = get_post_meta($lead_id, '_lead_phone', true);
        $company = get_post_meta($lead_id, '_lead_company', true);
        
        $status = wp_get_post_terms($lead_id, 'lead_status');
        $status_name = !empty($status) ? $status[0]->name : '';
        
        return [
            'id' => $lead_id,
            'title' => $lead->post_title,
            'content' => $lead->post_content,
            'excerpt' => $lead->post_excerpt,
            'email' => $email,
            'phone' => $phone,
            'company' => $company,
            'status' => $status_name,
            'created_date' => $lead->post_date,
            'modified_date' => $lead->post_modified,
            'type' => 'lead',
            'suggest' => [
                'input' => [$lead->post_title, $email, $phone, $company]
            ]
        ];
    }
    
    private function prepare_ticket_document($ticket_id) {
        $ticket = get_post($ticket_id);
        if (!$ticket) return false;
        
        $assigned_to = get_post_meta($ticket_id, '_assigned_to', true);
        $assignee = $assigned_to ? get_user_by('ID', $assigned_to) : null;
        
        $priority = wp_get_post_terms($ticket_id, 'ticket_priority');
        $priority_name = !empty($priority) ? $priority[0]->name : '';
        
        $status = wp_get_post_terms($ticket_id, 'ticket_status');
        $status_name = !empty($status) ? $status[0]->name : '';
        
        return [
            'id' => $ticket_id,
            'title' => $ticket->post_title,
            'content' => $ticket->post_content,
            'excerpt' => $ticket->post_excerpt,
            'status' => $status_name,
            'priority' => $priority_name,
            'assignee_name' => $assignee ? $assignee->display_name : '',
            'assignee_email' => $assignee ? $assignee->user_email : '',
            'created_date' => $ticket->post_date,
            'modified_date' => $ticket->post_modified,
            'type' => 'ticket',
            'suggest' => [
                'input' => [$ticket->post_title, $assignee ? $assignee->display_name : '']
            ]
        ];
    }
    
    private function prepare_user_document($user_id) {
        $user = get_user_by('ID', $user_id);
        if (!$user) return false;
        
        return [
            'id' => $user_id,
            'title' => $user->display_name,
            'content' => $user->description,
            'email' => $user->user_email,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'roles' => $user->roles,
            'created_date' => $user->user_registered,
            'type' => 'user',
            'suggest' => [
                'input' => [$user->display_name, $user->user_email, $user->first_name, $user->last_name]
            ]
        ];
    }
    
    private function get_index_name($type) {
        if ($type === 'all') {
            return $this->index_prefix . '*';
        }
        return $this->index_prefix . $type;
    }
    
    private function get_search_fields($type) {
        $base_fields = ['title^3', 'content^2', 'excerpt'];
        
        switch ($type) {
            case 'project':
                return array_merge($base_fields, ['client_name^2', 'status']);
            case 'task':
                return array_merge($base_fields, ['assignee_name^2', 'project_title^2', 'status', 'priority']);
            case 'contract':
                return array_merge($base_fields, ['client_name^2', 'project_title^2']);
            case 'lead':
                return array_merge($base_fields, ['email^2', 'phone', 'company^2', 'status']);
            case 'ticket':
                return array_merge($base_fields, ['assignee_name^2', 'status', 'priority']);
            case 'user':
                return array_merge(['title^3', 'content', 'email^2', 'first_name^2', 'last_name^2']);
            default:
                return $base_fields;
        }
    }
    
    private function get_highlight_fields($type) {
        return [
            'title' => ['fragment_size' => 150],
            'content' => ['fragment_size' => 150],
            'excerpt' => ['fragment_size' => 100]
        ];
    }
    
    private function format_search_results($response, $type) {
        $results = [
            'total' => $response['hits']['total']['value'] ?? 0,
            'max_score' => $response['hits']['max_score'] ?? 0,
            'hits' => []
        ];
        
        foreach ($response['hits']['hits'] as $hit) {
            $result = $hit['_source'];
            $result['score'] = $hit['_score'];
            
            if (isset($hit['highlight'])) {
                $result['highlight'] = $hit['highlight'];
            }
            
            $results['hits'][] = $result;
        }
        
        return $results;
    }
    
    private function make_request($method, $endpoint, $body = null) {
        $url = 'http://' . $this->host . ':' . $this->port . $endpoint;
        
        $args = [
            'method' => $method,
            'headers' => [
                'Content-Type' => 'application/json'
            ],
            'timeout' => 30
        ];
        
        if (!empty($this->username) && !empty($this->password)) {
            $args['headers']['Authorization'] = 'Basic ' . base64_encode($this->username . ':' . $this->password);
        }
        
        if ($body) {
            $args['body'] = json_encode($body);
        }
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            error_log('Elasticsearch request failed: ' . $response->get_error_message());
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
    }
    
    public static function create_indices() {
        $handler = new self();
        $types = ['project', 'task', 'contract', 'lead', 'ticket', 'user'];
        
        foreach ($types as $type) {
            $handler->create_index($type);
        }
    }
    
    private function create_index($type) {
        $index = $this->get_index_name($type);
        
        $mapping = [
            'mappings' => [
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'title' => ['type' => 'text', 'analyzer' => 'standard'],
                    'content' => ['type' => 'text', 'analyzer' => 'standard'],
                    'excerpt' => ['type' => 'text', 'analyzer' => 'standard'],
                    'type' => ['type' => 'keyword'],
                    'created_date' => ['type' => 'date'],
                    'modified_date' => ['type' => 'date'],
                    'suggest' => [
                        'type' => 'completion',
                        'analyzer' => 'standard'
                    ]
                ]
            ]
        ];
        
        // Add type-specific fields
        switch ($type) {
            case 'project':
                $mapping['mappings']['properties']['client_name'] = ['type' => 'text'];
                $mapping['mappings']['properties']['client_email'] = ['type' => 'keyword'];
                $mapping['mappings']['properties']['status'] = ['type' => 'keyword'];
                break;
            case 'task':
                $mapping['mappings']['properties']['assignee_name'] = ['type' => 'text'];
                $mapping['mappings']['properties']['assignee_email'] = ['type' => 'keyword'];
                $mapping['mappings']['properties']['project_title'] = ['type' => 'text'];
                $mapping['mappings']['properties']['status'] = ['type' => 'keyword'];
                $mapping['mappings']['properties']['priority'] = ['type' => 'keyword'];
                $mapping['mappings']['properties']['due_date'] = ['type' => 'date'];
                break;
            case 'contract':
                $mapping['mappings']['properties']['client_name'] = ['type' => 'text'];
                $mapping['mappings']['properties']['client_email'] = ['type' => 'keyword'];
                $mapping['mappings']['properties']['project_title'] = ['type' => 'text'];
                $mapping['mappings']['properties']['amount'] = ['type' => 'float'];
                $mapping['mappings']['properties']['start_date'] = ['type' => 'date'];
                $mapping['mappings']['properties']['end_date'] = ['type' => 'date'];
                break;
            case 'lead':
                $mapping['mappings']['properties']['email'] = ['type' => 'keyword'];
                $mapping['mappings']['properties']['phone'] = ['type' => 'keyword'];
                $mapping['mappings']['properties']['company'] = ['type' => 'text'];
                $mapping['mappings']['properties']['status'] = ['type' => 'keyword'];
                break;
            case 'ticket':
                $mapping['mappings']['properties']['assignee_name'] = ['type' => 'text'];
                $mapping['mappings']['properties']['assignee_email'] = ['type' => 'keyword'];
                $mapping['mappings']['properties']['status'] = ['type' => 'keyword'];
                $mapping['mappings']['properties']['priority'] = ['type' => 'keyword'];
                break;
            case 'user':
                $mapping['mappings']['properties']['email'] = ['type' => 'keyword'];
                $mapping['mappings']['properties']['first_name'] = ['type' => 'text'];
                $mapping['mappings']['properties']['last_name'] = ['type' => 'text'];
                $mapping['mappings']['properties']['roles'] = ['type' => 'keyword'];
                break;
        }
        
        $this->make_request('PUT', "/{$index}", $mapping);
    }
}
