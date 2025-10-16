<?php
declare(strict_types=1);

namespace WeaveStudios\FuneralNotices\Templates;

use WeaveStudios\FuneralNotices\Services\LicenseService;

use WeaveStudios\FuneralNotices\Address\AddressFieldManager;
use WeaveStudios\FuneralNotices\Streaming\StreamingDetector;

/**
 * Template Manager - handles loading and rendering template partials
 * Replaces Tangible Templates with PHP-based conditional templates
 * 
 * @since 2.0.0
 */
class TemplateManager {

    private string $template_path;
    private array $modes;
    private AddressFieldManager $address_manager;
    private StreamingDetector $streaming_detector;

    public function __construct() {
        $this->template_path = plugin_dir_path(dirname(__FILE__, 2)) . 'templates/';
        $this->address_manager = new AddressFieldManager();
        $this->streaming_detector = new StreamingDetector();
        
        $this->modes = [
            'firehawk' => [
                'name' => 'Firehawk Tributes Compatible',
                'description' => 'Grid layout matching Firehawk CRM styling',
                'css' => 'firehawk-compat.css'
            ],
            'modern' => [
                'name' => 'Modern Memorial',
                'description' => 'Clean, contemporary funeral notice design',
                'css' => 'modern.css'
            ],
            'elegant' => [
                'name' => 'Elegant Funeral',
                'description' => 'Formal, traditional funeral styling',
                'css' => 'elegant.css'
            ]
        ];
    }

    /**
     * Get the active display mode
     * Decides between single and archive modes with sensible fallbacks.
     */
    public function get_active_mode(): string {
        // Pull settings for sane defaults
        $settings = get_option('wfn_module_settings', []);
        $default_single  = $settings['default_single_layout']  ?? 'current';
        $default_archive = $settings['default_archive_layout'] ?? 'modern';
        
        // Single funeral notice page
        if (function_exists('is_singular') && is_singular('funeral-notice')) {
            return get_option('wfn_single_display_mode', $default_single);
        }
        
        // Archive page for funeral notices
        if (function_exists('is_post_type_archive') && is_post_type_archive('funeral-notice')) {
            return get_option('wfn_archive_display_mode', $default_archive);
        }
        
        // General fallback (shortcodes pick their own layout, so this is rarely used)
        return get_option('wfn_display_mode', $default_archive);
    }

    /**
     * Get the active archive/shortcode display mode
     */
    public function get_archive_mode(): string {
        return get_option('wfn_archive_display_mode', $this->get_active_mode());
    }

    /**
     * Get the active single page display mode
     */
    public function get_single_mode(): string {
        return get_option('wfn_single_display_mode', $this->get_active_mode());
    }

    /**
     * Get all available display modes
     */
    public function get_modes(): array {
        return $this->modes;
    }

    /**
     * Render a template partial with data
     * 
     * @param string $template_name The template name (e.g., 'date-time', 'streaming')
     * @param int $post_id The funeral notice post ID
     * @param array $args Additional arguments
     * @return string Rendered HTML
     */
    public function render_partial(string $template_name, int $post_id, array $args = []): string {
        // Sanitize template name
        $template_name = sanitize_file_name($template_name);
        
        // Build template path
        $template_file = $this->template_path . "partials/{$template_name}.php";
        
        if (!file_exists($template_file)) {
            return "<!-- Template not found: {$template_name} -->";
        }

        // Set up template variables
        $post_id = (int) $post_id;
        $args = wp_parse_args($args, [
            'mode' => $this->get_active_mode(),
            'class_prefix' => 'wfn'
        ]);

        // Start output buffering
        ob_start();
        
        // Include template with isolated scope
        include $template_file;
        
        return ob_get_clean();
    }

    /**
     * Render a complete template (archive or single)
     * 
     * @param string $template_type 'archive' or 'single'
     * @param array $args Template arguments
     * @return string Rendered HTML
     */
    public function render_template(string $template_type, array $args = []): string {
        $mode = $this->get_active_mode();
        $template_file = $this->template_path . "modes/{$mode}/{$template_type}.php";
        
        // Fallback to default if mode-specific template doesn't exist
        if (!file_exists($template_file)) {
            $template_file = $this->template_path . "modes/default/{$template_type}.php";
        }
        
        if (!file_exists($template_file)) {
            return "<!-- Template not found: {$template_type} in mode {$mode} -->";
        }

        // Set up template variables
        $args = wp_parse_args($args, [
            'mode' => $mode,
            'class_prefix' => 'wfn'
        ]);

        ob_start();
        include $template_file;
        return ob_get_clean();
    }

    /**
     * Get template data for a funeral notice
     * Centralizes all the field retrieval logic
     * 
     * @param int $post_id
     * @return array Structured template data
     */
    public function get_funeral_data(int $post_id): array {
        // Personal details - handle both grouped and individual field formats
        $person_group = get_field('wfn_person_group', $post_id) ?: [];

        // Try grouped format first, fallback to individual fields
        $first_name = $person_group['firstname'] ?? get_field('wfn_person_group_firstname', $post_id) ?? '';
        $last_name = $person_group['lastname'] ?? get_field('wfn_person_group_lastname', $post_id) ?? '';
        $birth_year = $person_group['birth_year'] ?? get_field('wfn_person_group_birth_year', $post_id) ?? '';
        $death_year = $person_group['death_year'] ?? get_field('wfn_person_group_death_year', $post_id) ?? '';

        // Event details - handle both grouped and individual field formats
        $details_group = get_field('wfn_details_group', $post_id) ?: [];
        $funeral_date = $details_group['funeral_date'] ?? get_field('wfn_details_group_funeral_date', $post_id) ?? '';
        $funeral_time = $details_group['funeral_time'] ?? get_field('wfn_details_group_funeral_time', $post_id) ?? '';
        $hide_time = $details_group['hide_time'] ?? get_field('wfn_details_group_hide_datetime', $post_id) ?? false;

        // Content - ACF uses post content field directly via acfe_post_field
        $notice_group = get_field('wfn_notice_group', $post_id) ?: [];
        $hide_intro = $notice_group['hide_intro_copy'] ?? false;
        
        // Get the actual post content (where the funeral notice is stored)
        $notice_content = get_post_field('post_content', $post_id);
        $notice_content = apply_filters('the_content', $notice_content);

        // Streaming - simplified approach based on URL presence only
        $streaming_group = get_field('wfn_streaming_group', $post_id) ?: [];

        // Get streaming URL with legacy fallback - handle empty strings properly
        $streaming_url = '';

        // Try various field sources, checking for non-empty values
        $url_sources = [
            $streaming_group['streaming_url'] ?? '',
            get_field('wfn_streaming_group_streaming_url', $post_id) ?? '',
            get_field('wfn_streaming_group_oneroom_streaming_link', $post_id) ?? '', // OneRoom legacy field
            get_field('wfn_streaming_group_public_streaming_link', $post_id) ?? '' // General legacy field
        ];

        foreach ($url_sources as $potential_url) {
            if (!empty(trim($potential_url))) {
                $streaming_url = trim($potential_url);
                break;
            }
        }
        
        // Get streaming service (legacy and new)
        $streaming_service_raw = $streaming_group['streaming_service'] ?? 
                                get_field('wfn_streaming_group_streaming_service', $post_id) ?? 
                                '';
        
        // Privacy settings
        $is_private = $streaming_group['streaming_private'] ?? 
                     get_field('wfn_streaming_group_streaming_private', $post_id) ?? 
                     false;
        
        // Streaming note
        $streaming_note = $streaming_group['streaming_note'] ?? 
                         get_field('wfn_streaming_group_streaming_note', $post_id) ?? 
                         '';
        
        // Streaming detection - simply check if we have a valid URL
        // No need for has_streaming field - URL presence determines availability
        $has_streaming = !empty($streaming_url);

        // Use StreamingDetector for comprehensive streaming service detection
        $streaming_info = $this->streaming_detector->detect_service($streaming_url);

        // PREFER auto-detected service from URL over old stored value
        // This prevents issues where old service type (e.g. "oneroom") conflicts with new URL (e.g. iStream)
        // Only use streaming_service_raw if auto-detection returns 'none' or 'other'
        $auto_detected_service = $streaming_info['service'] ?? 'none';
        if (!empty($auto_detected_service) && !in_array($auto_detected_service, ['none', 'other'])) {
            // Auto-detection found a specific service (youtube, vimeo, oneroom, istream, etc)
            $streaming_service = $auto_detected_service;
        } else {
            // Auto-detection couldn't determine service, use stored value as fallback
            $streaming_service = $streaming_service_raw ?: $auto_detected_service;
        }

        $embed_code = $streaming_info['embed'] ?? '';

        // Location - Enhanced with unified address handling
        $location_type = $details_group['location_type'] ?? get_field('wfn_details_group_location_type', $post_id) ?? null;
        $location_taxonomy = $details_group['location'] ?? get_field('wfn_details_group_location', $post_id) ?? null;
        
        // Use unified address manager for enhanced compatibility, with fallback to direct field access
        $custom_address = $this->address_manager->get_address_data($post_id);

        // Fallback: if address manager doesn't return data, try direct field access
        if (empty($custom_address) || empty($custom_address['address'])) {
            $custom_address = get_field('wfn_details_group_custom_address', $post_id) ?: [];
        }
        
        // Backwards compatibility (v1) - check for old checkbox structure
        if (!$location_type) {
            $is_other_location_array = $details_group['is_at_another_location'] ?? [];
            $is_other_location = in_array('yes', $is_other_location_array);
            $old_other_address = $details_group['other_address'] ?? '';
            
            if ($is_other_location) {
                $location_type = $old_other_address ? 'custom' : 'existing';
                // For backwards compatibility, convert old simple address to AddressFieldManager format
                if ($old_other_address && empty($custom_address['address'])) {
                    $custom_address = ['address' => $old_other_address, 'source' => 'legacy'];
                }
            } else {
                $location_type = 'existing';
            }
        }
        
        // Get location name and taxonomy fields if selected
        $location_name = '';
        $taxonomy_term_id = 0;
        $taxonomy_address = '';
        $taxonomy_map_url = '';
        if ($location_taxonomy) {
            if (is_object($location_taxonomy)) {
                $location_name = $location_taxonomy->name;
                $taxonomy_term_id = (int) $location_taxonomy->term_id;
            } else {
                $location_term = get_term($location_taxonomy, 'funeral-location');
                if ($location_term && !is_wp_error($location_term)) {
                    $location_name = $location_term->name;
                    $taxonomy_term_id = (int) $location_term->term_id;
                }
            }
            // Fetch ACF fields from taxonomy term for physical address and map link
            if ($taxonomy_term_id) {
                $taxonomy_address = get_field('location_address', 'funeral-location_' . $taxonomy_term_id) ?: '';
                $map_link = get_field('location_map_link', 'funeral-location_' . $taxonomy_term_id);
                if (is_array($map_link) && !empty($map_link['url'])) {
                    $taxonomy_map_url = $map_link['url'];
                }
            }
        }
        
        // Get formatted address components for display
        $address_components = $this->address_manager->get_address_components($custom_address);
        // If using an existing taxonomy location and the taxonomy has a physical address, prefer it
        if (($details_group['location_type'] ?? null) === 'existing' && !empty($taxonomy_address)) {
            $formatted_address = trim((string) $taxonomy_address);
        } else {
            $formatted_address = $this->address_manager->get_formatted_address($custom_address);
        }

        // Determine display_venue with duplicate check
        // For custom addresses, check if venue name is just the street address to avoid duplication
        $display_venue = '';
        if ($location_type === 'existing') {
            $display_venue = $location_name;
        } elseif ($location_type === 'custom') {
            $venue_name = $address_components['venue_name'] ?? '';
            // Prevent duplicate: if venue name appears at start of formatted address, it's a street address, not a business
            if ($venue_name && $formatted_address && stripos($formatted_address, $venue_name) === 0) {
                $display_venue = ''; // Don't show duplicate street address as venue name
            } else {
                $display_venue = $venue_name;
            }
        }

        return [
            'post_id' => $post_id,
            'post_url' => get_permalink($post_id),
            'person' => [
                'first_name' => $first_name,
                'last_name' => $last_name,
                'full_name' => trim("{$first_name} {$last_name}"),
                'birth_year' => $birth_year,
                'death_year' => $death_year,
                'years_display' => $birth_year && $death_year ? "{$birth_year} - {$death_year}" : ''
            ],
            'event' => [
                'funeral_date' => $funeral_date,
                'funeral_time' => $funeral_time,
                'hide_time' => $hide_time,
                'formatted_date' => $funeral_date ? date('M j, Y', strtotime($funeral_date)) : '',
                'formatted_time' => $funeral_time ? date('g:i A', strtotime($funeral_time)) : ''
            ],
            'content' => [
                'notice' => $notice_content,
                'hide_intro' => $hide_intro,
                'excerpt' => $notice_content ? wp_trim_words(strip_tags($notice_content), 60) : ''
            ],
            'streaming' => [
                'has_streaming' => $has_streaming,
                'is_private' => $is_private,
                'is_public' => $has_streaming && !$is_private, // Regular streaming public if not private (videos are handled separately)
                'streaming_service' => $streaming_service,
                'streaming_url' => $streaming_url,
                'embed_code' => $embed_code,
                'streaming_note' => $streaming_note,
                'can_embed' => in_array($streaming_service, ['oneroom', 'youtube', 'vimeo', 'facebook']),
                'is_button_only' => in_array($streaming_service, ['other', 'vimeo_pro', 'istream']),
                'license_required' => false, // Regular streaming URLs don't require license
                'has_video_or_license' => LicenseService::hasValidVideoLicense() // For video modal/button display
            ],
            'location' => [
                'type' => $location_type,
                'taxonomy_location' => $location_name,
                'custom_address' => $custom_address,
                'display_venue' => $display_venue,
                'display_address' => ($location_type === 'custom') ? $custom_address : [],
                'formatted_address' => $formatted_address,
                'address_components' => $address_components,
                'show_location' => $location_type !== 'none',
                'maps_url' => !empty($taxonomy_map_url)
                    ? $taxonomy_map_url
                    : $this->generate_enhanced_maps_url($location_type, $location_name, $custom_address),
                // Backwards compatibility
                'is_other_location' => $location_type === 'custom',
                'other_address' => $custom_address, // For legacy template compatibility
            ],
            'image' => [
                'featured_url' => get_the_post_thumbnail_url($post_id, 'medium'),
                'fallback_url' => $this->get_fallback_image_url(),
                'hero_background' => $this->get_hero_background_data($post_id)
            ],
            'tribute' => [
                'base_url' => $this->get_tribute_url(),
                'full_url' => $this->generate_tribute_url($first_name, $last_name),
                'has_url' => !empty($this->get_tribute_url()),
                'show_button' => apply_filters('wfn_show_tribute_button', true, $post_id)
            ],
            'documents' => [
                'service_sheet' => $this->get_service_sheet_data($post_id),
                'video_slideshow' => $this->get_video_slideshow_data($post_id),
                'additional' => $this->get_additional_documents($post_id)
            ]
        ];
    }

    /**
     * Get fallback image URL with robust error handling
     */
    private function get_fallback_image_url(): string {
        // Get fallback image from Settings module
        $settings = get_option('wfn_module_settings', []);
        $fallback_url = $settings['default_person_image'] ?? '';

        if (!empty($fallback_url)) {
            return $fallback_url;
        }

        // Default fallback to bundled image
        return plugin_dir_url(dirname(__FILE__, 2)) . 'assets/images/fallback.webp';
    }

    /**
     * Get hero background image data (from sitewide options)
     */
    private function get_hero_background_data(int $post_id): array {
        // Get hero background from ACF options (sitewide setting)
        $hero_background = get_field('wfn_hero_background_image', 'option');
        
        if (is_array($hero_background) && !empty($hero_background['url'])) {
            return [
                'url' => $hero_background['url'],
                'alt' => $hero_background['alt'] ?? '',
                'width' => $hero_background['width'] ?? 0,
                'height' => $hero_background['height'] ?? 0,
                'has_image' => true
            ];
        }
        
        return [
            'url' => '',
            'alt' => '',
            'width' => 0,
            'height' => 0,
            'has_image' => false
        ];
    }

    /**
     * Clean address data to remove coordinates and other unwanted fields
     */
    private function clean_address_data($address_data): array {
        if (!$address_data) {
            return [];
        }
        
        // If it's a string, return as array
        if (is_string($address_data)) {
            return [trim($address_data)];
        }
        
        // If it's an array, filter out non-display fields
        if (is_array($address_data)) {
            $clean_address = [];
            
            // Handle ACF Google Maps field structure - prioritize the main address
            if (isset($address_data['address']) && !empty($address_data['address'])) {
                $clean_address[] = trim($address_data['address']);
                // Don't add other fields if we have the main address to avoid duplicates
                return array_filter($clean_address, function($item) {
                    return !empty(trim($item));
                });
            }
            
            // Handle simple array of strings (fallback)
            foreach ($address_data as $key => $value) {
                if (is_string($value) && !empty(trim($value))) {
                    // Skip coordinates and other technical fields
                    $skip_fields = ['lat', 'lng', 'latitude', 'longitude', 'zoom', 'place_id', 'address', 
                                   'height', 'min_zoom', 'max_zoom', 'marker', 'map_type', 'hide_ui', 
                                   'hide_zoom_control', 'hide_map_selection', 'hide_fullscreen', 
                                   'hide_streetview', 'map_style', 'key', 'country_short', 'street_name_short'];
                    
                    if (!in_array($key, $skip_fields)) {
                        // Also skip if value looks like coordinates (decimal numbers)
                        if (!preg_match('/^-?\d+\.\d+$/', $value)) {
                            // Skip very long strings that might be encoded data
                            if (strlen($value) < 200) {
                                $clean_address[] = trim($value);
                            }
                        }
                    }
                }
            }
            
            // Remove duplicates and empty values
            $clean_address = array_filter(array_unique($clean_address), function($item) {
                return !empty(trim($item));
            });
            
            // Limit to first 2 address lines to avoid clutter
            return array_slice(array_values($clean_address), 0, 2);
        }
        
        return [];
    }

    // REMOVED: Old streaming detection methods - now using StreamingDetector class
    // for comprehensive streaming service support including OneRoom, Facebook, and others

    /**
     * Generate enhanced Google Maps URL with Place ID support
     */
    private function generate_enhanced_maps_url(string $location_type, string $location_name, array $custom_address): string {
        if ($location_type === 'none') {
            return '';
        }
        
        if ($location_type === 'custom' && !empty($custom_address)) {
            // Use AddressFieldManager for enhanced mapping
            return $this->address_manager->generate_maps_url($custom_address);
        } elseif ($location_type === 'existing' && !empty($location_name)) {
            // Use taxonomy location name for search
            return 'https://maps.google.com/maps?q=' . urlencode($location_name);
        }
        
        return '';
    }
    
    /**
     * Generate Google Maps URL from location data (legacy fallback)
     */
    private function generate_maps_url(string $location_type, string $location_name, array $custom_address_clean): string {
        if ($location_type === 'none') {
            return '';
        }
        
        $query = '';
        
        if ($location_type === 'existing' && !empty($location_name)) {
            // Use taxonomy location name for search
            $query = $location_name;
        } elseif ($location_type === 'custom' && !empty($custom_address_clean)) {
            // Use custom address for search
            $query = implode(', ', $custom_address_clean);
        }
        
        if (empty($query)) {
            return '';
        }
        
        // URL encode the query and generate Google Maps link
        return 'https://maps.google.com/maps?q=' . urlencode($query);
    }

    /**
     * Get tribute URL from settings
     */
    private function get_tribute_url(): string {
        // Try new settings module first
        $settings = get_option('wfn_module_settings', []);
        $tribute_url = $settings['tribute_form_url'] ?? '';

        // Fallback to ACF option for backwards compatibility
        if (empty($tribute_url)) {
            $tribute_url = get_field('wfn_tribute_url', 'option');
        }

        return $tribute_url ? trim($tribute_url) : '';
    }

    /**
     * Generate full tribute URL with person's name using flexible placeholders
     */
    private function generate_tribute_url(string $first_name, string $last_name): string {
        $base_url = $this->get_tribute_url();
        
        if (empty($base_url)) {
            return '';
        }
        
        // Check if URL contains placeholders
        if (preg_match('/\{[a-zA-Z_]+\}/', $base_url)) {
            // New placeholder system - replace placeholders in the URL
            $placeholders = [
                '{firstname}' => urlencode(trim($first_name)),
                '{lastname}' => urlencode(trim($last_name)),
                '{fullname}' => urlencode(trim($first_name . ' ' . $last_name)),
                '{first_name}' => urlencode(trim($first_name)), // Alternative format
                '{last_name}' => urlencode(trim($last_name)),   // Alternative format
                '{full_name}' => urlencode(trim($first_name . ' ' . $last_name)) // Alternative format
            ];
            
            return str_replace(array_keys($placeholders), array_values($placeholders), $base_url);
        } else {
            // Legacy system - append as tribute parameter (backwards compatibility)
            $tribute_param = urlencode(trim($first_name . ' ' . $last_name));
            $separator = strpos($base_url, '?') !== false ? '&' : '?';
            return $base_url . $separator . 'tribute=' . $tribute_param;
        }
    }

    /**
     * Get service sheet document data
     */
    private function get_service_sheet_data($post_id): ?array {
        // Try media group first (new structure)
        $media_group = get_field('wfn_media_group', $post_id);
        if (!empty($media_group['service_sheet']) && is_array($media_group['service_sheet'])) {
            return [
                'url' => $media_group['service_sheet']['url'],
                'title' => $media_group['service_sheet']['title'] ?: 'Service Sheet',
                'type' => 'service_sheet'
            ];
        }

        // Try legacy field
        $service_sheet = get_field('service_sheet', $post_id);
        if (!empty($service_sheet) && is_array($service_sheet)) {
            return [
                'url' => $service_sheet['url'],
                'title' => $service_sheet['title'] ?: 'Service Sheet',
                'type' => 'service_sheet'
            ];
        }

        return null;
    }

    /**
     * Get video slideshow data
     */
    private function get_video_slideshow_data($post_id): ?array {
        // Check if site has valid video license (license check is the real gatekeeper)
        if (!LicenseService::hasValidVideoLicense()) {
            return null;
        }

        // Get video data from post meta (set by VideoModule when upload completes)
        $video_status = get_post_meta($post_id, '_wfn_video_status', true);
        $video_data = get_post_meta($post_id, '_wfn_video_data', true);

        // Decode JSON if needed
        if (is_string($video_data)) {
            $video_data = json_decode($video_data, true);
        }

        // Only show if video is ready
        if ($video_status !== 'ready') {
            return null;
        }

        // Handle corrupted video data by reconstructing from video ID
        $video_id_meta = get_post_meta($post_id, '_wfn_video_id', true);

        // If video data is corrupted but we have a video ID, reconstruct the URLs
        if ((empty($video_data) || $video_data === 'null' || $video_data === null) && $video_id_meta) {
            // Get library ID from settings for URL reconstruction
            $library_id = defined('WFN_VIDEO_LIBRARY_ID') ? WFN_VIDEO_LIBRARY_ID : get_option('wfn_bunny_library_id', '');

            return [
                'video_id' => $video_id_meta,
                'stream_url' => $library_id ? "https://iframe.mediadelivery.net/embed/{$library_id}/{$video_id_meta}" : '',
                'thumbnail_url' => $library_id ? "https://vz-{$library_id}.b-cdn.net/{$video_id_meta}/thumbnail.jpg" : '',
                'duration' => 0, // Unknown duration for reconstructed data
                'title' => 'Memorial Video Slideshow',
                'modal_id' => 'wfn-video-modal-' . $post_id,
                'type' => 'video_slideshow'
            ];
        }

        // Normal case: use existing video data
        if (empty($video_data)) {
            return null;
        }

        // Return video data with modal information
        return [
            'video_id' => $video_data['video_id'] ?? $video_id_meta ?? '',
            'stream_url' => $video_data['stream_url'] ?? '',
            'thumbnail_url' => $video_data['thumbnail_url'] ?? '',
            'duration' => $video_data['duration'] ?? 0,
            'title' => 'Memorial Video Slideshow',
            'modal_id' => 'wfn-video-modal-' . $post_id,
            'type' => 'video_slideshow'
        ];
    }

    /**
     * Get additional documents
     */
    private function get_additional_documents($post_id): array {
        $documents = [];

        // Debug: Log what we're getting
        // error_log("WFN Debug: Getting additional documents for post {$post_id}");

        // Try media group first (new structure)
        $media_group = get_field('wfn_media_group', $post_id);
        // error_log("WFN Debug: Media group data: " . print_r($media_group, true));
        if (!empty($media_group['additional_documents']) && is_array($media_group['additional_documents'])) {
            foreach ($media_group['additional_documents'] as $doc) {
                $final_url = '';
                $document_type = $doc['document_type'] ?? 'file';

                // Handle new structure with document_type choice
                if ($document_type === 'url' && !empty($doc['url'])) {
                    // External URL
                    $final_url = $doc['url'];
                } elseif ($document_type === 'file' && !empty($doc['file'])) {
                    // File upload
                    $final_url = is_array($doc['file']) ? $doc['file']['url'] : $doc['file'];
                } elseif (!empty($doc['url'])) {
                    // Legacy: direct URL field
                    $final_url = $doc['url'];
                } elseif (!empty($doc['file'])) {
                    // Legacy: direct file field
                    $final_url = is_array($doc['file']) ? $doc['file']['url'] : $doc['file'];
                }

                if (!empty($final_url)) {
                    $documents[] = [
                        'url' => $final_url,
                        'title' => $doc['title'] ?: 'Document',
                        'type' => 'additional',
                        'document_type' => $document_type
                    ];
                }
            }
        }

        // Try legacy structure if no documents found
        if (empty($documents)) {
            $legacy_docs = get_field('additional_documents', $post_id);
            if (is_array($legacy_docs)) {
                foreach ($legacy_docs as $doc) {
                    // Handle both 'url' and 'file' field structures for legacy too
                    $file_url = '';
                    if (!empty($doc['url'])) {
                        $file_url = $doc['url'];
                    } elseif (!empty($doc['file'])) {
                        $file_url = is_array($doc['file']) ? $doc['file']['url'] : $doc['file'];
                    }

                    if (!empty($file_url)) {
                        $documents[] = [
                            'url' => $file_url,
                            'title' => $doc['title'] ?: 'Document',
                            'type' => 'additional'
                        ];
                    }
                }
            }
        }

        return $documents;
    }
} 