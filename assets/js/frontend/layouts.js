/**
 * WFN Layouts - Frontend JavaScript
 * 
 * Handles layout interactions, search functionality, and responsive features
 * for FCRM-style funeral notice layouts
 * 
 * @package WeaveStudios\FuneralNotices
 * @subpackage Assets\JS\Frontend
 * @since 2.0.0
 */

(function($) {
    'use strict';

    // Global namespace
    window.WFNLayouts = window.WFNLayouts || {};

    /**
     * Main Layouts Controller
     */
    class LayoutsController {
        constructor() {
            this.settings = window.wfnLayouts || {};
            this.isLoading = false;
            this.searchTimeout = null;
            
            this.init();
        }

        init() {
            this.bindEvents();
            this.initializeSearch();
            this.initializeGrid();
            this.initializeAccessibility();
            
            // Initialize on document ready
            $(document).ready(() => {
                this.onReady();
            });
        }

        onReady() {
            this.initializeDatePicker();
            this.initializeLoadMore();
            this.enhanceCards();
        }

        bindEvents() {
            // Search form events
            $(document).on('submit', '.wfn-search-form', (e) => {
                this.handleSearchSubmit(e);
            });

            $(document).on('input', '[name="search_name"]', (e) => {
                this.handleSearchInput(e);
            });

            $(document).on('click', '.modern-clear', (e) => {
                this.handleClearField(e);
            });

            $(document).on('click', '.modern-reset', (e) => {
                this.handleResetSearch(e);
            });

            // Grid events
            $(document).on('click', '.load-more-btn', (e) => {
                this.handleLoadMore(e);
            });

            // Card events
            $(document).on('mouseenter', '.wfn-funeral-card', (e) => {
                this.handleCardHover(e, true);
            });

            $(document).on('mouseleave', '.wfn-funeral-card', (e) => {
                this.handleCardHover(e, false);
            });

            // Responsive events
            $(window).on('resize', this.debounce(() => {
                this.handleResize();
            }, 250));

            // Keyboard events
            $(document).on('keydown', (e) => {
                this.handleKeyboard(e);
            });
        }

        /**
         * Search functionality
         */
        initializeSearch() {
            const $searchForm = $('.wfn-search-form');
            if (!$searchForm.length) return;

            // Add loading indicators
            $searchForm.find('input').on('input', () => {
                this.updateClearButtons();
            });

            // Initialize search state
            this.updateClearButtons();
        }

        handleSearchSubmit(e) {
            const $form = $(e.target);
            const $grid = $('.modern-funeral-notices-grid');
            const $loading = $('.modern-loading');

            // Show loading state
            if ($grid.length && $loading.length) {
                $grid.css('opacity', '0.6');
                $loading.show();
                this.isLoading = true;
            }

            // Let form submit normally
            // Loading state will be reset on page load
        }

        handleSearchInput(e) {
            clearTimeout(this.searchTimeout);
            
            this.searchTimeout = setTimeout(() => {
                this.updateClearButtons();
                
                // Optional: Enable live search
                if (this.settings.enableLiveSearch) {
                    this.performLiveSearch();
                }
            }, 300);
        }

        handleClearField(e) {
            e.preventDefault();
            
            const $button = $(e.target);
            const targetName = $button.data('target');
            const $targetInput = $(`[name="${targetName}"]`);
            
            if ($targetInput.length) {
                $targetInput.val('');
                
                // Clear related hidden fields for date range
                if (targetName === 'search_date_range') {
                    $('[name="search_date_from"]').val('');
                    $('[name="search_date_to"]').val('');
                }
                
                $button.hide();
                
                // Optional: Auto-submit on clear
                if (this.settings.autoSubmitOnClear) {
                    $targetInput.closest('form').submit();
                }
            }
        }

        handleResetSearch(e) {
            e.preventDefault();
            
            // Clear all search fields
            const $form = $('.wfn-search-form');
            $form.find('input[type="text"], input[type="hidden"]').val('');
            
            // Redirect to clean URL
            window.location.href = window.location.pathname;
        }

        updateClearButtons() {
            $('.modern-clear').each(function() {
                const targetName = $(this).data('target');
                const $targetInput = $(`[name="${targetName}"]`);
                
                if ($targetInput.val().trim()) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        }

        /**
         * Date picker initialization
         */
        initializeDatePicker() {
            const $dateInput = $('.date-range-input');
            if (!$dateInput.length || typeof flatpickr === 'undefined') return;

            $dateInput.each(function() {
                const $input = $(this);
                
                flatpickr(this, {
                    mode: 'range',
                    dateFormat: 'Y-m-d',
                    altInput: true,
                    altFormat: 'M j, Y',
                    allowInput: false,
                    showMonths: window.innerWidth > 768 ? 2 : 1,
                    onChange: function(selectedDates, dateStr, instance) {
                        const $form = $input.closest('form');
                        const $dateFrom = $form.find('[name="search_date_from"]');
                        const $dateTo = $form.find('[name="search_date_to"]');
                        
                        if (selectedDates.length === 2) {
                            $dateFrom.val(flatpickr.formatDate(selectedDates[0], 'Y-m-d'));
                            $dateTo.val(flatpickr.formatDate(selectedDates[1], 'Y-m-d'));
                        } else if (selectedDates.length === 1) {
                            $dateFrom.val(flatpickr.formatDate(selectedDates[0], 'Y-m-d'));
                            $dateTo.val('');
                        } else {
                            $dateFrom.val('');
                            $dateTo.val('');
                        }
                        
                        // Update clear button visibility
                        setTimeout(() => {
                            this.updateClearButtons();
                        }, 10);
                    }.bind(this)
                });
            });
        }

        /**
         * Grid functionality
         */
        initializeGrid() {
            this.adjustGridLayout();
            this.lazyLoadImages();
        }

        adjustGridLayout() {
            const $grid = $('.modern-funeral-notices-grid');
            if (!$grid.length) return;

            const columns = parseInt($grid.data('columns') || 3);
            const cards = $grid.find('.wfn-funeral-card').length;
            
            // Adjust grid for fewer items
            if (cards < columns && window.innerWidth > 1024) {
                $grid.css('grid-template-columns', `repeat(${Math.min(cards, columns)}, 1fr)`);
            }
        }

        lazyLoadImages() {
            if ('IntersectionObserver' in window) {
                const imageObserver = new IntersectionObserver((entries, observer) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const $img = $(entry.target);
                            const src = $img.data('src');
                            
                            if (src) {
                                $img.attr('src', src).removeAttr('data-src');
                                $img.removeClass('lazy-loading').addClass('lazy-loaded');
                            }
                            
                            observer.unobserve(entry.target);
                        }
                    });
                });

                $('.funeral-image[data-src]').each(function() {
                    imageObserver.observe(this);
                });
            }
        }

        /**
         * Load more functionality
         */
        initializeLoadMore() {
            const $loadMore = $('.load-more-btn');
            if (!$loadMore.length) return;

            // Show load more button if appropriate
            if (this.settings.enableLoadMore) {
                $loadMore.closest('.load-more-container').show();
            }
        }

        handleLoadMore(e) {
            e.preventDefault();
            
            const $button = $(e.target);
            const page = parseInt($button.data('page') || 2);
            
            if (this.isLoading) return;
            
            this.isLoading = true;
            $button.prop('disabled', true);
            
            // Add loading spinner
            $button.find('.button-icon').html('<span class="spinner"></span>');
            
            // AJAX load more (placeholder for future implementation)
            this.loadMoreNotices(page).then((response) => {
                this.appendNotices(response.notices);
                
                if (response.hasMore) {
                    $button.data('page', page + 1);
                } else {
                    $button.closest('.load-more-container').hide();
                }
            }).catch((error) => {
                console.error('Load more failed:', error);
                // Show error message
                this.showMessage('Failed to load more notices. Please try again.', 'error');
            }).finally(() => {
                this.isLoading = false;
                $button.prop('disabled', false);
                $button.find('.button-icon').html('↓');
            });
        }

        /**
         * Card enhancements
         */
        enhanceCards() {
            this.addCardAnimations();
            this.initializeCardAccessibility();
        }

        addCardAnimations() {
            // Add staggered animation for card entrance
            $('.wfn-funeral-card').each(function(index) {
                $(this).css('animation-delay', `${index * 0.1}s`);
            });
        }

        initializeCardAccessibility() {
            $('.wfn-funeral-card').each(function() {
                const $card = $(this);
                const $link = $card.find('.funeral-name-link').first();
                
                if ($link.length) {
                    // Make entire card clickable but maintain accessibility
                    $card.attr('role', 'article');
                    $card.attr('tabindex', '0');
                    
                    $card.on('click', function(e) {
                        // Only trigger if not clicking on a button or link
                        if (!$(e.target).closest('a, button').length) {
                            $link[0].click();
                        }
                    });
                    
                    $card.on('keydown', function(e) {
                        if (e.key === 'Enter' || e.key === ' ') {
                            e.preventDefault();
                            $link[0].click();
                        }
                    });
                }
            });
        }

        handleCardHover(e, isEntering) {
            const $card = $(e.currentTarget);
            
            if (isEntering) {
                $card.addClass('is-hovered');
            } else {
                $card.removeClass('is-hovered');
            }
        }

        /**
         * Accessibility features
         */
        initializeAccessibility() {
            // Skip link for screen readers
            this.addSkipLink();
            
            // ARIA live regions for dynamic content
            this.addLiveRegions();
            
            // Focus management
            this.initializeFocusManagement();
        }

        addSkipLink() {
            const $grid = $('.modern-funeral-notices-grid');
            if ($grid.length && !$('#skip-to-notices').length) {
                $grid.before('<a href="#main-notices" class="sr-only" id="skip-to-notices">Skip to funeral notices</a>');
                $grid.attr('id', 'main-notices');
            }
        }

        addLiveRegions() {
            if (!$('#notices-status').length) {
                $('body').append('<div id="notices-status" class="sr-only" aria-live="polite"></div>');
            }
        }

        initializeFocusManagement() {
            // Return focus to search after form submission
            const $searchForm = $('.wfn-search-form');
            if ($searchForm.length) {
                const urlParams = new URLSearchParams(window.location.search);
                if (urlParams.has('search_name') || urlParams.has('search_date_from')) {
                    setTimeout(() => {
                        $searchForm.find('input:first').focus();
                    }, 100);
                }
            }
        }

        /**
         * Responsive handling
         */
        handleResize() {
            this.adjustGridLayout();
            
            // Update date picker months
            if (typeof flatpickr !== 'undefined') {
                $('.date-range-input').each(function() {
                    if (this._flatpickr) {
                        this._flatpickr.set('showMonths', window.innerWidth > 768 ? 2 : 1);
                    }
                });
            }
        }

        /**
         * Keyboard navigation
         */
        handleKeyboard(e) {
            // Escape key handling
            if (e.key === 'Escape') {
                // Clear search if focused on search form
                if ($(e.target).closest('.wfn-modern-search').length) {
                    $('.modern-reset').trigger('click');
                }
                
                // Close any open modals (future enhancement)
                $('.wfn-modal.active').removeClass('active');
            }
            
            // Arrow key navigation for cards
            if (e.key === 'ArrowDown' || e.key === 'ArrowUp' || e.key === 'ArrowLeft' || e.key === 'ArrowRight') {
                this.handleCardNavigation(e);
            }
        }

        handleCardNavigation(e) {
            const $focused = $(document.activeElement);
            if (!$focused.hasClass('wfn-funeral-card')) return;
            
            const $cards = $('.wfn-funeral-card');
            const currentIndex = $cards.index($focused);
            let newIndex = currentIndex;
            
            const columns = this.getGridColumns();
            
            switch (e.key) {
                case 'ArrowLeft':
                    newIndex = Math.max(0, currentIndex - 1);
                    break;
                case 'ArrowRight':
                    newIndex = Math.min($cards.length - 1, currentIndex + 1);
                    break;
                case 'ArrowUp':
                    newIndex = Math.max(0, currentIndex - columns);
                    break;
                case 'ArrowDown':
                    newIndex = Math.min($cards.length - 1, currentIndex + columns);
                    break;
            }
            
            if (newIndex !== currentIndex) {
                e.preventDefault();
                $cards.eq(newIndex).focus();
            }
        }

        getGridColumns() {
            const $grid = $('.modern-funeral-notices-grid');
            if (!$grid.length) return 1;
            
            const gridStyle = window.getComputedStyle($grid[0]);
            const columns = gridStyle.gridTemplateColumns.split(' ').length;
            return columns || 3;
        }

        /**
         * Utility methods
         */
        debounce(func, wait) {
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

        showMessage(message, type = 'info') {
            // Show notification (simple implementation)
            const $message = $(`
                <div class="wfn-message wfn-message-${type}">
                    ${message}
                    <button type="button" class="wfn-message-close">×</button>
                </div>
            `);
            
            $('body').append($message);
            
            // Auto-hide after 5 seconds
            setTimeout(() => {
                $message.fadeOut(() => {
                    $message.remove();
                });
            }, 5000);
            
            // Manual close
            $message.find('.wfn-message-close').on('click', () => {
                $message.fadeOut(() => {
                    $message.remove();
                });
            });
            
            // Update live region for screen readers
            $('#notices-status').text(message);
        }

        /**
         * AJAX methods (placeholder for future implementation)
         */
        loadMoreNotices(page) {
            return new Promise((resolve, reject) => {
                // Placeholder - would implement actual AJAX call
                setTimeout(() => {
                    reject(new Error('Load more not yet implemented'));
                }, 1000);
            });
        }

        appendNotices(notices) {
            // Placeholder for appending new notices to grid
            console.log('Would append notices:', notices);
        }

        performLiveSearch() {
            // Placeholder for live search functionality
            console.log('Live search triggered');
        }
    }

    // Initialize when script loads
    const layoutsController = new LayoutsController();
    
    // Expose to global scope
    window.WFNLayouts.controller = layoutsController;

})(jQuery); 