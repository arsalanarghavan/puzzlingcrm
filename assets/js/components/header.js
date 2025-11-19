/**
 * Header Component JavaScript
 * 
 * Handles header interactions and functionality
 *
 * @package PuzzlingCRM
 * @since 2.1.0
 */

(function ($) {
	'use strict';

	/**
	 * Header Component
	 */
	const PuzzlingHeader = {
		/**
		 * Initialize
		 */
		init: function () {
			this.setupSearch();
			this.setupNotifications();
			this.setupProfileMenu();
			this.setupLanguageSwitcher();
			this.setupThemeSwitcher();
		},

		/**
		 * Setup search functionality
		 */
		setupSearch: function () {
			const $searchInput = $('#global-search-input');
			const $searchResults = $('#global-search-results');
			let searchTimeout;

			if ($searchInput.length === 0) {
				return;
			}

			$searchInput.on('input', function () {
				const query = $(this).val().trim();

				clearTimeout(searchTimeout);

				if (query.length < 2) {
					$searchResults.hide().html('');
					return;
				}

				$searchResults.show().html('<div class="text-center py-3"><div class="spinner-border spinner-border-sm" role="status"><span class="visually-hidden">Loading...</span></div></div>');

				searchTimeout = setTimeout(function () {
					PuzzlingHeader.performSearch(query, $searchResults);
				}, 300);
			});

			// Hide search results when clicking outside
			$(document).on('click', function (e) {
				if (!$(e.target).closest('.auto-complete-search').length) {
					$searchResults.hide();
				}
			});
		},

		/**
		 * Perform search
		 */
		performSearch: function (query, $results) {
			$.ajax({
				url: pzlHeader.ajaxUrl,
				type: 'POST',
				data: {
					action: 'pzl_global_search',
					nonce: pzlHeader.nonce,
					query: query
				},
				success: function (response) {
					if (response.success && response.data) {
						PuzzlingHeader.displaySearchResults(response.data, $results);
					} else {
						$results.html('<div class="text-center py-3 text-muted">' + pzlHeader.i18n.noResults + '</div>');
					}
				},
				error: function () {
					$results.html('<div class="text-center py-3 text-danger">Error loading results</div>');
				}
			});
		},

		/**
		 * Display search results
		 */
		displaySearchResults: function (results, $container) {
			const $resultsContent = $('#global-search-results-content');
			
			if (!results || results.length === 0) {
				$resultsContent.html('<div class="text-center py-3 text-muted">' + (pzlHeader.i18n.noResults || 'No results found') + '</div>');
				return;
			}

			let html = '';
			let currentSection = '';
			
			results.forEach(function (item) {
				// Add section header if different
				if (item.section && item.section !== currentSection) {
					html += '<div class="search-section">';
					html += '<div class="search-section-title">' + item.section + '</div>';
					currentSection = item.section;
				}
				
				html += '<a href="' + item.url + '" class="search-result-item">';
				html += '<div class="d-flex align-items-center">';
				if (item.icon) {
					html += '<i class="' + item.icon + ' me-2 text-muted"></i>';
				}
				html += '<div class="flex-grow-1">';
				html += '<div class="fw-medium">' + item.title + '</div>';
				if (item.description) {
					html += '<div class="text-muted fs-12">' + item.description + '</div>';
				}
				html += '</div>';
				html += '</div>';
				html += '</a>';
			});
			
			if (currentSection) {
				html += '</div>';
			}

			$resultsContent.html(html);
		},

		/**
		 * Setup notifications
		 */
		setupNotifications: function () {
			const $notificationDropdown = $('.notifications-dropdown');

			if ($notificationDropdown.length === 0) {
				return;
			}

			// Load notifications when dropdown is opened
			$notificationDropdown.on('show.bs.dropdown', function () {
				PuzzlingHeader.loadNotifications();
			});

			// Setup notification polling (every 60 seconds)
			setInterval(function () {
				PuzzlingHeader.updateNotificationCount();
			}, 60000);
		},

		/**
		 * Load notifications
		 */
		loadNotifications: function () {
			const $scroll = $('#header-notification-scroll');

			if ($scroll.length === 0) {
				return;
			}

			$scroll.html('<li class="dropdown-item text-center"><div class="spinner-border spinner-border-sm" role="status"></div></li>');

			$.ajax({
				url: pzlHeader.ajaxUrl,
				type: 'POST',
				data: {
					action: 'pzl_get_notifications',
					nonce: pzlHeader.nonce
				},
				success: function (response) {
					if (response.success && response.data) {
						PuzzlingHeader.displayNotifications(response.data);
					}
				}
			});
		},

		/**
		 * Display notifications
		 */
		displayNotifications: function (notifications) {
			const $scroll = $('#header-notification-scroll');

			if (notifications.length === 0) {
				$scroll.html('<li class="dropdown-item text-center text-muted">' + pzlHeader.i18n.notifications + '</li>');
				return;
			}

			let html = '';
			notifications.forEach(function (notif) {
				html += '<li class="dropdown-item">';
				html += '<div class="d-flex align-items-start">';
				html += '<div class="pe-2"><span class="avatar avatar-md bg-primary-transparent avatar-rounded"><i class="' + (notif.icon || 'ri-notification-3-line') + '"></i></span></div>';
				html += '<div class="flex-grow-1">';
				html += '<p class="mb-0 fw-semibold">' + notif.title + '</p>';
				html += '<span class="text-muted fw-normal fs-12">' + notif.message + '</span>';
				html += '</div>';
				html += '<div><span class="text-muted fs-11">' + notif.time + '</span></div>';
				html += '</div>';
				html += '</li>';
			});

			$scroll.html(html);
		},

		/**
		 * Update notification count
		 */
		updateNotificationCount: function () {
			$.ajax({
				url: pzlHeader.ajaxUrl,
				type: 'POST',
				data: {
					action: 'pzl_get_notification_count',
					nonce: pzlHeader.nonce
				},
				success: function (response) {
					if (response.success && response.data) {
						const count = response.data.count || 0;
						const $badge = $('.header-icon-pulse');
						const $countText = $('#notifiation-data');

						if (count > 0) {
							$badge.show();
							$countText.text(count + ' ' + pzlHeader.i18n.unread);
						} else {
							$badge.hide();
							$countText.text('0 ' + pzlHeader.i18n.unread);
						}
					}
				}
			});
		},

		/**
		 * Setup profile menu
		 * Bootstrap dropdown handles this automatically, no need to initialize manually
		 */
		setupProfileMenu: function () {
			// Bootstrap dropdown is auto-initialized via data-bs-toggle="dropdown"
			// No manual initialization needed
		},

		/**
		 * Setup language switcher
		 * Uses global changeLanguage function (defined in head)
		 * Bootstrap dropdown handles the dropdown, we just need to ensure onclick works
		 */
		setupLanguageSwitcher: function () {
			// Language switcher uses onclick="changeLanguage()" which is defined in head
			// Bootstrap dropdown handles the dropdown toggle automatically
			// No additional setup needed
		},

		/**
		 * Setup theme switcher (light/dark mode)
		 * Use the global theme switcher if available
		 */
		setupThemeSwitcher: function () {
			// Use global theme switcher if available
			if (window.PuzzlingThemeSwitcher) {
				return; // Already handled by theme-switcher.js
			}
			
			// Fallback if theme-switcher.js is not loaded
			$(document).on('click', '.layout-setting', function (e) {
				e.preventDefault();
				if (window.PuzzlingThemeSwitcher) {
					window.PuzzlingThemeSwitcher.toggleTheme();
				}
			});
		}
	};

	/**
	 * Initialize on document ready
	 * Also check if pzlHeader is available (localized script)
	 */
	function initPuzzlingHeader() {
		// Check if jQuery is available
		if (typeof jQuery === 'undefined') {
			console.warn('PuzzlingCRM Header: jQuery not found. Retrying...');
			setTimeout(initPuzzlingHeader, 100);
			return;
		}
		
		var $ = jQuery;
		
		// Check if pzlHeader is available (optional, will use defaults if not)
		if (typeof pzlHeader === 'undefined') {
			console.warn('PuzzlingCRM Header: pzlHeader object not found. Using defaults.');
			// Create default pzlHeader object
			window.pzlHeader = {
				ajaxUrl: window.ajaxurl || '/wp-admin/admin-ajax.php',
				nonce: '',
				i18n: {
					search: 'Search...',
					noResults: 'No results found',
					loading: 'Loading...',
					notifications: 'Notifications',
					viewAll: 'View All',
					unread: 'Unread'
				}
			};
		}
		
		// Initialize header
		try {
			PuzzlingHeader.init();
			console.log('PuzzlingCRM Header initialized successfully');
		} catch (error) {
			console.error('PuzzlingCRM Header initialization error:', error);
		}
	}

	// Initialize when DOM is ready
	if (typeof jQuery !== 'undefined') {
		jQuery(document).ready(function ($) {
			initPuzzlingHeader();
		});
	} else {
		// Fallback if jQuery is not loaded yet
		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', initPuzzlingHeader);
		} else {
			initPuzzlingHeader();
		}
	}

	// Make globally accessible
	window.PuzzlingHeader = PuzzlingHeader;

})(jQuery);

