<?php

/**
 * HumanKind Funeral Notices
 *
 * @author   Gareth Bissland | Weave Digital Studio
 * @license  GPL-2.0
 * @link     https://github.com/gbissland/hk-funeral-notices
 * @package  hk-funeral-notices
 */

/**
 * Plugin Name:       HumanKind Funeral Notices
 * Plugin URI:        https://humankindwebsites.com/funeral-notices-wordpress-plugin/
 * Description:       Professional funeral notice management with modern responsive layouts, advanced search, and comprehensive styling controls for funeral homes. Premium video streaming features available.
 * Version:           2.2.3
 * Author:            Gareth Bissland | Weave Digital Studio
 * Author URI:        https://weave.co.nz
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Domain Path:       /languages
 * Text Domain:       hk-funeral-notices
 * GitHub Plugin URI: https://github.com/HumanKind-nz/hk-funeral-notices
 * Primary Branch:    main
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Update URI:        https://humankindwebsites.com/wp-content/plugins/hoster/inc/secure-download.php?file=json&download=8031&token=HOSTER_TOKEN_HERE
 * Website:           https://humankindwebsites.com/
 */

// If this file is called directly, abort.
if (!defined("WPINC")) {
	die();
}

// Define plugin constants
define('WFN_VERSION', '2.2.3');
define('WFN_PLUGIN_FILE', __FILE__);
define('WFN_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WFN_PLUGIN_URL', plugin_dir_url(__FILE__));

// Hoster integration constants
define('WFN_HOSTER_DOWNLOAD_ID', 8031);
define('WFN_HOSTER_API_URL', 'https://humankindwebsites.com/wp-json/hoster/v1');
define('WFN_HOSTER_REMOTE_URL', 'https://humankindwebsites.com/wp-content/plugins/hoster/inc/secure-download.php?file=json&download=8031&token=HOSTER_TOKEN_HERE');
define('WFN_PREMIUM_FEATURE_VIDEO', 'video_streaming');

/**
 * Check PHP version requirement
 */
if (version_compare(PHP_VERSION, '8.0', '<')) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p>';
        echo '<strong>HumanKind Funeral Notices:</strong> This plugin requires PHP 8.0 or higher. ';
        echo 'You are running PHP ' . PHP_VERSION . '. Please upgrade PHP to continue using this plugin.';
        echo '</p></div>';
    });
    return;
}

/**
 * Autoload modern classes
 */
spl_autoload_register(function ($class) {
    $prefix = 'WeaveStudios\\FuneralNotices\\';
    $base_dir = __DIR__ . '/src/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-hk-funeral-notices-activator.php
 */
function activate_hk_funeral_notices()
{
	require_once plugin_dir_path(__FILE__) .
		"includes/class-hk-funeral-notices-activator.php";
	HK_Funeral_Notices_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-hk-funeral-notices-deactivator.php
 */
function deactivate_hk_funeral_notices()
{
	require_once plugin_dir_path(__FILE__) .
		"includes/class-hk-funeral-notices-deactivator.php";
	HK_Funeral_Notices_Deactivator::deactivate();
}

register_activation_hook(__FILE__, "activate_hk_funeral_notices");
register_deactivation_hook(__FILE__, "deactivate_hk_funeral_notices");

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . "includes/class-hk-funeral-notices.php";

/**
 * Load license handler (needed for frontend license checks)
 */
require_once plugin_dir_path(__FILE__) . 'includes/class-hoster-license.php';

/**
 * Initialize license handler early
 */
function init_license_handler() {
    // Initialize the license handler early so LicenseService can use it
    if (class_exists('HK_Funeral_Notices_License_Handler')) {
        HK_Funeral_Notices_License_Handler::init();
    }
}
add_action('init', 'init_license_handler', 1); // Very early priority

/**
 * Initialize unified updater (GitHub + Hoster) - admin only
 */
if (is_admin()) {
    require_once plugin_dir_path(__FILE__) . 'includes/class-unified-updater.php';
    HK_Funeral_Notices_Unified_Updater::init(__FILE__, WFN_HOSTER_REMOTE_URL);
}

/**
 * Initialize modern plugin system
 */
function init_modern_funeral_notices() {
    if (class_exists('WeaveStudios\FuneralNotices\Plugin')) {
        WeaveStudios\FuneralNotices\Plugin::getInstance();
    }
}
add_action('plugins_loaded', 'init_modern_funeral_notices');



/**
 * Add plugin action links (Settings & GitHub)
 */
function hk_funeral_notices_plugin_action_links($links) {
	$settings_link = '<a href="' . admin_url('admin.php?page=hk-funeral-notices') . '">Settings</a>';
	$github_link = '<a href="https://github.com/HumanKind-nz/hk-funeral-notices" target="_blank" rel="noopener noreferrer">GitHub</a>';
	
	array_unshift($links, $settings_link);
	array_push($links, $github_link);
	
	return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'hk_funeral_notices_plugin_action_links');

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    0.7.0
 */
function run_hk_funeral_notices()
{
	$plugin = new HK_Funeral_Notices();
	$plugin->run();
}
run_hk_funeral_notices();
