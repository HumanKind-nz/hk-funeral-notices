<?php
declare(strict_types=1);

namespace HumanKind\FuneralNotices\Admin;

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
    private const GRID_CROP_SIZE = 'hkfn-grid-crop';

    /**
     * Pre-3.0 image size name — renditions with this name still exist in
     * attachment metadata on sites upgraded from v2.x. Read-only fallback.
     */
    private const LEGACY_GRID_CROP_SIZE = 'wfn-grid-crop';

    /**
     * Grid crop dimensions (1:1 square)
     *
     * Square is the compromise between portrait and landscape source photos:
     * a landscape photo can always fill a square, and a portrait photo can
     * almost always fit the whole head in one. Crops that extend past the
     * image edges are padded with a blurred fill (see composite_extended_crop).
     */
    private const GRID_WIDTH = 800;
    private const GRID_HEIGHT = 800;

    /**
     * Aspect ratio label stored with crop meta, used to detect crops saved
     * under an older ratio so regeneration can adjust instead of distorting.
     */
    private const ASPECT_RATIO_LABEL = '1:1';

    /**
     * Metadata key for storing user crop coordinates
     */
    private const CROP_META_KEY = 'hkfn_grid_crop_data';

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
        add_action('wp_ajax_hkfn_save_crop_coordinates', [$this, 'ajax_save_crop_coordinates']);

        // Add custom image sizes to media library dropdown
        add_filter('image_size_names_choose', [$this, 'add_grid_crop_to_media_sizes']);

        // Stop the person photo itself opening the media library
        add_filter('admin_post_thumbnail_html', [$this, 'unlink_person_photo_thumbnail'], 10, 3);
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
            $this->delete_previous_crop($attachment_id, wp_basename($cropped_image['path']));
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

        $src_x = (float) $crop_data['src_x'];
        $src_y = (float) $crop_data['src_y'];
        $src_w = (float) $crop_data['src_w'];
        $src_h = (float) $crop_data['src_h'];

        // Crops stored under an older aspect ratio (e.g. 4:3 from pre-square
        // versions) must not be squashed into the current output size on
        // regeneration — recentre the rectangle to the current ratio instead.
        $stored_ratio = isset($crop_data['aspect_ratio']) ? (string) $crop_data['aspect_ratio'] : '';
        if ($stored_ratio !== self::ASPECT_RATIO_LABEL) {
            $image_size = $image_editor->get_size();
            if (!is_wp_error($image_size)) {
                [$src_x, $src_y, $src_w, $src_h] = $this->adjust_rect_to_ratio(
                    $src_x,
                    $src_y,
                    $src_w,
                    $src_h,
                    (int) $image_size['width'],
                    (int) $image_size['height']
                );
            }
        }

        // Crops extending past the image edges are composited with a blurred
        // fill; the WordPress editor can only crop within the source image.
        $image_size = $image_editor->get_size();
        $out_of_bounds = !is_wp_error($image_size) && (
            $src_x < 0 || $src_y < 0
            || $src_x + $src_w > (int) $image_size['width']
            || $src_y + $src_h > (int) $image_size['height']
        );

        // Generate filename for cropped image in SAME directory as original
        $filename = $this->generate_crop_filename($source_path);
        $destination = dirname($source_path) . '/' . $filename;

        if ($out_of_bounds) {
            // Composite works from the source file in natural coordinates, so
            // map legacy-zoomed coordinates back to the unzoomed image.
            $zoom_scale = $zoom_level > 100 ? $zoom_level / 100 : 1.0;

            return self::composite_extended_crop(
                $source_path,
                $src_x / $zoom_scale,
                $src_y / $zoom_scale,
                $src_w / $zoom_scale,
                $src_h / $zoom_scale,
                self::GRID_WIDTH,
                self::GRID_HEIGHT,
                $destination
            );
        }

        // Apply crop using user coordinates (coordinates are already calculated for zoomed image)
        $crop_result = $image_editor->crop(
            (int) round($src_x),
            (int) round($src_y),
            (int) round($src_w),
            (int) round($src_h),
            self::GRID_WIDTH,
            self::GRID_HEIGHT
        );

        if (is_wp_error($crop_result)) {
            error_log('WFN Image Crop: Crop operation failed - ' . $crop_result->get_error_message());
            return false;
        }

        // Save cropped image
        $save_result = $image_editor->save($destination);

        if (is_wp_error($save_result)) {
            error_log('WFN Image Crop: Save failed - ' . $save_result->get_error_message());
            return false;
        }

        return $save_result;
    }

    /**
     * Recentre a crop rectangle onto the current output aspect ratio
     *
     * Expands the deficient dimension around the rectangle's centre, then
     * shifts back inside the image where possible so a regenerated legacy
     * crop stays blur-free unless the image genuinely cannot contain it.
     *
     * @return array{0: float, 1: float, 2: float, 3: float} [x, y, w, h]
     */
    private function adjust_rect_to_ratio(float $x, float $y, float $w, float $h, int $img_w, int $img_h): array {
        if ($w <= 0 || $h <= 0) {
            return [$x, $y, $w, $h];
        }

        $target = self::GRID_WIDTH / self::GRID_HEIGHT;
        $current = $w / $h;

        if (abs($current - $target) / $target < 0.01) {
            return [$x, $y, $w, $h];
        }

        if ($current > $target) {
            // Too wide for the target ratio — grow height around the centre
            $new_h = $w / $target;
            $y -= ($new_h - $h) / 2;
            $h = $new_h;
        } else {
            $new_w = $h * $target;
            $x -= ($new_w - $w) / 2;
            $w = $new_w;
        }

        // Shift back inside the image where the rectangle fits
        if ($w <= $img_w) {
            $x = max(0.0, min($x, $img_w - $w));
        } else {
            $x = ($img_w - $w) / 2;
        }
        if ($h <= $img_h) {
            $y = max(0.0, min($y, $img_h - $h));
        } else {
            $y = ($img_h - $h) / 2;
        }

        return [$x, $y, $w, $h];
    }

    /**
     * Generate a crop that extends past the image edges
     *
     * The area outside the photo is filled with a heavily blurred, slightly
     * darkened enlargement of the cropped region — the pillarbox treatment
     * used for portrait photos on television. A heavy blur reads as soft
     * ambient colour; a light blur would show recognisable smeared detail.
     *
     * Pure GD, no WordPress dependencies, so it can be exercised standalone.
     *
     * @param string $source_path Path to the source image file
     * @param float  $x           Crop X in natural coordinates (may be negative)
     * @param float  $y           Crop Y in natural coordinates (may be negative)
     * @param float  $w           Crop width (may exceed image bounds)
     * @param float  $h           Crop height (may exceed image bounds)
     * @param int    $out_w       Output width in pixels
     * @param int    $out_h       Output height in pixels
     * @param string $destination Path to write the output file
     * @return array|false Same shape as WP_Image_Editor::save() or false
     */
    public static function composite_extended_crop(
        string $source_path,
        float $x,
        float $y,
        float $w,
        float $h,
        int $out_w,
        int $out_h,
        string $destination
    ) {
        if ($w <= 0 || $h <= 0 || !function_exists('imagecreatetruecolor')) {
            return false;
        }

        $info = @getimagesize($source_path);
        $contents = @file_get_contents($source_path);
        if (!$info || $contents === false) {
            error_log('WFN Image Crop: Extended crop could not read source image');
            return false;
        }

        $source = @imagecreatefromstring($contents);
        unset($contents);
        if (!$source) {
            error_log('WFN Image Crop: Extended crop could not decode source image');
            return false;
        }

        $img_w = imagesx($source);
        $img_h = imagesy($source);

        // Portion of the crop rectangle actually covered by the photo
        $ix = max(0.0, $x);
        $iy = max(0.0, $y);
        $ix2 = min((float) $img_w, $x + $w);
        $iy2 = min((float) $img_h, $y + $h);
        $int_w = $ix2 - $ix;
        $int_h = $iy2 - $iy;

        // Refuse crops that are nearly all padding
        if ($int_w < 1 || $int_h < 1 || ($int_w * $int_h) / ($w * $h) < 0.05) {
            error_log('WFN Image Crop: Extended crop rejected - crop area barely covers the photo');
            return false;
        }

        $canvas = imagecreatetruecolor($out_w, $out_h);

        // Background: cover-fit the visible region through two tiny
        // intermediate canvases. Downscale to 16px strips away all detail,
        // then blurring at 120px and upscaling again yields a smooth
        // gradient with none of the block artefacts a single giant
        // bilinear upscale produces.
        $tiny_w = 16;
        $tiny_h = max(1, (int) round($tiny_w * $out_h / $out_w));
        $tiny = imagecreatetruecolor($tiny_w, $tiny_h);

        $cover = max($tiny_w / $int_w, $tiny_h / $int_h);
        $cover_w = (int) max(1, round($int_w * $cover));
        $cover_h = (int) max(1, round($int_h * $cover));
        imagecopyresampled(
            $tiny,
            $source,
            (int) round(($tiny_w - $cover_w) / 2),
            (int) round(($tiny_h - $cover_h) / 2),
            (int) round($ix),
            (int) round($iy),
            $cover_w,
            $cover_h,
            (int) round($int_w),
            (int) round($int_h)
        );

        $mid_w = 120;
        $mid_h = max(1, (int) round($mid_w * $out_h / $out_w));
        $mid = imagecreatetruecolor($mid_w, $mid_h);
        imagecopyresampled($mid, $tiny, 0, 0, 0, 0, $mid_w, $mid_h, $tiny_w, $tiny_h);
        for ($i = 0; $i < 4; $i++) {
            imagefilter($mid, IMG_FILTER_GAUSSIAN_BLUR);
        }

        imagecopyresampled($canvas, $mid, 0, 0, 0, 0, $out_w, $out_h, $mid_w, $mid_h);
        imagefilter($canvas, IMG_FILTER_GAUSSIAN_BLUR);
        imagefilter($canvas, IMG_FILTER_BRIGHTNESS, -12);

        // Foreground: the sharp photo region at its correct position
        $scale = $out_w / $w;
        imagecopyresampled(
            $canvas,
            $source,
            (int) round(($ix - $x) * $scale),
            (int) round(($iy - $y) * $scale),
            (int) round($ix),
            (int) round($iy),
            (int) max(1, round($int_w * $scale)),
            (int) max(1, round($int_h * $scale)),
            (int) round($int_w),
            (int) round($int_h)
        );

        $mime = $info['mime'] ?? 'image/jpeg';
        $saved = false;
        switch ($mime) {
            case 'image/png':
                $saved = imagepng($canvas, $destination, 6);
                break;
            case 'image/webp':
                $saved = imagewebp($canvas, $destination, 82);
                break;
            case 'image/gif':
                $saved = imagegif($canvas, $destination);
                break;
            default:
                $mime = 'image/jpeg';
                $saved = imagejpeg($canvas, $destination, 82);
                break;
        }

        if (!$saved) {
            error_log('WFN Image Crop: Extended crop save failed');
            return false;
        }

        return [
            'path' => $destination,
            'file' => wp_basename($destination),
            'width' => $out_w,
            'height' => $out_h,
            'mime-type' => $mime,
        ];
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

        // Unique suffix per crop: a re-crop gets a NEW filename so CDN,
        // browser, and page caches can never serve the stale crop.
        return sprintf(
            '%s-%dx%d-c%d.%s',
            $pathinfo['filename'],
            self::GRID_WIDTH,
            self::GRID_HEIGHT,
            time(),
            $extension
        );
    }


    /**
     * Delete the previous grid crop file when it is being replaced
     *
     * Only removes files matching our unique-crop naming pattern; never a
     * core-generated rendition another image size might reference.
     */
    private function delete_previous_crop(int $attachment_id, string $new_file): void {
        $metadata = wp_get_attachment_metadata($attachment_id);
        $old_file = $metadata['sizes'][self::GRID_CROP_SIZE]['file'] ?? '';

        if (!$old_file || $old_file === $new_file || !preg_match('/-\d+x\d+-c\d+\./', $old_file)) {
            return;
        }

        $source = get_attached_file($attachment_id);
        if (!$source) {
            return;
        }

        $old_path = dirname($source) . '/' . $old_file;
        if (file_exists($old_path)) {
            wp_delete_file($old_path);
        }
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

        // X/Y may be negative (crop extended past the image edges) but the
        // rectangle itself must have positive area
        return (float) $crop_data['src_w'] > 0 && (float) $crop_data['src_h'] > 0;
    }

    /**
     * Save crop coordinates via AJAX
     */
    public function ajax_save_crop_coordinates(): void {
        // Verify nonce
        check_ajax_referer('hkfn_crop_nonce', 'nonce');

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
            'aspect_ratio' => self::ASPECT_RATIO_LABEL,
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

        // Remove the superseded crop file, then update metadata
        $this->delete_previous_crop($attachment_id, wp_basename($cropped_image['path']));

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

        // WP_Image_Editor::save() returns a path, not a URL — map it for the JS preview
        $upload_dir = wp_upload_dir();
        $crop_url = str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $cropped_image['path']);

        $response = [
            'message' => 'Crop saved successfully',
            'crop_data' => $crop_data,
            'crop_url' => $crop_url,
        ];

        // Refresh the page cache so grid pages pick up the new crop — and
        // only the page cache. A crop can't invalidate object-cache data or
        // Beaver Builder assets (the crop file even gets a fresh filename),
        // so a full purge just leaves the whole site cache-cold and makes
        // this request and the editor's next one crawl. Under PHP-FPM the
        // response is handed to the browser before the purge runs, so the
        // crop modal closes immediately.
        if (function_exists('fastcgi_finish_request')) {
            @header('Content-Type: application/json; charset=' . get_option('blog_charset'));
            echo wp_json_encode(['success' => true, 'data' => $response]);
            fastcgi_finish_request();
            $this->purge_page_cache();
            exit;
        }

        $this->purge_page_cache();
        wp_send_json_success($response);
    }

    /**
     * Purge the page cache after a crop
     *
     * Deliberately narrower than a full cache-helper purge: no object-cache
     * flush and no Beaver Builder asset rebuild, neither of which a crop
     * affects. Covers the Nginx Helper and LiteSpeed page caches.
     */
    private function purge_page_cache(): void {
        global $nginx_purger;
        if (isset($nginx_purger) && is_object($nginx_purger) && method_exists($nginx_purger, 'purge_all')) {
            $nginx_purger->purge_all();
        } elseif (function_exists('litespeed_purge_all')) {
            do_action('litespeed_purge_all');
        }
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

        $plugin_file = dirname(__DIR__, 2) . '/hk-funeral-notices.php';

        // Media library modal for photo selection
        wp_enqueue_media();

        // Cropper.js (vendored, no CDN)
        wp_enqueue_style(
            'hkfn-cropperjs',
            plugins_url('assets/vendor/cropperjs/cropper.min.css', $plugin_file),
            [],
            '1.6.2'
        );
        wp_enqueue_script(
            'hkfn-cropperjs',
            plugins_url('assets/vendor/cropperjs/cropper.min.js', $plugin_file),
            [],
            '1.6.2',
            true
        );

        wp_enqueue_script(
            'hkfn-image-crop',
            plugins_url('assets/js/admin/image-crop-cropperjs.js', $plugin_file),
            ['hkfn-cropperjs', 'media-editor'],
            HKFN_VERSION . '.' . (string) filemtime(dirname(__DIR__, 2) . '/assets/js/admin/image-crop-cropperjs.js'),
            true
        );

        wp_enqueue_style(
            'hkfn-image-crop',
            plugins_url('assets/css/admin/image-crop-b.css', $plugin_file),
            [],
            HKFN_VERSION . '.' . (string) filemtime(dirname(__DIR__, 2) . '/assets/css/admin/image-crop-b.css')
        );

        // ACF Extended relocates the native content editor and featured image
        // boxes into our field groups with JS after first paint, so they
        // briefly render in their default positions and the page visibly
        // jumps when they move. Collapse the default positions from first
        // paint — these child selectors stop matching the moment ACFE
        // re-parents the boxes into .acf-input, so nothing stays hidden.
        if (class_exists('ACFE')) {
            wp_add_inline_style(
                'hkfn-image-crop',
                '#post-body-content > #postdivrich, .meta-box-sortables > #postimagediv {' .
                ' visibility: hidden; height: 0; min-height: 0; overflow: hidden;' .
                ' margin: 0; padding: 0; border: 0; }'
            );
        }

        // Current grid crop for the post being edited (persistent preview)
        $current_crop_url = '';
        $thumb_id = (int) get_post_thumbnail_id($post);
        if ($thumb_id) {
            $meta = wp_get_attachment_metadata($thumb_id);
            if (!empty($meta['sizes'][self::GRID_CROP_SIZE])) {
                $current_crop_url = (string) wp_get_attachment_image_url($thumb_id, self::GRID_CROP_SIZE);
            }
        }

        // Localize script with AJAX URL and nonces
        wp_localize_script('hkfn-image-crop', 'hkfnCrop', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('hkfn_crop_nonce'),
            'restNonce' => wp_create_nonce('wp_rest'),
            'restMediaUrl' => esc_url_raw(rest_url('wp/v2/media')),
            'currentCropUrl' => $current_crop_url,
            'gridWidth' => self::GRID_WIDTH,
            'gridHeight' => self::GRID_HEIGHT,
            'aspectRatio' => self::ASPECT_RATIO_LABEL,
            'zoomMin' => 100,
            'zoomMax' => 300,
            'zoomIncrement' => 10,
        ]);
    }

    /**
     * Stop the person photo thumbnail opening the media library
     *
     * Core wraps the featured image in a link to the media library. Staff kept
     * clicking the photo, landing somewhere none of the photo tools live, and
     * getting lost. The photo becomes a plain image; "Replace photo…" and
     * "Re-crop photo" underneath it are the way in.
     *
     * Runs on the AJAX re-render too (wp_ajax_get_post_thumbnail_html), so the
     * photo stays unclickable after an upload swaps it out.
     *
     * @param string   $content      Featured image metabox markup
     * @param int      $post_id      Post being edited
     * @param int|null $thumbnail_id Attachment ID, null when no photo is set
     * @return string
     */
    public function unlink_person_photo_thumbnail(string $content, int $post_id, $thumbnail_id): string {
        // With no photo set the link reads "Set person photo" and is the only
        // way in if our own upload button ever fails to load — leave it be.
        if (!$thumbnail_id || get_post_type($post_id) !== 'funeral-notice') {
            return $content;
        }

        // Unwrap the thumbnail, keeping the <img> that sits inside the link
        $unwrapped = preg_replace(
            '#<a\b[^>]*\bid=["\']set-post-thumbnail["\'][^>]*>(.*?)</a>#s',
            '$1',
            $content
        );
        if (is_string($unwrapped)) {
            $content = $unwrapped;
        }

        // "Click the image to edit or update" is no longer true
        $stripped = preg_replace(
            '#<p\b[^>]*\bid=["\']set-post-thumbnail-desc["\'][^>]*>.*?</p>#s',
            '',
            $content
        );

        return is_string($stripped) ? $stripped : $content;
    }

    /**
     * Add grid crop size to media library size dropdown
     *
     * @param array $sizes Existing image sizes
     * @return array Modified sizes
     */
    public function add_grid_crop_to_media_sizes(array $sizes): array {
        $sizes[self::GRID_CROP_SIZE] = __('Grid Crop (Square)', 'hk-funeral-notices');
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
     * Get the best available grid image URL for an attachment
     *
     * Prefers the current grid crop, then a legacy v2.x crop rendition
     * (so staff crops survive the wfn_ → hkfn_ rename until re-cropped),
     * then the large/full image for CSS to centre-crop.
     *
     * @param int $attachment_id Attachment post ID
     * @return string|false Image URL or false if the attachment is invalid
     */
    public static function grid_image_url(int $attachment_id) {
        if ($attachment_id <= 0) {
            return false;
        }

        $metadata = wp_get_attachment_metadata($attachment_id);
        if (!is_array($metadata)) {
            $metadata = [];
        }

        // Photos uploaded while a stale 4:3 registration of this size name was
        // in play carry a landscape rendition nobody chose. Skipping it falls
        // through to the large image, which CSS centre-crops to the same
        // result an uncropped upload gets today.
        if (!empty($metadata['sizes'][self::GRID_CROP_SIZE])
            && !self::rendition_matches_output_ratio($metadata['sizes'][self::GRID_CROP_SIZE])) {
            unset($metadata['sizes'][self::GRID_CROP_SIZE]);
        }

        foreach ([self::GRID_CROP_SIZE, self::LEGACY_GRID_CROP_SIZE] as $size) {
            if (!empty($metadata['sizes'][$size]['file'])) {
                $url = wp_get_attachment_image_url($attachment_id, $size);
                if ($url) {
                    return $url;
                }
            }
        }

        // No staff crop exists — a large rendition is plenty for a grid card
        $url = wp_get_attachment_image_url($attachment_id, 'large');
        return $url ?: wp_get_attachment_image_url($attachment_id, 'full');
    }

    /**
     * Does a stored rendition match the aspect ratio the crop tool outputs?
     *
     * @param array $size Entry from attachment metadata['sizes']
     * @return bool True when the rendition is the current output shape
     */
    private static function rendition_matches_output_ratio(array $size): bool {
        $width = (int) ($size['width'] ?? 0);
        $height = (int) ($size['height'] ?? 0);

        if ($width < 1 || $height < 1) {
            return false;
        }

        $target = self::GRID_WIDTH / self::GRID_HEIGHT;

        return abs(($width / $height) - $target) / $target < 0.01;
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
