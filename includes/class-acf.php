<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Register Funeral Notice Custom Post Type
 */
function register_funeral_notice_post_type() {
    // Get custom slug from settings module
    $settings = hkfn_get_option('module_settings', []);
    $single_slug = $settings['single_slug'] ?? 'funeral-notice';
    $labels = array(
        'name'                  => _x('Funerals', 'Post Type General Name', 'weave-funeral-notices'),
        'singular_name'         => _x('Funeral', 'Post Type Singular Name', 'weave-funeral-notices'),
        'menu_name'             => __('Funeral Notices', 'weave-funeral-notices'),
        'name_admin_bar'        => __('Funeral Notices', 'weave-funeral-notices'),
        'archives'              => __('Funeral Notice Archives', 'weave-funeral-notices'),
        'attributes'            => __('Funeral Notice Attributes', 'weave-funeral-notices'),
        'parent_item_colon'     => __('Parent Funeral Notice:', 'weave-funeral-notices'),
        'all_items'             => __('All Funeral Notices', 'weave-funeral-notices'),
        'add_new_item'          => __('+ New Funeral Notice', 'weave-funeral-notices'),
        'add_new'               => __('+ Funeral Notice', 'weave-funeral-notices'),
        'new_item'              => __('New Funeral Notice', 'weave-funeral-notices'),
        'edit_item'             => __('Edit Funeral Notice', 'weave-funeral-notices'),
        'update_item'           => __('Update Funeral Notice', 'weave-funeral-notices'),
        'view_item'             => __('View Funeral Notice', 'weave-funeral-notices'),
        'view_items'            => __('View Funeral Notices', 'weave-funeral-notices'),
        'search_items'          => __('Search Funeral Notices', 'weave-funeral-notices'),
        'not_found'             => __('No funeral notices found.', 'weave-funeral-notices'),
        'not_found_in_trash'    => __('No funeral notices found in Trash.', 'weave-funeral-notices'),
        'featured_image'        => __('Person Photo', 'weave-funeral-notices'),
        'set_featured_image'    => __('Set person photo', 'weave-funeral-notices'),
        'remove_featured_image' => __('Remove person photo', 'weave-funeral-notices'),
        'use_featured_image'    => __('Use as person photo', 'weave-funeral-notices'),
        'insert_into_item'      => __('Insert into funeral notice', 'weave-funeral-notices'),
        'uploaded_to_this_item' => __('Uploaded to this funeral notice', 'weave-funeral-notices'),
        'items_list'            => __('Funeral notices list', 'weave-funeral-notices'),
        'items_list_navigation' => __('Funeral notices list navigation', 'weave-funeral-notices'),
        'filter_items_list'     => __('Filter funeral notices list', 'weave-funeral-notices'),
    );
    
    $args = array(
        'label'                 => __('Funeral Notice', 'weave-funeral-notices'),
        'description'           => __('Funeral and memorial notices', 'weave-funeral-notices'),
        'labels'                => $labels,
        'supports'              => array('title', 'editor', 'thumbnail', 'custom-fields'),
        'hierarchical'          => false,
        'public'                => true,
        'show_ui'               => true,
        'show_in_menu'          => 'hk-funeral-notices',
        'menu_position'         => 5,
        'show_in_admin_bar'     => false, // We'll add custom admin bar items instead
        'show_in_nav_menus'     => true,
        'can_export'            => true,
        'has_archive'           => true,
        'exclude_from_search'   => false,
        'publicly_queryable'    => true,
        'capability_type'       => 'post', // Simplified to standard WordPress capabilities
        'map_meta_cap'          => true,
        'show_in_rest'          => true,
        'rewrite'               => array('slug' => $single_slug),
    );
    
    register_post_type('funeral-notice', $args);
}
add_action('init', 'register_funeral_notice_post_type');

/**
 * Register Funeral Location Taxonomy
 */
function register_funeral_location_taxonomy() {
    $labels = array(
        'name'                       => _x('Funeral Locations', 'Taxonomy General Name', 'weave-funeral-notices'),
        'singular_name'              => _x('Funeral Location', 'Taxonomy Singular Name', 'weave-funeral-notices'),
        'menu_name'                  => __('Locations', 'weave-funeral-notices'),
        'all_items'                  => __('All Locations', 'weave-funeral-notices'),
        'parent_item'                => __('Parent Location', 'weave-funeral-notices'),
        'parent_item_colon'          => __('Parent Location:', 'weave-funeral-notices'),
        'new_item_name'              => __('New Location Name', 'weave-funeral-notices'),
        'add_new_item'               => __('Add New Location', 'weave-funeral-notices'),
        'edit_item'                  => __('Edit Location', 'weave-funeral-notices'),
        'update_item'                => __('Update Location', 'weave-funeral-notices'),
        'view_item'                  => __('View Location', 'weave-funeral-notices'),
        'separate_items_with_commas' => __('Separate locations with commas', 'weave-funeral-notices'),
        'add_or_remove_items'        => __('Add or remove locations', 'weave-funeral-notices'),
        'choose_from_most_used'      => __('Choose from the most used', 'weave-funeral-notices'),
        'popular_items'              => __('Popular Locations', 'weave-funeral-notices'),
        'search_items'               => __('Search Locations', 'weave-funeral-notices'),
        'not_found'                  => __('Not Found', 'weave-funeral-notices'),
        'no_terms'                   => __('No locations', 'weave-funeral-notices'),
        'items_list'                 => __('Locations list', 'weave-funeral-notices'),
        'items_list_navigation'      => __('Locations list navigation', 'weave-funeral-notices'),
    );
    
    $args = array(
        'labels'                     => $labels,
        'hierarchical'               => false,
        'public'                     => true,
        'show_ui'                    => true,
        'show_admin_column'          => true,
        'show_in_nav_menus'          => true,
        'show_tagcloud'              => false,
        'show_in_rest'               => true,
        'show_in_menu'               => false,
        'capabilities'               => array(
            'manage_terms' => 'edit_posts',
            'edit_terms'   => 'edit_posts',
            'delete_terms' => 'edit_posts',
            'assign_terms' => 'edit_posts',
        ),
    );

    register_taxonomy('funeral-location', array('funeral-notice'), $args);
}
add_action('init', 'register_funeral_location_taxonomy');

/**
 * Disable block editor (Gutenberg) for funeral-notice post type
 * Keep classic editor for better user experience with ACF fields
 * REST API remains enabled for future API integrations
 */
function disable_gutenberg_for_funeral_notices($use_block_editor, $post) {
    if ($post->post_type === 'funeral-notice') {
        return false; // Use classic editor
    }
    return $use_block_editor;
}
add_filter('use_block_editor_for_post', 'disable_gutenberg_for_funeral_notices', 10, 2);

/**
 * Also disable for the post type generally
 */
function disable_gutenberg_for_funeral_notice_post_type($use_block_editor, $post_type) {
    if ($post_type === 'funeral-notice') {
        return false; // Use classic editor
    }
    return $use_block_editor;
}
add_filter('use_block_editor_for_post_type', 'disable_gutenberg_for_funeral_notice_post_type', 10, 2);

/**
 * Remove SEOPress meta boxes from funeral notices
 * 
 * SEOPress Meta and Content Analysis boxes can clutter the admin interface
 * for funeral notices. This removes them to provide a cleaner editing experience.
 * 
 * @since 2.0.0
 */
function hkfn_remove_seopress_metaboxes() {
    // Remove SEOPress meta boxes from funeral-notice post type
    remove_meta_box('seopress_cpt', 'funeral-notice', 'normal');
    remove_meta_box('seopress_content_analysis', 'funeral-notice', 'normal');
    remove_meta_box('seopress_ca', 'funeral-notice', 'normal');
    
    // Also remove any other SEOPress metaboxes that might appear
    remove_meta_box('seopress_social', 'funeral-notice', 'normal');
    remove_meta_box('seopress_advanced', 'funeral-notice', 'normal');
}
add_action('add_meta_boxes', 'hkfn_remove_seopress_metaboxes', 99);

/**
 * ========================================================================
 * FUNERAL NOTICE ADMIN INTERFACE CUSTOMIZATION
 * ========================================================================
 * 
 * Customizes the WordPress admin interface for funeral notices to provide
 * a cleaner, more focused user experience by hiding unnecessary fields and
 * modifying interface elements.
 */

/**
 * Hide title field for funeral notices admin interface
 * 
 * Since funeral notice titles are automatically generated from the person's
 * name fields, the title input field is hidden to prevent user confusion
 * and ensure consistency across all funeral notices.
 * 
 * HIDDEN ELEMENTS:
 * - Main title input field (#titlediv)
 * - Quick edit title field
 * - Bulk edit title field
 * 
 * @return void
 */
function hkfn_hide_title_field_for_funeral_notices() {
    global $post_type;
    
    // Only apply to funeral notice post type
    if ($post_type !== 'funeral-notice') return;
    
    ?>
    <style type="text/css">
        /* Aggressively collapse the entire title div and all its contents */
        body.post-type-funeral-notice #titlediv {
            display: none !important;
            visibility: hidden !important;
            height: 0 !important;
            min-height: 0 !important;
            max-height: 0 !important;
            margin: 0 !important;
            padding: 0 !important;
            border: none !important;
            overflow: hidden !important;
            line-height: 0 !important;
            font-size: 0 !important;
            position: absolute !important;
            left: -9999px !important;
        }
        
        /* Target all children of title div */
        body.post-type-funeral-notice #titlediv * {
            display: none !important;
            height: 0 !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        
        /* For funeral notices, remove the gap in post-body-content that's causing misalignment */
        /* Override WordPress core CSS: #post-body-content, .edit-form-section { margin-bottom: 20px; } */
        body.post-type-funeral-notice #post-body-content {
            padding-top: 0 !important;
            margin-top: 0 !important;
            margin-bottom: 0 !important;
        }
        
        /* Remove any top margin from the first ACF postbox to align with publish box */
        body.post-type-funeral-notice .acf-postbox:first-child,
        body.post-type-funeral-notice #acf_after_title-sortables .acf-postbox:first-child {
            margin-top: 0 !important;
        }
        
        /* Hide title field in quick edit */
        .quick-edit-row .inline-edit-col .title {
            display: none !important;
        }
        
        /* Hide title field in bulk edit */
        .bulk-edit-row .inline-edit-col .title {
            display: none !important;
        }
        
        /* Add visual indicator that title is auto-generated */
        .acf-field[data-name="hkfn_person_group"] .acf-label:after {
            content: " (Title auto-generated from names)";
            font-size: 11px;
            color: #666;
            font-weight: normal;
            font-style: italic;
        }
    </style>
    <?php
}
add_action('admin_head-post.php', 'hkfn_hide_title_field_for_funeral_notices');
add_action('admin_head-post-new.php', 'hkfn_hide_title_field_for_funeral_notices');

/**
 * Force media uploader to default to Upload tab
 *
 * When clicking "Set Person's Photo", the WordPress media library
 * defaults to the "Media Library" tab. This forces it to open on the
 * "Upload Files" tab for better UX.
 *
 * Note: This respects user preference - once a user switches to Upload tab,
 * WordPress remembers that choice for subsequent opens.
 */
function hkfn_force_media_upload_tab() {
    // Get current screen to check post type
    $screen = get_current_screen();

    // Only apply to funeral notice post type edit screens
    if (!$screen || $screen->post_type !== 'funeral-notice') {
        return;
    }

    ?>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Force media library to default to upload tab
            if (typeof wp !== 'undefined' && wp.media && wp.media.controller && wp.media.controller.Library) {
                wp.media.controller.Library.prototype.defaults.contentUserSetting = false;
            }
        });
    </script>
    <?php
}

/**
 * Register the media upload tab hooks after WordPress is fully loaded
 */
function hkfn_register_media_upload_hooks() {
    add_action('admin_footer-post.php', 'hkfn_force_media_upload_tab');
    add_action('admin_footer-post-new.php', 'hkfn_force_media_upload_tab');
}
add_action('admin_init', 'hkfn_register_media_upload_hooks');

/*
 * Crop-Thumbnails plugin integration removed in v3.0.0 — image cropping is
 * now built in (see src/Admin/ImageCropHandler.php and
 * assets/js/admin/image-crop-cropperjs.js). The crop-thumbnails plugin can
 * be uninstalled from sites once they are on v3.
 */

/**
 * Enqueue post editor JavaScript for funeral notices
 */
function hkfn_enqueue_post_editor_scripts($hook) {
    global $post_type;

    // Only load on funeral notice edit screens
    if (($hook === 'post.php' || $hook === 'post-new.php') && $post_type === 'funeral-notice') {
        // Get correct plugin URL
        $plugin_url = plugin_dir_url(dirname(__FILE__));

        wp_enqueue_script(
            'hkfn-post-editor',
            $plugin_url . 'assets/js/admin/post-editor.js',
            ['jquery'],
            HKFN_VERSION,
            true
        );

        // Add CSS for toggle switches if not already loaded
        wp_enqueue_style(
            'hkfn-post-editor',
            $plugin_url . 'assets/css/admin/dashboard.css',
            [],
            HKFN_VERSION
        );

        // Debug logging removed for production
    }
}
add_action('admin_enqueue_scripts', 'hkfn_enqueue_post_editor_scripts');

/**
 * Add helpful notice about auto-generated titles to funeral notice admin
 * 
 * REMOVED: This notice was confusing for users who don't need to understand
 * WordPress technical details. The auto-title system works seamlessly in
 * the background without needing user awareness.
 * 
 * @return void
 */
// function hkfn_add_auto_title_admin_notice() - REMOVED FOR BETTER UX

/**
 * Check if ACF Pro is installed and active
 * Display admin notice if missing
 *
 * @since 2.0.0
 */
function hkfn_check_acf_pro_dependency() {
    // Check if ACF Pro is installed and active
    if (!function_exists('acf_add_local_field_group') || !function_exists('acf_add_options_sub_page') || !class_exists('ACF_PRO')) {
        add_action('admin_notices', 'hkfn_acf_pro_missing_notice');
        return false;
    }
    return true;
}
add_action('admin_init', 'hkfn_check_acf_pro_dependency');

/**
 * Display admin notice when ACF Pro is missing
 *
 * @since 2.0.0
 */
function hkfn_acf_pro_missing_notice() {
    ?>
    <div class="notice notice-error">
        <p>
            <strong>HumanKind Funeral Notices:</strong> 
            This plugin requires <strong>Advanced Custom Fields PRO</strong> to function properly. 
            <a href="https://www.advancedcustomfields.com/pro/" target="_blank">Purchase ACF Pro</a> 
            or <a href="<?php echo admin_url('plugins.php'); ?>">activate it</a> if already installed.
        </p>
        <p><em>Required Pro features: Options pages, Group fields, Google Maps, and ACF Extended integration.</em></p>
    </div>
    <?php
}

/**
 * Create ACF options page for legacy field groups (backward compatibility)
 *
 * @since 0.9.2
 */
if ( function_exists( 'acf_add_options_sub_page' ) ){
	acf_add_options_sub_page(array(
		'title'      => 'Funeral Settings',
		'parent'     => 'edit.php?post_type=funeral-notice',
		'update_button' => __('Update', 'acf'),
		'updated_message' => __("Settings Updated", 'acf'),
		'menu_slug' => 'funeral-notice-settings',
		'capability' => 'edit_posts'
	));
}

// Google Maps API key for the ACF/ACFE location map field (admin address picker).
// Reads the site's configured key from the plugin setting, falling back to an
// HKFN_GOOGLE_MAPS_KEY wp-config constant. No key is hardcoded — each site
// supplies its own (Settings > Google Maps / Places API Key).
function hkfn_acf_google_map_key() {
	$settings = hkfn_get_option( 'module_settings', array() );
	$key      = is_array( $settings ) ? ( $settings['google_places_api_key'] ?? '' ) : '';

	if ( '' === $key ) {
		$key = (string) hkfn_get_constant( 'GOOGLE_MAPS_KEY', '' );
	}

	if ( '' !== $key ) {
		acf_update_setting( 'google_api_key', $key );
	}
}
add_action( 'acf/init', 'hkfn_acf_google_map_key' );


//  Load Funeral ACF fields from JSON (DISABLED - using modern FieldGroupManager)
/*
add_filter('acf/settings/load_json', 'my_acf_json_load_point');
function my_acf_json_load_point( $paths ) {	
	// Append path to load legacy JSON field groups
	$paths[] = plugin_dir_path(__FILE__) . 'acf-json';
	
	return $paths;
}
*/


/**
 * ========================================================================
 * FUNERAL NOTICE AUTO-TITLE GENERATION SYSTEM
 * ========================================================================
 * 
 * This system automatically generates post titles and slugs for funeral notices
 * based on the person's name fields. The title field is hidden from users and
 * all title/slug generation is handled automatically.
 * 
 * TITLE FORMAT: "LastName PostID" (e.g., "Smith 123")
 * SLUG FORMAT:  "lastname-postid" (e.g., "smith-123")
 * 
 * FEATURES:
 * - Automatic title generation from ACF name fields
 * - Unique slug generation with conflict resolution
 * - Backwards compatibility with legacy field structures
 * - Robust error handling and validation
 * - Prevents infinite loops during post updates
 * - Supports both new FieldGroupManager and legacy field formats
 * 
 * FIELD COMPATIBILITY:
 * - New format: hkfn_person_group['firstname'] / hkfn_person_group['lastname']
 * - Legacy format: hkfn_person_group_firstname / hkfn_person_group_lastname
 * 
 * @since 1.0.0
 * @updated 2.0.0 Enhanced for FieldGroupManager compatibility
 */

/**
 * Generate automatic title and slug for funeral notices from person name fields
 * 
 * This function is triggered on every post save and automatically generates
 * a standardized title format using the deceased person's last name and post ID.
 * 
 * @param int $post_id The post ID being saved
 * @return void
 */
function hkfn_generate_funeral_title_from_names($post_id) {
    // Prevent processing during autosave, revisions, or bulk operations
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (wp_is_post_revision($post_id)) return;
    if (defined('DOING_AJAX') && DOING_AJAX) return;
    
    // Only process funeral notice post types
    if (get_post_type($post_id) !== 'funeral-notice') return;
    
    // Get current post to check status
    $post = get_post($post_id);
    if (!$post || $post->post_status === 'trash') return;
    
    // Extract names using robust field detection
    $names = hkfn_extract_person_names($post_id);
    
    // Generate title only if we have at least a last name
    if (empty($names['last_name'])) {
        \HumanKind\FuneralNotices\Hooks\debug_log("WFN Auto-Title: No last name found for post {$post_id}, skipping title generation");
        return;
    }
    
    // Generate the new title and slug
    $new_title = hkfn_generate_title_format($names['last_name'], $post_id);
    $new_slug = hkfn_generate_unique_slug($new_title, $post_id);
    
    // Only update if title has actually changed (prevents unnecessary updates)
    $current_title = get_the_title($post_id);
    if ($current_title === $new_title) return;
    
    // Prevent infinite loops by temporarily removing this hook
    remove_action('save_post', 'hkfn_generate_funeral_title_from_names');
    
    // Update the post with new title and slug
    $result = wp_update_post([
        'ID' => $post_id,
        'post_title' => $new_title,
        'post_name' => $new_slug
    ], true); // Return WP_Error on failure
    
    // Log the update for debugging
    if (is_wp_error($result)) {
        error_log("WFN Auto-Title Error: Failed to update post {$post_id} - " . $result->get_error_message());
    } else {
        \HumanKind\FuneralNotices\Hooks\debug_log("WFN Auto-Title: Updated post {$post_id} title to '{$new_title}' with slug '{$new_slug}'");
    }
    
    // Re-hook the function
    add_action('save_post', 'hkfn_generate_funeral_title_from_names');
}
add_action('save_post', 'hkfn_generate_funeral_title_from_names');

/**
 * Extract person names from ACF fields with fallback support
 * 
 * Supports both new FieldGroupManager format and legacy field formats
 * for maximum compatibility during transitions.
 * 
 * @param int $post_id The post ID to extract names from
 * @return array Associative array with 'first_name' and 'last_name' keys
 */
function hkfn_extract_person_names($post_id) {
    $first_name = '';
    $last_name = '';
    
    // Method 1: Try new FieldGroupManager group structure
    $person_group = get_field('hkfn_person_group', $post_id);
    if (is_array($person_group) && !empty($person_group)) {
        $first_name = trim($person_group['firstname'] ?? '');
        $last_name = trim($person_group['lastname'] ?? '');
    }
    
    // Method 2: Fallback to legacy individual field format
    if (empty($first_name) && empty($last_name)) {
        $first_name = trim(get_field('hkfn_person_group_firstname', $post_id) ?: '');
        $last_name = trim(get_field('hkfn_person_group_lastname', $post_id) ?: '');
    }
    
    // Method 3: Final fallback to direct field names (if they exist)
    if (empty($first_name) && empty($last_name)) {
        $first_name = trim(get_field('firstname', $post_id) ?: '');
        $last_name = trim(get_field('lastname', $post_id) ?: '');
    }
    
    return [
        'first_name' => $first_name,
        'last_name' => $last_name
    ];
}

/**
 * Generate standardized title format for funeral notices
 * 
 * Creates a consistent title format across all funeral notices using
 * the person's last name and the post ID for uniqueness. Handles nicknames
 * and special formatting intelligently.
 * 
 * @param string $last_name The person's last name (may contain nicknames)
 * @param int $post_id The post ID for uniqueness
 * @return string The formatted title
 */
function hkfn_generate_title_format($last_name, $post_id) {
    // Clean and validate the last name with intelligent nickname handling
    $clean_last_name = hkfn_clean_name_for_title($last_name);
    
    // Ensure we have a valid last name
    if (empty($clean_last_name)) {
        $clean_last_name = 'Unknown';
    }
    
    // Format: "LastName PostID"
    return $clean_last_name . ' ' . $post_id;
}

/**
 * Clean and format names for title generation with intelligent nickname handling
 * 
 * Handles common name formats including nicknames, titles, and special characters
 * while preserving readability and ensuring URL safety.
 * 
 * EXAMPLES:
 * - "Billy (Bill)" → "Billy Bill"
 * - "John \"Johnny\" Smith" → "John Johnny Smith"
 * - "Mary-Jane O'Connor" → "Mary-Jane O'Connor"
 * - "Dr. Robert Smith Jr." → "Dr Robert Smith Jr"
 * 
 * @param string $name The raw name string to clean
 * @return string The cleaned name suitable for titles
 */
function hkfn_clean_name_for_title($name) {
    // Start with basic cleanup
    $clean_name = trim(strip_tags($name));
    
    // Handle nicknames in parentheses: "Billy (Bill)" → "Billy Bill"
    $clean_name = preg_replace('/\(([^)]+)\)/', '$1', $clean_name);
    
    // Handle nicknames in quotes: 'John "Johnny"' → "John Johnny"
    $clean_name = preg_replace('/["""]([^"""]+)["""]/', '$1', $clean_name);
    
    // Remove other problematic punctuation but keep essential ones
    // Keep: letters, numbers, spaces, hyphens, apostrophes, periods
    // Remove: special chars, brackets, excessive punctuation
    $clean_name = preg_replace('/[^\p{L}\p{N}\s\-\'\.]/u', ' ', $clean_name);
    
    // Clean up multiple spaces and trim
    $clean_name = preg_replace('/\s+/', ' ', $clean_name);
    $clean_name = trim($clean_name);
    
    // Log the transformation for debugging
    if ($name !== $clean_name) {
        error_log("WFN Name Cleaning: '{$name}' → '{$clean_name}'");
    }
    
    return $clean_name;
}

/**
 * Generate unique slug with conflict resolution
 * 
 * Creates a URL-friendly slug and ensures uniqueness by checking for
 * conflicts with existing posts and appending numbers if necessary.
 * 
 * @param string $title The title to create a slug from
 * @param int $post_id The post ID (to exclude from conflict checking)
 * @return string The unique slug
 */
function hkfn_generate_unique_slug($title, $post_id) {
    $base_slug = sanitize_title($title);
    $slug = $base_slug;
    $counter = 1;
    
    // Check for slug conflicts and resolve them
    while (hkfn_slug_exists($slug, $post_id)) {
        $slug = $base_slug . '-' . $counter;
        $counter++;
        
        // Prevent infinite loops (though this should never happen)
        if ($counter > 1000) {
            $slug = $base_slug . '-' . time();
            break;
        }
    }
    
    return $slug;
}

/**
 * Check if a slug already exists for another post
 * 
 * @param string $slug The slug to check
 * @param int $exclude_post_id Post ID to exclude from the check
 * @return bool True if slug exists, false otherwise
 */
function hkfn_slug_exists($slug, $exclude_post_id) {
    global $wpdb;
    
    $query = $wpdb->prepare(
        "SELECT ID FROM {$wpdb->posts} WHERE post_name = %s AND ID != %d AND post_status != 'trash'",
        $slug,
        $exclude_post_id
    );
    
    return (bool) $wpdb->get_var($query);
}

/**
 * Set temporary title for new funeral notices
 * 
 * Provides a placeholder title for new posts before ACF fields are saved.
 * This prevents issues with empty titles during the post creation process.
 * 
 * @param int $post_id The newly created post ID
 * @return void
 */
function hkfn_set_temporary_funeral_title($post_id) {
    // Only process funeral notices
    if (get_post_type($post_id) !== 'funeral-notice') return;
    
    $post = get_post($post_id);
    if (!$post) return;
    
    // Only set temporary title for new auto-draft posts without titles
    if ($post->post_status === 'auto-draft' && empty(trim($post->post_title))) {
        $temp_title = 'Funeral Notice ' . $post_id;
        
        wp_update_post([
            'ID' => $post_id,
            'post_title' => $temp_title,
            'post_name' => sanitize_title($temp_title)
        ]);
        
        \HumanKind\FuneralNotices\Hooks\debug_log("WFN Auto-Title: Set temporary title '{$temp_title}' for new post {$post_id}");
    }
}
add_action('wp_insert_post', 'hkfn_set_temporary_funeral_title');




// Change ACF time-picker for funeral inputs
function my_acf_input_admin_footer() {
?>
<script type="text/javascript">
(function($) {
	
   acf.add_filter('time_picker_args', function( args, field ){
	 args.showSecond = false;
	 args.stepMinute = 15;
	 return args;
   });
	
})(jQuery); 
</script>
<?php
		
}
add_action('acf/input/admin_footer', 'my_acf_input_admin_footer');

// button
function filter_submit_button_attributes( $attributes, $form, $args ) {
  $attributes['class'] .= ' wpbf-button';
  
  return $attributes;
}
add_filter( 'af/form/button_attributes', 'filter_submit_button_attributes', 10, 3 );




// Add thickbox library for video instructions
function add_thickbox_to_add_post()
{
  // $pagenow, is a global variable referring to the filename of the current page, 
  // such as ‘admin.php’, ‘post-new.php’
  global $pagenow;

  if ($pagenow != 'post-new.php') {
	return;
  }
  
  add_thickbox();
}

add_action( 'admin_enqueue_scripts', 'add_thickbox_to_add_post' );



function makePrintContentsSaySaved()
  {
  global $pagenow;
  if ( isset($pagenow) && $pagenow == 'post-new.php'
	&& isset($_GET['post_type']) && $_GET['post_type'] === 'funeral-notice'){
	add_action('admin_print_footer_scripts','makePrintContentsSaySavedGutenberg');
	add_filter(
	  'gettext',
	  function($translated,$text_domain,$original){
		if($translated === 'Publish'){
		  return __('Save');
		}
		return $translated;
	  },
	  10,
	  3
	);
  }
  }

/**
   * Change Publish Button to Save Gutenberg
   */
  function makePrintContentsSaySavedGutenberg(){
	// we've already checked we're on the right page
	if ( wp_script_is( 'wp-i18n' ) ) {
	  ?>
	  <script>
		// Note: Make sure that `wp.i18n` has already been defined by the time you call `wp.i18n.setLocaleData()`.
		wp.i18n.setLocaleData({
		  'Publish': [
			'Save'
		  ]
		});
	  </script>
	  <?php
	}
  }
  add_action('init', 'makePrintContentsSaySavedGutenberg');

add_action('admin_head', 'oneroom_fields_show_hide');
  function oneroom_fields_show_hide() {
	  // Check if the get_field function exists (ACF is active)
	  if (function_exists('get_field')) {
		  // Get the value of the ACF true/false field on the options page
		  $show_field = get_field('hkfn_settings_use_oneroom_api', 'option');
  
		  // Show/hide the fields based on the true/false field value
		  ?>
		  <style>
			  <?php if ($show_field): ?>
				  .acf-field-64d8324340181 {
					  display: none !important;
				  }
				  .acf-field-63bf682ce1c21 {
					  display: block;
				  }
			  <?php else: ?>
				  .acf-field-64d8324340181 {
					  display: block;
				  }
				  .acf-field-63bf682ce1c21 {
					  display: none !important;
				  }
			  <?php endif; ?>
		  </style>
		  <?php
	  }
  }


/**
 * Register custom image sizes for funeral notices
 */
function hkfn_register_image_sizes() {
	// Legacy 1:1 square size (500x500)
	add_image_size( 'funeral-image', 500, 500, true );

	// hkfn-grid-crop is NOT registered here. It belongs to ImageCropHandler,
	// which registers it square on after_setup_theme. A second registration
	// on init silently overwrote that with the retired Crop-Thumbnails 4:3
	// size, so photos uploaded without a crop came out landscape.
}
add_action( 'init', 'hkfn_register_image_sizes' );

function remove_editor_buttons_from_funeral_notice_editor($settings, $editor_id) {
	if (function_exists('get_current_screen') && get_current_screen()->post_type === 'funeral-notice') {
		$settings['media_buttons'] = false;
		$settings['quicktags'] = false;
	}
	return $settings;
}
add_filter('wp_editor_settings', 'remove_editor_buttons_from_funeral_notice_editor', 10, 2);


// Remove tinymce buttons from first row
function remove_tinymce_buttons_first_row_from_funeral_notice( $buttons ) {
	if (function_exists('get_current_screen') && get_current_screen()->post_type === 'funeral-notice') {
		$remove = array( 'spellchecker', 'AtD', 'wp_more', 'blockquote', 'numlist', 'wpgb' );
		$buttons = array_diff( $buttons, $remove );
	}
	return $buttons;
}
add_filter( 'mce_buttons', 'remove_tinymce_buttons_first_row_from_funeral_notice' );


// Remove tinymce buttons from second row
function remove_tinymce_buttons_second_row_from_funeral_notice( $buttons ) {
	if (function_exists('get_current_screen') && get_current_screen()->post_type === 'funeral-notice') {
		$remove = array( 'underline', 'alignjustify', 'strikethrough', 'hr', 'forecolor', 'charmap', 'wp_help', 'outdent', 'indent' );
		$buttons = array_diff( $buttons, $remove );
	}
	return $buttons;
}
add_filter( 'mce_buttons_2', 'remove_tinymce_buttons_second_row_from_funeral_notice' );

// Remove wpgb button
add_filter('mce_buttons', function ($buttons) {
	// Check if the current post type is 'funeral-notice'
	if ('funeral-notice' === get_post_type()) {
		$key = array_search('wpgb', $buttons, true);
		unset($buttons[$key]);
	}

	return $buttons;
}, 99);

/**
 * Register funeral notices shortcode (Legacy - disabled in favor of modern system)
 */
/*
function register_funeral_notices_shortcode() {
	add_shortcode('funeral_notices', 'render_funeral_notices_shortcode');
}
add_action('init', 'register_funeral_notices_shortcode');
*/

/**
 * Render funeral notices shortcode
 */
function render_funeral_notices_shortcode($atts = []) {
	// Parse shortcode attributes
	$atts = shortcode_atts([
		'type' => 'all',           // all, future, archived, today, this_week, this_month
		'per_page' => 12,          // Number of items per page
		'style' => 'firehawk',     // firehawk, modern, elegant
		'columns' => 3,            // 1, 2, 3, 4
		'show_pagination' => 'yes', // yes, no
		'show_search' => 'yes',    // yes, no - show search form above grid
		'location' => '',          // Filter by specific location slug
		'date_from' => '',         // Filter from specific date (Y-m-d format)
		'date_to' => ''            // Filter to specific date (Y-m-d format)
	], $atts);

	// Sanitize inputs
	$type = sanitize_text_field($atts['type']);
	$per_page = (int) $atts['per_page'];
	$style = sanitize_text_field($atts['style']);
	$columns = max(1, min(4, (int) $atts['columns']));
	$show_pagination = $atts['show_pagination'] === 'yes';
	$show_search = $atts['show_search'] === 'yes';
	
	// Override with GET parameters if search form was submitted
	$location_search = sanitize_text_field($_GET['hkfn_location_search'] ?? $atts['location'] ?? '');
	$date_from = sanitize_text_field($_GET['hkfn_date_from'] ?? $atts['date_from']);
	$date_to = sanitize_text_field($_GET['hkfn_date_to'] ?? $atts['date_to']);
	$search_term = sanitize_text_field($_GET['hkfn_search'] ?? '');

	// Get current page for pagination
	$paged = max(1, (int) (get_query_var('paged') ?: 1));

	// Build query arguments
	$args = [
		'post_type' => 'funeral-notice',
		'posts_per_page' => $per_page,
		'paged' => $paged,
		'post_status' => 'publish'
	];
	
	// Always order by funeral date - date is more important than search relevance
	$args['meta_key'] = 'wfn_details_group_funeral_date';
	$args['orderby'] = 'meta_value';
	$args['order'] = 'DESC';

	// Add date filtering based on type
	$today = date('Y-m-d');
	$meta_query = [];
	
	if ($type === 'archived') {
		$meta_query[] = [
			'key' => 'wfn_details_group_funeral_date',
			'value' => $today,
			'type' => 'DATE',
			'compare' => '<'
		];
	} elseif ($type === 'future') {
		$meta_query[] = [
			'key' => 'wfn_details_group_funeral_date',
			'value' => $today,
			'type' => 'DATE',
			'compare' => '>='
		];
	}

	// Apply meta query if we have conditions
	if (!empty($meta_query)) {
		if (count($meta_query) > 1) {
			$meta_query['relation'] = 'AND';
		}
		$args['meta_query'] = $meta_query;
	}

	// Add search functionality for names
	if (!empty($search_term)) {
		$search_meta_query = [
			'relation' => 'OR',
			[
				'key' => 'hkfn_person_group_firstname',
				'value' => $search_term,
				'compare' => 'LIKE'
			],
			[
				'key' => 'hkfn_person_group_lastname', 
				'value' => $search_term,
				'compare' => 'LIKE'
			]
		];
		
		// Combine with existing meta query
		if (!empty($args['meta_query'])) {
			$args['meta_query'] = [
				'relation' => 'AND',
				$args['meta_query'],
				$search_meta_query
			];
		} else {
			$args['meta_query'] = $search_meta_query;
		}
	}

	// Execute query
	$query = new WP_Query($args);
	
	// Start output
	ob_start();
	
	// Render search form if requested
	if ($show_search) {
		render_funeral_notices_search_form($type, $location_search, $date_from, $date_to, $search_term);
	}
	
	if (!$query->have_posts()) {
		echo '<div class="hkfn-no-results">No funerals found.</div>';
		wp_reset_postdata();
		return ob_get_clean();
	}
	
	// Render based on style
	switch ($style) {
		case 'firehawk':
			render_firehawk_grid($query, $columns);
			break;
		case 'modern':
			render_modern_grid($query, $columns);
			break;
		case 'elegant':
			render_elegant_grid($query, $columns);
			break;
		default:
			render_firehawk_grid($query, $columns); // Fallback
	}

	// Add pagination if enabled
	if ($show_pagination && $query->max_num_pages > 1) {
		render_funeral_notices_pagination($query, $paged);
	}

	$output = ob_get_clean();
	wp_reset_postdata();

	return $output;
}

/**
 * Render Firehawk-compatible grid
 */
function render_firehawk_grid($query, $columns) {
	// Enqueue Firehawk CSS
	wp_enqueue_style('hkfn-firehawk', plugin_dir_url(__FILE__) . '../assets/css/firehawk-compat.css', [], HKFN_VERSION);
	
	echo '<div class="firehawk-crm firehawk-crm-large-grid" id="hkfn-tributes-list">';
	echo '<div class="firehawk-crm-large-grid-view">';

	while ($query->have_posts()) {
		$query->the_post();
		$post_id = get_the_ID();

		// Use direct ACF access
		$person_group = get_field('hkfn_person_group', $post_id) ?: [];
		$first_name = $person_group['firstname'] ?? '';
		$last_name = $person_group['lastname'] ?? '';
		$birth_year = $person_group['birth_year'] ?? '';
		$death_year = $person_group['death_year'] ?? '';
		
		$full_name = trim("{$first_name} {$last_name}");
		$years_display = ($birth_year && $death_year) ? "{$birth_year} - {$death_year}" : '';
		
		// Format name for Firehawk style (LASTNAME, First)
		$formatted_name = strtoupper($last_name) . ', ' . $first_name;
		
		// Get image
		$featured_image = get_the_post_thumbnail_url($post_id, 'medium');
		$fallback_image = get_field('hkfn_fallback_image', 'option');
		$fallback_url = '';
		if (is_array($fallback_image) && isset($fallback_image['url'])) {
			$fallback_url = $fallback_image['url'];
		}
		$image_url = $featured_image ?: $fallback_url;

		echo '<div class="grid-col">';
		echo '<a href="' . esc_url(get_permalink($post_id)) . '">';
		echo '<div class="grid-item compact">';
		echo '<div class="top-content">';
		echo '<div class="top-img" style="background-image: url(\'' . esc_url($image_url) . '\')"></div>';
		echo '<div class="title-container">';
		echo '<div class="title">' . esc_html($formatted_name) . '</div>';
		if ($years_display) {
			echo '<div class="dates">' . esc_html($years_display) . '</div>';
		}
		echo '</div></div></div></a></div>';
	}

	echo '</div></div>';
}

/**
 * Render modern grid layout
 */
function render_modern_grid($query, $columns) {
	// Enqueue modern CSS
	wp_enqueue_style('hkfn-modern', plugin_dir_url(__FILE__) . '../assets/css/modern.css', [], HKFN_VERSION);
	
	$grid_class = "hkfn-modern-grid hkfn-cols-{$columns}";
	echo "<div class=\"{$grid_class}\">";

	while ($query->have_posts()) {
		$query->the_post();
		$post_id = get_the_ID();

		// Use direct ACF access
		$person_group = get_field('hkfn_person_group', $post_id) ?: [];
		$first_name = $person_group['firstname'] ?? '';
		$last_name = $person_group['lastname'] ?? '';
		$birth_year = $person_group['birth_year'] ?? '';
		$death_year = $person_group['death_year'] ?? '';
		
		$full_name = trim("{$first_name} {$last_name}");
		$years_display = ($birth_year && $death_year) ? "{$birth_year} - {$death_year}" : '';
		
		// Get image
		$featured_image = get_the_post_thumbnail_url($post_id, 'medium');
		$fallback_image = get_field('hkfn_fallback_image', 'option');
		$fallback_url = '';
		if (is_array($fallback_image) && isset($fallback_image['url'])) {
			$fallback_url = $fallback_image['url'];
		}
		$image_url = $featured_image ?: $fallback_url;

		echo '<article class="hkfn-funeral-card">';
		echo '<a href="' . esc_url(get_permalink($post_id)) . '" class="hkfn-card-link">';
		
		if ($image_url) {
			echo '<div class="hkfn-card-image">';
			echo '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($full_name) . '" loading="lazy">';
			echo '</div>';
		}
		
		echo '<div class="hkfn-card-content">';
		echo '<h3 class="hkfn-card-title">' . esc_html($full_name) . '</h3>';
		
		if ($years_display) {
			echo '<p class="hkfn-card-dates">' . esc_html($years_display) . '</p>';
		}
		
		echo '<span class="hkfn-card-more">View details</span>';
		echo '</div></a></article>';
	}

	echo '</div>';
}

/**
 * Render elegant grid layout  
 */
function render_elegant_grid($query, $columns) {
	// Enqueue elegant CSS
	wp_enqueue_style('hkfn-elegant', plugin_dir_url(__FILE__) . '../assets/css/elegant.css', [], HKFN_VERSION);
	
	$grid_class = "hkfn-elegant-grid hkfn-cols-{$columns}";
	echo "<div class=\"{$grid_class}\">";

	while ($query->have_posts()) {
		$query->the_post();
		$post_id = get_the_ID();

		// Use direct ACF access
		$person_group = get_field('hkfn_person_group', $post_id) ?: [];
		$details_group = get_field('hkfn_details_group', $post_id) ?: [];
		
		$first_name = $person_group['firstname'] ?? '';
		$last_name = $person_group['lastname'] ?? '';
		$birth_year = $person_group['birth_year'] ?? '';
		$death_year = $person_group['death_year'] ?? '';
		
		$full_name = trim("{$first_name} {$last_name}");
		$years_display = ($birth_year && $death_year) ? "{$birth_year} - {$death_year}" : '';
		
		// Get funeral date and time
		$funeral_date = $details_group['funeral_date'] ?? '';
		$funeral_time = $details_group['funeral_time'] ?? '';
		
		// Get image
		$featured_image = get_the_post_thumbnail_url($post_id, 'medium');
		$fallback_image = get_field('hkfn_fallback_image', 'option');
		$fallback_url = '';
		if (is_array($fallback_image) && isset($fallback_image['url'])) {
			$fallback_url = $fallback_image['url'];
		}
		$image_url = $featured_image ?: $fallback_url;

		echo '<article class="hkfn-elegant-card">';
		echo '<a href="' . esc_url(get_permalink($post_id)) . '" class="hkfn-elegant-link">';
		
		echo '<div class="hkfn-elegant-header">';
		if ($image_url) {
			echo '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($full_name) . '" class="hkfn-elegant-portrait">';
		}
		echo '<div class="hkfn-elegant-details">';
		echo '<h3 class="hkfn-elegant-name">' . esc_html($full_name) . '</h3>';
		if ($years_display) {
			echo '<p class="hkfn-elegant-years">' . esc_html($years_display) . '</p>';
		}
		echo '</div></div>';
		
		if ($funeral_date) {
			echo '<div class="hkfn-elegant-date">';
			echo '<strong>Service:</strong> ' . esc_html(date('j F Y', strtotime($funeral_date)));
			if ($funeral_time) {
				echo ' at ' . esc_html($funeral_time);
			}
			echo '</div>';
		}
		
		echo '</a></article>';
	}

	echo '</div>';
}

/**
 * Render search form
 */
function render_funeral_notices_search_form($type, $location_search, $date_from, $date_to, $search_term = '') {
	// Get current page URL without query parameters
	$current_url = is_front_page() ? home_url('/') : get_permalink();
	
	// Enqueue search CSS
	wp_enqueue_style('hkfn-search', plugin_dir_url(__FILE__) . '../assets/css/search.css', [], HKFN_VERSION);
	?>
	<div class="hkfn-shortcode-search-form">
		<form method="get" action="<?php echo esc_url($current_url); ?>" class="hkfn-search-form hkfn-shortcode-form">
			<div class="hkfn-search-row">
				<div class="hkfn-search-field">
					<input type="text" 
						   name="hkfn_search" 
						   placeholder="Search by name" 
						   value="<?php echo esc_attr($search_term); ?>" />
				</div>
				
				<div class="hkfn-search-field">
					<input type="text" 
						   name="hkfn_location_search" 
						   placeholder="Location, venue, or address..." 
						   value="<?php echo esc_attr($_GET['hkfn_location_search'] ?? $location_search); ?>" />
				</div>
				
				<div class="hkfn-search-actions">
					<button type="submit" class="hkfn-btn hkfn-btn-primary">Search</button>
					<?php if ($search_term || $location_search || $date_from || $date_to): ?>
					<a href="<?php echo esc_url($current_url); ?>" 
					   class="hkfn-btn hkfn-btn-secondary">Clear</a>
					<?php endif; ?>
				</div>
			</div>
		</form>
	</div>
	<?php
}

/**
 * Render pagination
 */
function render_funeral_notices_pagination($query, $paged) {
	echo '<div class="hkfn-pagination">';
	echo paginate_links([
		'base' => str_replace('999999999', '%#%', esc_url(get_pagenum_link(999999999))),
		'format' => '?paged=%#%',
		'current' => $paged,
		'total' => $query->max_num_pages,
		'prev_text' => '&laquo; Previous',
		'next_text' => 'Next &raquo;',
		'type' => 'list',
		'end_size' => 3,
		'mid_size' => 3
	]);
	echo '</div>';
}

/**
 * Customize funeral notice post update messages
 */
function hkfn_custom_post_update_messages($messages) {
	global $post, $post_ID;

	$messages['funeral-notice'] = array(
		0  => '', // Unused. Messages start at index 1.
		1  => sprintf(__('Funeral Notice updated. <a href="%s">View Funeral Notice</a>', 'weave-funeral-notices'), esc_url(get_permalink($post_ID))),
		2  => __('Custom field updated.', 'weave-funeral-notices'),
		3  => __('Custom field deleted.', 'weave-funeral-notices'),
		4  => __('Funeral Notice updated.', 'weave-funeral-notices'),
		5  => isset($_GET['revision']) ? sprintf(__('Funeral Notice restored to revision from %s', 'weave-funeral-notices'), wp_post_revision_title((int) $_GET['revision'], false)) : false,
		6  => sprintf(__('Funeral Notice published. <a href="%s">View Funeral Notice</a>', 'weave-funeral-notices'), esc_url(get_permalink($post_ID))),
		7  => __('Funeral Notice saved.', 'weave-funeral-notices'),
		8  => sprintf(__('Funeral Notice submitted. <a target="_blank" href="%s">Preview Funeral Notice</a>', 'weave-funeral-notices'), esc_url(add_query_arg('preview', 'true', get_permalink($post_ID)))),
		9  => sprintf(__('Funeral Notice scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview Funeral Notice</a>', 'weave-funeral-notices'), date_i18n(__('M j, Y @ G:i'), strtotime($post->post_date)), esc_url(get_permalink($post_ID))),
		10 => sprintf(__('Funeral Notice draft updated. <a target="_blank" href="%s">Preview Funeral Notice</a>', 'weave-funeral-notices'), esc_url(add_query_arg('preview', 'true', get_permalink($post_ID)))),
	);

	return $messages;
}
add_filter('post_updated_messages', 'hkfn_custom_post_update_messages');

