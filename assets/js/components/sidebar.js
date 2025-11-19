/**
 * Sidebar Component JavaScript
 * 
 * Handles sidebar interactions and functionality
 *
 * @package PuzzlingCRM
 * @since 2.1.0
 */

(function ($) {
	'use strict';

	/**
	 * Sidebar Component
	 */
	const PuzzlingSidebar = {
		/**
		 * Initialize
		 */
		init: function () {
			this.setupMenuToggle();
			this.setupSubMenus();
			this.setupMobileOverlay();
			this.highlightActivePage();
		},

		/**
		 * Setup menu toggle (show/hide sidebar)
		 */
		setupMenuToggle: function () {
			$(document).on('click', '.sidemenu-toggle', function (e) {
				e.preventDefault();
				PuzzlingSidebar.toggleSidebar();
			});
		},

		/**
		 * Toggle sidebar visibility
		 */
		toggleSidebar: function () {
			const $sidebar = $('#sidebar');
			const $overlay = $('.pzl-sidebar-overlay');
			const isOpen = !$sidebar.hasClass('pzl-sidebar--closed');

			if (isOpen) {
				$sidebar.addClass('pzl-sidebar--closed');
				$overlay.removeClass('pzl-sidebar-overlay--active');
				localStorage.setItem('pzl_sidebar_state', 'closed');
			} else {
				$sidebar.removeClass('pzl-sidebar--closed');
				$overlay.addClass('pzl-sidebar-overlay--active');
				localStorage.setItem('pzl_sidebar_state', 'open');
			}
		},

		/**
		 * Setup sub-menus (accordion behavior)
		 */
		setupSubMenus: function () {
			$(document).on('click', '.has-sub > .side-menu__item', function (e) {
				const $link = $(this);
				const $parent = $link.parent('.has-sub');
				const $submenu = $parent.find('> .slide-menu');

				// Only prevent default if has submenu
				if ($submenu.length > 0) {
					e.preventDefault();

					// Close other open submenus
					$('.has-sub').not($parent).removeClass('open');
					$('.slide-menu').not($submenu).slideUp(300);

					// Toggle current submenu
					$parent.toggleClass('open');
					$submenu.slideToggle(300);
				}
			});

			// Keep submenu open if current page is in submenu
			this.openActiveSubMenu();
		},

		/**
		 * Open submenu containing active page
		 */
		openActiveSubMenu: function () {
			const $activeLink = $('.side-menu__item.active');

			if ($activeLink.length > 0) {
				const $submenu = $activeLink.closest('.slide-menu');

				if ($submenu.length > 0) {
					$submenu.parent('.has-sub').addClass('open');
					$submenu.show();
				}
			}
		},

		/**
		 * Setup mobile overlay
		 */
		setupMobileOverlay: function () {
			// Create overlay if doesn't exist
			if ($('.pzl-sidebar-overlay').length === 0) {
				$('body').append('<div class="pzl-sidebar-overlay"></div>');
			}

			// Close sidebar when clicking overlay
			$(document).on('click', '.pzl-sidebar-overlay', function () {
				PuzzlingSidebar.toggleSidebar();
			});
		},

		/**
		 * Highlight active page in menu
		 */
		highlightActivePage: function () {
			if (typeof pzlSidebar === 'undefined' || !pzlSidebar.currentPage) {
				return;
			}

			const currentPage = pzlSidebar.currentPage;

			// Remove existing active classes
			$('.side-menu__item').removeClass('active');

			// Add active class to current page
			$('.side-menu__item').each(function () {
				const $link = $(this);
				const href = $link.attr('href');

				if (href && href.includes('/' + currentPage)) {
					$link.addClass('active');

					// Open parent submenu if exists
					const $submenu = $link.closest('.slide-menu');
					if ($submenu.length > 0) {
						$submenu.parent('.has-sub').addClass('open');
						$submenu.show();
					}
				}
			});

			// If dashboard (empty page), activate dashboard link
			if (!currentPage || currentPage === '') {
				$('.side-menu__item[href*="/dashboard"]').first().addClass('active');
			}
		},

		/**
		 * Restore sidebar state from localStorage
		 */
		restoreSidebarState: function () {
			const savedState = localStorage.getItem('pzl_sidebar_state');

			if (savedState === 'closed') {
				$('#sidebar').addClass('pzl-sidebar--closed');
			}
		}
	};

	/**
	 * Initialize on document ready
	 */
	$(document).ready(function () {
		PuzzlingSidebar.init();
		PuzzlingSidebar.restoreSidebarState();
	});

	// Make globally accessible
	window.PuzzlingSidebar = PuzzlingSidebar;

})(jQuery);

