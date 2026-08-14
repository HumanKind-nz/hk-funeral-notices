<?php
/**
 * Bridge between the consolidated React settings option and module options.
 *
 * The React settings app reads and writes one consolidated option
 * (hkfn_settings, see settings-page.php) but the plugin runtime reads
 * per-module options: the hkfn_module_settings aggregate (SettingsModule)
 * plus hkfn_module_{id}_settings for layouts, search, styling, and video,
 * and the standalone hkfn_license_key managed by LicenseService.
 *
 * This bridge keeps the two in sync in both directions:
 *
 * - Seed: a one-off, version-flagged copy of the current runtime values
 *   into hkfn_settings so the React UI reflects what the site actually
 *   uses (upgraded sites otherwise show defaults and an empty licence).
 * - Write-back: whenever hkfn_settings is saved, changed keys fan out to
 *   the module options the runtime reads. A changed licence key triggers
 *   real activation through LicenseService.
 *
 * @package HumanKind\FuneralNotices
 */

declare( strict_types=1 );

namespace HumanKind\FuneralNotices\SettingsBridge;

use HumanKind\FuneralNotices\Services\LicenseService;

defined( 'ABSPATH' ) || exit;

/**
 * Flag option marking the one-off seed as done.
 */
const SEEDED_FLAG = 'hkfn_settings_seeded';

/**
 * Map of runtime destination option => consolidated keys stored in it.
 *
 * license_key is handled separately (standalone option via LicenseService).
 *
 * @return array<string, string[]>
 */
function destination_map(): array {
	return [
		'hkfn_module_settings' => [
			'default_layout',
			'posts_per_page',
			'load_more_posts',
			'columns',
			'show_search',
			'show_pagination',
			'excerpt_length',
			'date_format',
			'time_format',
			'show_featured_image',
			'image_size',
			'enable_streaming',
			'cache_duration',
			'single_slug',
			'default_person_image',
			'location_name',
			'default_memorial_header',
			'default_venue_location',
			'address_field_mode',
			'google_places_api_key',
			'tribute_form_url',
			'enable_seo',
			'noindex_funeral_notices',
			'meta_description_length',
			'seo_title_suffix',
			'social_share_image',
			'social_share_message',
		],
		'hkfn_module_layouts_settings' => [
			'enable_archive_templates',
			'default_archive_layout',
			'default_single_layout',
			'default_card_style',
			'card_spacing',
			'enable_hover_effects',
			'enable_animations',
		],
		'hkfn_module_search_settings' => [
			'enable_advanced_search',
			'search_placeholder',
			'enable_date_range',
			'enable_location_filter',
			'show_search_count',
			'enable_ajax_search',
			'min_search_length',
			'search_delay',
		],
		'hkfn_module_styling_settings' => [
			'color_scheme',
			'enable_custom_css',
			'custom_css',
			'enable_dark_mode',
			'enable_high_contrast',
			'enable_reduced_motion',
			'css_optimization',
		],
		'hkfn_module_video_settings' => [
			'max_file_size_mb',
			'quality_preset',
			'enable_thumbnails',
			'enable_progress_tracking',
		],
	];
}

/**
 * Read a destination option with the same wfn_ fallback the runtime uses.
 *
 * @param string $option Option name (hkfn_ prefixed).
 * @return array Stored values, empty array when nothing is stored.
 */
function read_destination( string $option ): array {
	$value = get_option( $option, null );
	if ( $value === null ) {
		$value = get_option( preg_replace( '/^hkfn_/', 'wfn_', $option ), [] );
	}
	return is_array( $value ) ? $value : [];
}

/**
 * Whether a write-back is currently suppressed (during seeding).
 *
 * @param bool|null $set New state, or null to read.
 * @return bool
 */
function suppress_write_back( ?bool $set = null ): bool {
	static $suppressed = false;
	if ( $set !== null ) {
		$suppressed = $set;
	}
	return $suppressed;
}

/**
 * One-off seed of the consolidated option from the live runtime values.
 *
 * Overwrites hkfn_settings deliberately: until this bridge existed nothing
 * consumed that option, so the module options are the only truth worth
 * keeping. Runs once per site, tracked by the SEEDED_FLAG option.
 */
function maybe_seed(): void {
	if ( get_option( SEEDED_FLAG ) ) {
		return;
	}

	$seeded = [];
	foreach ( destination_map() as $option => $keys ) {
		$stored = read_destination( $option );
		foreach ( $keys as $key ) {
			if ( array_key_exists( $key, $stored ) ) {
				$seeded[ $key ] = $stored[ $key ];
			}
		}
	}

	$license_key = hkfn_get_option( 'license_key', '' );
	if ( is_string( $license_key ) && $license_key !== '' ) {
		$seeded['license_key'] = $license_key;
	}

	suppress_write_back( true );
	update_option( 'hkfn_settings', $seeded, false );
	suppress_write_back( false );

	update_option( SEEDED_FLAG, 1, false );
}
// Before the settings screen (admin) or the REST read (React app) serve values.
add_action( 'admin_init', __NAMESPACE__ . '\maybe_seed', 5 );
add_action( 'rest_api_init', __NAMESPACE__ . '\maybe_seed', 5 );

/**
 * Fan changed consolidated values back out to the runtime options.
 *
 * @param mixed $old_value Previous consolidated value.
 * @param mixed $new_value New consolidated value.
 */
function write_back( $old_value, $new_value ): void {
	if ( suppress_write_back() || ! is_array( $new_value ) ) {
		return;
	}
	$old_value = is_array( $old_value ) ? $old_value : [];

	foreach ( destination_map() as $option => $keys ) {
		$changed = [];
		foreach ( $keys as $key ) {
			if ( ! array_key_exists( $key, $new_value ) ) {
				continue;
			}
			$was = $old_value[ $key ] ?? null;
			if ( $was !== $new_value[ $key ] ) {
				$changed[ $key ] = $new_value[ $key ];
			}
		}
		if ( $changed ) {
			// Merge so module keys the React UI doesn't manage survive.
			update_option( $option, array_merge( read_destination( $option ), $changed ) );
		}
	}

	sync_license_key( $old_value, $new_value );
}
add_action( 'update_option_hkfn_settings', __NAMESPACE__ . '\write_back', 10, 2 );
add_action( 'add_option_hkfn_settings', function ( $option, $value ): void {
	write_back( [], $value );
}, 10, 2 );

/**
 * Activate a licence key changed through the settings screen.
 *
 * An emptied field is ignored on purpose: nothing consumed this value
 * before v3.0.3, so an empty field is far more likely a stale UI state
 * than a deliberate deactivation. Deactivation stays in LicenseService.
 *
 * @param array $old_value Previous consolidated value.
 * @param array $new_value New consolidated value.
 */
function sync_license_key( array $old_value, array $new_value ): void {
	$new_key = isset( $new_value['license_key'] ) ? sanitize_text_field( trim( (string) $new_value['license_key'] ) ) : '';
	$old_key = isset( $old_value['license_key'] ) ? sanitize_text_field( trim( (string) $old_value['license_key'] ) ) : '';

	if ( $new_key === '' || $new_key === $old_key ) {
		return;
	}
	if ( $new_key === hkfn_get_option( 'license_key', '' ) ) {
		return;
	}

	$result = LicenseService::getInstance()->activateLicense( $new_key );
	if ( empty( $result['success'] ) ) {
		error_log(
			'HKFN Settings: licence activation failed for key entered on settings screen - '
			. ( $result['message'] ?? 'unknown error' )
		);
	}
}
