<?php
/**
 * General hooks and filters.
 *
 * Handles activation/deactivation, plugin action links,
 * and debug logging.
 *
 * @package HumanKind\FuneralNotices
 */

declare( strict_types=1 );

namespace HumanKind\FuneralNotices\Hooks;

defined( 'ABSPATH' ) || exit;

/**
 * Flush rewrite rules on activation so CPT permalinks work immediately.
 *
 * The funeral-notice CPT is not registered during the activation request
 * (it registers on init, which has already run), so flushing directly here
 * bakes in rules WITHOUT the CPT and single notice URLs 404 until a manual
 * permalink save. Instead, flag the flush and run it on the next request
 * after the CPT has registered.
 */
register_activation_hook( HKFN_PLUGIN_FILE, function (): void {
	update_option( 'hkfn_flush_rewrite_pending', 1, false );
} );

add_action( 'init', function (): void {
	if ( get_option( 'hkfn_flush_rewrite_pending' ) ) {
		delete_option( 'hkfn_flush_rewrite_pending' );
		flush_rewrite_rules();
	}
}, 99 );

/**
 * Flush rewrite rules on deactivation to clean up.
 */
register_deactivation_hook( HKFN_PLUGIN_FILE, function (): void {
	flush_rewrite_rules();
} );

/**
 * Add a "Settings" link to the plugin row on the Plugins screen.
 *
 * @param array $links Existing action links.
 * @return array Modified action links.
 */
function add_plugin_action_links( array $links ): array {
	$settings_link = sprintf(
		'<a href="%s">%s</a>',
		esc_url( admin_url( 'admin.php?page=hk-funeral-notices-settings' ) ),
		esc_html__( 'Settings', 'hk-funeral-notices' )
	);
	array_unshift( $links, $settings_link );
	return $links;
}
add_filter(
	'plugin_action_links_' . plugin_basename( HKFN_PLUGIN_FILE ),
	__NAMESPACE__ . '\add_plugin_action_links'
);

/**
 * Load the plugin text domain for translations.
 */
add_action(
	'init',
	function (): void {
		load_plugin_textdomain(
			'hk-funeral-notices',
			false,
			dirname( plugin_basename( HKFN_PLUGIN_FILE ) ) . '/languages'
		);
	}
);

/**
 * Log debug messages when WP_DEBUG is enabled.
 *
 * @param string $message The message to log.
 */
function debug_log( string $message ): void {
	if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
		return;
	}

	// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	error_log( '[HK Funeral Notices] ' . $message );
}
