/**
 * WFN Funeral Notices - Admin Scripts
 * 
 * Main admin functionality for funeral notices plugin
 * Adapted from FCRM Enhancement Suite
 * 
 * @package WeaveStudios\FuneralNotices
 * @since 2.0.0
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        
        // Initialize admin functionality
        initAdminFeatures();
        
        // Handle form submissions with loading states
        handleFormSubmissions();
        
        // Initialize toggle switches
        initToggleSwitches();
        
        // Initialize color picker
        initColorPicker();
        
        // Initialize layout selection
        initLayoutSelection();
        
        // Initialize tabs
        initTabs();
        
        // Initialize tooltips
        initTooltips();
        
    });

    /**
     * Initialize admin features
     */
    function initAdminFeatures() {
        // Add smooth scrolling to anchor links
        $('a[href^="#"]').on('click', function(e) {
            e.preventDefault();
            var target = $(this.getAttribute('href'));
            if (target.length) {
                $('html, body').stop().animate({
                    scrollTop: target.offset().top - 100
                }, 500);
            }
        });

        // Add loading animation to buttons on click
        $('.button-primary, .button-secondary').on('click', function() {
            var $button = $(this);
            if (!$button.hasClass('loading')) {
                $button.addClass('loading');
                setTimeout(function() {
                    $button.removeClass('loading');
                }, 2000);
            }
        });

        // Animate module cards on load
        setTimeout(function() {
            $('.wfn-module-card').each(function(index) {
                var $card = $(this);
                setTimeout(function() {
                    $card.addClass('loaded');
                }, index * 100);
            });
        }, 500);
    }

    /**
     * Handle form submissions with loading states
     */
    function handleFormSubmissions() {
        $('form').on('submit', function(e) {
            var $form = $(this);
            var $submitButton = $form.find('input[type="submit"], button[type="submit"]');
            
            // Don't prevent default - let WordPress handle the form submission
            
            // Add loading state
            $submitButton.addClass('loading').prop('disabled', true);
            
            // Update button text if available
            var originalText = $submitButton.val() || $submitButton.text();
            if (wfnAdmin && wfnAdmin.strings && wfnAdmin.strings.saving) {
                $submitButton.val(wfnAdmin.strings.saving).text(wfnAdmin.strings.saving);
            }
            
            // Show processing message
            showNotification('Processing settings...', 'info');
        });
    }

    /**
     * Initialize toggle switches
     */
    function initToggleSwitches() {
        $('.toggle-switch input').on('change', function() {
            var $toggle = $(this);
            var isChecked = $toggle.is(':checked');
            var settingName = $toggle.attr('name');
            var settingLabel = $toggle.closest('tr').find('th').text().trim();
            
            // Show status update
            showNotification(
                settingLabel + ' ' + (isChecked ? 'enabled' : 'disabled'),
                isChecked ? 'success' : 'warning'
            );

            // Add visual feedback
            $toggle.closest('.toggle-switch').addClass('changed');
            setTimeout(function() {
                $toggle.closest('.toggle-switch').removeClass('changed');
            }, 300);
        });
    }

    /**
     * Initialize color picker
     */
    function initColorPicker() {
        if ($('.alpha-color-picker').length) {
            $('.alpha-color-picker').alphaColorPicker();
        }
        
        if ($('.wp-color-picker').length) {
            $('.wp-color-picker').each(function() {
                var $input = $(this);
                
                $input.wpColorPicker({
                    change: function(event, ui) {
                        var color = ui.color.toString();
                        showNotification('Colour updated to ' + color, 'info');
                    }
                });
            });
        }
    }

    /**
     * Initialize layout selection
     */
    function initLayoutSelection() {
        // Layout option clicks
        $('.layout-option').on('click', function() {
            var $option = $(this);
            var $radio = $option.find('input[type="radio"]');
            
            // Update selection
            $option.siblings().removeClass('selected');
            $option.addClass('selected');
            $radio.prop('checked', true);
            
            // Show feedback
            var layoutName = $option.find('h4').text();
            showNotification('Layout changed to ' + layoutName, 'success');
        });

        // Color scheme option clicks
        $('.color-scheme-option').on('click', function() {
            var $option = $(this);
            var $radio = $option.find('input[type="radio"]');
            
            // Update selection
            $option.siblings().removeClass('selected');
            $option.addClass('selected');
            $radio.prop('checked', true);
            
            // Show feedback
            var schemeName = $option.find('h5').text();
            showNotification('Colour scheme changed to ' + schemeName, 'success');
        });
    }

    /**
     * Initialize tabs
     */
    function initTabs() {
        $('.wfn-tab-nav button').on('click', function(e) {
            e.preventDefault();
            var $button = $(this);
            var targetTab = $button.data('tab');
            
            // Update nav
            $button.siblings().removeClass('active');
            $button.addClass('active');
            
            // Update content
            $('.wfn-tab-content').removeClass('active');
            $('#' + targetTab).addClass('active');
        });
    }

    /**
     * Show notification message
     */
    function showNotification(message, type) {
        type = type || 'info';
        
        var $notification = $('<div class="wfn-notification wfn-notification-' + type + '">' +
            '<span class="dashicons dashicons-' + getNotificationIcon(type) + '"></span>' +
            message +
            '<button class="notification-close">&times;</button>' +
            '</div>');
        
        // Remove existing notifications
        $('.wfn-notification').fadeOut(300, function() {
            $(this).remove();
        });
        
        // Add new notification
        $('body').append($notification);
        
        // Position notification
        $notification.css({
            position: 'fixed',
            top: '20px',
            right: '20px',
            zIndex: 100000,
            background: getNotificationColor(type),
            color: 'white',
            padding: '15px 20px',
            borderRadius: '6px',
            boxShadow: '0 4px 12px rgba(0,0,0,0.15)',
            maxWidth: '400px',
            display: 'flex',
            alignItems: 'center',
            gap: '10px'
        });
        
        // Close button functionality
        $notification.find('.notification-close').on('click', function() {
            $notification.fadeOut(300, function() {
                $(this).remove();
            });
        });
        
        // Auto-hide after 4 seconds
        setTimeout(function() {
            $notification.fadeOut(300, function() {
                $(this).remove();
            });
        }, 4000);
    }

    /**
     * Get notification icon based on type
     */
    function getNotificationIcon(type) {
        var icons = {
            'success': 'yes',
            'error': 'no',
            'warning': 'warning',
            'info': 'info'
        };
        return icons[type] || 'info';
    }

    /**
     * Get notification color based on type
     */
    function getNotificationColor(type) {
        var colors = {
            'success': '#28a745',
            'error': '#dc3545',
            'warning': '#ffc107',
            'info': '#17a2b8'
        };
        return colors[type] || '#17a2b8';
    }

    /**
     * Initialize tooltips
     */
    function initTooltips() {
        $('[data-tooltip]').each(function() {
            var $element = $(this);
            var tooltip = $element.data('tooltip');
            
            $element.on('mouseenter', function() {
                var $tooltip = $('<div class="wfn-tooltip">' + tooltip + '</div>');
                $('body').append($tooltip);
                
                var offset = $element.offset();
                $tooltip.css({
                    position: 'absolute',
                    top: offset.top - $tooltip.outerHeight() - 10,
                    left: offset.left + ($element.outerWidth() / 2) - ($tooltip.outerWidth() / 2),
                    background: '#333',
                    color: 'white',
                    padding: '8px 12px',
                    borderRadius: '4px',
                    fontSize: '13px',
                    zIndex: 10000,
                    whiteSpace: 'nowrap'
                });
            });
            
            $element.on('mouseleave', function() {
                $('.wfn-tooltip').remove();
            });
        });
    }

    /**
     * Handle settings section collapsing
     */
    $('.wfn-module-control h3').on('click', function() {
        var $section = $(this).closest('.wfn-module-control');
        var $content = $section.find('.form-table');
        
        $content.slideToggle(300);
        $section.toggleClass('collapsed');
    });

    /**
     * Initialize range sliders
     */
    function initRangeSliders() {
        $('.wfn-range-control input[type="range"]').on('input', function() {
            var $slider = $(this);
            var value = $slider.val();
            var $display = $slider.siblings('.wfn-range-value');
            
            $display.text(value);
        });
    }

    /**
     * AJAX save functionality for module settings
     */
    function initAjaxSave() {
        $('.wfn-ajax-save').on('click', function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var $form = $button.closest('form');
            var formData = $form.serialize();
            
            $button.addClass('loading').prop('disabled', true);
            
            $.ajax({
                url: wfnAdmin.ajax_url,
                type: 'POST',
                data: formData + '&action=wfn_save_settings&nonce=' + wfnAdmin.nonce,
                success: function(response) {
                    if (response.success) {
                        showNotification(wfnAdmin.strings.saved, 'success');
                    } else {
                        showNotification(wfnAdmin.strings.error, 'error');
                    }
                },
                error: function() {
                    showNotification(wfnAdmin.strings.error, 'error');
                },
                complete: function() {
                    $button.removeClass('loading').prop('disabled', false);
                }
            });
        });
    }

    // Initialize additional features
    initRangeSliders();
    initAjaxSave();

})(jQuery); 