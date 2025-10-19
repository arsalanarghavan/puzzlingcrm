<?php
/**
 * WebSocket Server for Real-time Notifications
 * 
 * This script runs as a standalone WebSocket server
 * Run with: php websocket-server.php [host] [port]
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    // Load WordPress if not already loaded
    $wp_load_path = dirname(__FILE__) . '/../../../wp-load.php';
    if (file_exists($wp_load_path)) {
        require_once $wp_load_path;
    } else {
        die('WordPress not found');
    }
}

class PuzzlingCRM_WebSocket_Server {
    
    private $host;
    private $port;
    private $socket;
    private $clients = [];
    private $notifications = [];
    
    public function __construct($host = '0.0.0.0', $port = 8080) {
        $this->host = $host;
        $this->port = $port;
        $this->start_server();
    }
    
    private function start_server() {
        // Create socket
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if (!$this->socket) {
            die("Could not create socket: " . socket_strerror(socket_last_error()) . "\n");
        }
        
        // Set socket options
        socket_set_option($this->socket, SOL_SOCKET, SO_REUSEADDR, 1);
        socket_set_option($this->socket, SOL_SOCKET, SO_KEEPALIVE, 1);
        
        // Bind socket
        if (!socket_bind($this->socket, $this->host, $this->port)) {
            die("Could not bind socket: " . socket_strerror(socket_last_error($this->socket)) . "\n");
        }
        
        // Listen for connections
        if (!socket_listen($this->socket, 5)) {
            die("Could not listen on socket: " . socket_strerror(socket_last_error($this->socket)) . "\n");
        }
        
        echo "WebSocket server started on {$this->host}:{$this->port}\n";
        
        $this->run();
    }
    
    private function run() {
        while (true) {
            $read = array_merge([$this->socket], $this->clients);
            $write = [];
            $except = [];
            
            if (socket_select($read, $write, $except, 0, 100000) > 0) {
                // New connection
                if (in_array($this->socket, $read)) {
                    $client = socket_accept($this->socket);
                    if ($client) {
                        $this->clients[] = $client;
                        echo "New client connected\n";
                    }
                }
                
                // Handle client messages
                foreach ($read as $client) {
                    if ($client === $this->socket) continue;
                    
                    $data = socket_read($client, 1024);
                    if ($data === false || $data === '') {
                        $this->disconnect_client($client);
                        continue;
                    }
                    
                    $this->handle_message($client, $data);
                }
            }
            
            // Check for new notifications
            $this->check_notifications();
            
            // Clean up disconnected clients
            $this->cleanup_clients();
        }
    }
    
    private function handle_message($client, $data) {
        // Parse WebSocket frame
        $decoded = $this->decode_frame($data);
        if (!$decoded) return;
        
        $message = json_decode($decoded, true);
        if (!$message) return;
        
        switch ($message['type']) {
            case 'auth':
                $this->handle_auth($client, $message);
                break;
            case 'ping':
                $this->send_message($client, ['type' => 'pong']);
                break;
        }
    }
    
    private function handle_auth($client, $message) {
        $token = $message['token'] ?? '';
        $user_data = $this->verify_token($token);
        
        if ($user_data) {
            // Store client with user ID
            $this->clients[array_search($client, $this->clients)] = [
                'socket' => $client,
                'user_id' => $user_data['user_id'],
                'authenticated' => true
            ];
            
            $this->send_message($client, [
                'type' => 'auth_success',
                'user_id' => $user_data['user_id']
            ]);
            
            echo "Client authenticated for user {$user_data['user_id']}\n";
        } else {
            $this->send_message($client, [
                'type' => 'auth_error',
                'message' => 'Invalid token'
            ]);
            $this->disconnect_client($client);
        }
    }
    
    private function verify_token($token) {
        $decoded = base64_decode($token);
        $data = json_decode($decoded, true);
        
        if (!$data || !isset($data['user_id'], $data['timestamp'], $data['nonce'])) {
            return false;
        }
        
        // Check if token is not too old (1 hour)
        if (time() - $data['timestamp'] > 3600) {
            return false;
        }
        
        // Verify nonce
        if (!wp_verify_nonce($data['nonce'], 'websocket_auth_' . $data['user_id'])) {
            return false;
        }
        
        return $data;
    }
    
    private function check_notifications() {
        global $wpdb;
        
        // Get pending notifications from transients
        $transients = $wpdb->get_results(
            "SELECT option_name, option_value FROM {$wpdb->options} 
             WHERE option_name LIKE 'puzzling_ws_notification_%' 
             AND option_value IS NOT NULL"
        );
        
        foreach ($transients as $transient) {
            $notification = maybe_unserialize($transient->option_value);
            if ($notification && isset($notification['id'])) {
                $this->broadcast_notification($notification);
                delete_option($transient->option_name);
            }
        }
    }
    
    private function broadcast_notification($notification) {
        $user_id = $notification['user_id'] ?? null;
        
        foreach ($this->clients as $client_data) {
            if (is_array($client_data) && 
                isset($client_data['authenticated']) && 
                $client_data['authenticated'] && 
                $client_data['user_id'] == $user_id) {
                
                $this->send_message($client_data['socket'], [
                    'type' => 'notification',
                    'data' => $notification
                ]);
            }
        }
    }
    
    private function send_message($client, $message) {
        $frame = $this->encode_frame(json_encode($message));
        socket_write($client, $frame);
    }
    
    private function encode_frame($data) {
        $length = strlen($data);
        
        if ($length < 126) {
            return chr(129) . chr($length) . $data;
        } elseif ($length < 65536) {
            return chr(129) . chr(126) . pack('n', $length) . $data;
        } else {
            return chr(129) . chr(127) . pack('J', $length) . $data;
        }
    }
    
    private function decode_frame($data) {
        $length = ord($data[1]) & 127;
        
        if ($length == 126) {
            $masks = substr($data, 4, 4);
            $data = substr($data, 8);
        } elseif ($length == 127) {
            $masks = substr($data, 10, 4);
            $data = substr($data, 14);
        } else {
            $masks = substr($data, 2, 4);
            $data = substr($data, 6);
        }
        
        $decoded = '';
        for ($i = 0; $i < strlen($data); $i++) {
            $decoded .= $data[$i] ^ $masks[$i % 4];
        }
        
        return $decoded;
    }
    
    private function disconnect_client($client) {
        $key = array_search($client, $this->clients);
        if ($key !== false) {
            unset($this->clients[$key]);
            socket_close($client);
            echo "Client disconnected\n";
        }
    }
    
    private function cleanup_clients() {
        foreach ($this->clients as $key => $client_data) {
            $client = is_array($client_data) ? $client_data['socket'] : $client_data;
            if (!is_resource($client) || socket_last_error($client)) {
                unset($this->clients[$key]);
                socket_close($client);
            }
        }
    }
    
    public function __destruct() {
        if ($this->socket) {
            socket_close($this->socket);
        }
    }
}

// Start server if run directly
if (php_sapi_name() === 'cli') {
    $host = $argv[1] ?? '0.0.0.0';
    $port = intval($argv[2] ?? 8080);
    
    $server = new PuzzlingCRM_WebSocket_Server($host, $port);
}
