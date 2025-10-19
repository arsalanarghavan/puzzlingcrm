/**
 * WebSocket Notifications Client
 * Handles real-time notifications via WebSocket connection
 */

class PuzzlingCRM_WebSocket_Notifications {
    constructor() {
        this.ws = null;
        this.connected = false;
        this.reconnectAttempts = 0;
        this.maxReconnectAttempts = 5;
        this.reconnectInterval = 5000; // 5 seconds
        this.pingInterval = null;
        this.notificationContainer = null;
        
        this.init();
    }
    
    init() {
        this.createNotificationContainer();
        this.connect();
        this.bindEvents();
    }
    
    createNotificationContainer() {
        // Create notification container if it doesn't exist
        if (!document.getElementById('puzzling-notifications-container')) {
            const container = document.createElement('div');
            container.id = 'puzzling-notifications-container';
            container.className = 'puzzling-notifications-container';
            container.innerHTML = `
                <div class="notification-bell" id="notification-bell">
                    <i class="ri-notification-3-line"></i>
                    <span class="notification-count" id="notification-count">0</span>
                </div>
                <div class="notification-dropdown" id="notification-dropdown">
                    <div class="notification-header">
                        <h4>اعلان‌ها</h4>
                        <button class="mark-all-read" id="mark-all-read">همه را خوانده شده</button>
                    </div>
                    <div class="notification-list" id="notification-list">
                        <div class="loading">در حال بارگذاری...</div>
                    </div>
                    <div class="notification-footer">
                        <button class="view-all-notifications" id="view-all-notifications">مشاهده همه</button>
                    </div>
                </div>
            `;
            document.body.appendChild(container);
        }
        
        this.notificationContainer = document.getElementById('puzzling-notifications-container');
    }
    
    connect() {
        // Get authentication token
        this.authenticate().then(authData => {
            this.ws = new WebSocket(authData.websocket_url);
            
            this.ws.onopen = () => {
                console.log('WebSocket connected');
                this.connected = true;
                this.reconnectAttempts = 0;
                
                // Send authentication
                this.send({
                    type: 'auth',
                    token: authData.token
                });
                
                // Start ping interval
                this.startPingInterval();
            };
            
            this.ws.onmessage = (event) => {
                this.handleMessage(JSON.parse(event.data));
            };
            
            this.ws.onclose = () => {
                console.log('WebSocket disconnected');
                this.connected = false;
                this.stopPingInterval();
                this.attemptReconnect();
            };
            
            this.ws.onerror = (error) => {
                console.error('WebSocket error:', error);
            };
        }).catch(error => {
            console.error('Authentication failed:', error);
        });
    }
    
    authenticate() {
        return new Promise((resolve, reject) => {
            jQuery.ajax({
                url: puzzlingcrm_ajax_obj.ajax_url,
                type: 'POST',
                data: {
                    action: 'puzzling_websocket_auth',
                    nonce: puzzlingcrm_ajax_obj.nonce
                },
                success: (response) => {
                    if (response.success) {
                        resolve(response.data);
                    } else {
                        reject(response.data);
                    }
                },
                error: (xhr, status, error) => {
                    reject(error);
                }
            });
        });
    }
    
    send(message) {
        if (this.ws && this.connected) {
            this.ws.send(JSON.stringify(message));
        }
    }
    
    handleMessage(message) {
        switch (message.type) {
            case 'auth_success':
                console.log('WebSocket authenticated for user:', message.user_id);
                this.loadNotifications();
                break;
                
            case 'auth_error':
                console.error('WebSocket authentication failed:', message.message);
                break;
                
            case 'notification':
                this.showNotification(message.data);
                this.updateNotificationCount();
                break;
                
            case 'pong':
                // Handle pong response
                break;
        }
    }
    
    showNotification(notification) {
        // Create notification element
        const notificationEl = document.createElement('div');
        notificationEl.className = `notification-item ${notification.type}`;
        notificationEl.dataset.notificationId = notification.id;
        notificationEl.innerHTML = `
            <div class="notification-content">
                <div class="notification-title">${notification.title}</div>
                <div class="notification-message">${notification.message}</div>
                <div class="notification-time">${this.formatTime(notification.timestamp)}</div>
            </div>
            <div class="notification-actions">
                <button class="mark-read" data-id="${notification.id}">
                    <i class="ri-check-line"></i>
                </button>
                ${notification.action_url ? `<a href="${notification.action_url}" class="action-link">مشاهده</a>` : ''}
            </div>
        `;
        
        // Add to notification list
        const notificationList = document.getElementById('notification-list');
        if (notificationList.querySelector('.loading')) {
            notificationList.innerHTML = '';
        }
        notificationList.insertBefore(notificationEl, notificationList.firstChild);
        
        // Show browser notification if permission granted
        this.showBrowserNotification(notification);
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            notificationEl.classList.add('fade-out');
            setTimeout(() => {
                if (notificationEl.parentNode) {
                    notificationEl.parentNode.removeChild(notificationEl);
                }
            }, 300);
        }, 5000);
    }
    
    showBrowserNotification(notification) {
        if (Notification.permission === 'granted') {
            new Notification(notification.title, {
                body: notification.message,
                icon: '/wp-content/plugins/puzzlingcrm/assets/images/logo.png',
                tag: notification.id
            });
        }
    }
    
    loadNotifications() {
        jQuery.ajax({
            url: puzzlingcrm_ajax_obj.ajax_url,
            type: 'GET',
            data: {
                action: 'puzzling_get_notifications',
                nonce: puzzlingcrm_ajax_obj.nonce,
                per_page: 10
            },
            success: (response) => {
                if (response.success) {
                    this.renderNotifications(response.data.notifications);
                    this.updateNotificationCount();
                }
            }
        });
    }
    
    renderNotifications(notifications) {
        const notificationList = document.getElementById('notification-list');
        
        if (notifications.length === 0) {
            notificationList.innerHTML = '<div class="no-notifications">اعلانی وجود ندارد</div>';
            return;
        }
        
        notificationList.innerHTML = notifications.map(notification => `
            <div class="notification-item ${notification.type} ${notification.is_read ? 'read' : 'unread'}" data-notification-id="${notification.id}">
                <div class="notification-content">
                    <div class="notification-title">${notification.title}</div>
                    <div class="notification-message">${notification.message}</div>
                    <div class="notification-time">${this.formatTime(notification.created_at)}</div>
                </div>
                <div class="notification-actions">
                    ${!notification.is_read ? `<button class="mark-read" data-id="${notification.id}">
                        <i class="ri-check-line"></i>
                    </button>` : ''}
                    ${notification.action_url ? `<a href="${notification.action_url}" class="action-link">مشاهده</a>` : ''}
                </div>
            </div>
        `).join('');
    }
    
    updateNotificationCount() {
        const unreadCount = document.querySelectorAll('.notification-item.unread').length;
        const countEl = document.getElementById('notification-count');
        if (countEl) {
            countEl.textContent = unreadCount;
            countEl.style.display = unreadCount > 0 ? 'block' : 'none';
        }
    }
    
    formatTime(timestamp) {
        const now = new Date();
        const time = new Date(timestamp);
        const diff = now - time;
        
        if (diff < 60000) { // Less than 1 minute
            return 'همین الان';
        } else if (diff < 3600000) { // Less than 1 hour
            return Math.floor(diff / 60000) + ' دقیقه پیش';
        } else if (diff < 86400000) { // Less than 1 day
            return Math.floor(diff / 3600000) + ' ساعت پیش';
        } else {
            return time.toLocaleDateString('fa-IR');
        }
    }
    
    startPingInterval() {
        this.pingInterval = setInterval(() => {
            this.send({ type: 'ping' });
        }, 30000); // Ping every 30 seconds
    }
    
    stopPingInterval() {
        if (this.pingInterval) {
            clearInterval(this.pingInterval);
            this.pingInterval = null;
        }
    }
    
    attemptReconnect() {
        if (this.reconnectAttempts < this.maxReconnectAttempts) {
            this.reconnectAttempts++;
            console.log(`Attempting to reconnect... (${this.reconnectAttempts}/${this.maxReconnectAttempts})`);
            
            setTimeout(() => {
                this.connect();
            }, this.reconnectInterval);
        } else {
            console.error('Max reconnection attempts reached');
        }
    }
    
    bindEvents() {
        // Notification bell click
        jQuery(document).on('click', '#notification-bell', (e) => {
            e.stopPropagation();
            const dropdown = document.getElementById('notification-dropdown');
            dropdown.classList.toggle('show');
        });
        
        // Mark notification as read
        jQuery(document).on('click', '.mark-read', (e) => {
            const notificationId = e.currentTarget.dataset.id;
            this.markAsRead(notificationId);
        });
        
        // Mark all as read
        jQuery(document).on('click', '#mark-all-read', () => {
            this.markAllAsRead();
        });
        
        // Close dropdown when clicking outside
        jQuery(document).on('click', (e) => {
            if (!jQuery(e.target).closest('#puzzling-notifications-container').length) {
                document.getElementById('notification-dropdown').classList.remove('show');
            }
        });
        
        // Request notification permission
        if (Notification.permission === 'default') {
            Notification.requestPermission();
        }
    }
    
    markAsRead(notificationId) {
        jQuery.ajax({
            url: puzzlingcrm_ajax_obj.ajax_url,
            type: 'POST',
            data: {
                action: 'puzzling_mark_notification_read',
                nonce: puzzlingcrm_ajax_obj.nonce,
                notification_id: notificationId
            },
            success: () => {
                const notificationEl = document.querySelector(`[data-notification-id="${notificationId}"]`);
                if (notificationEl) {
                    notificationEl.classList.remove('unread');
                    notificationEl.classList.add('read');
                    notificationEl.querySelector('.mark-read').remove();
                }
                this.updateNotificationCount();
            }
        });
    }
    
    markAllAsRead() {
        const unreadNotifications = document.querySelectorAll('.notification-item.unread');
        const notificationIds = Array.from(unreadNotifications).map(el => el.dataset.notificationId);
        
        if (notificationIds.length === 0) return;
        
        // Mark all as read in UI first
        unreadNotifications.forEach(el => {
            el.classList.remove('unread');
            el.classList.add('read');
            const markReadBtn = el.querySelector('.mark-read');
            if (markReadBtn) markReadBtn.remove();
        });
        
        // Update count
        this.updateNotificationCount();
        
        // Send to server
        notificationIds.forEach(id => {
            this.markAsRead(id);
        });
    }
    
    disconnect() {
        if (this.ws) {
            this.ws.close();
        }
        this.stopPingInterval();
    }
}

// Initialize when DOM is ready
jQuery(document).ready(() => {
    if (typeof puzzlingcrm_ajax_obj !== 'undefined') {
        window.puzzlingNotifications = new PuzzlingCRM_WebSocket_Notifications();
    }
});
