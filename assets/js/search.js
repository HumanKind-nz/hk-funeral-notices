/**
 * Funeral Notices Search JavaScript
 * Handles search form interactions and AJAX requests
 */

(function($) {
    'use strict';

    // Search functionality
    const WFNSearch = {
        
        init: function() {
            this.bindEvents();
            this.initDatePickers();
        },

        bindEvents: function() {
            // Handle form submission
            $(document).on('submit', '.wfn-search-form', this.handleFormSubmit.bind(this));
            
            // Handle quick filter clicks
            $(document).on('click', '.wfn-quick-filter', this.handleQuickFilter.bind(this));
            
            // Handle real-time search (debounced)
            $(document).on('input', '.wfn-search-form input[name="wfn_search"]', 
                this.debounce(this.handleRealTimeSearch.bind(this), 500)
            );
            
            // Handle filter changes
            $(document).on('change', '.wfn-search-form select, .wfn-search-form input[type="date"]', 
                this.handleFilterChange.bind(this));
        },

        initDatePickers: function() {
            // Set max date to today for "to" date picker when "from" is selected
            $(document).on('change', 'input[name="wfn_date_from"]', function() {
                const fromDate = $(this).val();
                const toDateInput = $('input[name="wfn_date_to"]');
                
                if (fromDate) {
                    toDateInput.attr('min', fromDate);
                }
            });

            // Set min date based on "from" date for "to" date picker
            $(document).on('change', 'input[name="wfn_date_to"]', function() {
                const toDate = $(this).val();
                const fromDateInput = $('input[name="wfn_date_from"]');
                
                if (toDate) {
                    fromDateInput.attr('max', toDate);
                }
            });
        },

        handleFormSubmit: function(e) {
            const $form = $(e.target);
            
            // Allow normal form submission for shortcode forms
            if ($form.hasClass('wfn-shortcode-form')) {
                return true; // Let the form submit normally
            }
            
            e.preventDefault();
            const formData = this.getFormData($form);
            
            // For archive pages, update URL and reload
            if (this.isArchivePage()) {
                this.updateURLAndReload(formData);
            } else {
                // For other forms, perform AJAX search
                this.performAjaxSearch(formData, $form);
            }
        },

        handleQuickFilter: function(e) {
            e.preventDefault();
            
            const $link = $(e.target);
            const href = $link.attr('href');
            
            if (this.isArchivePage()) {
                window.location.href = href;
            } else {
                // Extract parameters from href and perform AJAX search
                const url = new URL(href, window.location.origin);
                const params = Object.fromEntries(url.searchParams);
                this.performAjaxSearch(params, $link.closest('.wfn-search-form'));
            }
        },

        handleRealTimeSearch: function(e) {
            const $input = $(e.target);
            const $form = $input.closest('.wfn-search-form');
            
            // Skip AJAX for shortcode forms - let them submit normally
            if ($form.hasClass('wfn-shortcode-form')) {
                return;
            }
            
            // Only do real-time search for archive pages
            if (!this.isArchivePage() && $input.val().length >= 3) {
                const formData = this.getFormData($form);
                this.performAjaxSearch(formData, $form);
            }
        },

        handleFilterChange: function(e) {
            const $input = $(e.target);
            const $form = $input.closest('.wfn-search-form');
            
            // Skip AJAX for shortcode forms - let them submit normally
            if ($form.hasClass('wfn-shortcode-form')) {
                return;
            }
            
            // Auto-submit for archive page forms only
            if (!this.isArchivePage()) {
                const formData = this.getFormData($form);
                this.performAjaxSearch(formData, $form);
            }
        },

        getFormData: function($form) {
            const formData = {};
            
            $form.find('input, select').each(function() {
                const $field = $(this);
                const name = $field.attr('name');
                const value = $field.val();
                
                if (name && value) {
                    formData[name] = value;
                }
            });
            
            return formData;
        },

        isArchivePage: function() {
            return $('body').hasClass('post-type-archive-funeral-notice');
        },

        updateURLAndReload: function(formData) {
            const url = new URL(window.location);
            
            // Clear existing search parameters
            url.searchParams.delete('wfn_search');
            url.searchParams.delete('wfn_location');
            url.searchParams.delete('wfn_date_from');
            url.searchParams.delete('wfn_date_to');
            url.searchParams.delete('paged');
            
            // Add new parameters
            Object.keys(formData).forEach(key => {
                if (formData[key]) {
                    url.searchParams.set(key, formData[key]);
                }
            });
            
            window.location.href = url.toString();
        },

        performAjaxSearch: function(searchData, $form) {
            const $container = $form.closest('.wfn-shortcode-search-form').siblings('.wfn-modern-grid, .wfn-elegant-grid, .firehawk-crm').first();
            
            if (!$container.length) {
                console.warn('Could not find results container for AJAX search');
                return;
            }

            // Show loading state
            $container.addClass('wfn-search-loading');
            
            // Prepare AJAX data
            const ajaxData = {
                action: 'wfn_search',
                nonce: wfnSearch.nonce,
                search_term: searchData.wfn_search || '',
                location: searchData.wfn_location || '',
                date_from: searchData.wfn_date_from || '',
                date_to: searchData.wfn_date_to || ''
            };

            $.ajax({
                url: wfnSearch.ajaxUrl,
                type: 'POST',
                data: ajaxData,
                success: function(response) {
                    if (response.success) {
                        $container.html(response.data.html);
                        
                        // Update results count if element exists
                        const $resultsCount = $('.wfn-results-count');
                        if ($resultsCount.length) {
                            $resultsCount.text(response.data.found_posts + ' results found');
                        }
                    } else {
                        console.error('Search failed:', response.data);
                        $container.html('<p class="wfn-no-results">Search failed. Please try again.</p>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', error);
                    $container.html('<p class="wfn-no-results">Search failed. Please try again.</p>');
                },
                complete: function() {
                    $container.removeClass('wfn-search-loading');
                }
            });
        },

        // Utility function for debouncing
        debounce: function(func, wait) {
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

    // Initialize when document is ready
    $(document).ready(function() {
        WFNSearch.init();
    });

    // Expose to global scope for debugging
    window.WFNSearch = WFNSearch;

})(jQuery); 