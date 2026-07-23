<?php
/**
 * Plugin Name:       HumanKind Funeral Notices
 * Plugin URI:        https://humankindwebsites.com/plugins/funeral-notices/
 * Description:       Professional funeral notice management with modern responsive layouts, advanced search, and comprehensive styling controls for funeral homes. Premium video streaming features available.
 * Version:           3.0.0
 * Requires at least: 6.6
 * Requires PHP:      8.1
 * Author:            Gareth Bissland | Weave Digital Studio
 * Author URI:        https://weave.co.nz
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       hk-funeral-notices
 * Domain Path:       /languages
 * GitHub Plugin URI: https://github.com/HumanKind-nz/hk-funeral-notices
 * Primary Branch:    main
 * Website:           https://humankindwebsites.com/
 *
 * @package HumanKind\FuneralNotices
 */

declare( strict_types=1 );

namespace HumanKind\FuneralNotices;

defined( 'ABSPATH' ) || exit;

// Plugin constants.
define( 'HKFN_VERSION', '3.0.0' );
define( 'HKFN_PLUGIN_FILE', __FILE__ );
define( 'HKFN_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'HKFN_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Hoster integration constants.
define( 'HKFN_HOSTER_DOWNLOAD_ID', 8031 );
define( 'HKFN_HOSTER_API_URL', 'https://humankindwebsites.com/wp-json/hoster/v1' );
define( 'HKFN_HOSTER_REMOTE_URL', 'https://humankindwebsites.com/wp-content/plugins/hoster/inc/secure-download.php?file=json&download=8031&token=HOSTER_TOKEN_HERE' );
define( 'HKFN_PREMIUM_FEATURE_VIDEO', 'video_streaming' );

// Backward-compat aliases for any external code referencing old constants.
define( 'WFN_VERSION', HKFN_VERSION );
define( 'WFN_PLUGIN_FILE', HKFN_PLUGIN_FILE );
define( 'WFN_PLUGIN_DIR', HKFN_PLUGIN_DIR );
define( 'WFN_PLUGIN_URL', HKFN_PLUGIN_URL );

// Global helper functions (must be loaded before any other code that uses them).
require_once __DIR__ . '/inc/helpers.php';

/**
 * Check PHP version requirement.
 */
if ( version_compare( PHP_VERSION, '8.1', '<' ) ) {
	add_action(
		'admin_notices',
		function (): void {
			echo '<div class="notice notice-error"><p>';
			echo '<strong>HumanKind Funeral Notices:</strong> This plugin requires PHP 8.1 or higher. ';
			echo 'You are running PHP ' . esc_html( PHP_VERSION ) . '. Please upgrade PHP to continue using this plugin.';
			echo '</p></div>';
		}
	);
	return;
}

/**
 * Autoload modern classes from src/.
 */
spl_autoload_register(
	function ( string $class ): void {
		$prefix   = 'HumanKind\\FuneralNotices\\';
		$base_dir = __DIR__ . '/src/';

		$len = strlen( $prefix );
		if ( strncmp( $prefix, $class, $len ) !== 0 ) {
			return;
		}

		$relative_class = substr( $class, $len );
		$file           = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

		if ( file_exists( $file ) ) {
			require $file;
		}
	}
);

// Scaffold-pattern includes.
require_once HKFN_PLUGIN_DIR . 'inc/hooks.php';
require_once HKFN_PLUGIN_DIR . 'inc/settings-page.php';
require_once HKFN_PLUGIN_DIR . 'inc/github-updater.php';

// Legacy integration files — will migrate to inc/ individually in future.
require_once HKFN_PLUGIN_DIR . 'includes/class-acf.php';
require_once HKFN_PLUGIN_DIR . 'includes/class-role-integration.php';
require_once HKFN_PLUGIN_DIR . 'includes/class-wpbf.php';
require_once HKFN_PLUGIN_DIR . 'includes/class-query.php';
require_once HKFN_PLUGIN_DIR . 'includes/class-seopress.php';
require_once HKFN_PLUGIN_DIR . 'includes/class-acf-style.php';
require_once HKFN_PLUGIN_DIR . 'includes/class-hoster-license.php';

/**
 * Initialize license handler early so LicenseService can use it.
 */
add_action(
	'init',
	function (): void {
		if ( class_exists( 'HK_Funeral_Notices_License_Handler' ) ) {
			\HK_Funeral_Notices_License_Handler::init();
		}
	},
	1
);

/**
 * Initialize modern plugin system.
 */
add_action(
	'plugins_loaded',
	function (): void {
		if ( class_exists( Plugin::class ) ) {
			Plugin::getInstance();
		}
	}
);
