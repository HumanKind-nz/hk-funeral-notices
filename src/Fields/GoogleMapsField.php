<?php
declare(strict_types=1);

namespace HumanKind\FuneralNotices\Fields;

/**
 * Custom Google Maps ACF Field
 * 
 * Provides Google Places autocomplete and mapping functionality
 * Compatible with ACFE Pro Google Maps data structure
 * 
 * @since 2.0.0
 */
class GoogleMapsField extends \acf_field {
    
    public function __construct() {
        $this->name = 'hkfn_google_maps';
        $this->label = 'Google Maps (WFN)';
        $this->category = 'choice';
        $this->defaults = [
            'center_lat' => '-41.2865',
            'center_lng' => '174.7762',
            'zoom' => 14,
            'height' => 400,
        ];
        
        parent::__construct();
    }
    
    /**
     * Render the field HTML
     */
    public function render_field($field) {
        $value = $field['value'] ?: [];
        $field_name = $field['name'];
        $field_key = $field['key'];
        
        // Ensure we have an array structure
        if (is_string($value)) {
            $value = [];
        }
        
        // Default values
        $address = $value['address'] ?? '';
        $lat = $value['lat'] ?? 0;
        $lng = $value['lng'] ?? 0;
        $zoom = $value['zoom'] ?? $field['zoom'] ?? 16;
        $place_id = $value['place_id'] ?? '';
        
        ?>
        <div class="hkfn-google-maps-field" data-field-key="<?php echo esc_attr($field_key); ?>">
            
            <!-- Address Input with Autocomplete -->
            <div class="hkfn-address-input-wrapper">
                <label for="<?php echo esc_attr($field_key); ?>_address_input">
                    Search for an address:
                </label>
                <input 
                    type="text" 
                    id="<?php echo esc_attr($field_key); ?>_address_input"
                    class="hkfn-address-autocomplete" 
                    placeholder="Start typing an address..."
                    value="<?php echo esc_attr($address); ?>"
                />
            </div>
            
            <!-- Map Display -->
            <div class="hkfn-map-container" style="margin: 10px 0;">
                <div 
                    id="<?php echo esc_attr($field_key); ?>_map" 
                    class="hkfn-map-display"
                    style="height: <?php echo intval($field['height']); ?>px; border: 1px solid #ddd; border-radius: 4px;"
                ></div>
            </div>
            
            <!-- Status Display -->
            <div class="hkfn-address-status" style="margin: 5px 0; font-size: 12px; color: #666;">
                <span id="<?php echo esc_attr($field_key); ?>_status">
                    <?php if ($address): ?>
                        ✓ Address selected: <?php echo esc_html($address); ?>
                    <?php else: ?>
                        No address selected
                    <?php endif; ?>
                </span>
            </div>
            
            <!-- Hidden Fields for Data Storage (ACFE Compatible Structure) -->
            <input type="hidden" name="<?php echo esc_attr($field_name); ?>[address]" value="<?php echo esc_attr($value['address'] ?? ''); ?>" />
            <input type="hidden" name="<?php echo esc_attr($field_name); ?>[lat]" value="<?php echo esc_attr($value['lat'] ?? ''); ?>" />
            <input type="hidden" name="<?php echo esc_attr($field_name); ?>[lng]" value="<?php echo esc_attr($value['lng'] ?? ''); ?>" />
            <input type="hidden" name="<?php echo esc_attr($field_name); ?>[zoom]" value="<?php echo esc_attr($value['zoom'] ?? 16); ?>" />
            <input type="hidden" name="<?php echo esc_attr($field_name); ?>[place_id]" value="<?php echo esc_attr($value['place_id'] ?? ''); ?>" />
            <input type="hidden" name="<?php echo esc_attr($field_name); ?>[name]" value="<?php echo esc_attr($value['name'] ?? ''); ?>" />
            <input type="hidden" name="<?php echo esc_attr($field_name); ?>[street_number]" value="<?php echo esc_attr($value['street_number'] ?? ''); ?>" />
            <input type="hidden" name="<?php echo esc_attr($field_name); ?>[street_name]" value="<?php echo esc_attr($value['street_name'] ?? ''); ?>" />
            <input type="hidden" name="<?php echo esc_attr($field_name); ?>[street_name_short]" value="<?php echo esc_attr($value['street_name_short'] ?? ''); ?>" />
            <input type="hidden" name="<?php echo esc_attr($field_name); ?>[city]" value="<?php echo esc_attr($value['city'] ?? ''); ?>" />
            <input type="hidden" name="<?php echo esc_attr($field_name); ?>[state]" value="<?php echo esc_attr($value['state'] ?? ''); ?>" />
            <input type="hidden" name="<?php echo esc_attr($field_name); ?>[post_code]" value="<?php echo esc_attr($value['post_code'] ?? ''); ?>" />
            <input type="hidden" name="<?php echo esc_attr($field_name); ?>[country]" value="<?php echo esc_attr($value['country'] ?? ''); ?>" />
            <input type="hidden" name="<?php echo esc_attr($field_name); ?>[country_short]" value="<?php echo esc_attr($value['country_short'] ?? ''); ?>" />
            
        </div>
        <?php
    }
    
    /**
     * Render field settings
     */
    public function render_field_settings($field) {
        ?>
        <tr class="field_option field_option_<?php echo $this->name; ?>">
            <td class="label">
                <label for=""><?php esc_html_e("Map Center Latitude", 'acf'); ?></label>
            </td>
            <td>
                <?php 
                acf_render_field_setting($field, [
                    'label' => '',
                    'instructions' => 'Default center latitude for new maps',
                    'type' => 'text',
                    'name' => 'center_lat',
                    'placeholder' => '-41.2865'
                ]);
                ?>
            </td>
        </tr>
        
        <tr class="field_option field_option_<?php echo $this->name; ?>">
            <td class="label">
                <label for=""><?php esc_html_e("Map Center Longitude", 'acf'); ?></label>
            </td>
            <td>
                <?php 
                acf_render_field_setting($field, [
                    'label' => '',
                    'instructions' => 'Default center longitude for new maps',
                    'type' => 'text',
                    'name' => 'center_lng',
                    'placeholder' => '174.7762'
                ]);
                ?>
            </td>
        </tr>
        
        <tr class="field_option field_option_<?php echo $this->name; ?>">
            <td class="label">
                <label for=""><?php esc_html_e("Default Zoom", 'acf'); ?></label>
            </td>
            <td>
                <?php 
                acf_render_field_setting($field, [
                    'label' => '',
                    'instructions' => 'Default zoom level (1-20)',
                    'type' => 'number',
                    'name' => 'zoom',
                    'min' => 1,
                    'max' => 20,
                    'step' => 1,
                    'placeholder' => 14
                ]);
                ?>
            </td>
        </tr>
        
        <tr class="field_option field_option_<?php echo $this->name; ?>">
            <td class="label">
                <label for=""><?php esc_html_e("Map Height", 'acf'); ?></label>
            </td>
            <td>
                <?php 
                acf_render_field_setting($field, [
                    'label' => '',
                    'instructions' => 'Height of the map in pixels',
                    'type' => 'number',
                    'name' => 'height',
                    'min' => 200,
                    'max' => 800,
                    'step' => 10,
                    'placeholder' => 400
                ]);
                ?>
            </td>
        </tr>
        <?php
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function input_admin_enqueue_scripts() {
        // Get API key from module settings
        $settings = hkfn_get_option('module_settings', []);
        $api_key = $settings['google_places_api_key'] ?? '';
        
        // Fallback to ACF options for backwards compatibility
        if (empty($api_key)) {
            $api_key = get_field('hkfn_google_places_api_key', 'option') ?: '';
        }
        
        if (!$api_key) {
            // Show admin notice if no API key
            add_action('admin_notices', function() {
                echo '<div class="notice notice-warning"><p>';
                echo '<strong>HumanKind Funeral Notices:</strong> a Google Places API key is needed for address autocomplete. ';
                echo '<a href="' . admin_url('admin.php?page=hk-funeral-notices-settings') . '">Add one under Settings, General, Advanced</a>';
                echo '</p></div>';
            });
            return;
        }
        
        // Debug logging removed for production

        // Feature flag for modern vs legacy API loading (rollback capability)
        $use_modern_api = apply_filters('hkfn_use_modern_google_maps_api', true);

        if ($use_modern_api) {
            // Modern async loading (no callback, better performance)
            wp_enqueue_script(
                'hkfn-google-maps-api',
                "https://maps.googleapis.com/maps/api/js?key={$api_key}&libraries=places&loading=async",
                [],
                null,
                true
            );
        } else {
            // Legacy callback method (fallback for compatibility)
            wp_enqueue_script(
                'hkfn-google-maps-api',
                "https://maps.googleapis.com/maps/api/js?key={$api_key}&libraries=places&callback=initWFNGoogleMaps",
                [],
                null,
                true
            );
        }
        
        // Enqueue our custom field JavaScript
        wp_enqueue_script(
            'hkfn-google-maps-field',
            plugin_dir_url(__FILE__) . '../../assets/js/admin/google-maps-field.js',
            ['hkfn-google-maps-api', 'jquery'],
            HKFN_VERSION,
            true
        );

        // Enqueue field styles
        wp_enqueue_style(
            'hkfn-google-maps-field',
            plugin_dir_url(__FILE__) . '../../assets/css/admin/google-maps-field.css',
            [],
            HKFN_VERSION
        );
    }
    
    /**
     * Format value for return/display
     */
    public function format_value($value, $post_id, $field) {
        // Return the ACFE-compatible structure
        if (!is_array($value)) {
            return [];
        }
        
        // Ensure all expected fields exist
        $formatted = [
            'address' => $value['address'] ?? '',
            'lat' => (float) ($value['lat'] ?? 0),
            'lng' => (float) ($value['lng'] ?? 0),
            'zoom' => (int) ($value['zoom'] ?? 16),
            'place_id' => $value['place_id'] ?? '',
            'name' => $value['name'] ?? '',
            'street_number' => $value['street_number'] ?? '',
            'street_name' => $value['street_name'] ?? '',
            'street_name_short' => $value['street_name_short'] ?? '',
            'city' => $value['city'] ?? '',
            'state' => $value['state'] ?? '',
            'post_code' => $value['post_code'] ?? '',
            'country' => $value['country'] ?? '',
            'country_short' => $value['country_short'] ?? '',
        ];
        
        return $formatted;
    }
    
    /**
     * Load value from database
     */
    public function load_value($value, $post_id, $field) {
        // Handle serialized data if needed
        if (is_string($value) && !empty($value)) {
            $decoded = maybe_unserialize($value);
            if (is_array($decoded)) {
                return $decoded;
            }
        }
        
        return is_array($value) ? $value : [];
    }
    
    /**
     * Update value before saving
     */
    public function update_value($value, $post_id, $field) {
        // Ensure we're saving an array
        if (!is_array($value)) {
            return [];
        }
        
        // Clean up numeric values
        if (isset($value['lat'])) {
            $value['lat'] = (float) $value['lat'];
        }
        if (isset($value['lng'])) {
            $value['lng'] = (float) $value['lng'];
        }
        if (isset($value['zoom'])) {
            $value['zoom'] = (int) $value['zoom'];
        }
        
        return $value;
    }
}