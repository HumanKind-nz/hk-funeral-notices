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
            $(document).on('change', '.wfn-module-toggle', this.handleModuleToggle.bind(this));
            
            // Module card hover effects
            $(document).on('mouseenter', '.wfn-module-card', this.handleCardHover);
            $(document).on('mouseleave', '.wfn-module-card', this.handleCardLeave);
            
            // Toggle label updates
            $(document).on('change', '.wfn-module-toggle', this.updateToggleLabel);
            
            // Header logo interaction
            $(document).on('click', '.wfn-plugin-logo', this.handleLogoClick);
            
            // Keyboard navigation
            $(document).on('keydown', this.handleKeyboard);
        },

        // Initialize animations
        initAnimations: function() {
            // Stagger animation for module cards
            $('.wfn-module-card').each(function(index) {
                $(this).css({
                    'animation-delay': (index * 0.1) + 's',
                    'animation': 'wfnFadeInUp 0.6s ease forwards'
                });
            });

            // Add CSS animation keyframes
            if (!$('#wfn-animations').length) {
                $('<style id="wfn-animations">').text(`
                    @keyframes wfnFadeInUp {
                        0% {
                            opacity: 0;
                            transform: translateY(20px);
                        }
                        100% {
                            opacity: 1;
                            transform: translateY(0);
                        }
                    }
                    
                    @keyframes wfnPulse {
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
            const $card = $toggle.closest('.wfn-module-card');

            // Add loading state
            $card.addClass('wfn-loading');
            
            // Disable toggle temporarily
            $toggle.prop('disabled', true);

            // Send AJAX request
            $.ajax({
                url: wfnAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'wfn_toggle_module',
                    nonce: wfnAdmin.nonce,
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
                    $card.removeClass('wfn-loading');
                    $toggle.prop('disabled', false);
                }
            });
        },

        // Update module card appearance
        updateModuleCard: function($card, enabled) {
            const $status = $card.find('.wfn-status-indicator');
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
            $card.css('animation', 'wfnPulse 0.6s ease');
            setTimeout(() => {
                $card.css('animation', '');
            }, 600);
        },

        // Update toggle label
        updateToggleLabel: function(e) {
            const $toggle = $(e.target);
            const $label = $toggle.siblings('.wfn-toggle-label');
            const enabled = $toggle.is(':checked');
            
            $label.text(enabled ? 'Enabled' : 'Disabled');
        },

        // Update statistics
        updateStats: function() {
            const activeModules = $('.wfn-module-toggle:checked').length;
            $('.wfn-stat-card').eq(2).find('.stat-number').text(activeModules);
        },

        // Card hover effects
        handleCardHover: function() {
            $(this).find('.wfn-module-icon').css('animation', 'wfnPulse 1s ease infinite');
        },

        handleCardLeave: function() {
            $(this).find('.wfn-module-icon').css('animation', '');
        },

        // Logo click easter egg
        handleLogoClick: function() {
            const $logo = $('.wfn-plugin-logo');
            $logo.css('animation', 'wfnPulse 0.8s ease');
            
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
                    const $moduleToggle = $('.wfn-module-toggle').eq(keyNum - 1);
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
            if (!$('#wfn-notifications').length) {
                $('<div id="wfn-notifications">').appendTo('body');
            }
        },

        showNotification: function(message, type = 'success', duration = 4000) {
            const $notification = $(`
                <div class="wfn-notification ${type}">
                    ${message}
                    <button type="button" class="wfn-notification-close" aria-label="Close">×</button>
                </div>
            `);

            // Add to container
            $('#wfn-notifications').append($notification);

            // Show notification
            setTimeout(() => {
                $notification.addClass('show');
            }, 100);

            // Auto-hide after duration
            setTimeout(() => {
                this.hideNotification($notification);
            }, duration);

            // Manual close
            $notification.find('.wfn-notification-close').on('click', () => {
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
            $('.wfn-module-toggle:not(:checked)').each((index, toggle) => {
                setTimeout(() => {
                    $(toggle).click();
                }, index * 200);
            });
        },

        disableAllModules: function() {
            $('.wfn-module-toggle:checked').each((index, toggle) => {
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

            $('.wfn-module-toggle').each(function() {
                const moduleId = $(this).data('module');
                settings.modules[moduleId] = $(this).is(':checked');
            });

            const blob = new Blob([JSON.stringify(settings, null, 2)], {
                type: 'application/json'
            });
            
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'wfn-settings.json';
            a.click();
            URL.revokeObjectURL(url);

            this.showNotification('Settings exported successfully', 'success');
        },

        // Performance monitoring
        trackPerformance: function() {
            if (window.performance && window.performance.timing) {
                const timing = window.performance.timing;
                const loadTime = timing.loadEventEnd - timing.navigationStart;
                
                console.log('WFN Admin Dashboard Load Time:', loadTime + 'ms');
                
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

    // Console welcome message
    console.log(`
    ╔══════════════════════════════════════╗
    ║     Weave Funeral Notices v2.0      ║
    ║      Professional Admin Panel       ║
    ╚══════════════════════════════════════╝
    
    Dashboard loaded successfully!
    Use WFNDashboard object for interaction.
    `);
});

// Additional notification styles
jQuery(document).ready(function($) {
    if (!$('#wfn-notification-styles').length) {
        $('<style id="wfn-notification-styles">').text(`
            #wfn-notifications {
                position: fixed;
                top: 32px;
                right: 20px;
                z-index: 999999;
                pointer-events: none;
            }
            
            .wfn-notification {
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
            
            .wfn-notification.show {
                transform: translateX(0);
                opacity: 1;
            }
            
            .wfn-notification.success {
                background: linear-gradient(135deg, #27ae60 0%, #219a52 100%);
            }
            
            .wfn-notification.error {
                background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            }
            
            .wfn-notification.warning {
                background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
            }
            
            .wfn-notification-close {
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
            
            .wfn-notification-close:hover {
                opacity: 1;
            }
        `).appendTo('head');
    }
});