<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://humankindfuneralnotices.com
 * @since      1.0.0
 *
 * @package    HK_Funeral_Notices
 * @subpackage HK_Funeral_Notices/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    HK_Funeral_Notices
 * @subpackage HK_Funeral_Notices/public
 * @author     Gareth Bissland | HumanKind Digital Studio
 */
class HK_Funeral_Notices_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}


	/**
	 * Register the stylesheets for the public-facing side of the site.
	 * 
	 * @deprecated 2.1.0 Legacy CSS file has been removed. Styles now handled by modules.
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		// Legacy CSS file removed in v2.1.0
		// All styles are now handled by the module system (LayoutsModule, StylingModule, etc.)
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @deprecated 2.1.0 Legacy JS file has been removed (was empty).
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		// Legacy JS file removed in v2.1.0 (file was empty)
		// All JavaScript functionality is now handled by the module system
	}

}
