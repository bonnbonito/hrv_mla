<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://www.linkedin.com/in/bonn-joel-elimanco-56a43a20
 * @since      1.0.0
 *
 * @package    HRV_MLA
 * @subpackage HRV_MLA/admin
 */

use Bonn\PluginUpdateChecker\v5p0\Plugin\Update;

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    HRV_MLA
 * @subpackage HRV_MLA/admin
 * @author     Bonn Joel Elimanco <bonnbonito@gmail.com>
 */

class HRV_MLA_Admin {

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
	 * @param      string $plugin_name       The name of this plugin.
	 * @param      string $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name     = $plugin_name;
		$this->version         = $version;
		$this->client_id       = get_field( 'xero_client_key', 'option' ) ? get_field( 'xero_client_key', 'option' ) : '';
		$this->client_secret   = get_field( 'xero_secret_key', 'option' ) ? get_field( 'xero_secret_key', 'option' ) : '';
		$this->redirect_url    = get_field( 'xero_redirect_url', 'option' ) ? get_field( 'xero_redirect_url', 'option' ) : '';
		$this->ciirus_url      = 'http://xml.ciirus.com/CiirusXML.12.017.asmx';
		$this->ciirus_api      = 'http://api.ciirus.com/CiirusXML.15.025.asmx';
		$this->ciirus_user     = '74db9a060ce9426';
		$this->ciirus_password = '4e1276922b63493';
		$this->days_to_notify  = get_field( 'number_of_days', 'option' ) ? (int) get_field( 'number_of_days', 'option' ) : 36;
		$this->deposit         = get_field( 'stripe_deposit', 'option' ) ? (int) get_field( 'stripe_deposit', 'option' ) : 250;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		/**
		   * This function is provided for demonstration purposes only.
		   *
		   * An instance of this class should be passed to the run() function
		   * defined in HRV_MLA_Loader as all of the hooks are defined
		   * in that particular class.
		   *
		   * The HRV_MLA_Loader will then create the relationship
		   * between the defined hooks and the functions defined in this
		   * class.
		   */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/hrv_mla-admin.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		 /**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in HRV_MLA_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The HRV_MLA_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/hrv_mla-admin.js', array( 'jquery' ), $this->version, false );

		wp_localize_script(
			$this->plugin_name,
			'XERO',
			array(
				'ajax_url'      => admin_url( 'admin-ajax.php' ),
				'nonce'         => wp_create_nonce( 'xero-nonce' ),
				'golfnonce'     => wp_create_nonce( 'golf-nonce' ),
				'client_id'     => $this->client_id,
				'client_secret' => $this->client_secret,
				'redirect_url'  => $this->redirect_url,
			),
		);
	}


	public function booking_register_query_vars() {
		add_filter(
			'query_vars',
			function ( $vars ) {
				$vars[] = 'future_booking';
				$vars[] = 'property_owner';
				return $vars;
			}
		);

		add_filter( 'views_edit-bookings', array( $this, 'booking_future_lists' ) );
	}

	public function booking_future_lists( $views ) {
		array_push(
			$views,
			sprintf(
				'
				<a href="%1$s" %2$s>%3$s <span class="count">(%4$d)</span></a>
				',
				add_query_arg(
					array(
						'post_type'      => 'bookings',
						'orderby'        => 'days_left',
						'order'          => 'asc',
						'future_booking' => 1,
					),
					'edit.php'
				),
				$this->future_booking_add_current_class(),
				__( 'Future Bookings', 'hrv-mla' ),
				$this->count_future_bookings(),
			)
		);
		return $views;
	}

	public function future_booking_add_current_class() {
		$attrs = 'class=""';

		if ( 1 === (int) filter_input( INPUT_GET, 'future_booking' ) ) {
			$attrs = 'class="current" aria-current="page"';
		}

		return $attrs;
	}

	public function count_future_bookings() {
		$today       = date( 'Ymd' );
		$future_args = new WP_Query(
			array(
				'post_type'    => 'bookings',
				'meta_key'     => 'arrival_date',
				'meta_value'   => $today,
				'meta_compare' => '>=',
			)
		);

		return $future_args->found_posts;
	}

	/**
	 * Add Dashboard Menu
	 */
	public function add_hrv_page_menu_settings() {
		add_menu_page( 'Xero Integration', 'Xero Integration', 'manage_options', 'xero-integration', array( $this, 'hrv_xero_integration' ), 'dashicons-controls-repeat' );

		//add_submenu_page( 'xero-integration', 'HRV Import', 'HRV Import', 'manage_options', 'hrv-import', array( $this, 'hrv_import_function' ) );

		//add_submenu_page( 'xero-integration', 'Booking Import', 'Booking Import', 'manage_options', 'booking-import', array( $this, 'hrv_import_booking_function' ) );
	}

	/**
	 * Import Old bookings
	 */
	public function hrv_import_booking_function() {

		global $wpdb;

		$results = $wpdb->get_results( "SELECT * FROM `VillaBookings` WHERE `BookingDate` > '2022-12-30' AND `CancelNo` IS NULL ORDER BY `BookingDate` ASC", ARRAY_A );

		foreach ( $results as $result ) {
			$user = $wpdb->get_row( 'SELECT * FROM `Client` WHERE `ClientID` = ' . $result['ClientID'] );

			$booking_details = $wpdb->get_row( 'SELECT * FROM `VillaBookingDetail` WHERE `BookingID` = ' . $result['BookingID'] );

			$villa = $wpdb->get_row( 'SELECT * FROM `Villa` WHERE `VillaID` = ' . $booking_details->VillaID );



			$owners = $wpdb->get_row( 'SELECT * FROM `Owners` WHERE `OwnerID` = ' . $booking_details->OwnerID );

			echo '<pre>';
			print_r($result);
			print_r($user);
			echo '</pre>';

			$comments = $result['Comments'];
			$villa_percentage = $result['commpertage'];
			$total_price = $booking_details->Pricetous;
			$villa_name = $villa->VillaName;
			$villa_address = $villa->Address1 . ' ' . $villa->Address2 . ' ' . $villa->Address3;
			$owner_name = $owners->OwnerName;
			$owner_email = $owners->Email;

			$first_name    = $user->FirstName;
			$last_name     = $user->FamilyName;
			$phone         = $user->ResPhone;
			$email         = $user->Email;
			$arrival       = $result['ArrivalDate'];
			$departure     = $result['DepartureDate'];
			$post_date     = date( 'Y-m-d', strtotime( $result['BookingDate'] ) );
			$state = $user->state;
			$postalzipcode = $user->zip;

			$booking_title = 'HRV-' . $result['BookingID'] . ' - ' . $first_name . ' ' . $last_name;

			$phamout = $result['phamount'];
			$bbqamount = $result['bbqamount'];
			$commission = $result['commission'];

			$booking = array(
				'post_type'   => 'bookings',
				'post_title'  => $booking_title,
				'post_status' => 'publish',
				'post_author' => 3,
				'post_date'   => $post_date,
			);

			$post_id = post_exists( $booking_title ) or wp_insert_post( $booking );

			if ( $post_id ) {
				update_field( 'state', $state, $post_id );
				update_field( 'postalzipcode', $postalzipcode, $post_id );
				update_post_meta( $post_id, 'manual_booking', 'yes' );
				update_field( 'first_name', $first_name, $post_id );
				update_field( 'surname', $last_name, $post_id );
				update_field( 'email', $email, $post_id );
				update_field( 'phone', $phone, $post_id );
				update_field( 'adults', $result['AdultsNo'], $post_id );
				update_field( 'children', $result['ChildrenNo'], $post_id );
				update_field( 'arrival_date', date( 'Ymd', strtotime( $arrival ) ), $post_id );
				update_field( 'end_date', date( 'Ymd', strtotime( $departure ) ), $post_id );
				update_field( 'no_of_nights', $result['NoofNights'], $post_id );
				update_field( 'no_of_bedrooms', $result['Noofbedrooms'], $post_id );
				update_field( 'booking_season_price', $result['PricetoClient'], $post_id );
				update_field( 'payment_status', 'deposit', $post_id );
				update_field( 'booking_property_address', $villa_address, $post_id );
				update_field( 'property', $villa_name, $post_id );
				update_field( 'booking_property_owner_name', $owner_name, $post_id );
				update_field( 'booking_property_owner_email', $owner_email, $post_id );
			 	update_field( 'comments', $comments, $post_id );
				update_field( 'total_profit', "", $post_id);
				update_field( 'owner_total_villa_price', $total_price, $post_id);
				update_field( 'property_owner_percentage', $villa_percentage, $post_id);
				update_field( 'old_booking_id', $result['BookingID'], $post_id);

				$rows = array();

				if ( $phamout || $bbqamount ) {

					if ( $phamout ) {

						$rows[]        = array(
							'extra_cost'       => 'Extra',
							'price'            => $phamout,
						);

					}

					if ( $bbqamount ) {

						$rows[]        = array(
							'extra_cost'       => 'bbqamount',
							'price'            => $bbqamount,
						);

					}


					update_field( 'field_61fbad0ce3c30', $rows, $post_id );
				}

				if ( $commission ) {
					update_field('total_profit', $commission, $post_id);
				}


				$my_post = array(
					'ID'            => $post_id,
					'post_date'     => date( 'Y-m-d', strtotime( $result['BookingDate'] ) ),
					'post_date_gmt' => gmdate( 'Y-m-d', strtotime( $result['BookingDate'] ) ),
				);

				wp_update_post( $my_post );
			}
		}

		echo 'Booking Imported' . count( $results );
	}

	public function xero_provider() {
		$provider = new \League\OAuth2\Client\Provider\GenericProvider(
			array(
				'clientId'                => $this->client_id,
				'clientSecret'            => $this->client_secret,
				'redirectUri'             => $this->redirect_url,
				'urlAuthorize'            => 'https://login.xero.com/identity/connect/authorize',
				'urlAccessToken'          => 'https://identity.xero.com/connect/token',
				'urlResourceOwnerDetails' => 'https://api.xero.com/api.xro/2.0/Organisation',
			)
		);

		return $provider;
	}

	/**
	 * Authorization URL
	 */
	public function xero_authorization_url() {
		$provider = $this->xero_provider();

		$options = array(
			'scope' => array( 'openid email profile offline_access accounting.settings accounting.transactions accounting.contacts accounting.journals.read accounting.reports.read accounting.attachments' ),
		);

		return $provider->getAuthorizationUrl( $options );
	}

	/**
	 * Connect to Xero
	 */
	public function hrv_import_function() {
		 global $wpdb;

		$results = $wpdb->get_results( 'SELECT * FROM `villa`', ARRAY_A );

		foreach ( $results as $result ) {
			$property = array(
				'post_type'   => 'properties',
				'post_title'  => $result['VillaName'],
				'post_status' => 'publish',
				'post_author' => 3,
			);

			$post_id = post_exists( $result['VillaName'] ) or wp_insert_post( $property );

			if ( $post_id ) {
				$gallery = array(
					attachment_url_to_postid( 'https://hrv.test/wp-content/uploads/2022/07/' . str_replace( 'gif', 'jpg', $result['OtherImage1'] ) ),
					attachment_url_to_postid( 'https://hrv.test/wp-content/uploads/2022/07/' . str_replace( 'gif', 'jpg', $result['OtherImage2'] ) ),
					attachment_url_to_postid( 'https://hrv.test/wp-content/uploads/2022/07/' . str_replace( 'gif', 'jpg', $result['OtherImage3'] ) ),
					attachment_url_to_postid( 'https://hrv.test/wp-content/uploads/2022/07/' . str_replace( 'gif', 'jpg', $result['OtherImage4'] ) ),
					attachment_url_to_postid( 'https://hrv.test/wp-content/uploads/2022/07/' . str_replace( 'gif', 'jpg', $result['OtherImage5'] ) ),
					attachment_url_to_postid( 'https://hrv.test/wp-content/uploads/2022/07/' . str_replace( 'gif', 'jpg', $result['OtherImage6'] ) ),
					attachment_url_to_postid( 'https://hrv.test/wp-content/uploads/2022/07/' . str_replace( 'gif', 'jpg', $result['OtherImage7'] ) ),
					attachment_url_to_postid( 'https://hrv.test/wp-content/uploads/2022/07/' . str_replace( 'gif', 'jpg', $result['OtherImage8'] ) ),
					attachment_url_to_postid( 'https://hrv.test/wp-content/uploads/2022/07/' . str_replace( 'gif', 'jpg', $result['OtherImage9'] ) ),
					attachment_url_to_postid( 'https://hrv.test/wp-content/uploads/2022/07/' . str_replace( 'gif', 'jpg', $result['OtherImage10'] ) ),
					attachment_url_to_postid( 'https://hrv.test/wp-content/uploads/2022/07/' . str_replace( 'gif', 'jpg', $result['OtherImage11'] ) ),
					attachment_url_to_postid( 'https://hrv.test/wp-content/uploads/2022/07/' . str_replace( 'gif', 'jpg', $result['OtherImage12'] ) ),
					attachment_url_to_postid( 'https://hrv.test/wp-content/uploads/2022/07/' . str_replace( 'gif', 'jpg', $result['OtherImage13'] ) ),
					attachment_url_to_postid( 'https://hrv.test/wp-content/uploads/2022/07/' . str_replace( 'gif', 'jpg', $result['OtherImage14'] ) ),
					attachment_url_to_postid( 'https://hrv.test/wp-content/uploads/2022/07/' . str_replace( 'gif', 'jpg', $result['OtherImage15'] ) ),
				);

				$gallery = array_unique( $gallery );

				$featured_img_id = attachment_url_to_postid( 'https://hrv.test/wp-content/uploads/2022/07/' . str_replace( 'gif', 'jpg', $result['FrontElevationImage'] ) );

				update_field( 'content', $result['gendesc'], $post_id );
				update_field( 'outside', $result['outside'], $post_id );
				update_field( 'suitability', $result['suitability'], $post_id );
				update_field( 'dining', $result['dining'], $post_id );
				update_field( 'address', $result['Address1'] . ' ' . $result['Address2'] . ' ' . $result['Address3'], $post_id );
				update_field( 'villa_id', $result['VillaID'], $post_id );
				update_field( 'bedroom_2', $result['bedroom2'], $post_id );
				update_field( 'bedroom_3', $result['bedroom3'], $post_id );
				update_field( 'bedroom_4', $result['bedroom4'], $post_id );
				update_field( 'bedroom_5', $result['bedroom5'], $post_id );
				update_field( 'bedroom_6', $result['bedroom6'], $post_id );
				update_field( 'gallery', $gallery, $post_id );
				update_field( 'front_elevation_image', $featured_img_id, $post_id );
				update_field( 'ciirus_id', $result['cirrusvillano'], $post_id );

				set_post_thumbnail( $post_id, $featured_img_id );
			}

			echo '<pre>';
			echo $post_id;

			echo ' Telephone = ' . $result['Telephone'] . '<br>';
			echo ' Email = ' . $result['Email'] . '<br>';
			echo ' Website = ' . $result['Website'] . '<br>';
			update_field( 'contact', $result['Contact'], $post_id );
			update_field( 'bedrooms', $result['NoofBedrooms'], $post_id );
			update_field( 'check_in_instructions', $result['CheckInInstruction'], $post_id );

			echo 'villainfo = ' . $result['villainfo'] . '<br>';
			echo 'noofsleepers = ' . $result['noofsleepers'] . '<br>';
			update_field( 'sleepers', $result['noofsleepers'], $post_id );
			echo 'noofbaths = ' . $result['noofbaths'] . '<br>';
			echo 'nosmoking = ' . $result['nosmoking'] . '<br>';
			if ( $result['nosmoking'] == 'Y' ) {
				update_field( 'no_smoking', 1, $post_id );
			} else {
				update_field( 'no_smoking', 0, $post_id );
			}
			echo 'nopets = ' . $result['nopets'] . '<br>';
			if ( $result['nopets'] == 'Y' ) {
				update_field( 'no_pets', 1, $post_id );
			} else {
				update_field( 'no_pets', 0, $post_id );
			}
			echo 'nochildren = ' . $result['nochildren'] . '<br>';
			echo 'webpagelink = ' . $result['webpagelink'] . '<br>';
			echo 'entertainment = ' . $result['entertainment'] . '<br>';
			echo 'outside = ' . $result['outside'] . '<br>';
			echo 'suitability = ' . $result['suitability'] . '<br>';
			echo 'dining = ' . $result['dining'] . '<br>';
			echo 'shortdesc = ' . $result['shortdesc'] . '<br>';
			echo 'gendesc = ' . $result['gendesc'] . '<br>';
			echo 'lounge = ' . $result['lounge'] . '<br>';
			update_field( 'lounge', $result['lounge'], $post_id );
			echo 'familyroom = ' . $result['familyroom'] . '<br>';
			echo 'breakfastnook = ' . $result['breakfastnook'] . '<br>';
			update_field( 'breakfastnook', $result['breakfastnook'], $post_id );
			echo 'mastersuite = ' . $result['mastersuite'] . '<br>';
			echo 'bedroom2 = ' . $result['bedroom2'] . '<br>';
			echo 'bedroom3 = ' . $result['bedroom3'] . '<br>';
			echo 'bedroom4 = ' . $result['bedroom4'] . '<br>';
			echo 'bedroom5 = ' . $result['bedroom5'] . '<br>';
			echo 'bedroom6	 = ' . $result['bedroom6'] . '<br>';
			echo 'location	 = ' . $result['location'] . '<br>';
			echo 'lmid	 = ' . $result['lmid'] . '<br>';
			echo 'specificcheckininstructions	 = ' . $result['specificcheckininstructions'] . '<br>';
			update_field( 'specific_check_in_instructions', $result['specificcheckininstructions'], $post_id );
			echo 'alarminformation	 = ' . $result['alarminformation'] . '<br>';
			update_field( 'alarm_information', $result['alarminformation'], $post_id );
			echo 'cirrusvillano	 = ' . $result['cirrusvillano'] . '<br>';
			echo '</pre>';
			echo '<br>';
			echo '<hr>';
		}

		echo 'Import';
	}

	/**
	 * Connect to Xero
	 */
	public function hrv_xero_integration() {
		$provider = $this->xero_provider();

		if ( isset( $_GET['disconnect'] ) && $_GET['disconnect'] == '1' ) {
			delete_option( 'xero_token' );
			delete_option( 'xero_access_token' );
			delete_option( 'token_expires' );
			delete_option( 'tenant_id' );
			delete_option( 'refresh_token' );
			delete_option( 'id_token' );
		}

		if ( isset( $_GET['refresh'] ) && $_GET['refresh'] === '1' ) {
			$this->xero_refresh_token();
		}

		if ( isset( $_GET['code'] ) && $_GET['code'] && ! get_option( 'xero_token' ) ) {
			update_option( 'xero_token', $_GET['code'] );

			try {
				// Try to get an access token using the authorization code grant.
				$access_token = $provider->getAccessToken(
					'authorization_code',
					array(
						'code' => get_option( 'xero_token' ),
					)
				);

				$config      = XeroAPI\XeroPHP\Configuration::getDefaultConfiguration()->setAccessToken( (string) $access_token->getToken() );
				$identityApi = new XeroAPI\XeroPHP\Api\IdentityApi(
					new GuzzleHttp\Client(),
					$config
				);

				$result = $identityApi->getConnections();

				update_option( 'xero_access_token', $access_token->getToken() );
				update_option( 'token_expires', $access_token->getExpires() );
				update_option( 'tenant_id', $result[0]->getTenantId() );
				update_option( 'refresh_token', $access_token->getRefreshToken() );
				update_option( 'id_token', $access_token->getValues()['id_token'] );
			} catch ( \League\OAuth2\Client\Provider\Exception\IdentityProviderException $e ) {
				echo 'Callback failed';
				exit();
			}
		}
		?>
<style>
.xero-wrap button {
    cursor: pointer;
}

.xero-wrap .connected {
    display: flex;
    align-items: center;
    grid-gap: 10px;
}

.xero-wrap .connected button {
    background: red;
    border: 1px solid #000;
    border-radius: 4px;
    color: #fff;
    padding: 2px 10px;
}
</style>
<h2>Xero Integration</h2>

<div class="xero-wrap">
    <?php if ( get_option( 'xero_token' ) ) : ?>
    <div class="connected">
        <p>Connected</p>
        <a href="<?php echo admin_url( 'admin.php?page=xero-integration&disconnect=1' ); ?>" id="xeroDisconnect"
            class="xero-btn red">Disconnect</a>
    </div>
    <div class="refresh">
        <a id="xeroRefresh" href="<?php echo admin_url( 'admin.php?page=xero-integration&refresh=1' ); ?>">Refresh
            Token</a>
    </div>
    <?php
	else :
		?>
    <a href="<?php echo $this->xero_authorization_url(); ?>">Connect</a>
    <?php endif; ?>
</div>

<?php
	}

	/**
	 * Check if token expired
	 */
	public function xero_refresh_if_expired() {
		if ( time() > get_option( 'token_expires' ) ) {
			if ( get_option( 'refresh_token' ) ) {
				$provider = $this->xero_provider();

				$tenant_id = get_option( 'tenant_id' );

				$newAccessToken = $provider->getAccessToken(
					'refresh_token',
					array(
						'refresh_token' => get_option( 'refresh_token' ),
					)
				);

				update_option( 'xero_access_token', $newAccessToken->getToken() );
				update_option( 'token_expires', $newAccessToken->getExpires() );
				update_option( 'tenant_id', $tenant_id );
				update_option( 'refresh_token', $newAccessToken->getRefreshToken() );
				update_option( 'id_token', $newAccessToken->getValues()['id_token'] );
			}
		}
	}

	public function xero_refresh_token() {
		if ( get_option( 'refresh_token' ) ) {
			$provider = $this->xero_provider();

			$tenant_id = get_option( 'tenant_id' );

			$newAccessToken = $provider->getAccessToken(
				'refresh_token',
				array(
					'refresh_token' => get_option( 'refresh_token' ),
				)
			);

			  update_option( 'xero_access_token', $newAccessToken->getToken() );
			  update_option( 'token_expires', $newAccessToken->getExpires() );
			  update_option( 'tenant_id', $tenant_id );
			  update_option( 'refresh_token', $newAccessToken->getRefreshToken() );
			  update_option( 'id_token', $newAccessToken->getValues()['id_token'] );
		}
	}

	public function booking_column_sort( $query ) {
		 global $pagenow;
		if ( ! is_admin() && 'edit.php' != $pagenow && isset( $_GET['post_type'] ) && 'bookings' !== $_GET['post_type'] ) {
			return;
		}
		$meta_query             = array();
		$meta_query['relation'] = 'AND';
		$orderby                = $query->get( 'orderby' );
		$future                 = $query->get( 'future_booking' );
		$property_owner         = $query->get( 'property_owner' );

		if ( 'days_left' == $orderby ) {
			$query->set( 'meta_key', 'arrival_date' );
			$query->set( 'orderby', 'meta_value_num' );
		}

		if ( 1 == $future ) {
			$today        = date( 'Ymd' );
			$meta_query[] = array(
				'key'     => 'arrival_date',
				'value'   => $today,
				'compare' => '>=',
				'type'    => 'DATE',
			);
		}

		if ( isset( $property_owner ) && ! empty( $property_owner ) ) {
			$meta_query[] = array(
				'key'     => 'booking_property_owner',
				'value'   => $property_owner,
				'compare' => '=',
			);
		}

		$query->set( 'meta_query', $meta_query );
	}

	public function add_booking_column() {
		add_filter(
			'manage_edit-bookings_columns',
			function ( $columns ) {
				unset( $columns['date'] );
				$columns['email']        = __( 'E-mail', 'hrv_mla' );
				$columns['arrival_date'] = __( 'Arrival Date', 'hrv_mla' );
				$columns['end_date']     = __( 'Departure' );
				$columns['days_left']    = __( 'Days left', 'hrv_mla' );
				$columns['owner']        = __( 'Owner', 'hrv_mla' );
				$columns['payment']      = __( 'Payment Status', 'hrv_mla' );
				return $columns;
			}
		);

		add_filter(
			'manage_edit-bookings_sortable_columns',
			function ( $columns ) {
				$columns['days_left']    = 'days_left';
				$columns['owner']        = 'owner';
				$columns['arrival_date'] = 'arrival_date';

				return $columns;
			}
		);
	}

	public function hrv_mla_booking_days( $column_name, $post_id ) {
		if ( 'days_left' == $column_name ) {
			$today        = time();
			$arrival_date = strtotime( get_field( 'arrival_date', $post_id ) );
			$diff         = $arrival_date - $today;
			$days         = floor( $diff / ( 60 * 60 * 24 ) );
			echo intval( $days ) > 0 ? intval( $days ) : 'Done';
		}

		if ( 'arrival_date' == $column_name ) {
			echo date( 'F j, Y', strtotime( get_field( 'arrival_date', $post_id ) ) );
		}

		if ( 'end_date' == $column_name ) {
			echo date( 'F j, Y', strtotime( get_field( 'end_date', $post_id ) ) );
		}

		if ( 'email' == $column_name ) {
			echo get_field( 'email', $post_id );
		}

		if ( 'owner' == $column_name ) {
			$name = get_field( 'booking_property_owner_name', $post_id );
			if ( $name ) {
				echo $name;
			}
		}

		if ( 'payment' == $column_name ) {
			$status = get_field( 'payment_status', $post_id );
			switch ( $status ) {
				case 'full':
					$output = '<div style="background: green; color: #fff; padding: 4px; display: inline-block;">Fully Paid</div>';
					break;

				case 'deposit':
					$output = '<div style="background: orange; color: #fff; padding: 4px; display: inline-block;">Deposit Paid</div>';
					break;

				default:
					$output = '<div style="background: red; color: #fff; padding: 4px; display: inline-block;">Not Paid</div>';
					break;
			}
			echo $output;
		}
	}


	/**
	 * Stripe Options page
	 *
	 * @since    1.0.0
	 */
	public function acf_options() {
		if ( function_exists( 'acf_add_options_page' ) ) {
			acf_add_options_page(
				array(
					'page_title' => 'Stripe Settings',
					'menu_title' => 'Stripe Settings',
					'menu_slug'  => 'hrv-stripe-settings',
					'capability' => 'edit_posts',
					'redirect'   => false,
				)
			);

			acf_add_options_page(
				array(
					'page_title' => 'HRV Settings',
					'menu_title' => 'HRV Settings',
					'menu_slug'  => 'hrv-settings',
					'capability' => 'edit_posts',
					'redirect'   => false,
				)
			);

			acf_add_options_page(
				array(
					'page_title'  => __( 'Xero Keys' ),
					'menu_title'  => __( 'Xero Keys' ),
					'parent_slug' => 'xero-integration',
				)
			);
		}

		if ( function_exists( 'acf_add_local_field_group' ) ) :

			acf_add_local_field_group(
				array(
					'key'                   => 'group_609ba1ac82596',
					'title'                 => 'Stripe Keys',
					'fields'                => array(
						array(
							'key'               => 'field_609ba4bb353f8',
							'label'             => 'Testing?',
							'name'              => 'testing',
							'type'              => 'true_false',
							'instructions'      => '',
							'required'          => 0,
							'conditional_logic' => 0,
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'message'           => '',
							'default_value'     => 0,
							'ui'                => 0,
							'ui_on_text'        => '',
							'ui_off_text'       => '',
						),
						array(
							'key'               => 'field_609ba39e47f38',
							'label'             => 'Test Publishable Key',
							'name'              => 'test_publishable_key',
							'type'              => 'text',
							'instructions'      => '',
							'required'          => 0,
							'conditional_logic' => 0,
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'default_value'     => '',
							'placeholder'       => '',
							'prepend'           => '',
							'append'            => '',
							'maxlength'         => '',
						),
						array(
							'key'               => 'field_609ba3d547f39',
							'label'             => 'Test Secret Key',
							'name'              => 'test_secret_key',
							'type'              => 'text',
							'instructions'      => '',
							'required'          => 0,
							'conditional_logic' => 0,
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'default_value'     => '',
							'placeholder'       => '',
							'prepend'           => '',
							'append'            => '',
							'maxlength'         => '',
						),
						array(
							'key'               => 'field_609ba3da47f3a',
							'label'             => 'Live Publishable Key',
							'name'              => 'live_publishable_key',
							'type'              => 'text',
							'instructions'      => '',
							'required'          => 0,
							'conditional_logic' => 0,
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'default_value'     => '',
							'placeholder'       => '',
							'prepend'           => '',
							'append'            => '',
							'maxlength'         => '',
						),
						array(
							'key'               => 'field_609ba3e147f3b',
							'label'             => 'Live Secret Key',
							'name'              => 'live_secret_key',
							'type'              => 'text',
							'instructions'      => '',
							'required'          => 0,
							'conditional_logic' => 0,
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'default_value'     => '',
							'placeholder'       => '',
							'prepend'           => '',
							'append'            => '',
							'maxlength'         => '',
						),
					),
					'location'              => array(
						array(
							array(
								'param'    => 'options_page',
								'operator' => '==',
								'value'    => 'hrv-stripe-settings',
							),
						),
					),
					'menu_order'            => 0,
					'position'              => 'normal',
					'style'                 => 'default',
					'label_placement'       => 'top',
					'instruction_placement' => 'label',
					'hide_on_screen'        => '',
					'active'                => true,
					'description'           => '',
				)
			);
		endif;
	}

	/**
	 * Send Email Function
	 */
	public function send_email( $to, $subject, $content ) {
		 $header = file_get_contents( plugin_dir_url( __FILE__ ) . 'partials/email-header.php' );
		$footer  = file_get_contents( plugin_dir_url( __FILE__ ) . 'partials/email-footer.php' );

		$email_content = $header . $content . $footer;

		$headers = array( 'Content-Type: text/html; charset=UTF-8' );

		wp_mail( $to, $subject, $email_content, $headers );
	}

	/**
	 * Send Email Deposit
	 */
	public function send_hrv_email( $to, $subject, $content ) {
		 $headers  = array( 'Content-Type: text/html; charset=UTF-8' );
		$headers[] = 'From: HRV Booking <admin@highlandsreservevillas.com>';

		if ( get_field( 'testing', 'option' ) ) {
			$to = 'bonnbonito@gmail.com';
		}

		wp_mail( $to, $subject, $content, $headers );
	}

	/**
	 * Add contact to xero
	 *
	 * @since    1.0.0
	 * @param      string $token      Xero access token.
	 * @param      array  $args       User array.
	 */

	public function xero_contact( $user ) {
		 $this->xero_refresh_if_expired();

		$config = XeroAPI\XeroPHP\Configuration::getDefaultConfiguration()->setAccessToken( get_option( 'xero_access_token' ) );

		$apiInstance = new XeroAPI\XeroPHP\Api\AccountingApi(
			new GuzzleHttp\Client(),
			$config
		);

		$xeroTenantId    = get_option( 'tenant_id' );
		$summarizeErrors = true;

		$exists = $apiInstance->getContacts( $xeroTenantId, '', 'EmailAddress=="' . $user['email'] . '"' );

		// echo count( $exists->getContacts() );

		// print_r( $exists->getContacts()[0]->getContactId() );

		if ( count( $exists->getContacts() ) == 0 ) {
			$phone = new XeroAPI\XeroPHP\Models\Accounting\Phone();
			$phone->setPhoneNumber( $user['phone'] );
			$phone->setPhoneType( XeroAPI\XeroPHP\Models\Accounting\Phone::PHONE_TYPE_MOBILE );
			$phones = array();
			array_push( $phones, $phone );

			$contact = new XeroAPI\XeroPHP\Models\Accounting\Contact();
			$contact->setName( $user['name'] );
			$contact->setEmailAddress( $user['email'] );
			$contact->setPhones( $phones );

			$contacts     = new XeroAPI\XeroPHP\Models\Accounting\Contacts();
			$arr_contacts = array();
			array_push( $arr_contacts, $contact );
			$contacts->setContacts( $arr_contacts );

			$created        = $apiInstance->createContacts( $xeroTenantId, $contacts, $summarizeErrors );
			$result_contact = $created->getContacts()[0]->getContactId();
		} else {
			$result_contact = $exists->getContacts()[0]->getContactId();
		}

		return $result_contact;
	}

	public function add_xero_invoice( $contact_id, $booking ) {
		 $this->xero_refresh_if_expired();

		$config = XeroAPI\XeroPHP\Configuration::getDefaultConfiguration()->setAccessToken( get_option( 'xero_access_token' ) );

		$apiInstance = new XeroAPI\XeroPHP\Api\AccountingApi(
			new GuzzleHttp\Client(),
			$config
		);

		$xeroTenantId    = get_option( 'tenant_id' );
		$summarizeErrors = true;
		$unitdp          = 4;
		$dateValue       = new DateTime();
		$dueDateValue    = new DateTime();

		$contact = new XeroAPI\XeroPHP\Models\Accounting\Contact();
		$contact->setContactID( $contact_id );

		$reference   = 'HRV Booking ID - ' . $booking['booking_id'];
		$description = $booking['property'] . ' booking: ' . date( 'F j, Y', strtotime( $booking['start_date'] ) ) . ' - ' . date( 'F j, Y', strtotime( $booking['end_date'] ) );

		$lineItem = new XeroAPI\XeroPHP\Models\Accounting\LineItem();
		$lineItem->setDescription( $description );
		$lineItem->setQuantity( 1.0 );
		$lineItem->setUnitAmount( $booking['total_price'] );
		$lineItem->setAccountCode( '000' );
		$lineItem->setTracking( $lineItemTrackings );
		$lineItems = array();
		array_push( $lineItems, $lineItem );

		$invoice = new XeroAPI\XeroPHP\Models\Accounting\Invoice();
		$invoice->setType( XeroAPI\XeroPHP\Models\Accounting\Invoice::TYPE_ACCREC );
		$invoice->setContact( $contact );
		$invoice->setDate( $dateValue );
		$invoice->setDueDate( $dueDateValue );
		$invoice->setLineItems( $lineItems );
		$invoice->setReference( $reference );
		$invoice->setStatus( XeroAPI\XeroPHP\Models\Accounting\Invoice::STATUS_DRAFT );

		$invoices     = new XeroAPI\XeroPHP\Models\Accounting\Invoices();
		$arr_invoices = array();
		array_push( $arr_invoices, $invoice );
		$invoices->setInvoices( $arr_invoices );

		$result = $apiInstance->createInvoices( $xeroTenantId, $invoices, $summarizeErrors, $unitdp );

		return $result;
	}

	public function ciirus_is_property_available( $id, $arrive, $departure ) {
		$client = new SoapClient(
			null,
			array(
				'location' => $this->ciirus_url,
				'uri'      => 'http://xml.ciirus.com/',
				'trace'    => 1,
			)
		);
		$params = array(
			new SoapParam( $this->ciirus_user, 'ns1:APIUsername' ),
			new SoapParam( $this->ciirus_password, 'ns1:APIPassword' ),
			new SoapParam( intval( $id ), 'ns1:PropertyID' ),
			new SoapParam( $arrive, 'ns1:ArrivalDate' ),
			new SoapParam( $departure, 'ns1:DepartureDate' ),
		);

		try {
			$return = $client->__soapCall(
				'IsPropertyAvailable',
				$params,
				array(
					'soapaction'   => 'http://xml.ciirus.com/IsPropertyAvailable',
					'soap_version' => SOAP_1_1,
				)
			);

			if ( $return == 'true' ) {
				$status = 'available';
			}

			if ( $return == 'false' ) {
				$status = 'not available';
			}
		} catch ( SoapFault $fault ) {
			if ( strpos( $fault, 'The minimum night stay for this property could not be determined' ) > 0 ) {
				$status = ' minimum stay';
			} elseif ( strpos( $fault, 'The credentials could not be authenticated' ) != false ) {
				$status = 'not ok';
			} else {
				$status = 'ok ' . $fault;
			}
		}
		return $status;
	}

	public function ciirus_get_property_rates( $id, $checkin, $nights ) {
		$curl = curl_init();

		curl_setopt_array(
			$curl,
			array(
				CURLOPT_URL            => 'http://api.ciirus.com/CiirusXML.15.025.asmx/GetPropertyRates?APIUserName=' . $this->ciirus_user . '&APIPassword=' . $this->ciirus_password . '&PropertyID=' . $id,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING       => '',
				CURLOPT_MAXREDIRS      => 10,
				CURLOPT_TIMEOUT        => 30,
				CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST  => 'GET',
			)
		);

		$response = curl_exec( $curl );
		$err      = curl_error( $curl );

		curl_close( $curl );

		if ( $err ) {
			return 'cURL Error #:' . $err;
		} else {
			libxml_use_internal_errors( true );
			$xml_result     = simplexml_load_string( $response );
			$json_encode    = wp_json_encode( $xml_result );
			$arr_output     = json_decode( $json_encode, true );
			$rates          = $arr_output['Rate'];
			$property_rates = array();
			$total_rates    = 0;
			$checkin_date   = date( 'Y-m-d', strtotime( $checkin ) );

			if ( is_array( $rates ) || is_object( $rates ) ) {
				$chDate = date( 'Y-m-d', strtotime( $checkin_date ) );

				for ( $j = 0; $j < count( $rates ); $j++ ) {
					$from = date( 'Y-m-d', strtotime( $rates[ $j ]['FromDate'] ) );
					$to   = date( 'Y-m-d', strtotime( $rates[ $j ]['ToDate'] ) );

					if ( ( $chDate >= $from ) && ( $chDate <= $to ) ) {
						$property_rates['rates'][] = array(
							'date' => $chDate,
							'rate' => $rates[ $j ]['DailyRate'],
						);
						$total_rates               = $total_rates + $rates[ $j ]['DailyRate'];
						break;
					}
				}
				$property_rates['per_day'] = $total_rates;
				$property_rates['total_rates'] = round( $total_rates * $nights, 0 );
			} else {
				$property_rates = false;
			}

			return $property_rates;
		}
	}

	public function ciirus_get_tax_rates( $id ) {
		$curl = curl_init();

		curl_setopt_array(
			$curl,
			array(
				CURLOPT_URL            => 'http://api.ciirus.com/XMLAdditionalFunctions15.025.asmx/GetTaxRates?APIUserName=' . $this->ciirus_user . '&APIPassword=' . $this->ciirus_password . '&PropertyID=' . $id,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING       => '',
				CURLOPT_MAXREDIRS      => 10,
				CURLOPT_TIMEOUT        => 30,
				CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST  => 'GET',
			)
		);

		$response = curl_exec( $curl );
		$err      = curl_error( $curl );

		curl_close( $curl );

		if ( $err ) {
			return 'cURL Error #:' . $err;
		} else {
			libxml_use_internal_errors( true );
			$xml_result = simplexml_load_string( $response );
			if ( $xml_result ) {
				$json_encode               = wp_json_encode( $xml_result );
				$arr_output                = json_decode( $json_encode, true );
				$total_taxrates            = $arr_output['Tax1Percent'] + $arr_output['Tax2Percent'] + $arr_output['Tax3Percent'];
				$arr_output['total_rates'] = $total_taxrates;
				return $arr_output;
			} else {
				return false;
			}
		}
	}

	public function ciirus_extra_fees( $id ) {
		$curl = curl_init();

		curl_setopt_array(
			$curl,
			array(
				CURLOPT_URL            => 'https://api.ciirus.com/XMLAdditionalFunctions15.025.asmx',
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING       => '',
				CURLOPT_MAXREDIRS      => 10,
				CURLOPT_TIMEOUT        => 0,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST  => 'POST',
				CURLOPT_POSTFIELDS     => '<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema"
    xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
    <soap:Body>
        <GetExtras xmlns="http://xml.ciirus.com/">
            <APIUserName>' . $this->ciirus_user . '</APIUserName>
            <APIPassword>' . $this->ciirus_password . '</APIPassword>
            <PropertyID>' . $id . '</PropertyID>
        </GetExtras>
    </soap:Body>
</soap:Envelope>',
CURLOPT_HTTPHEADER => array(
'SOAPAction: http://xml.ciirus.com/GetExtras',
'Content-Type: text/xml; charset=utf-8',
),
)
);

$response = curl_exec( $curl );

curl_close( $curl );

$response = preg_replace( '/(<\ /?)(\w+):([^>]*>)/', '$1$2$3', $response );
    $response = preg_replace( '/xmlns[^=]*="[^"]*"/', '', $response );

    $xml = simplexml_load_string( $response );
    $json = json_encode( $xml );
    $array = json_decode( $json, true );

    $mandatoryItems = array();

    $items = $array['soapBody']['GetExtrasResponse']['GetExtrasResult']['Extras']['PropertyExtras'];

    foreach ( $items as $item ) {
    if ( $item['Mandatory'] == 'true' ) {
    if ( $item['PercentageFee'] == 'true' ) {
    $mandatoryItems[] = array(
    'type' => 'percentage',
    'value' => $item['Percentage'],
    );
    }
    if ( $item['FlatFee'] == 'true' ) {
    $mandatoryItems[] = array(
    'type' => 'flat',
    'value' => $item['FlatFeeAmount'],
    );
    }

    if ( $item['DailyFee'] == 'true' ) {
    $mandatoryItems[] = array(
    'type' => 'daily',
    'value' => $item['DailyFeeAmount'],
    );
    }
    }
    }

    return $mandatoryItems;

    }

    public function ciirus_get_cleaning_fee( $id, $nights ) {
    $curl = curl_init();

    curl_setopt_array(
    $curl,
    array(
    CURLOPT_URL => 'http://api.ciirus.com/XMLAdditionalFunctions15.025.asmx/GetCleaningFee?APIUserName=' .
    $this->ciirus_user . '&APIPassword=' . $this->ciirus_password . '&PropertyID=' . $id,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'GET',
    )
    );

    $response = curl_exec( $curl );
    $err = curl_error( $curl );

    curl_close( $curl );

    if ( $err ) {
    return 'cURL Error #:' . $err;
    } else {
    libxml_use_internal_errors( true );

    $xml_result = simplexml_load_string( $response );

    if ( $xml_result ) {
    $json_encode = wp_json_encode( $xml_result );
    $arr_output = json_decode( $json_encode, true );

    if ( $arr_output['ChargeCleaningFee'] == 'true' && $arr_output['OnlyChargeCleaningFeeWhenLessThanDays'] > $nights )
    {
    return round( $arr_output['CleaningFeeAmount'], 0 );
    } else {
    return 0;
    }
    } else {
    return 0;
    }
    }
    }


    public function ciirus_calculated_booking_price( $id, $checkin, $nights ) {
    $additional = get_field( 'additional_pricing', 'option' );
    $commission_percent = $this->getPercentage( $additional, 'commission_percent' );
    $default_commission_percent = $this->getPercentage( $additional, 'default_commission_percent' );
    $minimum_price = $additional['minimum_price'];

    $api_price = $this->getPropertyRates( $id, $checkin, $nights );
    $total_api_price = $api_price['total'];
    $cleaning = $this->ciirus_get_cleaning_fee( $id, $nights );
    $tax = $this->getTaxRates( $id );
    $extras = $this->getExtras( $id, $total_api_price );

    $tax_price = $this->calculateTax( $total_api_price, $tax );
    $cleaning_tax_price = $this->calculateTax( $cleaning, $tax );

    $price = $this->calculatePrices( $total_api_price, $cleaning, $tax_price, $cleaning_tax_price, $extras );

    $total_price = $price['total'];

    if ( $total_price > $minimum_price ) {
    $commission_percent = $commission_percent;
    } else {
    $commission_percent = $default_commission_percent;
    }

    $additional_price = $this->calculateCommission( $total_price, $commission_percent );
    $total_price = $total_price + $additional_price;

    $price['per_day'] = $api_price['per_day'];
    $price['old_total'] = $price['total'];
    $price['additional'] = $additional_price;
    $price['total'] = $total_price;


    return $price;
    }

    private function getPercentage( $additional, $key ) {
    return $additional[ $key ] ? $additional[ $key ] : 0;
    }

    private function getPropertyRates( $id, $checkin, $nights ) {
    $api_get_price = $this->ciirus_get_property_rates( $id, $checkin, $nights );
    $total = $api_get_price['total_rates'] ? $api_get_price['total_rates'] : 0;
    $per_day = $api_get_price['per_day'] ? $api_get_price['per_day'] : 0;
    return array(
    'total' => $total,
    'per_day' => $per_day,
    );
    }

    private function getTaxRates( $id ) {
    $propertyTaxRatesApi = $this->ciirus_get_tax_rates( $id );
    return $propertyTaxRatesApi['total_rates'] ? $propertyTaxRatesApi['total_rates'] : 0;
    }

    private function getExtras( $id, $api_price ) {
    $get_extras_function = show_extras( $id );
    $get_extras = $get_extras_function ? $get_extras_function : array();
    $extras = 0;

    foreach ( $get_extras as $e ) {
    if ( $e['type'] === 'percentage' ) {
    $extras += $api_price * ( $e['value'] / 100 );
    } else {
    $extras += $e['value'];
    }
    }

    return round( $extras, 0 );
    }

    private function calculateTax( $price, $tax ) {
    return round( ( $tax / 100 ) * $price, 0 );
    }

    private function calculatePrices( $api_price, $cleaning, $tax_price, $cleaning_tax_price, $extras ) {
    $booking_price = round( $api_price, 0 );
    $cleaning_price = round( $cleaning, 0 );
    $total_price = $booking_price + $cleaning_price + $tax_price + $cleaning_tax_price + $extras;

    return array(
    'booking_price' => $booking_price,
    'cleaning_price' => $cleaning_price,
    'tax_price' => $tax_price,
    'cleaning_tax' => $cleaning_tax_price,
    'extras' => $extras,
    'total' => $total_price,
    );
    }

    private function calculateCommission( $total_price, $commission_percent ) {
    $additional_price = ( $commission_percent / 100 ) * $total_price;
    return round( $additional_price, 0 );
    }

    public function ciirus_make_booking( $booking_details ) {
    $curl = curl_init();

    curl_setopt_array(
    $curl,
    array(
    CURLOPT_URL => 'https://api.ciirus.com/CiirusXML.15.025.asmx',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => $booking_details,
    CURLOPT_HTTPHEADER => array(
    'Content-Type: text/xml; charset=utf-8',
    'SOAPAction: http://xml.ciirus.com/MakeBooking',
    ),
    )
    );

    $response = curl_exec( $curl );
    $err = curl_error( $curl );

    if ( $err ) {
    return 'cURL Error #:' . $err;
    } else {
    // $response = preg_replace( '/(<\ /?)(\w+):([^>]*>)/', '$1$2$3', $response );
        $xml = new SimpleXMLElement( $response );
        $body = $xml->xpath( '//soapBody ' )[0];
        $json_encode = wp_json_encode( $body );
        $arr_output = json_decode( $json_encode, true );
        $result = $arr_output['MakeBookingResponse']['MakeBookingResult'];

        if ( 'true' == $result['BookingPlaced'] ) {
        return array(
        'BookingPlaced' => $result['BookingPlaced'],
        'BookingID' => $result['BookingID'],
        'TotalAmountIncludingTax' => $result['TotalAmountIncludingTax'],
        );
        } else {
        return array(
        'BookingPlaced' => $result['BookingPlaced'],
        'ErrorMessage' => $result['ErrorMessage'],
        );
        }
        }
        }

        public function ciirus_test_booking() {
        $curl = curl_init();

        curl_setopt_array(
        $curl,
        array(
        CURLOPT_URL => 'http://api.ciirus.com/CiirusXML.15.025.asmx',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => '
        <?xml version="1.0" encoding="utf-8"?>
        <soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
            xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
            <soap:Body>
                <MakeBooking xmlns="http://xml.ciirus.com/">
                    <APIUsername>74db9a060ce9426</APIUsername>
                    <APIPassword>4e1276922b63493</APIPassword>
                    <BD>
                        <ArrivalDate>22 Sep 2024</ArrivalDate>
                        <DepartureDate>30 Sep 2024</DepartureDate>
                        <PropertyID>227504</PropertyID>
                        <GuestName>John</GuestName>
                        <GuestEmailAddress>jcprangue@gmail.com</GuestEmailAddress>
                        <GuestTelephone>1234567</GuestTelephone>
                        <GuestAddress>San Jose</GuestAddress>
                        <GuestList>
                            <sGuests>
                                <Name>Dominic Ace</Name>
                                <Age>-1</Age>
                            </sGuests>
                        </GuestList>
                    </BD>
                </MakeBooking>
            </soap:Body>
        </soap:Envelope>',
        CURLOPT_HTTPHEADER => array(
        'Content-Type: text/xml; charset=utf-8',
        'SOAPAction: http://xml.ciirus.com/MakeBooking',
        ),
        )
        );

        $response = curl_exec( $curl );
        $err = curl_error( $curl );

        if ( $err ) {
        return 'cURL Error #:' . $err;
        } else {
        $response = preg_replace( '/(<\ /?)(\w+):([^>]*>)/', '$1$2$3', $response );
            $xml = new SimpleXMLElement( $response );
            $body = $xml->xpath( '//soapBody ' )[0];
            $json_encode = wp_json_encode( $body );
            $arr_output = json_decode( $json_encode, true );
            $result = $arr_output['MakeBookingResponse']['MakeBookingResult'];

            if ( 'true' == $result['BookingPlaced'] ) {
            return array(
            'BookingPlaced' => $result['BookingPlaced'],
            'BookingID' => $result['BookingID'],
            'TotalAmountIncludingTax' => $result['TotalAmountIncludingTax'],
            );
            } else {
            return array(
            'BookingPlaced' => $result['BookingPlaced'],
            'ErrorMessage' => $result['ErrorMessage'],
            );
            }
            }
            }

            public function get_season_total_price() { }

            public function golf_booking_email_content() {
            ob_start();
            ?>

            <!DOCTYPE html
                PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "https://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
            <html xmlns="https://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml"
                xmlns:o="urn:schemas-microsoft-com:office:office">

            <head>
                <title>Golf Agents TeeTime Booking</title>
                <meta http–equiv="Content-Type" content="text/html; charset=utf-8">
                <meta http–equiv="X-UA-Compatible" content="IE=edge">
                <meta name="viewport" content="width=device-width, initial-scale=1.0 ">
                <meta name="format-detection" content="telephone=no">
            </head>

            <body>
                <table border="0" width="90%">
                    <tbody>

                        <tr>
                            <td align="center" colspan="5">
                                <font color="#215272" size="7" face="verdana">Golf Agents TeeTime Booking</font>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="5">&nbsp;</td>
                        </tr>
                        <tr>
                            <td align="center" colspan="5">
                                <font face="verdana" color="00,33,66" size="2px"><b>Barrie &amp; Jane Pike, 69 Nant
                                        Talwg
                                        Way,
                                        Barry, South Glamorgan, CF62 6LZ, Wales, UK.<br>
                                        Tel/Fax 01446 407557 (Intl. +44 1446 407557) Email : <a
                                            href="mailto:barrie.pike@gmail.com" target="_blank">
                                            barrie.pike@gmail.com</a> <br>
                                    </b></font>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <table border="0" width="90%">
                    <tbody>
                        <tr>
                            <td colspan="5">&nbsp; </td>
                        </tr>
                        <tr>
                            <td colspan="5">
                                <font size="4">Please make the following reservations and confirm by email.
                                </font>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="5">&nbsp; </td>
                        </tr>
                        <tr>
                            <td colspan="5"><b>Guest Name : </b>&nbsp; <b>GUEST_EMAIL</b></td>
                        </tr>
                    </tbody>
                </table>
                <table border="0" width="90%">
                    <tbody>
                        <tr>
                            <td colspan="5">
                                <hr size="1">
                            </td>
                        </tr>
                        <tr>
                            <th valign="top" align="center"><b>Date</b></th>
                            <th valign="top" align="center"><b>Course Name</b></th>
                            <th valign="top" align="center"><b>Preferred Time </b></th>
                            <th valign="top" align="center"><b>Holes </b></th>
                            <th valign="top" align="center"><b>Golfers</b> </th>
                        </tr>
                        <tr>
                            <td colspan="5">
                                <hr size="1">
                            </td>
                        </tr>
                        <!-- START -->
                        [GOLF_BOOKING_DETAILS]
                        <!-- END -->


                        <tr>
                            <td colspan="5">
                                <hr size="1">
                            </td>
                        </tr>
                    </tbody>
                </table>
            </body>

            </html>



            <?php
			return ob_get_clean();
	}


	/**
	 * Get deposit email content
	 */
	public function fully_paid_email_content() {
		ob_start();
		require 'emails/paid-template.html';
		$content = ob_get_clean();
		return $content;
	}

	public function request_payment_email_content() {
		ob_start();
		require 'emails/template.html';
		$content = ob_get_clean();
		return $content;
	}

	public function request_golf_email_content() {
		ob_start();
		require 'emails/golf-template.html';
		$content = ob_get_clean();
		return $content;
	}


	public function send_ask_payment_email_schedule() {
		if ( ! wp_next_scheduled( 'send_ask_payment_email_hook' ) ) {
			wp_schedule_event( time(), 'daily', 'send_ask_payment_email_hook' );
		}
	}

	public function send_ask_review_schedule() {
		if ( ! wp_next_scheduled( 'send_ask_review_hook' ) ) {
			wp_schedule_event( time(), 'daily', 'send_ask_review_hook' );
		}
	}

	public function capture_deposit_stripe_schedule() {
		if ( ! wp_next_scheduled( 'capture_deposit_stripe_hook' ) ) {
			wp_schedule_event( time(), 'daily', 'capture_deposit_stripe_hook' );
		}
	}

	public function send_ask_payment_email_function() {
		$query = new WP_Query(
			array(
				'post_type'      => 'bookings',
				'posts_per_page' => -1,
			)
		);

		while ( $query->have_posts() ) :
			$query->the_post();

			$arrival_date  = strtotime( get_field( 'arrival_date' ) );
			$today         = time();
			$diff          = $arrival_date - $today;
			$days          = floor( $diff / ( 60 * 60 * 24 ) );
			$total_price   = get_field( 'total_price' );
			$deposit_price = $total_price - ( $total_price * 0.10 );
			$deposit_price = $deposit_price > $this->deposit ? $this->deposit : $deposit_price;

			if ( $days <= $this->days_to_notify && 'full' !== get_field('payment_status', get_the_ID()) ) {
				if ( get_post_meta( get_the_ID(), 'reminder_email_sent', true ) != 'yes' ) {
					$this->send_reminder_email_process( get_the_ID() );
				}
			}

		endwhile;
		wp_reset_postdata();
	}

	public function send_reminder_email_process( $post_id, $to_email ) {

		global $post;
		$post = get_post( $post_id );

		setup_postdata($post);

		$arrival_date  = strtotime( get_field( 'arrival_date' ) );
		$property_id = get_field('property_post')[0];
		$today         = time();
		$diff          = $arrival_date - $today;

		$total_price   = get_field( 'total_price' );


		$email_to_send = $this->get_request_payment_content();
		if ( get_field('api_price') ) {
			$email_to_send = str_replace( '[BOOKING_DETAILS]', $this->booking_details_content_api(), $email_to_send );
			$email_to_send = str_replace( 'TOTAL_ROOM_RATE',  number_format(get_field('total_ciirus_price_with_comission'), 2), $email_to_send );
			$total_price   = get_field( 'total_price' );

		} else {
			$email_to_send = str_replace( '[BOOKING_DETAILS]', $this->booking_details_content(), $email_to_send );
			$email_to_send = str_replace( 'HOME_RENTAL_PRICE',  number_format( get_field('booking_season_price'), 2), $email_to_send  );
			$email_to_send = str_replace( 'TOTAL_PRICE',  number_format( get_field('total_price'), 2), $email_to_send );
		}

		$golf_bookings = '';

		if ( have_rows( 'golf_booking' ) ) {
			$golf_bookings .= '<div style="overflow-x:auto;" class="golf-table"><table style="border: 1px solid #000; border-collapse: collapse;" class="golf"><thead>
						<tr>
						<th valign="top" align="center" style="border: 1px solid #000; border-collapse: collapse; padding: 5px;">Date</th>
						<th valign="top" align="center" style="border: 1px solid #000; border-collapse: collapse; padding: 5px;">Golf Course</th>
						<th valign="top" align="center" style="border: 1px solid #000; border-collapse: collapse; padding: 5px;">Preferred Time</th>
						<th valign="top" align="center" style="border: 1px solid #000; border-collapse: collapse; padding: 5px;">Number of Holes</th>
						<th valign="top" align="center" style="border: 1px solid #000; border-collapse: collapse; padding: 5px;">Number of Players</th>
						</tr>
					</thead><tbody>';
			 while ( have_rows( 'golf_booking' ) ) {
				 the_row();
				 $golf_courses = get_sub_field( 'golf_course' );

				 $golf_bookings .= '<tr>
				<td valign="middle" align="center" style="border: 1px solid #000; border-collapse: collapse; padding: 5px;">' . get_sub_field( 'date' ) . '</td>
				<td valign="middle" align="center" style="border: 1px solid #000; border-collapse: collapse; padding: 5px;">' . get_the_title( $golf_courses ) . '</td>
				<td valign="middle" align="center" style="border: 1px solid #000; border-collapse: collapse; padding: 5px;">' . get_sub_field( 'preferred_time' ) . '</td>
				<td valign="middle" align="center" style="border: 1px solid #000; border-collapse: collapse; padding: 5px;">' . get_sub_field( 'number_of_rounds' ) . ' Holes</td>
				<td valign="middle" align="center" style="border: 1px solid #000; border-collapse: collapse; padding: 5px;">' . get_sub_field( 'number_of_players' ) . ' Golfers</td>
				</tr>';
			 }

			 $golf_bookings .= '</tbody></table></div>';

			 $email_to_send = str_replace( '[GOLF_BOOKING_DETAILS]', $golf_bookings, $email_to_send );

		 } else {
			$email_to_send = str_replace( '[GOLF_BOOKING_DETAILS]', '', $email_to_send );
		 }

		$total_golf_booking = get_field('total_golf_booking_price');

		if ( $total_golf_booking ) {
			$golf_content = '<tr class="item">
                    <td>Golf Fees</td>
                    <td>$'.number_format( $total_golf_booking, 2).'</td>
                </tr>';
			$email_to_send = str_replace('GOLF_FEES', $golf_content, $email_to_send);
		} else {
			$email_to_send = str_replace('GOLF_FEES', '', $email_to_send);
		}



		$other_addons = '';

		if ( have_rows( 'extra_cost' ) ) :
			while ( have_rows( 'extra_cost' ) ) :
				the_row();
				$other_addons .= '<tr class="item">
			<td>' . get_sub_field( 'extra_cost' ) . '</td>
			<td>$' . (get_sub_field( 'price' ) ? number_format( get_sub_field( 'price' ), 2) : '0.00') . '</td>
			</tr>';
			endwhile;
		endif;

		$email_subject = $this->get_reminder_email_subject() ? $this->get_reminder_email_subject() : 'Thanks for enquiring';
		$email_subject = str_replace( '[REF_#]', 'HRV-' . get_the_ID(), $email_subject );
		$email_subject = str_replace( '[GUEST_NAME]', get_field( 'first_name' ) . ' ' . get_field( 'surname' ), $email_subject );

		$email_to_send = str_replace( 'BOOKING_ID', 'HRV-' . get_the_ID(), $email_to_send );
		$email_to_send = str_replace( 'NO_ADULTS', get_field( 'adult' ), $email_to_send );
		$email_to_send = str_replace( 'NO_NIGHTS', get_field( 'no_of_nights' ), $email_to_send );
		$email_to_send = str_replace( 'NO_CHILDREN', get_field( 'children' ), $email_to_send );
		$email_to_send = str_replace( '[GUEST_NAME]', get_field( 'first_name' ) . ' ' . get_field( 'surname' ), $email_to_send );
		$email_to_send = str_replace( 'DEPARTURE_DATE', date( 'd/M/Y', strtotime( get_field( 'end_date' ) ) ), $email_to_send );
		$email_to_send = str_replace( 'ARRIVAL_DATE', date( 'd/M/Y', strtotime( get_field( 'arrival_date' ) ) ), $email_to_send );
		$email_to_send = str_replace( 'PROPERTY_NAME', get_field( 'property' ), $email_to_send );
		$email_to_send = str_replace( 'PROPERTY_ADDRESS', get_field( 'booking_property_address' ), $email_to_send );
		$email_to_send = str_replace( 'TOTAL_PRICE',  number_format($total_price, 2), $email_to_send );
		$email_to_send = str_replace( 'RENT_PRICE',  number_format(get_field( 'booking_season_price' ), 2), $email_to_send );
		$email_to_send = str_replace( 'TOTAL_BALANCE',  number_format(get_field('balance'), 2), $email_to_send );
		$email_to_send = str_replace( 'OTHER_ADDONS', $other_addons, $email_to_send );
		$email_to_send = str_replace( 'DUE_DATE', get_field( 'due_date' ), $email_to_send );
		$email_to_send = str_replace( 'INVOICE_DATE', get_the_date( 'd/M/Y' ), $email_to_send );
		$email_to_send = str_replace( 'TOTAL_DEPOSIT',  number_format( get_field( 'total_amount_paid' ), 2), $email_to_send );
		$email_to_send = str_replace( 'AMOUNT_PAID',  number_format( get_field( 'balance' ), 2), $email_to_send );
		$email = $to_email ? $to_email : get_field( 'email' );

		$this->send_hrv_email( $email, $email_subject, $email_to_send );


		wp_reset_postdata();

		update_post_meta( get_the_ID(), 'reminder_email_sent', 'yes' );

		return array(
			'email' => $email
		);

	}

	public function capture_deposit_stripe_function() {
		$days_ago = date('Y-m-d', strtotime('-4 days'));
		$year = date('Y', strtotime($days_ago));
		$month = date('m', strtotime($days_ago));
		$day = date('d', strtotime($days_ago));
		$query = new WP_Query(
			array(
				'post_type'      => 'bookings',
				'posts_per_page' => -1,
				'date_query'     => array(
					array(
						'year' => $year,
						'month' => $month,
						'day'	=> $day,
					),
				),
			)
		);

		while ( $query->have_posts() ) :
			$query->the_post();

			if ( get_field('stripe_payment_intent') && ! get_field('stripe_payment_captured') ) {
				$secret = get_field( 'testing', 'option' ) ? get_field( 'test_secret_key', 'option' ) : get_field( 'live_secret_key', 'option' );
				$stripe = new \Stripe\StripeClient( $secret );


				$capture = $stripe->paymentIntents->capture(get_field('stripe_payment_intent'), array());

				if ( $capture->status === 'succeeded' ) {
					update_field( 'stripe_charge_id', $capture->latest_charge, get_the_ID() );
					update_field('stripe_payment_captured', 1, get_the_ID());
				}
			}

		endwhile;
		wp_reset_postdata();
	}

	public function acf_diable_field( $field ) {
		$field['readonly'] = true;
		return $field;
	}

	public function send_ask_review_email_function() {
		$query = new WP_Query(
			array(
				'post_type'      => 'bookings',
				'posts_per_page' => -1,
			)
		);

		while ( $query->have_posts() ) :
			$query->the_post();

			$arrival_date  = strtotime( get_field( 'arrival_date' ) );
			$today         = time();
			$diff          = $arrival_date - $today;
			$days          = floor( $diff / ( 60 * 60 * 24 ) );
			$total_price   = get_field( 'total_price' );
			$deposit_price = $total_price - ( $total_price * 0.10 );
			$deposit_price = $deposit_price > $this->deposit ? $this->deposit : $deposit_price;

			if ( $days <= $this->days_to_notify ) {
				if ( get_post_meta( get_the_ID(), 'reminder_email_sent', true ) != 'yes' ) {
					$request_payment_email_content = $this->request_payment_email_content();

					$other_addons = '';

					if ( have_rows( 'extra_cost' ) ) :
						while ( have_rows( 'extra_cost' ) ) :
							the_row();
							$other_addons .= '<tr>
						<td>' . get_sub_field( 'extra_cost' ) . '</td>
						<td colspan="2" align="right">$' . (get_sub_field( 'price' ) ? number_format(get_sub_field( 'price' ),2) : '0.00') . '</td>
						</tr>';
						endwhile;
					endif;

					$total_golf_booking = get_field('total_golf_booking_price');

					if ( $total_golf_booking ) {
						$golf_content = '<tr class="item">
								<td>Golf Fees</td>
								<td>$'.number_format( $total_golf_booking, 2).'</td>
							</tr>';
						$request_payment_email_content = str_replace('GOLF_FEES', $golf_content, $request_payment_email_content);
					} else {
						$request_payment_email_content = str_replace('GOLF_FEES', '', $request_payment_email_content);
					}

					$request_payment_email_content = str_replace( 'BOOKING_ID', 'HRV-' . get_the_ID(), $request_payment_email_content );
					$request_payment_email_content = str_replace( 'NO_ADULTS', get_field( 'adult' ), $request_payment_email_content );
					$request_payment_email_content = str_replace( 'NO_NIGHTS', get_field( 'no_of_nights' ), $request_payment_email_content );
					$request_payment_email_content = str_replace( 'NO_CHILDREN', get_field( 'children' ), $request_payment_email_content );
					$request_payment_email_content = str_replace( '[GUEST_NAME]', get_field( 'first_name' ) . ' ' . get_field( $surname ), $request_payment_email_content );
					$request_payment_email_content = str_replace( 'DEPARTURE_DATE', date( 'd/M/Y', strtotime( get_field( 'end_date' ) ) ), $request_payment_email_content );
					$request_payment_email_content = str_replace( 'ARRIVAL_DATE', date( 'd/M/Y', strtotime( get_field( 'arrival_date' ) ) ), $request_payment_email_content );
					$request_payment_email_content = str_replace( 'PROPERTY_NAME', get_field( 'property' ), $request_payment_email_content );
					$request_payment_email_content = str_replace( 'PROPERTY_ADDRESS', get_field( 'booking_property_address' ), $request_payment_email_content );
					$request_payment_email_content = str_replace( 'TOTAL_PRICE', $total_price, $request_payment_email_content );
					$request_payment_email_content = str_replace( 'RENT_PRICE', get_field( 'booking_season_price' ), $request_payment_email_content );
					$request_payment_email_content = str_replace( 'TOTAL_DEPOSIT', $deposit_price, $request_payment_email_content );
					$request_payment_email_content = str_replace( 'TOTAL_BALANCE', $total_price - $deposit_price, $request_payment_email_content );
					$request_payment_email_content = str_replace( 'OTHER_ADDONS', $other_addons, $request_payment_email_content );
					$request_payment_email_content = str_replace( 'INVOICE_DATE', get_the_date( 'd/M/Y' ), $request_payment_email_content );
					$request_payment_email_content = str_replace( 'DIRECTIONS_FROM_AIRPORT', get_field( 'directions_from_airport' ), $request_payment_email_content );

					$this->send_hrv_email( $email, 'Request for payment', $request_payment_email_content );

					update_post_meta( get_the_ID(), 'reminder_email_sent', 'yes' );
				}
			}

		endwhile;
		wp_reset_postdata();
	}

	public function calculate_deposit( $post_id ) {
		if ( 'bookings' == get_post_type( $post_id ) ) {
			$total_price = get_field( 'total_price', $post_id );
			$deposit_amount = get_field( 'deposit_amount', $post_id ) ? get_field( 'deposit_amount', $post_id ) : 0;
			$additional_deposit = get_field('additional_deposit', $post_id) ? get_field('additional_deposit', $post_id) : 0;
			$total_amount_paid = $deposit_amount + $additional_deposit;
			update_field( 'total_amount_paid', $total_amount_paid, $post_id );
			$balance = $total_price - $total_amount_paid > 0 ? $total_price - $total_amount_paid : 0;
			update_field( 'balance', $balance, $post_id );
		}
	}

	public function update_property_address( $post_id ) {
		if ( 'bookings' == get_post_type( $post_id ) && ( 'no' !== get_post_meta( $post_id, 'manual_booking', true ) ) ) {
			$property_id    = get_field( 'property_post', $post_id )[0];
			if ( $property_id ) {
				$address = get_field('address', $property_id);
				if ( get_field('booking_property_address', $post_id) === "" && $address) {
					update_field('booking_property_address', $address, $post_id);
				}

			}

		}
	}

	public function update_booking_property_owner( $post_id ) {
		if ( 'bookings' == get_post_type( $post_id ) && ( 'no' !== get_post_meta( $post_id, 'manual_booking', true ) ) ) {
			$property_id    = get_field( 'property_post', $post_id )[0];
			if ( $property_id ) {
				$owner = get_field('property_owner', $property_id)[0];
				$selected = array(
					'value'=> $owner,
					'label'=> get_the_title( $owner )
				);
				if ( get_field('field_62b5a407bb418', $post_id) === "" && $owner ) {
					update_field('field_62b5a407bb418', $owner, $post_id);
				}

			}
		}
	}



	public function update_due_date( $post_id ) {
		if ( 'bookings' == get_post_type( $post_id ) && ( 'no' !== get_post_meta( $post_id, 'manual_booking', true ) ) ) {
			$arrival_date    = get_field( 'arrival_date', $post_id );
			$arrival_timestamp = strtotime( $arrival_date );
			$due_timestamp = $arrival_timestamp - (36 * 24 * 60 * 60);
			$due_date = date( 'Ymd', $due_timestamp );

			update_field('due_date', $due_date, $post_id);
		}
	}

	public function populate_booking_fields( $post_id ) {
		if ( 'bookings' == get_post_type( $post_id ) ) {


		}
	}


	public function calculate_total_price( $post_id ) {
		if ( 'bookings' == get_post_type( $post_id ) && ( 'no' !== get_post_meta( $post_id, 'manual_booking', true ) ) ) {
			$total_all = 0;
			if ( ! get_field( 'api_price', $post_id ) ) {
				$booking_season_price = get_field( 'booking_season_price', $post_id ) ? get_field( 'booking_season_price', $post_id ) : 0;
				$total_all            = $total_all + $booking_season_price;

				$extra_costs = get_field( 'extra_cost', $post_id );

				if ( $extra_costs ) {
					foreach ( $extra_costs as $cost ) {
						$total_all = $total_all + $cost['price'];
					}
				}

				$golf_bookings = get_field( 'golf_booking', $post_id );

				if ( $golf_bookings ) {
					foreach ( $golf_bookings as $golf ) {
						$total_all = $total_all + ($golf['price'] ? $golf['price'] : 0);
					}
				}

				update_field( 'total_price', $total_all, $post_id );
			}
		}
	}

	public function calculate_total_extra_price( $post_id ) {
		if ( 'bookings' == get_post_type( $post_id ) && ( 'no' !== get_post_meta( $post_id, 'manual_booking', true ) ) ) {
			$rows = array();
			$total_extra_owner = 0;
			$extra_cost = get_field( 'extra_cost', $post_id );

			if ( ! $extra_cost[0]['price'] ) return;

			foreach ( $extra_cost as $cost ) {
				$owner_price = $cost['owner_percentage'] !== '' && $cost['owner_price'] === '' ? number_format( ($cost['price'] / ( 1 + ( $cost['owner_percentage'] / 100 ))), 2) : $cost['owner_price'];
				$rows[]        = array(
					'extra_cost'       => $cost['extra_cost'],
					'price'            => $cost[ 'price' ],
					'owner_percentage' => $cost[ 'owner_percentage' ],
					'owner_price' => $owner_price,
					'commission'  => $cost['owner_percentage'] !== '' && $cost['owner_price'] === '' ? $cost[ 'price' ] - $owner_price : $cost['commission'],
				);
				$total_extra_owner = $total_extra_owner + $owner_price;

			}

			update_field( 'field_61fbad0ce3c30', $rows, $post_id );


			if ( get_field( 'api_price', $post_id ) ) {
				$total_all                         = 0;
				$owner_total = 0;
				$total_ciirus_price_with_comission = get_field( 'total_ciirus_price_with_comission', $post_id );
				$extra_costs                       = get_field( 'extra_cost', $post_id );

				if ( $extra_costs ) {
					foreach ( $extra_costs as $cost ) {
						$total_all = $total_all + $cost['price'];
						$owner_total = $owner_total + $cost['owner_price'];
					}
				}

				$golf_bookings = get_field( 'golf_booking', $post_id );

				if ( $golf_bookings ) {
					foreach ( $golf_bookings as $golf ) {
						$total_all = $total_all + $golf['price'];
					}
				}

				$total_price_and_extras = $total_all + $total_ciirus_price_with_comission;

				$total_owner_amount = $owner_total + get_field('ciirus_room_price', $post_id);

				update_field( 'extra_price', $total_all, $post_id );
				update_field( 'total_price_and_extras', $total_price_and_extras, $post_id );
				update_field( 'total_payment_to_owner', $total_owner_amount, $post_id );

				update_field('api_profit', $total_price_and_extras - $total_owner_amount, $post_id );

			} else {
				$booking_season_price = get_field('booking_season_price', $post_id);
				$property_percentage = get_field('property_owner_percentage', $post_id);
				if ( $property_percentage ) {
					$owner_total_villa_price = round( ($booking_season_price / ( 1 + ($property_percentage/100))), 2);

					if ( get_field('owner_total_villa_price', $post_id) === '' ) {
						update_field('owner_total_villa_price', $owner_total_villa_price, $post_id );
					}


				}

				$owner_total_price = $owner_total_villa_price + $total_extra_owner;



				$total_price = get_field('total_price', $post_id);
				if ( get_field('total_profit', $post_id) === '') {
					update_field('total_profit', $total_price - $owner_total_price, $post_id);
				}

				update_field('owner_total_extra_price', $total_extra_owner, $post_id);

				if (get_field('owner_total_price', $post_id) === '' ) {
					update_field('owner_total_price', ( get_field('owner_total_villa_price', $post_id) + get_field('owner_total_extra_price', $post_id) ), $post_id);
				}


			}
		}
	}

	public function add_extras( $post_id ) {
		if ( 'bookings' == get_post_type( $post_id ) ) {
			$rows = array();

			$property_id    = get_field( 'property_post', $post_id )[0];
			$extra_costs    = get_field( 'extra_cost', $property_id );
			$current_extras = get_field( 'extra_cost', $post_id );

			if ( ! $current_extras && $extra_costs ) {

				foreach ( $extra_costs as $cost ) {
					$rows[] = array(
						'extra_cost' => $cost['name'],
					);
				}

				update_field( 'field_61fbad0ce3c30', $rows, $post_id );
			}
		}
	}

	public function manual_pricing_metabox() {
		$id = $_GET['post'];

		add_meta_box(
			'manual_pricing_metabox',
			__( 'Manual Booking Pricing', 'hrv-mla' ),
			array( $this, 'manual_pricing_metabox_callback' ),
			'bookings',
			'side',
			'default',
		);
	}

	public function manual_pricing_metabox_callback($post) {
		$id = $post->ID;

		if ( ! isset( $id ) && empty( $id ) ) {
			return;
		}

		$manual_pricing = get_post_meta($id, 'manual_booking', true);


		ob_start();
		wp_nonce_field( 'manual_pricing', 'manual_nonce' );
		?>
            <label><input type="checkbox" name="manual_booking" id="manual_booking" value="yes"
                    <?php echo ($manual_pricing === 'yes' ? 'checked' : ''); ?>>Enable Manual Pricing</label>

            <?php
		echo ob_get_clean();
	}

	public function update_manual_pricing_metabox( $post_id, $post ) {
		// nonce check
		if ( ! isset( $_POST[ 'manual_nonce' ] ) || ! wp_verify_nonce( $_POST[ 'manual_nonce' ], 'manual_pricing' ) ) {
			return $post_id;
		}

		if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) {
			return $post_id;
		}

		// define your own post type here
		if( 'bookings' !== $post->post_type ) {
			return $post_id;
		}

		if( isset( $_POST[ 'manual_booking' ] ) ) {
			update_post_meta( $post_id, 'manual_booking', sanitize_text_field( $_POST[ 'manual_booking' ] ) );
		} else {
			delete_post_meta( $post_id, 'manual_booking' );
		}
	}

	public function reminder_email_metabox() {
		$id = $_GET['post'];

		add_meta_box(
			'reminder_email_metabox',
			__( 'Reminder Email', 'hrv-mla' ),
			array( $this, 'reminder_email_metabox_callback' ),
			'bookings',
			'side',
			'default',
		);

	}

	public function reminder_email_metabox_callback($post) {
		$id = $post->ID;

		if ( ! isset( $id ) && empty( $id ) ) {
			return;
		}

		ob_start();
		$reminder_sent = get_post_meta($id, 'reminder_email_sent', true);
		?>
            <div id="reminder-email">
                <label style="margin-bottom: 1em; display: block;"><input type="checkbox" value="yes"
                        name="reminder_email_sent" disabled
                        <?php echo ( 'yes' === $reminder_sent ? 'checked' : '' ); ?>>Email
                    already sent?</label>

                <button id="reminderEmailBtn" class="button button-primary button-large"
                    style="margin-bottom: 10px;">Test Reminder Email</button>
                <input type="email" name="reminderEmail" id="reminderEmailField" placeholder="Enter email to test."
                    value="">

                <script>
                const reminderEmailBtn = document.getElementById('reminderEmailBtn');
                const reminderEmailField = document.getElementById('reminderEmailField');

                function reminderEmailFunction(elem, action) {
                    elem.addEventListener('click', function(e) {
                        e.preventDefault();
                        elem.setAttribute('disabled', 'disabled');
                        elem.innerHTML = 'Sending...';

                        const form = new FormData();
                        form.append('action', action);
                        form.append('nonce', XERO.golfnonce);
                        form.append('email', reminderEmailField.value);
                        form.append('post_id', <?php echo $id; ?>);

                        const params = new URLSearchParams(form);
                        console.log(params);
                        fetch(XERO.ajax_url, {
                                method: 'POST',
                                credentials: 'same-origin',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                    'Cache-Control': 'no-cache',
                                },
                                body: params,
                            })
                            .then((response) => response.json())
                            .then((data) => {
                                if (data) {
                                    console.log(data);
                                    elem.innerHTML = 'Sent';
                                    location.reload();
                                }
                            })
                            .catch((error) => {
                                console.log('EMAIL FAILED');
                                console.error(error);
                            });

                    });
                }

                reminderEmailFunction(reminderEmailBtn, 'send_reminder_email_ajax');
                </script>

            </div>

            <?php
		echo ob_get_clean();
	}

	public function booking_paid_email_metabox() {
		$id = $_GET['post'];

		if (isset( $id ) && !empty( $id ) && 'full' === get_field('payment_status', $id ) ) {

			add_meta_box(
				'booking_paid_email_metabox',
				__( 'Fully Paid Email', 'hrv-mla' ),
				array( $this, 'booking_paid_email_metabox_callback' ),
				'bookings',
				'side',
				'default',
			);
		}
	}


	public function booking_manual_email_metabox() {
		$id = $_GET['post'];
		if ( 'bookings' != get_post_type( $id ) ) return;

		if (isset( $id ) && !empty( $id ) ) {

			add_meta_box(
				'booking_manual_email_metabox',
				__( 'Manual Email', 'hrv-mla' ),
				array( $this, 'booking_manual_email_metabox_callback' ),
				'bookings',
				'side',
				'default',
			);
		}

	}

	public function booking_manual_email_metabox_callback($post) {
		$id = $post->ID;

		if ( ! isset( $id ) && empty( $id ) ) {
			return;
		}

		ob_start();
		 $owner_sent = get_post_meta($id, '_owner_manual_email_sent', true);
		 $customer_sent = get_post_meta($id, '_customer_manual_email_sent', true);
		?>
            <div id="booking-manual-email">

                <label style="margin-bottom: 1em; display: block;">
                    <input type="checkbox" value="yes" name="manual_customer_email_sent" disabled
                        <?php echo ( 'yes' === $customer_sent ? 'checked' : '' ); ?>>Email
                    already sent?</label>

                <button id="customerManualEmail"
                    class="button button-primary button-large"><?php echo ( 'yes' === $customer_sent ? 'Send Manual Email to Customer again?' : 'Send Manual Email to Customer' ); ?></button>


                <hr />


                <label style="margin-bottom: 1em; display: block;">
                    <input type="checkbox" value="yes" name="manual_owner_email_sent" disabled
                        <?php echo ( 'yes' === $owner_sent ? 'checked' : '' ); ?>>Email
                    already sent?</label>

                <button id="ownerManualEmail"
                    class="button button-primary button-large"><?php echo ( 'yes' === $owner_sent ? 'Send Manual Email to Owner again?' : 'Send Manual Email to Owner' ); ?></button>

                <script>
                const ownerManualEmail = document.getElementById('ownerManualEmail');
                const customerManualEmail = document.getElementById('customerManualEmail');

                function sendEmailFunction(elem, action) {
                    elem.addEventListener('click', function(e) {
                        e.preventDefault();
                        elem.setAttribute('disabled', 'disabled');
                        elem.innerHTML = 'Sending...';

                        const form = new FormData();
                        form.append('action', action);
                        form.append('nonce', XERO.golfnonce);
                        form.append('post_id', <?php echo $id; ?>);

                        const params = new URLSearchParams(form);
                        console.log(params);
                        fetch(XERO.ajax_url, {
                                method: 'POST',
                                credentials: 'same-origin',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                    'Cache-Control': 'no-cache',
                                },
                                body: params,
                            })
                            .then((response) => response.json())
                            .then((data) => {
                                if (data) {
                                    console.log(data);
                                    elem.innerHTML = 'Sent';
                                    //location.reload();
                                }
                            })
                            .catch((error) => {
                                console.log('EMAIL FAILED');
                                console.error(error);
                            });

                    });
                }

                sendEmailFunction(customerManualEmail, 'send_customer_manual_booking_email');
                sendEmailFunction(ownerManualEmail, 'send_owner_manual_booking_email');
                </script>

            </div>

            <?php
		echo ob_get_clean();
	}

	public function booking_paid_email_metabox_callback($post) {
		$id = $post->ID;

		if ( ! isset( $id ) && empty( $id ) ) {
			return;
		}

		ob_start();
		 $customer_sent = get_post_meta($id, '_paid_email_sent', true);
		?>
            <div id="booking-full-email">
                <label style="margin-bottom: 1em; display: block;"><input type="checkbox" value="yes"
                        name="paid_email_sent" disabled
                        <?php echo ( 'yes' === $customer_sent ? 'checked' : '' ); ?>>Email
                    already sent?</label>

                <button id="paidEmailBtn"
                    class="button button-primary button-large"><?php echo ( 'yes' === $customer_sent ? 'Send Fully Paid Email Again?' : 'Send Fully Paid Email' ); ?></button>

                <script>
                const paidEmailBtn = document.getElementById('paidEmailBtn');

                function sendPaidEmailFunction(elem, action) {
                    elem.addEventListener('click', function(e) {
                        e.preventDefault();
                        elem.setAttribute('disabled', 'disabled');
                        elem.innerHTML = 'Sending...';

                        const form = new FormData();
                        form.append('action', action);
                        form.append('nonce', XERO.golfnonce);
                        form.append('post_id', <?php echo $id; ?>);

                        const params = new URLSearchParams(form);
                        console.log(params);
                        fetch(XERO.ajax_url, {
                                method: 'POST',
                                credentials: 'same-origin',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                    'Cache-Control': 'no-cache',
                                },
                                body: params,
                            })
                            .then((response) => response.json())
                            .then((data) => {
                                if (data) {
                                    console.log(data);
                                    elem.innerHTML = 'Sent';
                                    location.reload();
                                }
                            })
                            .catch((error) => {
                                console.log('EMAIL FAILED');
                                console.error(error);
                            });

                    });
                }

                sendPaidEmailFunction(paidEmailBtn, 'send_booking_paid_email');
                </script>

            </div>

            <?php
		echo ob_get_clean();
	}

	public function booking_paid_save_post($post_id) {

		// Check if our nonce is set.
		if (!isset($_POST['paid_meta_box_nonce'])) {
			return;
		}

		// Verify that the nonce is valid.
		if (!wp_verify_nonce($_POST['paid_meta_box_nonce'], 'paid_email_meta_box')) {
			return;
		}

		// If this is an autosave, our form has not been submitted, so we don't want to do anything.
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
			return;
		}

		// Check the user's permissions.
		if (!current_user_can('edit_post', $post_id)) {
			return;
		}

		// Check if there's a field set
		$checkbox_value = isset($_POST['paid_email_sent']) && $_POST['paid_email_sent'] === 'yes' ? 'yes' : 'no';

		// Update the meta field in the database.
		update_post_meta($post_id, '_paid_email_sent', $checkbox_value);

		//$this->full_paid_process( $post_id );

	}

	public function customer_manual_email_process( $post_id ) {
		global $post;
		$post = get_post( $post_id );

		setup_postdata($post);

		$arrival_date  = strtotime( get_field( 'arrival_date' ) );
		$property_id = get_field('property_post')[0];
		$today         = time();
		$diff          = $arrival_date - $today;

		$total_price   = get_field( 'total_price' );

		$email_to_send = $this->manual_email_content();
		if ( get_field('api_price') ) {
			$email_to_send = str_replace( '[BOOKING_DETAILS]', $this->booking_details_content_api(), $email_to_send );
			$email_to_send = str_replace( 'TOTAL_ROOM_RATE',  number_format(get_field('total_ciirus_price_with_comission'), 2), $email_to_send );
			$total_price   = get_field( 'total_price' );

		} else {
			$email_to_send = str_replace( '[BOOKING_DETAILS]', $this->booking_details_content(), $email_to_send );
			$email_to_send = str_replace( 'HOME_RENTAL_PRICE',  number_format( get_field('booking_season_price'), 2), $email_to_send  );
			$email_to_send = str_replace( 'TOTAL_PRICE',  number_format( get_field('total_price'), 2), $email_to_send );
		}




		$other_addons = '';

		if ( have_rows( 'extra_cost' ) ) :
			while ( have_rows( 'extra_cost' ) ) :
				the_row();
				$other_addons .= '<tr class="item">
			<td>' . get_sub_field( 'extra_cost' ) . '</td>
			<td>$' . (get_sub_field( 'price' ) ? number_format( get_sub_field( 'price' ), 2) : '0.00') . '</td>
			</tr>';
			endwhile;
		endif;

		$golf_bookings = '';

		if ( have_rows( 'golf_booking') ) {
			$golf_bookings .= '<div style="overflow-x:auto;" class="golf-table"><table style="border: 1px solid #000; border-collapse: collapse;" class="golf"><thead>
						<tr>
						<th valign="top" align="center" style="border: 1px solid #000; border-collapse: collapse; padding: 5px;">Date</th>
						<th valign="top" align="center" style="border: 1px solid #000; border-collapse: collapse; padding: 5px;">Golf Course</th>
						<th valign="top" align="center" style="border: 1px solid #000; border-collapse: collapse; padding: 5px;">Preferred Time</th>
						<th valign="top" align="center" style="border: 1px solid #000; border-collapse: collapse; padding: 5px;">Number of Holes</th>
						<th valign="top" align="center" style="border: 1px solid #000; border-collapse: collapse; padding: 5px;">Number of Players</th>
						</tr>
					</thead><tbody>';
			 while ( have_rows( 'golf_booking' ) ) {
				 the_row();
				 $golf_courses = get_sub_field( 'golf_course' );

				 $golf_bookings .= '<tr>
				<td valign="middle" align="center" style="border: 1px solid #000; border-collapse: collapse; padding: 5px;">' . get_sub_field( 'date' ) . '</td>
				<td valign="middle" align="center" style="border: 1px solid #000; border-collapse: collapse; padding: 5px;">' . get_the_title( $golf_courses ) . '</td>
				<td valign="middle" align="center" style="border: 1px solid #000; border-collapse: collapse; padding: 5px;">' . get_sub_field( 'preferred_time' ) . '</td>
				<td valign="middle" align="center" style="border: 1px solid #000; border-collapse: collapse; padding: 5px;">' . get_sub_field( 'number_of_rounds' ) . ' Holes</td>
				<td valign="middle" align="center" style="border: 1px solid #000; border-collapse: collapse; padding: 5px;">' . get_sub_field( 'number_of_players' ) . ' Golfers</td>
				</tr>';
			 }

			 $golf_bookings .= '</tbody></table></div>';

			 $email_to_send = str_replace( '[GOLF_BOOKING_DETAILS]', $golf_bookings, $email_to_send );

		 } else {
			$email_to_send = str_replace( '[GOLF_BOOKING_DETAILS]', '', $email_to_send );
		 }






		$email_subject = $this->get_manual_booking_subject() ? $this->get_manual_booking_subject() : 'Thanks for enquiring';
		$email_subject = str_replace( '[REF_#]', 'HRV-' . get_the_ID(), $email_subject );
		$email_subject = str_replace( '[GUEST_NAME]', get_field( 'first_name' ) . ' ' . get_field( 'surname' ), $email_subject );

		$email_to_send = str_replace( 'BOOKING_ID', 'HRV-' . get_the_ID(), $email_to_send );
		$email_to_send = str_replace( 'NO_ADULTS', get_field( 'adult' ), $email_to_send );
		$email_to_send = str_replace( 'NO_NIGHTS', get_field( 'no_of_nights' ), $email_to_send );
		$email_to_send = str_replace( 'NO_CHILDREN', get_field( 'children' ), $email_to_send );
		$email_to_send = str_replace( '[GUEST_NAME]', get_field( 'first_name' ) . ' ' . get_field( 'surname' ), $email_to_send );
		$email_to_send = str_replace( 'DEPARTURE_DATE', date( 'd/M/Y', strtotime( get_field( 'end_date' ) ) ), $email_to_send );
		$email_to_send = str_replace( 'ARRIVAL_DATE', date( 'd/M/Y', strtotime( get_field( 'arrival_date' ) ) ), $email_to_send );
		$email_to_send = str_replace( 'PROPERTY_NAME', get_field( 'property' ), $email_to_send );
		$email_to_send = str_replace( 'PROPERTY_ADDRESS', get_field( 'booking_property_address' ), $email_to_send );
		$email_to_send = str_replace( 'TOTAL_PRICE',  number_format($total_price, 2), $email_to_send );
		$email_to_send = str_replace( 'RENT_PRICE',  number_format(get_field( 'booking_season_price' ), 2), $email_to_send );
		$email_to_send = str_replace( 'TOTAL_BALANCE',  number_format(get_field('balance'), 2), $email_to_send );
		$email_to_send = str_replace( 'OTHER_ADDONS', $other_addons, $email_to_send );
		$email_to_send = str_replace( 'DUE_DATE', get_field( 'due_date' ), $email_to_send );
		$email_to_send = str_replace( 'INVOICE_DATE', get_the_date( 'd/M/Y' ), $email_to_send );
		$email_to_send = str_replace( 'TOTAL_DEPOSIT',  number_format( get_field( 'total_amount_paid' ), 2), $email_to_send );
		$email_to_send = str_replace( 'AMOUNT_PAID',  number_format( get_field( 'balance' ), 2), $email_to_send );

		$total_golf_booking = get_field('total_golf_booking_price');
		if ( $total_golf_booking ) {
			$golf_content = '<tr class="item">
                    <td>Golf Fees</td>
                    <td>$'.number_format( $total_golf_booking, 2).'</td>
                </tr>';
			$email_to_send = str_replace('GOLF_FEES', $golf_content, $email_to_send);
		} else {
			$email_to_send = str_replace('GOLF_FEES', '', $email_to_send);
		}

		$email = get_field( 'email' );

		$this->send_hrv_email( $email, $email_subject, $email_to_send );

		//$this->send_hrv_email( get_field( 'admin_email', 'option' ), $email_subject, $email_to_send );

		wp_reset_postdata();

		update_post_meta($post_id, '_customer_manual_email_sent', 'yes');
	}

	public function update_owner_email( $post_id ) {
		if ( 'bookings' == get_post_type( $post_id ) && ( 'no' !== get_post_meta( $post_id, 'manual_booking', true ) ) ) {

			$property_owner_id = get_field( 'booking_property_owner', $post_id );
			$property_owner_email = get_field('owner_email', $property_owner_id);

			if ( get_field( 'booking_property_owner_email', $post_id) === '' && $property_owner_email) {
				update_field( 'booking_property_owner_email', $property_owner_email, $post_id);
			}

		}
	}

	public function update_owner_name( $post_id ) {
		if ( 'bookings' == get_post_type( $post_id ) && ( 'no' !== get_post_meta( $post_id, 'manual_booking', true ) ) ) {

			$property_owner_id = get_field( 'booking_property_owner', $post_id );
			$property_owner_name = get_the_title( $property_owner_id );

			if ( get_field( 'booking_property_owner_name', $post_id) === '' && $property_owner_name) {
				update_field( 'booking_property_owner_name', $property_owner_name, $post_id);
			}

		}
	}

	public function update_total_golf_booking_price( $post_id ) {
		if ( 'bookings' === get_post_type( $post_id ) ) {


			if ( have_rows( 'golf_booking', $post_id ) ) {
				$total = 0;

				while ( have_rows( 'golf_booking', $post_id ) ) {
					the_row();

					$total += get_sub_field('price') ? get_sub_field('price') : 0;

				}

			}

			update_field('total_golf_booking_price', $total, $post_id);



		}
	}

	public function owner_manual_email_process( $post_id ) {

		global $post;
		$post = get_post( $post_id );
		$test_email             = 'bonnbonito@gmail.com';


		setup_postdata($post);

		$arrival_date  = strtotime( get_field( 'arrival_date' ) );
		$property_id = get_field('property_post')[0];
		$property_owner_id = get_field( 'booking_property_owner' );
		$property_owner_email = get_field('booking_property_owner_email');
		$owner_name = get_the_title( $property_owner_id );
		$today         = time();
		$diff          = $arrival_date - $today;
		$days          = floor( $diff / ( 60 * 60 * 24 ) );
		$total_price   = get_field( 'owner_total_price' );
		$booking_id  = 'HRV-'. get_the_ID();

		$api_price = get_field( 'api_price' );


		$other_addons = '';

		if ( have_rows( 'extra_cost' ) ) :
			while ( have_rows( 'extra_cost' ) ) :
				the_row();
				$other_addons .= '<tr class="item">
			<td>' . get_sub_field( 'extra_cost' ) . '</td>
			<td>$' .  number_format(get_sub_field( 'owner_price' ),2) . '</td>
			</tr>';
			endwhile;
		endif;

		$owner_email_subject = $this->get_owner_manual_email_subject() ? $this->get_owner_manual_email_subject() : 'Fully paid';
		$email_to_owner = $this->manual_email_owner_content();
		if ( $api_price == 1 ) {
			$email_to_owner = str_replace('[OWNER_DETAILS]', $this->booking_owner_details_content_api(), $email_to_owner);
			$email_to_owner = str_replace( '[BOOKING_DETAILS]', $this->booking_owner_details_content_api(), $email_to_owner );
			// $email_to_owner = str_replace( 'TOTAL_ROOM_RATE', get_field('total_payment_to_owner'), $email_to_owner );
			$email_to_owner = str_replace( 'HOME_RENTAL_PRICE',  number_format(get_field('ciirus_room_price'),2), $email_to_owner );
			$email_to_owner = str_replace( 'TOTAL_PRICE',  number_format(get_field('total_payment_to_owner'),2), $email_to_owner );
		} else {
			$email_to_owner = str_replace( '[OWNER_DETAILS]', $this->booking_owner_details_content(), $email_to_owner );
			$email_to_owner = str_replace( '[BOOKING_DETAILS]', $this->booking_owner_details_content(), $email_to_owner );
			$email_to_owner = str_replace( 'HOME_RENTAL_PRICE',  number_format(get_field('owner_total_villa_price'),2), $email_to_owner  );
			$email_to_owner = str_replace( 'TOTAL_PRICE',  number_format(get_field('owner_total_price'),2), $email_to_owner );
		}

		$email_to_owner = str_replace( 'BOOKING_ID', 'HRV-' . get_the_ID(), $email_to_owner );
		$email_to_owner = str_replace( 'NO_ADULTS', get_field( 'adult' ), $email_to_owner );
		$email_to_owner = str_replace( 'NO_NIGHTS', get_field( 'no_of_nights' ), $email_to_owner );
		$email_to_owner = str_replace( 'NO_CHILDREN', get_field( 'children' ), $email_to_owner );
		$email_to_owner = str_replace( '[GUEST_NAME]', get_field( 'first_name' ) . ' ' . get_field( 'surname' ), $email_to_owner );
		$email_to_owner = str_replace( 'DEPARTURE_DATE', date( 'd/M/Y', strtotime( get_field( 'end_date' ) ) ), $email_to_owner );
		$email_to_owner = str_replace( 'ARRIVAL_DATE', date( 'd/M/Y', strtotime( get_field( 'arrival_date' ) ) ), $email_to_owner );
		$email_to_owner = str_replace( 'PROPERTY_NAME', get_field( 'property' ), $email_to_owner );
		$email_to_owner = str_replace( 'PROPERTY_ADDRESS', get_field( 'booking_property_address' ), $email_to_owner );


		$email_to_owner = str_replace( 'OTHER_ADDONS', $other_addons, $email_to_owner );
		$email_to_owner = str_replace( 'INVOICE_DATE', date( 'd/M/Y' ), $email_to_owner );
		$email_to_owner = str_replace( '[OWNER_NAME]', $owner_name, $email_to_owner );

		$owner_email_subject = str_replace( '[REF_#]', $booking_id, $owner_email_subject );
		$owner_email_subject = str_replace( '[GUEST_NAME]', $firstname . ' ' . $surname, $owner_email_subject );
		$owner_email_subject = str_replace( '[OWNER_NAME]', $owner_name, $owner_email_subject );
		$owner_email_subject = str_replace( '[PROPERTY ADDRESS]', get_field( 'booking_property_address' ), $owner_email_subject);


		if ( $property_owner_email ) {
			if ( get_field( 'testing', 'option' ) ) {
			$property_owner_email = $test_email;
			}
		}


		$this->send_hrv_email( $property_owner_email, $owner_email_subject, $email_to_owner );

		wp_reset_postdata();

		update_post_meta($post_id, '_owner_manual_email_sent', 'yes');

	}

	public function full_paid_process( $post_id ) {
		global $post;
		$post = get_post( $post_id );

		setup_postdata($post);

		$arrival_date  = strtotime( get_field( 'arrival_date' ) );
		$property_id = get_field('property_post')[0];
		$today         = time();
		$diff          = $arrival_date - $today;
		$days          = floor( $diff / ( 60 * 60 * 24 ) );
		$total_price   =  number_format( get_field( 'total_price' ), 2);
		$deposit_price = $total_price - ( $total_price * 0.10 );
		$deposit_price = $deposit_price > $this->deposit ? $this->deposit : $deposit_price;


		$email_to_send = $this->get_fully_paid_content();
		if ( get_field('api_price') ) {
			$email_to_send = str_replace( '[BOOKING_DETAILS]', $this->fully_paid_details_content_api(), $email_to_send );
			$email_to_send = str_replace( 'TOTAL_ROOM_RATE', number_format(get_field('total_ciirus_price_with_comission'),2), $email_to_send );

		} else {
			$email_to_send = str_replace( '[BOOKING_DETAILS]', $this->fully_paid_details_content(), $email_to_send );
			$email_to_send = str_replace( 'HOME_RENTAL_PRICE', number_format(get_field('booking_season_price'),2), $email_to_send  );
		}




		$other_addons = '';

		if ( have_rows( 'extra_cost' ) ) :
			while ( have_rows( 'extra_cost' ) ) :
				the_row();
				$other_addons .= '<tr class="item">
			<td>' . get_sub_field( 'extra_cost' ) . '</td>
			<td>$' .  (get_sub_field( 'price' ) ? number_format(get_sub_field( 'price' ),2) : '0.00') . '</td>
			</tr>';
			endwhile;
		endif;

		$email_subject = $this->get_fully_paid_subject() ? $this->get_fully_paid_subject() : 'Fully paid';
		$email_subject = str_replace( '[REF_#]', 'HRV-' . get_the_ID(), $email_subject );
		$email_subject = str_replace( '[GUEST_NAME]', get_field( 'first_name' ) . ' ' . get_field( 'surname' ), $email_subject );

		$directions = '';
		if ( get_field( 'directions_from_airport', $property_id ) && !empty( get_field( 'directions_from_airport', $property_id ) ) ) {
			ob_start();
			?>
            <tr class="heading">
                <td colspan="2">Directions</td>
            </tr>

            <tr class="item">
                <td colspan="2"><?php the_field('directions_from_airport', $property_id); ?></td>
            </tr>
            <?php
			$directions = ob_get_clean();
		}

		$checkin = '';
		if ( get_field( 'check_in_instructions', $property_id ) && !empty(get_field( 'check_in_instructions', $property_id )) ) {
			ob_start();
			?>
            <tr class="heading">
                <td colspan="2">Check In Information</td>
            </tr>

            <tr class="item">
                <td colspan="2"><?php the_field('check_in_instructions', $property_id); ?></td>
            </tr>
            <?php
			$checkin = ob_get_clean();
		}

		$specific_checkin = '';
		if ( get_field( 'specific_check_in_instructions', $property_id ) && !empty(get_field( 'specific_check_in_instructions', $property_id )) ) {
			ob_start();
			?>
            <tr class="heading">
                <td colspan="2">Property Check In Details</td>
            </tr>

            <tr class="item">
                <td colspan="2"><?php the_field('specific_check_in_instructions', $property_id); ?></td>
            </tr>
            <?php
			$specific_checkin = ob_get_clean();
		}

		$alarm = '';
		if ( get_field( 'alarm_information', $property_id ) && !empty(get_field( 'alarm_information', $property_id )) ) {
			ob_start();
			?>
            <tr class="heading">
                <td colspan="2">Alarm Information</td>
            </tr>

            <tr class="item">
                <td colspan="2"><?php the_field('alarm_information', $property_id); ?></td>
            </tr>
            <?php
			$alarm = ob_get_clean();
		}

		$email_to_send = str_replace( 'BOOKING_ID', 'HRV-' . get_the_ID(), $email_to_send );
		$email_to_send = str_replace( 'NO_ADULTS', get_field( 'adult' ), $email_to_send );
		$email_to_send = str_replace( 'NO_NIGHTS', get_field( 'no_of_nights' ), $email_to_send );
		$email_to_send = str_replace( 'NO_CHILDREN', get_field( 'children' ), $email_to_send );
		$email_to_send = str_replace( '[GUEST_NAME]', get_field( 'first_name' ) . ' ' . get_field( 'surname' ), $email_to_send );
		$email_to_send = str_replace( 'DEPARTURE_DATE', date( 'd/M/Y', strtotime( get_field( 'end_date' ) ) ), $email_to_send );
		$email_to_send = str_replace( 'ARRIVAL_DATE', date( 'd/M/Y', strtotime( get_field( 'arrival_date' ) ) ), $email_to_send );
		$email_to_send = str_replace( 'PROPERTY_NAME', get_field( 'property' ), $email_to_send );
		$email_to_send = str_replace( 'PROPERTY_ADDRESS', get_field( 'booking_property_address' ), $email_to_send );
		$email_to_send = str_replace( 'TOTAL_PRICE', $total_price, $email_to_send );
		$email_to_send = str_replace( 'RENT_PRICE', number_format( get_field( 'booking_season_price' ), 2), $email_to_send );
		$email_to_send = str_replace( 'TOTAL_BALANCE', '0.00', $email_to_send );
		$email_to_send = str_replace( 'OTHER_ADDONS', $other_addons, $email_to_send );
		$email_to_send = str_replace( 'DUE_DATE', get_field( 'due_date' ), $email_to_send );
		$email_to_send = str_replace( 'INVOICE_DATE', get_the_date( 'd/M/Y' ), $email_to_send );
		$email_to_send = str_replace( '[DIRECTIONS_FROM_AIRPORT]', $directions, $email_to_send );
		$email_to_send = str_replace( 'TOTAL_DEPOSIT', number_format( get_field( 'total_amount_paid' ), 2), $email_to_send );
		$email_to_send = str_replace( 'AMOUNT_PAID',  number_format( get_field( 'balance' ), 2), $email_to_send );
		$email_to_send = str_replace( '[CHECK_IN_INSTRUCTIONS]', $checkin, $email_to_send );
		$email_to_send = str_replace( '[SPECIFIC_CHECK_IN_INSTRUCTIONS]', $specific_checkin, $email_to_send);
		$email_to_send = str_replace( '[ALARM_INFORMATION]', $alarm, $email_to_send);

		$total_golf_booking = get_field('total_golf_booking_price');
		if ( $total_golf_booking ) {
			$golf_content = '<tr class="item">
                    <td>Golf Fees</td>
                    <td>$'.number_format( $total_golf_booking, 2).'</td>
                </tr>';
			$email_to_send = str_replace('GOLF_FEES', $golf_content, $email_to_send);
		} else {
			$email_to_send = str_replace('GOLF_FEES', '', $email_to_send);
		}

		$email = get_field( 'email' );

		$this->send_hrv_email( $email, $email_subject, $email_to_send );

		//$this->send_hrv_email( get_field( 'admin_email', 'option' ), $email_subject, $email_to_send );

		wp_reset_postdata();

		update_post_meta($post_id, '_paid_email_sent', 'yes');
	}



	public function booking_golf_email_metabox() {
		add_meta_box(
			'booking_golf_email_metabox',
			__( 'Email Golf Booking', 'hrv-mla' ),
			array( $this, 'booking_golf_email_metabox_callback' ),
			'bookings',
			'side',
			'default',
		);
	}

	public function booking_golf_email_metabox_callback() {
		$id = $_GET['post'];

		if ( ! isset( $id ) && empty( $id ) ) {
			return;
		}

		ob_start();
		?>
            <div style="background: #ddd; color: #000; padding: 10px; margin-bottom: 10px;">Please update first
                before
                sending
                an
                email.</div>
            <div id="booking_golf_email_status" style="display: none;">
                <h4 id="statusText">Sending Email...</h4>
            </div>
            <div id="booking_golf_email_wrap">
                <input type="email" id="booking_golf_email" value="orlando.rentals@gmail.com">
                <button id="booking_email_btn" class="button button-primary button-large" style="margin-top: 5px;">Send
                    Golf
                    Booking
                    Email</button>
            </div>
            <script>
            const booking_golf_email_wrap = document.getElementById('booking_golf_email_wrap');
            const booking_golf_email_status = document.getElementById('booking_golf_email_status');
            const booking_email_btn = document.getElementById('booking_email_btn');
            const booking_golf_email = document.getElementById('booking_golf_email');
            if (booking_email_btn) {
                booking_email_btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (!booking_golf_email.value) {
                        alert('Please enter an email');
                        return;
                    }
                    booking_golf_email_status.style.display = "block";
                    booking_golf_email_wrap.style.display = "none";
                    const form = new FormData();
                    form.append('action', 'send_golf_booking_email');
                    form.append('nonce', XERO.golfnonce);
                    form.append('post_id', <?php echo $id; ?>);
                    form.append('golf_booking_email', booking_golf_email.value);

                    const params = new URLSearchParams(form);
                    const statusText = document.querySelector('#statusText');
                    console.log(params);
                    fetch(XERO.ajax_url, {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                                'Cache-Control': 'no-cache',
                            },
                            body: params,
                        })
                        .then((response) => response.json())
                        .then((data) => {
                            if (data) {
                                console.log(data);
                                if (2 == data.code) {
                                    alert("Golf Booking empty");
                                    booking_golf_email_status.style.display = "none";
                                    booking_golf_email_wrap.style.display = "block";
                                }
                                statusText.innerText = "EMAIL SENT";
                                location.reload();
                            }
                        })
                        .catch((error) => {
                            console.log('EMAIL FAILED');
                            console.error(error);
                        });

                });
            }
            </script>
            <?php
			echo ob_get_clean();
	}

	public function send_booking_paid_email() {
		 $status = array(
			 'code' => 2,
		 );
		 if ( ! wp_verify_nonce( $_POST['nonce'], 'golf-nonce' ) ) {
			 wp_send_json( 'Nonce Error' );
		 }

		$this->full_paid_process( $_POST['post_id'] );

		 $status['post'] = $_POST;
		 wp_send_json( $status );
	}

	public function send_customer_manual_booking_email() {
		 $status = array(
			 'code' => 2,
		 );
		 if ( ! wp_verify_nonce( $_POST['nonce'], 'golf-nonce' ) ) {
			 wp_send_json( 'Nonce Error' );
		 }

		$this->customer_manual_email_process( $_POST['post_id'] );

		 $status['post'] = $_POST;
		 wp_send_json( $status );
	}

	public function send_reminder_email_ajax() {
		 $status = array(
			 'code' => 2,
		 );
		 if ( ! wp_verify_nonce( $_POST['nonce'], 'golf-nonce' ) ) {
			 wp_send_json( 'Nonce Error' );
		 }

		$result = $this->send_reminder_email_process( $_POST['post_id'], $_POST['email'] );

		 $status['post'] = $_POST;
		 $status['result'] = $result;
		 wp_send_json( $status );
	}

	public function send_owner_manual_booking_email() {
		 $status = array(
			 'code' => 2,
		 );
		 if ( ! wp_verify_nonce( $_POST['nonce'], 'golf-nonce' ) ) {
			 wp_send_json( 'Nonce Error' );
		 }

		$this->owner_manual_email_process( $_POST['post_id'] );

		 $status['post'] = $_POST;
		 wp_send_json( $status );
	}

	public function send_golf_booking_email() {
		 $status = array(
			 'code' => 2,
		 );
		 if ( ! wp_verify_nonce( $_POST['nonce'], 'golf-nonce' ) ) {
			 wp_send_json( 'Nonce Error' );
		 }

		 $rows = get_field( 'golf_booking', $_POST['post_id'] );

		 if ( ! $rows ) {
			 $status['post'] = $_POST;
			 wp_send_json( $status );
		 }

		 $golf_booking_email_content = $this->request_golf_email_content();

		 $golf_bookings = '<p>Please book these Courses and Tee Times for our client <strong>'. get_field( 'first_name', $_POST['post_id'] ) . ' ' . get_field( 'surname', $_POST['post_id'] ) .'</strong></p><br/>';

		 if ( have_rows( 'golf_booking', $_POST['post_id'] ) ) {
			$golf_bookings .= '<div style="overflow-x:auto;" class="golf-table"><table class="golf"><thead>
						<tr>
						<th valign="top" align="center">Date</th>
						<th valign="top" align="center">Golf Course</th>
						<th valign="top" align="center">Preferred Time</th>
						<th valign="top" align="center">Number of Holes</th>
						<th valign="top" align="center">Number of Players</th>
						</tr>
					</thead><tbody>';
			 while ( have_rows( 'golf_booking', $_POST['post_id'] ) ) {
				 the_row();
				 $golf_courses = get_sub_field( 'golf_course' );

				 $golf_bookings .= '<tr>
				<td valign="middle" align="center">' . get_sub_field( 'date' ) . '</td>
				<td valign="middle" align="center">' . get_the_title( $golf_courses ) . '</td>
				<td valign="middle" align="center">' . get_sub_field( 'preferred_time' ) . '</td>
				<td valign="middle" align="center">' . get_sub_field( 'number_of_rounds' ) . ' Holes</td>
				<td valign="middle" align="center">' . get_sub_field( 'number_of_players' ) . ' Golfers</td>
				</tr>';
			 }

			 $golf_bookings .= '</tbody></table></div>';

		 }

		 $guest_name = get_field( 'first_name', $_POST['post_id'] ) . ' ' . get_field( 'surname', $_POST['post_id'] );

		 $golf_booking_email_content = str_replace( '[GUEST_NAME]', $guest_name, $golf_booking_email_content );
		 $golf_booking_email_content = str_replace( '[GOLF_BOOKING_DETAILS]', $golf_bookings, $golf_booking_email_content );
		 $golf_booking_email_content = str_replace( 'INVOICE_DATE', date('F j, Y'), $golf_booking_email_content );

		 $this->send_hrv_email( $_POST['golf_booking_email'], 'Golf Reservations', $golf_booking_email_content );

		 $status['code'] = 1;
		 $status['post'] = $_POST;
		 $status['mail'] = $golf_bookings;
		 wp_send_json( $status );
	}

	public function get_all_owners() {
		$args = array(
			'post_type'      => 'owner',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'order'          => 'ASC',
			'orderby'        => 'title',
		);

		$owners_posts = get_posts($args);
		$owners = array();

		foreach ($owners_posts as $post) {
			setup_postdata($post);
			$owners[] = array(
				'id'    => $post->ID,
				'title' => $post->post_title,
			);

			wp_reset_postdata();
		}

		// Now $owners array contains the required information


		return $owners;
	}

	public function owner_filter_dropdown_start(){
		global $typenow;
		if ( 'bookings' === $typenow ) {
		ob_start();
		}
	}

	public function render_owner_filter_options( $which ) {
		global $typenow;
		if ( 'bookings' === $typenow ) {
			$owners = $this->get_all_owners();
			?>
            <select name="property_owner" id="selectPropertyOwner">
                <option value="">Choose Property Owner</option>
                <?php
				foreach ( $owners as $owner ) {
					?>
                <option value="<?php echo $owner['id']; ?>"
                    <?php echo( isset( $_GET['property_owner'] ) && $owner['id'] == $_GET['property_owner'] && ! empty( $_GET['property_owner'] ) ? 'selected' : '' ); ?>>
                    <?php echo $owner['title']; ?>
                </option>
                <?php } ?>
            </select>
            <?php
		}
		$content = ob_get_clean();
		echo $content;
	}

	public function owner_radio_values( $field ) {
		$owners = $this->get_all_owners();

		foreach ( $owners as $owner ) {
			$field['choices'][ $owner['id'] ] = $owner['title'];
		}

		return $field;
	}

	/**
	 * Get to Property Owner Email subject
	 */
	public function get_to_owner_email_subject() {
		return get_field( 'to_property_owner_email_subject', 'option' );
	}

	/**
	 * Get to customer Email subject
	 */
	public function get_to_customer_email_subject() {
		return get_field( 'to_customer_email_subject', 'option' );
	}

	/**
	 * Get to admin Email subject
	 */
	public function get_to_admin_email_subject() {
		return get_field( 'to_admin_email_subject', 'option' );
	}

	public function get_fully_paid_subject() {
		return get_field('fully_paid_email_subject', 'option');
	}

	public function get_owner_manual_email_subject() {
		return get_field('manual_booking_owner_subject_email', 'option');
	}

	public function get_manual_booking_subject() {
		return get_field('manual_booking_subject', 'option');
	}

	public function get_reminder_email_subject() {
		return get_field('reminder_email_subject', 'option');
	}

	public function fully_paid_details_content() {
		 ob_start();
		require 'emails/fully-paid-details.html';
		return ob_get_clean();
	}


	public function fully_paid_details_content_api() {
		 ob_start();
		require 'emails/fully-paid-details-api.html';
		return ob_get_clean();
	}


	public function booking_details_content() {
		 ob_start();
		require 'emails/booking-details.html';
		return ob_get_clean();
	}

	public function booking_details_content_api() {
		 ob_start();
		require 'emails/booking-details-api.html';
		return ob_get_clean();
	}

	public function booking_owner_details_content() {
		ob_start();
		require 'emails/booking-owner-details.html';
		return ob_get_clean();
	}

	public function booking_owner_details_content_api() {
		ob_start();
		require 'emails/booking-owner-details-api.html';
		return ob_get_clean();
	}

	public function booking_admin_details_content() {
		ob_start();
		require 'emails/booking-admin-details.html';
		return ob_get_clean();
	}

	public function booking_admin_details_content_api() {
		ob_start();
		require 'emails/booking-admin-details-api.html';
		return ob_get_clean();
	}

	public function email_template() {
		ob_start();
		require 'emails/template.html';
		return ob_get_clean();
	}

	public function paid_email_template() {
		ob_start();
		require 'emails/paid-template.html';
		return ob_get_clean();
	}

	public function get_to_customer_content_option() {
		return get_field( 'to_customer_email_content', 'option' );
	}

	/**
	 * Get to customer Email content
	 */
	public function get_to_customer_email_content() {
		$content = $this->email_template();
		$content = str_replace( 'EMAIL_CONTENT', get_field( 'to_customer_email_content', 'option' ), $content );
		return $content;
	}

	/**
	 * Get to customer Email content
	 */
	public function get_request_payment_content() {
		 $content = $this->email_template();
		$content  = str_replace( 'EMAIL_CONTENT', get_field( 'reminder_email_content', 'option' ), $content );
		return $content;
	}



	public function manual_email_content() {
		$content = $this->email_template();
		$content  = str_replace( 'EMAIL_CONTENT', get_field( 'manual_booking_content', 'option' ), $content );
		return $content;
	}

	public function manual_email_owner_content() {
		$content = $this->email_template();
		$content  = str_replace( 'EMAIL_CONTENT', get_field( 'manual_booking_owner_content_email', 'option' ), $content );
		return $content;
	}


	/**
	 * Get fully paid Email content
	 */
	public function get_fully_paid_content() {
		$content = $this->fully_paid_email_content();
		$content  = str_replace( 'EMAIL_CONTENT', get_field( 'fully_paid_email_content', 'option' ), $content );
		return $content;
	}

	/**
	 * Get fully paid Email content - Owner
	 */
	public function get_fully_paid_content_owner() {
		$content = $this->fully_paid_email_content();
		$content  = str_replace( 'EMAIL_CONTENT', get_field( 'fully_paid_email_content_owner', 'option' ), $content );
		return $content;
	}

	/**
	 * Get to admin Email content
	 */
	public function get_admin_email_hrv_content() {
		 $content = $this->email_template();
		$content  = str_replace( 'EMAIL_CONTENT', '[BOOKING_DETAILS]', $content );
		return $content;
	}

	/**
	 * Get to admin Email content
	 */
	public function get_to_admin_email_content() {
		$content = $this->email_template();
		$content = str_replace( 'EMAIL_CONTENT', get_field( 'to_admin_email_content', 'option' ), $content );
		return $content;
	}


	/**
	 * Get to Property Owner Email content
	 */
	public function get_to_owner_email_content() {
		$content = $this->email_template();
		$content = str_replace( 'EMAIL_CONTENT', get_field( 'to_property_owner_email_content', 'option' ), $content );
		return $content;
	}

	/**
	 * Change booking title on post
	 */
	public function bookings_save_post( $post_id ) {
		if ( 'bookings' !== get_post_type( $post_id ) ) {
			return;
		}
			$first_name    = get_field( 'first_name', $post_id );
			$surname       = get_field( 'surname', $post_id );
			$property_id   = get_field( 'property_post', $post_id );


			$property_link = array(
				'title'  => get_the_title( $property_id[0] ),
				'url'    => get_the_permalink( $property_id[0] ),
				'target' => '_blank',
			);

			if ( $property_id ) {
				update_field( 'property', get_the_title( $property_id[0] ), $post_id );
				update_field( 'property_link', $property_link, $post_id );
			}

			$post_update = array(
				'ID'         => $post_id,
			);

			if ( 'no' === get_post_meta( $post_id, 'manual_booking', true ) ) {
				$post_update['post_title'] = 'HRV-' . $post_id . ' - ' . $first_name . ' ' . $surname;
			} else {
				if ( get_field('old_booking_id', $post_id) === '' ) {

					$post_update['post_title'] = 'HRV-' . $post_id . ' - ' . $first_name . ' ' . $surname;

				} else {
					$post_update['post_title'] = 'HRV-' . get_field('old_booking_id', $post_id) . ' - ' . $first_name . ' ' . $surname;
				}


			}

			$bedrooms = get_field('bedrooms', $property_id[0] );
			if ( get_field('no_of_bedrooms', $post_id) === '' && $bedrooms ) {
				update_field( 'no_of_bedrooms', $bedrooms, $post_id );
			}


			$arrival_date = strtotime( get_field( 'arrival_date', $post_id ) );
			$end_date = strtotime( get_field( 'end_date', $post_id ) );
			$diff         = $end_date - $arrival_date;
			$days         = floor( $diff / ( 60 * 60 * 24 ) );

			$nights = $days > 0 ? $days : 0;

			if ( get_field( 'no_of_nights', $post_id ) === '' && $nights ) {
				update_field('no_of_nights', $nights, $post_id);
			}





			wp_update_post( $post_update );
	}
}
?>