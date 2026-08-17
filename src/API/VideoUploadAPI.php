<?php
declare(strict_types=1);

namespace HumanKind\FuneralNotices\API;

use HumanKind\FuneralNotices\Services\BunnyStreamService;
use HumanKind\FuneralNotices\Services\LicenseService;
use WP_REST_Controller;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * Video Upload REST API Controller
 *
 * Handles direct-to-Bunny video upload coordination:
 * 1. Client requests upload session (gets Bunny video ID + upload URL)
 * 2. Client uploads directly to Bunny CDN via JavaScript
 * 3. Client notifies WordPress when upload completes
 * 4. WordPress stores video_id in post meta
 *
 * @since 2.3.1
 */
class VideoUploadAPI extends WP_REST_Controller {

    /**
     * @var string REST API namespace
     */
    protected $namespace = 'hkfn/v1';

    /**
     * @var string Base route
     */
    protected $rest_base = 'video';

    /**
     * @var BunnyStreamService
     */
    private BunnyStreamService $bunny_service;

    /**
     * Constructor
     */
    public function __construct() {
        $this->bunny_service = new BunnyStreamService();

        // Register cleanup hooks
        add_action('hkfn_cleanup_abandoned_uploads', [$this, 'cleanup_abandoned_uploads']);

        // Register post deletion hook to cleanup videos from Bunny
        add_action('before_delete_post', [$this, 'cleanup_video_on_post_delete']);
    }

    /**
     * Register REST API routes
     */
    public function register_routes(): void {
        // Initialize upload session
        register_rest_route($this->namespace, "/{$this->rest_base}/init-upload", [
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'init_upload'],
                'permission_callback' => [$this, 'check_permissions'],
                'args' => $this->get_init_upload_args()
            ]
        ]);

        // Notify upload complete
        register_rest_route($this->namespace, "/{$this->rest_base}/upload-complete", [
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'upload_complete'],
                'permission_callback' => [$this, 'check_permissions'],
                'args' => $this->get_upload_complete_args()
            ]
        ]);

        // Get upload status (for page refreshes/resume)
        register_rest_route($this->namespace, "/{$this->rest_base}/upload-status/(?P<post_id>\d+)", [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_upload_status'],
                'permission_callback' => [$this, 'check_permissions'],
                'args' => [
                    'post_id' => [
                        'required' => true,
                        'type' => 'integer',
                        'validate_callback' => function($param) {
                            return is_numeric($param) && $param > 0;
                        }
                    ]
                ]
            ]
        ]);

        // Delete video
        register_rest_route($this->namespace, "/{$this->rest_base}/delete/(?P<post_id>\d+)", [
            [
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => [$this, 'delete_video'],
                'permission_callback' => [$this, 'check_permissions'],
                'args' => [
                    'post_id' => [
                        'required' => true,
                        'type' => 'integer',
                        'validate_callback' => function($param) {
                            return is_numeric($param) && $param > 0;
                        }
                    ]
                ]
            ]
        ]);
    }

    /**
     * Initialize upload session
     *
     * Creates video entry in Bunny and returns direct upload URL
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function init_upload(WP_REST_Request $request) {
        $post_id = $request->get_param('post_id');
        $filename = $request->get_param('filename');
        $filesize = $request->get_param('filesize');

        // Verify post exists and is funeral-notice type
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'funeral-notice') {
            return new WP_Error(
                'invalid_post',
                'Invalid funeral notice post ID',
                ['status' => 400]
            );
        }

        // Check if user can edit this post
        if (!current_user_can('edit_post', $post_id)) {
            return new WP_Error(
                'forbidden',
                'You do not have permission to upload videos for this post',
                ['status' => 403]
            );
        }

        // Verify license for video streaming
        if (!LicenseService::isVideoConfigured()) {
            return new WP_Error(
                'license_required',
                'Premium license required for video streaming feature',
                ['status' => 402]
            );
        }

        // Check Bunny service is configured
        if (!$this->bunny_service->is_configured()) {
            return new WP_Error(
                'service_not_configured',
                'Video streaming service is not configured',
                ['status' => 500]
            );
        }

        // Create video entry in Bunny with person's name from ACF fields
        $video_title = $this->generate_video_title($post_id);
        $result = $this->bunny_service->create_upload_session([
            'title' => $video_title,
            'filename' => $filename,
            'filesize' => $filesize,
            'post_id' => $post_id
        ]);

        if (!$result['success']) {
            return new WP_Error(
                'upload_session_failed',
                $result['message'] ?? 'Failed to create upload session',
                ['status' => 500]
            );
        }

        // Store upload session metadata
        update_post_meta($post_id, '_hkfn_video_upload_session', [
            'video_id' => $result['video_id'],
            'filename' => $filename,
            'filesize' => $filesize,
            'started_at' => current_time('mysql'),
            'status' => 'uploading'
        ]);

        return new WP_REST_Response([
            'success' => true,
            'video_id' => $result['video_id'],
            'upload_url' => $result['upload_url'],
            'api_key' => $result['api_key'], // Bunny API key for direct upload
            'library_id' => $result['library_id'],
            'chunk_size' => $result['chunk_size'] ?? 5242880, // 5MB default
            'expires_at' => $result['expires_at'] ?? time() + 3600
        ], 200);
    }

    /**
     * Handle upload completion notification
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function upload_complete(WP_REST_Request $request) {
        $post_id = $request->get_param('post_id');
        $video_id = $request->get_param('video_id');

        // Verify post exists
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'funeral-notice') {
            return new WP_Error(
                'invalid_post',
                'Invalid funeral notice post ID',
                ['status' => 400]
            );
        }

        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return new WP_Error(
                'forbidden',
                'You do not have permission to update this post',
                ['status' => 403]
            );
        }

        // Verify this video_id matches the session
        $session = get_post_meta($post_id, '_hkfn_video_upload_session', true);
        if (!$session || $session['video_id'] !== $video_id) {
            return new WP_Error(
                'invalid_video_id',
                'Video ID does not match upload session',
                ['status' => 400]
            );
        }

        // Check if there's an existing video to replace
        $old_video_id = get_post_meta($post_id, '_hkfn_video_id', true);
        if (!empty($old_video_id) && $old_video_id !== $video_id) {
            // Delete the old video from Bunny CDN
            $delete_result = $this->bunny_service->delete_video($old_video_id);

            if ($delete_result['success']) {
                error_log("WFN: Successfully deleted replaced video {$old_video_id} from Bunny CDN for post {$post_id}");
            } else {
                error_log("WFN: Failed to delete replaced video {$old_video_id} from Bunny CDN: " . ($delete_result['message'] ?? 'Unknown error'));
            }
        }

        // Store video ID in post meta (matches existing VideoModule pattern)
        update_post_meta($post_id, '_hkfn_video_id', $video_id);
        update_post_meta($post_id, '_hkfn_video_status', 'ready'); // Video is ready for playback immediately
        update_post_meta($post_id, '_hkfn_video_uploaded_at', current_time('mysql'));

        // Track which site uploaded this video (for safe cross-site migrations)
        update_post_meta($post_id, '_hkfn_bunny_video_source_site', get_site_url());

        // Store basic video data for frontend rendering
        $video_data = [
            'video_id' => $video_id,
            'stream_url' => "https://iframe.mediadelivery.net/embed/" . $this->bunny_service->get_library_id() . "/{$video_id}",
            'thumbnail_url' => "https://vz-" . $this->bunny_service->get_library_id() . ".b-cdn.net/{$video_id}/thumbnail.jpg",
            'duration' => 0, // Unknown until transcoding completes
            'uploaded_at' => current_time('mysql')
        ];
        update_post_meta($post_id, '_hkfn_video_data', json_encode($video_data));

        // Update session status
        $session['status'] = 'completed';
        $session['completed_at'] = current_time('mysql');
        update_post_meta($post_id, '_hkfn_video_upload_session', $session);

        // Check transcoding status (async)
        $transcoding_status = $this->bunny_service->get_video_status($video_id);

        return new WP_REST_Response([
            'success' => true,
            'message' => 'Video upload recorded successfully',
            'video_id' => $video_id,
            'transcoding_status' => $transcoding_status['status'] ?? 'processing'
        ], 200);
    }

    /**
     * Get upload status for a post
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function get_upload_status(WP_REST_Request $request) {
        $post_id = $request->get_param('post_id');

        // Verify post exists
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'funeral-notice') {
            return new WP_Error(
                'invalid_post',
                'Invalid funeral notice post ID',
                ['status' => 400]
            );
        }

        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return new WP_Error(
                'forbidden',
                'You do not have permission to view this post',
                ['status' => 403]
            );
        }

        $session = get_post_meta($post_id, '_hkfn_video_upload_session', true);
        $video_id = get_post_meta($post_id, '_hkfn_video_id', true);

        return new WP_REST_Response([
            'success' => true,
            'status' => $session['status'] ?? 'none',
            'video_id' => $video_id ?: ($session['video_id'] ?? ''),
            'session' => $session ?: null
        ], 200);
    }

    /**
     * Delete video from post and Bunny CDN
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function delete_video(WP_REST_Request $request) {
        $post_id = $request->get_param('post_id');

        // Verify post exists
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'funeral-notice') {
            return new WP_Error(
                'invalid_post',
                'Invalid funeral notice post ID',
                ['status' => 400]
            );
        }

        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return new WP_Error(
                'forbidden',
                'You do not have permission to edit this post',
                ['status' => 403]
            );
        }

        // Get video ID
        $video_id = get_post_meta($post_id, '_hkfn_video_id', true);

        if (!$video_id) {
            return new WP_Error(
                'no_video',
                'No video found for this post',
                ['status' => 404]
            );
        }

        // Step 1: Unlink from post - clear meta fields first (graceful degradation)
        delete_post_meta($post_id, '_hkfn_video_id');
        delete_post_meta($post_id, '_hkfn_video_metadata');
        delete_post_meta($post_id, '_hkfn_video_status');
        delete_post_meta($post_id, '_hkfn_video_upload_status');
        delete_post_meta($post_id, '_hkfn_video_upload_session');
        delete_post_meta($post_id, '_hkfn_video_data');

        // Clear the ACF field value as well
        $media_group = get_field('hkfn_media_group', $post_id);
        if (is_array($media_group)) {
            $media_group['video_slideshow'] = null;
            update_field('hkfn_media_group', $media_group, $post_id);
        }

        // Step 2: Delete from BunnyStream (gracefully handle failures)
        $delete_result = $this->bunny_service->delete_video($video_id);

        if (!$delete_result['success']) {
            // Log the error but don't fail the request - video is already unlinked
            error_log("WFN: Failed to delete video {$video_id} from Bunny: " . ($delete_result['message'] ?? 'Unknown error'));

            return new WP_REST_Response([
                'success' => true,
                'message' => 'Video removed from post. Note: Video could not be deleted from hosting service, but will no longer appear on the funeral notice.',
                'video_id' => $video_id,
                'bunny_deleted' => false
            ], 200);
        }

        return new WP_REST_Response([
            'success' => true,
            'message' => 'Video removed successfully',
            'video_id' => $video_id,
            'bunny_deleted' => true
        ], 200);
    }

    /**
     * Check permissions for API access
     *
     * @param WP_REST_Request $request
     * @return bool|WP_Error
     */
    public function check_permissions(WP_REST_Request $request): bool {
        // Must be logged in
        if (!is_user_logged_in()) {
            return false;
        }

        // Must have capability to edit posts
        return current_user_can('edit_posts');
    }

    /**
     * Arguments for init-upload endpoint
     *
     * @return array
     */
    private function get_init_upload_args(): array {
        return [
            'post_id' => [
                'required' => true,
                'type' => 'integer',
                'validate_callback' => function($param) {
                    return is_numeric($param) && $param > 0;
                }
            ],
            'filename' => [
                'required' => true,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_file_name',
                'validate_callback' => function($param) {
                    return !empty($param) && strlen($param) < 256;
                }
            ],
            'filesize' => [
                'required' => true,
                'type' => 'integer',
                'validate_callback' => function($param) {
                    return is_numeric($param) && $param > 0 && $param <= 943718400; // 900MB max
                }
            ]
        ];
    }

    /**
     * Arguments for upload-complete endpoint
     *
     * @return array
     */
    private function get_upload_complete_args(): array {
        return [
            'post_id' => [
                'required' => true,
                'type' => 'integer',
                'validate_callback' => function($param) {
                    return is_numeric($param) && $param > 0;
                }
            ],
            'video_id' => [
                'required' => true,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => function($param) {
                    return !empty($param) && strlen($param) < 100;
                }
            ]
        ];
    }

    /**
     * Cleanup abandoned upload sessions
     *
     * ⚠️ DEPRECATED AND DISABLED - November 21, 2025
     *
     * This function was permanently disabled after the third video deletion incident.
     * Automatic cleanup is too dangerous for irreplaceable memorial content.
     *
     * INCIDENT TIMELINE:
     * - Oct 20, 2025: First deletion incident (VideoModule cleanup)
     * - Nov 21, 2025 (AM): Second deletion incident (Lychgate video)
     * - Nov 21, 2025 (6:44 AM cron): Third deletion incident (ALL videos)
     *
     * ROOT CAUSE: This function deletes videos from shared Bunny library without
     * checking which collection they belong to. All 20 sites share library 499405,
     * so one site's cleanup can delete other sites' videos.
     *
     * SAFE ALTERNATIVES:
     * - Manual cleanup via admin Video Management page
     * - Contact support for assistance with stuck uploads
     *
     * @param int $max_age_hours Maximum age in hours for abandoned sessions (default: 24)
     * @deprecated Since v2.6.4 - Permanently disabled for safety
     */
    public function cleanup_abandoned_uploads(int $max_age_hours = 24): void {
        // PERMANENTLY DISABLED - Too dangerous for production
        \HumanKind\FuneralNotices\Hooks\debug_log('WFN: cleanup_abandoned_uploads() called but permanently disabled for safety (v2.6.4)');
        error_log('WFN: Automatic video cleanup disabled due to data loss incidents in Oct/Nov 2025');
        error_log('WFN: Use manual cleanup via admin interface if needed');
        return; // Early exit - function does nothing
    }

    /**
     * Cancel an active upload session
     *
     * Allows users to cancel an in-progress upload, cleaning up resources.
     *
     * @param int $post_id Post ID
     * @return array Result with success status
     */
    public function cancel_upload(int $post_id): array {
        $session = get_post_meta($post_id, '_hkfn_video_upload_session', true);

        if (!$session || !is_array($session)) {
            return [
                'success' => false,
                'message' => 'No active upload session found'
            ];
        }

        $video_id = $session['video_id'] ?? '';

        // Delete video from Bunny if it exists
        if (!empty($video_id)) {
            $delete_result = $this->bunny_service->delete_video($video_id);
            if (!$delete_result['success']) {
                error_log("Failed to delete cancelled Bunny video {$video_id}: " . ($delete_result['message'] ?? 'Unknown error'));
            }
        }

        // Remove session metadata
        delete_post_meta($post_id, '_hkfn_video_upload_session');

        return [
            'success' => true,
            'message' => 'Upload cancelled successfully'
        ];
    }

    /**
     * Check if upload session has expired
     *
     * @param array $session Session data
     * @return bool True if expired
     */
    private function is_session_expired(array $session): bool {
        $expires_at = $session['expires_at'] ?? 0;
        return $expires_at > 0 && $expires_at < time();
    }

    /**
     * Clean up video from Bunny when post is deleted
     *
     * ⚠️ DEPRECATED AND DISABLED - November 21, 2025
     *
     * This function was permanently disabled after the third video deletion incident.
     * Automatic cleanup is too dangerous for irreplaceable memorial content.
     *
     * RISK: The "backward compatibility" fallback (line 589) assumes current site owns
     * videos without source_site metadata. With 20 sites sharing library 499405, this
     * could cause one site to delete another site's videos during post cleanup.
     *
     * SAFE ALTERNATIVE: Users should manually delete videos via the "Remove Video"
     * button before deleting posts if needed.
     *
     * @param int $post_id Post ID being deleted
     * @deprecated Since v2.6.4 - Permanently disabled for safety
     */
    public function cleanup_video_on_post_delete(int $post_id): void {
        // PERMANENTLY DISABLED - Too dangerous for production
        \HumanKind\FuneralNotices\Hooks\debug_log('WFN: cleanup_video_on_post_delete() called but permanently disabled for safety (v2.6.4)');
        \HumanKind\FuneralNotices\Hooks\debug_log("WFN: Post {$post_id} is being deleted, but automatic video cleanup is disabled");
        \HumanKind\FuneralNotices\Hooks\debug_log('WFN: Users should manually delete videos before deleting posts if needed');
        return; // Early exit - function does nothing
    }

    /**
     * Generate video title from person's name (ACF fields)
     *
     * Uses ACF person fields to create a proper title even for auto-draft posts.
     * Fallback to post ID if name fields are empty.
     *
     * @param int $post_id Post ID
     * @return string Video title
     */
    private function generate_video_title(int $post_id): string {
        // Try to get person's name from ACF fields (works even for auto-drafts)
        $person_group = get_field('hkfn_person_group', $post_id);

        // Try grouped format first
        $first_name = $person_group['firstname'] ?? '';
        $last_name = $person_group['lastname'] ?? '';

        // Fallback to individual fields
        if (empty($first_name)) {
            $first_name = get_field('hkfn_person_group_firstname', $post_id) ?? '';
        }
        if (empty($last_name)) {
            $last_name = get_field('hkfn_person_group_lastname', $post_id) ?? '';
        }

        // Build title based on what we have
        $full_name = trim("{$first_name} {$last_name}");

        if (!empty($full_name)) {
            return "{$full_name} {$post_id} - Memorial Video";
        }

        // Ultimate fallback: use post ID only
        return "Post {$post_id} - Memorial Video";
    }
}
