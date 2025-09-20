<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://humankindfuneralnotices.com
 * @since      1.0.0
 *
 * @package    HK_Funeral_Notices
 * @subpackage HK_Funeral_Notices/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    HK_Funeral_Notices
 * @subpackage HK_Funeral_Notices/includes
 * @author     Gareth Bissland | HumanKind Digital Studio
 */
class HK_Funeral_Notices_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'weave-funeral-notices',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
