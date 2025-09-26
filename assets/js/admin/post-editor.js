/**
 * Post Editor JavaScript
 * Handles additional functionality for funeral notice post editor including video upload progress tracking
 * @since 2.1.4
 */

(function($) {
    'use strict';

    /**
     * Video Upload Progress Tracker for Admin Post Editor
     */
    class WFNVideoUploadTracker {
        constructor() {
            this.postId = this.getPostId();
            this.progressContainer = null;
            this.statusInterval = null;
            this.lastStatus = null;

            this.init();
        }

        init() {
            if (!this.postId || $('#post-type-funeral-notice').length === 0) {
                return;
            }

            this.setupProgressContainer();
            this.bindEvents();
            this.startTracking();
        }

        /**
         * Get the current post ID
         */
        getPostId() {
            return $('#post_ID').val() || null;
        }

        /**
         * Setup progress container in the ACF media group
         */
        setupProgressContainer() {
            const $mediaGroup = $('.acf-field[data-name="wfn_media_group"]');
            const $videoField = $('.acf-field[data-name="video_slideshow"]');

            if ($videoField.length && !$('.wfn-video-upload-progress').length) {
                this.progressContainer = $('<div class="wfn-video-upload-progress" style="display: none;"></div>');
                $videoField.after(this.progressContainer);
            }
        }

        /**
         * Bind event listeners
         */
        bindEvents() {
            // Listen for file uploads via ACF
            $(document).on('change', 'input[data-name="video_slideshow"]', this.handleFileSelected.bind(this));

            // Listen for ACF form save
            $(document).on('click', '#publish, #save-post', this.handlePostSave.bind(this));

            // Manual refresh button
            $(document).on('click', '.wfn-refresh-video-status', this.checkVideoStatus.bind(this));

            // Retry upload button
            $(document).on('click', '.wfn-retry-upload', this.retryUpload.bind(this));
        }

        /**
         * Handle file selection
         */
        handleFileSelected(event) {
            const file = event.target.files[0];
            if (file) {
                this.showPreUploadValidation(file);
            }
        }

        /**
         * Show pre-upload validation
         */
        showPreUploadValidation(file) {
            if (!this.progressContainer) return;

            const maxSize = 100 * 1024 * 1024; // 100MB
            const allowedTypes = ['video/mp4', 'video/mov', 'video/avi', 'video/wmv'];

            let message = '';
            let isValid = true;

            if (file.size > maxSize) {
                message = `File too large (${this.formatFileSize(file.size)}). Maximum size is 100MB.`;
                isValid = false;
            } else if (!allowedTypes.includes(file.type)) {
                message = `Invalid file type (${file.type}). Only MP4, MOV, AVI, and WMV files are allowed.`;
                isValid = false;
            } else {
                message = `Video file selected: ${file.name} (${this.formatFileSize(file.size)})`;
                isValid = true;
            }

            this.showMessage(message, isValid ? 'info' : 'error');
        }

        /**
         * Handle post save
         */
        handlePostSave(event) {
            // Check if there's a video file selected
            const $videoInput = $('input[data-name="video_slideshow"]');
            if ($videoInput.length && $videoInput[0].files && $videoInput[0].files.length > 0) {
                this.showMessage('Saving post and preparing video upload...', 'info');
                this.startIntensiveTracking();
            }
        }

        /**
         * Start video upload tracking
         */
        startTracking() {
            // Initial status check
            this.checkVideoStatus();

            // Set up periodic checks (every 10 seconds)
            if (this.statusInterval) {
                clearInterval(this.statusInterval);
            }

            this.statusInterval = setInterval(() => {
                this.checkVideoStatus();
            }, 10000);
        }

        /**
         * Start intensive tracking (every 2 seconds during active upload)
         */
        startIntensiveTracking() {
            if (this.statusInterval) {
                clearInterval(this.statusInterval);
            }

            this.statusInterval = setInterval(() => {
                this.checkVideoStatus();
            }, 2000);
        }

        /**
         * Check video upload status via AJAX
         */
        checkVideoStatus() {
            if (!this.postId) return;

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'wfn_video_upload_status',
                    post_id: this.postId,
                    nonce: wfnVideo?.nonces?.status || ''
                },
                success: (response) => {
                    if (response.success && response.data) {
                        this.updateProgressDisplay(response.data);
                    }
                },
                error: (xhr, status, error) => {
                    console.error('Video status check failed:', error);
                    this.showMessage('Unable to check video upload status', 'error');
                }
            });
        }

        /**
         * Update progress display
         */
        updateProgressDisplay(data) {
            if (!this.progressContainer) return;

            const { video_status, upload_status, video_data } = data;

            // If no video data and no upload in progress, hide progress
            if (!video_data && (!upload_status || upload_status.status === 'not_started')) {
                this.hideProgress();
                return;
            }

            this.showProgress();

            // Handle different states
            if (upload_status && upload_status.status !== 'completed') {
                this.showUploadProgress(upload_status);
            } else if (video_status === 'ready' && video_data) {
                this.showVideoReady(video_data);
                this.returnToNormalTracking();
            } else if (video_status === 'failed') {
                this.showUploadError(upload_status);
                this.returnToNormalTracking();
            } else if (video_status === 'processing') {
                this.showProcessingState(video_data);
            } else if (video_status === 'uploaded') {
                this.showUploadedState(video_data);
            }

            this.lastStatus = data;
        }

        /**
         * Show upload progress
         */
        showUploadProgress(status) {
            const progress = Math.max(0, Math.min(100, status.progress || 0));
            const message = status.message || 'Uploading video...';
            const stage = status.stage || 'upload';

            const html = `
                <div class="wfn-upload-progress-container">
                    <div class="wfn-progress-header">
                        <h4>Video Upload Progress</h4>
                        <span class="wfn-progress-stage">${this.getStageLabel(stage)}</span>
                    </div>
                    <div class="wfn-progress-bar">
                        <div class="wfn-progress-fill" style="width: ${progress}%"></div>
                        <span class="wfn-progress-text">${progress}%</span>
                    </div>
                    <p class="wfn-progress-message">${message}</p>
                    <div class="wfn-progress-actions">
                        <button type="button" class="button button-secondary wfn-refresh-video-status">
                            Refresh Status
                        </button>
                    </div>
                </div>
            `;

            this.progressContainer.html(html);
        }

        /**
         * Show video ready state
         */
        showVideoReady(videoData) {
            const html = `
                <div class="wfn-upload-success">
                    <div class="wfn-success-header">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <h4>Video Upload Complete!</h4>
                    </div>
                    <div class="wfn-video-details">
                        <p><strong>Duration:</strong> ${this.formatDuration(videoData.duration)}</p>
                        <p><strong>Size:</strong> ${this.formatFileSize(videoData.file_size)}</p>
                        <p><strong>Resolution:</strong> ${videoData.width}x${videoData.height}</p>
                    </div>
                    <div class="wfn-video-actions">
                        <a href="${videoData.stream_url}" target="_blank" class="button button-secondary">
                            Preview Video
                        </a>
                        <button type="button" class="button button-secondary wfn-refresh-video-status">
                            Refresh
                        </button>
                    </div>
                    <p class="wfn-success-note">
                        The memorial video is now ready and will be displayed to visitors with a "View Slideshow" button.
                    </p>
                </div>
            `;

            this.progressContainer.html(html);
        }

        /**
         * Show processing state
         */
        showProcessingState(videoData) {
            const html = `
                <div class="wfn-upload-processing">
                    <div class="wfn-processing-header">
                        <div class="wfn-spinner"></div>
                        <h4>Processing Video...</h4>
                    </div>
                    <p>Your video has been uploaded successfully and is being processed. This may take a few minutes.</p>
                    <div class="wfn-progress-actions">
                        <button type="button" class="button button-secondary wfn-refresh-video-status">
                            Check Status
                        </button>
                    </div>
                </div>
            `;

            this.progressContainer.html(html);
        }

        /**
         * Show uploaded state (before processing)
         */
        showUploadedState(videoData) {
            const html = `
                <div class="wfn-upload-uploaded">
                    <div class="wfn-uploaded-header">
                        <span class="dashicons dashicons-cloud-upload"></span>
                        <h4>Video Uploaded Successfully</h4>
                    </div>
                    <p>Your video has been uploaded and will begin processing shortly.</p>
                    <div class="wfn-progress-actions">
                        <button type="button" class="button button-secondary wfn-refresh-video-status">
                            Check Status
                        </button>
                    </div>
                </div>
            `;

            this.progressContainer.html(html);
        }

        /**
         * Show upload error
         */
        showUploadError(status) {
            const message = status?.message || 'Video upload failed';
            const canRetry = status?.retryable !== false;

            const html = `
                <div class="wfn-upload-error">
                    <div class="wfn-error-header">
                        <span class="dashicons dashicons-warning"></span>
                        <h4>Upload Error</h4>
                    </div>
                    <p class="wfn-error-message">${message}</p>
                    <div class="wfn-error-actions">
                        ${canRetry ? '<button type="button" class="button button-primary wfn-retry-upload">Retry Upload</button>' : ''}
                        <button type="button" class="button button-secondary wfn-refresh-video-status">
                            Check Status
                        </button>
                    </div>
                    <p class="wfn-error-help">
                        If the problem persists, please check that your video file is under 100MB and in MP4, MOV, AVI, or WMV format.
                    </p>
                </div>
            `;

            this.progressContainer.html(html);
        }

        /**
         * Retry upload
         */
        retryUpload() {
            if (!this.postId) return;

            this.showMessage('Retrying video upload...', 'info');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'wfn_retry_video_upload',
                    post_id: this.postId,
                    nonce: wfnVideo?.nonces?.retry || ''
                },
                success: (response) => {
                    if (response.success) {
                        this.showMessage('Upload retry initiated', 'success');
                        this.startIntensiveTracking();
                    } else {
                        this.showMessage('Failed to retry upload: ' + response.data, 'error');
                    }
                },
                error: () => {
                    this.showMessage('Network error during retry', 'error');
                }
            });
        }

        /**
         * Show progress container
         */
        showProgress() {
            if (this.progressContainer) {
                this.progressContainer.show();
            }
        }

        /**
         * Hide progress container
         */
        hideProgress() {
            if (this.progressContainer) {
                this.progressContainer.hide();
            }
        }

        /**
         * Return to normal tracking frequency
         */
        returnToNormalTracking() {
            if (this.statusInterval) {
                clearInterval(this.statusInterval);
                this.startTracking();
            }
        }

        /**
         * Show a temporary message
         */
        showMessage(message, type = 'info', duration = 5000) {
            // Remove existing messages
            $('.wfn-temp-message').remove();

            // Create new message
            const $message = $(`
                <div class="wfn-temp-message wfn-message-${type}">
                    ${message}
                </div>
            `);

            // Insert after the video field
            const $videoField = $('.acf-field[data-name="video_slideshow"]');
            if ($videoField.length) {
                $videoField.after($message);
            }

            // Auto-remove
            setTimeout(() => {
                $message.fadeOut(300, function() {
                    $(this).remove();
                });
            }, duration);
        }

        /**
         * Format file size
         */
        formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        /**
         * Format duration in seconds to MM:SS
         */
        formatDuration(seconds) {
            if (!seconds || seconds <= 0) return '0:00';
            const minutes = Math.floor(seconds / 60);
            const remainingSeconds = Math.floor(seconds % 60);
            return minutes + ':' + remainingSeconds.toString().padStart(2, '0');
        }

        /**
         * Get stage label
         */
        getStageLabel(stage) {
            const labels = {
                'validation': 'Validating File',
                'upload': 'Uploading to CDN',
                'processing': 'Processing Video',
                'thumbnail': 'Generating Thumbnails',
                'finalizing': 'Finalizing Upload'
            };
            return labels[stage] || 'Processing';
        }

        /**
         * Cleanup when leaving page
         */
        destroy() {
            if (this.statusInterval) {
                clearInterval(this.statusInterval);
            }
        }
    }

    // Initialize when document is ready
    $(document).ready(function() {
        // Initialize video upload tracker for funeral notice posts
        if ($('body').hasClass('post-type-funeral-notice') && $('#post').length) {
            window.wfnVideoTracker = new WFNVideoUploadTracker();
        }
    });

    // Cleanup on page unload
    $(window).on('beforeunload', function() {
        if (window.wfnVideoTracker) {
            window.wfnVideoTracker.destroy();
        }
    });

})(jQuery);