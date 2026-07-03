<?php
/**
 * Settings page registration and REST API exposure.
 *
 * Renders a minimal wrapper div; the React app (built from src/js/settings/)
 * mounts into it. All UI uses @wordpress/components — no custom CSS needed.
 *
 * @package HumanKind\FuneralNotices
 */

declare( strict_types=1 );

namespace HumanKind\FuneralNotices\SettingsPage;

defined( 'ABSPATH' ) || exit;

const OPTION_NAME = 'hkfn_settings';

/**
 * Default settings values.
 *
 * Consolidated from all module settings into a single REST-exposed option.
 * Individual module settings (hkfn_module_{id}_settings) remain for backward
 * compatibility but the React UI reads/writes this consolidated option.
 *
 * @return array Settings with default values.
 */
function get_defaults(): array {
	return [
		// General.
		'default_layout'           => 'modern',
		'posts_per_page'           => 12,
		'load_more_posts'          => 9,
		'columns'                  => 3,
		'show_search'              => true,
		'show_pagination'          => true,
		'excerpt_length'           => 150,
		'date_format'              => 'F j, Y',
		'time_format'              => 'g:i a',
		'show_featured_image'      => true,
		'image_size'               => 'medium',
		'enable_streaming'         => true,
		'cache_duration'           => 3600,
		'single_slug'              => 'funeral-notice',
		'default_person_image'     => '',
		'location_name'            => '',
		'default_memorial_header'  => 'In loving memory of',
		'default_venue_location'   => '',
		'address_field_mode'       => 'auto',
		'google_places_api_key'    => '',
		'tribute_form_url'         => '',

		// SEO.
		'enable_seo'               => true,
		'noindex_funeral_notices'  => false,
		'meta_description_length'  => 160,
		'seo_title_suffix'         => ' - Funeral Notice',
		'social_share_image'       => '',
		'social_share_message'     => "Please join us in remembering {fullname}'s funeral service on {date}",

		// Layouts.
		'enable_archive_templates' => false,
		'default_archive_layout'   => 'modern',
		'default_single_layout'    => 'current',
		'default_card_style'       => 'standard',
		'card_spacing'             => 20,
		'image_aspect_ratio'       => '4:3',
		'enable_hover_effects'     => true,
		'enable_animations'        => true,

		// Search.
		'enable_advanced_search'   => true,
		'search_placeholder'       => 'Search funeral notices...',
		'enable_date_range'        => true,
		'enable_location_filter'   => true,
		'show_search_count'        => true,
		'enable_ajax_search'       => true,
		'min_search_length'        => 3,
		'search_delay'             => 300,

		// Styling.
		'color_scheme'             => 'custom',
		'enable_custom_css'        => false,
		'custom_css'               => '',
		'enable_dark_mode'         => false,
		'enable_high_contrast'     => false,
		'enable_reduced_motion'    => false,
		'css_optimization'         => true,

		// Video.
		'max_file_size_mb'         => 500,
		'quality_preset'           => 'balanced',
		'enable_thumbnails'        => true,
		'enable_progress_tracking' => true,

		// Licence.
		'license_key'              => '',
	];
}

/**
 * Retrieve plugin settings merged with defaults.
 *
 * @return array Current settings.
 */
function get_settings(): array {
	$saved = get_option( OPTION_NAME, [] );
	return wp_parse_args( $saved, get_defaults() );
}

/**
 * Enqueue the React settings app on the settings page only.
 *
 * @param string $hook The current admin page hook suffix.
 */
function enqueue_settings_assets( string $hook ): void {
	// Match both the top-level page and the dashboard settings submenu.
	if ( ! str_contains( $hook, 'hk-funeral-notices' ) ) {
		return;
	}

	$asset_file = HKFN_PLUGIN_DIR . 'build/settings/index.asset.php';
	if ( ! file_exists( $asset_file ) ) {
		return;
	}

	$asset = require $asset_file;

	wp_enqueue_script(
		'hkfn-settings',
		HKFN_PLUGIN_URL . 'build/settings/index.js',
		$asset['dependencies'],
		$asset['version'],
		true
	);

	// Ensure wp-components styles are loaded.
	wp_enqueue_style( 'wp-components' );

	wp_localize_script(
		'hkfn-settings',
		'hkfnPlugin',
		[
			'version' => HKFN_VERSION,
			'iconUrl' => \HumanKind\FuneralNotices\GitHubUpdater\HK_Funeral_Notices_Updater::ICON_SMALL,
		]
	);
}
add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\enqueue_settings_assets' );

/**
 * Register the plugin setting for both admin and REST API contexts.
 *
 * Using register_setting with show_in_rest makes the option available
 * at GET/POST /wp/v2/settings. The React app reads and writes via this
 * endpoint using @wordpress/api-fetch.
 */
function register_plugin_settings(): void {
	register_setting(
		'hkfn_settings',
		OPTION_NAME,
		[
			'type'              => 'object',
			'sanitize_callback' => __NAMESPACE__ . '\sanitize_settings',
			'default'           => get_defaults(),
			'show_in_rest'      => [
				'schema' => [
					'type'       => 'object',
					'properties' => [
						// General.
						'default_layout'           => [ 'type' => 'string' ],
						'posts_per_page'           => [ 'type' => 'integer' ],
						'load_more_posts'          => [ 'type' => 'integer' ],
						'columns'                  => [ 'type' => 'integer' ],
						'show_search'              => [ 'type' => 'boolean' ],
						'show_pagination'          => [ 'type' => 'boolean' ],
						'excerpt_length'           => [ 'type' => 'integer' ],
						'date_format'              => [ 'type' => 'string' ],
						'time_format'              => [ 'type' => 'string' ],
						'show_featured_image'      => [ 'type' => 'boolean' ],
						'image_size'               => [ 'type' => 'string' ],
						'enable_streaming'         => [ 'type' => 'boolean' ],
						'cache_duration'           => [ 'type' => 'integer' ],
						'single_slug'              => [ 'type' => 'string' ],
						'default_person_image'     => [ 'type' => 'string' ],
						'location_name'            => [ 'type' => 'string' ],
						'default_memorial_header'  => [ 'type' => 'string' ],
						'default_venue_location'   => [ 'type' => 'string' ],
						'address_field_mode'       => [ 'type' => 'string' ],
						'google_places_api_key'    => [ 'type' => 'string' ],
						'tribute_form_url'         => [ 'type' => 'string' ],

						// SEO.
						'enable_seo'               => [ 'type' => 'boolean' ],
						'noindex_funeral_notices'  => [ 'type' => 'boolean' ],
						'meta_description_length'  => [ 'type' => 'integer' ],
						'seo_title_suffix'         => [ 'type' => 'string' ],
						'social_share_image'       => [ 'type' => 'string' ],
						'social_share_message'     => [ 'type' => 'string' ],

						// Layouts.
						'enable_archive_templates' => [ 'type' => 'boolean' ],
						'default_archive_layout'   => [ 'type' => 'string' ],
						'default_single_layout'    => [ 'type' => 'string' ],
						'default_card_style'       => [ 'type' => 'string' ],
						'card_spacing'             => [ 'type' => 'integer' ],
						'image_aspect_ratio'       => [ 'type' => 'string' ],
						'enable_hover_effects'     => [ 'type' => 'boolean' ],
						'enable_animations'        => [ 'type' => 'boolean' ],

						// Search.
						'enable_advanced_search'   => [ 'type' => 'boolean' ],
						'search_placeholder'       => [ 'type' => 'string' ],
						'enable_date_range'        => [ 'type' => 'boolean' ],
						'enable_location_filter'   => [ 'type' => 'boolean' ],
						'show_search_count'        => [ 'type' => 'boolean' ],
						'enable_ajax_search'       => [ 'type' => 'boolean' ],
						'min_search_length'        => [ 'type' => 'integer' ],
						'search_delay'             => [ 'type' => 'integer' ],

						// Styling.
						'color_scheme'             => [ 'type' => 'string' ],
						'enable_custom_css'        => [ 'type' => 'boolean' ],
						'custom_css'               => [ 'type' => 'string' ],
						'enable_dark_mode'         => [ 'type' => 'boolean' ],
						'enable_high_contrast'     => [ 'type' => 'boolean' ],
						'enable_reduced_motion'    => [ 'type' => 'boolean' ],
						'css_optimization'         => [ 'type' => 'boolean' ],

						// Video.
						'max_file_size_mb'         => [ 'type' => 'integer' ],
						'quality_preset'           => [ 'type' => 'string' ],
						'enable_thumbnails'        => [ 'type' => 'boolean' ],
						'enable_progress_tracking' => [ 'type' => 'boolean' ],

						// Licence.
						'license_key'              => [ 'type' => 'string' ],
					],
				],
			],
		]
	);
}
add_action( 'admin_init', __NAMESPACE__ . '\register_plugin_settings' );
add_action( 'rest_api_init', __NAMESPACE__ . '\register_plugin_settings' );

/**
 * Sanitise settings input.
 *
 * @param mixed $input Raw input from the REST API or form submission.
 * @return array Sanitised settings.
 */
function sanitize_settings( $input ): array {
	$defaults  = get_defaults();
	$sanitised = [];

	// Booleans.
	$boolean_keys = [
		'show_search', 'show_pagination', 'show_featured_image', 'enable_streaming',
		'enable_seo', 'noindex_funeral_notices', 'enable_archive_templates',
		'enable_hover_effects', 'enable_animations', 'enable_advanced_search',
		'enable_date_range', 'enable_location_filter', 'show_search_count',
		'enable_ajax_search', 'enable_custom_css', 'enable_dark_mode',
		'enable_high_contrast', 'enable_reduced_motion', 'css_optimization',
		'enable_thumbnails', 'enable_progress_tracking',
	];

	foreach ( $boolean_keys as $key ) {
		$sanitised[ $key ] = isset( $input[ $key ] ) ? (bool) $input[ $key ] : ( $defaults[ $key ] ?? false );
	}

	// Integers with min/max.
	$int_ranges = [
		'posts_per_page'          => [ 1, 50 ],
		'load_more_posts'         => [ 1, 50 ],
		'columns'                 => [ 1, 4 ],
		'excerpt_length'          => [ 50, 500 ],
		'cache_duration'          => [ 300, 86400 ],
		'meta_description_length' => [ 120, 200 ],
		'card_spacing'            => [ 0, 60 ],
		'min_search_length'       => [ 1, 10 ],
		'search_delay'            => [ 0, 2000 ],
		'max_file_size_mb'        => [ 50, 2000 ],
	];

	foreach ( $int_ranges as $key => [ $min, $max ] ) {
		$sanitised[ $key ] = max( $min, min( $max, (int) ( $input[ $key ] ?? $defaults[ $key ] ) ) );
	}

	// Strings with allowed values.
	$enum_fields = [
		'default_layout'         => [ 'current', 'firehawk', 'modern', 'elegant', 'minimal' ],
		'date_format'            => [ 'F j, Y', 'j F Y', 'Y-m-d', 'd/m/Y', 'm/d/Y' ],
		'time_format'            => [ 'g:i a', 'G:i', 'h:i A' ],
		'image_size'             => [ 'thumbnail', 'medium', 'large', 'full' ],
		'address_field_mode'     => [ 'auto', 'acfe', 'custom' ],
		'default_archive_layout' => [ 'current', 'firehawk', 'modern', 'elegant', 'minimal' ],
		'default_single_layout'  => [ 'current', 'firehawk', 'modern', 'elegant', 'minimal' ],
		'default_card_style'     => [ 'standard', 'elevated', 'outlined', 'minimal' ],
		'image_aspect_ratio'     => [ '4:3', '16:9', '1:1', '3:2' ],
		'color_scheme'           => [ 'custom', 'light', 'dark' ],
		'quality_preset'         => [ 'low', 'balanced', 'high' ],
	];

	foreach ( $enum_fields as $key => $allowed ) {
		$sanitised[ $key ] = in_array( $input[ $key ] ?? '', $allowed, true )
			? $input[ $key ]
			: $defaults[ $key ];
	}

	// Sanitised text strings.
	$text_fields = [
		'single_slug', 'default_person_image', 'location_name',
		'default_memorial_header', 'default_venue_location',
		'google_places_api_key', 'seo_title_suffix',
		'social_share_image', 'search_placeholder', 'license_key',
	];

	foreach ( $text_fields as $key ) {
		$sanitised[ $key ] = sanitize_text_field( $input[ $key ] ?? $defaults[ $key ] );
	}

	// URL fields.
	$url_fields = [ 'tribute_form_url' ];
	foreach ( $url_fields as $key ) {
		$sanitised[ $key ] = esc_url_raw( $input[ $key ] ?? '' );
	}

	// Textarea fields.
	$sanitised['social_share_message'] = sanitize_textarea_field( $input['social_share_message'] ?? $defaults['social_share_message'] );
	$sanitised['custom_css']           = wp_strip_all_tags( $input['custom_css'] ?? '' );

	return $sanitised;
}
