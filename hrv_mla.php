<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://www.linkedin.com/in/bonn-joel-elimanco-56a43a20
 * @since             1.0.0
 * @package           HRV_MLA
 *
 * @wordpress-plugin
 * Plugin Name:       HRV - MLA Plugin
 * Plugin URI:        https://www.mlawebdesigns.co.uk
 * Description:       Plugin for HRV Website
 * Version:           2.0.2
 * Author:            Bonn Joel Elimanco
 * Author URI:        https://www.linkedin.com/in/bonn-joel-elimanco-56a43a20
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       hrv_mla
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 20.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'HRV_MLA_VERSION', '2.0.4' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-hrv_mla-activator.php
 */
function activate_hrv_mla() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-hrv_mla-activator.php';
	HRV_MLA_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-hrv_mla-deactivator.php
 */
function deactivate_hrv_mla() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-hrv_mla-deactivator.php';
	HRV_MLA_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_hrv_mla' );
register_deactivation_hook( __FILE__, 'deactivate_hrv_mla' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';
require plugin_dir_path( __FILE__ ) . 'includes/class-hrv_mla.php';
require plugin_dir_path( __FILE__ ) . 'plugin-update-checker/plugin-update-checker.php';

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$myUpdateChecker = PucFactory::buildUpdateChecker(
	'https://github.com/bonnbonito/hrv_mla/',
	__FILE__,
	'hrv_mla'
);

//Set the branch that contains the stable release.
$myUpdateChecker->setBranch('master');

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_hrv_mla() {

	$plugin = new HRV_MLA();
	$plugin->run();

}
run_hrv_mla();
