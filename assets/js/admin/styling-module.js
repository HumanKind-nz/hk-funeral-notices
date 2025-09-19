/**
 * WFN Styling Module Admin JavaScript
 * 
 * Handles color pickers, sliders, and preview functionality
 * 
 * @package WeaveStudios\FuneralNotices
 * @since 2.0.0
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Tab functionality
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        var targetTab = $(this).attr('href');
        
        // Remove active class from all tabs and content
        $('.nav-tab').removeClass('nav-tab-active');
        $('.tab-content').removeClass('active');
        
        // Add active class to clicked tab and corresponding content
        $(this).addClass('nav-tab-active');
        $(targetTab).addClass('active');
    });
    
    // Handle custom colors visibility
    $('input[name="wfn_module_settings[color_scheme]"]').on('change', function() {
        var $customColors = $('.wfn-custom-colors');
        if ($(this).val() === 'custom') {
            $customColors.show();
        } else {
            $customColors.hide();
        }
    });

    // Initialize color pickers with slight delay to ensure all dependencies are loaded
    function initializeColorPickers() {
        console.log('Initializing color pickers...');
        
        // Check if wp-color-picker is available
        if (typeof $.fn.wpColorPicker === 'undefined') {
            console.error('WordPress Color Picker not available. Make sure wp-color-picker script is loaded.');
            // Try again after a short delay
            setTimeout(initializeColorPickers, 500);
            return;
        }
    
    var $colorFields = $('.wp-color-picker-field');
    console.log('Found', $colorFields.length, 'color picker fields');
    
    $colorFields.each(function(index) {
        var $input = $(this);
        var defaultColor = $input.data('default-color') || $input.val();
        
        console.log('Initializing color picker', index + 1, 'with default color:', defaultColor);
        
        var colorPickerOptions = {
            defaultColor: defaultColor,
            change: function(event, ui) {
                console.log('Color changed to:', ui.color.toString());
                previewChanges();
            },
            clear: function() {
                console.log('Color cleared');
                previewChanges();
            },
            hide: true,
            palettes: [
                '#2c5282', '#667eea', '#28a745', '#dc3545',
                '#ffc107', '#17a2b8', '#6f42c1', '#e83e8c',
                '#fd7e14', '#20c997', '#8b4513', '#22543d',
                '#1a365d', '#b7791f', '#22543d', '#38a169'
            ]
        };

        try {
            $input.wpColorPicker(colorPickerOptions);
            console.log('Color picker', index + 1, 'initialized successfully');
        } catch (e) {
            console.error('Color picker initialization failed for element', index + 1, ':', $input, e);
        }
    });
    
    console.log('Color picker initialization complete.');
    }
    
    // Call initialization with slight delay
    setTimeout(initializeColorPickers, 100);
    
    // Also handle legacy color picker class for backward compatibility
    $('.color-picker').each(function() {
        var $input = $(this);
        if (!$input.hasClass('wp-color-picker-field')) {
            $input.wpColorPicker({
                defaultColor: false,
                change: function(event, ui) {
                    previewChanges();
                },
                clear: function() {
                    previewChanges();
                },
                hide: true
            });
        }
    });

    // Initialize range sliders with live preview
    $('input[type="range"]').on('input', function() {
        var $slider = $(this);
        var $valueDisplay = $slider.next('.slider-value');
        var value = $slider.val();
        
        // Update display value
        if ($valueDisplay.length) {
            var unit = $slider.hasClass('font-size-slider') || 
                      $slider.hasClass('border-radius-slider') || 
                      $slider.hasClass('card-padding-slider') || 
                      $slider.hasClass('grid-gap-slider') ? 'px' : '';
            $valueDisplay.text(value + unit);
        }
        
        // Live preview
        if (typeof previewChanges === 'function') {
            previewChanges();
        }
    });

    // Handle color scheme selection
    $('.scheme-selector').on('change', function() {
        if ($(this).is(':checked')) {
            var schemeId = $(this).val();
            applyColorScheme(schemeId);
            previewChanges();
        }
    });

    // Handle toggle switches
    $('.toggle-switch input[type="checkbox"]').on('change', function() {
        var isChecked = $(this).is(':checked');
        var $relatedControls = $(this).closest('.wfn-module-control').find('.form-table tr').not(':first');
        
        if (isChecked) {
            $relatedControls.show();
        } else {
            $relatedControls.hide();
        }
        
        previewChanges();
    });

    // Apply color scheme to form inputs
    function applyColorScheme(schemeId) {
        if (typeof wfnStyling.colorSchemes[schemeId] !== 'undefined') {
            var scheme = wfnStyling.colorSchemes[schemeId];
            
            // Update color picker values
            $('input[name*="primary_color"]').wpColorPicker('color', scheme.primary);
            $('input[name*="secondary_color"]').wpColorPicker('color', scheme.secondary);
            $('input[name*="accent_color"]').wpColorPicker('color', scheme.accent);
            $('input[name*="text_color"]').wpColorPicker('color', scheme.text);
            $('input[name*="background_color"]').wpColorPicker('color', scheme.background);
            $('input[name*="border_color"]').wpColorPicker('color', scheme.border);
        }
    }

    // Preview changes (placeholder for future live preview functionality)
    function previewChanges() {
        // This would trigger a live preview of changes
        // For now, we'll just add a subtle visual feedback
        showSaveReminder();
    }

    // Show save reminder
    function showSaveReminder() {
        var $submitButton = $('#submit');
        if ($submitButton.length && !$submitButton.hasClass('save-reminder')) {
            $submitButton.addClass('save-reminder');
            $submitButton.text($submitButton.text() + ' *');
            
            // Remove reminder after 5 seconds
            setTimeout(function() {
                $submitButton.removeClass('save-reminder');
                $submitButton.text($submitButton.text().replace(' *', ''));
            }, 5000);
        }
    }

    // Handle custom CSS textarea with basic syntax highlighting
    var $customCssTextarea = $('textarea[name*="custom_css"]');
    if ($customCssTextarea.length) {
        $customCssTextarea.on('input', function() {
            var content = $(this).val();
            var lineCount = content.split('\n').length;
            
            // Auto-resize textarea
            this.style.height = 'auto';
            this.style.height = Math.max(150, lineCount * 18) + 'px';
            
            previewChanges();
        });

        // Initial resize
        $customCssTextarea.trigger('input');
    }

    // Handle reset to defaults button
    $(document).on('click', '.reset-defaults', function(e) {
        e.preventDefault();
        
        if (confirm('Are you sure you want to reset all styling settings to defaults? This action cannot be undone.')) {
            resetToDefaults();
        }
    });

    // Reset to defaults function
    function resetToDefaults() {
        // Reset color scheme to Professional
        $('.scheme-selector[value="professional"]').prop('checked', true).trigger('change');
        
        // Reset sliders to defaults
        $('input[name*="border_radius"]').val(6).trigger('input');
        $('input[name*="card_padding"]').val(20).trigger('input');
        $('input[name*="grid_gap"]').val(20).trigger('input');
        $('input[name*="font_size_base"]').val(16).trigger('input');
        $('input[name*="line_height"]').val(1.6).trigger('input');
        
        // Reset toggles
        $('input[name*="typography_enabled"]').prop('checked', false).trigger('change');
        $('input[name*="shadow_enabled"]').prop('checked', true).trigger('change');
        
        // Reset dropdowns
        $('select[name*="primary_font"]').val('system');
        $('select[name*="heading_font"]').val('system');
        
        // Clear custom CSS
        $('textarea[name*="custom_css"]').val('').trigger('input');
        
        showSaveReminder();
    }

    // Initialize toggles state on page load
    $('.toggle-switch input[type="checkbox"]').each(function() {
        $(this).trigger('change');
    });

    // Add smooth transitions to form elements
    $('.form-table input, .form-table select, .form-table textarea').on('focus', function() {
        $(this).closest('tr').addClass('focused');
    }).on('blur', function() {
        $(this).closest('tr').removeClass('focused');
    });

    // Style enhancements
    $('<style>')
        .text('.form-table tr.focused { background-color: rgba(31, 75, 143, 0.05); transition: background-color 0.3s ease; }')
        .appendTo('head');
}); 