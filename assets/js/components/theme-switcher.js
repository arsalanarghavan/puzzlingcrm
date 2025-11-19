/**
 * Theme Switcher JavaScript (from maneli-car-inquiry)
 * 
 * Handles light/dark theme switching
 *
 * @package PuzzlingCRM
 * @since 2.1.0
 */

(function ($) {
	'use strict';

	/**
	 * Theme Switcher
	 */
	const PuzzlingThemeSwitcher = {
		/**
		 * Initialize
		 */
		init: function () {
			this.applySavedTheme();
			this.setupThemeToggle();
		},

		/**
		 * Apply saved theme from localStorage
		 */
		applySavedTheme: function () {
			// Already applied in head script, this is just a backup
			const savedTheme = localStorage.getItem('xintradarktheme');
			const $html = $('html');

			if (savedTheme && savedTheme === 'true') {
				$html.attr('data-theme-mode', 'dark');
				$html.attr('data-header-styles', 'dark');
				$html.attr('data-menu-styles', 'dark');
			}
		},

		/**
		 * Setup theme toggle button
		 */
		setupThemeToggle: function () {
			$(document).on('click', '.layout-setting', function (e) {
				e.preventDefault();
				e.stopPropagation();
				PuzzlingThemeSwitcher.toggleTheme();
			});
		},

		/**
		 * Toggle between light and dark theme (fixed version)
		 */
		toggleTheme: function () {
			const $html = $('html');
			const currentTheme = $html.attr('data-theme-mode') || 'light';
			
			console.log('Current theme:', currentTheme);

			if (currentTheme === 'dark') {
				// Switch to light mode
				console.log('Switching to light mode...');
				$html.attr('data-theme-mode', 'light');
				$html.attr('data-header-styles', 'light');
				$html.attr('data-menu-styles', 'dark');
				
				// Remove all dark mode CSS variables
				const htmlEl = $html[0];
				if (htmlEl && htmlEl.style) {
					htmlEl.style.removeProperty('--body-bg-rgb');
					htmlEl.style.removeProperty('--body-bg-rgb2');
					htmlEl.style.removeProperty('--light-rgb');
					htmlEl.style.removeProperty('--form-control-bg');
					htmlEl.style.removeProperty('--input-border');
					htmlEl.style.removeProperty('--default-body-bg-color');
					htmlEl.style.removeProperty('--menu-bg');
					htmlEl.style.removeProperty('--header-bg');
					htmlEl.style.removeProperty('--custom-white');
				}
				
				// Update body class
				const $body = $('body');
				$body.removeClass('dark-mode').addClass('light-mode');
				
				// Clear localStorage
				localStorage.removeItem('xintradarktheme');
				localStorage.removeItem('xintraMenu');
				localStorage.removeItem('xintraHeader');
				localStorage.removeItem('bodylightRGB');
				localStorage.removeItem('bodyBgRGB');
				
				console.log('Switched to light mode');
			} else {
				// Switch to dark mode
				console.log('Switching to dark mode...');
				$html.attr('data-theme-mode', 'dark');
				$html.attr('data-header-styles', 'dark');
				$html.attr('data-menu-styles', 'dark');
				
				// Set CSS variables for dark mode
				const htmlEl = $html[0];
				if (htmlEl && htmlEl.style && !localStorage.getItem("bodyBgRGB")) {
					htmlEl.style.setProperty('--body-bg-rgb', '25, 25, 28');
					htmlEl.style.setProperty('--body-bg-rgb2', '45, 45, 48');
					htmlEl.style.setProperty('--light-rgb', '43, 46, 49');
					htmlEl.style.setProperty('--form-control-bg', 'rgb(25, 25, 28)');
					htmlEl.style.setProperty('--input-border', 'rgba(255, 255, 255, 0.1)');
					htmlEl.style.setProperty('--default-body-bg-color', 'rgb(45, 45, 48)');
					htmlEl.style.setProperty('--menu-bg', 'rgb(25, 25, 28)');
					htmlEl.style.setProperty('--header-bg', 'rgb(25, 25, 28)');
					htmlEl.style.setProperty('--custom-white', 'rgb(25, 25, 28)');
				}
				
				// Update body class
				const $body = $('body');
				$body.removeClass('light-mode').addClass('dark-mode');
				
				localStorage.setItem('xintradarktheme', 'true');
				localStorage.setItem('xintraMenu', 'dark');
				localStorage.setItem('xintraHeader', 'dark');
				localStorage.removeItem('bodylightRGB');
				localStorage.removeItem('bodyBgRGB');
				
				console.log('Switched to dark mode');
			}
		},

		/**
		 * Get current theme
		 */
		getCurrentTheme: function () {
			return $('html').attr('data-theme-mode') || 'light';
		}
	};

	/**
	 * Initialize on document ready
	 */
	$(document).ready(function () {
		PuzzlingThemeSwitcher.init();
	});

	// Make globally accessible
	window.PuzzlingThemeSwitcher = PuzzlingThemeSwitcher;

})(jQuery);
