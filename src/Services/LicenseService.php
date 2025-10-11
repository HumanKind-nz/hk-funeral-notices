<?php
declare(strict_types=1);

namespace WeaveStudios\FuneralNotices\Services;

/**
 * License Service
 * Handles premium feature validation and license checking
 *
 * @package WeaveStudios\FuneralNotices\Services
 * @version 1.0.0
 */
class LicenseService {

    private static $instance = null;
    private $license_handler = null;

    // Cache key for license status
    private const LICENSE_STATUS_CACHE = 'wfn_license_status_cache';

    /**
     * Get singleton instance
     */
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Private constructor for singleton
     */
    private function __construct() {
        // Initialize license handler if available
        if (class_exists('HK_Funeral_Notices_License_Handler')) {
            $this->license_handler = \HK_Funeral_Notices_License_Handler::init();
        }
    }

    /**
     * Check if video streaming feature is licensed
     *
     * @return bool
     */
    public static function hasValidVideoLicense(): bool {
        return self::getInstance()->checkFeatureLicense(WFN_PREMIUM_FEATURE_VIDEO);
    }

    /**
     * Check if a specific premium feature is licensed
     *
     * @param string $feature Feature identifier
     * @return bool
     */
    public function checkFeatureLicense(string $feature): bool {

        // For now, we only have video streaming as premium feature
        if ($feature !== WFN_PREMIUM_FEATURE_VIDEO) {
            return true; // Non-premium features are always available
        }

        // Get stored license key
        $license_key = get_option('wfn_license_key', '');
        if (empty($license_key)) {
            return false;
        }

        // Check cached status first
        $cached_status = get_transient(self::LICENSE_STATUS_CACHE . '_' . md5($license_key));
        if ($cached_status !== false) {
            return $cached_status['valid'] ?? false;
        }

        // If no license handler is available, fallback to stored license status
        if ($this->license_handler === null) {
            $license_status = get_option('wfn_license_status', []);
            return ($license_status['valid'] ?? false) === true;
        }

        // Check license with API
        $response = $this->license_handler->check_license($license_key);

        $is_valid = false;
        if ($response && isset($response['success']) && $response['success'] === true) {
            $is_valid = isset($response['status']) && $response['status'] === 'active';
        }

        // Cache the result
        set_transient(
            self::LICENSE_STATUS_CACHE . '_' . md5($license_key),
            ['valid' => $is_valid],
            4 * HOUR_IN_SECONDS
        );

        return $is_valid;
    }

    /**
     * Get license status information
     *
     * @return array
     */
    public function getLicenseStatus(): array {

        $license_key = get_option('wfn_license_key', '');

        if (empty($license_key)) {
            return [
                'status' => 'inactive',
                'message' => 'No license key entered',
                'type' => 'error'
            ];
        }

        if ($this->license_handler === null) {
            return [
                'status' => 'error',
                'message' => 'License system unavailable',
                'type' => 'error'
            ];
        }

        // Force fresh check for status display
        $response = $this->license_handler->check_license($license_key, null, true);

        if (!$response) {
            return [
                'status' => 'error',
                'message' => 'Unable to verify license',
                'type' => 'error'
            ];
        }

        if (isset($response['success']) && $response['success'] === true) {
            if (isset($response['status']) && $response['status'] === 'active') {
                return [
                    'status' => 'active',
                    'message' => 'License is active and valid',
                    'type' => 'success',
                    'license_data' => $response
                ];
            } else {
                // License is invalid/expired - clean up stored data
                if ($this->license_handler !== null) {
                    $this->license_handler->cleanup_invalid_license();
                }
                return [
                    'status' => 'invalid',
                    'message' => $response['message'] ?? 'License is not valid',
                    'type' => 'error'
                ];
            }
        } else {
            // License verification failed - clean up stored data
            if ($this->license_handler !== null) {
                $this->license_handler->cleanup_invalid_license();
            }
            return [
                'status' => 'invalid',
                'message' => $response['message'] ?? 'License verification failed',
                'type' => 'error'
            ];
        }
    }

    /**
     * Activate a license key
     *
     * @param string $license_key
     * @return array
     */
    public function activateLicense(string $license_key): array {
        if ($this->license_handler === null) {
            return [
                'success' => false,
                'message' => 'License system unavailable'
            ];
        }

        // Clean and store the license key
        $license_key = sanitize_text_field(trim($license_key));

        if (empty($license_key)) {
            return [
                'success' => false,
                'message' => 'Please enter a valid license key'
            ];
        }

        // Attempt activation
        $response = $this->license_handler->activate_license($license_key);

        if ($response && isset($response['success']) && $response['success'] === true) {
            // Store the license key
            update_option('wfn_license_key', $license_key);

            // Update license status to active
            update_option('wfn_license_status', [
                'valid' => true,
                'features' => ['video_hosting'],
                'expires' => $response['expires'] ?? '',
                'message' => 'License activated successfully',
                'license_type' => 'premium',
                'site_limit' => $response['site_limit'] ?? 'Unlimited',
                'customer_name' => $response['customer_name'] ?? '',
                'last_check' => current_time('mysql'),
                'validated_at' => current_time('mysql')
            ]);

            // Clear license cache
            $this->clearLicenseCache();

            return [
                'success' => true,
                'message' => 'License activated successfully'
            ];
        } else {
            return [
                'success' => false,
                'message' => $response['message'] ?? 'License activation failed'
            ];
        }
    }

    /**
     * Deactivate the current license
     *
     * @return array
     */
    public function deactivateLicense(): array {
        if ($this->license_handler === null) {
            return [
                'success' => false,
                'message' => 'License system unavailable'
            ];
        }

        $license_key = get_option('wfn_license_key', '');

        if (empty($license_key)) {
            return [
                'success' => false,
                'message' => 'No license key to deactivate'
            ];
        }

        // Attempt deactivation
        $response = $this->license_handler->deactivate_license($license_key);

        // Remove the license key regardless of API response
        delete_option('wfn_license_key');

        // Update license status to invalid
        update_option('wfn_license_status', [
            'valid' => false,
            'features' => [],
            'expires' => '',
            'message' => 'License deactivated',
            'license_type' => '',
            'site_limit' => '',
            'customer_name' => '',
            'last_check' => current_time('mysql'),
            'validated_at' => ''
        ]);

        // Clear license cache
        $this->clearLicenseCache();

        if ($response && isset($response['success']) && $response['success'] === true) {
            return [
                'success' => true,
                'message' => 'License deactivated successfully'
            ];
        } else {
            return [
                'success' => true, // Still successful locally even if API fails
                'message' => 'License removed locally' . (isset($response['message']) ? ' (' . $response['message'] . ')' : '')
            ];
        }
    }

    /**
     * Get premium feature availability status
     *
     * @return array
     */
    public function getPremiumFeatureStatus(): array {
        return [
            'video_streaming' => [
                'available' => $this->checkFeatureLicense(WFN_PREMIUM_FEATURE_VIDEO),
                'name' => 'Video Streaming',
                'description' => 'Upload and display video slideshows using BunnyStream'
            ]
        ];
    }

    /**
     * Clear all license-related caches
     */
    public function clearLicenseCache(): void {
        global $wpdb;

        // Clear license status cache
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
                '_transient_' . self::LICENSE_STATUS_CACHE . '%',
                '_transient_timeout_' . self::LICENSE_STATUS_CACHE . '%'
            )
        );

        // Clear license handler cache if available
        if ($this->license_handler && method_exists($this->license_handler, 'clear_cache')) {
            $this->license_handler->clear_cache();
        }
    }

    /**
     * Get the stored license key
     *
     * @return string
     */
    public function getLicenseKey(): string {
        return get_option('wfn_license_key', '');
    }

    /**
     * Get user-friendly error message for unlicensed feature access
     *
     * @param string $feature
     * @return string
     */
    public static function getFeatureRequiresLicenseMessage(string $feature = 'video_streaming'): string {
        switch ($feature) {
            case 'video_streaming':
                return 'Video streaming features require a premium license. Please contact your administrator to activate this feature.';
            default:
                return 'This premium feature requires a valid license.';
        }
    }
}