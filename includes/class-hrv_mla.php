<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://www.linkedin.com/in/bonn-joel-elimanco-56a43a20
 * @since      1.0.0
 *
 * @package    HRV_MLA
 * @subpackage HRV_MLA/includes
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
 * @package    HRV_MLA
 * @subpackage HRV_MLA/includes
 * @author     Bonn Joel Elimanco <bonnbonito@gmail.com>
 */
class HRV_MLA {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      HRV_MLA_Loader    $loader    Maintains and registers all hooks for the plugin.
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
		if ( defined( 'HRV_MLA_VERSION' ) ) {
			$this->version = HRV_MLA_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'hrv_mla';

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
	 * - HRV_MLA_Loader. Orchestrates the hooks of the plugin.
	 * - HRV_MLA_i18n. Defines internationalization functionality.
	 * - HRV_MLA_Admin. Defines all hooks for the admin area.
	 * - HRV_MLA_Public. Defines all hooks for the public side of the site.
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
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-hrv_mla-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-hrv_mla-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-hrv_mla-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-hrv_mla-public.php';

		$this->loader = new HRV_MLA_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the HRV_MLA_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new HRV_MLA_i18n();

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

		$plugin_admin = new HRV_MLA_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		$this->loader->add_action( 'acf/init', $plugin_admin, 'acf_options' );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_hrv_page_menu_settings' );
		$this->loader->add_action( 'init', $plugin_admin, 'add_booking_column' );
		$this->loader->add_action( 'manage_bookings_posts_custom_column', $plugin_admin, 'hrv_mla_booking_days', 10, 2 );
		$this->loader->add_action( 'pre_get_posts', $plugin_admin, 'booking_column_sort' );
		$this->loader->add_action( 'admin_init', $plugin_admin, 'send_ask_payment_email_schedule' );
		$this->loader->add_action( 'admin_init', $plugin_admin, 'send_ask_review_schedule' );
		$this->loader->add_action( 'send_ask_payment_email_hook', $plugin_admin, 'send_ask_payment_email_function' );
		$this->loader->add_action( 'send_ask_review_hook', $plugin_admin, 'send_ask_review_email_function' );
		$this->loader->add_action( 'capture_deposit_stripe', $plugin_admin, 'capture_deposit_stripe_function' );
		$this->loader->add_action( 'acf/save_post', $plugin_admin, 'add_extras' );
		$this->loader->add_action( 'acf/save_post', $plugin_admin, 'calculate_total_price' );
		$this->loader->add_action( 'acf/save_post', $plugin_admin, 'calculate_total_extra_price' );
		$this->loader->add_action( 'init', $plugin_admin, 'booking_register_query_vars' );
		$this->loader->add_action( 'load-post.php', $plugin_admin, 'booking_golf_email_metabox' );
		$this->loader->add_action( 'wp_ajax_send_golf_booking_email', $plugin_admin, 'send_golf_booking_email' );
		$this->loader->add_action( 'manage_posts_extra_tablenav', $plugin_admin, 'render_owner_filter_options' );
		$this->loader->add_filter( 'acf/load_field/name=booking_property_owner', $plugin_admin, 'owner_radio_values' );
		$this->loader->add_action( 'acf/save_post', $plugin_admin, 'bookings_save_post' );

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new HRV_MLA_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
		$this->loader->add_action( 'init', $plugin_public, 'add_shortcodes' );
		$this->loader->add_action( 'init', $plugin_public, 'search_results' );
		$this->loader->add_action( 'wp_ajax_nopriv_book_property', $plugin_public, 'book_property' );
		$this->loader->add_action( 'wp_ajax_book_property', $plugin_public, 'book_property' );
		$this->loader->add_action( 'wp_ajax_nopriv_compute_season_price', $plugin_public, 'compute_season_price' );
		$this->loader->add_action( 'wp_ajax_compute_season_price', $plugin_public, 'compute_season_price' );
		$this->loader->add_action( 'wp_ajax_nopriv_property_available', $plugin_public, 'property_available' );
		$this->loader->add_action( 'wp_ajax_property_available', $plugin_public, 'property_available' );
		$this->loader->add_action( 'wp_ajax_nopriv_get_all_property_details', $plugin_public, 'get_all_property_details' );
		$this->loader->add_action( 'wp_ajax_get_all_property_details', $plugin_public, 'get_all_property_details' );
		$this->loader->add_action( 'wp_ajax_nopriv_check_availability', $plugin_public, 'check_availability' );
		$this->loader->add_action( 'wp_ajax_check_availability', $plugin_public, 'check_availability' );
		$this->loader->add_action( 'wp_footer', $plugin_public, 'contact_date_picker', 99 );
		// $this->loader->add_action( 'wp_footer', $plugin_public, 'mailchimp_test' );
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
	 * @return    HRV_MLA_Loader    Orchestrates the hooks of the plugin.
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