<?php
/**
 * Global helper functions.
 *
 * These are intentionally in the global namespace so they can be called
 * from any file without use/import statements.
 *
 * @package HumanKind\FuneralNotices
 */

defined( 'ABSPATH' ) || exit;

/**
 * Read a wp-config constant with legacy WFN_ fallback.
 *
 * Sites configured for v2.x define WFN_* constants in wp-config.php
 * (video credentials, licence bypass, debug). v3 reads the HKFN_* name
 * first and falls back to the WFN_* name so upgrades work without
 * editing wp-config on every site.
 *
 * @param string $suffix Constant name without the HKFN_/WFN_ prefix.
 * @param mixed  $default Returned when neither constant is defined.
 * @return mixed
 */
function hkfn_get_constant( string $suffix, $default = null ) {
	if ( defined( 'HKFN_' . $suffix ) ) {
		return constant( 'HKFN_' . $suffix );
	}
	if ( defined( 'WFN_' . $suffix ) ) {
		return constant( 'WFN_' . $suffix );
	}
	return $default;
}

/**
 * Dual-read option helper for wfn_ → hkfn_ migration.
 *
 * Reads hkfn_ prefixed option first. Falls back to wfn_ if not found.
 * On next save, the option is written with the hkfn_ prefix only.
 *
 * @param string $key     Option key WITHOUT prefix (e.g. 'module_settings').
 * @param mixed  $default Default value if neither option exists.
 * @return mixed
 */
function hkfn_get_option( string $key, $default = false ) {
	$value = get_option( 'hkfn_' . $key, null );
	if ( $value !== null ) {
		return $value;
	}
	return get_option( 'wfn_' . $key, $default );
}

/**
 * The site's Google Maps / Places API key, '' when none is set.
 *
 * One lookup for every consumer (the address autocomplete field, the
 * address field manager and ACF's own map field), in this order:
 * the plugin setting (Settings, General, Advanced), the older ACF
 * options field, then HKFN_GOOGLE_MAPS_KEY (or WFN_GOOGLE_MAPS_KEY) in
 * wp-config.php or GridPane's user-configs.php.
 *
 * @return string
 */
function hkfn_google_maps_key(): string {
	$settings = hkfn_get_option( 'module_settings', array() );
	$key      = is_array( $settings ) ? trim( (string) ( $settings['google_places_api_key'] ?? '' ) ) : '';

	if ( '' === $key && function_exists( 'get_field' ) ) {
		$key = trim( (string) ( get_field( 'hkfn_google_places_api_key', 'option' ) ?: '' ) );
	}

	if ( '' === $key ) {
		$key = trim( (string) hkfn_get_constant( 'GOOGLE_MAPS_KEY', '' ) );
	}

	return $key;
}
