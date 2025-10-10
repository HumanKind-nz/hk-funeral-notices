<?php
declare(strict_types=1);

namespace WeaveStudios\FuneralNotices\Services;

use WeaveStudios\FuneralNotices\Services\LicenseService;

/**
 * Bunny Stream Service
 *
 * Handles all interactions with Bunny Stream API for video hosting,
 * including upload, management, and playback functionality.
 *
 * @since 2.1.4
 */
class BunnyStreamService {

    // Bunny Stream API constants
    private const API_BASE_URL = 'https://video.bunnycdn.com';
    private const STREAM_BASE_URL = 'https://iframe.mediadelivery.net/embed';

    private string $library_id;
    private string $api_key;
    private string $cdn_hostname;

    // Retry configuration
    private int $max_retries = 3;
    private array $retry_delays = [1, 2, 4]; // Exponential backoff in seconds
    private array $retryable_http_codes = [500, 502, 503, 504, 408, 429];
    private array $retryable_curl_errors = [
        CURLE_OPERATION_TIMEDOUT,
        CURLE_COULDNT_CONNECT,
        CURLE_COULDNT_RESOLVE_HOST,
        CURLE_SSL_CONNECT_ERROR,
        CURLE_RECV_ERROR,
        CURLE_SEND_ERROR
    ];

    /**
     * Constructor
     *
     * @param string $library_id Bunny Stream Library ID
     * @param string $api_key Bunny Stream API Key
     * @param string $cdn_hostname CDN hostname for video delivery
     */
    public function __construct(string $library_id = '', string $api_key = '', string $cdn_hostname = '') {
        // Use provided credentials or get from constants/options (constants take precedence)
        $this->library_id = $library_id ?: (defined('WFN_VIDEO_LIBRARY_ID') ? WFN_VIDEO_LIBRARY_ID : get_option('wfn_bunny_library_id', ''));
        $this->api_key = $api_key ?: (defined('WFN_VIDEO_API_KEY') ? WFN_VIDEO_API_KEY : get_option('wfn_bunny_api_key', ''));
        $this->cdn_hostname = $cdn_hostname ?: (defined('WFN_VIDEO_CDN_HOSTNAME') ? WFN_VIDEO_CDN_HOSTNAME : get_option('wfn_bunny_cdn_hostname', ''));

        // Log configuration for debugging
        if (defined('WFN_DEBUG') && WFN_DEBUG) {
            error_log('BunnyStreamService initialized - Library: ' . $this->library_id);
        }
    }

    /**
     * Check if service is properly configured
     *
     * @return bool True if all required credentials are set
     */
    public function is_configured(): bool {
        return !empty($this->library_id) && !empty($this->api_key);
    }

    /**
     * Get library ID
     *
     * @return string
     */
    public function get_library_id(): string {
        return $this->library_id;
    }

    /**
     * Make HTTP request with retry logic and comprehensive error handling
     *
     * @param string $url Request URL
     * @param string $method HTTP method (GET, POST, PUT, DELETE)
     * @param array $data Request data
     * @param array $headers Additional headers
     * @param array $options Additional cURL options
     * @return array Response with success status and data
     */
    private function make_http_request_with_retry(string $url, string $method = 'GET', array $data = [], array $headers = [], array $options = []): array {
        $attempt = 0;
        $last_error = null;

        // Default headers
        $default_headers = [
            'AccessKey: ' . $this->api_key,
            'Content-Type: application/json',
            'User-Agent: WeaveStudios-FuneralNotices/2.1.3'
        ];

        $headers = array_merge($default_headers, $headers);

        while ($attempt < $this->max_retries) {
            $attempt++;

            try {
                $result = $this->execute_http_request($url, $method, $data, $headers, $options);

                // If successful, return immediately
                if ($result['success'] || !$this->is_retryable_error($result)) {
                    // Log successful retry if not first attempt
                    if ($attempt > 1) {
                        $this->log_retry_success($url, $attempt, $result);
                    }
                    return $result;
                }

                // Store error for potential retry
                $last_error = $result;

                // Don't retry on the last attempt
                if ($attempt >= $this->max_retries) {
                    break;
                }

                // Wait before retry with exponential backoff
                $delay = $this->retry_delays[$attempt - 1] ?? end($this->retry_delays);
                $this->log_retry_attempt($url, $attempt, $result, $delay);
                sleep($delay);

            } catch (\Exception $e) {
                $last_error = [
                    'success' => false,
                    'message' => 'Exception during HTTP request: ' . $e->getMessage(),
                    'error_code' => 'EXCEPTION',
                    'exception' => $e->getTraceAsString()
                ];

                // Don't retry on the last attempt
                if ($attempt >= $this->max_retries) {
                    break;
                }

                // Wait before retry
                $delay = $this->retry_delays[$attempt - 1] ?? end($this->retry_delays);
                $this->log_exception_retry($url, $attempt, $e, $delay);
                sleep($delay);
            }
        }

        // All retries failed
        $this->log_retry_failure($url, $this->max_retries, $last_error);

        return [
            'success' => false,
            'message' => 'All retry attempts failed. Last error: ' . ($last_error['message'] ?? 'Unknown error'),
            'error_code' => 'MAX_RETRIES_EXCEEDED',
            'attempts' => $this->max_retries,
            'last_error' => $last_error
        ];
    }

    /**
     * Execute single HTTP request
     *
     * @param string $url Request URL
     * @param string $method HTTP method
     * @param array $data Request data
     * @param array $headers Request headers
     * @param array $options Additional cURL options
     * @return array Response
     */
    private function execute_http_request(string $url, string $method, array $data, array $headers, array $options): array {
        $ch = curl_init();

        // Base cURL options
        $curl_options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_USERAGENT => 'WeaveStudios-FuneralNotices/2.1.3',
            CURLOPT_VERBOSE => defined('WFN_DEBUG') && WFN_DEBUG
        ];

        // Method-specific options
        switch (strtoupper($method)) {
            case 'POST':
                $curl_options[CURLOPT_POST] = true;
                if (!empty($data)) {
                    $curl_options[CURLOPT_POSTFIELDS] = json_encode($data);
                }
                break;

            case 'PUT':
                $curl_options[CURLOPT_CUSTOMREQUEST] = 'PUT';
                if (!empty($data)) {
                    $curl_options[CURLOPT_POSTFIELDS] = json_encode($data);
                }
                break;

            case 'DELETE':
                $curl_options[CURLOPT_CUSTOMREQUEST] = 'DELETE';
                break;

            case 'GET':
                if (!empty($data)) {
                    $curl_options[CURLOPT_URL] .= '?' . http_build_query($data);
                }
                break;
        }

        // Apply additional options (these can override defaults)
        foreach ($options as $option => $value) {
            $curl_options[$option] = $value;
        }

        curl_setopt_array($ch, $curl_options);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error_code = curl_errno($ch);
        $curl_error_message = curl_error($ch);
        $request_info = curl_getinfo($ch);

        curl_close($ch);

        // Handle cURL errors
        if ($curl_error_code !== 0) {
            return [
                'success' => false,
                'message' => "cURL error ({$curl_error_code}): {$curl_error_message}",
                'error_code' => 'CURL_ERROR',
                'curl_error_code' => $curl_error_code,
                'curl_error_message' => $curl_error_message,
                'is_retryable' => in_array($curl_error_code, $this->retryable_curl_errors),
                'request_info' => $request_info
            ];
        }

        // Parse response
        $data = json_decode($response, true);

        // Handle HTTP errors
        if ($http_code >= 400) {
            $error_message = $this->parse_api_error_message($data, $http_code);

            return [
                'success' => false,
                'message' => $error_message,
                'error_code' => 'HTTP_ERROR',
                'http_code' => $http_code,
                'response' => $response,
                'parsed_data' => $data,
                'is_retryable' => in_array($http_code, $this->retryable_http_codes),
                'request_info' => $request_info
            ];
        }

        // Successful response
        return [
            'success' => true,
            'data' => $data,
            'http_code' => $http_code,
            'response' => $response,
            'request_info' => $request_info
        ];
    }

    /**
     * Check if an error is retryable
     *
     * @param array $error Error response
     * @return bool True if error should trigger retry
     */
    private function is_retryable_error(array $error): bool {
        // Check if explicitly marked as retryable
        if (isset($error['is_retryable'])) {
            return (bool)$error['is_retryable'];
        }

        // Check HTTP code
        if (isset($error['http_code']) && in_array($error['http_code'], $this->retryable_http_codes)) {
            return true;
        }

        // Check cURL error code
        if (isset($error['curl_error_code']) && in_array($error['curl_error_code'], $this->retryable_curl_errors)) {
            return true;
        }

        // Check for rate limiting
        if (isset($error['http_code']) && $error['http_code'] === 429) {
            return true;
        }

        return false;
    }

    /**
     * Parse API error message from response
     *
     * @param mixed $data Parsed response data
     * @param int $http_code HTTP response code
     * @return string Error message
     */
    private function parse_api_error_message($data, int $http_code): string {
        // Try to extract meaningful error message
        if (is_array($data)) {
            if (isset($data['message'])) {
                return "API Error ({$http_code}): " . $data['message'];
            }
            if (isset($data['error'])) {
                return "API Error ({$http_code}): " . $data['error'];
            }
            if (isset($data['errors']) && is_array($data['errors'])) {
                return "API Error ({$http_code}): " . implode(', ', $data['errors']);
            }
        }

        // Default HTTP status messages
        $status_messages = [
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            408 => 'Request Timeout',
            409 => 'Conflict',
            422 => 'Unprocessable Entity',
            429 => 'Too Many Requests',
            500 => 'Internal Server Error',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout'
        ];

        $status_message = $status_messages[$http_code] ?? 'Unknown Error';
        return "HTTP Error ({$http_code}): {$status_message}";
    }

    /**
     * Log retry attempt
     *
     * @param string $url Request URL
     * @param int $attempt Attempt number
     * @param array $error Error that triggered retry
     * @param int $delay Delay before retry
     */
    private function log_retry_attempt(string $url, int $attempt, array $error, int $delay): void {
        if (defined('WFN_DEBUG') && WFN_DEBUG) {
            error_log(sprintf(
                'BunnyStream: Retry attempt %d/%d for %s. Error: %s. Waiting %ds before retry.',
                $attempt,
                $this->max_retries,
                $url,
                $error['message'] ?? 'Unknown error',
                $delay
            ));
        }
    }

    /**
     * Log successful retry
     *
     * @param string $url Request URL
     * @param int $attempt Successful attempt number
     * @param array $result Successful result
     */
    private function log_retry_success(string $url, int $attempt, array $result): void {
        if (defined('WFN_DEBUG') && WFN_DEBUG) {
            error_log(sprintf(
                'BunnyStream: Request succeeded on attempt %d for %s',
                $attempt,
                $url
            ));
        }
    }

    /**
     * Log retry failure after all attempts
     *
     * @param string $url Request URL
     * @param int $attempts Total attempts made
     * @param array $last_error Last error encountered
     */
    private function log_retry_failure(string $url, int $attempts, array $last_error): void {
        error_log(sprintf(
            'BunnyStream: All %d retry attempts failed for %s. Last error: %s',
            $attempts,
            $url,
            $last_error['message'] ?? 'Unknown error'
        ));
    }

    /**
     * Log exception retry
     *
     * @param string $url Request URL
     * @param int $attempt Attempt number
     * @param \Exception $exception Exception that occurred
     * @param int $delay Delay before retry
     */
    private function log_exception_retry(string $url, int $attempt, \Exception $exception, int $delay): void {
        if (defined('WFN_DEBUG') && WFN_DEBUG) {
            error_log(sprintf(
                'BunnyStream: Exception on attempt %d/%d for %s: %s. Waiting %ds before retry.',
                $attempt,
                $this->max_retries,
                $url,
                $exception->getMessage(),
                $delay
            ));
        }
    }

    /**
     * Check if video streaming is licensed
     *
     * @return bool
     */
    public function is_licensed(): bool {
        return LicenseService::hasValidVideoLicense();
    }

    /**
     * Get configuration status for admin display
     *
     * @return array Configuration status with details
     */
    public function get_configuration_status(): array {
        return [
            'configured' => $this->is_configured(),
            'licensed' => $this->is_licensed(),
            'library_id' => !empty($this->library_id) ? 'Set' : 'Not set',
            'api_key' => !empty($this->api_key) ? 'Set' : 'Not set',
            'cdn_hostname' => !empty($this->cdn_hostname) ? 'Set' : 'Not set',
            'missing_items' => $this->get_missing_configuration_items()
        ];
    }

    /**
     * Get list of missing configuration items
     *
     * @return array List of missing configuration keys
     */
    private function get_missing_configuration_items(): array {
        $missing = [];

        if (empty($this->library_id)) $missing[] = 'Library ID';
        if (empty($this->api_key)) $missing[] = 'API Key';
        if (empty($this->cdn_hostname)) $missing[] = 'CDN Hostname';

        return $missing;
    }

    /**
     * Upload video file to Bunny Stream
     *
     * @param string $file_path Path to video file
     * @param array $metadata Video metadata (title, description, etc.)
     * @return array Upload result with video ID and details
     */
    public function upload_video(string $file_path, array $metadata = []): array {
        // Check license first
        if (!LicenseService::hasValidVideoLicense()) {
            return [
                'success' => false,
                'message' => LicenseService::getFeatureRequiresLicenseMessage('video_streaming'),
                'error_code' => 'LICENSE_REQUIRED'
            ];
        }

        if (!$this->is_configured()) {
            return [
                'success' => false,
                'message' => 'Bunny Stream not configured. Missing: ' . implode(', ', $this->get_missing_configuration_items()),
                'error_code' => 'NOT_CONFIGURED'
            ];
        }

        // Validate file exists and is readable
        if (!file_exists($file_path) || !is_readable($file_path)) {
            return [
                'success' => false,
                'message' => 'Video file not found or not readable: ' . basename($file_path),
                'error_code' => 'FILE_NOT_FOUND'
            ];
        }

        // Validate file size (max 500MB as per PRD)
        $file_size = filesize($file_path);
        $max_size = 500 * 1024 * 1024; // 500MB in bytes

        if ($file_size > $max_size) {
            return [
                'success' => false,
                'message' => 'File size exceeds maximum limit of 500MB. Current size: ' . $this->format_file_size($file_size),
                'error_code' => 'FILE_TOO_LARGE'
            ];
        }

        // Validate file type
        $allowed_types = ['video/mp4', 'video/quicktime', 'video/x-msvideo', 'video/webm'];
        $file_type = mime_content_type($file_path);

        if (!in_array($file_type, $allowed_types)) {
            return [
                'success' => false,
                'message' => 'Invalid file type: ' . $file_type . '. Allowed types: MP4, MOV, AVI, WebM',
                'error_code' => 'INVALID_FILE_TYPE'
            ];
        }

        // Prepare video metadata
        $video_title = $metadata['title'] ?? 'Memorial Video - ' . date('Y-m-d H:i:s');
        $video_description = $metadata['description'] ?? 'Memorial video slideshow';
        $site_domain = parse_url(get_site_url(), PHP_URL_HOST) ?? 'unknown-site';

        // Step 1: Create video entry in Bunny Stream
        $create_result = $this->create_video_entry($video_title, $metadata);

        if (!$create_result['success']) {
            return $create_result;
        }

        $video_id = $create_result['video_id'];

        // Step 2: Upload video file to Bunny Stream
        $upload_result = $this->upload_video_file($video_id, $file_path);

        if (!$upload_result['success']) {
            // Clean up created video entry on upload failure
            $this->delete_video($video_id);
            return $upload_result;
        }

        // Step 3: Add video to site collection for organization
        $collection_result = $this->add_video_to_collection($video_id, $site_domain);

        // Log successful upload
        error_log("Video uploaded successfully - ID: {$video_id}, Site: {$site_domain}, Size: " . $this->format_file_size($file_size));

        return [
            'success' => true,
            'message' => 'Video uploaded successfully',
            'video_id' => $video_id,
            'video_title' => $video_title,
            'file_size' => $file_size,
            'formatted_size' => $this->format_file_size($file_size),
            'site_domain' => $site_domain,
            'upload_time' => current_time('mysql', true),
            'collection_added' => $collection_result['success'] ?? false
        ];
    }

    /**
     * Create video entry in Bunny Stream
     *
     * @param string $title Video title
     * @param array $metadata Additional metadata
     * @return array Creation result with video ID
     */
    private function create_video_entry(string $title, array $metadata = []): array {
        $endpoint = "/library/{$this->library_id}/videos";

        $video_data = [
            'title' => sanitize_text_field($title),
            'collectionId' => $metadata['collection_id'] ?? null
        ];

        // Add optional metadata if provided
        if (!empty($metadata['description'])) {
            $video_data['description'] = sanitize_textarea_field($metadata['description']);
        }

        $response = $this->make_api_request($endpoint, 'POST', $video_data);

        if (!$response['success']) {
            return [
                'success' => false,
                'message' => 'Failed to create video entry: ' . $response['message'],
                'error_code' => 'CREATE_FAILED'
            ];
        }

        return [
            'success' => true,
            'video_id' => $response['data']['guid'] ?? $response['data']['id'] ?? '',
            'video_data' => $response['data']
        ];
    }

    /**
     * Upload video file to Bunny Stream
     *
     * @param string $video_id Video ID from Bunny Stream
     * @param string $file_path Path to video file
     * @return array Upload result
     */
    private function upload_video_file(string $video_id, string $file_path): array {
        $upload_url = self::API_BASE_URL . "/library/{$this->library_id}/videos/{$video_id}";

        // Initialize cURL for file upload
        $curl = curl_init();

        // Prepare file for upload
        $file_data = curl_file_create($file_path, mime_content_type($file_path), basename($file_path));

        curl_setopt_array($curl, [
            CURLOPT_URL => $upload_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 300, // 5 minutes timeout for large files
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_POSTFIELDS => file_get_contents($file_path),
            CURLOPT_HTTPHEADER => [
                'AccessKey: ' . $this->api_key,
                'Content-Type: application/octet-stream',
                'Content-Length: ' . filesize($file_path)
            ],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3
        ]);

        $response = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($curl);

        curl_close($curl);

        // Handle cURL errors
        if ($curl_error) {
            error_log("Video upload cURL error: {$curl_error}");
            return [
                'success' => false,
                'message' => 'Network error during video upload: ' . $curl_error,
                'error_code' => 'NETWORK_ERROR'
            ];
        }

        // Handle HTTP errors
        if ($http_code < 200 || $http_code >= 300) {
            error_log("Video upload HTTP error: {$http_code} - {$response}");
            return [
                'success' => false,
                'message' => "Upload failed with HTTP code {$http_code}",
                'error_code' => 'HTTP_ERROR',
                'http_code' => $http_code
            ];
        }

        return [
            'success' => true,
            'message' => 'File uploaded successfully',
            'response' => $response
        ];
    }

    /**
     * Add video to site-specific collection
     *
     * @param string $video_id Video ID
     * @param string $site_domain Site domain for organization
     * @return array Collection result
     */
    private function add_video_to_collection(string $video_id, string $site_domain): array {
        // Get or create collection for this site
        $collection_result = $this->get_or_create_site_collection($site_domain);

        if (!$collection_result['success']) {
            // Don't fail the upload if collection management fails
            error_log("Failed to add video to collection: " . $collection_result['message']);
            return $collection_result;
        }

        $collection_id = $collection_result['collection_id'];

        // Update video to assign it to the collection
        $endpoint = "/library/{$this->library_id}/videos/{$video_id}";
        $update_data = ['collectionId' => $collection_id];

        $response = $this->make_api_request($endpoint, 'POST', $update_data);

        return [
            'success' => $response['success'],
            'collection_id' => $collection_id,
            'collection_name' => $collection_result['collection_name'] ?? $site_domain
        ];
    }

    /**
     * Get or create collection for site domain
     *
     * @param string $site_domain Site domain
     * @return array Collection result with ID
     */
    private function get_or_create_site_collection(string $site_domain): array {
        $collection_name = 'Site: ' . $site_domain;

        // First, try to find existing collection
        $collections = $this->get_collections();

        if ($collections['success']) {
            // Handle paginated API response - collections are in 'items' array
            $collections_data = $collections['data'];
            $items = $collections_data['items'] ?? $collections_data;

            foreach ($items as $collection) {
                // Skip if collection is not an array or doesn't have required fields
                if (!is_array($collection) || !isset($collection['name'])) {
                    continue;
                }

                if ($collection['name'] === $collection_name) {
                    return [
                        'success' => true,
                        'collection_id' => $collection['guid'] ?? $collection['id'],
                        'collection_name' => $collection_name,
                        'existing' => true
                    ];
                }
            }
        }

        // Create new collection if not found
        return $this->create_site_collection($site_domain);
    }

    /**
     * Get all collections from library
     *
     * @return array Collections list
     */
    private function get_collections(): array {
        $endpoint = "/library/{$this->library_id}/collections";
        return $this->make_api_request($endpoint, 'GET');
    }

    /**
     * Format file size for human readable display
     *
     * @param int $bytes File size in bytes
     * @return string Formatted file size
     */
    private function format_file_size(int $bytes): string {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Delete video from Bunny Stream
     *
     * @param string $video_id Bunny Stream video ID
     * @return array Deletion result
     */
    public function delete_video(string $video_id): array {
        if (!$this->is_configured()) {
            return [
                'success' => false,
                'message' => 'Bunny Stream not configured',
                'error_code' => 'NOT_CONFIGURED'
            ];
        }

        // Validate video ID
        $video_id = sanitize_text_field(trim($video_id));
        if (empty($video_id)) {
            return [
                'success' => false,
                'message' => 'Video ID is required',
                'error_code' => 'MISSING_VIDEO_ID'
            ];
        }

        // Get video information before deletion (for logging)
        $video_info = $this->get_video_info($video_id);

        // Delete the video
        $endpoint = "/library/{$this->library_id}/videos/{$video_id}";
        $response = $this->make_api_request($endpoint, 'DELETE');

        if (!$response['success']) {
            // Handle specific error cases
            if ($response['error_code'] === 'NOT_FOUND') {
                // Video already deleted or doesn't exist
                return [
                    'success' => true,
                    'message' => 'Video was already deleted or does not exist',
                    'video_id' => $video_id,
                    'already_deleted' => true
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to delete video: ' . $response['message'],
                'error_code' => 'DELETE_FAILED',
                'video_id' => $video_id,
                'api_response' => $response
            ];
        }

        // Log successful deletion
        $video_title = $video_info['success'] ? $video_info['title'] : 'Unknown';
        error_log("Video deleted from Bunny Stream: {$video_title} (ID: {$video_id})");

        return [
            'success' => true,
            'message' => 'Video deleted successfully',
            'video_id' => $video_id,
            'video_title' => $video_title,
            'deleted_at' => current_time('mysql', true)
        ];
    }

    /**
     * Delete multiple videos in batch
     *
     * @param array $video_ids Array of video IDs to delete
     * @param bool $stop_on_error Whether to stop on first error or continue
     * @return array Batch deletion result
     */
    public function delete_videos_batch(array $video_ids, bool $stop_on_error = false): array {
        if (!$this->is_configured()) {
            return [
                'success' => false,
                'message' => 'Bunny Stream not configured',
                'error_code' => 'NOT_CONFIGURED'
            ];
        }

        if (empty($video_ids)) {
            return [
                'success' => true,
                'message' => 'No videos to delete',
                'deleted_count' => 0,
                'failed_count' => 0,
                'results' => []
            ];
        }

        $results = [];
        $deleted_count = 0;
        $failed_count = 0;

        foreach ($video_ids as $video_id) {
            $delete_result = $this->delete_video($video_id);

            $results[] = [
                'video_id' => $video_id,
                'success' => $delete_result['success'],
                'message' => $delete_result['message'],
                'video_title' => $delete_result['video_title'] ?? 'Unknown'
            ];

            if ($delete_result['success']) {
                $deleted_count++;
            } else {
                $failed_count++;

                // Stop on error if requested
                if ($stop_on_error) {
                    break;
                }
            }
        }

        return [
            'success' => $failed_count === 0,
            'message' => "Deleted {$deleted_count} videos, {$failed_count} failed",
            'deleted_count' => $deleted_count,
            'failed_count' => $failed_count,
            'total_requested' => count($video_ids),
            'results' => $results,
            'completed_at' => current_time('mysql', true)
        ];
    }

    /**
     * Get video information
     *
     * @param string $video_id Video ID
     * @return array Video information
     */
    public function get_video_info(string $video_id): array {
        if (!$this->is_configured()) {
            return [
                'success' => false,
                'message' => 'Bunny Stream not configured',
                'error_code' => 'NOT_CONFIGURED'
            ];
        }

        $endpoint = "/library/{$this->library_id}/videos/{$video_id}";
        $response = $this->make_api_request($endpoint, 'GET');

        if (!$response['success']) {
            return [
                'success' => false,
                'message' => 'Failed to get video info: ' . $response['message'],
                'error_code' => 'VIDEO_INFO_FAILED',
                'video_id' => $video_id
            ];
        }

        $video_data = $response['data'];

        return [
            'success' => true,
            'video_id' => $video_id,
            'title' => $video_data['title'] ?? 'Untitled',
            'description' => $video_data['description'] ?? '',
            'duration' => $video_data['length'] ?? 0,
            'file_size' => $video_data['storageSize'] ?? 0,
            'formatted_size' => $this->format_file_size($video_data['storageSize'] ?? 0),
            'status' => $video_data['status'] ?? 'unknown',
            'collection_id' => $video_data['collectionId'] ?? null,
            'created_at' => $video_data['dateUploaded'] ?? '',
            'thumbnail_url' => $video_data['thumbnailUrl'] ?? '',
            'stream_url' => "https://iframe.mediadelivery.net/embed/{$this->library_id}/{$video_id}?autoplay=false&preload=false&controls=true&t=0",
            'playback_url' => "https://iframe.mediadelivery.net/play/{$this->library_id}/{$video_id}?autoplay=false&preload=false&controls=true&t=0",
            'raw_data' => $video_data
        ];
    }

    /**
     * Get playback URL for video
     *
     * @param string $video_id Video ID
     * @return string Playback URL
     */
    public function get_playback_url(string $video_id): string {
        // Check license first
        if (!LicenseService::hasValidVideoLicense()) {
            return '';
        }

        if (empty($this->cdn_hostname)) {
            return '';
        }

        return "https://{$this->cdn_hostname}/{$video_id}/playlist.m3u8";
    }

    /**
     * Delete all videos for a specific site domain
     *
     * @param string $site_domain Site domain to clean up
     * @param bool $delete_collection Whether to delete the collection too
     * @return array Cleanup result
     */
    public function delete_site_videos(string $site_domain, bool $delete_collection = true): array {
        if (!$this->is_configured()) {
            return [
                'success' => false,
                'message' => 'Bunny Stream not configured',
                'error_code' => 'NOT_CONFIGURED'
            ];
        }

        // Find the site collection
        $site_collections = $this->get_site_collections();

        if (!$site_collections['success']) {
            return $site_collections;
        }

        $target_collection = null;
        foreach ($site_collections['site_collections'] as $collection) {
            if ($collection['site_domain'] === $site_domain) {
                $target_collection = $collection;
                break;
            }
        }

        if (!$target_collection) {
            return [
                'success' => true,
                'message' => "No collection found for site: {$site_domain}",
                'site_domain' => $site_domain,
                'videos_deleted' => 0,
                'collection_deleted' => false
            ];
        }

        $collection_id = $target_collection['collection_id'];

        // Get all videos in the collection
        $stats = $this->get_collection_statistics($collection_id);

        if (!isset($stats['videos'])) {
            return [
                'success' => false,
                'message' => 'Failed to get videos for collection',
                'error_code' => 'COLLECTION_VIDEOS_FAILED'
            ];
        }

        // Delete all videos
        $video_ids = array_map(fn($video) => $video['guid'] ?? $video['id'] ?? '', $stats['videos']);
        $video_ids = array_filter($video_ids); // Remove empty IDs

        $deletion_result = $this->delete_videos_batch($video_ids);

        // Optionally delete the collection
        $collection_deleted = false;
        if ($delete_collection && $deletion_result['success']) {
            $collection_delete_result = $this->delete_site_collection($collection_id, false); // Don't delete videos again
            $collection_deleted = $collection_delete_result['success'];
        }

        return [
            'success' => $deletion_result['success'] && (!$delete_collection || $collection_deleted),
            'message' => "Site cleanup completed: {$deletion_result['deleted_count']} videos deleted",
            'site_domain' => $site_domain,
            'collection_id' => $collection_id,
            'videos_deleted' => $deletion_result['deleted_count'],
            'videos_failed' => $deletion_result['failed_count'],
            'collection_deleted' => $collection_deleted,
            'video_results' => $deletion_result['results'],
            'completed_at' => current_time('mysql', true)
        ];
    }

    /**
     * Verify video deletion (check if video still exists)
     *
     * @param string $video_id Video ID to verify
     * @return array Verification result
     */
    public function verify_video_deletion(string $video_id): array {
        $info_result = $this->get_video_info($video_id);

        if ($info_result['success']) {
            return [
                'success' => false,
                'message' => 'Video still exists and was not deleted',
                'video_id' => $video_id,
                'exists' => true,
                'video_info' => $info_result
            ];
        }

        // If getting info failed with NOT_FOUND, video is successfully deleted
        if ($info_result['error_code'] === 'NOT_FOUND') {
            return [
                'success' => true,
                'message' => 'Video deletion verified - video no longer exists',
                'video_id' => $video_id,
                'exists' => false
            ];
        }

        // Other errors might indicate API issues
        return [
            'success' => false,
            'message' => 'Unable to verify deletion due to API error: ' . $info_result['message'],
            'video_id' => $video_id,
            'exists' => 'unknown',
            'error_info' => $info_result
        ];
    }

    /**
     * Get video statistics from Bunny Stream
     *
     * @param string $video_id Bunny Stream video ID
     * @return array Video statistics (views, storage used, etc.)
     */
    public function get_video_stats(string $video_id): array {
        if (!$this->is_configured()) {
            return [
                'success' => false,
                'message' => 'Bunny Stream not configured',
                'error_code' => 'NOT_CONFIGURED'
            ];
        }

        if (empty($video_id)) {
            return [
                'success' => false,
                'message' => 'Video ID is required',
                'error_code' => 'MISSING_VIDEO_ID'
            ];
        }

        try {
            // Get video statistics from Bunny Stream Statistics API
            $url = "https://video.bunnycdn.com/library/{$this->library_id}/statistics";

            $headers = [
                'AccessKey: ' . $this->api_key,
                'Content-Type: application/json'
            ];

            $params = [
                'videoGuid' => $video_id,
                'dateFrom' => date('Y-m-d', strtotime('-30 days')), // Last 30 days
                'dateTo' => date('Y-m-d'),
                'hourly' => false
            ];

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url . '?' . http_build_query($params),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 3
            ]);

            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                throw new \Exception("cURL error: {$error}");
            }

            $data = json_decode($response, true);

            if ($http_code === 200 && $data) {
                // Calculate totals from the statistics data
                $total_views = 0;
                $total_watch_time = 0;
                $countries = [];

                if (isset($data['viewsChart']) && is_array($data['viewsChart'])) {
                    foreach ($data['viewsChart'] as $entry) {
                        $total_views += (int)($entry['views'] ?? 0);
                    }
                }

                if (isset($data['watchTimeChart']) && is_array($data['watchTimeChart'])) {
                    foreach ($data['watchTimeChart'] as $entry) {
                        $total_watch_time += (int)($entry['watchTimeHours'] ?? 0);
                    }
                }

                if (isset($data['countryViewsChart']) && is_array($data['countryViewsChart'])) {
                    foreach ($data['countryViewsChart'] as $country => $views) {
                        $countries[$country] = (int)$views;
                    }
                    // Sort countries by views (descending)
                    arsort($countries);
                }

                return [
                    'success' => true,
                    'video_id' => $video_id,
                    'statistics' => [
                        'total_views' => $total_views,
                        'total_watch_time_hours' => $total_watch_time,
                        'date_range' => [
                            'from' => $params['dateFrom'],
                            'to' => $params['dateTo']
                        ],
                        'countries' => array_slice($countries, 0, 10), // Top 10 countries
                        'raw_data' => $data // Include raw data for debugging
                    ]
                ];
            } else {
                $error_message = 'Failed to retrieve video statistics';
                if ($data && isset($data['message'])) {
                    $error_message .= ': ' . $data['message'];
                }

                return [
                    'success' => false,
                    'message' => $error_message,
                    'http_code' => $http_code,
                    'error_code' => 'API_ERROR',
                    'video_id' => $video_id,
                    'response' => $response
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Exception getting video statistics: ' . $e->getMessage(),
                'error_code' => 'EXCEPTION',
                'video_id' => $video_id
            ];
        }
    }

    /**
     * Get library statistics from Bunny Stream
     *
     * @return array Library statistics (storage, bandwidth, videos count, etc.)
     */
    public function get_library_stats(): array {
        if (!$this->is_configured()) {
            return [
                'success' => false,
                'message' => 'Bunny Stream not configured',
                'error_code' => 'NOT_CONFIGURED'
            ];
        }

        try {
            // Get library information and statistics
            $library_info = $this->get_library_info();
            $library_stats = $this->get_library_bandwidth_stats();

            if (!$library_info['success'] || !$library_stats['success']) {
                return [
                    'success' => false,
                    'message' => 'Failed to retrieve complete library statistics',
                    'error_code' => 'INCOMPLETE_DATA',
                    'library_info' => $library_info,
                    'bandwidth_stats' => $library_stats
                ];
            }

            $info = $library_info['library'];
            $stats = $library_stats['statistics'];

            return [
                'success' => true,
                'library_id' => $this->library_id,
                'statistics' => [
                    // Basic library info
                    'library_name' => $info['Name'] ?? 'Unknown',
                    'videos_count' => (int)($info['VideoCount'] ?? 0),
                    'storage_used_bytes' => (int)($info['StorageUsed'] ?? 0),
                    'storage_used_gb' => round(((int)($info['StorageUsed'] ?? 0)) / 1024 / 1024 / 1024, 2),

                    // Bandwidth statistics (last 30 days)
                    'bandwidth_used_bytes' => (int)($stats['total_bandwidth'] ?? 0),
                    'bandwidth_used_gb' => round(((int)($stats['total_bandwidth'] ?? 0)) / 1024 / 1024 / 1024, 2),
                    'requests_served' => (int)($stats['total_requests'] ?? 0),

                    // Date range for statistics
                    'stats_date_range' => $stats['date_range'] ?? null,

                    // Raw data for debugging
                    'raw_library_info' => $info,
                    'raw_bandwidth_stats' => $stats
                ],
                'costs' => $this->calculate_estimated_costs([
                    'storage_gb' => round(((int)($info['StorageUsed'] ?? 0)) / 1024 / 1024 / 1024, 2),
                    'bandwidth_gb' => round(((int)($stats['total_bandwidth'] ?? 0)) / 1024 / 1024 / 1024, 2),
                    'videos_count' => (int)($info['VideoCount'] ?? 0)
                ])
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Exception getting library statistics: ' . $e->getMessage(),
                'error_code' => 'EXCEPTION'
            ];
        }
    }

    /**
     * Get basic library information from Bunny Stream
     *
     * @return array Library information
     */
    private function get_library_info(): array {
        try {
            $url = "https://video.bunnycdn.com/library/{$this->library_id}";

            $headers = [
                'AccessKey: ' . $this->api_key,
                'Content-Type: application/json'
            ];

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_SSL_VERIFYPEER => true
            ]);

            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                throw new \Exception("cURL error: {$error}");
            }

            $data = json_decode($response, true);

            if ($http_code === 200 && $data) {
                return [
                    'success' => true,
                    'library' => $data
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to get library info',
                    'http_code' => $http_code,
                    'response' => $response
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Exception getting library info: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get bandwidth statistics for the library
     *
     * @return array Bandwidth statistics
     */
    private function get_library_bandwidth_stats(): array {
        try {
            $url = "https://video.bunnycdn.com/library/{$this->library_id}/statistics";

            $headers = [
                'AccessKey: ' . $this->api_key,
                'Content-Type: application/json'
            ];

            $params = [
                'dateFrom' => date('Y-m-d', strtotime('-30 days')),
                'dateTo' => date('Y-m-d'),
                'hourly' => false
            ];

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url . '?' . http_build_query($params),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_SSL_VERIFYPEER => true
            ]);

            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                throw new \Exception("cURL error: {$error}");
            }

            $data = json_decode($response, true);

            if ($http_code === 200 && $data) {
                // Calculate totals from bandwidth chart
                $total_bandwidth = 0;
                $total_requests = 0;

                if (isset($data['bandwidthChart']) && is_array($data['bandwidthChart'])) {
                    foreach ($data['bandwidthChart'] as $entry) {
                        $total_bandwidth += (int)($entry['bandwidth'] ?? 0);
                    }
                }

                if (isset($data['requestsChart']) && is_array($data['requestsChart'])) {
                    foreach ($data['requestsChart'] as $entry) {
                        $total_requests += (int)($entry['requests'] ?? 0);
                    }
                }

                return [
                    'success' => true,
                    'statistics' => [
                        'total_bandwidth' => $total_bandwidth,
                        'total_requests' => $total_requests,
                        'date_range' => [
                            'from' => $params['dateFrom'],
                            'to' => $params['dateTo']
                        ],
                        'raw_data' => $data
                    ]
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to get bandwidth statistics',
                    'http_code' => $http_code,
                    'response' => $response
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Exception getting bandwidth stats: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Calculate estimated costs based on usage statistics
     *
     * @param array $usage Usage data (storage_gb, bandwidth_gb, videos_count)
     * @return array Cost estimates
     */
    private function calculate_estimated_costs(array $usage): array {
        // Bunny Stream pricing (approximate - as of 2024)
        $pricing = [
            'storage_per_gb_month' => 0.005, // $0.005 per GB per month
            'bandwidth_per_gb' => 0.01,      // $0.01 per GB bandwidth
            'encoding_per_minute' => 0.005,  // $0.005 per minute of encoding
        ];

        $storage_cost = ($usage['storage_gb'] ?? 0) * $pricing['storage_per_gb_month'];
        $bandwidth_cost = ($usage['bandwidth_gb'] ?? 0) * $pricing['bandwidth_per_gb'];

        // Estimate encoding costs (assume average 5 minutes per video)
        $avg_minutes_per_video = 5;
        $encoding_cost = ($usage['videos_count'] ?? 0) * $avg_minutes_per_video * $pricing['encoding_per_minute'];

        return [
            'currency' => 'USD',
            'period' => 'month',
            'breakdown' => [
                'storage' => [
                    'amount' => round($storage_cost, 4),
                    'unit' => 'per month',
                    'usage' => $usage['storage_gb'] . ' GB'
                ],
                'bandwidth' => [
                    'amount' => round($bandwidth_cost, 4),
                    'unit' => 'last 30 days',
                    'usage' => $usage['bandwidth_gb'] . ' GB'
                ],
                'encoding' => [
                    'amount' => round($encoding_cost, 4),
                    'unit' => 'estimated total',
                    'usage' => $usage['videos_count'] . ' videos'
                ]
            ],
            'total_monthly' => round($storage_cost, 4),
            'total_usage_based' => round($bandwidth_cost + $encoding_cost, 4),
            'note' => 'Estimates based on public Bunny pricing. Actual costs may vary.'
        ];
    }

    /**
     * Generate embed code for video player
     *
     * @param string $video_id Bunny Stream video ID
     * @param array $options Player options (width, height, autoplay, etc.)
     * @return array Embed code and player URL
     */
    public function get_embed_code(string $video_id, array $options = []): array {
        if (!$this->is_configured()) {
            return [
                'success' => false,
                'message' => 'Bunny Stream not configured',
                'error_code' => 'NOT_CONFIGURED'
            ];
        }

        if (empty($video_id)) {
            return [
                'success' => false,
                'message' => 'Video ID is required',
                'error_code' => 'MISSING_VIDEO_ID'
            ];
        }

        try {
            // Default player options
            $defaults = [
                'width' => '100%',
                'height' => '400',
                'autoplay' => false,
                'muted' => true,
                'controls' => true,
                'responsive' => true,
                'preload' => 'metadata',
                'poster' => true, // Use video thumbnail as poster
                'title' => 'Memorial Video Slideshow',
                'allow_fullscreen' => true
            ];

            $options = array_merge($defaults, $options);

            // Build the Bunny Stream player URL
            $player_url = "https://iframe.bunny.net/embed/{$video_id}";

            // Add query parameters for player configuration
            $params = [];

            if ($options['autoplay']) {
                $params['autoplay'] = 'true';
            }

            if ($options['muted']) {
                $params['muted'] = 'true';
            }

            if (!$options['controls']) {
                $params['controls'] = 'false';
            }

            if ($options['preload'] !== 'metadata') {
                $params['preload'] = $options['preload'];
            }

            // Add parameters to URL if any
            if (!empty($params)) {
                $player_url .= '?' . http_build_query($params);
            }

            // Generate iframe embed code
            $iframe_attributes = [
                'src' => $player_url,
                'width' => $options['width'],
                'height' => $options['height'],
                'frameborder' => '0',
                'title' => esc_attr($options['title'])
            ];

            if ($options['allow_fullscreen']) {
                $iframe_attributes['allowfullscreen'] = '';
                $iframe_attributes['allow'] = 'fullscreen';
            }

            if ($options['responsive']) {
                $iframe_attributes['style'] = 'width: 100%; height: auto; aspect-ratio: 16/9;';
            }

            // Build iframe HTML
            $iframe_html = '<iframe';
            foreach ($iframe_attributes as $attr => $value) {
                if ($value === '') {
                    $iframe_html .= " {$attr}";
                } else {
                    $iframe_html .= " {$attr}=\"{$value}\"";
                }
            }
            $iframe_html .= '></iframe>';

            // Generate direct video URL (for "View in new window" functionality)
            $direct_url = "https://video.bunnycdn.com/play/{$this->library_id}/{$video_id}";

            // Generate responsive wrapper HTML
            $responsive_html = '';
            if ($options['responsive']) {
                $responsive_html = '<div class="wfn-video-responsive-wrapper" style="position: relative; width: 100%; height: 0; padding-bottom: 56.25%; /* 16:9 aspect ratio */">' .
                    str_replace('style="width: 100%; height: auto; aspect-ratio: 16/9;"', 'style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;"', $iframe_html) .
                    '</div>';
            }

            return [
                'success' => true,
                'video_id' => $video_id,
                'library_id' => $this->library_id,
                'embed_data' => [
                    'iframe_html' => $iframe_html,
                    'responsive_html' => $responsive_html ?: $iframe_html,
                    'player_url' => $player_url,
                    'direct_url' => $direct_url,
                    'hls_playlist_url' => "https://{$this->cdn_hostname}.b-cdn.net/{$video_id}/playlist.m3u8",
                    'thumbnail_url' => "https://{$this->cdn_hostname}.b-cdn.net/{$video_id}/thumbnail.jpg",
                    'options_used' => $options
                ],
                'javascript_init' => $this->generate_video_player_js($video_id, $options)
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Exception generating embed code: ' . $e->getMessage(),
                'error_code' => 'EXCEPTION',
                'video_id' => $video_id
            ];
        }
    }

    /**
     * Generate JavaScript for video player initialization
     *
     * @param string $video_id Bunny Stream video ID
     * @param array $options Player options
     * @return string JavaScript code
     */
    private function generate_video_player_js(string $video_id, array $options): string {
        $js = "
        // Memorial Video Player - Video ID: {$video_id}
        (function() {
            const videoContainer = document.querySelector('[data-video-id=\"{$video_id}\"]');
            if (!videoContainer) return;

            const iframe = videoContainer.querySelector('iframe');
            if (!iframe) return;

            // Add loading state
            videoContainer.classList.add('wfn-video-loading');

            // Handle iframe load
            iframe.addEventListener('load', function() {
                videoContainer.classList.remove('wfn-video-loading');
                videoContainer.classList.add('wfn-video-ready');
            });

            // Handle errors
            iframe.addEventListener('error', function() {
                videoContainer.classList.remove('wfn-video-loading');
                videoContainer.classList.add('wfn-video-error');
                console.error('Failed to load memorial video: {$video_id}');
            });

            // Add fullscreen support for mobile
            if (typeof videoContainer.requestFullscreen === 'function') {
                const fullscreenBtn = videoContainer.querySelector('.wfn-fullscreen-btn');
                if (fullscreenBtn) {
                    fullscreenBtn.addEventListener('click', function() {
                        videoContainer.requestFullscreen();
                    });
                }
            }
        })();
        ";

        return trim($js);
    }

    /**
     * Get video thumbnail URL
     *
     * @param string $video_id Bunny Stream video ID
     * @param string $size Thumbnail size (small, medium, large)
     * @return string Thumbnail URL
     */
    public function get_video_thumbnail(string $video_id, string $size = 'medium'): string {
        if (!$this->is_configured() || empty($video_id)) {
            return '';
        }

        $sizes = [
            'small' => '320x180',
            'medium' => '640x360',
            'large' => '1280x720'
        ];

        $dimensions = $sizes[$size] ?? $sizes['medium'];

        return "https://{$this->cdn_hostname}.b-cdn.net/{$video_id}/thumbnail_{$dimensions}.jpg";
    }

    /**
     * Get HLS playlist URL for video
     *
     * @param string $video_id Bunny Stream video ID
     * @return string HLS playlist URL
     */
    public function get_hls_playlist_url(string $video_id): string {
        if (!$this->is_configured() || empty($video_id)) {
            return '';
        }

        return "https://{$this->cdn_hostname}.b-cdn.net/{$video_id}/playlist.m3u8";
    }

    /**
     * Get direct playback URL for video
     *
     * @param string $video_id Bunny Stream video ID
     * @return string Direct playback URL
     */
    public function get_direct_playback_url(string $video_id): string {
        if (!$this->is_configured() || empty($video_id)) {
            return '';
        }

        return "https://video.bunnycdn.com/play/{$this->library_id}/{$video_id}";
    }

    /**
     * Generate modal embed code for popup video player
     *
     * @param string $video_id Bunny Stream video ID
     * @param array $options Player and modal options
     * @return array Modal embed code and trigger button
     */
    public function get_modal_embed_code(string $video_id, array $options = []): array {
        if (!$this->is_configured()) {
            return [
                'success' => false,
                'message' => 'Bunny Stream not configured',
                'error_code' => 'NOT_CONFIGURED'
            ];
        }

        if (empty($video_id)) {
            return [
                'success' => false,
                'message' => 'Video ID is required',
                'error_code' => 'MISSING_VIDEO_ID'
            ];
        }

        // Default modal options
        $defaults = [
            'button_text' => 'View Slideshow',
            'button_class' => 'wfn-video-button',
            'modal_width' => '90%',
            'modal_max_width' => '800px',
            'close_on_overlay' => true,
            'show_new_window_link' => true,
            'new_window_text' => 'Open in New Window'
        ];

        $options = array_merge($defaults, $options);

        // Get the embed code for the video
        $embed_result = $this->get_embed_code($video_id, [
            'width' => '100%',
            'height' => '450',
            'autoplay' => false,
            'muted' => true,
            'responsive' => true
        ]);

        if (!$embed_result['success']) {
            return $embed_result;
        }

        $embed_data = $embed_result['embed_data'];
        $unique_id = 'wfn-video-modal-' . $video_id;

        // Generate trigger button HTML
        $button_html = sprintf(
            '<button type="button" class="%s" data-video-modal="%s" data-video-id="%s">%s</button>',
            esc_attr($options['button_class']),
            esc_attr($unique_id),
            esc_attr($video_id),
            esc_html($options['button_text'])
        );

        // Generate modal HTML
        $modal_html = sprintf('
        <div id="%s" class="wfn-video-modal" style="display: none;">
            <div class="wfn-video-modal-overlay"></div>
            <div class="wfn-video-modal-content" style="width: %s; max-width: %s;">
                <div class="wfn-video-modal-header">
                    <h3>Memorial Video Slideshow</h3>
                    <div class="wfn-video-modal-actions">
                        %s
                        <button type="button" class="wfn-video-modal-close" data-close-modal="%s">&times;</button>
                    </div>
                </div>
                <div class="wfn-video-modal-body">
                    <div class="wfn-video-container" data-video-id="%s" data-video-src="%s">
                        <div class="wfn-video-placeholder">
                            <p>Loading video...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>',
            esc_attr($unique_id),
            esc_attr($options['modal_width']),
            esc_attr($options['modal_max_width']),
            $options['show_new_window_link'] ? sprintf(
                '<a href="%s" target="_blank" class="wfn-video-new-window">%s</a>',
                esc_url($embed_data['direct_url']),
                esc_html($options['new_window_text'])
            ) : '',
            esc_attr($unique_id),
            esc_attr($video_id),
            esc_attr($embed_data['iframe_url'])
        );

        return [
            'success' => true,
            'video_id' => $video_id,
            'modal_data' => [
                'button_html' => $button_html,
                'modal_html' => $modal_html,
                'modal_id' => $unique_id,
                'trigger_selector' => '[data-video-modal="' . $unique_id . '"]',
                'direct_url' => $embed_data['direct_url'],
                'hls_playlist_url' => $embed_data['hls_playlist_url'],
                'thumbnail_url' => $embed_data['thumbnail_url']
            ],
            'javascript_init' => $this->generate_modal_player_js($unique_id, $options)
        ];
    }

    /**
     * Generate JavaScript for modal video player
     *
     * @param string $modal_id Unique modal ID
     * @param array $options Modal options
     * @return string JavaScript code
     */
    private function generate_modal_player_js(string $modal_id, array $options): string {
        $close_on_overlay = $options['close_on_overlay'] ? 'true' : 'false';

        $js = "
        // Memorial Video Modal - {$modal_id}
        (function() {
            const modal = document.getElementById('{$modal_id}');
            const trigger = document.querySelector('[data-video-modal=\"{$modal_id}\"]');
            const closeBtn = modal?.querySelector('[data-close-modal=\"{$modal_id}\"]');
            const overlay = modal?.querySelector('.wfn-video-modal-overlay');

            if (!modal || !trigger) return;

            // Open modal
            trigger.addEventListener('click', function(e) {
                e.preventDefault();
                modal.style.display = 'block';
                document.body.classList.add('wfn-video-modal-open');

                // Focus management for accessibility
                modal.setAttribute('aria-hidden', 'false');
                closeBtn?.focus();
            });

            // Close modal function
            const closeModal = function() {
                modal.style.display = 'none';
                document.body.classList.remove('wfn-video-modal-open');
                modal.setAttribute('aria-hidden', 'true');
                trigger.focus(); // Return focus to trigger
            };

            // Close button
            if (closeBtn) {
                closeBtn.addEventListener('click', closeModal);
            }

            // Close on overlay click
            if (overlay && {$close_on_overlay}) {
                overlay.addEventListener('click', closeModal);
            }

            // Close on Escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && modal.style.display === 'block') {
                    closeModal();
                }
            });

            // Initialize modal attributes
            modal.setAttribute('role', 'dialog');
            modal.setAttribute('aria-modal', 'true');
            modal.setAttribute('aria-hidden', 'true');
            modal.setAttribute('aria-labelledby', 'Memorial Video Slideshow');
        })();
        ";

        return trim($js);
    }

    /**
     * Create site-specific collection for organizing videos
     *
     * @param string $site_domain Domain name for collection organization
     * @return array Collection creation result
     */
    public function create_site_collection(string $site_domain): array {
        if (!$this->is_configured()) {
            return [
                'success' => false,
                'message' => 'Bunny Stream not configured',
                'error_code' => 'NOT_CONFIGURED'
            ];
        }

        // Sanitize domain name
        $clean_domain = sanitize_text_field($site_domain);
        $collection_name = 'Site: ' . $clean_domain;

        // Create collection data
        $collection_data = [
            'name' => $collection_name,
            'description' => 'Memorial videos for ' . $clean_domain,
            'tags' => [
                'site:' . str_replace('.', '-', $clean_domain),
                'funeral-notices',
                'memorial-videos'
            ]
        ];

        $endpoint = "/library/{$this->library_id}/collections";
        $response = $this->make_api_request($endpoint, 'POST', $collection_data);

        if (!$response['success']) {
            return [
                'success' => false,
                'message' => 'Failed to create collection: ' . $response['message'],
                'error_code' => 'COLLECTION_CREATE_FAILED',
                'api_response' => $response
            ];
        }

        $collection_id = $response['data']['guid'] ?? $response['data']['id'] ?? '';

        if (empty($collection_id)) {
            return [
                'success' => false,
                'message' => 'Collection created but ID not returned',
                'error_code' => 'COLLECTION_ID_MISSING'
            ];
        }

        // Log successful collection creation
        error_log("Bunny Stream collection created: {$collection_name} (ID: {$collection_id})");

        return [
            'success' => true,
            'message' => 'Collection created successfully',
            'collection_id' => $collection_id,
            'collection_name' => $collection_name,
            'site_domain' => $clean_domain,
            'created_at' => current_time('mysql', true)
        ];
    }

    /**
     * Get all site collections with statistics
     *
     * @return array List of site collections with video counts and storage usage
     */
    public function get_site_collections(): array {
        if (!$this->is_configured()) {
            return [
                'success' => false,
                'message' => 'Bunny Stream not configured',
                'error_code' => 'NOT_CONFIGURED'
            ];
        }

        $collections_response = $this->get_collections();

        if (!$collections_response['success']) {
            return $collections_response;
        }

        $site_collections = [];
        $collections = $collections_response['data'] ?? [];

        foreach ($collections as $collection) {
            // Only include site-specific collections
            if (strpos($collection['name'], 'Site: ') === 0) {
                $site_domain = str_replace('Site: ', '', $collection['name']);
                $collection_id = $collection['guid'] ?? $collection['id'] ?? '';

                // Get collection statistics
                $stats = $this->get_collection_statistics($collection_id);

                $site_collections[] = [
                    'collection_id' => $collection_id,
                    'collection_name' => $collection['name'],
                    'site_domain' => $site_domain,
                    'description' => $collection['description'] ?? '',
                    'video_count' => $collection['videoCount'] ?? 0,
                    'total_size' => $stats['total_size'] ?? 0,
                    'formatted_size' => $this->format_file_size($stats['total_size'] ?? 0),
                    'created_at' => $collection['dateCreated'] ?? '',
                    'last_modified' => $collection['dateModified'] ?? ''
                ];
            }
        }

        return [
            'success' => true,
            'site_collections' => $site_collections,
            'total_sites' => count($site_collections),
            'total_collections' => count($collections)
        ];
    }

    /**
     * Get collection statistics (video count, total size)
     *
     * @param string $collection_id Collection ID
     * @return array Collection statistics
     */
    public function get_collection_statistics(string $collection_id): array {
        $endpoint = "/library/{$this->library_id}/collections/{$collection_id}/videos";
        $response = $this->make_api_request($endpoint, 'GET');

        if (!$response['success']) {
            return [
                'video_count' => 0,
                'total_size' => 0,
                'error' => $response['message']
            ];
        }

        $videos = $response['data'] ?? [];
        $total_size = 0;

        foreach ($videos as $video) {
            $total_size += $video['storageSize'] ?? $video['size'] ?? 0;
        }

        return [
            'video_count' => count($videos),
            'total_size' => $total_size,
            'videos' => $videos
        ];
    }

    /**
     * Delete site collection and all its videos
     *
     * @param string $collection_id Collection ID to delete
     * @param bool $delete_videos Whether to delete all videos in collection
     * @return array Deletion result
     */
    public function delete_site_collection(string $collection_id, bool $delete_videos = true): array {
        if (!$this->is_configured()) {
            return [
                'success' => false,
                'message' => 'Bunny Stream not configured',
                'error_code' => 'NOT_CONFIGURED'
            ];
        }

        $deleted_videos = [];

        // Optionally delete all videos in collection first
        if ($delete_videos) {
            $stats = $this->get_collection_statistics($collection_id);

            if (isset($stats['videos'])) {
                foreach ($stats['videos'] as $video) {
                    $video_id = $video['guid'] ?? $video['id'] ?? '';
                    if ($video_id) {
                        $delete_result = $this->delete_video($video_id);
                        $deleted_videos[] = [
                            'video_id' => $video_id,
                            'title' => $video['title'] ?? 'Unknown',
                            'deleted' => $delete_result['success']
                        ];
                    }
                }
            }
        }

        // Delete the collection
        $endpoint = "/library/{$this->library_id}/collections/{$collection_id}";
        $response = $this->make_api_request($endpoint, 'DELETE');

        if (!$response['success']) {
            return [
                'success' => false,
                'message' => 'Failed to delete collection: ' . $response['message'],
                'error_code' => 'COLLECTION_DELETE_FAILED',
                'deleted_videos' => $deleted_videos
            ];
        }

        return [
            'success' => true,
            'message' => 'Collection deleted successfully',
            'collection_id' => $collection_id,
            'deleted_videos' => $deleted_videos,
            'video_count' => count($deleted_videos)
        ];
    }

    /**
     * Move video to different collection
     *
     * @param string $video_id Video ID
     * @param string $target_collection_id Target collection ID
     * @return array Move result
     */
    public function move_video_to_collection(string $video_id, string $target_collection_id): array {
        if (!$this->is_configured()) {
            return [
                'success' => false,
                'message' => 'Bunny Stream not configured',
                'error_code' => 'NOT_CONFIGURED'
            ];
        }

        $endpoint = "/library/{$this->library_id}/videos/{$video_id}";
        $update_data = ['collectionId' => $target_collection_id];

        $response = $this->make_api_request($endpoint, 'POST', $update_data);

        if (!$response['success']) {
            return [
                'success' => false,
                'message' => 'Failed to move video: ' . $response['message'],
                'error_code' => 'VIDEO_MOVE_FAILED'
            ];
        }

        return [
            'success' => true,
            'message' => 'Video moved successfully',
            'video_id' => $video_id,
            'target_collection_id' => $target_collection_id
        ];
    }

    /**
     * Get collection usage summary for all sites
     *
     * @return array Usage summary with costs and storage breakdown
     */
    public function get_collection_usage_summary(): array {
        $site_collections = $this->get_site_collections();

        if (!$site_collections['success']) {
            return $site_collections;
        }

        $total_storage = 0;
        $total_videos = 0;
        $site_breakdown = [];

        foreach ($site_collections['site_collections'] as $collection) {
            $total_storage += $collection['total_size'];
            $total_videos += $collection['video_count'];

            $site_breakdown[] = [
                'site_domain' => $collection['site_domain'],
                'video_count' => $collection['video_count'],
                'storage_used' => $collection['total_size'],
                'formatted_size' => $collection['formatted_size'],
                'collection_id' => $collection['collection_id']
            ];
        }

        // Sort by storage usage (highest first)
        usort($site_breakdown, fn($a, $b) => $b['storage_used'] <=> $a['storage_used']);

        return [
            'success' => true,
            'summary' => [
                'total_sites' => count($site_breakdown),
                'total_videos' => $total_videos,
                'total_storage' => $total_storage,
                'formatted_total_storage' => $this->format_file_size($total_storage),
                'average_per_site' => count($site_breakdown) > 0 ? round($total_storage / count($site_breakdown)) : 0
            ],
            'site_breakdown' => $site_breakdown,
            'generated_at' => current_time('mysql', true)
        ];
    }

    /**
     * Make API request to Bunny Stream using the retry mechanism
     *
     * @param string $endpoint API endpoint
     * @param string $method HTTP method
     * @param array $data Request data
     * @return array API response
     */
    private function make_api_request(string $endpoint, string $method = 'GET', array $data = []): array {
        $url = self::API_BASE_URL . $endpoint;

        // Use the new retry mechanism with proper headers
        $result = $this->make_http_request_with_retry(
            $url,
            $method,
            $data,
            ['Accept: application/json'], // Additional headers beyond the defaults
            [] // No additional cURL options needed
        );

        // Handle the response from retry mechanism
        if (!$result['success']) {
            return $result;
        }

        // Success - return in expected format
        return [
            'success' => true,
            'data' => $result['data'],
            'http_code' => $result['http_code']
        ];
    }

    /**
     * Handle API errors and provide user-friendly messages
     *
     * @param array $response API response with error info
     * @return array Processed error information
     */
    private function handle_api_error(array $response): array {
        $http_code = $response['http_code'] ?? 0;
        $response_data = $response['response'] ?? [];
        $raw_response = $response['raw_response'] ?? '';

        // Extract error message from Bunny Stream response
        $error_message = '';
        if (is_array($response_data)) {
            $error_message = $response_data['message'] ?? $response_data['error'] ?? $response_data['Message'] ?? '';
        }

        // Map HTTP codes to user-friendly messages
        $user_friendly_message = match($http_code) {
            400 => 'Invalid request data. Please check your video file and try again.',
            401 => 'Authentication failed. Please check your Bunny Stream API key.',
            403 => 'Access denied. Your API key may not have sufficient permissions.',
            404 => 'Resource not found. The video or library may not exist.',
            409 => 'Conflict occurred. The video may already exist or be processing.',
            413 => 'File too large. Please use a smaller video file (max 500MB).',
            429 => 'Too many requests. Please wait before trying again.',
            500, 502, 503, 504 => 'Bunny Stream server error. Please try again later.',
            default => $error_message ?: 'An unexpected error occurred while communicating with Bunny Stream.'
        };

        // Determine error code
        $error_code = match($http_code) {
            400 => 'BAD_REQUEST',
            401 => 'UNAUTHORIZED',
            403 => 'FORBIDDEN',
            404 => 'NOT_FOUND',
            409 => 'CONFLICT',
            413 => 'FILE_TOO_LARGE',
            429 => 'RATE_LIMITED',
            500, 502, 503, 504 => 'SERVER_ERROR',
            default => 'API_ERROR'
        };

        // Log detailed error for debugging
        error_log("Bunny Stream API error: HTTP {$http_code} - " . ($error_message ?: $raw_response));

        return [
            'success' => false,
            'message' => $user_friendly_message,
            'error_code' => $error_code,
            'http_code' => $http_code,
            'api_message' => $error_message,
            'debug_info' => defined('WFN_DEBUG') && WFN_DEBUG ? [
                'raw_response' => $raw_response,
                'response_data' => $response_data
            ] : null
        ];
    }

    /**
     * Get video transcoding status and encoding information
     *
     * @param string $video_id Bunny Stream video ID
     * @return array Transcoding status and details
     */
    public function get_transcoding_status(string $video_id): array {
        if (!$this->is_configured()) {
            return [
                'success' => false,
                'message' => 'Bunny Stream not configured',
                'error_code' => 'NOT_CONFIGURED'
            ];
        }

        if (empty($video_id)) {
            return [
                'success' => false,
                'message' => 'Video ID is required',
                'error_code' => 'MISSING_VIDEO_ID'
            ];
        }

        try {
            $url = self::API_BASE_URL . "/library/{$this->library_id}/videos/{$video_id}";
            $result = $this->make_http_request_with_retry($url, 'GET');

            if (!$result['success']) {
                return [
                    'success' => false,
                    'message' => 'Failed to get video transcoding status: ' . $result['message'],
                    'error_code' => 'API_REQUEST_FAILED',
                    'api_result' => $result
                ];
            }

            $video_data = $result['data'];

            // Parse transcoding status from video data
            $status = $this->parse_transcoding_status($video_data);

            return [
                'success' => true,
                'video_id' => $video_id,
                'transcoding_status' => $status,
                'raw_video_data' => $video_data
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Exception getting transcoding status: ' . $e->getMessage(),
                'error_code' => 'EXCEPTION',
                'video_id' => $video_id
            ];
        }
    }

    /**
     * Parse transcoding status from video data
     *
     * @param array $video_data Raw video data from API
     * @return array Parsed transcoding information
     */
    private function parse_transcoding_status(array $video_data): array {
        // Extract key transcoding information
        $status = [
            'overall_status' => $this->determine_overall_status($video_data),
            'upload_completed' => isset($video_data['storageSize']) && $video_data['storageSize'] > 0,
            'processing_progress' => $video_data['encodeProgress'] ?? 0,
            'is_ready_for_playback' => false,
            'created_date' => $video_data['dateUploaded'] ?? null,
            'duration_seconds' => $video_data['length'] ?? 0,
            'file_size_bytes' => $video_data['storageSize'] ?? 0,
            'resolutions_available' => [],
            'thumbnail_generated' => false,
            'errors' => []
        ];

        // Check if video is ready for playback
        $status['is_ready_for_playback'] = $this->is_video_ready_for_playback($video_data);

        // Extract available resolutions
        if (isset($video_data['videoLibrary']) && isset($video_data['videoLibrary']['resolutions'])) {
            $status['resolutions_available'] = $video_data['videoLibrary']['resolutions'];
        }

        // Check for thumbnail
        if (isset($video_data['thumbnailFileName']) && !empty($video_data['thumbnailFileName'])) {
            $status['thumbnail_generated'] = true;
        }

        // Extract any processing errors
        if (isset($video_data['status']) && $video_data['status'] < 0) {
            $status['errors'][] = $this->get_status_error_message($video_data['status']);
        }

        // Calculate estimated completion time if still processing
        if ($status['overall_status'] === 'processing' && $status['processing_progress'] > 0) {
            $status['estimated_completion'] = $this->estimate_completion_time($video_data);
        }

        return $status;
    }

    /**
     * Determine overall transcoding status
     *
     * @param array $video_data Video data from API
     * @return string Status: uploaded, processing, completed, failed
     */
    private function determine_overall_status(array $video_data): string {
        $status_code = $video_data['status'] ?? 0;
        $encode_progress = $video_data['encodeProgress'] ?? 0;

        // Status codes from Bunny Stream API documentation
        switch ($status_code) {
            case 0:
                return 'uploaded'; // Created, not yet processed
            case 1:
                return 'processing'; // Processing/encoding
            case 2:
                return 'completed'; // Finished processing
            case 3:
                return 'completed'; // Resolution finished
            case 4:
                return 'completed'; // Presigned upload finished
            default:
                if ($status_code < 0) {
                    return 'failed'; // Negative status codes indicate errors
                }
                return 'unknown';
        }
    }

    /**
     * Check if video is ready for playback
     *
     * @param array $video_data Video data from API
     * @return bool True if video can be played
     */
    private function is_video_ready_for_playback(array $video_data): bool {
        $status_code = $video_data['status'] ?? 0;
        $encode_progress = $video_data['encodeProgress'] ?? 0;

        // Video is ready if status is completed (2 or higher) or has significant encoding progress
        return $status_code >= 2 || $encode_progress >= 100;
    }

    /**
     * Get error message for status code
     *
     * @param int $status_code Negative status code indicating error
     * @return string Error message
     */
    private function get_status_error_message(int $status_code): string {
        $error_messages = [
            -1 => 'Upload failed or corrupted',
            -2 => 'Processing failed due to invalid file',
            -3 => 'Encoding failed',
            -4 => 'File too large or unsupported format',
            -5 => 'Processing timeout'
        ];

        return $error_messages[$status_code] ?? 'Unknown processing error (code: ' . $status_code . ')';
    }

    /**
     * Estimate completion time for processing video
     *
     * @param array $video_data Video data from API
     * @return array Completion estimate
     */
    private function estimate_completion_time(array $video_data): array {
        $progress = $video_data['encodeProgress'] ?? 0;
        $created_date = $video_data['dateUploaded'] ?? null;

        if (!$created_date || $progress <= 0) {
            return [
                'estimated_minutes' => null,
                'estimated_completion' => null,
                'confidence' => 'low'
            ];
        }

        try {
            $created_time = new \DateTime($created_date);
            $current_time = new \DateTime();
            $elapsed_minutes = ($current_time->getTimestamp() - $created_time->getTimestamp()) / 60;

            if ($elapsed_minutes > 0 && $progress > 10) {
                // Estimate based on current progress rate
                $remaining_progress = 100 - $progress;
                $rate_per_minute = $progress / $elapsed_minutes;
                $estimated_remaining_minutes = $remaining_progress / $rate_per_minute;

                $estimated_completion = clone $current_time;
                $estimated_completion->add(new \DateInterval('PT' . round($estimated_remaining_minutes) . 'M'));

                return [
                    'estimated_minutes' => round($estimated_remaining_minutes),
                    'estimated_completion' => $estimated_completion->format('Y-m-d H:i:s'),
                    'confidence' => $progress > 50 ? 'high' : 'medium'
                ];
            }
        } catch (\Exception $e) {
            // Ignore date parsing errors
        }

        return [
            'estimated_minutes' => null,
            'estimated_completion' => null,
            'confidence' => 'low'
        ];
    }

    /**
     * Monitor multiple videos' transcoding status
     *
     * @param array $video_ids Array of video IDs to monitor
     * @return array Status for all videos
     */
    public function monitor_transcoding_batch(array $video_ids): array {
        if (!$this->is_configured()) {
            return [
                'success' => false,
                'message' => 'Bunny Stream not configured',
                'error_code' => 'NOT_CONFIGURED'
            ];
        }

        if (empty($video_ids)) {
            return [
                'success' => false,
                'message' => 'No video IDs provided',
                'error_code' => 'NO_VIDEO_IDS'
            ];
        }

        $results = [
            'success' => true,
            'total_videos' => count($video_ids),
            'videos' => [],
            'summary' => [
                'completed' => 0,
                'processing' => 0,
                'failed' => 0,
                'unknown' => 0
            ]
        ];

        foreach ($video_ids as $video_id) {
            $status = $this->get_transcoding_status($video_id);
            $results['videos'][$video_id] = $status;

            if ($status['success']) {
                $overall_status = $status['transcoding_status']['overall_status'];
                switch ($overall_status) {
                    case 'completed':
                        $results['summary']['completed']++;
                        break;
                    case 'processing':
                        $results['summary']['processing']++;
                        break;
                    case 'failed':
                        $results['summary']['failed']++;
                        break;
                    default:
                        $results['summary']['unknown']++;
                        break;
                }
            } else {
                $results['summary']['unknown']++;
            }
        }

        return $results;
    }

    /**
     * Wait for video transcoding to complete (polling method)
     *
     * @param string $video_id Video ID to monitor
     * @param int $max_wait_seconds Maximum time to wait (default 10 minutes)
     * @param int $poll_interval_seconds How often to check status (default 30 seconds)
     * @return array Final transcoding status
     */
    public function wait_for_transcoding_completion(string $video_id, int $max_wait_seconds = 600, int $poll_interval_seconds = 30): array {
        if (!$this->is_configured()) {
            return [
                'success' => false,
                'message' => 'Bunny Stream not configured',
                'error_code' => 'NOT_CONFIGURED'
            ];
        }

        $start_time = time();
        $attempts = 0;

        while ((time() - $start_time) < $max_wait_seconds) {
            $attempts++;
            $status = $this->get_transcoding_status($video_id);

            if (!$status['success']) {
                return [
                    'success' => false,
                    'message' => 'Failed to check transcoding status: ' . $status['message'],
                    'error_code' => 'STATUS_CHECK_FAILED',
                    'attempts' => $attempts,
                    'elapsed_seconds' => time() - $start_time
                ];
            }

            $overall_status = $status['transcoding_status']['overall_status'];

            // Check if transcoding is complete (success or failure)
            if (in_array($overall_status, ['completed', 'failed'])) {
                return [
                    'success' => true,
                    'video_id' => $video_id,
                    'final_status' => $overall_status,
                    'transcoding_status' => $status['transcoding_status'],
                    'attempts' => $attempts,
                    'elapsed_seconds' => time() - $start_time,
                    'completed' => $overall_status === 'completed'
                ];
            }

            // Log progress if in debug mode
            if (defined('WFN_DEBUG') && WFN_DEBUG) {
                error_log(sprintf(
                    'BunnyStream: Transcoding progress for %s - Status: %s, Progress: %d%%, Attempt: %d',
                    $video_id,
                    $overall_status,
                    $status['transcoding_status']['processing_progress'],
                    $attempts
                ));
            }

            // Wait before next poll
            sleep($poll_interval_seconds);
        }

        // Timeout reached
        $final_status = $this->get_transcoding_status($video_id);

        return [
            'success' => false,
            'message' => 'Transcoding monitoring timeout reached',
            'error_code' => 'MONITORING_TIMEOUT',
            'video_id' => $video_id,
            'timeout_seconds' => $max_wait_seconds,
            'attempts' => $attempts,
            'final_status_check' => $final_status,
            'completed' => false
        ];
    }

    /**
     * List all videos in the library
     *
     * @param int $page Page number (1-based)
     * @param int $per_page Items per page (max 100)
     * @param string $search Optional search term
     * @return array Response with videos array or error
     */
    public function list_all_videos(int $page = 1, int $per_page = 100, string $search = ''): array {
        if (!$this->is_configured()) {
            return [
                'success' => false,
                'message' => 'Bunny Stream service not configured',
                'error_code' => 'NOT_CONFIGURED',
                'videos' => []
            ];
        }

        try {
            $params = [
                'page' => $page,
                'itemsPerPage' => min($per_page, 100)
            ];

            if (!empty($search)) {
                $params['search'] = $search;
            }

            $url = "https://video.bunnycdn.com/library/{$this->library_id}/videos?" . http_build_query($params);

            $response = wp_remote_get($url, [
                'timeout' => 30,
                'headers' => [
                    'AccessKey' => $this->api_key,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ]
            ]);

            if (is_wp_error($response)) {
                return [
                    'success' => false,
                    'message' => 'HTTP request failed: ' . $response->get_error_message(),
                    'error_code' => 'HTTP_ERROR',
                    'videos' => []
                ];
            }

            $response_code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);

            if ($response_code !== 200) {
                return [
                    'success' => false,
                    'message' => "API request failed with status {$response_code}: {$body}",
                    'error_code' => 'API_ERROR',
                    'response_code' => $response_code,
                    'videos' => []
                ];
            }

            $data = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return [
                    'success' => false,
                    'message' => 'Failed to parse JSON response: ' . json_last_error_msg(),
                    'error_code' => 'JSON_PARSE_ERROR',
                    'videos' => []
                ];
            }

            return [
                'success' => true,
                'message' => 'Videos retrieved successfully',
                'videos' => $data['items'] ?? [],
                'total_items' => $data['totalItems'] ?? 0,
                'current_page' => $data['currentPage'] ?? 1,
                'items_per_page' => $data['itemsPerPage'] ?? $per_page
            ];

        } catch (\Exception $e) {
            error_log('Bunny Stream list_all_videos error: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Request failed: ' . $e->getMessage(),
                'error_code' => 'EXCEPTION',
                'videos' => []
            ];
        }
    }

    /**
     * Create upload session for direct client-side upload
     *
     * Creates video entry in Bunny and returns direct upload URL
     * for JavaScript client to upload file chunks directly to CDN.
     *
     * @param array $metadata Upload metadata (title, filename, filesize, post_id)
     * @return array Session data with video_id and upload_url
     */
    public function create_upload_session(array $metadata): array {
        // Check license first
        if (!$this->is_licensed()) {
            return [
                'success' => false,
                'message' => 'Premium license required for video streaming',
                'error_code' => 'LICENSE_REQUIRED'
            ];
        }

        // Get site domain for collection organization
        $site_domain = parse_url(get_site_url(), PHP_URL_HOST) ?? 'unknown-site';

        // Create video entry in Bunny Stream
        $video_title = $metadata['title'] ?? 'Memorial Video - ' . date('Y-m-d H:i:s');
        $create_result = $this->create_video_entry($video_title, $metadata);

        if (!$create_result['success']) {
            return $create_result;
        }

        $video_id = $create_result['video_id'];

        // Add video to site collection for organization
        $collection_result = $this->add_video_to_collection($video_id, $site_domain);

        // Build direct upload URL
        $upload_url = self::API_BASE_URL . "/library/{$this->library_id}/videos/{$video_id}";

        return [
            'success' => true,
            'video_id' => $video_id,
            'upload_url' => $upload_url,
            'api_key' => $this->api_key, // Client needs this for Authorization header
            'chunk_size' => 5242880, // 5MB chunks
            'expires_at' => time() + 3600, // 1 hour expiry
            'library_id' => $this->library_id,
            'site_domain' => $site_domain,
            'collection_added' => $collection_result['success'] ?? false
        ];
    }

    /**
     * Get video status and transcoding progress
     *
     * @param string $video_id Bunny video ID
     * @return array Video status information
     */
    public function get_video_status(string $video_id): array {
        $endpoint = "/library/{$this->library_id}/videos/{$video_id}";
        $response = $this->make_api_request($endpoint, 'GET');

        if (!$response['success']) {
            return [
                'success' => false,
                'message' => 'Failed to get video status: ' . $response['message'],
                'error_code' => 'STATUS_CHECK_FAILED',
                'status' => 'unknown'
            ];
        }

        $video_data = $response['data'];

        return [
            'success' => true,
            'status' => $video_data['status'] ?? 'unknown',
            'video_id' => $video_id,
            'guid' => $video_data['guid'] ?? '',
            'title' => $video_data['title'] ?? '',
            'length' => $video_data['length'] ?? 0,
            'width' => $video_data['width'] ?? 0,
            'height' => $video_data['height'] ?? 0,
            'available_resolutions' => $video_data['availableResolutions'] ?? '',
            'thumbnail_count' => $video_data['thumbnailCount'] ?? 0,
            'encoding_progress' => $video_data['encodeProgress'] ?? 0,
            'storage_size' => $video_data['storageSize'] ?? 0,
            'has_mp4_fallback' => $video_data['hasMP4Fallback'] ?? false
        ];
    }
}