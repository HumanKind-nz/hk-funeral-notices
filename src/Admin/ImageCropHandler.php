<?php
declare(strict_types=1);

namespace WeaveStudios\FuneralNotices\Admin;

/**
 * Image Crop Handler
 *
 * Handles user-controlled image cropping for funeral notice featured images.
 * Registers custom image size for grid display and applies user-defined crop coordinates.
 *
 * @since 2.4.0
 */
class ImageCropHandler {

    /**
     * Custom image size name for grid display
     */
    private const GRID_CROP_SIZE = 'wfn-grid-crop';

    /**
     * Grid crop dimensions (4:3 aspect ratio)
     */
    private const GRID_WIDTH = 800;
    private const GRID_HEIGHT = 600;

    /**
     * Metadata key for storing user crop coordinates
     */
    private const CROP_META_KEY = 'wfn_grid_crop_data';

    /**
     * Initialize the image crop handler
     */
    public function __construct() {
        $this->register_hooks();
    }

    /**
     * Register WordPress hooks
     */
    private function register_hooks(): void {
        // Register custom image size for grid display
        add_action('after_setup_theme', [$this, 'register_grid_crop_size']);

        // Apply user-controlled crop to grid size generation
        add_filter('wp_generate_attachment_metadata', [$this, 'apply_user_crop_to_grid_size'], 10, 2);

        // Enqueue crop tool assets on funeral notice edit screen
        add_action('admin_enqueue_scripts', [$this, 'enqueue_crop_assets']);

        // Force media uploader to default to Upload tab
        add_action('admin_footer-post.php', [$this, 'force_media_upload_tab']);
        add_action('admin_footer-post-new.php', [$this, 'force_media_upload_tab']);

        // AJAX handler for saving crop coordinates
        add_action('wp_ajax_wfn_save_crop_coordinates', [$this, 'ajax_save_crop_coordinates']);

        // Add custom image sizes to media library dropdown
        add_filter('image_size_names_choose', [$this, 'add_grid_crop_to_media_sizes']);
    }

    /**
     * Register custom image size for grid display
     *
     * This size uses hard crop mode with user-controlled coordinates
     */
    public function register_grid_crop_size(): void {
        add_image_size(
            self::GRID_CROP_SIZE,
            self::GRID_WIDTH,
            self::GRID_HEIGHT,
            true // Hard crop - we'll control the coordinates
        );
    }

    /**
     * Apply user-defined crop coordinates to grid image size generation
     *
     * @param array $metadata Attachment metadata
     * @param int $attachment_id Attachment post ID
     * @return array Modified metadata
     */
    public function apply_user_crop_to_grid_size(array $metadata, int $attachment_id): array {
        // Get user-defined crop coordinates
        $crop_data = get_post_meta($attachment_id, self::CROP_META_KEY, true);

        // If no custom crop data, let WordPress use default center crop
        if (empty($crop_data) || !is_array($crop_data)) {
            return $metadata;
        }

        // Validate crop data structure
        if (!$this->is_valid_crop_data($crop_data)) {
            return $metadata;
        }

        // Get the original image file path
        $file_path = get_attached_file($attachment_id);
        if (!$file_path || !file_exists($file_path)) {
            return $metadata;
        }

        // Generate cropped image using WordPress image editor
        $cropped_image = $this->generate_cropped_image($file_path, $crop_data);

        // If crop was successful, add to metadata
        if ($cropped_image && !is_wp_error($cropped_image)) {
            $metadata['sizes'][self::GRID_CROP_SIZE] = [
                'file' => wp_basename($cropped_image['path']),
                'width' => $cropped_image['width'],
                'height' => $cropped_image['height'],
                'mime-type' => $cropped_image['mime-type'],
            ];
        }

        return $metadata;
    }

    /**
     * Generate cropped image file using user-defined coordinates
     *
     * @param string $source_path Path to source image
     * @param array $crop_data Crop coordinates [src_x, src_y, src_w, src_h] and optional zoom_level
     * @return array|false Cropped image data or false on failure
     */
    private function generate_cropped_image(string $source_path, array $crop_data) {
        // Load WordPress image editor
        $image_editor = wp_get_image_editor($source_path);

        if (is_wp_error($image_editor)) {
            error_log('WFN Image Crop: Failed to load image editor - ' . $image_editor->get_error_message());
            return false;
        }

        // Get zoom level (default to 100 = no zoom)
        $zoom_level = isset($crop_data['zoom_level']) ? (int) $crop_data['zoom_level'] : 100;

        // Apply zoom before crop if zoom level > 100
        if ($zoom_level > 100) {
            $zoom_scale = $zoom_level / 100; // Convert 150 to 1.5, 200 to 2.0, etc.

            // Get current image dimensions
            $current_size = $image_editor->get_size();
            if (!is_wp_error($current_size)) {
                $new_width = (int) round($current_size['width'] * $zoom_scale);
                $new_height = (int) round($current_size['height'] * $zoom_scale);

                // Resize image to zoomed dimensions
                $resize_result = $image_editor->resize($new_width, $new_height, false);

                if (is_wp_error($resize_result)) {
                    error_log('WFN Image Crop: Zoom operation failed - ' . $resize_result->get_error_message());
                    // Continue without zoom rather than failing completely
                }
            }
        }

        // Apply crop using user coordinates (coordinates are already calculated for zoomed image)
        $crop_result = $image_editor->crop(
            (int) $crop_data['src_x'],
            (int) $crop_data['src_y'],
            (int) $crop_data['src_w'],
            (int) $crop_data['src_h'],
            self::GRID_WIDTH,
            self::GRID_HEIGHT
        );

        if (is_wp_error($crop_result)) {
            error_log('WFN Image Crop: Crop operation failed - ' . $crop_result->get_error_message());
            return false;
        }

        // Generate filename for cropped image in SAME directory as original
        $filename = $this->generate_crop_filename($source_path);
        $destination = dirname($source_path) . '/' . $filename;

        // Save cropped image
        $save_result = $image_editor->save($destination);

        if (is_wp_error($save_result)) {
            error_log('WFN Image Crop: Save failed - ' . $save_result->get_error_message());
            return false;
        }

        return $save_result;
    }

    /**
     * Generate filename for cropped image
     *
     * @param string $source_path Original image path
     * @return string Filename for cropped image
     */
    private function generate_crop_filename(string $source_path): string {
        $pathinfo = pathinfo($source_path);
        $extension = $pathinfo['extension'] ?? 'jpg';

        return sprintf(
            '%s-%dx%d.%s',
            $pathinfo['filename'],
            self::GRID_WIDTH,
            self::GRID_HEIGHT,
            $extension
        );
    }

    /**
     * Validate crop data structure
     *
     * @param array $crop_data Crop coordinates to validate
     * @return bool True if valid
     */
    private function is_valid_crop_data(array $crop_data): bool {
        $required_keys = ['src_x', 'src_y', 'src_w', 'src_h'];

        foreach ($required_keys as $key) {
            if (!isset($crop_data[$key]) || !is_numeric($crop_data[$key])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Save crop coordinates via AJAX
     */
    public function ajax_save_crop_coordinates(): void {
        // Verify nonce
        check_ajax_referer('wfn_crop_nonce', 'nonce');

        // Verify user capability
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
            return;
        }

        // Get and validate attachment ID
        $attachment_id = isset($_POST['attachment_id']) ? (int) $_POST['attachment_id'] : 0;
        if (!$attachment_id) {
            wp_send_json_error(['message' => 'Invalid attachment ID']);
            return;
        }

        // Get crop coordinates and zoom level
        $zoom_level = isset($_POST['zoom_level']) ? (int) $_POST['zoom_level'] : 100;

        // Validate zoom level (100-300 range)
        $zoom_level = max(100, min(300, $zoom_level));

        $crop_data = [
            'src_x' => isset($_POST['src_x']) ? (float) $_POST['src_x'] : 0,
            'src_y' => isset($_POST['src_y']) ? (float) $_POST['src_y'] : 0,
            'src_w' => isset($_POST['src_w']) ? (float) $_POST['src_w'] : 0,
            'src_h' => isset($_POST['src_h']) ? (float) $_POST['src_h'] : 0,
            'zoom_level' => $zoom_level,
            'aspect_ratio' => '4:3',
            'created_at' => current_time('mysql'),
        ];

        // Validate crop data
        if (!$this->is_valid_crop_data($crop_data)) {
            wp_send_json_error(['message' => 'Invalid crop coordinates']);
            return;
        }

        // Save crop metadata
        update_post_meta($attachment_id, self::CROP_META_KEY, $crop_data);

        // Get the original image file path
        $file_path = get_attached_file($attachment_id);
        if (!$file_path || !file_exists($file_path)) {
            wp_send_json_error(['message' => 'Original image file not found']);
            return;
        }

        // Generate the cropped image file directly
        $cropped_image = $this->generate_cropped_image($file_path, $crop_data);

        if (!$cropped_image || is_wp_error($cropped_image)) {
            $error_msg = is_wp_error($cropped_image) ? $cropped_image->get_error_message() : 'Failed to generate cropped image';
            error_log('WFN Image Crop AJAX: ' . $error_msg);
            wp_send_json_error(['message' => $error_msg]);
            return;
        }

        // Update attachment metadata with new crop size
        $metadata = wp_get_attachment_metadata($attachment_id);
        $metadata['sizes'][self::GRID_CROP_SIZE] = [
            'file' => wp_basename($cropped_image['path']),
            'width' => $cropped_image['width'],
            'height' => $cropped_image['height'],
            'mime-type' => $cropped_image['mime-type'],
        ];
        wp_update_attachment_metadata($attachment_id, $metadata);

        // Log success for debugging
        error_log(sprintf(
            'WFN Image Crop: Successfully created crop for attachment %d at %s',
            $attachment_id,
            $cropped_image['path']
        ));

        wp_send_json_success([
            'message' => 'Crop saved successfully',
            'crop_data' => $crop_data,
            'crop_url' => $cropped_image['url'] ?? '',
        ]);
    }

    /**
     * Enqueue crop tool assets on funeral notice edit screen
     *
     * @param string $hook Current admin page hook
     */
    public function enqueue_crop_assets(string $hook): void {
        // Only load on post edit screens
        if (!in_array($hook, ['post.php', 'post-new.php'])) {
            return;
        }

        // Only load for funeral-notice post type
        global $post;
        if (!$post || get_post_type($post) !== 'funeral-notice') {
            return;
        }

        // Enqueue JavaScript with timestamp for cache busting during development
        wp_enqueue_script(
            'wfn-image-crop',
            plugins_url('assets/js/admin/image-crop.js', dirname(__DIR__, 1)),
            ['jquery', 'acf-input'],
            WFN_VERSION . '.' . filemtime(dirname(__DIR__, 1) . '/assets/js/admin/image-crop.js'),
            true
        );

        // Enqueue CSS with timestamp for cache busting
        wp_enqueue_style(
            'wfn-image-crop',
            plugins_url('assets/css/admin/image-crop.css', dirname(__DIR__, 1)),
            [],
            WFN_VERSION . '.' . filemtime(dirname(__DIR__, 1) . '/assets/css/admin/image-crop.css')
        );

        // Localize script with AJAX URL and nonces
        wp_localize_script('wfn-image-crop', 'wfnCrop', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wfn_crop_nonce'),
            'restNonce' => wp_create_nonce('wp_rest'),
            'gridWidth' => self::GRID_WIDTH,
            'gridHeight' => self::GRID_HEIGHT,
            'aspectRatio' => '4:3',
            'zoomMin' => 100,
            'zoomMax' => 300,
            'zoomIncrement' => 10,
        ]);
    }

    /**
     * Add grid crop size to media library size dropdown
     *
     * @param array $sizes Existing image sizes
     * @return array Modified sizes
     */
    public function add_grid_crop_to_media_sizes(array $sizes): array {
        $sizes[self::GRID_CROP_SIZE] = __('Grid Crop (4:3)', 'hk-funeral-notices');
        return $sizes;
    }

    /**
     * Get grid crop size name
     *
     * @return string Image size name
     */
    public static function get_grid_crop_size(): string {
        return self::GRID_CROP_SIZE;
    }

    /**
     * Check if image has grid crop available
     *
     * @param int $attachment_id Attachment post ID
     * @return bool True if grid crop exists
     */
    public static function has_grid_crop(int $attachment_id): bool {
        $metadata = wp_get_attachment_metadata($attachment_id);
        return isset($metadata['sizes'][self::GRID_CROP_SIZE]);
    }

    /**
     * Force media uploader to default to Upload tab
     *
     * Outputs JavaScript to modify WordPress media library defaults
     * to always open on the Upload tab instead of Media Library tab
     */
    public function force_media_upload_tab(): void {
        // Only on funeral-notice post type
        global $post;
        if (!$post || get_post_type($post) !== 'funeral-notice') {
            return;
        }

        ?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Force media library to default to upload tab
                if (typeof wp !== 'undefined' && wp.media && wp.media.controller && wp.media.controller.Library) {
                    wp.media.controller.Library.prototype.defaults.contentUserSetting = false;
                }
            });
        </script>
        <?php
    }
}
