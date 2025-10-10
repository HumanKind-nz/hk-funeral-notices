/**
 * Memorial Video Player JavaScript
 * Frontend functionality for video modal and player
 * @since 2.1.4
 */

(function($) {
    'use strict';

    /**
     * Video Player Class
     */
    class WFNVideoPlayer {
        constructor() {
            this.init();
        }

        init() {
            this.bindEvents();
            this.setupAccessibility();
            this.preloadModals();
        }

        /**
         * Bind event listeners
         */
        bindEvents() {
            // Modal trigger buttons
            $(document).on('click', '[data-video-modal]', this.openModal.bind(this));

            // Modal close buttons and overlay
            $(document).on('click', '.wfn-video-modal-close', this.closeModal.bind(this));
            $(document).on('click', '.wfn-video-modal-overlay', this.closeModalOnOverlay.bind(this));

            // Keyboard events
            $(document).on('keydown', this.handleKeydown.bind(this));

            // Window resize handling
            $(window).on('resize', this.handleResize.bind(this));

            // Focus management
            $(document).on('focus', '.wfn-video-modal', this.manageFocus.bind(this));
        }

        /**
         * Setup accessibility features
         */
        setupAccessibility() {
            // Ensure all modals have proper ARIA attributes
            $('.wfn-video-modal').each(function() {
                const $modal = $(this);
                if (!$modal.attr('role')) {
                    $modal.attr('role', 'dialog');
                }
                if (!$modal.attr('aria-modal')) {
                    $modal.attr('aria-modal', 'true');
                }
                if (!$modal.attr('aria-hidden')) {
                    $modal.attr('aria-hidden', 'true');
                }
            });

            // Ensure buttons have proper labels
            $('[data-video-modal]').each(function() {
                const $button = $(this);
                if (!$button.attr('aria-label')) {
                    $button.attr('aria-label', 'Open memorial video slideshow');
                }
            });
        }

        /**
         * Preload modal content for better performance
         */
        preloadModals() {
            // This could be expanded to preload video thumbnails or metadata
            $('.wfn-video-modal').each(function() {
                const $modal = $(this);
                // Add any preload logic here
            });
        }

        /**
         * Open video modal
         */
        openModal(event) {
            event.preventDefault();

            const $trigger = $(event.currentTarget);
            const modalId = $trigger.data('video-modal');
            const $modal = $('#' + modalId);

            if ($modal.length === 0) {
                console.error('Video modal not found:', modalId);
                return;
            }

            // Store the trigger element for focus return
            $modal.data('trigger', $trigger);

            // Show modal
            $modal.show().attr('aria-hidden', 'false');
            $('body').addClass('wfn-video-modal-open');

            // Focus management
            this.focusModal($modal);

            // Initialize video if needed
            this.initializeVideo($modal);

            // Fire custom event
            $(document).trigger('wfn:modal:opened', { modal: $modal, trigger: $trigger });
        }

        /**
         * Close video modal
         */
        closeModal(event) {
            if (event) {
                event.preventDefault();
            }

            const $closeBtn = $(event.currentTarget);
            const modalId = $closeBtn.data('close-modal');
            const $modal = $('#' + modalId);

            this.closeModalElement($modal);
        }

        /**
         * Close modal when clicking overlay
         */
        closeModalOnOverlay(event) {
            if (event.target === event.currentTarget) {
                const $modal = $(event.currentTarget).closest('.wfn-video-modal');
                this.closeModalElement($modal);
            }
        }

        /**
         * Close modal element
         */
        closeModalElement($modal) {
            if ($modal.length === 0) {
                return;
            }

            // Hide modal
            $modal.hide().attr('aria-hidden', 'true');
            $('body').removeClass('wfn-video-modal-open');

            // Pause video if playing
            this.pauseVideo($modal);

            // Return focus to trigger
            const $trigger = $modal.data('trigger');
            if ($trigger && $trigger.length) {
                $trigger.focus();
            }

            // Fire custom event
            $(document).trigger('wfn:modal:closed', { modal: $modal });
        }

        /**
         * Handle keyboard events
         */
        handleKeydown(event) {
            // Close modal on Escape key
            if (event.key === 'Escape') {
                const $openModal = $('.wfn-video-modal[aria-hidden="false"]');
                if ($openModal.length) {
                    this.closeModalElement($openModal);
                }
            }

            // Trap focus within modal
            if (event.key === 'Tab') {
                this.trapFocus(event);
            }
        }

        /**
         * Handle window resize
         */
        handleResize() {
            const $openModal = $('.wfn-video-modal[aria-hidden="false"]');
            if ($openModal.length) {
                this.adjustModalSize($openModal);
            }
        }

        /**
         * Focus management
         */
        focusModal($modal) {
            // Find the first focusable element in the modal
            const focusableElements = $modal.find('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');

            if (focusableElements.length) {
                focusableElements.first().focus();
            } else {
                $modal.attr('tabindex', '-1').focus();
            }
        }

        /**
         * Trap focus within modal
         */
        trapFocus(event) {
            const $modal = $('.wfn-video-modal[aria-hidden="false"]');

            if ($modal.length === 0) {
                return;
            }

            const focusableElements = $modal.find('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
            const firstElement = focusableElements.first();
            const lastElement = focusableElements.last();

            if (event.shiftKey) {
                // Shift + Tab
                if (document.activeElement === firstElement[0]) {
                    event.preventDefault();
                    lastElement.focus();
                }
            } else {
                // Tab
                if (document.activeElement === lastElement[0]) {
                    event.preventDefault();
                    firstElement.focus();
                }
            }
        }

        /**
         * Manage focus within modal
         */
        manageFocus(event) {
            const $modal = $(event.currentTarget);
            if ($modal.hasClass('wfn-video-modal') && $modal.attr('aria-hidden') === 'false') {
                // Modal is open and focused
            }
        }

        /**
         * Initialize video when modal opens
         */
        initializeVideo($modal) {
            const $videoContainer = $modal.find('.wfn-video-container');
            let $iframe = $videoContainer.find('iframe');

            // If iframe doesn't exist, create it from data attributes
            if ($iframe.length === 0) {
                const videoSrc = $videoContainer.data('video-src');
                if (videoSrc) {
                    const iframeHtml = `<iframe src="${videoSrc}" width="100%" height="450" frameborder="0" allow="accelerometer; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>`;
                    $videoContainer.html(iframeHtml);
                    $iframe = $videoContainer.find('iframe');
                }
            } else {
                // Restore iframe src if it was cleared when modal was closed
                const originalSrc = $iframe.data('original-src');
                if (originalSrc && !$iframe.attr('src')) {
                    $iframe.attr('src', originalSrc);
                }
            }

            if ($iframe.length === 0) {
                console.error('Could not create video iframe');
                return;
            }

            // Add loading state
            $videoContainer.addClass('wfn-video-loading');

            // Set up a timeout to remove loading state after 3 seconds regardless
            const loadingTimeout = setTimeout(() => {
                $videoContainer.removeClass('wfn-video-loading').addClass('wfn-video-ready');
            }, 3000);

            // Handle iframe load
            $iframe.on('load', function() {
                clearTimeout(loadingTimeout);
                $videoContainer.removeClass('wfn-video-loading').addClass('wfn-video-ready');
            });

            // Handle iframe errors
            $iframe.on('error', function() {
                clearTimeout(loadingTimeout);
                $videoContainer.removeClass('wfn-video-loading').addClass('wfn-video-error');
                console.error('Failed to load video iframe');
            });

            // Set up postMessage communication if needed
            this.setupVideoMessages($iframe);
        }

        /**
         * Setup video player messages
         */
        setupVideoMessages($iframe) {
            // Listen for messages from the video player
            $(window).on('message', (event) => {
                const data = event.originalEvent.data;

                // Handle Bunny Stream player events
                if (data && typeof data === 'object') {
                    switch (data.event) {
                        case 'video-play':
                            this.onVideoPlay(data);
                            break;
                        case 'video-pause':
                            this.onVideoPause(data);
                            break;
                        case 'video-ended':
                            this.onVideoEnded(data);
                            break;
                    }
                }
            });
        }

        /**
         * Handle video play event
         */
        onVideoPlay(data) {
            $(document).trigger('wfn:video:play', data);
        }

        /**
         * Handle video pause event
         */
        onVideoPause(data) {
            $(document).trigger('wfn:video:pause', data);
        }

        /**
         * Handle video ended event
         */
        onVideoEnded(data) {
            $(document).trigger('wfn:video:ended', data);
        }

        /**
         * Stop/pause video when modal closes
         */
        pauseVideo($modal) {
            const $iframe = $modal.find('iframe');

            if ($iframe.length) {
                // For Bunny Stream, we need to reload the iframe to stop playback
                // This is the most reliable way to ensure video stops completely
                const currentSrc = $iframe.attr('src');

                if (currentSrc) {
                    // Remove and re-add the iframe to stop playback
                    $iframe.attr('src', '');

                    // Store the original src for when modal reopens
                    $iframe.data('original-src', currentSrc);

                    // Also try postMessage as backup
                    try {
                        $iframe[0].contentWindow.postMessage(JSON.stringify({
                            'event': 'command',
                            'func': 'pause',
                            'args': ''
                        }), '*');
                    } catch (e) {
                        // Silently fail if postMessage doesn't work
                        console.log('PostMessage pause failed, iframe src cleared instead');
                    }
                }
            }
        }

        /**
         * Adjust modal size based on window size
         */
        adjustModalSize($modal) {
            const windowHeight = $(window).height();
            const windowWidth = $(window).width();
            const $content = $modal.find('.wfn-video-modal-content');

            // Adjust modal size for mobile
            if (windowWidth < 768) {
                $content.css({
                    'width': '95%',
                    'max-width': '95%',
                    'margin': '2% auto'
                });
            } else {
                $content.css({
                    'width': '',
                    'max-width': '',
                    'margin': ''
                });
            }

            // Adjust for very small screens
            if (windowHeight < 600) {
                $content.css('margin-top', '1%');
            }
        }
    }

    /**
     * Upload Progress Tracker (for admin)
     */
    class WFNUploadTracker {
        constructor() {
            this.init();
        }

        init() {
            if (typeof wfnVideo === 'undefined') {
                return;
            }

            this.postId = wfnVideo.postId;
            this.videoId = wfnVideo.videoId;
            this.ajaxUrl = wfnVideo.ajaxUrl;
            this.nonces = wfnVideo.nonces;

            this.startTracking();
        }

        /**
         * Start tracking upload progress
         */
        startTracking() {
            // Check if there's an active upload
            this.checkUploadStatus();

            // Set up periodic status checks
            this.statusInterval = setInterval(() => {
                this.checkUploadStatus();
            }, 5000); // Check every 5 seconds
        }

        /**
         * Check upload status via AJAX
         */
        checkUploadStatus() {
            $.ajax({
                url: this.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wfn_video_upload_status',
                    post_id: this.postId,
                    nonce: this.nonces.status
                },
                success: (response) => {
                    if (response.success) {
                        this.updateUploadDisplay(response.data);
                    }
                },
                error: (xhr, status, error) => {
                    console.error('Upload status check failed:', error);
                }
            });
        }

        /**
         * Update upload progress display
         */
        updateUploadDisplay(data) {
            const { video_status, upload_status } = data;

            // Update progress if upload is in progress
            if (upload_status && upload_status.status !== 'completed') {
                this.showProgressBar(upload_status);
            } else if (video_status === 'ready') {
                this.showVideoReady();
                this.stopTracking();
            } else if (video_status === 'failed') {
                this.showUploadError(upload_status);
                this.stopTracking();
            }
        }

        /**
         * Show progress bar
         */
        showProgressBar(status) {
            let $progressContainer = $('.wfn-upload-progress');

            if ($progressContainer.length === 0) {
                $progressContainer = $('<div class="wfn-upload-progress"></div>');
                $('.acf-field[data-name="memorial_video"]').append($progressContainer);
            }

            const progress = status.progress || 0;
            const message = status.message || 'Processing...';

            $progressContainer.html(`
                <div class="wfn-progress-bar">
                    <div class="wfn-progress-fill" style="width: ${progress}%"></div>
                    <span class="wfn-progress-text">${progress}%</span>
                </div>
                <p class="wfn-progress-message">${message}</p>
            `);
        }

        /**
         * Show video ready state
         */
        showVideoReady() {
            $('.wfn-upload-progress').html(`
                <div class="wfn-upload-success">
                    <span class="dashicons dashicons-yes-alt"></span>
                    Memorial video is ready for viewing!
                </div>
            `);
        }

        /**
         * Show upload error
         */
        showUploadError(status) {
            const message = status?.message || 'Upload failed';

            $('.wfn-upload-progress').html(`
                <div class="wfn-upload-error">
                    <span class="dashicons dashicons-warning"></span>
                    ${message}
                </div>
            `);
        }

        /**
         * Stop tracking
         */
        stopTracking() {
            if (this.statusInterval) {
                clearInterval(this.statusInterval);
                this.statusInterval = null;
            }
        }
    }

    /**
     * Initialize when DOM is ready
     */
    $(document).ready(function() {
        // Initialize video player
        new WFNVideoPlayer();

        // Initialize upload tracker (admin only)
        if ($('body').hasClass('wp-admin')) {
            new WFNUploadTracker();
        }
    });

    // Expose classes globally for extending
    window.WFNVideoPlayer = WFNVideoPlayer;
    window.WFNUploadTracker = WFNUploadTracker;

})(jQuery);