<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://humankindfuneralnotices.com
 * @since      1.0.0
 *
 * @package    HK_Funeral_Notices
 * @subpackage HK_Funeral_Notices/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    HK_Funeral_Notices
 * @subpackage HK_Funeral_Notices/includes
 * @author     Gareth Bissland | HumanKind Digital Studio
 */
class HK_Funeral_Notices {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      HK_Funeral_Notices_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'HK_FUNERAL_NOTICES_VERSION' ) ) {
			$this->version = HK_FUNERAL_NOTICES_VERSION;
		} else {
			$this->version = '2.0.0';
		}
		$this->plugin_name = 'hk-funeral-notices';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - HK_Funeral_Notices_Loader. Orchestrates the hooks of the plugin.
	 * - HK_Funeral_Notices_i18n. Defines internationalization functionality.
	 * - HK_Funeral_Notices_Admin. Defines all hooks for the admin area.
	 * - HK_Funeral_Notices_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-hk-funeral-notices-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-hk-funeral-notices-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-hk-funeral-notices-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-hk-funeral-notices-public.php';

		$this->loader = new HK_Funeral_Notices_Loader();
		
		//  Functions for Custom Fields  (ACF & ACFE)
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-acf.php';
		
		// Role integration with HK Funeral Suite
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-role-integration.php';
		
		// Additions for WPBF Theme
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wpbf.php';
		
		
		//  Additional Query Loop parameters for BB
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-query.php';
		
		//  Meta and no-index settings with SEO Press for Funeral Notices
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-seopress.php';
		
		// Style Funeral Backend
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-acf-style.php';

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the HK_Funeral_Notices_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new HK_Funeral_Notices_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new HK_Funeral_Notices_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new HK_Funeral_Notices_Public( $this->get_plugin_name(), $this->get_version() );

		// Legacy enqueue hooks disabled in v2.1.0 - files removed, functionality moved to modules
		// $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		// $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    HK_Funeral_Notices_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
