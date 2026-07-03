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
