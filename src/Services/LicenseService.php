<?php
declare(strict_types=1);

namespace HumanKind\FuneralNotices\Services;

/**
 * Video availability service.
 *
 * Video hosting used to sit behind a premium licence key checked against
 * humankindwebsites.com. That gate never protected anything: the Bunny Stream
 * integration has always required the site's own library ID and API key, and
 * the site owner pays Bunny directly. There is no shared account to protect.
 *
 * As of 3.1.0 the feature is simply available whenever Bunny credentials are
 * present. Set them in wp-config.php:
 *
 *     define('HKFN_VIDEO_LIBRARY_ID',   '...');
 *     define('HKFN_VIDEO_API_KEY',      '...');
 *     define('HKFN_VIDEO_CDN_HOSTNAME', '...');
 *
 * ...or via the Video settings screen. Constants win over stored options.
 *
 * The class name and the hasValidVideoLicense() method are kept as they were so
 * any theme or snippet on an existing site keeps working.
 *
 * @package HumanKind\FuneralNotices\Services
 * @version 2.0.0
 */
class LicenseService {

    private static $instance = null;

    /**
     * Get singleton instance.
     *
     * Retained for backwards compatibility — the class holds no state now.
     */
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {}

    /**
     * Resolve the Bunny Stream library ID.
     *
     * Checks, in order: the BUNNYSTREAM_ constants (used by existing sites via
     * wp-config or a GridPane user-config), the VIDEO_ constants, then the
     * stored option. hkfn_get_constant() covers both HKFN_ and legacy WFN_
     * prefixes for each name.
     *
     * This is the single definition of "where do credentials come from" — both
     * the availability check and VideoModule read through it, so they cannot
     * disagree about whether a site is set up.
     */
    public static function getVideoLibraryId(): string {
        return (string) (
            hkfn_get_constant('BUNNYSTREAM_LIBRARY_ID')
            ?: hkfn_get_constant('VIDEO_LIBRARY_ID')
            ?: hkfn_get_option('bunny_library_id', '')
        );
    }

    /**
     * Resolve the Bunny Stream API key. See getVideoLibraryId() for the order.
     */
    public static function getVideoApiKey(): string {
        return (string) (
            hkfn_get_constant('BUNNYSTREAM_API_KEY')
            ?: hkfn_get_constant('VIDEO_API_KEY')
            ?: hkfn_get_option('bunny_api_key', '')
        );
    }

    /**
     * Resolve the Bunny CDN hostname. Optional — playback works without it.
     */
    public static function getVideoCdnHostname(): string {
        return (string) (
            hkfn_get_constant('BUNNYSTREAM_CDN_HOSTNAME')
            ?: hkfn_get_constant('VIDEO_CDN_HOSTNAME')
            ?: hkfn_get_option('bunny_cdn_hostname', '')
        );
    }

    /**
     * Is video hosting configured and therefore available?
     *
     * @return bool True when a Bunny Stream library ID and API key are set.
     */
    public static function isVideoConfigured(): bool {
        // Testing override, honoured for backwards compatibility.
        if (hkfn_get_constant('BYPASS_LICENSE')) {
            return true;
        }

        return self::getVideoLibraryId() !== '' && self::getVideoApiKey() !== '';
    }

    /**
     * Which pieces of video configuration are missing?
     *
     * @return array List of human-readable missing field names.
     */
    public static function getMissingVideoConfig(): array {
        $missing = [];

        if (self::getVideoLibraryId() === '') {
            $missing[] = 'Library ID';
        }

        if (self::getVideoApiKey() === '') {
            $missing[] = 'API Key';
        }

        return $missing;
    }

    /**
     * Message shown when video is used but not configured.
     *
     * @return string
     */
    public static function getVideoNotConfiguredMessage(): string {
        return 'Video hosting is not configured. Add your Bunny Stream library ID and API key in the Video settings, or define HKFN_VIDEO_LIBRARY_ID and HKFN_VIDEO_API_KEY in wp-config.php.';
    }

    /**
     * Whether video streaming is available.
     *
     * @deprecated 3.1.0 Use isVideoConfigured(). Kept so existing site code keeps working.
     * @return bool
     */
    public static function hasValidVideoLicense(): bool {
        return self::isVideoConfigured();
    }

    /**
     * Message shown when video is unavailable.
     *
     * @deprecated 3.1.0 Use getVideoNotConfiguredMessage().
     * @param string $feature Unused, retained for signature compatibility.
     * @return string
     */
    public static function getFeatureRequiresLicenseMessage(string $feature = 'video_streaming'): string {
        return self::getVideoNotConfiguredMessage();
    }
}
