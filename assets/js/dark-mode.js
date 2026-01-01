/**
 * Dark Mode Handler for PuzzlingCRM
 * Manages dark/light theme switching with localStorage persistence
 */

(function($) {
    'use strict';

    class DarkModeHandler {
        constructor() {
            this.theme = this.getStoredTheme();
            this.init();
        }

        init() {
            // Apply stored theme on load
            this.applyTheme(this.theme);
            
            // Create toggle button
            this.createToggleButton();
            
            // Listen for system preference changes
            this.listenToSystemPreference();
        }

        getStoredTheme() {
            // Check if theme is already set on HTML element (from dashboard-wrapper.php)
            const htmlTheme = document.documentElement.getAttribute('data-theme-mode');
            if (htmlTheme === 'dark' || htmlTheme === 'light') {
                return htmlTheme;
            }
            
            const stored = localStorage.getItem('puzzlingcrm_theme');
            
            if (stored) {
                return stored;
            }
            
            // Check system preference
            if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                return 'dark';
            }
            
            return 'light';
        }

        applyTheme(theme) {
            // Use data-theme-mode to match dashboard-wrapper.php
            document.documentElement.setAttribute('data-theme-mode', theme);
            document.documentElement.setAttribute('data-theme', theme); // Keep for compatibility
            this.theme = theme;
            localStorage.setItem('puzzlingcrm_theme', theme);
            
            // Update toggle button icon
            this.updateToggleIcon();
            
            // Trigger event for other components
            $(document).trigger('puzzlingcrm:theme-changed', [theme]);
            
            // Update charts if they exist
            this.updateCharts();
        }

        toggleTheme() {
            const newTheme = this.theme === 'dark' ? 'light' : 'dark';
            this.applyTheme(newTheme);
            
            // Show notification
            this.showNotification(`حالت ${newTheme === 'dark' ? 'تاریک' : 'روشن'} فعال شد`);
        }

        createToggleButton() {
            const button = $(`
                <button class="dark-mode-toggle" title="تغییر تم">
                    <i class="fas fa-moon"></i>
                </button>
            `);
            
            button.on('click', () => {
                this.toggleTheme();
            });
            
            $('body').append(button);
        }

        updateToggleIcon() {
            const icon = $('.dark-mode-toggle i');
            
            if (this.theme === 'dark') {
                icon.removeClass('fa-moon').addClass('fa-sun');
            } else {
                icon.removeClass('fa-sun').addClass('fa-moon');
            }
        }

        listenToSystemPreference() {
            if (window.matchMedia) {
                const darkModeQuery = window.matchMedia('(prefers-color-scheme: dark)');
                
                darkModeQuery.addEventListener('change', (e) => {
                    // Only apply if user hasn't manually set a preference
                    if (!localStorage.getItem('puzzlingcrm_theme_manual')) {
                        this.applyTheme(e.matches ? 'dark' : 'light');
                    }
                });
            }
        }

        showNotification(message) {
            const notification = $(`
                <div class="theme-notification" style="
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: var(--bg-secondary);
                    color: var(--text-primary);
                    padding: 15px 20px;
                    border-radius: 8px;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
                    z-index: 10000;
                    display: flex;
                    align-items: center;
                    gap: 10px;
                ">
                    <i class="fas fa-check-circle" style="color: var(--success-color);"></i>
                    <span>${message}</span>
                </div>
            `);
            
            $('body').append(notification);
            
            setTimeout(() => {
                notification.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 2000);
        }

        updateCharts() {
            // Update ApexCharts if they exist
            if (window.Apex) {
                const colors = this.theme === 'dark' ? {
                    theme: {
                        mode: 'dark'
                    },
                    chart: {
                        background: 'transparent',
                        foreColor: '#e8e8e8'
                    },
                    grid: {
                        borderColor: '#2d3748'
                    },
                    tooltip: {
                        theme: 'dark'
                    }
                } : {
                    theme: {
                        mode: 'light'
                    },
                    chart: {
                        background: 'transparent',
                        foreColor: '#373d3f'
                    },
                    grid: {
                        borderColor: '#e0e0e0'
                    },
                    tooltip: {
                        theme: 'light'
                    }
                };
                
                // Update global defaults
                if (Apex._chartInstances && Array.isArray(Apex._chartInstances)) {
                    Apex._chartInstances.forEach(chart => {
                        if (chart && chart.updateOptions) {
                            chart.updateOptions(colors, false, true);
                        }
                    });
                }
            }
            
            // Update Chart.js if it exists
            if (window.Chart) {
                const chartColor = this.theme === 'dark' ? '#e8e8e8' : '#373d3f';
                const gridColor = this.theme === 'dark' ? '#2d3748' : '#e0e0e0';
                
                Chart.defaults.color = chartColor;
                Chart.defaults.borderColor = gridColor;
                Chart.defaults.backgroundColor = 'transparent';
                
                // Update all existing charts
                Object.values(Chart.instances).forEach(chart => {
                    if (chart) {
                        chart.options.plugins.legend.labels.color = chartColor;
                        chart.options.scales.x.grid.color = gridColor;
                        chart.options.scales.y.grid.color = gridColor;
                        chart.options.scales.x.ticks.color = chartColor;
                        chart.options.scales.y.ticks.color = chartColor;
                        chart.update();
                    }
                });
            }
        }

        // Public API
        getCurrentTheme() {
            return this.theme;
        }

        setTheme(theme) {
            if (theme === 'dark' || theme === 'light') {
                this.applyTheme(theme);
                localStorage.setItem('puzzlingcrm_theme_manual', 'true');
            }
        }

        isSystemDarkMode() {
            return window.matchMedia && 
                   window.matchMedia('(prefers-color-scheme: dark)').matches;
        }
    }

    // Initialize on document ready
    $(document).ready(function() {
        window.PuzzlingDarkMode = new DarkModeHandler();
    });

    // Public jQuery plugin
    $.fn.puzzlingDarkMode = function(action, ...args) {
        if (window.PuzzlingDarkMode) {
            if (typeof action === 'string' && typeof window.PuzzlingDarkMode[action] === 'function') {
                return window.PuzzlingDarkMode[action](...args);
            }
        }
        return this;
    };

})(jQuery);

