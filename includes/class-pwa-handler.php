<?php
/**
 * Progressive Web App (PWA) Handler
 * Converts the CRM into a mobile-friendly PWA
 *
 * @package PuzzlingCRM
 * @subpackage PuzzlingCRM/includes
 * @author     Arsalan Arghavan
 */

class PuzzlingCRM_PWA_Handler {
    
    public function __construct() {
        add_action('wp_head', [$this, 'add_pwa_meta_tags']);
        add_action('wp_head', [$this, 'add_pwa_manifest_link']);
        add_action('wp_footer', [$this, 'add_pwa_scripts']);
        add_action('init', [$this, 'register_pwa_manifest_endpoint']);
        add_action('init', [$this, 'register_service_worker_endpoint']);
        add_action('wp_ajax_puzzling_pwa_install', [$this, 'handle_pwa_install']);
        add_action('wp_ajax_nopriv_puzzling_pwa_install', [$this, 'handle_pwa_install']);
    }
    
    public function add_pwa_meta_tags() {
        if (!$this->is_pwa_page()) {
            return;
        }
        
        echo '<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">' . "\n";
        echo '<meta name="mobile-web-app-capable" content="yes">' . "\n";
        echo '<meta name="apple-mobile-web-app-capable" content="yes">' . "\n";
        echo '<meta name="apple-mobile-web-app-status-bar-style" content="default">' . "\n";
        echo '<meta name="apple-mobile-web-app-title" content="PuzzlingCRM">' . "\n";
        echo '<meta name="application-name" content="PuzzlingCRM">' . "\n";
        echo '<meta name="theme-color" content="#667eea">' . "\n";
        echo '<meta name="msapplication-TileColor" content="#667eea">' . "\n";
        echo '<meta name="msapplication-navbutton-color" content="#667eea">' . "\n";
        echo '<meta name="msapplication-starturl" content="/">' . "\n";
        
        // iOS specific
        echo '<link rel="apple-touch-icon" sizes="180x180" href="' . PUZZLINGCRM_PLUGIN_URL . 'assets/images/pwa/apple-touch-icon.png">' . "\n";
        echo '<link rel="icon" type="image/png" sizes="32x32" href="' . PUZZLINGCRM_PLUGIN_URL . 'assets/images/pwa/favicon-32x32.png">' . "\n";
        echo '<link rel="icon" type="image/png" sizes="16x16" href="' . PUZZLINGCRM_PLUGIN_URL . 'assets/images/pwa/favicon-16x16.png">' . "\n";
        echo '<link rel="mask-icon" href="' . PUZZLINGCRM_PLUGIN_URL . 'assets/images/pwa/safari-pinned-tab.svg" color="#667eea">' . "\n";
    }
    
    public function add_pwa_manifest_link() {
        if (!$this->is_pwa_page()) {
            return;
        }
        
        echo '<link rel="manifest" href="' . home_url('/wp-json/puzzlingcrm/v1/pwa/manifest') . '">' . "\n";
    }
    
    public function add_pwa_scripts() {
        if (!$this->is_pwa_page()) {
            return;
        }
        
        ?>
        <script>
        // PWA Installation prompt
        let deferredPrompt;
        const installButton = document.getElementById('pwa-install-button');
        
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            
            if (installButton) {
                installButton.style.display = 'block';
                installButton.addEventListener('click', () => {
                    deferredPrompt.prompt();
                    deferredPrompt.userChoice.then((choiceResult) => {
                        if (choiceResult.outcome === 'accepted') {
                            console.log('User accepted the PWA install prompt');
                        }
                        deferredPrompt = null;
                    });
                });
            }
        });
        
        // Service Worker Registration
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('<?php echo home_url('/wp-json/puzzlingcrm/v1/pwa/sw'); ?>')
                    .then((registration) => {
                        console.log('SW registered: ', registration);
                    })
                    .catch((registrationError) => {
                        console.log('SW registration failed: ', registrationError);
                    });
            });
        }
        
        // PWA Install tracking
        window.addEventListener('appinstalled', (evt) => {
            console.log('PWA was installed');
            // Track installation
            if (typeof jQuery !== 'undefined') {
                jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                    action: 'puzzling_pwa_install',
                    nonce: '<?php echo wp_create_nonce('puzzlingcrm-ajax-nonce'); ?>'
                });
            }
        });
        
        // Offline detection
        window.addEventListener('online', () => {
            document.body.classList.remove('offline');
            this.showNotification('اتصال به اینترنت برقرار شد', 'success');
        });
        
        window.addEventListener('offline', () => {
            document.body.classList.add('offline');
            this.showNotification('اتصال به اینترنت قطع شد', 'warning');
        });
        
        // PWA specific functions
        function showNotification(message, type = 'info') {
            if ('Notification' in window && Notification.permission === 'granted') {
                new Notification(message, {
                    icon: '<?php echo PUZZLINGCRM_PLUGIN_URL; ?>assets/images/pwa/icon-192x192.png',
                    badge: '<?php echo PUZZLINGCRM_PLUGIN_URL; ?>assets/images/pwa/badge-72x72.png'
                });
            }
        }
        
        // Request notification permission
        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission();
        }
        </script>
        <?php
    }
    
    public function register_pwa_manifest_endpoint() {
        register_rest_route('puzzlingcrm/v1', '/pwa/manifest', [
            'methods' => 'GET',
            'callback' => [$this, 'get_pwa_manifest'],
            'permission_callback' => '__return_true'
        ]);
    }
    
    public function register_service_worker_endpoint() {
        register_rest_route('puzzlingcrm/v1', '/pwa/sw', [
            'methods' => 'GET',
            'callback' => [$this, 'get_service_worker'],
            'permission_callback' => '__return_true'
        ]);
    }
    
    public function get_pwa_manifest() {
        $manifest = [
            'name' => 'PuzzlingCRM - مدیریت مشتریان',
            'short_name' => 'PuzzlingCRM',
            'description' => 'سیستم مدیریت مشتریان و پروژه‌ها',
            'start_url' => home_url('/'),
            'display' => 'standalone',
            'background_color' => '#ffffff',
            'theme_color' => '#667eea',
            'orientation' => 'portrait-primary',
            'scope' => home_url('/'),
            'lang' => 'fa',
            'dir' => 'rtl',
            'icons' => [
                [
                    'src' => PUZZLINGCRM_PLUGIN_URL . 'assets/images/pwa/icon-72x72.png',
                    'sizes' => '72x72',
                    'type' => 'image/png',
                    'purpose' => 'any'
                ],
                [
                    'src' => PUZZLINGCRM_PLUGIN_URL . 'assets/images/pwa/icon-96x96.png',
                    'sizes' => '96x96',
                    'type' => 'image/png',
                    'purpose' => 'any'
                ],
                [
                    'src' => PUZZLINGCRM_PLUGIN_URL . 'assets/images/pwa/icon-128x128.png',
                    'sizes' => '128x128',
                    'type' => 'image/png',
                    'purpose' => 'any'
                ],
                [
                    'src' => PUZZLINGCRM_PLUGIN_URL . 'assets/images/pwa/icon-144x144.png',
                    'sizes' => '144x144',
                    'type' => 'image/png',
                    'purpose' => 'any'
                ],
                [
                    'src' => PUZZLINGCRM_PLUGIN_URL . 'assets/images/pwa/icon-152x152.png',
                    'sizes' => '152x152',
                    'type' => 'image/png',
                    'purpose' => 'any'
                ],
                [
                    'src' => PUZZLINGCRM_PLUGIN_URL . 'assets/images/pwa/icon-192x192.png',
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'purpose' => 'any maskable'
                ],
                [
                    'src' => PUZZLINGCRM_PLUGIN_URL . 'assets/images/pwa/icon-384x384.png',
                    'sizes' => '384x384',
                    'type' => 'image/png',
                    'purpose' => 'any'
                ],
                [
                    'src' => PUZZLINGCRM_PLUGIN_URL . 'assets/images/pwa/icon-512x512.png',
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'any maskable'
                ]
            ],
            'screenshots' => [
                [
                    'src' => PUZZLINGCRM_PLUGIN_URL . 'assets/images/pwa/screenshot-mobile-1.png',
                    'sizes' => '390x844',
                    'type' => 'image/png',
                    'form_factor' => 'narrow'
                ],
                [
                    'src' => PUZZLINGCRM_PLUGIN_URL . 'assets/images/pwa/screenshot-desktop-1.png',
                    'sizes' => '1280x720',
                    'type' => 'image/png',
                    'form_factor' => 'wide'
                ]
            ],
            'categories' => ['business', 'productivity', 'utilities'],
            'shortcuts' => [
                [
                    'name' => 'داشبورد',
                    'short_name' => 'داشبورد',
                    'description' => 'داشبورد اصلی',
                    'url' => home_url('/dashboard/'),
                    'icons' => [
                        [
                            'src' => PUZZLINGCRM_PLUGIN_URL . 'assets/images/pwa/shortcut-dashboard.png',
                            'sizes' => '96x96'
                        ]
                    ]
                ],
                [
                    'name' => 'پروژه‌ها',
                    'short_name' => 'پروژه‌ها',
                    'description' => 'مدیریت پروژه‌ها',
                    'url' => admin_url('admin.php?page=puzzling-projects'),
                    'icons' => [
                        [
                            'src' => PUZZLINGCRM_PLUGIN_URL . 'assets/images/pwa/shortcut-projects.png',
                            'sizes' => '96x96'
                        ]
                    ]
                ],
                [
                    'name' => 'وظایف',
                    'short_name' => 'وظایف',
                    'description' => 'مدیریت وظایف',
                    'url' => admin_url('admin.php?page=puzzling-tasks'),
                    'icons' => [
                        [
                            'src' => PUZZLINGCRM_PLUGIN_URL . 'assets/images/pwa/shortcut-tasks.png',
                            'sizes' => '96x96'
                        ]
                    ]
                ],
                [
                    'name' => 'سرنخ‌ها',
                    'short_name' => 'سرنخ‌ها',
                    'description' => 'مدیریت سرنخ‌ها',
                    'url' => admin_url('admin.php?page=puzzling-leads'),
                    'icons' => [
                        [
                            'src' => PUZZLINGCRM_PLUGIN_URL . 'assets/images/pwa/shortcut-leads.png',
                            'sizes' => '96x96'
                        ]
                    ]
                ]
            ],
            'related_applications' => [],
            'prefer_related_applications' => false
        ];
        
        header('Content-Type: application/json');
        echo json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
    
    public function get_service_worker() {
        $sw_content = $this->generate_service_worker();
        
        header('Content-Type: application/javascript');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        echo $sw_content;
        exit;
    }
    
    private function generate_service_worker() {
        $cache_name = 'puzzlingcrm-v' . PUZZLINGCRM_VERSION;
        $cache_urls = [
            '/',
            '/wp-content/plugins/puzzlingcrm/assets/css/',
            '/wp-content/plugins/puzzlingcrm/assets/js/',
            '/wp-content/plugins/puzzlingcrm/assets/images/',
            admin_url('admin.php?page=puzzling-dashboard'),
            admin_url('admin.php?page=puzzling-projects'),
            admin_url('admin.php?page=puzzling-tasks'),
            admin_url('admin.php?page=puzzling-leads'),
            admin_url('admin.php?page=puzzling-contracts'),
            admin_url('admin.php?page=puzzling-tickets')
        ];
        
        return "
const CACHE_NAME = '{$cache_name}';
const urlsToCache = " . json_encode($cache_urls) . ";

// Install event
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => {
                console.log('Opened cache');
                return cache.addAll(urlsToCache);
            })
    );
});

// Fetch event
self.addEventListener('fetch', (event) => {
    event.respondWith(
        caches.match(event.request)
            .then((response) => {
                // Return cached version or fetch from network
                if (response) {
                    return response;
                }
                
                return fetch(event.request).then((response) => {
                    // Check if valid response
                    if (!response || response.status !== 200 || response.type !== 'basic') {
                        return response;
                    }
                    
                    // Clone the response
                    const responseToCache = response.clone();
                    
                    caches.open(CACHE_NAME)
                        .then((cache) => {
                            cache.put(event.request, responseToCache);
                        });
                    
                    return response;
                });
            })
    );
});

// Activate event
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    if (cacheName !== CACHE_NAME) {
                        console.log('Deleting old cache:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
});

// Background sync
self.addEventListener('sync', (event) => {
    if (event.tag === 'background-sync') {
        event.waitUntil(doBackgroundSync());
    }
});

// Push notifications
self.addEventListener('push', (event) => {
    const options = {
        body: event.data ? event.data.text() : 'اعلان جدید',
        icon: '/wp-content/plugins/puzzlingcrm/assets/images/pwa/icon-192x192.png',
        badge: '/wp-content/plugins/puzzlingcrm/assets/images/pwa/badge-72x72.png',
        vibrate: [100, 50, 100],
        data: {
            dateOfArrival: Date.now(),
            primaryKey: 1
        },
        actions: [
            {
                action: 'explore',
                title: 'مشاهده',
                icon: '/wp-content/plugins/puzzlingcrm/assets/images/pwa/action-view.png'
            },
            {
                action: 'close',
                title: 'بستن',
                icon: '/wp-content/plugins/puzzlingcrm/assets/images/pwa/action-close.png'
            }
        ]
    };
    
    event.waitUntil(
        self.registration.showNotification('PuzzlingCRM', options)
    );
});

// Notification click
self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    
    if (event.action === 'explore') {
        event.waitUntil(
            clients.openWindow('/')
        );
    }
});

// Background sync function
function doBackgroundSync() {
    return new Promise((resolve) => {
        // Implement background sync logic here
        console.log('Background sync completed');
        resolve();
    });
}
";
    }
    
    public function handle_pwa_install() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'nonce');
        
        // Track PWA installation
        $install_count = get_option('puzzlingcrm_pwa_installs', 0);
        update_option('puzzlingcrm_pwa_installs', $install_count + 1);
        
        // Log installation
        $this->log_pwa_event('install', [
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'timestamp' => current_time('mysql')
        ]);
        
        wp_send_json_success(['message' => 'PWA installation tracked']);
    }
    
    private function is_pwa_page() {
        // Check if current page should have PWA features
        $pwa_pages = [
            'puzzling-dashboard',
            'puzzling-projects',
            'puzzling-tasks',
            'puzzling-leads',
            'puzzling-contracts',
            'puzzling-tickets'
        ];
        
        $current_page = $_GET['page'] ?? '';
        return in_array($current_page, $pwa_pages) || is_front_page();
    }
    
    private function log_pwa_event($event, $data = []) {
        $log_entry = [
            'event' => $event,
            'data' => $data,
            'timestamp' => current_time('mysql'),
            'user_id' => get_current_user_id()
        ];
        
        $logs = get_option('puzzlingcrm_pwa_logs', []);
        $logs[] = $log_entry;
        
        // Keep only last 100 entries
        if (count($logs) > 100) {
            $logs = array_slice($logs, -100);
        }
        
        update_option('puzzlingcrm_pwa_logs', $logs);
    }
    
    public static function create_pwa_images() {
        // This method would create PWA images programmatically
        // For now, we'll assume images are manually created
        $image_sizes = [
            'icon-72x72.png' => 72,
            'icon-96x96.png' => 96,
            'icon-128x128.png' => 128,
            'icon-144x144.png' => 144,
            'icon-152x152.png' => 152,
            'icon-192x192.png' => 192,
            'icon-384x384.png' => 384,
            'icon-512x512.png' => 512,
            'apple-touch-icon.png' => 180,
            'favicon-32x32.png' => 32,
            'favicon-16x16.png' => 16,
            'badge-72x72.png' => 72
        ];
        
        $pwa_dir = PUZZLINGCRM_PLUGIN_DIR . 'assets/images/pwa/';
        
        if (!file_exists($pwa_dir)) {
            wp_mkdir_p($pwa_dir);
        }
        
        // Create placeholder images (in production, you'd use actual images)
        foreach ($image_sizes as $filename => $size) {
            $filepath = $pwa_dir . $filename;
            if (!file_exists($filepath)) {
                // Create a simple colored square as placeholder
                $this->create_placeholder_image($filepath, $size, $size);
            }
        }
    }
    
    private function create_placeholder_image($filepath, $width, $height) {
        // This is a placeholder - in production you'd create actual PWA icons
        // For now, we'll create a simple colored square
        $image = imagecreate($width, $height);
        $bg_color = imagecolorallocate($image, 102, 126, 234); // #667eea
        imagefill($image, 0, 0, $bg_color);
        
        // Add text
        $text_color = imagecolorallocate($image, 255, 255, 255);
        $font_size = $width / 8;
        imagestring($image, 5, $width/4, $height/2 - $font_size/2, 'P', $text_color);
        
        imagepng($image, $filepath);
        imagedestroy($image);
    }
}
