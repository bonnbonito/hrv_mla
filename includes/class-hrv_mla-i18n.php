<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://www.linkedin.com/in/bonn-joel-elimanco-56a43a20
 * @since      1.0.0
 *
 * @package    HRV_MLA
 * @subpackage HRV_MLA/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    HRV_MLA
 * @subpackage HRV_MLA/includes
 * @author     Bonn Joel Elimanco <bonnbonito@gmail.com>
 */
class HRV_MLA_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'hrv_mla',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
