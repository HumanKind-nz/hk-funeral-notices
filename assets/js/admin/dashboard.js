/**
 * WFN Admin Dashboard JavaScript
 * 
 * Interactive functionality for the FCRM-style admin interface
 * including module toggles, notifications, and AJAX functionality
 * 
 * @package WeaveStudios\FuneralNotices
 * @since 2.0.0
 */

jQuery(document).ready(function($) {
    'use strict';

    // Dashboard object
    const WFNDashboard = {
        
        // Initialize dashboard functionality
        init: function() {
            this.bindEvents();
            this.initAnimations();
            this.setupNotifications();
        },

        // Bind event handlers
        bindEvents: function() {
            // Module toggle switches
            $(document).on('change', '.hkfn-module-toggle', this.handleModuleToggle.bind(this));
            
            // Module card hover effects
            $(document).on('mouseenter', '.hkfn-module-card', this.handleCardHover);
            $(document).on('mouseleave', '.hkfn-module-card', this.handleCardLeave);
            
            // Toggle label updates
            $(document).on('change', '.hkfn-module-toggle', this.updateToggleLabel);
            
            // Header logo interaction
            $(document).on('click', '.hkfn-plugin-logo', this.handleLogoClick);
            
            // Keyboard navigation
            $(document).on('keydown', this.handleKeyboard);
        },

        // Initialize animations
        initAnimations: function() {
            // Stagger animation for module cards
            $('.hkfn-module-card').each(function(index) {
                $(this).css({
                    'animation-delay': (index * 0.1) + 's',
                    'animation': 'hkfnFadeInUp 0.6s ease forwards'
                });
            });

            // Add CSS animation keyframes
            if (!$('#hkfn-animations').length) {
                $('<style id="hkfn-animations">').text(`
                    @keyframes hkfnFadeInUp {
                        0% {
                            opacity: 0;
                            transform: translateY(20px);
                        }
                        100% {
                            opacity: 1;
                            transform: translateY(0);
                        }
                    }
                    
                    @keyframes hkfnPulse {
                        0%, 100% {
                            transform: scale(1);
                        }
                        50% {
                            transform: scale(1.05);
                        }
                    }
                `).appendTo('head');
            }
        },

        // Handle module toggle
        handleModuleToggle: function(e) {
            const $toggle = $(e.target);
            const moduleId = $toggle.data('module');
            const enabled = $toggle.is(':checked');
            const $card = $toggle.closest('.hkfn-module-card');

            // Add loading state
            $card.addClass('hkfn-loading');
            
            // Disable toggle temporarily
            $toggle.prop('disabled', true);

            // Send AJAX request
            $.ajax({
                url: hkfnAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'hkfn_toggle_module',
                    nonce: hkfnAdmin.nonce,
                    module_id: moduleId,
                    enabled: enabled
                },
                success: (response) => {
                    if (response.success) {
                        this.updateModuleCard($card, enabled);
                        this.showNotification(response.data.message, 'success');
                        this.updateStats();
                    } else {
                        this.showNotification('Error updating module: ' + response.data, 'error');
                        $toggle.prop('checked', !enabled); // Revert toggle
                    }
                },
                error: () => {
                    this.showNotification('Network error occurred', 'error');
                    $toggle.prop('checked', !enabled); // Revert toggle
                },
                complete: () => {
                    $card.removeClass('hkfn-loading');
                    $toggle.prop('disabled', false);
                }
            });
        },

        // Update module card appearance
        updateModuleCard: function($card, enabled) {
            const $status = $card.find('.hkfn-status-indicator');
            const $configButton = $card.find('.button-secondary');
            
            if (enabled) {
                $status.removeClass('inactive').addClass('active').text('Active');
                $configButton.show();
                $card.addClass('module-active');
            } else {
                $status.removeClass('active').addClass('inactive').text('Inactive');
                $configButton.hide();
                $card.removeClass('module-active');
            }

            // Add success animation
            $card.css('animation', 'hkfnPulse 0.6s ease');
            setTimeout(() => {
                $card.css('animation', '');
            }, 600);
        },

        // Update toggle label
        updateToggleLabel: function(e) {
            const $toggle = $(e.target);
            const $label = $toggle.siblings('.hkfn-toggle-label');
            const enabled = $toggle.is(':checked');
            
            $label.text(enabled ? 'Enabled' : 'Disabled');
        },

        // Update statistics
        updateStats: function() {
            const activeModules = $('.hkfn-module-toggle:checked').length;
            $('.hkfn-stat-card').eq(2).find('.stat-number').text(activeModules);
        },

        // Card hover effects
        handleCardHover: function() {
            $(this).find('.hkfn-module-icon').css('animation', 'hkfnPulse 1s ease infinite');
        },

        handleCardLeave: function() {
            $(this).find('.hkfn-module-icon').css('animation', '');
        },

        // Logo click easter egg
        handleLogoClick: function() {
            const $logo = $('.hkfn-plugin-logo');
            $logo.css('animation', 'hkfnPulse 0.8s ease');
            
            setTimeout(() => {
                $logo.css('animation', '');
            }, 800);
        },

        // Keyboard navigation
        handleKeyboard: function(e) {
            // Enable quick module toggling with number keys
            if (e.ctrlKey || e.metaKey) {
                const keyNum = parseInt(e.key);
                if (keyNum >= 1 && keyNum <= 9) {
                    const $moduleToggle = $('.hkfn-module-toggle').eq(keyNum - 1);
                    if ($moduleToggle.length) {
                        $moduleToggle.click();
                        e.preventDefault();
                    }
                }
            }
        },

        // Notification system
        setupNotifications: function() {
            // Create notification container if it doesn't exist
            if (!$('#hkfn-notifications').length) {
                $('<div id="hkfn-notifications">').appendTo('body');
            }
        },

        showNotification: function(message, type = 'success', duration = 4000) {
            const $notification = $(`
                <div class="hkfn-notification ${type}">
                    ${message}
                    <button type="button" class="hkfn-notification-close" aria-label="Close">×</button>
                </div>
            `);

            // Add to container
            $('#hkfn-notifications').append($notification);

            // Show notification
            setTimeout(() => {
                $notification.addClass('show');
            }, 100);

            // Auto-hide after duration
            setTimeout(() => {
                this.hideNotification($notification);
            }, duration);

            // Manual close
            $notification.find('.hkfn-notification-close').on('click', () => {
                this.hideNotification($notification);
            });
        },

        hideNotification: function($notification) {
            $notification.removeClass('show');
            setTimeout(() => {
                $notification.remove();
            }, 300);
        },

        // Module management helpers
        enableAllModules: function() {
            $('.hkfn-module-toggle:not(:checked)').each((index, toggle) => {
                setTimeout(() => {
                    $(toggle).click();
                }, index * 200);
            });
        },

        disableAllModules: function() {
            $('.hkfn-module-toggle:checked').each((index, toggle) => {
                setTimeout(() => {
                    $(toggle).click();
                }, index * 200);
            });
        },

        // Settings export/import
        exportSettings: function() {
            const settings = {
                modules: {},
                timestamp: Date.now(),
                version: '2.0.0'
            };

            $('.hkfn-module-toggle').each(function() {
                const moduleId = $(this).data('module');
                settings.modules[moduleId] = $(this).is(':checked');
            });

            const blob = new Blob([JSON.stringify(settings, null, 2)], {
                type: 'application/json'
            });
            
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'hkfn-settings.json';
            a.click();
            URL.revokeObjectURL(url);

            this.showNotification('Settings exported successfully', 'success');
        },

        // Performance monitoring
        trackPerformance: function() {
            if (window.performance && window.performance.timing) {
                const timing = window.performance.timing;
                const loadTime = timing.loadEventEnd - timing.navigationStart;

                // Send to analytics if needed
                if (typeof gtag !== 'undefined') {
                    gtag('event', 'admin_performance', {
                        'load_time': loadTime,
                        'page': 'dashboard'
                    });
                }
            }
        }
    };

    // Initialize dashboard
    WFNDashboard.init();

    // Performance tracking
    $(window).on('load', () => {
        WFNDashboard.trackPerformance();
    });

    // Global WFN object for external access
    window.WFNDashboard = WFNDashboard;
});

// Additional notification styles
jQuery(document).ready(function($) {
    if (!$('#hkfn-notification-styles').length) {
        $('<style id="hkfn-notification-styles">').text(`
            #hkfn-notifications {
                position: fixed;
                top: 32px;
                right: 20px;
                z-index: 999999;
                pointer-events: none;
            }
            
            .hkfn-notification {
                display: flex;
                align-items: center;
                justify-content: space-between;
                min-width: 300px;
                margin-bottom: 10px;
                padding: 12px 20px;
                border-radius: 6px;
                color: white;
                font-weight: 500;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
                transform: translateX(400px);
                transition: all 0.3s ease;
                pointer-events: auto;
                opacity: 0;
            }
            
            .hkfn-notification.show {
                transform: translateX(0);
                opacity: 1;
            }
            
            .hkfn-notification.success {
                background: linear-gradient(135deg, #27ae60 0%, #219a52 100%);
            }
            
            .hkfn-notification.error {
                background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            }
            
            .hkfn-notification.warning {
                background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
            }
            
            .hkfn-notification-close {
                background: none;
                border: none;
                color: white;
                font-size: 18px;
                cursor: pointer;
                padding: 0;
                margin-left: 15px;
                opacity: 0.8;
                transition: opacity 0.2s ease;
            }
            
            .hkfn-notification-close:hover {
                opacity: 1;
            }
        `).appendTo('head');
    }
});