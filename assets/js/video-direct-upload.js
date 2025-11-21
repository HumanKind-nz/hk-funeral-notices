/**
 * Direct-to-Bunny Video Upload - Production Version
 *
 * Handles direct video uploads from funeral post edit pages.
 * Integrated with ACF fields for seamless user experience.
 *
 * @since 2.4.0
 */

(function($) {
    'use strict';

    /**
     * Production Video Uploader Class
     */
    class WFNVideoUploader {
        constructor(file, postId, options = {}) {
            this.file = file;
            this.postId = postId;
            this.onProgress = options.onProgress || (() => {});
            this.onComplete = options.onComplete || (() => {});
            this.onError = options.onError || (() => {});

            this.uploadSession = null;
            this.uploadedBytes = 0;
            this.startTime = null;
            this.xhr = null;
            this.aborted = false;
        }

        /**
         * Start the upload process
         */
        async start() {
            try {
                this.startTime = Date.now();
                this.aborted = false;

                // Step 1: Initialize upload session with WordPress
                this.uploadSession = await this.initUploadSession();

                // Step 2: Upload file directly to Bunny CDN
                await this.uploadToBunny();

                // Step 3: Notify WordPress of completion
                await this.notifyWordPress();

                this.onComplete({
                    success: true,
                    videoId: this.uploadSession.video_id,
                    message: 'Video uploaded successfully'
                });

            } catch (error) {
                this.onError({
                    success: false,
                    message: error.message || 'Upload failed',
                    error: error
                });
            }
        }

        /**
         * Initialize upload session with WordPress
         */
        async initUploadSession() {
            const response = await fetch(wfnVideoUpload.restUrl + 'wfn/v1/video/init-upload', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': wfnVideoUpload.nonce
                },
                body: JSON.stringify({
                    post_id: this.postId,
                    filename: this.file.name,
                    filesize: this.file.size
                })
            });

            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.message || 'Failed to initialize upload session');
            }

            return await response.json();
        }

        /**
         * Upload file directly to Bunny CDN
         */
        async uploadToBunny() {
            return new Promise((resolve, reject) => {
                const xhr = new XMLHttpRequest();

                // Track upload progress
                xhr.upload.addEventListener('progress', (e) => {
                    if (e.lengthComputable) {
                        this.uploadedBytes = e.loaded;
                        const progress = this.calculateProgress(e.loaded, e.total);
                        this.onProgress(progress);
                    }
                });

                // Handle completion
                xhr.addEventListener('load', () => {
                    if (xhr.status >= 200 && xhr.status < 300) {
                        resolve();
                    } else {
                        reject(new Error(`Upload failed with status ${xhr.status}`));
                    }
                });

                // Handle errors
                xhr.addEventListener('error', () => {
                    reject(new Error('Network error during upload'));
                });

                xhr.addEventListener('abort', () => {
                    reject(new Error('Upload cancelled'));
                });

                // Open connection to Bunny CDN
                xhr.open('PUT', this.uploadSession.upload_url);

                // Set Bunny authentication header
                xhr.setRequestHeader('AccessKey', this.uploadSession.api_key);
                xhr.setRequestHeader('Content-Type', 'application/octet-stream');

                // Send file
                xhr.send(this.file);

                // Store XHR for potential abort
                this.xhr = xhr;
            });
        }

        /**
         * Notify WordPress that upload is complete
         */
        async notifyWordPress() {
            const response = await fetch(wfnVideoUpload.restUrl + 'wfn/v1/video/upload-complete', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': wfnVideoUpload.nonce
                },
                body: JSON.stringify({
                    post_id: this.postId,
                    video_id: this.uploadSession.video_id
                })
            });

            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.message || 'Failed to notify WordPress');
            }

            return await response.json();
        }

        /**
         * Calculate upload progress statistics
         */
        calculateProgress(loaded, total) {
            const percentage = (loaded / total) * 100;
            const elapsedSeconds = (Date.now() - this.startTime) / 1000;
            const speed = loaded / elapsedSeconds; // bytes per second
            const remainingBytes = total - loaded;
            const etaSeconds = remainingBytes / speed;

            return {
                percentage: Math.round(percentage * 100) / 100,
                loaded: loaded,
                total: total,
                speed: speed,
                speedMBps: (speed / 1024 / 1024).toFixed(2),
                eta: etaSeconds,
                etaFormatted: this.formatTime(etaSeconds),
                loadedFormatted: this.formatBytes(loaded),
                totalFormatted: this.formatBytes(total)
            };
        }

        /**
         * Format seconds into human-readable time
         */
        formatTime(seconds) {
            if (!isFinite(seconds) || seconds < 0) {
                return 'Calculating...';
            }

            const minutes = Math.floor(seconds / 60);
            const remainingSeconds = Math.floor(seconds % 60);

            if (minutes > 0) {
                return `${minutes}m ${remainingSeconds}s`;
            } else {
                return `${remainingSeconds}s`;
            }
        }

        /**
         * Format bytes into human-readable size
         */
        formatBytes(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        }

        /**
         * Abort the upload
         */
        abort() {
            this.aborted = true;
            if (this.xhr) {
                this.xhr.abort();
            }
        }
    }

    /**
     * Initialize video upload for ACF field
     */
    function initVideoUpload() {
        // Wait for ACF to be ready
        if (typeof acf === 'undefined') {
            setTimeout(initVideoUpload, 500);
            return;
        }

        // Get current post ID
        const postId = wfnVideoUpload.postId || $('#post_ID').val();

        if (!postId) {
            console.error('WFN Video Upload: Post ID not found');
            return;
        }

        // Find the video upload ACF field
        const $videoField = $('.acf-field[data-name="video_slideshow"]');

        if (!$videoField.length) {
            return; // Field not on this page
        }

        // Check if already initialized
        if ($videoField.hasClass('wfn-direct-upload-initialized')) {
            return;
        }

        $videoField.addClass('wfn-direct-upload-initialized');

        // Hide default ACF file uploader
        $videoField.find('.acf-file-uploader').hide();

        // Inject custom upload UI
        const uploadUI = `
            <div class="wfn-video-upload">
                <div class="wfn-upload-dropzone">
                    <input type="file" id="wfn-video-file-input" accept="video/mp4,video/mov,video/avi,video/webm" style="display: none;">
                    <div class="wfn-dropzone-content">
                        <span class="dashicons dashicons-video-alt2"></span>
                        <p><strong>Choose a video file or drag it here</strong></p>
                        <p class="description">Maximum file size: 500MB. Supported formats: MP4, MOV, AVI, WEBM</p>
                        <button type="button" class="button button-primary wfn-select-file">Select Video File</button>
                    </div>
                </div>

                <div class="wfn-upload-progress" style="display: none;">
                    <h4>Uploading Memorial Video</h4>
                    <div class="wfn-progress-bar-container">
                        <div class="wfn-progress-bar">
                            <div class="wfn-progress-fill" style="width: 0%;"></div>
                        </div>
                        <span class="wfn-progress-text">0%</span>
                    </div>
                    <div class="wfn-upload-stats">
                        <div class="wfn-stat">
                            <span class="label">Speed:</span>
                            <span class="value wfn-upload-speed">0 MB/s</span>
                        </div>
                        <div class="wfn-stat">
                            <span class="label">Time Remaining:</span>
                            <span class="value wfn-upload-eta">Calculating...</span>
                        </div>
                        <div class="wfn-stat">
                            <span class="label">Uploaded:</span>
                            <span class="value wfn-upload-size">0 MB / 0 MB</span>
                        </div>
                    </div>
                    <button type="button" class="button wfn-cancel-upload">Cancel Upload</button>
                </div>

                <div class="wfn-upload-complete" style="display: none;">
                    <div class="wfn-success-message">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <h4>Video Uploaded Successfully!</h4>
                        <p class="wfn-video-id"></p>
                        <p class="wfn-license-notice" style="display: none; color: #d63638; margin-top: 8px;">
                            <strong>Note:</strong> A valid premium license is required to display this video on the front end. <a href="#" class="wfn-license-link">Manage License</a>
                        </p>
                        <div class="wfn-video-actions" style="margin-top: 16px;">
                            <button type="button" class="button button-link-delete wfn-delete-video-btn">
                                <span class="dashicons dashicons-trash"></span> Remove Video
                            </button>
                        </div>
                    </div>
                </div>

                <div class="wfn-upload-error" style="display: none;">
                    <div class="wfn-error-message">
                        <span class="dashicons dashicons-warning"></span>
                        <h4>Upload Failed</h4>
                        <p class="wfn-error-text"></p>
                        <button type="button" class="button wfn-try-again">Try Again</button>
                    </div>
                </div>
            </div>
        `;

        $videoField.find('.acf-input').append(uploadUI);

        // Check if post already has a video
        checkExistingVideo(postId);

        // Upload state
        let currentUploader = null;

        // Button click handler
        $videoField.on('click', '.wfn-select-file', function() {
            $('#wfn-video-file-input').click();
        });

        // File selection handler
        $videoField.on('change', '#wfn-video-file-input', function() {
            const file = this.files[0];
            if (file) {
                startUpload(file);
            }
        });

        // Cancel upload handler
        $videoField.on('click', '.wfn-cancel-upload', function() {
            if (currentUploader) {
                currentUploader.abort();
            }
        });

        // Try again handler
        $videoField.on('click', '.wfn-try-again', function() {
            resetUI();
        });

        /**
         * Start video upload
         */
        function startUpload(file) {
            // Validate file size
            const maxSize = 524288000; // 500MB
            if (file.size > maxSize) {
                showError('File size exceeds 500MB maximum. Please choose a smaller file.');
                return;
            }

            // Validate file type
            const allowedTypes = ['video/mp4', 'video/quicktime', 'video/x-msvideo', 'video/webm'];
            if (!allowedTypes.includes(file.type)) {
                showError('Invalid file type. Please upload MP4, MOV, AVI, or WEBM format.');
                return;
            }

            // Hide dropzone, show progress
            $videoField.find('.wfn-upload-dropzone').hide();
            $videoField.find('.wfn-upload-progress').show();

            // Create uploader
            currentUploader = new WFNVideoUploader(file, postId, {
                onProgress: updateProgress,
                onComplete: handleComplete,
                onError: handleError
            });

            // Start upload
            currentUploader.start();
        }

        /**
         * Update progress UI
         */
        function updateProgress(progress) {
            $videoField.find('.wfn-progress-fill').css('width', progress.percentage + '%');
            $videoField.find('.wfn-progress-text').text(progress.percentage.toFixed(1) + '%');
            $videoField.find('.wfn-upload-speed').text(progress.speedMBps + ' MB/s');
            $videoField.find('.wfn-upload-eta').text(progress.etaFormatted);
            $videoField.find('.wfn-upload-size').text(progress.loadedFormatted + ' / ' + progress.totalFormatted);
        }

        /**
         * Handle upload completion
         */
        function handleComplete(result) {
            $videoField.find('.wfn-upload-progress').hide();
            $videoField.find('.wfn-upload-complete').show();
            $videoField.find('.wfn-video-id').text('Video ID: ' + result.videoId);

            currentUploader = null;

            // Show success notice
            showNotice('Video uploaded successfully! It will be available on the funeral notice shortly.', 'success');
        }

        /**
         * Handle upload error
         */
        function handleError(error) {
            showError(error.message);
            currentUploader = null;
        }

        /**
         * Show error message
         */
        function showError(message) {
            $videoField.find('.wfn-upload-dropzone').hide();
            $videoField.find('.wfn-upload-progress').hide();
            $videoField.find('.wfn-upload-error').show();
            $videoField.find('.wfn-error-text').text(message);
        }

        /**
         * Reset UI to initial state
         */
        function resetUI() {
            $videoField.find('.wfn-upload-error').hide();
            $videoField.find('.wfn-upload-complete').hide();
            $videoField.find('.wfn-upload-progress').hide();
            $videoField.find('.wfn-upload-dropzone').show();
            $('#wfn-video-file-input').val('');
        }

        /**
         * Show WordPress admin notice
         */
        function showNotice(message, type = 'info') {
            const noticeClass = 'notice notice-' + type + ' is-dismissible';
            const notice = $('<div class="' + noticeClass + '"><p>' + message + '</p></div>');
            $('.wrap h1').after(notice);
        }

        /**
         * Check if post already has an uploaded video
         */
        async function checkExistingVideo(postId) {
            try {
                console.log('WFN: Checking for existing video...', {
                    postId: postId,
                    hasLicense: wfnVideoUpload.hasLicense,
                    restUrl: wfnVideoUpload.restUrl
                });

                const response = await fetch(wfnVideoUpload.restUrl + 'wfn/v1/video/upload-status/' + postId, {
                    method: 'GET',
                    headers: {
                        'X-WP-Nonce': wfnVideoUpload.nonce
                    }
                });

                console.log('WFN: Response status:', response.status);

                if (!response.ok) {
                    console.log('WFN: No existing video or error - showing default UI');
                    return; // No existing video or error - show default UI
                }

                const data = await response.json();
                console.log('WFN: Video data:', data);

                // If video exists and is ready, show completed state
                if (data.video_id) {
                    console.log('WFN: Video found, showing complete state');
                    $videoField.find('.wfn-upload-dropzone').hide();
                    $videoField.find('.wfn-upload-complete').show();
                    $videoField.find('.wfn-video-id').text('Video ID: ' + data.video_id);

                    // Show license notice if no license (check for empty string or false)
                    const hasLicense = wfnVideoUpload.hasLicense === '1' || wfnVideoUpload.hasLicense === true;

                    if (!hasLicense) {
                        console.log('WFN: No license - showing notice');
                        $videoField.find('.wfn-license-notice').show();
                        $videoField.find('.wfn-license-link').attr('href', wfnVideoUpload.licenseUrl);
                    } else {
                        console.log('WFN: Has license - hiding notice');
                        $videoField.find('.wfn-license-notice').hide();
                    }

                    // Add option to upload a different video (only if licensed)
                    if (hasLicense && !$videoField.find('.wfn-upload-another').length) {
                        console.log('WFN: Adding upload another button');
                        $videoField.find('.wfn-upload-complete').append(
                            '<button type="button" class="button wfn-upload-another">Upload Different Video</button>'
                        );
                    } else {
                        console.log('WFN: Not adding upload button (hasLicense:', hasLicense, ')');
                    }
                }

                // Handle upload another button
                $videoField.on('click', '.wfn-upload-another', function() {
                    if (confirm('Are you sure you want to replace the current video? This action cannot be undone.')) {
                        resetUI();
                    }
                });

            } catch (error) {
                console.error('WFN Error checking existing video:', error);
                // Show default UI on error
            }
        }

        /**
         * Handle delete video button
         */
        $videoField.on('click', '.wfn-delete-video-btn', async function() {
            const $btn = $(this);

            if (!confirm('Are you sure you want to remove this video?\n\nThis will:\n1. Remove the video from this funeral notice\n2. Delete the video from the hosting service\n\nThis action cannot be undone.')) {
                return;
            }

            // Disable button and show loading state
            $btn.prop('disabled', true).html('<span class="spinner is-active" style="float: none; margin: 0;"></span> Removing...');

            try {
                // Call REST API to delete video
                const response = await fetch(wfnVideoUpload.restUrl + 'wfn/v1/video/delete/' + postId, {
                    method: 'DELETE',
                    headers: {
                        'X-WP-Nonce': wfnVideoUpload.nonce
                    }
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    // Show success message
                    showNotice('Video removed successfully', 'success');

                    // Reset UI to initial state
                    resetUI();
                } else {
                    throw new Error(result.message || 'Failed to delete video');
                }

            } catch (error) {
                console.error('WFN Delete Error:', error);
                showNotice('Error removing video: ' + error.message, 'error');

                // Re-enable button
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-trash"></span> Remove Video');
            }
        });
    }

    // Initialize when DOM is ready
    $(document).ready(function() {
        initVideoUpload();
    });

    // Expose uploader class globally for extensions
    window.WFNVideoUploader = WFNVideoUploader;

})(jQuery);
