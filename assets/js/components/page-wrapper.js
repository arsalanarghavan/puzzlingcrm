/**
 * Page Wrapper Component JavaScript
 * 
 * Handles dynamic margin adjustment based on sidebar state
 *
 * @package PuzzlingCRM
 * @since 2.1.0
 */

(function ($) {
	'use strict';

	/**
	 * Page Wrapper Component
	 */
	const PuzzlingPageWrapper = {
		/**
		 * Initialize
		 */
		init: function () {
			this.setupSidebarAdaptation();
			this.setupShareActions();
			this.setupFilterModal();
			this.updateWrapperMargin();
			
			// Listen for sidebar state changes
			$(document).on('sidebar:toggled', this.updateWrapperMargin.bind(this));
			
			// Listen for window resize
			$(window).on('resize', this.debounce(this.updateWrapperMargin.bind(this), 250));
			
			// Listen for theme mode changes (if sidebar width changes)
			$(document).on('theme:changed', this.updateWrapperMargin.bind(this));
		},

		/**
		 * Setup sidebar adaptation
		 */
		setupSidebarAdaptation: function () {
			// Use MutationObserver to watch for sidebar state changes
			const observer = new MutationObserver((mutations) => {
				mutations.forEach((mutation) => {
					if (mutation.type === 'attributes' && mutation.attributeName === 'data-toggled') {
						this.updateWrapperMargin();
					}
				});
			});

			// Observe the html element for data-toggled changes
			const htmlElement = document.documentElement;
			if (htmlElement) {
				observer.observe(htmlElement, {
					attributes: true,
					attributeFilter: ['data-toggled']
				});
			}

			// Also observe the sidebar element for class changes
			const sidebar = document.getElementById('sidebar');
			if (sidebar) {
				observer.observe(sidebar, {
					attributes: true,
					attributeFilter: ['class']
				});
			}
		},

		/**
		 * Update wrapper margin based on sidebar state
		 */
		updateWrapperMargin: function () {
			const $wrapper = $('.pzl-page-wrapper');
			if (!$wrapper.length) {
				return;
			}

			const html = document.documentElement;
			const toggled = html.getAttribute('data-toggled') || '';
			const isRTL = html.getAttribute('dir') === 'rtl';
			
			// Margin from sidebar (always 10px)
			const sidebarMargin = 10;
			// Margin from opposite edge (always 15px)
			const oppositeMargin = 15;
			
			// Apply margins only on desktop
			if (window.innerWidth >= 992) {
				if (isRTL) {
					// RTL: sidebar on right, margin-right from sidebar, margin-left from left edge
					$wrapper.css('margin-right', sidebarMargin + 'px');
					$wrapper.css('margin-left', oppositeMargin + 'px');
					$wrapper.css('width', ''); // Remove width, let it be auto
				} else {
					// LTR: sidebar on left, margin-left from sidebar, margin-right from right edge
					$wrapper.css('margin-left', sidebarMargin + 'px');
					$wrapper.css('margin-right', oppositeMargin + 'px');
					$wrapper.css('width', ''); // Remove width, let it be auto
				}
			} else {
				// Mobile: reset to default
				$wrapper.css('margin-right', '15px');
				$wrapper.css('margin-left', '15px');
				$wrapper.css('width', '');
			}
		},

		/**
		 * Setup share actions
		 */
		setupShareActions: function () {
			// Copy link
			$(document).on('click', '#pzl-share-link', function (e) {
				e.preventDefault();
				const url = window.location.href;
				
				// Use Clipboard API if available
				if (navigator.clipboard && navigator.clipboard.writeText) {
					navigator.clipboard.writeText(url).then(function () {
						PuzzlingPageWrapper.showNotification('لینک با موفقیت کپی شد', 'success');
					}).catch(function () {
						PuzzlingPageWrapper.fallbackCopyTextToClipboard(url);
					});
				} else {
					PuzzlingPageWrapper.fallbackCopyTextToClipboard(url);
				}
			});

			// Export PDF
			$(document).on('click', '#pzl-share-pdf', function (e) {
				e.preventDefault();
				PuzzlingPageWrapper.showNotification('در حال آماده‌سازی PDF...', 'info');
				// TODO: Implement PDF export functionality
				setTimeout(function () {
					PuzzlingPageWrapper.showNotification('قابلیت خروجی PDF به زودی اضافه می‌شود', 'info');
				}, 1000);
			});

			// Print
			$(document).on('click', '#pzl-share-print', function (e) {
				e.preventDefault();
				window.print();
			});
		},

		/**
		 * Fallback copy to clipboard for older browsers
		 */
		fallbackCopyTextToClipboard: function (text) {
			const textArea = document.createElement('textarea');
			textArea.value = text;
			textArea.style.position = 'fixed';
			textArea.style.left = '-999999px';
			textArea.style.top = '-999999px';
			document.body.appendChild(textArea);
			textArea.focus();
			textArea.select();
			
			try {
				document.execCommand('copy');
				this.showNotification('لینک با موفقیت کپی شد', 'success');
			} catch (err) {
				this.showNotification('خطا در کپی کردن لینک', 'error');
			}
			
			document.body.removeChild(textArea);
		},

		/**
		 * Setup filter modal
		 */
		setupFilterModal: function () {
			// Filter modal is already set up in the template
			// This function can be extended to add dynamic filter content
			$(document).on('click', '#pzl-apply-filters', function () {
				// TODO: Implement filter application logic
				const modal = bootstrap.Modal.getInstance(document.getElementById('pzl-filter-modal'));
				if (modal) {
					modal.hide();
				}
				PuzzlingPageWrapper.showNotification('فیلترها اعمال شدند', 'success');
			});
		},

		/**
		 * Show notification
		 */
		showNotification: function (message, type) {
			type = type || 'info';
			
			// Use Toastify if available
			if (typeof Toastify !== 'undefined') {
				Toastify({
					text: message,
					duration: 3000,
					gravity: 'top',
					position: 'left',
					backgroundColor: type === 'success' ? '#28a745' : type === 'error' ? '#dc3545' : '#17a2b8',
				}).showToast();
			} else {
				// Fallback to alert
				alert(message);
			}
		},

		/**
		 * Debounce function
		 */
		debounce: function (func, wait) {
			let timeout;
			return function executedFunction(...args) {
				const later = () => {
					clearTimeout(timeout);
					func(...args);
				};
				clearTimeout(timeout);
				timeout = setTimeout(later, wait);
			};
		}
	};

	/**
	 * Initialize on document ready
	 */
	$(document).ready(function () {
		PuzzlingPageWrapper.init();
	});

	// Also initialize after a short delay to ensure all scripts are loaded
	setTimeout(function () {
		PuzzlingPageWrapper.init();
	}, 100);

})(jQuery);

