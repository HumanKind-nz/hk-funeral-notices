<?php
declare(strict_types=1);

namespace WeaveStudios\FuneralNotices\Address;

/**
 * Address Field Manager
 * 
 * Handles hybrid compatibility between ACFE Pro Google Maps fields and custom native fields.
 * Provides unified data access regardless of which system is being used.
 * 
 * @since 2.0.0
 */
class AddressFieldManager {
    
    /**
     * Field mode constants
     */
    const MODE_AUTO = 'auto';
    const MODE_ACFE = 'acfe';
    const MODE_CUSTOM = 'custom';
    
    /**
     * Data source constants
     */
    const SOURCE_ACFE = 'acfe';
    const SOURCE_CUSTOM = 'custom';
    const SOURCE_LEGACY = 'legacy';
    const SOURCE_UNKNOWN = 'unknown';
    
    /**
     * Detect which field system is available/preferred
     * 
     * @return string Field mode (auto, acfe, custom)
     */
    public function get_field_mode(): string {
        // Check module settings first (allows manual override)
        $settings = get_option('wfn_module_settings', []);
        $admin_preference = $settings['address_field_mode'] ?? 'auto';
        
        // Debug logging
        error_log('WFN: Module settings preference: ' . $admin_preference);
        
        // Also check ACF options as fallback for backwards compatibility
        if (empty($admin_preference) || $admin_preference === 'auto') {
            $admin_preference = get_field('wfn_address_field_mode', 'option') ?: 'auto';
            error_log('WFN: ACF options preference: ' . $admin_preference);
        }
        
        if ($admin_preference === self::MODE_CUSTOM) {
            error_log('WFN: Forced custom mode');
            return self::MODE_CUSTOM;
        }
        
        if ($admin_preference === self::MODE_ACFE) {
            error_log('WFN: Forced ACFE mode');
            return self::MODE_ACFE;
        }
        
        // Auto-detect based on plugin availability (default behavior)
        $acfe_available = $this->is_acfe_available();
        error_log('WFN: ACFE available: ' . ($acfe_available ? 'yes' : 'no'));
        
        if ($acfe_available) {
            error_log('WFN: Auto-detected ACFE mode');
            return self::MODE_ACFE;
        }
        
        // Default to custom field
        error_log('WFN: Defaulting to custom mode');
        return self::MODE_CUSTOM;
    }
    
    /**
     * Check if ACFE Pro is available and has Google Maps field
     * 
     * @return bool
     */
    public function is_acfe_available(): bool {
        // Check if ACFE class exists
        if (!class_exists('acfe')) {
            return false;
        }
        
        // Check if ACF functions are available
        if (!function_exists('acf_get_field_type')) {
            return false;
        }
        
        // Check if ACFE Google Maps field type exists
        $acfe_maps_field = acf_get_field_type('acfe_google_maps');
        return !empty($acfe_maps_field);
    }
    
    /**
     * Get address data with automatic fallback across all sources
     * 
     * @param int $post_id
     * @return array Normalized address data
     */
    public function get_address_data($post_id): array {
        // Try new custom field first (wfn_details_group -> custom_address)
        $custom_data = get_field('wfn_details_group', $post_id);
        if (!empty($custom_data['custom_address']) && is_array($custom_data['custom_address'])) {
            return $this->normalize_address_data($custom_data['custom_address'], self::SOURCE_CUSTOM);
        }
        
        // Try ACFE field (wfn_location_group -> other_funeral_address)
        $location_group = get_field('wfn_location_group', $post_id);
        if (!empty($location_group['other_funeral_address']) && is_array($location_group['other_funeral_address'])) {
            return $this->normalize_address_data($location_group['other_funeral_address'], self::SOURCE_ACFE);
        }
        
        // Try direct ACFE field access (meta key format)
        $acfe_direct = get_field('wfn_location_group_other_funeral_address', $post_id);
        if (!empty($acfe_direct) && is_array($acfe_direct)) {
            return $this->normalize_address_data($acfe_direct, self::SOURCE_ACFE);
        }
        
        // Try legacy simple text address
        $simple_address = get_field('wfn_details_group', $post_id);
        if (!empty($simple_address['custom_address']) && is_string($simple_address['custom_address'])) {
            return $this->convert_simple_address($simple_address['custom_address']);
        }
        
        // No address data found
        return $this->get_empty_address_data();
    }
    
    /**
     * Normalize address data from different sources into consistent format
     * 
     * @param array $data Raw address data
     * @param string $source Data source identifier
     * @return array Normalized address data
     */
    private function normalize_address_data($data, $source): array {
        if (!is_array($data)) {
            return $this->get_empty_address_data();
        }
        
        // Base structure (ACFE compatible)
        $normalized = [
            'address' => '',
            'lat' => 0,
            'lng' => 0,
            'zoom' => 16,
            'place_id' => '',
            'name' => '',
            'street_number' => '',
            'street_name' => '',
            'street_name_short' => '',
            'city' => '',
            'state' => '',
            'post_code' => '',
            'country' => '',
            'country_short' => '',
            'source' => $source,
            'has_coordinates' => false,
            'has_place_id' => false,
        ];
        
        // Merge with actual data
        $merged = array_merge($normalized, $data);
        
        // Set convenience flags
        $merged['has_coordinates'] = !empty($merged['lat']) && !empty($merged['lng']);
        $merged['has_place_id'] = !empty($merged['place_id']);
        
        // Ensure numeric types
        $merged['lat'] = (float) $merged['lat'];
        $merged['lng'] = (float) $merged['lng'];
        $merged['zoom'] = (int) ($merged['zoom'] ?: 16);
        
        return $merged;
    }
    
    /**
     * Convert simple text address to structured format
     * 
     * @param string $simple_address
     * @return array
     */
    private function convert_simple_address($simple_address): array {
        $base = $this->get_empty_address_data();
        $base['address'] = trim($simple_address);
        $base['source'] = self::SOURCE_LEGACY;
        
        return $base;
    }
    
    /**
     * Get empty address data structure
     * 
     * @return array
     */
    private function get_empty_address_data(): array {
        return [
            'address' => '',
            'lat' => 0,
            'lng' => 0,
            'zoom' => 16,
            'place_id' => '',
            'name' => '',
            'street_number' => '',
            'street_name' => '',
            'street_name_short' => '',
            'city' => '',
            'state' => '',
            'post_code' => '',
            'country' => '',
            'country_short' => '',
            'source' => self::SOURCE_UNKNOWN,
            'has_coordinates' => false,
            'has_place_id' => false,
        ];
    }
    
    /**
     * Generate Google Maps URL from address data
     * 
     * @param array $address_data Normalized address data
     * @return string Google Maps URL
     */
    public function generate_maps_url($address_data): string {
        if (empty($address_data)) {
            return '';
        }
        
        // Use Place ID for maximum accuracy (best option)
        if (!empty($address_data['place_id'])) {
            return "https://www.google.com/maps/place/?q=place_id:{$address_data['place_id']}";
        }
        
        // Use coordinates as fallback (good accuracy)
        if ($address_data['has_coordinates']) {
            return "https://www.google.com/maps/place/{$address_data['lat']},{$address_data['lng']}";
        }
        
        // Use formatted address as final fallback
        if (!empty($address_data['address'])) {
            return "https://www.google.com/maps/place/" . urlencode($address_data['address']);
        }
        
        return '';
    }
    
    /**
     * Get formatted address display string
     * 
     * @param array $address_data Normalized address data
     * @return string Formatted address for display
     */
    public function get_formatted_address($address_data): string {
        if (empty($address_data)) {
            return '';
        }
        
        // Use the full formatted address if available
        if (!empty($address_data['address'])) {
            return $address_data['address'];
        }
        
        // Build from components if no formatted address
        $parts = array_filter([
            trim($address_data['street_number'] . ' ' . $address_data['street_name']),
            $address_data['city'],
            $address_data['state'],
            $address_data['post_code'],
            $address_data['country']
        ]);
        
        return implode(', ', $parts);
    }
    
    /**
     * Get address components for display
     * 
     * @param array $address_data Normalized address data
     * @return array Address components
     */
    public function get_address_components($address_data): array {
        if (empty($address_data)) {
            return [];
        }
        
        return [
            'venue_name' => $address_data['name'] ?: '',
            'street_address' => trim($address_data['street_number'] . ' ' . $address_data['street_name']),
            'city' => $address_data['city'] ?: '',
            'state' => $address_data['state'] ?: '',
            'post_code' => $address_data['post_code'] ?: '',
            'country' => $address_data['country'] ?: '',
            'full_address' => $this->get_formatted_address($address_data),
        ];
    }
    
    /**
     * Check if Google Places API key is configured
     * 
     * @return bool
     */
    public function is_api_key_configured(): bool {
        // Check module settings first
        $settings = get_option('wfn_module_settings', []);
        $api_key = $settings['google_places_api_key'] ?? '';
        
        // Debug logging
        error_log('WFN: API key from module settings: ' . (empty($api_key) ? 'empty' : 'present (' . strlen($api_key) . ' chars)'));
        
        // Fallback to ACF options for backwards compatibility
        if (empty($api_key)) {
            $api_key = get_field('wfn_google_places_api_key', 'option') ?: '';
            error_log('WFN: API key from ACF options: ' . (empty($api_key) ? 'empty' : 'present (' . strlen($api_key) . ' chars)'));
        }
        
        $is_configured = !empty($api_key) && strlen($api_key) > 20;
        error_log('WFN: API key configured: ' . ($is_configured ? 'yes' : 'no'));
        
        return $is_configured;
    }
    
    /**
     * Get status information for admin display
     * 
     * @return array Status information
     */
    public function get_status_info(): array {
        $mode = $this->get_field_mode();
        $is_acfe_available = $this->is_acfe_available();
        $is_api_configured = $this->is_api_key_configured();
        
        $status_map = [
            self::MODE_ACFE => [
                'icon' => '🔧',
                'label' => 'ACFE Pro',
                'description' => 'Using ACFE Pro Google Maps fields',
                'color' => '#00a0d2',
                'available' => $is_acfe_available,
            ],
            self::MODE_CUSTOM => [
                'icon' => '⚡',
                'label' => 'Native',
                'description' => 'Using built-in Google Maps fields',
                'color' => '#00a32a',
                'available' => true,
            ],
        ];
        
        return [
            'mode' => $mode,
            'info' => $status_map[$mode] ?? $status_map[self::MODE_CUSTOM],
            'acfe_available' => $is_acfe_available,
            'api_configured' => $is_api_configured,
            'warnings' => $this->get_status_warnings($mode, $is_acfe_available, $is_api_configured),
        ];
    }
    
    /**
     * Get status warnings for admin display
     * 
     * @param string $mode Current field mode
     * @param bool $is_acfe_available Whether ACFE is available
     * @param bool $is_api_configured Whether API key is configured
     * @return array Warning messages
     */
    private function get_status_warnings($mode, $is_acfe_available, $is_api_configured): array {
        $warnings = [];
        
        if (!$is_api_configured) {
            $warnings[] = 'Google Places API key not configured. Address autocomplete will not work.';
        }
        
        if ($mode === self::MODE_ACFE && !$is_acfe_available) {
            $warnings[] = 'ACFE Pro not available but mode is set to ACFE. Falling back to native fields.';
        }
        
        return $warnings;
    }
    
    /**
     * Log address data access for debugging
     * 
     * @param int $post_id
     * @param array $address_data
     * @param string $context
     */
    public function log_address_access($post_id, $address_data, $context = ''): void {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $source = $address_data['source'] ?? 'unknown';
            $has_data = !empty($address_data['address']);
            
            error_log(sprintf(
                'WFN Address Access: Post %d, Source %s, Has Data: %s, Context: %s',
                $post_id,
                $source,
                $has_data ? 'Yes' : 'No',
                $context
            ));
        }
    }
}