/**
 * Funeral Notice Image Crop Tool
 *
 * Provides user-controlled cropping for funeral notice featured images.
 * Grid display uses 4:3 crop, single page uses full original image.
 *
 * @since 2.4.0
 */

(function($) {
    'use strict';

    const WFN_ImageCrop = {

        /**
         * Current attachment being cropped
         */
        currentAttachment: null,

        /**
         * Crop UI elements
         */
        $cropModal: null,
        $cropPreview: null,
        $cropOverlay: null,

        /**
         * Crop coordinates
         */
        cropData: {
            src_x: 0,
            src_y: 0,
            src_w: 0,
            src_h: 0
        },

        /**
         * Current zoom level (100-300)
         */
        currentZoom: 100,

        /**
         * Preview update timeout
         */
        previewTimeout: null,

        /**
         * Initialize crop tool
         */
        init() {
            // Only run on funeral-notice post type edit screens
            if (!this.isFuneralNoticeScreen()) {
                return;
            }

            // Wait for ACF to be ready
            if (typeof acf !== 'undefined') {
                this.initACFIntegration();
            }
        },

        /**
         * Check if we're on funeral notice edit screen
         */
        isFuneralNoticeScreen() {
            const $body = $('body');
            return $body.hasClass('post-type-funeral-notice') ||
                   $body.hasClass('post-new-php');
        },

        /**
         * Initialize ACF integration
         */
        initACFIntegration() {
            const self = this;

            // Note: Media uploader default tab is handled via PHP in ImageCropHandler.php
            // Note: Intentionally NOT hooking into media frame selection
            // Let WordPress handle image selection normally
            // Users can crop by clicking the thumbnail after selection

            // Wait for page to fully load before attaching click handlers
            setTimeout(function() {
                // Use event capturing (true parameter) to catch event before WordPress
                document.addEventListener('click', function(e) {
                    // Check if clicked element is an image inside the featured image container
                    const $target = $(e.target);

                    if (!$target.is('img')) {
                        return; // Not an image
                    }

                    // Check if it's inside the featured image area
                    const $postimagediv = $target.closest('#postimagediv');
                    const $acfeField = $target.closest('[data-type="acfe_post_field"]');

                    if (!$postimagediv.length && !$acfeField.length) {
                        return; // Not in featured image area
                    }

                    // Find attachment ID using multiple methods
                    let attachmentId = null;

                    // Method 1: From #_thumbnail_id
                    attachmentId = $('#_thumbnail_id').val();

                    // Method 2: From ACFE field
                    if (!attachmentId && $acfeField.length) {
                        attachmentId = $acfeField.find('input[name*="[ID]"]').val();
                    }

                    if (attachmentId && parseInt(attachmentId) > 0) {
                        // Prevent WordPress from opening media library
                        e.preventDefault();
                        e.stopPropagation();
                        e.stopImmediatePropagation();

                        // Open crop tool
                        self.loadAttachmentData(parseInt(attachmentId));

                        return false;
                    }
                }, true); // TRUE = use capture phase (earlier than bubble phase)
            }, 1000);

            // Add manual trigger button (comparison hidden by default)
            self.addManualPreviewButton();

            // Optionally show comparison preview on page load (disabled for cleaner UX)
            // self.showInitialComparisonPreview();
        },

        /**
         * Check if field is the featured image field
         */
        isFeaturedImageField($el) {
            // ACF Pro's featured image field uses 'acfe_post_field' type
            const $field = $el.closest('[data-type="acfe_post_field"]');
            return $field.length > 0 && $field.find('[data-field_type="featured_image"]').length > 0;
        },

        /**
         * Load full attachment data from WordPress media library
         * This ensures we get the full-size image URL, not a thumbnail
         */
        loadAttachmentData(attachmentId) {
            const self = this;

            // Get the REST nonce
            const restNonce = (typeof wfnCrop !== 'undefined' && wfnCrop.restNonce)
                ? wfnCrop.restNonce
                : null;

            // Use REST API to get attachment data
            $.ajax({
                url: `/wp-json/wp/v2/media/${attachmentId}`,
                type: 'GET',
                beforeSend: function(xhr) {
                    if (restNonce) {
                        xhr.setRequestHeader('X-WP-Nonce', restNonce);
                    }
                },
                success: function(attachmentData) {
                    // Use full size URL
                    const fullAttachment = {
                        id: attachmentId,
                        url: attachmentData.source_url, // Full size image URL
                        title: attachmentData.title.rendered || 'Featured Image'
                    };

                    self.showCropTool(fullAttachment);
                },
                error: function(xhr, status, error) {
                    alert('Could not load image data. Please try again.');
                }
            });
        },

        /**
         * Show crop tool modal
         */
        showCropTool(attachment) {
            this.currentAttachment = attachment;

            // Create modal if it doesn't exist
            if (!this.$cropModal) {
                this.createCropModal();
            }

            // Reset button state to enabled
            const $applyButton = this.$cropModal.find('.wfn-crop-apply');
            $applyButton.prop('disabled', false).text('Apply Crop');

            // Load image and initialize crop area
            this.loadImage(attachment);

            // Show modal
            this.$cropModal.addClass('wfn-crop-active');
        },

        /**
         * Create crop modal HTML
         */
        createCropModal() {
            const modalHTML = `
                <div class="wfn-crop-modal">
                    <div class="wfn-crop-modal-backdrop"></div>
                    <div class="wfn-crop-modal-content">
                        <div class="wfn-crop-header">
                            <h2>Crop Image for Grid/List Display</h2>
                            <p class="wfn-crop-description">
                                This crop will be shown on grid and list pages only.
                                The full image will be shown on the funeral page.
                            </p>
                            <button class="wfn-crop-close" title="Close">&times;</button>
                        </div>

                        <div class="wfn-crop-body">
                            <div class="wfn-crop-workspace">
                                <div class="wfn-crop-image-container">
                                    <div class="wfn-crop-image-wrapper">
                                        <img class="wfn-crop-image" src="" alt="Image to crop">
                                        <div class="wfn-crop-overlay">
                                            <div class="wfn-crop-area">
                                                <div class="wfn-crop-handle wfn-crop-handle-nw"></div>
                                                <div class="wfn-crop-handle wfn-crop-handle-ne"></div>
                                                <div class="wfn-crop-handle wfn-crop-handle-sw"></div>
                                                <div class="wfn-crop-handle wfn-crop-handle-se"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="wfn-crop-zoom-controls">
                                    <label for="wfn-zoom-slider" class="wfn-zoom-label">
                                        Zoom: <span class="wfn-zoom-display">100%</span>
                                    </label>
                                    <div class="wfn-zoom-slider-container">
                                        <button type="button" class="wfn-zoom-btn wfn-zoom-out" aria-label="Zoom out" title="Zoom out (-)">−</button>
                                        <input type="range"
                                               id="wfn-zoom-slider"
                                               class="wfn-zoom-slider"
                                               min="100"
                                               max="300"
                                               step="10"
                                               value="100"
                                               aria-label="Zoom level, 100 to 300 percent">
                                        <button type="button" class="wfn-zoom-btn wfn-zoom-in" aria-label="Zoom in" title="Zoom in (+)">+</button>
                                    </div>
                                    <div class="wfn-zoom-range-labels">
                                        <span>100%</span>
                                        <span>300%</span>
                                    </div>
                                </div>
                            </div>

                            <div class="wfn-crop-preview-panel">
                                <h3>Grid/List Preview (4:3)</h3>
                                <div class="wfn-crop-preview-card">
                                    <div class="wfn-crop-preview-image">
                                        <canvas class="wfn-crop-preview-canvas"></canvas>
                                    </div>
                                    <div class="wfn-crop-preview-content">
                                        <div class="wfn-crop-preview-title">Person Name</div>
                                        <div class="wfn-crop-preview-dates">1950 - 2025</div>
                                    </div>
                                </div>
                                <p class="wfn-crop-preview-note">
                                    Preview of how the image will appear on grid and list pages.
                                </p>
                            </div>
                        </div>

                        <div class="wfn-crop-footer">
                            <button class="button wfn-crop-reset">Reset Crop & Zoom</button>
                            <div class="wfn-crop-actions">
                                <button class="button wfn-crop-cancel">Cancel</button>
                                <button class="button button-primary wfn-crop-apply">Apply Crop</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            $('body').append(modalHTML);
            this.$cropModal = $('.wfn-crop-modal');

            // Bind events
            this.bindCropEvents();
        },

        /**
         * Load image into crop tool
         */
        loadImage(attachment) {
            const imageUrl = attachment.url;
            const $img = this.$cropModal.find('.wfn-crop-image');

            $img.attr('src', imageUrl);

            // Wait for image to load
            $img.one('load', () => {
                this.initializeCropArea();
                this.updatePreview();
            });
        },

        /**
         * Initialize crop area with default 4:3 ratio
         */
        initializeCropArea() {
            const $img = this.$cropModal.find('.wfn-crop-image');
            const $cropArea = this.$cropModal.find('.wfn-crop-area');

            const imgWidth = $img.width();
            const imgHeight = $img.height();

            // Calculate 4:3 crop area centered on image
            const targetRatio = 4 / 3;
            const imageRatio = imgWidth / imgHeight;

            let cropWidth, cropHeight;

            if (imageRatio > targetRatio) {
                // Image is wider than 4:3, constrain by height
                cropHeight = imgHeight;
                cropWidth = cropHeight * targetRatio;
            } else {
                // Image is taller than 4:3, constrain by width
                cropWidth = imgWidth;
                cropHeight = cropWidth / targetRatio;
            }

            // Center the crop area
            const left = (imgWidth - cropWidth) / 2;
            const top = (imgHeight - cropHeight) / 2;

            // Position crop area
            $cropArea.css({
                left: left + 'px',
                top: top + 'px',
                width: cropWidth + 'px',
                height: cropHeight + 'px'
            });

            // Store original image dimensions for coordinate calculation
            this.originalWidth = $img[0].naturalWidth;
            this.originalHeight = $img[0].naturalHeight;
            this.displayWidth = imgWidth;
            this.displayHeight = imgHeight;

            // Calculate initial crop coordinates
            this.updateCropData();
        },

        /**
         * Bind crop tool events
         */
        bindCropEvents() {
            const self = this;

            // Close modal
            this.$cropModal.find('.wfn-crop-close, .wfn-crop-cancel, .wfn-crop-modal-backdrop').on('click', function() {
                self.closeCropModal();
            });

            // Reset crop and zoom
            this.$cropModal.find('.wfn-crop-reset').on('click', function() {
                self.resetZoom();
                self.initializeCropArea();
                self.updatePreview();
            });

            // Apply crop
            this.$cropModal.find('.wfn-crop-apply').on('click', function() {
                self.applyCrop();
            });

            // Make crop area draggable
            this.makeCropAreaDraggable();

            // Bind zoom events
            this.bindZoomEvents();

            // Bind keyboard shortcuts
            this.bindKeyboardShortcuts();

            // Bind mouse wheel zoom
            this.bindMouseWheelZoom();

            // Bind touch gestures
            this.bindTouchGestures();
        },

        /**
         * Make crop area draggable
         */
        makeCropAreaDraggable() {
            const self = this;
            const $cropArea = this.$cropModal.find('.wfn-crop-area');
            let isDragging = false;
            let startX, startY, startLeft, startTop;

            $cropArea.on('mousedown', function(e) {
                if ($(e.target).hasClass('wfn-crop-handle')) {
                    return; // Let handle events take priority
                }

                isDragging = true;
                startX = e.pageX;
                startY = e.pageY;
                startLeft = $cropArea.position().left;
                startTop = $cropArea.position().top;

                $cropArea.addClass('wfn-crop-dragging');
                e.preventDefault();
            });

            $(document).on('mousemove', function(e) {
                if (!isDragging) return;

                const deltaX = e.pageX - startX;
                const deltaY = e.pageY - startY;
                const newLeft = startLeft + deltaX;
                const newTop = startTop + deltaY;

                // Get the actual zoomed image dimensions
                const $img = self.$cropModal.find('.wfn-crop-image');
                const zoomScale = self.currentZoom / 100;

                // The image's visual dimensions after zoom transform
                const visualWidth = $img.width() * zoomScale;
                const visualHeight = $img.height() * zoomScale;

                // Calculate the offset from centering (transform scales from center)
                const offsetX = ($img.width() * (zoomScale - 1)) / 2;
                const offsetY = ($img.height() * (zoomScale - 1)) / 2;

                // Constrain to zoomed image bounds
                const minLeft = -offsetX;
                const minTop = -offsetY;
                const maxLeft = $img.width() - $cropArea.width() + offsetX;
                const maxTop = $img.height() - $cropArea.height() + offsetY;

                const constrainedLeft = Math.max(minLeft, Math.min(newLeft, maxLeft));
                const constrainedTop = Math.max(minTop, Math.min(newTop, maxTop));

                $cropArea.css({
                    left: constrainedLeft + 'px',
                    top: constrainedTop + 'px'
                });

                self.updateCropData();
                self.updatePreview();
            });

            $(document).on('mouseup', function() {
                if (isDragging) {
                    isDragging = false;
                    $cropArea.removeClass('wfn-crop-dragging');
                }
            });
        },

        /**
         * Update crop data based on current crop area position
         *
         * IMPORTANT: At zoom 100%, position.left is relative to the image top-left.
         * At zoom > 100%, CSS transform creates negative space, so position.left can be negative.
         * We need to remove the offset to get back to the actual image coordinates.
         */
        updateCropData() {
            const $cropArea = this.$cropModal.find('.wfn-crop-area');
            const $img = this.$cropModal.find('.wfn-crop-image');
            const position = $cropArea.position();

            // Get current displayed dimensions (without zoom transform)
            const imgWidth = $img.width();
            const imgHeight = $img.height();

            // Calculate scale factor between displayed image and original
            const scaleX = this.originalWidth / imgWidth;
            const scaleY = this.originalHeight / imgHeight;

            // At zoom 100%: position is directly relative to image
            // At zoom > 100%: CSS transform creates offset, position includes that offset
            // We need to remove the offset to get true image-relative coordinates
            const zoomScale = this.currentZoom / 100;
            const offsetX = (imgWidth * (zoomScale - 1)) / 2;
            const offsetY = (imgHeight * (zoomScale - 1)) / 2;

            // Remove zoom offset to get coordinates relative to original image position
            const imageRelativeLeft = position.left - offsetX;
            const imageRelativeTop = position.top - offsetY;

            // Convert to original image pixel coordinates
            this.cropData = {
                src_x: Math.round(imageRelativeLeft * scaleX),
                src_y: Math.round(imageRelativeTop * scaleY),
                src_w: Math.round($cropArea.width() * scaleX),
                src_h: Math.round($cropArea.height() * scaleY)
            };
        },

        /**
         * Update live preview - directly extract from original image
         *
         * The backend PHP will handle zoom by zooming the original image first,
         * then cropping. We simulate that here by extracting directly from the
         * original image at the calculated crop coordinates.
         */
        updatePreview() {
            const $canvas = this.$cropModal.find('.wfn-crop-preview-canvas');
            const canvas = $canvas[0];
            const ctx = canvas.getContext('2d');
            const $img = this.$cropModal.find('.wfn-crop-image')[0];

            // Set canvas size to grid crop dimensions (4:3 ratio)
            const previewWidth = 300;
            const previewHeight = 225; // 300 * 3/4
            canvas.width = previewWidth;
            canvas.height = previewHeight;

            // Clear canvas
            ctx.clearRect(0, 0, previewWidth, previewHeight);

            // Extract directly from the original image using crop coordinates
            // cropData already contains coordinates in original image space
            // This matches what the backend PHP will do: zoom first, then crop
            ctx.drawImage(
                $img,
                this.cropData.src_x,     // Source X in original image
                this.cropData.src_y,     // Source Y in original image
                this.cropData.src_w,     // Source width in original image
                this.cropData.src_h,     // Source height in original image
                0, 0,                    // Destination X, Y on canvas
                previewWidth,            // Destination width (scales to preview)
                previewHeight            // Destination height (scales to preview)
            );
        },

        /**
         * Apply crop and save to WordPress
         */
        applyCrop() {
            if (!this.currentAttachment) {
                return;
            }

            const $applyButton = this.$cropModal.find('.wfn-crop-apply');

            // Don't allow double-clicks
            if ($applyButton.prop('disabled')) {
                return;
            }

            $applyButton.prop('disabled', true).text('Saving...');

            // Send crop data to server via AJAX
            $.ajax({
                url: wfnCrop.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wfn_save_crop_coordinates',
                    nonce: wfnCrop.nonce,
                    attachment_id: this.currentAttachment.id,
                    src_x: this.cropData.src_x,
                    src_y: this.cropData.src_y,
                    src_w: this.cropData.src_w,
                    src_h: this.cropData.src_h,
                    zoom_level: this.currentZoom
                },
                success: (response) => {
                    if (response.success) {
                        this.closeCropModal();
                        // Show success message
                        this.showNotice('Crop saved successfully! Scroll down to see both versions.', 'success');
                        // Refresh the admin preview to show the cropped version
                        this.refreshAdminPreview(this.currentAttachment.id);

                        // Make sure comparison is visible after cropping
                        setTimeout(() => {
                            const $field = this.findFeaturedImageField();
                            const $comparison = $field.find('.wfn-image-comparison');
                            if ($comparison.length && $comparison.is(':hidden')) {
                                $comparison.slideDown(300);
                            }
                        }, 1500);
                    } else {
                        this.showNotice('Error saving crop: ' + response.data.message, 'error');
                        $applyButton.prop('disabled', false).text('Apply Crop');
                    }
                },
                error: () => {
                    this.showNotice('Network error while saving crop', 'error');
                    $applyButton.prop('disabled', false).text('Apply Crop');
                }
            });
        },

        /**
         * Close crop modal
         */
        closeCropModal() {
            if (this.$cropModal) {
                this.$cropModal.removeClass('wfn-crop-active');
            }
        },

        /**
         * Refresh admin preview with BOTH full and cropped images
         * Shows side-by-side comparison
         */
        refreshAdminPreview(attachmentId) {
            const self = this;

            // Add a small delay to let WordPress finish saving the metadata
            setTimeout(function() {
                // Find the featured image field using helper function
                const $field = self.findFeaturedImageField();

                if ($field && $field.length) {
                    // Force reload by fetching fresh attachment data
                    if (typeof wp !== 'undefined' && wp.media) {
                        // Try to clear the attachment cache, but don't fail if it errors
                        try {
                            const cachedAttachment = wp.media.attachment(attachmentId);
                            if (cachedAttachment && cachedAttachment.get('id')) {
                                wp.media.model.Attachment.get(attachmentId).clear();
                            }
                        } catch (e) {
                            // Silent fail - not critical
                        }

                        // Use WordPress REST API to get attachment data
                        // Get the proper REST nonce - prioritize wfnCrop.restNonce
                        const restNonce = (typeof wfnCrop !== 'undefined' && wfnCrop.restNonce)
                            ? wfnCrop.restNonce
                            : (typeof wpApiSettings !== 'undefined' && wpApiSettings.nonce)
                                ? wpApiSettings.nonce
                                : (wp.media.view.settings && wp.media.view.settings.post && wp.media.view.settings.post.nonce)
                                    ? wp.media.view.settings.post.nonce
                                    : null;

                        $.ajax({
                            url: `/wp-json/wp/v2/media/${attachmentId}?context=edit`,
                            type: 'GET',
                            beforeSend: function(xhr) {
                                if (restNonce) {
                                    xhr.setRequestHeader('X-WP-Nonce', restNonce);
                                }
                            },
                            success: function(attachmentData) {
                                const fullUrl = attachmentData.source_url; // Full size original
                                const gridCropUrl = attachmentData.media_details &&
                                                    attachmentData.media_details.sizes &&
                                                    attachmentData.media_details.sizes['wfn-grid-crop']
                                    ? attachmentData.media_details.sizes['wfn-grid-crop'].source_url
                                    : null;

                                // Remove any existing preview comparison
                                $field.find('.wfn-image-comparison').remove();

                                // Create side-by-side preview
                                const cacheBuster = '?v=' + new Date().getTime();
                                const width = attachmentData.media_details ? attachmentData.media_details.width : 'Unknown';
                                const height = attachmentData.media_details ? attachmentData.media_details.height : 'Unknown';

                                const comparisonHTML = `
                                    <div class="wfn-image-comparison" style="
                                        display: grid;
                                        grid-template-columns: 1fr 1fr;
                                        gap: 20px;
                                        margin-top: 15px;
                                        padding: 15px;
                                        background: #f5f5f5;
                                        border-radius: 4px;
                                    ">
                                        <div style="text-align: center;">
                                            <div style="font-weight: 600; margin-bottom: 8px; color: #2c3e50;">
                                                Shown on Funeral Page
                                            </div>
                                            <img src="${fullUrl}${cacheBuster}" style="
                                                max-width: 100%;
                                                height: auto;
                                                max-height: 300px;
                                                border: 2px solid #ddd;
                                                border-radius: 4px;
                                            " />
                                            <div style="font-size: 11px; color: #666; margin-top: 4px;">
                                                Full Image: ${width} × ${height}
                                            </div>
                                        </div>
                                        <div style="text-align: center;">
                                            <div style="font-weight: 600; margin-bottom: 8px; color: #2c3e50;">
                                                Shown on Grid/List Page
                                            </div>
                                            ${gridCropUrl ? `
                                                <img src="${gridCropUrl}${cacheBuster}" style="
                                                    max-width: 100%;
                                                    height: auto;
                                                    max-height: 300px;
                                                    border: 2px solid #3498db;
                                                    border-radius: 4px;
                                                " />
                                                <div style="font-size: 11px; color: #666; margin-top: 4px;">
                                                    Cropped: 800 × 600 (4:3)
                                                </div>
                                            ` : `
                                                <div style="
                                                    padding: 40px;
                                                    background: white;
                                                    border: 2px dashed #ccc;
                                                    border-radius: 4px;
                                                    color: #999;
                                                ">
                                                    No crop yet.<br>Click image above to crop.
                                                </div>
                                            `}
                                        </div>
                                    </div>
                                `;

                                // Find the right place to insert - look for .inside div or .acf-input
                                const $insertTarget = $field.find('.inside').length
                                    ? $field.find('.inside')
                                    : $field.find('.acf-input');

                                if ($insertTarget.length) {
                                    $insertTarget.append(comparisonHTML);
                                    // Don't show notice here - it's already shown in applyCrop()
                                }
                            },
                            error: function(xhr, status, error) {
                                // Silent error - not critical to user workflow
                            }
                        });
                    }
                }
            }, 1000); // Wait 1 second for metadata to be saved
        },

        /**
         * Find the featured image field using multiple methods
         */
        findFeaturedImageField() {
            let $field = null;

            // Try 1: ACFE post field with featured_image
            $field = $('.acf-field[data-type="acfe_post_field"]').filter(function() {
                return $(this).find('[data-field_type="featured_image"]').length > 0;
            });

            // Try 2: Direct image field named 'person_image'
            if (!$field || !$field.length) {
                $field = $('.acf-field[data-name="person_image"]');
            }

            // Try 3: Any ACF image field (first one)
            if (!$field || !$field.length) {
                $field = $('.acf-field[data-type="image"]').first();
            }

            // Try 4: Look for WordPress featured image metabox
            if (!$field || !$field.length) {
                $field = $('#postimagediv');
            }

            return $field && $field.length ? $field : null;
        },

        /**
         * Get attachment ID from field using multiple methods
         */
        getAttachmentIdFromField($field) {
            if (!$field || !$field.length) return null;

            // Method 1: Hidden input with [ID]
            let $idInput = $field.find('input[name*="[ID]"]');
            if ($idInput.length && $idInput.val()) {
                return $idInput.val();
            }

            // Method 2: data-id attribute on image
            const dataId = $field.find('img[data-id]').attr('data-id');
            if (dataId) {
                return dataId;
            }

            // Method 3: input type hidden with name="ID"
            $idInput = $field.find('input[type="hidden"][name="ID"]');
            if ($idInput.length && $idInput.val()) {
                return $idInput.val();
            }

            // Method 4: WordPress featured image thumbnail id
            const thumbnailId = $field.find('#_thumbnail_id').val();
            if (thumbnailId) {
                return thumbnailId;
            }

            return null;
        },

        /**
         * Add manual button to toggle comparison preview visibility
         */
        addManualPreviewButton() {
            const self = this;

            setTimeout(function() {
                const $field = self.findFeaturedImageField();

                // Check if button already exists globally
                if (!$field || $('.wfn-manual-preview-btn').length > 0) {
                    return;
                }

                const $button = $(`
                    <button type="button" class="button wfn-manual-preview-btn" style="margin-top: 10px; display: inline-flex; align-items: center; gap: 5px;">
                        <span class="dashicons dashicons-visibility" style="margin-top: 0;"></span>
                        <span>Toggle Image Preview</span>
                    </button>
                `);

                $button.on('click', function(e) {
                    e.preventDefault();

                    // Check if comparison already exists
                    const $existingComparison = $field.find('.wfn-image-comparison');

                    if ($existingComparison.length) {
                        // Toggle visibility
                        $existingComparison.slideToggle(300);
                    } else {
                        // Create new comparison
                        const attachmentId = self.getAttachmentIdFromField($field);
                        if (attachmentId) {
                            self.refreshAdminPreview(parseInt(attachmentId));
                        } else {
                            alert('No image found. Please upload an image first.');
                        }
                    }
                });

                // Find the right place to insert the button
                const $insertTarget = $field.find('.acf-input').length
                    ? $field.find('.acf-input')
                    : $field.find('.inside');

                if ($insertTarget.length) {
                    $insertTarget.append($button);
                }
            }, 2500); // Run after all comparison attempts
        },

        /**
         * Show comparison preview on page load for existing images
         */
        showInitialComparisonPreview() {
            const self = this;

            // Try multiple times with increasing delays to ensure ACF has loaded
            const attempts = [500, 1000, 1500, 2500];

            attempts.forEach(function(delay) {
                setTimeout(function() {
                    // Look for ALL possible featured image field variations
                    let $field = null;

                    // Try 1: ACFE post field with featured_image
                    $field = $('.acf-field[data-type="acfe_post_field"]').filter(function() {
                        return $(this).find('[data-field_type="featured_image"]').length > 0;
                    });

                    // Try 2: Direct image field named 'person_image'
                    if (!$field.length) {
                        $field = $('.acf-field[data-name="person_image"]');
                    }

                    // Try 3: Any ACF image field
                    if (!$field.length) {
                        $field = $('.acf-field[data-type="image"]');
                    }

                    // Try 4: Look for WordPress featured image metabox
                    if (!$field.length) {
                        $field = $('#postimagediv');
                    }

                    // Look for attachment ID in various possible locations
                    let attachmentId = null;
                    if ($field && $field.length) {
                        // Method 1: Hidden input with [ID]
                        let $idInput = $field.find('input[name*="[ID]"]');

                        if (!$idInput.length) {
                            // Method 2: data-id attribute on image
                            const dataId = $field.find('img[data-id]').attr('data-id');
                            if (dataId) attachmentId = dataId;
                        }

                        if (!$idInput.length && !attachmentId) {
                            // Method 3: input type hidden with name="ID"
                            $idInput = $field.find('input[type="hidden"][name="ID"]');
                        }

                        if ($idInput.length) {
                            attachmentId = $idInput.val();
                        }
                    }

                    // Also try using the helper function
                    if (!attachmentId && $field && $field.length) {
                        attachmentId = self.getAttachmentIdFromField($field);
                    }

                    if (attachmentId && parseInt(attachmentId) > 0) {
                        // Check if comparison already exists
                        if ($field.find('.wfn-image-comparison').length === 0) {
                            self.refreshAdminPreview(parseInt(attachmentId));
                        }
                    }
                }, delay);
            });
        },

        /**
         * Bind zoom events
         */
        bindZoomEvents() {
            const self = this;

            // Zoom slider input
            this.$cropModal.find('#wfn-zoom-slider').on('input', function() {
                const zoomLevel = parseInt($(this).val());
                self.applyZoom(zoomLevel);
            });

            // Zoom in button
            this.$cropModal.find('.wfn-zoom-in').on('click', function() {
                self.zoomIn();
            });

            // Zoom out button
            this.$cropModal.find('.wfn-zoom-out').on('click', function() {
                self.zoomOut();
            });
        },

        /**
         * Bind keyboard shortcuts for zoom
         */
        bindKeyboardShortcuts() {
            const self = this;

            $(document).on('keydown.wfn-crop', function(e) {
                // Only active when crop tool is visible
                if (!self.$cropModal || !self.$cropModal.hasClass('wfn-crop-active')) {
                    return;
                }

                // Check for zoom keys
                if (e.key === '+' || e.key === '=') {
                    e.preventDefault();
                    self.zoomIn();
                } else if (e.key === '-' || e.key === '_') {
                    e.preventDefault();
                    self.zoomOut();
                } else if (e.key === '0') {
                    e.preventDefault();
                    self.resetZoom();
                }
            });
        },

        /**
         * Bind mouse wheel zoom (Ctrl+Scroll)
         */
        bindMouseWheelZoom() {
            const self = this;

            this.$cropModal.find('.wfn-crop-image-container').on('wheel', function(e) {
                // Only zoom if Ctrl key held (Cmd on Mac)
                if (e.ctrlKey || e.metaKey) {
                    e.preventDefault();

                    const delta = e.originalEvent.deltaY;
                    if (delta < 0) {
                        // Scroll up = zoom in
                        self.zoomIn();
                    } else {
                        // Scroll down = zoom out
                        self.zoomOut();
                    }
                }
            });
        },

        /**
         * Bind touch gestures for pinch-to-zoom
         */
        bindTouchGestures() {
            const self = this;
            let touchStartDistance = 0;
            let initialZoomLevel = 100;

            const $cropContainer = this.$cropModal.find('.wfn-crop-image-container');

            $cropContainer.on('touchstart', function(e) {
                if (e.touches && e.touches.length === 2) {
                    touchStartDistance = self.getTouchDistance(e.touches[0], e.touches[1]);
                    initialZoomLevel = self.currentZoom;
                }
            });

            $cropContainer.on('touchmove', function(e) {
                if (e.touches && e.touches.length === 2) {
                    e.preventDefault(); // Prevent browser zoom

                    const currentDistance = self.getTouchDistance(e.touches[0], e.touches[1]);
                    const scale = currentDistance / touchStartDistance;

                    // Map scale to zoom level (100-300%)
                    let newZoom = Math.round(initialZoomLevel * scale);
                    newZoom = Math.max(100, Math.min(300, newZoom)); // Clamp to range

                    // Snap to 10% increments
                    newZoom = Math.round(newZoom / 10) * 10;

                    self.applyZoom(newZoom);
                    self.$cropModal.find('#wfn-zoom-slider').val(newZoom);
                }
            });
        },

        /**
         * Calculate distance between two touch points
         */
        getTouchDistance(touch1, touch2) {
            const dx = touch1.clientX - touch2.clientX;
            const dy = touch1.clientY - touch2.clientY;
            return Math.sqrt(dx * dx + dy * dy);
        },

        /**
         * Apply zoom to image
         */
        applyZoom(zoomLevel) {
            this.currentZoom = zoomLevel;

            // Update zoom display
            this.$cropModal.find('.wfn-zoom-display').text(zoomLevel + '%');

            // Apply CSS transform to image
            const scale = zoomLevel / 100;
            this.$cropModal.find('.wfn-crop-image').css('transform', `scale(${scale})`);

            // Update crop data and preview (debounced)
            this.updateCropData();
            this.debouncedUpdatePreview();
        },

        /**
         * Zoom in by 10%
         */
        zoomIn() {
            const currentZoom = parseInt(this.$cropModal.find('#wfn-zoom-slider').val());
            const newZoom = Math.min(300, currentZoom + 10);
            this.$cropModal.find('#wfn-zoom-slider').val(newZoom);
            this.applyZoom(newZoom);
        },

        /**
         * Zoom out by 10%
         */
        zoomOut() {
            const currentZoom = parseInt(this.$cropModal.find('#wfn-zoom-slider').val());
            const newZoom = Math.max(100, currentZoom - 10);
            this.$cropModal.find('#wfn-zoom-slider').val(newZoom);
            this.applyZoom(newZoom);
        },

        /**
         * Reset zoom to 100%
         */
        resetZoom() {
            this.$cropModal.find('#wfn-zoom-slider').val(100);
            this.applyZoom(100);
        },

        /**
         * Debounced preview update for performance
         */
        debouncedUpdatePreview() {
            clearTimeout(this.previewTimeout);
            this.previewTimeout = setTimeout(() => {
                this.updatePreview();
            }, 100); // Reduced to 100ms for snappier feedback during zoom
        },

        /**
         * Show admin notice
         */
        showNotice(message, type) {
            const noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
            const $notice = $(`
                <div class="notice ${noticeClass} is-dismissible">
                    <p>${message}</p>
                </div>
            `);

            $('.wrap h1').after($notice);

            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                $notice.fadeOut(() => $notice.remove());
            }, 5000);
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        WFN_ImageCrop.init();
    });

})(jQuery);
