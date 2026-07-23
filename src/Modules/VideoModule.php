<?php
declare(strict_types=1);

namespace HumanKind\FuneralNotices\Modules;

use HumanKind\FuneralNotices\Services\BunnyStreamService;

/**
 * Video Module
 *
 * Handles video upload and management functionality for memorial videos.
 * Integrates with professional video hosting service and requires premium license.
 *
 * @since 2.1.4
 */
class VideoModule extends BaseModule {

    private BunnyStreamService $bunny_service;

    protected array $default_settings = [
        'max_file_size_mb' => 900,
        'allowed_formats' => ['mp4', 'mov', 'avi', 'webm'],
        'max_duration_minutes' => 30,
        'auto_transcode' => true,
        'quality_preset' => 'balanced', // fast, balanced, high_quality
        'enable_thumbnails' => true,
        'thumbnail_count' => 3,
        'enable_progress_tracking' => true,
        'cleanup_failed_uploads' => true,
        'retention_days' => 30,
        'enable_notifications' => false,
        'notify_on_completion' => false,
        'notify_on_failure' => false
    ];

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct(
            'video',
            'Memorial Videos',
            'Upload and manage memorial video slideshows with premium hosting',
            '2.1.4'
        );

        // Initialize services with hardcoded credentials
        $bunny_config = $this->get_bunny_credentials();
        $this->bunny_service = new BunnyStreamService(
            $bunny_config['library_id'],
            $bunny_config['api_key'],
            $bunny_config['cdn_hostname']
        );
    }

    /**
     * Initialize the module
     */
    public function init(): void {
        parent::init();

        // Always register admin notices (to show license status)
        if (is_admin()) {
            add_action('admin_notices', [$this, 'show_license_notices']);
        }

        // Admin hooks for upload progress tracking (always load, even without license)
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);

        // Only initialize premium features if license is active
        if (!$this->has_premium_license()) {
            return;
        }

        // Hook into ACF save_post for handling video uploads
        add_action('acf/save_post', [$this, 'handle_video_upload'], 20);

        // Ajax handlers for upload progress and status
        add_action('wp_ajax_hkfn_video_upload_progress', [$this, 'ajax_upload_progress']);
        add_action('wp_ajax_hkfn_video_upload_status', [$this, 'ajax_upload_status']);
        add_action('wp_ajax_hkfn_retry_video_upload', [$this, 'ajax_retry_upload']);
        add_action('wp_ajax_hkfn_video_delete', [$this, 'ajax_delete_video']);
        add_action('wp_ajax_hkfn_video_replace', [$this, 'ajax_replace_video']);

        // REMOVED: Ajax handlers for maintenance tasks (feature permanently disabled after incident)
        // add_action('wp_ajax_hkfn_run_video_maintenance', [$this, 'ajax_run_maintenance']);
        // add_action('wp_ajax_hkfn_cleanup_orphaned_videos', [$this, 'ajax_cleanup_orphaned_videos']);
        // add_action('wp_ajax_hkfn_cleanup_stuck_uploads', [$this, 'ajax_cleanup_stuck_uploads']);

        // Manual trigger for testing/debugging
        add_action('wp_ajax_hkfn_manual_process_video', [$this, 'ajax_manual_process_video']);

        // Background processing hooks
        add_action('wp_ajax_nopriv_hkfn_process_video_upload', [$this, 'process_video_upload_background']);
        add_action('wp_ajax_hkfn_process_video_upload', [$this, 'process_video_upload_background']);

        // WordPress cron hook for reliable background processing
        add_action('hkfn_process_video_upload_cron', [$this, 'process_video_upload_cron']);

        // Cleanup hooks
        add_action('before_delete_post', [$this, 'cleanup_video_on_post_delete']);
        // DISABLED: Automatic cleanup caused catastrophic video deletion across all sites
        // add_action('hkfn_cleanup_failed_uploads', [$this, 'cleanup_failed_uploads']);
        // add_action('hkfn_video_maintenance', [$this, 'run_scheduled_maintenance']);

        // DISABLED: Schedule cleanup if not already scheduled
        // Safety: Removed automatic scheduling after incident where orphaned video cleanup
        // deleted all videos from BunnyStream on 2025-10-20. Manual cleanup only via admin.
        // if (!wp_next_scheduled('hkfn_cleanup_failed_uploads')) {
        //     wp_schedule_event(time(), 'daily', 'hkfn_cleanup_failed_uploads');
        // }

        // DISABLED: Schedule comprehensive maintenance (weekly)
        // if (!wp_next_scheduled('hkfn_video_maintenance')) {
        //     wp_schedule_event(time(), 'weekly', 'hkfn_video_maintenance');
        // }

        // Clear any existing schedules (cleanup from old installs)
        if (hkfn_get_constant('DISABLE_AUTO_VIDEO_CLEANUP')) {
            $timestamp = wp_next_scheduled('hkfn_cleanup_failed_uploads');
            if ($timestamp) wp_unschedule_event($timestamp, 'hkfn_cleanup_failed_uploads');

            $timestamp = wp_next_scheduled('hkfn_video_maintenance');
            if ($timestamp) wp_unschedule_event($timestamp, 'hkfn_video_maintenance');
        }

        // Frontend hooks for displaying videos
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        add_filter('hkfn_memorial_video_button', [$this, 'render_video_button'], 10, 2);
        add_filter('hkfn_memorial_video_modal', [$this, 'render_video_modal'], 10, 2);
    }

    /**
     * Initialize frontend functionality
     */
    protected function init_frontend(): void {
        if (!$this->has_premium_license()) {
            return;
        }

        // Frontend video display functionality
        add_action('wp_footer', [$this, 'render_video_modals']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
    }

    /**
     * Get module features
     */
    public function get_features(): array {
        return [
            'Video Upload & Validation',
            'Modal Video Players',
            'Thumbnail Generation',
            'Mobile-Responsive Players'
        ];
    }

    /**
     * Get video hosting credentials from WordPress constants
     *
     * These should be defined in wp-config.php and never committed to version control:
     * define('HKFN_BUNNYSTREAM_LIBRARY_ID', 'your_library_id');
     * define('HKFN_BUNNYSTREAM_API_KEY', 'your_api_key');
     *
     * Also supports legacy constant names for backward compatibility:
     * define('HKFN_VIDEO_LIBRARY_ID', 'your_library_id');
     * define('HKFN_VIDEO_API_KEY', 'your_api_key');
     */
    private function get_bunny_credentials(): array {
        // Check new names first, then legacy names; each also falls back
        // to the WFN_-prefixed v2.x constant via hkfn_get_constant().
        $library_id = hkfn_get_constant('BUNNYSTREAM_LIBRARY_ID') ?: hkfn_get_constant('VIDEO_LIBRARY_ID') ?: '';
        $api_key = hkfn_get_constant('BUNNYSTREAM_API_KEY') ?: hkfn_get_constant('VIDEO_API_KEY') ?: '';

        return [
            'library_id' => $library_id,
            'api_key' => $api_key,
            'cdn_hostname' => '' // Not needed for basic video hosting
        ];
    }

    /**
     * Check if premium license is active
     */
    private function has_premium_license(): bool {
        // For testing purposes, allow temporary bypass with constant
        if (hkfn_get_constant('BYPASS_LICENSE')) {
            return true;
        }

        $license_status = hkfn_get_option('license_status', [
            'valid' => false,
            'features' => [],
            'expires' => '',
            'message' => 'No license key entered',
            'last_check' => ''
        ]);

        return $license_status['valid'] && in_array('video_hosting', $license_status['features'] ?? []);
    }

    /**
     * Handle video upload from ACF save_post hook
     */
    public function handle_video_upload($post_id): void {
        // Only process funeral notice posts
        if (get_post_type($post_id) !== 'funeral-notice') {
            return;
        }

        // Check if premium license is active
        if (!$this->has_premium_license()) {
            return;
        }

        // Get the memorial video field value
        // Get the video file from the nested media group
        $media_group = get_field('hkfn_media_group', $post_id);
        $video_file = $media_group['video_slideshow'] ?? null;

        if (!$video_file || !is_array($video_file)) {
            return;
        }

        // Check if this is a new upload or replacement
        $existing_video_id = get_post_meta($post_id, '_hkfn_video_id', true);

        if ($existing_video_id) {
            // This is a replacement - handle differently
            $this->handle_video_replacement($post_id, $video_file, $existing_video_id);
        } else {
            // This is a new upload
            $this->handle_new_video_upload($post_id, $video_file);
        }
    }

    /**
     * Handle new video upload
     */
    private function handle_new_video_upload(int $post_id, array $video_file): void {
        // Validate the file first
        $validation_result = $this->validate_video_file($video_file);

        if (!$validation_result['valid']) {
            $this->store_upload_error($post_id, $validation_result['message']);
            return;
        }

        // Store upload status as processing
        $this->update_upload_status($post_id, [
            'status' => 'validating',
            'progress' => 10,
            'message' => 'File validation completed',
            'file_info' => [
                'filename' => $video_file['filename'],
                'filesize' => $video_file['filesize'],
                'mime_type' => $video_file['mime_type']
            ]
        ]);

        // Schedule background upload processing
        $this->schedule_background_upload($post_id, $video_file);
    }

    /**
     * Handle video replacement
     */
    private function handle_video_replacement(int $post_id, array $video_file, string $existing_video_id): void {
        // Validate the new file
        $validation_result = $this->validate_video_file($video_file);

        if (!$validation_result['valid']) {
            $this->store_upload_error($post_id, $validation_result['message']);
            return;
        }

        // Store old video ID for cleanup after successful upload
        update_post_meta($post_id, '_hkfn_video_id_old', $existing_video_id);

        // Clear current video ID to indicate replacement in progress
        delete_post_meta($post_id, '_hkfn_video_id');

        $this->update_upload_status($post_id, [
            'status' => 'replacing',
            'progress' => 10,
            'message' => 'Replacing existing video',
            'old_video_id' => $existing_video_id
        ]);

        // Schedule background replacement processing
        $this->schedule_background_upload($post_id, $video_file, $existing_video_id);
    }

    /**
     * Validate video file before upload
     */
    private function validate_video_file(array $video_file): array {
        $settings = $this->get_settings();

        // Check file exists - convert URL to local file path
        if (!isset($video_file['url'])) {
            return [
                'valid' => false,
                'message' => 'Video file URL not provided'
            ];
        }

        // Convert URL to local file path
        $upload_dir = wp_upload_dir();
        $file_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $video_file['url']);

        if (!file_exists($file_path)) {
            return [
                'valid' => false,
                'message' => 'Video file not found or inaccessible'
            ];
        }

        // Check file size
        $max_size = $settings['max_file_size_mb'] * 1024 * 1024; // Convert MB to bytes
        if ($video_file['filesize'] > $max_size) {
            return [
                'valid' => false,
                'message' => sprintf(
                    'File size (%s) exceeds maximum limit of %d MB',
                    size_format($video_file['filesize']),
                    $settings['max_file_size_mb']
                )
            ];
        }

        // Check file format
        $file_extension = pathinfo($video_file['filename'], PATHINFO_EXTENSION);
        if (!in_array(strtolower($file_extension), $settings['allowed_formats'])) {
            return [
                'valid' => false,
                'message' => sprintf(
                    'File format "%s" is not allowed. Supported formats: %s',
                    $file_extension,
                    implode(', ', $settings['allowed_formats'])
                )
            ];
        }

        // Check MIME type
        $allowed_mime_types = [
            'video/mp4',
            'video/quicktime',
            'video/x-msvideo',
            'video/webm'
        ];

        if (!in_array($video_file['mime_type'], $allowed_mime_types)) {
            return [
                'valid' => false,
                'message' => 'Invalid video file type detected'
            ];
        }

        // Additional video-specific validation would go here
        // (duration check, resolution check, etc.)

        return ['valid' => true];
    }

    /**
     * Schedule background upload processing
     */
    private function schedule_background_upload(int $post_id, array $video_file, ?string $replace_video_id = null): void {
        $upload_data = [
            'post_id' => $post_id,
            'file_path' => $video_file['url'],
            'file_info' => $video_file,
            'replace_video_id' => $replace_video_id,
            'scheduled_at' => time(),
            'attempt_count' => 0
        ];

        // Store upload job data
        update_post_meta($post_id, '_hkfn_video_upload_job', $upload_data);

        // Use WordPress cron for reliable background processing
        $hook_name = 'hkfn_process_video_upload_cron';

        // Schedule processing in 30 seconds (allows page load to complete)
        wp_schedule_single_event(
            time() + 30,
            $hook_name,
            [$post_id]
        );

        // Also keep AJAX as fallback for manual triggers
        $ajax_url = admin_url('admin-ajax.php');
        $args = [
            'action' => 'hkfn_process_video_upload',
            'post_id' => $post_id,
            'nonce' => wp_create_nonce('hkfn_video_upload_' . $post_id)
        ];

        // Try AJAX as immediate fallback (non-blocking)
        wp_remote_post($ajax_url, [
            'body' => $args,
            'blocking' => false,
            'timeout' => 1,
            'sslverify' => false // Help with SSL issues
        ]);

        $this->update_upload_status($post_id, [
            'status' => 'queued',
            'progress' => 15,
            'message' => 'Upload queued for processing'
        ]);
    }

    /**
     * Process video upload in background
     */
    public function process_video_upload_background(): void {
        // Verify nonce
        $post_id = intval($_POST['post_id'] ?? 0);
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'hkfn_video_upload_' . $post_id)) {
            wp_die('Security check failed');
        }

        // Get upload job data
        $upload_job = get_post_meta($post_id, '_hkfn_video_upload_job', true);
        if (!$upload_job) {
            wp_die('Upload job not found');
        }

        // Increment attempt count
        $upload_job['attempt_count']++;
        update_post_meta($post_id, '_hkfn_video_upload_job', $upload_job);

        $this->update_upload_status($post_id, [
            'status' => 'uploading',
            'progress' => 20,
            'message' => 'Starting upload to video hosting service',
            'attempt' => $upload_job['attempt_count']
        ]);

        try {
            // Use shared processing logic
            $this->execute_video_upload_process($post_id, $upload_job);

        } catch (\Exception $e) {
            $this->handle_upload_failure($post_id, $e->getMessage(), $upload_job);
        }

        wp_die(); // Required for AJAX handlers
    }

    /**
     * Process video upload via WordPress cron (more reliable than AJAX)
     */
    public function process_video_upload_cron(int $post_id): void {
        // Get upload job data
        $upload_job = get_post_meta($post_id, '_hkfn_video_upload_job', true);
        if (!$upload_job) {
            error_log("WFN Cron: Upload job not found for post $post_id");
            return;
        }

        // Increment attempt count
        $upload_job['attempt_count']++;
        update_post_meta($post_id, '_hkfn_video_upload_job', $upload_job);

        $this->update_upload_status($post_id, [
            'status' => 'uploading',
            'progress' => 20,
            'message' => 'Starting upload to video hosting service (cron)',
            'attempt' => $upload_job['attempt_count']
        ]);

        try {
            // Use the same processing logic as AJAX method
            $this->execute_video_upload_process($post_id, $upload_job);
        } catch (\Exception $e) {
            error_log("WFN Cron Upload Error for post $post_id: " . $e->getMessage());
            $this->handle_upload_failure($post_id, $e->getMessage(), $upload_job);
        }
    }

    /**
     * Execute video upload process (shared between AJAX and cron)
     */
    private function execute_video_upload_process(int $post_id, array $upload_job): void {
        // Prepare metadata
        $post = get_post($post_id);
        $person_group = get_field('hkfn_person_group', $post_id) ?: [];

        $metadata = [
            'title' => sprintf(
                'Memorial Slideshow - %s %s',
                $person_group['firstname'] ?? 'Memorial',
                $person_group['lastname'] ?? 'Slideshow'
            ),
            'description' => 'Memorial video slideshow for ' . ($post->post_title ?? 'Unknown'),
            'post_id' => $post_id,
            'site_domain' => parse_url(get_site_url(), PHP_URL_HOST),
            'created_by' => get_current_user_id(),
            'original_filename' => $upload_job['file_info']['filename']
        ];

        $this->update_upload_status($post_id, [
            'status' => 'uploading',
            'progress' => 30,
            'message' => 'Uploading to video hosting servers'
        ]);

        // Convert URL to local file path
        $upload_dir = wp_upload_dir();
        $local_file_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $upload_job['file_path']);

        // Verify file exists before upload
        if (!file_exists($local_file_path)) {
            throw new \Exception('Video file not found at local path: ' . $local_file_path . ' (converted from: ' . $upload_job['file_path'] . ')');
        }

        // Upload to Bunny Stream
        $upload_result = $this->bunny_service->upload_video($local_file_path, $metadata);

        if (!$upload_result['success']) {
            throw new \Exception('Video hosting upload failed: ' . $upload_result['message']);
        }

        $video_id = $upload_result['video_id'];

        $this->update_upload_status($post_id, [
            'status' => 'processing',
            'progress' => 60,
            'message' => 'Video uploaded, processing for playback',
            'video_id' => $video_id
        ]);

        // Store video metadata
        $this->store_video_metadata($post_id, [
            'video_id' => $video_id,
            'upload_result' => $upload_result,
            'file_info' => $upload_job['file_info'],
            'uploaded_at' => current_time('mysql')
        ]);

        // Clean up local file from Media Library after successful upload
        $this->cleanup_local_video_file($local_file_path, $upload_job);

        // Clean up old video if this was a replacement
        if (!empty($upload_job['replace_video_id'])) {
            $this->cleanup_old_video($post_id, $upload_job['replace_video_id']);
        }

        // Monitor transcoding status
        $this->monitor_transcoding_progress($post_id, $video_id);
    }

    /**
     * Store video metadata in post meta
     */
    private function store_video_metadata(int $post_id, array $metadata): void {
        update_post_meta($post_id, '_hkfn_video_id', $metadata['video_id']);
        update_post_meta($post_id, '_hkfn_video_metadata', $metadata);
        update_post_meta($post_id, '_hkfn_video_status', 'uploaded');

        // Clear upload job data
        delete_post_meta($post_id, '_hkfn_video_upload_job');
    }

    /**
     * Monitor transcoding progress
     */
    private function monitor_transcoding_progress(int $post_id, string $video_id): void {
        $max_checks = 20; // Maximum number of status checks
        $check_count = 0;

        while ($check_count < $max_checks) {
            sleep(10); // Wait 10 seconds between checks
            $check_count++;

            $status = $this->bunny_service->get_transcoding_status($video_id);

            if (!$status['success']) {
                break;
            }

            $transcoding_status = $status['transcoding_status'];
            $progress = 60 + ($transcoding_status['processing_progress'] * 0.35); // Map to 60-95%

            $this->update_upload_status($post_id, [
                'status' => 'processing',
                'progress' => round($progress),
                'message' => sprintf(
                    'Transcoding: %d%% complete',
                    $transcoding_status['processing_progress']
                ),
                'transcoding_status' => $transcoding_status
            ]);

            // Check if transcoding is complete
            if ($transcoding_status['overall_status'] === 'completed') {
                $this->finalize_video_upload($post_id, $video_id);
                return;
            }

            if ($transcoding_status['overall_status'] === 'failed') {
                throw new \Exception('Video transcoding failed: ' . implode(', ', $transcoding_status['errors']));
            }
        }

        // If we reach here, transcoding is taking too long
        $this->update_upload_status($post_id, [
            'status' => 'processing',
            'progress' => 90,
            'message' => 'Video is still processing, this may take a few more minutes'
        ]);
    }

    /**
     * Finalize video upload process
     */
    private function finalize_video_upload(int $post_id, string $video_id): void {
        $this->update_upload_status($post_id, [
            'status' => 'completed',
            'progress' => 100,
            'message' => 'Memorial video ready for viewing',
            'completed_at' => current_time('mysql')
        ]);

        update_post_meta($post_id, '_hkfn_video_status', 'ready');
        update_post_meta($post_id, '_hkfn_video_id', $video_id);

        // Get video info and store complete data
        $video_info = $this->bunny_service->get_video_info($video_id);
        if ($video_info['success']) {
            update_post_meta($post_id, '_hkfn_video_data', json_encode($video_info['data']));
        }

        // Send notification if enabled
        $settings = $this->get_settings();
        if ($settings['notify_on_completion']) {
            $this->send_completion_notification($post_id, $video_id);
        }

        // Clear upload status after success
        delete_post_meta($post_id, '_hkfn_video_upload_status');
    }

    /**
     * Handle upload failure
     */
    private function handle_upload_failure(int $post_id, string $error_message, array $upload_job): void {
        $max_attempts = 3;

        if ($upload_job['attempt_count'] < $max_attempts) {
            // Retry the upload
            $this->update_upload_status($post_id, [
                'status' => 'retrying',
                'progress' => 5,
                'message' => sprintf('Upload failed, retrying (attempt %d/%d)', $upload_job['attempt_count'], $max_attempts),
                'last_error' => $error_message
            ]);

            // Schedule retry after delay
            wp_schedule_single_event(time() + (60 * $upload_job['attempt_count']), 'hkfn_retry_video_upload', [$post_id]);

        } else {
            // Maximum attempts reached, mark as failed
            $this->update_upload_status($post_id, [
                'status' => 'failed',
                'progress' => 0,
                'message' => 'Upload failed after maximum retries: ' . $error_message,
                'failed_at' => current_time('mysql')
            ]);

            update_post_meta($post_id, '_hkfn_video_status', 'failed');

            // Send failure notification if enabled
            $settings = $this->get_settings();
            if ($settings['notify_on_failure']) {
                $this->send_failure_notification($post_id, $error_message);
            }
        }
    }

    /**
     * Update upload status
     */
    private function update_upload_status(int $post_id, array $status): void {
        $status['updated_at'] = current_time('mysql');
        update_post_meta($post_id, '_hkfn_video_upload_status', $status);
    }

    /**
     * Store upload error
     */
    private function store_upload_error(int $post_id, string $error_message): void {
        $this->update_upload_status($post_id, [
            'status' => 'error',
            'progress' => 0,
            'message' => $error_message,
            'error_at' => current_time('mysql')
        ]);

        update_post_meta($post_id, '_hkfn_video_status', 'error');
    }

    /**
     * Render module admin content
     */
    protected function render_module_admin_content(): void {
        if (!$this->has_premium_license()) {
            $this->render_license_required_notice();
            return;
        }

        $settings = $this->get_settings();

        ?>
        <form method="post" action="">
            <?php $this->render_nonce_field(); ?>

            <div class="hkfn-settings-grid">
                <!-- Upload Settings -->
                <div class="hkfn-settings-card">
                    <h3>Upload Configuration</h3>

                    <div class="hkfn-form-row">
                        <label for="max_file_size_mb">Maximum File Size (MB)</label>
                        <input type="number"
                               id="max_file_size_mb"
                               name="hkfn_module_settings[max_file_size_mb]"
                               value="<?php echo esc_attr($settings['max_file_size_mb']); ?>"
                               min="10"
                               max="1000"
                               step="10">
                        <p class="description">Maximum allowed file size for video uploads (10-1000 MB)</p>
                    </div>

                    <div class="hkfn-form-row">
                        <label for="max_duration_minutes">Maximum Duration (Minutes)</label>
                        <input type="number"
                               id="max_duration_minutes"
                               name="hkfn_module_settings[max_duration_minutes]"
                               value="<?php echo esc_attr($settings['max_duration_minutes']); ?>"
                               min="5"
                               max="120"
                               step="5">
                        <p class="description">Maximum allowed video duration (5-120 minutes)</p>
                    </div>

                    <div class="hkfn-form-row">
                        <label for="allowed_formats">Allowed File Formats</label>
                        <div class="hkfn-checkbox-group">
                            <?php
                            $all_formats = ['mp4', 'mov', 'avi', 'webm', 'mkv'];
                            foreach ($all_formats as $format):
                            ?>
                                <label class="hkfn-checkbox-label">
                                    <input type="checkbox"
                                           name="hkfn_module_settings[allowed_formats][]"
                                           value="<?php echo esc_attr($format); ?>"
                                           <?php checked(in_array($format, $settings['allowed_formats'])); ?>>
                                    <?php echo strtoupper($format); ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <p class="description">Select which video formats to allow for upload</p>
                    </div>
                </div>

                <!-- Processing Settings -->
                <div class="hkfn-settings-card">
                    <h3>Processing Configuration</h3>

                    <div class="hkfn-form-row">
                        <label for="quality_preset">Quality Preset</label>
                        <select id="quality_preset" name="hkfn_module_settings[quality_preset]">
                            <option value="fast" <?php selected($settings['quality_preset'], 'fast'); ?>>Fast (Lower Quality)</option>
                            <option value="balanced" <?php selected($settings['quality_preset'], 'balanced'); ?>>Balanced (Recommended)</option>
                            <option value="high_quality" <?php selected($settings['quality_preset'], 'high_quality'); ?>>High Quality (Slower)</option>
                        </select>
                        <p class="description">Choose encoding quality vs speed tradeoff</p>
                    </div>

                    <div class="hkfn-form-group">
                        <label class="hkfn-toggle-switch">
                            <input type="checkbox"
                                   name="hkfn_module_settings[auto_transcode]"
                                   value="1"
                                   <?php checked($settings['auto_transcode']); ?>>
                            <span class="hkfn-toggle-slider"></span>
                            <span class="hkfn-toggle-label">Auto-Transcode Videos</span>
                        </label>
                        <p class="hkfn-form-description">Automatically transcode videos for optimal streaming</p>
                    </div>

                    <div class="hkfn-form-group">
                        <label class="hkfn-toggle-switch">
                            <input type="checkbox"
                                   name="hkfn_module_settings[enable_thumbnails]"
                                   value="1"
                                   <?php checked($settings['enable_thumbnails']); ?>>
                            <span class="hkfn-toggle-slider"></span>
                            <span class="hkfn-toggle-label">Generate Thumbnails</span>
                        </label>
                        <p class="hkfn-form-description">Automatically generate video thumbnails</p>
                    </div>
                </div>

                <!-- Notification Settings -->
                <div class="hkfn-settings-card">
                    <h3>Notifications</h3>

                    <div class="hkfn-form-group">
                        <label class="hkfn-toggle-switch">
                            <input type="checkbox"
                                   name="hkfn_module_settings[notify_on_completion]"
                                   value="1"
                                   <?php checked($settings['notify_on_completion']); ?>>
                            <span class="hkfn-toggle-slider"></span>
                            <span class="hkfn-toggle-label">Notify on Upload Success</span>
                        </label>
                        <p class="hkfn-form-description">Send email notification when video processing completes</p>
                    </div>

                    <div class="hkfn-form-group">
                        <label class="hkfn-toggle-switch">
                            <input type="checkbox"
                                   name="hkfn_module_settings[notify_on_failure]"
                                   value="1"
                                   <?php checked($settings['notify_on_failure']); ?>>
                            <span class="hkfn-toggle-slider"></span>
                            <span class="hkfn-toggle-label">Notify on Upload Failure</span>
                        </label>
                        <p class="hkfn-form-description">Send email notification when video processing fails</p>
                    </div>
                </div>

                <!-- Video Hosting Status -->
                <div class="hkfn-settings-card">
                    <h3>Professional Video Hosting</h3>
                    <?php $is_configured = $this->bunny_service->is_configured(); ?>

                    <?php if ($is_configured): ?>
                        <p>Your memorial videos are hosted on enterprise-grade infrastructure with global CDN delivery for optimal viewing experience.</p>

                        <div class="hkfn-service-status">
                            <div class="hkfn-status-item">
                                <span class="hkfn-status-label">Hosting Service:</span>
                                <span class="hkfn-status-value hkfn-status-success">Active</span>
                            </div>
                            <div class="hkfn-status-item">
                                <span class="hkfn-status-label">CDN Delivery:</span>
                                <span class="hkfn-status-value hkfn-status-success">Global</span>
                            </div>
                            <div class="hkfn-status-item">
                                <span class="hkfn-status-label">Video Quality:</span>
                                <span class="hkfn-status-value">Auto-Optimized</span>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="hkfn-notice hkfn-notice-warning">
                            <p><strong>Video hosting not configured.</strong></p>
                            <p>To enable video hosting, add the following constants to your <code>wp-config.php</code> file:</p>
                            <pre><code>define('HKFN_BUNNYSTREAM_LIBRARY_ID', 'your_library_id');
define('HKFN_BUNNYSTREAM_API_KEY', 'your_api_key');</code></pre>
                            <p><em>Contact support for hosting service credentials.</em></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php $this->render_submit_button(); ?>
        </form>

        <!-- Video Management Section -->
        <div class="hkfn-settings-section">
            <h2>Video Management</h2>
            <?php $this->render_video_management_section(); ?>
        </div>
        <?php
    }

    /**
     * Render license required notice
     */
    private function render_license_required_notice(): void {
        ?>
        <div class="hkfn-license-required">
            <div class="hkfn-notice hkfn-notice-warning">
                <h3>Premium License Required</h3>
                <p>The Memorial Video feature requires an active premium license to enable video hosting and management capabilities.</p>

                <div class="hkfn-license-actions">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=hkfn-module-license')); ?>" class="button button-primary">
                        Configure License
                    </a>
                    <a href="https://humankindwebsites.com/pricing" target="_blank" class="button button-secondary">
                        View Pricing Plans
                    </a>
                </div>
            </div>

            <div class="hkfn-feature-preview">
                <h4>Premium Video Features Include:</h4>
                <ul class="hkfn-feature-list">
                    <li>Professional video hosting via secure CDN</li>
                    <li>Automatic video transcoding and optimization</li>
                    <li>Mobile-responsive video players</li>
                    <li>Upload progress tracking</li>
                    <li>Automatic thumbnail generation</li>
                    <li>Site-specific cost tracking</li>
                    <li>Video statistics and analytics</li>
                    <li>Secure, fast CDN delivery worldwide</li>
                </ul>
            </div>
        </div>
        <?php
    }

    /**
     * Render video management section
     */
    private function render_video_management_section(): void {
        // Get all posts with videos
        $video_posts = get_posts([
            'post_type' => 'funeral-notice',
            'meta_query' => [
                [
                    'key' => '_hkfn_video_id',
                    'compare' => 'EXISTS'
                ]
            ],
            'posts_per_page' => -1,
            'post_status' => 'any'
        ]);

        if (empty($video_posts)) {
            echo '<p>No memorial videos found.</p>';
            return;
        }

        ?>
        <div class="hkfn-video-management">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Memorial</th>
                        <th>Video Status</th>
                        <th>Upload Progress</th>
                        <th>File Info</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($video_posts as $post): ?>
                        <?php $this->render_video_management_row($post); ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Video Statistics -->
        <?php $this->render_video_statistics(); ?>

        <!-- Maintenance Controls -->
        <?php // REMOVED: Maintenance UI disabled after 2025-10-20 incident - manual-only cleanup via scripts ?>
        <?php // $this->render_maintenance_controls(); ?>
        <?php
    }

    /**
     * Render individual video management row
     */
    private function render_video_management_row(\WP_Post $post): void {
        $video_id = get_post_meta($post->ID, '_hkfn_video_id', true);
        $video_status = get_post_meta($post->ID, '_hkfn_video_status', true) ?: 'unknown';
        $upload_status = get_post_meta($post->ID, '_hkfn_video_upload_status', true) ?: [];
        $video_metadata = get_post_meta($post->ID, '_hkfn_video_metadata', true) ?: [];

        ?>
        <tr>
            <td>
                <strong><?php echo esc_html($post->post_title); ?></strong><br>
                <small>ID: <?php echo $post->ID; ?></small>
            </td>
            <td>
                <span class="hkfn-video-status hkfn-video-status-<?php echo esc_attr($video_status); ?>">
                    <?php echo esc_html(ucfirst($video_status)); ?>
                </span>
                <?php if ($video_id): ?>
                    <br><small>Video ID: <?php echo esc_html($video_id); ?></small>
                <?php endif; ?>
            </td>
            <td>
                <?php if (!empty($upload_status)): ?>
                    <div class="hkfn-progress-bar">
                        <div class="hkfn-progress-fill" style="width: <?php echo intval($upload_status['progress'] ?? 0); ?>%"></div>
                    </div>
                    <small><?php echo esc_html($upload_status['message'] ?? 'Processing...'); ?></small>
                <?php else: ?>
                    <span class="hkfn-status-complete">Complete</span>
                <?php endif; ?>
            </td>
            <td>
                <?php if (!empty($video_metadata['file_info'])): ?>
                    <small>
                        <?php echo esc_html($video_metadata['file_info']['filename']); ?><br>
                        Size: <?php echo size_format($video_metadata['file_info']['filesize']); ?>
                    </small>
                <?php else: ?>
                    <span class="hkfn-text-muted">No info available</span>
                <?php endif; ?>
            </td>
            <td>
                <div class="hkfn-video-actions">
                    <?php if ($video_id && $video_status === 'ready'): ?>
                        <button class="button button-small hkfn-preview-video" data-video-id="<?php echo esc_attr($video_id); ?>" data-post-id="<?php echo $post->ID; ?>">
                            Preview
                        </button>
                    <?php endif; ?>

                    <button class="button button-small button-secondary hkfn-delete-video" data-post-id="<?php echo $post->ID; ?>" data-video-id="<?php echo esc_attr($video_id); ?>">
                        Delete
                    </button>
                </div>
            </td>
        </tr>
        <?php
    }

    // Additional methods for AJAX handlers, frontend rendering, etc. will be added in the next part...

    /**
     * AJAX handler for upload progress
     */
    public function ajax_upload_progress(): void {
        $post_id = intval($_POST['post_id'] ?? 0);
        if (!$post_id) {
            wp_send_json_error('Invalid post ID');
        }

        // Use same nonce as status since they're similar operations
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'hkfn_video_status_' . $post_id)) {
            wp_send_json_error('Security check failed');
        }

        $upload_status = get_post_meta($post_id, '_hkfn_video_upload_status', true) ?: [];

        wp_send_json_success($upload_status);
    }

    /**
     * AJAX handler for upload status check
     */
    public function ajax_upload_status(): void {
        $post_id = intval($_POST['post_id'] ?? 0);
        if (!$post_id) {
            wp_send_json_error('Invalid post ID');
        }

        // Verify nonce with post ID
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'hkfn_video_status_' . $post_id)) {
            wp_send_json_error('Security check failed');
        }

        $video_status = get_post_meta($post_id, '_hkfn_video_status', true) ?: 'unknown';
        $upload_status = get_post_meta($post_id, '_hkfn_video_upload_status', true) ?: [];

        wp_send_json_success([
            'video_status' => $video_status,
            'upload_status' => $upload_status
        ]);
    }

    /**
     * AJAX handler for retry video upload
     */
    public function ajax_retry_upload(): void {
        $post_id = intval($_POST['post_id'] ?? 0);
        if (!$post_id) {
            wp_send_json_error('Invalid post ID');
        }

        // Verify nonce with post ID
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'hkfn_video_retry_' . $post_id)) {
            wp_send_json_error('Security check failed');
        }

        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            wp_send_json_error('Insufficient permissions');
        }

        try {
            // Reset status and clear any error states
            update_post_meta($post_id, '_hkfn_video_status', 'pending');
            update_post_meta($post_id, '_hkfn_video_upload_status', [
                'status' => 'retrying',
                'progress' => 0,
                'message' => 'Preparing to retry upload...',
                'retryable' => false // Disable retry while retrying
            ]);

            // Trigger the upload process again
            $this->handle_video_upload($post_id);

            wp_send_json_success([
                'message' => 'Upload retry initiated successfully'
            ]);

        } catch (\Exception $e) {
            error_log('WFN Video Upload Retry Error: ' . $e->getMessage());

            // Update status to show retry failed
            update_post_meta($post_id, '_hkfn_video_upload_status', [
                'status' => 'failed',
                'progress' => 0,
                'message' => 'Retry failed: ' . $e->getMessage(),
                'retryable' => true
            ]);

            wp_send_json_error('Retry failed: ' . $e->getMessage());
        }
    }

    /**
     * AJAX handler for video deletion
     */
    public function ajax_delete_video(): void {
        $post_id = intval($_POST['post_id'] ?? 0);
        $video_id = sanitize_text_field($_POST['video_id'] ?? '');

        if (!$post_id || !$video_id) {
            wp_send_json_error('Invalid parameters');
        }

        // Verify nonce with post ID
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'hkfn_video_delete_' . $post_id)) {
            wp_send_json_error('Security check failed');
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        // Delete video from Bunny Stream
        $delete_result = $this->bunny_service->delete_video($video_id);

        if ($delete_result['success']) {
            // Clean up local metadata
            delete_post_meta($post_id, '_hkfn_video_id');
            delete_post_meta($post_id, '_hkfn_video_metadata');
            delete_post_meta($post_id, '_hkfn_video_status');
            delete_post_meta($post_id, '_hkfn_video_upload_status');

            wp_send_json_success('Video deleted successfully');
        } else {
            wp_send_json_error('Failed to delete video: ' . $delete_result['message']);
        }
    }

    /**
     * Clean up video when post is deleted
     *
     * Only deletes videos that were uploaded by the current site.
     * This prevents accidental deletion when posts are migrated between sites.
     */
    public function cleanup_video_on_post_delete(int $post_id): void {
        if (get_post_type($post_id) !== 'funeral-notice') {
            return;
        }

        $video_id = get_post_meta($post_id, '_hkfn_video_id', true);
        if (empty($video_id)) {
            return; // No video to delete
        }

        // Check if this site uploaded the video (migration safety)
        $source_site = get_post_meta($post_id, '_hkfn_bunny_video_source_site', true);
        $current_site = get_site_url();

        if (!empty($source_site) && $source_site !== $current_site) {
            error_log("WFN VideoModule: Skipping video deletion for post {$post_id} - video {$video_id} belongs to {$source_site}, not {$current_site}");
            return;
        }

        // If no source site is recorded, assume current site owns it (backward compatibility)
        if (empty($source_site)) {
            error_log("WFN VideoModule: No source site recorded for video {$video_id} - assuming current site ownership for backward compatibility");
        }

        // Delete video from Bunny CDN
        $delete_result = $this->bunny_service->delete_video($video_id);

        if ($delete_result['success']) {
            error_log("WFN: Successfully deleted video {$video_id} from Bunny CDN when deleting post {$post_id}");
        } else {
            error_log("WFN: Failed to delete video {$video_id} from Bunny CDN when deleting post {$post_id}: " . ($delete_result['message'] ?? 'Unknown error'));
        }

        // Also clean up any incomplete upload session from direct upload system
        $session = get_post_meta($post_id, '_hkfn_video_upload_session', true);
        if (is_array($session) && !empty($session['video_id']) && $session['video_id'] !== $video_id) {
            // There's a different video in the session (incomplete upload)
            $this->bunny_service->delete_video($session['video_id']);
            error_log("WFN: Also cleaned up incomplete upload session video {$session['video_id']} from Bunny CDN");
        }
    }

    /**
     * Clean up failed uploads
     */
    public function cleanup_failed_uploads(): void {
        $settings = $this->get_settings();
        $retention_days = $settings['retention_days'];

        // Find posts with failed upload status older than retention period
        $failed_uploads = get_posts([
            'post_type' => 'funeral-notice',
            'meta_query' => [
                [
                    'key' => '_hkfn_video_status',
                    'value' => ['failed', 'error'],
                    'compare' => 'IN'
                ]
            ],
            'posts_per_page' => -1,
            'date_query' => [
                [
                    'before' => date('Y-m-d', strtotime("-{$retention_days} days"))
                ]
            ]
        ]);

        foreach ($failed_uploads as $post) {
            $video_id = get_post_meta($post->ID, '_hkfn_video_id', true);
            if ($video_id) {
                $this->bunny_service->delete_video($video_id);
            }

            // Clean up metadata
            delete_post_meta($post->ID, '_hkfn_video_id');
            delete_post_meta($post->ID, '_hkfn_video_metadata');
            delete_post_meta($post->ID, '_hkfn_video_status');
            delete_post_meta($post->ID, '_hkfn_video_upload_status');
            delete_post_meta($post->ID, '_hkfn_video_upload_job');
        }
    }

    /**
     * Clean up orphaned videos (videos in Bunny Stream with no corresponding WordPress post)
     *
     * ⚠️ DEPRECATED AND DISABLED - November 2025
     *
     * This function is too dangerous for production use. It has caused multiple incidents
     * of accidental video deletion across shared library configurations.
     *
     * INCIDENTS:
     * - October 20, 2025: Deleted all videos from Gee & Hickton
     * - November 21, 2025: Deleted videos from Lychgate
     *
     * REPLACEMENT: Use manual admin review tool for orphaned video management.
     * Videos are precious, irreplaceable memorial content. Automated deletion is unacceptable risk.
     *
     * @deprecated 2.5.3 Use manual cleanup via admin dashboard only
     * @return array Empty result - function disabled
     */
    public function cleanup_orphaned_videos(): array {
        $results = [
            'found' => 0,
            'deleted' => 0,
            'errors' => 0,
            'messages' => ['⚠️ AUTOMATIC CLEANUP PERMANENTLY DISABLED - Use manual admin review instead']
        ];

        // PERMANENTLY DISABLED - Too dangerous for production
        error_log('WFN: cleanup_orphaned_videos() called but permanently disabled for safety');
        return $results;

        try {
            // COLLECTION-AWARE CLEANUP: Only check videos in THIS site's collection
            // This prevents deleting videos from other sites in the shared library
            $site_domain = parse_url(get_site_url(), PHP_URL_HOST);

            // Get this site's collection ID
            $site_collections = $this->bunny_service->get_site_collections();

            if (!$site_collections['success']) {
                $results['errors']++;
                $results['messages'][] = 'Failed to fetch site collections: ' . $site_collections['message'];
                return $results;
            }

            // Find this site's collection
            $site_collection_id = null;
            foreach ($site_collections['site_collections'] as $collection) {
                if ($collection['site_domain'] === $site_domain) {
                    $site_collection_id = $collection['collection_id'];
                    break;
                }
            }

            if (!$site_collection_id) {
                $results['messages'][] = "No collection found for site: {$site_domain} - skipping cleanup";
                return $results;
            }

            // Get only videos in THIS site's collection
            $collection_stats = $this->bunny_service->get_collection_statistics($site_collection_id);

            if (!$collection_stats['success']) {
                $results['errors']++;
                $results['messages'][] = 'Failed to fetch collection videos: ' . $collection_stats['message'];
                return $results;
            }

            $bunny_videos = $collection_stats['videos'] ?? [];
            $results['found'] = count($bunny_videos);
            $results['messages'][] = "Checking {$results['found']} videos in collection for site: {$site_domain}";

            foreach ($bunny_videos as $video) {
                $video_id = $video['guid'];

                // Check if this video ID exists in any post on THIS site
                $posts = get_posts([
                    'post_type' => 'funeral-notice',
                    'meta_query' => [
                        [
                            'key' => '_hkfn_video_id',
                            'value' => $video_id,
                            'compare' => '='
                        ]
                    ],
                    'posts_per_page' => 1,
                    'fields' => 'ids'
                ]);

                // If no posts found, this video is orphaned IN THIS SITE'S COLLECTION
                if (empty($posts)) {
                    $delete_result = $this->bunny_service->delete_video($video_id);

                    if ($delete_result['success']) {
                        $results['deleted']++;
                        $results['messages'][] = "Deleted orphaned video from {$site_domain} collection: {$video_id} ({$video['title']})";
                    } else {
                        $results['errors']++;
                        $results['messages'][] = "Failed to delete orphaned video {$video_id}: {$delete_result['message']}";
                    }
                }
            }

        } catch (\Exception $e) {
            $results['errors']++;
            $results['messages'][] = 'Orphaned video cleanup failed: ' . $e->getMessage();
        }

        return $results;
    }

    /**
     * Clean up incomplete uploads (uploads stuck in processing for too long)
     */
    public function cleanup_stuck_uploads(): array {
        $results = [
            'found' => 0,
            'cleaned' => 0,
            'errors' => 0,
            'messages' => []
        ];

        // Find uploads that have been processing for more than 24 hours
        $stuck_uploads = get_posts([
            'post_type' => 'funeral-notice',
            'meta_query' => [
                [
                    'key' => '_hkfn_video_status',
                    'value' => ['uploading', 'processing'],
                    'compare' => 'IN'
                ]
            ],
            'posts_per_page' => -1,
            'date_query' => [
                [
                    'before' => date('Y-m-d H:i:s', strtotime('-24 hours')),
                    'column' => 'post_modified'
                ]
            ]
        ]);

        $results['found'] = count($stuck_uploads);

        foreach ($stuck_uploads as $post) {
            try {
                $video_id = get_post_meta($post->ID, '_hkfn_video_id', true);

                // Check if video still exists and its actual status
                if ($video_id) {
                    $video_info = $this->bunny_service->get_video_info($video_id);

                    if ($video_info['success'] && $video_info['status'] === 'finished') {
                        // Video is actually ready, update status
                        update_post_meta($post->ID, '_hkfn_video_status', 'ready');
                        update_post_meta($post->ID, '_hkfn_video_data', $video_info['data']);
                        delete_post_meta($post->ID, '_hkfn_video_upload_status');

                        $results['messages'][] = "Fixed stuck upload for: {$post->post_title} (ID: {$post->ID})";
                    } else {
                        // Video failed or doesn't exist, clean up
                        if ($video_id) {
                            $this->bunny_service->delete_video($video_id);
                        }

                        update_post_meta($post->ID, '_hkfn_video_status', 'failed');
                        update_post_meta($post->ID, '_hkfn_video_upload_status', [
                            'status' => 'failed',
                            'message' => 'Upload timed out - cleaned up by maintenance task',
                            'failed_at' => current_time('mysql')
                        ]);

                        $results['messages'][] = "Cleaned stuck upload for: {$post->post_title} (ID: {$post->ID})";
                    }
                } else {
                    // No video ID, just reset status
                    delete_post_meta($post->ID, '_hkfn_video_status');
                    delete_post_meta($post->ID, '_hkfn_video_upload_status');
                    delete_post_meta($post->ID, '_hkfn_video_upload_job');

                    $results['messages'][] = "Reset stuck upload status for: {$post->post_title} (ID: {$post->ID})";
                }

                $results['cleaned']++;

            } catch (\Exception $e) {
                $results['errors']++;
                $results['messages'][] = "Error processing {$post->post_title}: {$e->getMessage()}";
            }
        }

        return $results;
    }

    /**
     * Run comprehensive maintenance tasks
     *
     * ⚠️ DEPRECATED AND DISABLED - November 2025
     *
     * @deprecated 2.5.3 Automatic maintenance disabled - manual cleanup only
     * @return array Empty result - function disabled
     */
    public function run_maintenance(): array {
        error_log('WFN: run_maintenance() called but permanently disabled for safety');

        return [
            'started_at' => current_time('mysql'),
            'completed_at' => current_time('mysql'),
            'total_errors' => 0,
            'tasks' => [],
            'disabled' => true,
            'message' => '⚠️ AUTOMATIC MAINTENANCE PERMANENTLY DISABLED - Too dangerous for production use'
        ];

        $overall_results = [
            'started_at' => current_time('mysql'),
            'completed_at' => null,
            'total_errors' => 0,
            'tasks' => []
        ];

        // Task 1: Clean up failed uploads
        $failed_results = ['found' => 0, 'cleaned' => 0, 'errors' => 0, 'messages' => []];
        try {
            $this->cleanup_failed_uploads();
            $failed_results['messages'][] = 'Failed upload cleanup completed successfully';
        } catch (\Exception $e) {
            $failed_results['errors']++;
            $failed_results['messages'][] = 'Failed upload cleanup error: ' . $e->getMessage();
        }

        $overall_results['tasks']['failed_uploads'] = $failed_results;
        $overall_results['total_errors'] += $failed_results['errors'];

        // Task 2: Clean up orphaned videos
        $orphaned_results = $this->cleanup_orphaned_videos();
        $overall_results['tasks']['orphaned_videos'] = $orphaned_results;
        $overall_results['total_errors'] += $orphaned_results['errors'];

        // Task 3: Clean up stuck uploads
        $stuck_results = $this->cleanup_stuck_uploads();
        $overall_results['tasks']['stuck_uploads'] = $stuck_results;
        $overall_results['total_errors'] += $stuck_results['errors'];

        // Task 4: Update statistics
        $stats_results = $this->update_video_statistics();
        $overall_results['tasks']['statistics'] = $stats_results;
        $overall_results['total_errors'] += $stats_results['errors'];

        $overall_results['completed_at'] = current_time('mysql');

        // Store maintenance log
        update_option('hkfn_video_last_maintenance', $overall_results);

        return $overall_results;
    }

    /**
     * Update video statistics and usage data
     */
    private function update_video_statistics(): array {
        $results = [
            'found' => 0,
            'updated' => 0,
            'errors' => 0,
            'messages' => []
        ];

        try {
            // Get all posts with videos
            $video_posts = get_posts([
                'post_type' => 'funeral-notice',
                'meta_query' => [
                    [
                        'key' => '_hkfn_video_id',
                        'compare' => 'EXISTS'
                    ]
                ],
                'posts_per_page' => -1,
                'fields' => 'ids'
            ]);

            $results['found'] = count($video_posts);

            // Calculate summary statistics
            $total_videos = 0;
            $ready_videos = 0;
            $failed_videos = 0;
            $processing_videos = 0;
            $total_storage_mb = 0;
            $total_bandwidth_mb = 0;

            foreach ($video_posts as $post_id) {
                $video_status = get_post_meta($post_id, '_hkfn_video_status', true);
                $video_data = get_post_meta($post_id, '_hkfn_video_data', true);

                $total_videos++;

                switch ($video_status) {
                    case 'ready':
                        $ready_videos++;
                        break;
                    case 'failed':
                        $failed_videos++;
                        break;
                    case 'processing':
                    case 'uploading':
                        $processing_videos++;
                        break;
                }

                // Add to storage calculation
                if (!empty($video_data['file_size'])) {
                    $total_storage_mb += ($video_data['file_size'] / 1024 / 1024);
                }
            }

            // Update statistics
            $statistics = [
                'total_videos' => $total_videos,
                'ready_videos' => $ready_videos,
                'failed_videos' => $failed_videos,
                'processing_videos' => $processing_videos,
                'total_storage_mb' => round($total_storage_mb, 2),
                'last_updated' => current_time('mysql')
            ];

            update_option('hkfn_video_statistics', $statistics);

            $results['updated'] = 1;
            $results['messages'][] = "Statistics updated: {$total_videos} total videos, {$ready_videos} ready, {$failed_videos} failed";

        } catch (\Exception $e) {
            $results['errors']++;
            $results['messages'][] = 'Statistics update failed: ' . $e->getMessage();
        }

        return $results;
    }

    /**
     * Get video statistics
     */
    public function get_video_statistics(): array {
        return hkfn_get_option('video_statistics', [
            'total_videos' => 0,
            'ready_videos' => 0,
            'failed_videos' => 0,
            'processing_videos' => 0,
            'total_storage_mb' => 0,
            'last_updated' => null
        ]);
    }

    /**
     * Send completion notification
     */
    private function send_completion_notification(int $post_id, string $video_id): void {
        $post = get_post($post_id);
        $admin_email = get_option('admin_email');

        $subject = 'Memorial Video Processing Complete - ' . $post->post_title;
        $message = sprintf(
            "The memorial video for %s has finished processing and is now ready for viewing.\n\n" .
            "Post: %s\n" .
            "Video ID: %s\n" .
            "View: %s\n",
            $post->post_title,
            get_edit_post_link($post_id),
            $video_id,
            get_permalink($post_id)
        );

        wp_mail($admin_email, $subject, $message);
    }

    /**
     * Send failure notification
     */
    private function send_failure_notification(int $post_id, string $error_message): void {
        $post = get_post($post_id);
        $admin_email = get_option('admin_email');

        $subject = 'Memorial Video Upload Failed - ' . $post->post_title;
        $message = sprintf(
            "The memorial video upload for %s has failed.\n\n" .
            "Post: %s\n" .
            "Error: %s\n" .
            "Edit: %s\n",
            $post->post_title,
            get_edit_post_link($post_id),
            $error_message,
            get_edit_post_link($post_id)
        );

        wp_mail($admin_email, $subject, $message);
    }

    /**
     * Enqueue admin assets for post editor and video settings page
     */
    public function enqueue_admin_assets($hook): void {
        // First call parent method to load the standard module CSS
        parent::enqueue_admin_assets($hook);

        // Only load post editor assets on funeral notice post editor
        global $post;
        if ($hook !== 'post.php' && $hook !== 'post-new.php') {
            return;
        }

        if (!isset($post) || $post->post_type !== 'funeral-notice') {
            return;
        }

        // Enqueue admin video upload styles for post editor
        wp_enqueue_style(
            'hkfn-video-upload-admin',
            HKFN_PLUGIN_URL . 'assets/css/admin/video-upload.css',
            [],
            $this->get_version()
        );

        // Enqueue direct upload JavaScript
        wp_enqueue_script(
            'hkfn-video-direct-upload',
            HKFN_PLUGIN_URL . 'assets/js/video-direct-upload.js',
            ['jquery', 'acf-input'],
            $this->get_version(),
            true
        );

        // Localize script with upload settings and REST API info
        wp_localize_script('hkfn-video-direct-upload', 'hkfnVideoUpload', [
            'postId' => $post->ID,
            'restUrl' => rest_url(),
            'nonce' => wp_create_nonce('wp_rest'),
            'hasLicense' => $this->has_premium_license() ? '1' : '',  // Pass as string for JS boolean check
            'licenseUrl' => admin_url('admin.php?page=hkfn-module-license'),
            'settings' => [
                'maxFileSize' => ($this->get_settings()['max_file_size_mb'] ?? 900) * 1024 * 1024, // Convert to bytes
                'allowedFormats' => $this->get_settings()['allowed_formats'] ?? ['mp4', 'mov', 'avi', 'webm'],
                'maxDuration' => ($this->get_settings()['max_duration_minutes'] ?? 30) * 60 // Convert to seconds
            ]
        ]);

        // Legacy localization for existing upload progress tracking
        wp_localize_script('acf-input', 'hkfnVideo', [
            'postId' => $post->ID,
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonces' => [
                'status' => wp_create_nonce('hkfn_video_status_' . $post->ID),
                'retry' => wp_create_nonce('hkfn_video_retry_' . $post->ID),
                'delete' => wp_create_nonce('hkfn_video_delete_' . $post->ID)
            ],
            'settings' => [
                'maxFileSize' => ($this->get_settings()['max_file_size_mb'] ?? 900) * 1024 * 1024, // Convert to bytes
                'allowedFormats' => $this->get_settings()['allowed_formats'] ?? ['mp4', 'mov', 'avi', 'webm'],
                'maxDuration' => ($this->get_settings()['max_duration_minutes'] ?? 30) * 60 // Convert to seconds
            ]
        ]);
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets(): void {
        if (!is_singular('funeral-notice')) {
            return;
        }

        global $post;

        // Check if video is ready (license check happens in TemplateManager)
        $video_status = get_post_meta($post->ID, '_hkfn_video_status', true);
        if ($video_status !== 'ready') {
            return;
        }

        wp_enqueue_style(
            'hkfn-video-player',
            HKFN_PLUGIN_URL . 'assets/css/video-player.css',
            [],
            $this->get_version()
        );

        wp_enqueue_script(
            'hkfn-video-player',
            HKFN_PLUGIN_URL . 'assets/js/video-player.js',
            ['jquery'],
            $this->get_version(),
            true
        );

        // Localize script with video data
        $video_id = get_post_meta($post->ID, '_hkfn_video_id', true);
        wp_localize_script('hkfn-video-player', 'hkfnVideo', [
            'postId' => $post->ID,
            'videoId' => $video_id ?: '',
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonces' => [
                'progress' => wp_create_nonce('hkfn_video_progress'),
                'status' => wp_create_nonce('hkfn_video_status')
            ]
        ]);
    }

    /**
     * Render video button for frontend
     */
    public function render_video_button(string $content, int $post_id): string {
        $video_id = get_post_meta($post_id, '_hkfn_video_id', true);
        $video_status = get_post_meta($post_id, '_hkfn_video_status', true);

        if (!$video_id || $video_status !== 'ready') {
            return $content;
        }

        $modal_result = $this->bunny_service->get_modal_embed_code($video_id, [
            'button_text' => 'View Slideshow',
            'button_class' => 'hkfn-video-button hkfn-memorial-video-btn'
        ]);

        if ($modal_result['success']) {
            return $content . $modal_result['modal_data']['button_html'];
        }

        return $content;
    }

    /**
     * Render video modal for frontend
     */
    public function render_video_modal(string $content, int $post_id): string {
        $video_id = get_post_meta($post_id, '_hkfn_video_id', true);
        $video_status = get_post_meta($post_id, '_hkfn_video_status', true);

        if (!$video_id || $video_status !== 'ready') {
            return $content;
        }

        $modal_result = $this->bunny_service->get_modal_embed_code($video_id);

        if ($modal_result['success']) {
            return $content . $modal_result['modal_data']['modal_html'];
        }

        return $content;
    }

    /**
     * Render all video modals in footer
     */
    public function render_video_modals(): void {
        if (!is_singular('funeral-notice')) {
            return;
        }

        global $post;
        echo apply_filters('hkfn_memorial_video_modal', '', $post->ID);
    }

    /**
     * Clean up local video file from Media Library after successful upload
     */
    private function cleanup_local_video_file(string $local_file_path, array $upload_job): void {
        try {
            // Get the attachment ID from the upload job if available
            $attachment_id = null;

            // Try to find the attachment ID by file path
            if (isset($upload_job['file_info']['attachment_id'])) {
                $attachment_id = $upload_job['file_info']['attachment_id'];
            } else {
                // Search for attachment by file path
                $upload_dir = wp_upload_dir();
                $relative_path = str_replace($upload_dir['basedir'] . '/', '', $local_file_path);

                global $wpdb;
                $attachment_id = $wpdb->get_var($wpdb->prepare(
                    "SELECT post_id FROM {$wpdb->postmeta}
                     WHERE meta_key = '_wp_attached_file'
                     AND meta_value LIKE %s",
                    '%' . $relative_path
                ));
            }

            if ($attachment_id) {
                // Only delete the physical files, keep the database record for ACF field reference
                $file_path = get_attached_file($attachment_id);
                $upload_dir = wp_upload_dir();

                // Delete the main file
                if ($file_path && file_exists($file_path)) {
                    $deleted = unlink($file_path);

                    // Also delete any generated thumbnails/sizes
                    $metadata = wp_get_attachment_metadata($attachment_id);
                    if (is_array($metadata) && isset($metadata['sizes'])) {
                        $path_parts = pathinfo($file_path);
                        foreach ($metadata['sizes'] as $size) {
                            $size_file = $path_parts['dirname'] . '/' . $size['file'];
                            if (file_exists($size_file)) {
                                unlink($size_file);
                            }
                        }
                    }

                    if ($deleted) {
                        error_log("WFN: Successfully cleaned up media files (ID: $attachment_id) after Bunny Stream upload - database record preserved");
                    } else {
                        error_log("WFN: Failed to delete media files for attachment (ID: $attachment_id)");
                    }
                }
            } else {
                // Fallback: try to delete the file directly if no attachment found
                if (file_exists($local_file_path)) {
                    $deleted = unlink($local_file_path);

                    if ($deleted) {
                        error_log("WFN: Successfully deleted local file after Bunny Stream upload: " . basename($local_file_path));
                    } else {
                        error_log("WFN: Failed to delete local file: " . $local_file_path);
                    }
                }
            }
        } catch (\Exception $e) {
            error_log("WFN: Error during local file cleanup: " . $e->getMessage());
            // Don't throw exception - cleanup failure shouldn't stop the upload process
        }
    }

    /**
     * Clean up old video during replacement
     */
    private function cleanup_old_video(int $post_id, string $old_video_id): void {
        // Delete old video from Bunny Stream
        $this->bunny_service->delete_video($old_video_id);

        // Clean up old video ID meta
        delete_post_meta($post_id, '_hkfn_video_id_old');
    }

    /**
     * AJAX handler for running maintenance tasks
     */
    public function ajax_run_maintenance(): void {
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'hkfn_video_maintenance')) {
            wp_send_json_error('Security check failed');
        }

        try {
            $results = $this->run_maintenance();
            wp_send_json_success([
                'message' => 'Maintenance completed successfully',
                'results' => $results
            ]);
        } catch (\Exception $e) {
            error_log('WFN Video Maintenance Error: ' . $e->getMessage());
            wp_send_json_error('Maintenance failed: ' . $e->getMessage());
        }
    }

    /**
     * AJAX handler for cleaning up orphaned videos
     */
    public function ajax_cleanup_orphaned_videos(): void {
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'hkfn_video_maintenance')) {
            wp_send_json_error('Security check failed');
        }

        try {
            $results = $this->cleanup_orphaned_videos();
            wp_send_json_success([
                'message' => "Orphaned video cleanup completed. Found: {$results['found']}, Deleted: {$results['deleted']}, Errors: {$results['errors']}",
                'results' => $results
            ]);
        } catch (\Exception $e) {
            error_log('WFN Orphaned Video Cleanup Error: ' . $e->getMessage());
            wp_send_json_error('Orphaned video cleanup failed: ' . $e->getMessage());
        }
    }

    /**
     * AJAX handler for cleaning up stuck uploads
     */
    public function ajax_cleanup_stuck_uploads(): void {
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'hkfn_video_maintenance')) {
            wp_send_json_error('Security check failed');
        }

        try {
            $results = $this->cleanup_stuck_uploads();
            wp_send_json_success([
                'message' => "Stuck upload cleanup completed. Found: {$results['found']}, Cleaned: {$results['cleaned']}, Errors: {$results['errors']}",
                'results' => $results
            ]);
        } catch (\Exception $e) {
            error_log('WFN Stuck Upload Cleanup Error: ' . $e->getMessage());
            wp_send_json_error('Stuck upload cleanup failed: ' . $e->getMessage());
        }
    }

    /**
     * Render video statistics section
     */
    private function render_video_statistics(): void {
        $stats = $this->get_video_statistics();
        $last_maintenance = hkfn_get_option('video_last_maintenance', null);

        ?>
        <div class="hkfn-settings-card">
            <h3>Video Statistics</h3>

            <div class="hkfn-stats-grid">
                <div class="hkfn-stat-item">
                    <div class="hkfn-stat-value"><?php echo intval($stats['total_videos']); ?></div>
                    <div class="hkfn-stat-label">Total Videos</div>
                </div>

                <div class="hkfn-stat-item">
                    <div class="hkfn-stat-value hkfn-stat-success"><?php echo intval($stats['ready_videos']); ?></div>
                    <div class="hkfn-stat-label">Ready</div>
                </div>

                <div class="hkfn-stat-item">
                    <div class="hkfn-stat-value hkfn-stat-processing"><?php echo intval($stats['processing_videos']); ?></div>
                    <div class="hkfn-stat-label">Processing</div>
                </div>

                <div class="hkfn-stat-item">
                    <div class="hkfn-stat-value hkfn-stat-error"><?php echo intval($stats['failed_videos']); ?></div>
                    <div class="hkfn-stat-label">Failed</div>
                </div>

                <div class="hkfn-stat-item">
                    <div class="hkfn-stat-value"><?php echo $stats['total_storage_mb']; ?>MB</div>
                    <div class="hkfn-stat-label">Total Storage</div>
                </div>
            </div>

            <?php if ($stats['last_updated']): ?>
                <p class="description">Last updated: <?php echo esc_html($stats['last_updated']); ?></p>
            <?php endif; ?>

            <?php if ($last_maintenance): ?>
                <div class="hkfn-maintenance-info">
                    <p><strong>Last Maintenance:</strong> <?php echo esc_html($last_maintenance['completed_at']); ?></p>
                    <?php if ($last_maintenance['total_errors'] > 0): ?>
                        <p class="hkfn-text-warning">⚠️ <?php echo $last_maintenance['total_errors']; ?> errors during last maintenance</p>
                    <?php else: ?>
                        <p class="hkfn-text-success">✅ Last maintenance completed without errors</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render maintenance controls section
     */
    private function render_maintenance_controls(): void {
        ?>
        <div class="hkfn-settings-card">
            <h3>Video Maintenance</h3>
            <p>Use these tools to maintain your video library and clean up any issues.</p>

            <div class="hkfn-maintenance-actions">
                <div class="hkfn-maintenance-action">
                    <h4>Full Maintenance</h4>
                    <p>Run comprehensive maintenance including cleanup of failed uploads, orphaned videos, and stuck uploads.</p>
                    <button type="button" class="button button-primary hkfn-run-maintenance" data-action="full">
                        Run Full Maintenance
                    </button>
                </div>

                <div class="hkfn-maintenance-action">
                    <h4>Clean Orphaned Videos</h4>
                    <p>Remove hosted videos that no longer have corresponding WordPress posts.</p>
                    <button type="button" class="button button-secondary hkfn-run-maintenance" data-action="orphaned">
                        Clean Orphaned Videos
                    </button>
                </div>

                <div class="hkfn-maintenance-action">
                    <h4>Fix Stuck Uploads</h4>
                    <p>Reset uploads that have been stuck in processing for more than 24 hours.</p>
                    <button type="button" class="button button-secondary hkfn-run-maintenance" data-action="stuck">
                        Fix Stuck Uploads
                    </button>
                </div>
            </div>

            <div id="hkfn-maintenance-results" class="hkfn-maintenance-results" style="display: none;">
                <h4>Maintenance Results</h4>
                <div class="hkfn-maintenance-output"></div>
            </div>
        </div>

        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('.hkfn-run-maintenance').on('click', function(e) {
                e.preventDefault();

                const $button = $(this);
                const action = $button.data('action');
                const $results = $('#hkfn-maintenance-results');
                const $output = $('.hkfn-maintenance-output');

                // Show loading state
                $button.prop('disabled', true).text('Running...');
                $results.show();
                $output.html('<p>Running maintenance task...</p>');

                // Prepare AJAX data
                const ajaxAction = action === 'full' ? 'hkfn_run_video_maintenance' :
                                 action === 'orphaned' ? 'hkfn_cleanup_orphaned_videos' :
                                 'hkfn_cleanup_stuck_uploads';

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: ajaxAction,
                        nonce: '<?php echo wp_create_nonce('hkfn_video_maintenance'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $output.html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');

                            // Show detailed results if available
                            if (response.data.results) {
                                let detailsHtml = '<div class="hkfn-maintenance-details">';

                                if (response.data.results.tasks) {
                                    // Full maintenance results
                                    Object.keys(response.data.results.tasks).forEach(function(taskName) {
                                        const task = response.data.results.tasks[taskName];
                                        detailsHtml += '<h5>' + taskName.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase()) + '</h5>';
                                        if (task.messages && task.messages.length > 0) {
                                            detailsHtml += '<ul>';
                                            task.messages.forEach(function(msg) {
                                                detailsHtml += '<li>' + msg + '</li>';
                                            });
                                            detailsHtml += '</ul>';
                                        }
                                    });
                                } else {
                                    // Single task results
                                    if (response.data.results.messages && response.data.results.messages.length > 0) {
                                        detailsHtml += '<ul>';
                                        response.data.results.messages.forEach(function(msg) {
                                            detailsHtml += '<li>' + msg + '</li>';
                                        });
                                        detailsHtml += '</ul>';
                                    }
                                }

                                detailsHtml += '</div>';
                                $output.append(detailsHtml);
                            }

                            // Refresh statistics after 2 seconds
                            setTimeout(function() {
                                location.reload();
                            }, 2000);

                        } else {
                            $output.html('<div class="notice notice-error"><p>Error: ' + response.data + '</p></div>');
                        }
                    },
                    error: function(xhr, status, error) {
                        $output.html('<div class="notice notice-error"><p>AJAX Error: ' + error + '</p></div>');
                    },
                    complete: function() {
                        // Reset button
                        $button.prop('disabled', false);
                        switch(action) {
                            case 'full':
                                $button.text('Run Full Maintenance');
                                break;
                            case 'orphaned':
                                $button.text('Clean Orphaned Videos');
                                break;
                            case 'stuck':
                                $button.text('Fix Stuck Uploads');
                                break;
                        }
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * Run scheduled maintenance via WordPress cron
     *
     * ⚠️ DEPRECATED AND DISABLED - November 2025
     *
     * @deprecated 2.5.3 Cron-based maintenance permanently disabled
     * @return void
     */
    public function run_scheduled_maintenance(): void {
        // PERMANENTLY DISABLED - Too dangerous for automatic execution
        error_log('WFN: run_scheduled_maintenance() called but permanently disabled for safety');
        error_log('WFN: Automatic video cleanup has been removed due to data loss incidents in Oct/Nov 2025');
        return;

        try {
            // Run comprehensive maintenance
            $results = $this->run_maintenance();

            // Log results for debugging
            error_log('WFN Scheduled Video Maintenance completed with ' . $results['total_errors'] . ' errors');

            // If there were errors, consider sending a notification
            if ($results['total_errors'] > 0) {
                // Optional: Send admin notification for maintenance issues
                $admin_email = get_option('admin_email');
                $subject = 'Video Maintenance Issues - ' . get_bloginfo('name');
                $message = "The scheduled video maintenance encountered {$results['total_errors']} errors.\n\n";
                $message .= "Please check the WFN Video Management page for details.\n\n";
                $message .= "Maintenance completed at: {$results['completed_at']}";

                wp_mail($admin_email, $subject, $message);
            }

        } catch (\Exception $e) {
            error_log('WFN Scheduled Video Maintenance failed: ' . $e->getMessage());
        }
    }

    /**
     * Show admin notices for license status on funeral notice edit pages
     *
     * NOTE: Banner notice removed per client request - licensing prompts should be more subtle
     * License validation still occurs via ACF field conditional logic
     */
    public function show_license_notices(): void {
        // License status banner removed - more subtle prompting preferred
        // Field-level prompts handled via ACF conditional logic instead
        return;
    }

    /**
     * Manual video processing trigger (for testing/debugging)
     */
    public function ajax_manual_process_video(): void {
        // Verify nonce and permissions
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'hkfn_manual_video_process') || !current_user_can('manage_options')) {
            wp_send_json_error('Security check failed');
        }

        $post_id = intval($_POST['post_id'] ?? 0);
        if (!$post_id) {
            wp_send_json_error('Invalid post ID');
        }

        // Check if post has queued video
        $upload_status = get_post_meta($post_id, '_hkfn_video_upload_status', true);
        if (empty($upload_status) || $upload_status['status'] !== 'queued') {
            wp_send_json_error('No queued video found for this post');
        }

        try {
            // Trigger cron processing immediately
            $this->process_video_upload_cron($post_id);
            wp_send_json_success('Video processing initiated successfully');
        } catch (\Exception $e) {
            wp_send_json_error('Processing failed: ' . $e->getMessage());
        }
    }
}