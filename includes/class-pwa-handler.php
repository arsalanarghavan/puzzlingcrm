<?php
/**
 * Progressive Web App (PWA) Handler
 * 
 * Makes PuzzlingCRM work as a Progressive Web App
 *
 * @package    PuzzlingCRM
 * @subpackage PuzzlingCRM/includes
 */

class PuzzlingCRM_PWA_Handler {

    /**
     * Initialize PWA Handler
     */
    public function __construct() {
        add_action('wp_head', [$this, 'add_manifest_link']);
        add_action('wp_footer', [$this, 'add_service_worker_registration']);
        add_action('init', [$this, 'handle_pwa_requests']);
    }

    /**
     * Add manifest link to head
     */
    public function add_manifest_link() {
        ?>
        <link rel="manifest" href="<?php echo esc_url(home_url('/puzzlingcrm-manifest.json')); ?>">
        <meta name="theme-color" content="#4CAF50">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="apple-mobile-web-app-title" content="PuzzlingCRM">
        <link rel="apple-touch-icon" href="<?php echo PUZZLINGCRM_PLUGIN_URL; ?>assets/images/logo.png">
        <?php
    }

    /**
     * Add service worker registration script
     */
    public function add_service_worker_registration() {
        ?>
        <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('<?php echo home_url('/puzzlingcrm-sw.js'); ?>')
                    .then(function(registration) {
                        console.log('PuzzlingCRM Service Worker registered:', registration);
                        
                        // Check for updates periodically
                        setInterval(function() {
                            registration.update();
                        }, 60000); // Check every minute
                    })
                    .catch(function(error) {
                        console.log('PuzzlingCRM Service Worker registration failed:', error);
                    });
            });

            // Listen for updates
            navigator.serviceWorker.addEventListener('controllerchange', function() {
                window.location.reload();
            });
        }

        // Install prompt
        let deferredPrompt;
        window.addEventListener('beforeinstallprompt', function(e) {
            e.preventDefault();
            deferredPrompt = e;
            
            // Show install button
            const installBtn = document.querySelector('.puzzlingcrm-install-app');
            if (installBtn) {
                installBtn.style.display = 'block';
                installBtn.addEventListener('click', function() {
                    installBtn.style.display = 'none';
                    deferredPrompt.prompt();
                    deferredPrompt.userChoice.then(function(choiceResult) {
                        if (choiceResult.outcome === 'accepted') {
                            console.log('User accepted the install prompt');
                        }
                        deferredPrompt = null;
                    });
                });
            }
        });

        // Detect if app is installed
        window.addEventListener('appinstalled', function() {
            console.log('PuzzlingCRM app installed');
            // Hide install button
            const installBtn = document.querySelector('.puzzlingcrm-install-app');
            if (installBtn) {
                installBtn.style.display = 'none';
            }
        });
        </script>
        <?php
    }

    /**
     * Handle PWA-specific requests
     */
    public function handle_pwa_requests() {
        // Manifest
        if (strpos($_SERVER['REQUEST_URI'], '/puzzlingcrm-manifest.json') !== false) {
            $this->serve_manifest();
            exit;
        }

        // Service Worker
        if (strpos($_SERVER['REQUEST_URI'], '/puzzlingcrm-sw.js') !== false) {
            $this->serve_service_worker();
            exit;
        }

        // Offline page
        if (strpos($_SERVER['REQUEST_URI'], '/puzzlingcrm-offline.html') !== false) {
            $this->serve_offline_page();
            exit;
        }
    }

    /**
     * Serve manifest.json
     */
    private function serve_manifest() {
        header('Content-Type: application/json');
        
        $manifest = [
            'name' => get_option('puzzlingcrm_app_name', 'PuzzlingCRM'),
            'short_name' => get_option('puzzlingcrm_app_short_name', 'PCRM'),
            'description' => 'سیستم مدیریت ارتباط با مشتری و مدیریت پروژه',
            'start_url' => home_url('/?puzzlingcrm_pwa=1'),
            'scope' => home_url('/'),
            'display' => 'standalone',
            'background_color' => '#ffffff',
            'theme_color' => '#4CAF50',
            'orientation' => 'any',
            'icons' => [
                [
                    'src' => PUZZLINGCRM_PLUGIN_URL . 'assets/images/icon-72x72.png',
                    'sizes' => '72x72',
                    'type' => 'image/png',
                    'purpose' => 'any maskable'
                ],
                [
                    'src' => PUZZLINGCRM_PLUGIN_URL . 'assets/images/icon-96x96.png',
                    'sizes' => '96x96',
                    'type' => 'image/png',
                    'purpose' => 'any maskable'
                ],
                [
                    'src' => PUZZLINGCRM_PLUGIN_URL . 'assets/images/icon-128x128.png',
                    'sizes' => '128x128',
                    'type' => 'image/png',
                    'purpose' => 'any maskable'
                ],
                [
                    'src' => PUZZLINGCRM_PLUGIN_URL . 'assets/images/icon-144x144.png',
                    'sizes' => '144x144',
                    'type' => 'image/png',
                    'purpose' => 'any maskable'
                ],
                [
                    'src' => PUZZLINGCRM_PLUGIN_URL . 'assets/images/icon-152x152.png',
                    'sizes' => '152x152',
                    'type' => 'image/png',
                    'purpose' => 'any maskable'
                ],
                [
                    'src' => PUZZLINGCRM_PLUGIN_URL . 'assets/images/icon-192x192.png',
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'purpose' => 'any maskable'
                ],
                [
                    'src' => PUZZLINGCRM_PLUGIN_URL . 'assets/images/icon-384x384.png',
                    'sizes' => '384x384',
                    'type' => 'image/png',
                    'purpose' => 'any maskable'
                ],
                [
                    'src' => PUZZLINGCRM_PLUGIN_URL . 'assets/images/icon-512x512.png',
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'any maskable'
                ]
            ],
            'categories' => ['business', 'productivity'],
            'shortcuts' => [
                [
                    'name' => 'داشبورد',
                    'short_name' => 'Dashboard',
                    'url' => home_url('/?page=puzzling-dashboard'),
                    'icons' => [['src' => PUZZLINGCRM_PLUGIN_URL . 'assets/images/icon-96x96.png', 'sizes' => '96x96']]
                ],
                [
                    'name' => 'لیدها',
                    'short_name' => 'Leads',
                    'url' => home_url('/?page=puzzling-leads'),
                    'icons' => [['src' => PUZZLINGCRM_PLUGIN_URL . 'assets/images/icon-96x96.png', 'sizes' => '96x96']]
                ],
                [
                    'name' => 'پروژه‌ها',
                    'short_name' => 'Projects',
                    'url' => home_url('/?page=puzzling-projects'),
                    'icons' => [['src' => PUZZLINGCRM_PLUGIN_URL . 'assets/images/icon-96x96.png', 'sizes' => '96x96']]
                ]
            ]
        ];

        echo json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /**
     * Serve service worker
     */
    private function serve_service_worker() {
        header('Content-Type: application/javascript');
        header('Service-Worker-Allowed: /');
        
        ?>
const CACHE_NAME = 'puzzlingcrm-v<?php echo PUZZLINGCRM_VERSION; ?>';
const urlsToCache = [
    '/',
    '/puzzlingcrm-offline.html',
    '<?php echo PUZZLINGCRM_PLUGIN_URL; ?>assets/css/all-pages-complete.css',
    '<?php echo PUZZLINGCRM_PLUGIN_URL; ?>assets/js/puzzlingcrm-scripts.js',
    '<?php echo PUZZLINGCRM_PLUGIN_URL; ?>assets/images/logo.png'
];

// Install event - cache static assets
self.addEventListener('install', function(event) {
    console.log('[Service Worker] Installing...');
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(function(cache) {
                console.log('[Service Worker] Caching app shell');
                return cache.addAll(urlsToCache);
            })
            .then(function() {
                return self.skipWaiting();
            })
    );
});

// Activate event - clean up old caches
self.addEventListener('activate', function(event) {
    console.log('[Service Worker] Activating...');
    event.waitUntil(
        caches.keys().then(function(cacheNames) {
            return Promise.all(
                cacheNames.map(function(cacheName) {
                    if (cacheName !== CACHE_NAME) {
                        console.log('[Service Worker] Deleting old cache:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        }).then(function() {
            return self.clients.claim();
        })
    );
});

// Fetch event - serve from cache, fallback to network
self.addEventListener('fetch', function(event) {
    // Skip non-GET requests
    if (event.request.method !== 'GET') {
        return;
    }

    // Skip Chrome extensions
    if (event.request.url.startsWith('chrome-extension://')) {
        return;
    }

    event.respondWith(
        caches.match(event.request)
            .then(function(response) {
                // Cache hit - return response
                if (response) {
                    return response;
                }

                return fetch(event.request).then(function(response) {
                    // Check if valid response
                    if (!response || response.status !== 200 || response.type !== 'basic') {
                        return response;
                    }

                    // Clone the response
                    const responseToCache = response.clone();

                    caches.open(CACHE_NAME)
                        .then(function(cache) {
                            cache.put(event.request, responseToCache);
                        });

                    return response;
                }).catch(function() {
                    // Network failed, return offline page
                    return caches.match('/puzzlingcrm-offline.html');
                });
            })
    );
});

// Background sync
self.addEventListener('sync', function(event) {
    console.log('[Service Worker] Background sync:', event.tag);
    if (event.tag === 'sync-data') {
        event.waitUntil(syncData());
    }
});

// Push notifications
self.addEventListener('push', function(event) {
    console.log('[Service Worker] Push received');
    const data = event.data ? event.data.json() : {};
    
    const options = {
        body: data.message || 'شما یک اعلان جدید دارید',
        icon: '<?php echo PUZZLINGCRM_PLUGIN_URL; ?>assets/images/icon-192x192.png',
        badge: '<?php echo PUZZLINGCRM_PLUGIN_URL; ?>assets/images/icon-96x96.png',
        vibrate: [200, 100, 200],
        data: data,
        actions: [
            { action: 'view', title: 'مشاهده' },
            { action: 'dismiss', title: 'نادیده گرفتن' }
        ]
    };

    event.waitUntil(
        self.registration.showNotification(data.title || 'PuzzlingCRM', options)
    );
});

// Notification click
self.addEventListener('notificationclick', function(event) {
    console.log('[Service Worker] Notification clicked');
    event.notification.close();

    if (event.action === 'view') {
        event.waitUntil(
            clients.openWindow(event.notification.data.url || '/')
        );
    }
});

// Helper function to sync data
async function syncData() {
    try {
        const response = await fetch('/wp-admin/admin-ajax.php?action=puzzlingcrm_sync_offline_data', {
            method: 'POST',
            credentials: 'include'
        });
        
        if (response.ok) {
            console.log('[Service Worker] Data synced successfully');
        }
    } catch (error) {
        console.error('[Service Worker] Sync failed:', error);
    }
}
        <?php
    }

    /**
     * Serve offline page
     */
    private function serve_offline_page() {
        header('Content-Type: text/html; charset=UTF-8');
        ?>
        <!DOCTYPE html>
        <html dir="rtl" lang="fa">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>آفلاین - PuzzlingCRM</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body {
                    font-family: 'Tahoma', 'Arial', sans-serif;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    padding: 20px;
                }
                .offline-container {
                    background: white;
                    padding: 40px;
                    border-radius: 20px;
                    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                    text-align: center;
                    max-width: 500px;
                    width: 100%;
                }
                .offline-icon {
                    font-size: 80px;
                    color: #667eea;
                    margin-bottom: 20px;
                }
                h1 {
                    color: #333;
                    font-size: 28px;
                    margin-bottom: 15px;
                }
                p {
                    color: #666;
                    line-height: 1.8;
                    margin-bottom: 25px;
                    font-size: 16px;
                }
                .retry-btn {
                    background: #667eea;
                    color: white;
                    border: none;
                    padding: 15px 40px;
                    font-size: 16px;
                    border-radius: 50px;
                    cursor: pointer;
                    transition: all 0.3s;
                }
                .retry-btn:hover {
                    background: #764ba2;
                    transform: translateY(-2px);
                    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
                }
                .cached-info {
                    margin-top: 30px;
                    padding-top: 30px;
                    border-top: 1px solid #eee;
                    color: #999;
                    font-size: 14px;
                }
            </style>
        </head>
        <body>
            <div class="offline-container">
                <div class="offline-icon">📡</div>
                <h1>اتصال اینترنت برقرار نیست</h1>
                <p>
                    متأسفانه در حال حاضر به اینترنت دسترسی ندارید.
                    لطفاً اتصال خود را بررسی کرده و دوباره تلاش کنید.
                </p>
                <button class="retry-btn" onclick="location.reload()">
                    تلاش مجدد
                </button>
                <div class="cached-info">
                    <p>برخی از صفحات ممکن است به صورت آفلاین در دسترس باشند</p>
                </div>
            </div>
        </body>
        </html>
        <?php
    }

    /**
     * Check if request is from PWA
     */
    public static function is_pwa_request() {
        return isset($_GET['puzzlingcrm_pwa']) || 
               (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'PWA');
    }
}

