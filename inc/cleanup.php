<?php
/**
 * One-off cleanup of data left behind by removed features.
 *
 * Each routine is flag-guarded so it runs once per site and is a no-op
 * afterwards. Once every site has updated past the version that introduced
 * a routine, that routine can be deleted from this file.
 *
 * @package HumanKind\FuneralNotices
 */

declare( strict_types=1 );

namespace HumanKind\FuneralNotices\Cleanup;

defined( 'ABSPATH' ) || exit;

/**
 * Remove the usage-analytics scheduled events and options (removed in 3.0.2).
 *
 * The analytics module reported anonymous usage counts to a hosted Supabase
 * project. Both the module and the service were removed in 3.0.2, which
 * leaves two kinds of orphan behind on existing sites:
 *
 * 1. Scheduled cron events with no listener. v2.x registered these under
 *    wfn_ names and v3.0.0 re-registered them under hkfn_ names without
 *    clearing the old ones, so an upgraded site can carry both sets.
 * 2. Options holding the anonymous site identifier and registration flag,
 *    again under both prefixes.
 *
 * Neither is harmful on its own. A cron event with no listener just fires
 * and does nothing. Clearing them keeps the cron array and options table
 * honest, and makes sure no site keeps calling a project that has been
 * deleted.
 */
function remove_usage_analytics(): void {
	if ( get_option( 'hkfn_analytics_cleanup_done' ) ) {
		return;
	}

	$hooks = [
		'hkfn_send_monthly_analytics',
		'hkfn_supabase_heartbeat',
		'wfn_send_monthly_analytics',
		'wfn_supabase_heartbeat',
	];

	foreach ( $hooks as $hook ) {
		wp_unschedule_hook( $hook );
	}

	$options = [
		'hkfn_analytics_site_id',
		'hkfn_analytics_registered',
		'hkfn_module_analytics_enabled',
		'wfn_analytics_site_id',
		'wfn_analytics_registered',
		'wfn_module_analytics_enabled',
	];

	foreach ( $options as $option ) {
		delete_option( $option );
	}

	update_option( 'hkfn_analytics_cleanup_done', 1, false );
}

add_action( 'admin_init', __NAMESPACE__ . '\remove_usage_analytics' );
