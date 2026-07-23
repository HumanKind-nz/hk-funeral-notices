<?php
/**
 * Funeral Location Address Partial Template
 * Replaces Tangible Template: [WFN] Funeral Location Address
 * 
 * Shows location details with Google Maps integration
 * Handles both custom addresses and taxonomy-based locations
 * 
 * @var int $post_id
 * @var array $args
 * @var string $mode
 */

// Get template manager instance to fetch structured data
$template_manager = new \HumanKind\FuneralNotices\Templates\TemplateManager();
$data = $template_manager->get_funeral_data($post_id);

// Check if using custom location
if ($data['location']['is_other_location'] && !empty($data['location']['custom_address'])): 
    $address = $data['location']['custom_address'];
    $components = $data['location']['address_components'];
    $formatted_address = $data['location']['formatted_address'];
    $maps_url = $data['location']['maps_url'];
    
    // Use enhanced address structure if available, fallback to legacy
    $address_name = $components['venue_name'] ?? $address['name'] ?? '';
    $display_address = $formatted_address ?: ($address['address'] ?? '');

    // Fallback for legacy structure
    if (!$display_address && is_array($address)) {
        $street_number = $address['street_number'] ?? '';
        $street_name = $address['street_name'] ?? '';
        $city = $address['city'] ?? '';
        $post_code = $address['post_code'] ?? '';
        $display_address = trim("{$street_number} {$street_name}, {$city}, {$post_code}");
    }

    // Prevent duplicate display: if venue name is just the street address, don't show it separately
    // This happens when Google Places returns the street address as the "name" for residential addresses
    if ($address_name && $display_address) {
        // Check if venue name appears at the start of the formatted address (case-insensitive)
        if (stripos($display_address, $address_name) === 0) {
            $address_name = ''; // Clear venue name to avoid duplication
        }
    }
    ?>
    <h5 class="details">WHERE</h5>
    <div class="address">
        <?php if ($address_name): ?>
            <strong><?php echo esc_html($address_name); ?></strong><br />
        <?php endif; ?>
        <?php if ($display_address): ?>
            <?php echo esc_html($display_address); ?><br />
        <?php endif; ?>
        <?php if ($maps_url): ?>
            <div class="view-link">
                <a href="<?php echo esc_url($maps_url); ?>" target="_blank" rel="noopener">
                    View on Google Maps
                </a>
            </div>
        <?php endif; ?>
    </div>

<?php else:
    // Use taxonomy-based location
    $location_terms = get_the_terms($post_id, 'funeral-location');
    if ($location_terms && !is_wp_error($location_terms)):
        foreach ($location_terms as $location): 
            $location_address = get_field('location_address', "funeral-location_{$location->term_id}");
            $map_link = get_field('location_map_link', "funeral-location_{$location->term_id}");
            ?>
            <h5 class="details">WHERE</h5>
            <div class="address">
                <strong><?php echo esc_html($location->name); ?></strong><br />
                <?php if ($location_address): ?>
                    <?php echo esc_html($location_address); ?><br />
                <?php endif; ?>
                <?php 
                    $map_url = '';
                    if (!empty($map_link)) {
                        if (is_array($map_link)) {
                            $map_url = $map_link['url'] ?? '';
                        } elseif (is_string($map_link)) {
                            $map_url = $map_link;
                        }
                    }
                ?>
                <?php if (!empty($map_url)): ?>
                    <a class="fn_loc_name" href="<?php echo esc_url($map_url); ?>" target="_blank" rel="noopener">
                        View on Google Maps
                    </a>
                <?php endif; ?>
            </div>
            <?php
        endforeach;
    endif;
endif; ?> 