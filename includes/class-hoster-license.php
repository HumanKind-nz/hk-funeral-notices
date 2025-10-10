<?php
/**
 * Hoster License Handler
 * Integrates with HumanKind Websites licensing system
 *
 * @package    HK_Funeral_Notices
 * @subpackage License
 * @version    1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('HK_Funeral_Notices_License_Handler')) {
    class HK_Funeral_Notices_License_Handler
    {
        private $api_url = 'https://humankindwebsites.com/wp-json/hoster/v1';
        private $download_id = 8031;

        // Cache settings
        private const CACHE_KEY = 'wfn_license_status';
        private const CACHE_DURATION = 12; // Hours
        private const ERROR_CACHE_DURATION = 1; // Hour for error responses

        /**
         * Initialize the license handler
         */
        public static function init() {
            static $instance = null;

            if ($instance === null) {
                $instance = new self();
            }

            return $instance;
        }

        /**
         * Activate a license
         *
         * @param string $license_key The license key
         * @param string $site_url Optional site URL, defaults to current site
         * @return array Response from API
         */
        public function activate_license($license_key, $site_url = null) {
            if (empty($site_url)) {
                $site_url = get_site_url();
            }

            $response = $this->send_request('hoster-activate-license', $this->download_id, $license_key, $site_url);

            // Clear cache on activation attempt
            delete_transient(self::CACHE_KEY);

            // If activation was successful, store the token and license key
            if (isset($response['success']) && $response['success']) {
                // Store license key for future reference
                update_option('wfn_license_key', $license_key);

                // Store hoster token for secure updates (if provided)
                if (isset($response['token']) && !empty($response['token'])) {
                    update_option('wfn_hoster_token', $response['token']);
                    error_log('WFN: Hoster token stored successfully for secure updates');
                } else {
                    error_log('WFN: No token received in activation response');
                }
            }

            return $response;
        }

        /**
         * Deactivate a license
         *
         * @param string $license_key The license key
         * @param string $site_url Optional site URL, defaults to current site
         * @return array Response from API
         */
        public function deactivate_license($license_key, $site_url = null) {
            if (empty($site_url)) {
                $site_url = get_site_url();
            }

            $response = $this->send_request('hoster-deactivate-license', $this->download_id, $license_key, $site_url);

            // Clear cache on deactivation
            delete_transient(self::CACHE_KEY);

            // If deactivation was successful, clean up stored license data
            if (isset($response['success']) && $response['success']) {
                // Remove stored license key and token
                delete_option('wfn_license_key');
                delete_option('wfn_hoster_token');
                error_log('WFN: License key and token cleaned up after deactivation');
            }

            return $response;
        }

        /**
         * Check license status with caching
         *
         * @param string $license_key The license key
         * @param string $site_url Optional site URL, defaults to current site
         * @param bool $force_check Force fresh check, skip cache
         * @return array Response from API
         */
        public function check_license($license_key, $site_url = null, $force_check = false) {
            if (empty($site_url)) {
                $site_url = get_site_url();
            }


            // Check cache first unless forced
            if (!$force_check) {
                $cached = get_transient(self::CACHE_KEY . '_' . md5($license_key));
                if (false !== $cached) {
                    return $cached;
                }
            }

            $response = $this->send_request('hoster-check-license', $this->download_id, $license_key, $site_url);

            // Cache the response
            if ($response && isset($response['success'])) {
                if ($response['success']) {
                    // Cache successful responses longer
                    set_transient(self::CACHE_KEY . '_' . md5($license_key), $response, self::CACHE_DURATION * HOUR_IN_SECONDS);
                } else {
                    // Cache error responses shorter
                    set_transient(self::CACHE_KEY . '_' . md5($license_key), $response, self::ERROR_CACHE_DURATION * HOUR_IN_SECONDS);
                }
            }

            return $response;
        }

        /**
         * Send request to hoster API
         *
         * @param string $endpoint API endpoint
         * @param int $download_id Download ID
         * @param string $license_key License key
         * @param string $site_url Site URL
         * @return array Response from API
         */
        private function send_request($endpoint, $download_id, $license_key, $site_url) {
            $response = wp_remote_post("{$this->api_url}/{$endpoint}/", [
                'body' => json_encode([
                    'download_id' => $download_id,
                    'license_key' => $license_key,
                    'site_url' => $site_url
                ]),
                'headers' => [
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'HK Funeral Notices/' . WFN_VERSION . '; ' . get_bloginfo('url')
                ],
                'timeout' => 15,
            ]);

            if (is_wp_error($response)) {
                error_log('HK Funeral Notices License API Error: ' . $response->get_error_message());
                return [
                    'success' => false,
                    'message' => 'Connection failed: ' . $response->get_error_message()
                ];
            }

            $response_code = wp_remote_retrieve_response_code($response);
            if ($response_code !== 200) {
                error_log('HK Funeral Notices License API HTTP Error: ' . $response_code);
                return [
                    'success' => false,
                    'message' => 'Server error: HTTP ' . $response_code
                ];
            }

            $body = wp_remote_retrieve_body($response);
            $decoded = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log('HK Funeral Notices License API JSON Error: ' . json_last_error_msg());
                return [
                    'success' => false,
                    'message' => 'Invalid response format'
                ];
            }

            return $decoded;
        }

        /**
         * Clear all license caches
         */
        public function clear_cache() {
            global $wpdb;

            // Delete all transients starting with our cache key
            $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
                    '_transient_' . self::CACHE_KEY . '%',
                    '_transient_timeout_' . self::CACHE_KEY . '%'
                )
            );
        }

        /**
         * Clean up license data when license becomes invalid/expired
         * This should be called when check_license returns invalid/expired status
         */
        public function cleanup_invalid_license() {
            delete_option('wfn_license_key');
            delete_option('wfn_hoster_token');

            // Update license status to invalid
            update_option('wfn_license_status', [
                'valid' => false,
                'features' => [],
                'expires' => '',
                'message' => 'License expired or invalid',
                'license_type' => '',
                'site_limit' => '',
                'customer_name' => '',
                'last_check' => current_time('mysql'),
                'validated_at' => ''
            ]);

            $this->clear_cache();
            error_log('WFN: Cleaned up invalid/expired license data');
        }

        /**
         * Get stored hoster token for secure updates
         */
        public function get_stored_token() {
            return get_option('wfn_hoster_token', '');
        }
    }
}