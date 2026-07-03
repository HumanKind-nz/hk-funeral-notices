/**
 * Load More Functionality
 * Handles AJAX loading of additional funeral notices
 *
 * @since 2.4.0
 */
(function($) {
    'use strict';

    // Load More Handler
    var LoadMoreHandler = {
        button: null,
        container: null,
        offset: 0,
        postsPerLoad: 8,
        layout: 'modern',
        filters: {},
        isLoading: false,

        /**
         * Initialize
         */
        init: function() {
            this.button = $('.hkfn-load-more-button');

            if (!this.button.length) {
                return;
            }

            // Get initial data from button
            this.offset = parseInt(this.button.data('offset')) || 0;
            this.postsPerLoad = parseInt(this.button.data('per-load')) || 8;
            this.layout = this.button.data('layout') || 'modern';
            this.filters = this.button.data('filters') || {};

            // Find the grid container
            this.container = this.findGridContainer();

            // Bind click event
            var self = this;
            this.button.on('click', function(e) {
                e.preventDefault();
                self.loadMore();
            });
        },

        /**
         * Find the appropriate grid container based on layout
         */
        findGridContainer: function() {
            var containerMap = {
                'modern': '.hkfn-enhancement-modern-grid, .hkfn-modern-grid',
                'elegant': '.hkfn-enhancement-elegant-grid, .hkfn-elegant-grid',
                'minimal': '.hkfn-enhancement-minimal-grid, .hkfn-minimal-grid',
                'firehawk': '.firehawk-crm-large-grid-view',
                'current': '.hkfn-current-grid'
            };

            var selector = containerMap[this.layout] || containerMap['modern'];
            return $(selector);
        },

        /**
         * Load more posts via AJAX
         */
        loadMore: function() {
            if (this.isLoading) {
                return;
            }

            this.isLoading = true;
            this.setLoadingState(true);

            var self = this;

            $.ajax({
                url: hkfnLoadMore.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'hkfn_load_more',
                    nonce: hkfnLoadMore.nonce,
                    offset: self.offset,
                    posts_per_load: self.postsPerLoad,
                    layout: self.layout,
                    filters: self.filters
                },
                success: function(response) {
                    if (response.success && response.data.html) {
                        // Create elements from HTML and hide them initially
                        var newItems = $(response.data.html).hide();

                        // Append to container
                        self.container.append(newItems);

                        // Fade in with staggered animation
                        newItems.each(function(index) {
                            var item = $(this);
                            setTimeout(function() {
                                item.fadeIn(400);
                            }, index * 80);
                        });

                        // Update offset
                        self.offset = response.data.offset;
                        self.button.data('offset', self.offset);

                        // Hide button if no more posts
                        if (!response.data.has_more) {
                            self.button.fadeOut();
                        }
                    }

                    self.isLoading = false;
                    self.setLoadingState(false);
                },
                error: function(xhr, status, error) {
                    console.error('Load More Error:', error);
                    self.isLoading = false;
                    self.setLoadingState(false);

                    // Show error message
                    self.showError('Failed to load more. Please try again.');
                }
            });
        },

        /**
         * Set loading state on button
         */
        setLoadingState: function(loading) {
            if (loading) {
                this.button.prop('disabled', true);
                this.button.data('original-text', this.button.html());
                this.button.html('<span class="hkfn-spinner"></span> Loading...');
                this.button.addClass('hkfn-loading');
            } else {
                this.button.prop('disabled', false);
                this.button.html(this.button.data('original-text') || 'Load More');
                this.button.removeClass('hkfn-loading');
            }
        },

        /**
         * Show error message
         */
        showError: function(message) {
            var self = this;
            // Create error notice
            var errorNotice = $('<div class="hkfn-error-notice">')
                .text(message)
                .insertBefore(this.button);

            // Auto-hide after 5 seconds
            setTimeout(function() {
                errorNotice.fadeOut(function() {
                    errorNotice.remove();
                });
            }, 5000);
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        LoadMoreHandler.init();
    });

})(jQuery);
