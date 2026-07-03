/**
 * Funeral Notices Styling Admin JavaScript
 * Handles color pickers, range sliders, and reset functionality
 */

(function($) {
    'use strict';

    const HKFNStylingAdmin = {
        
        init: function() {
            this.initColorPickers();
            this.bindEvents();
        },

        initColorPickers: function() {
            // Initialize WordPress color pickers
            $('.hkfn-color-picker').wpColorPicker({
                defaultColor: false,
                hide: true,
                palettes: [
                    '#000000', '#ffffff', '#dd3333', '#dd9933', 
                    '#eeee22', '#81d742', '#1e73be', '#8224e3',
                    '#2563eb', '#6b7280', '#d4af37', '#f8f8f8'
                ],
                change: function(event, ui) {
                    // Color picker change handled automatically
                },
                clear: function() {
                    // Color picker clear handled automatically
                }
            });
        },

        bindEvents: function() {
            // Handle range input changes
            $(document).on('input change', '.hkfn-range-input', function() {
                const $input = $(this);
                const $valueSpan = $input.siblings('.range-value');
                const value = $input.val();
                
                // Determine suffix based on field name
                let suffix = '';
                const fieldName = $input.attr('name');
                if (fieldName && (fieldName.includes('radius') || fieldName.includes('padding'))) {
                    suffix = 'px';
                }
                
                $valueSpan.text(value + suffix);
            });

            // Handle reset button
            $(document).on('click', '#hkfn-reset-styles', function(e) {
                e.preventDefault();
                HKFNStylingAdmin.resetStyles();
            });

            // Handle form submission
            $(document).on('submit', 'form', function() {
                // Update hidden inputs with current values before submission
                HKFNStylingAdmin.updateHiddenInputs();
            });
        },

        updateHiddenInputs: function() {
            // Ensure all color picker values are synced to their inputs
            $('.hkfn-color-picker').each(function() {
                const $input = $(this);
                const color = $input.wpColorPicker('color');
                if (color) {
                    $input.val(color);
                }
            });
        },

        resetStyles: function() {
            if (!confirm(hkfnStyling.resetConfirm)) {
                return;
            }

            // Show loading state
            const $button = $('#hkfn-reset-styles');
            const originalText = $button.html();
            $button.html('<span class="dashicons dashicons-update spin"></span> Resetting...').prop('disabled', true);

            // Perform AJAX reset
            $.ajax({
                url: hkfnStyling.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'hkfn_reset_styles',
                    nonce: hkfnStyling.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Reset form values to defaults
                        HKFNStylingAdmin.setDefaultValues();
                        
                        // Show success message
                        HKFNStylingAdmin.showMessage(hkfnStyling.resetSuccess, 'success');
                    } else {
                        HKFNStylingAdmin.showMessage('Reset failed. Please try again.', 'error');
                    }
                },
                error: function() {
                    HKFNStylingAdmin.showMessage('Reset failed. Please try again.', 'error');
                },
                complete: function() {
                    $button.html(originalText).prop('disabled', false);
                }
            });
        },

        setDefaultValues: function() {
            const defaults = {
                // Colors
                primary: '#2563eb',
                accent: '#d4af37',
                card_bg: '#ffffff',
                card_border: '#e5e7eb',
                primary_button_bg: '#2563eb',
                primary_button_text: '#ffffff',
                primary_button_hover: '#1d4ed8',
                secondary_button_bg: '#6b7280',
                secondary_button_text: '#ffffff',
                secondary_button_hover: '#4b5563',
                
                // Styles
                border_radius: '6',
                button_padding: '12',
                card_padding: '20',
                card_shadow_intensity: '0.1',
                
                // Text
                card_link_text: 'View details'
            };

            // Update color pickers
            Object.keys(defaults).forEach(function(key) {
                const $input = $('input[name="' + key + '"]');
                if ($input.hasClass('hkfn-color-picker')) {
                    $input.wpColorPicker('color', defaults[key]);
                } else if ($input.hasClass('hkfn-range-input')) {
                    $input.val(defaults[key]);
                    // Update range value display
                    const suffix = (key.includes('radius') || key.includes('padding')) ? 'px' : '';
                    $input.siblings('.range-value').text(defaults[key] + suffix);
                } else {
                    $input.val(defaults[key]);
                }
            });
        },

        showMessage: function(message, type) {
            // Remove existing notices
            $('.notice').remove();
            
            // Create new notice
            const $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
            $('.wrap h1').after($notice);
            
            // Auto-dismiss after 3 seconds
            setTimeout(function() {
                $notice.fadeOut(function() {
                    $(this).remove();
                });
            }, 3000);
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        HKFNStylingAdmin.init();
    });

})(jQuery); 