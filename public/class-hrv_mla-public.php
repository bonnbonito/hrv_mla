<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link  https://www.linkedin.com/in/bonn-joel-elimanco-56a43a20
 * @since 1.0.0
 *
 * @package    HRV_MLA
 * @subpackage HRV_MLA/public
 */

use \DrewM\MailChimp\MailChimp;

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    HRV_MLA
 * @subpackage HRV_MLA/public
 * @author     Bonn Joel Elimanco <bonnbonito@gmail.com>
 */

class HRV_MLA_Public {
	/**
	 * The ID of this plugin.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 1.0.0
	 * @param string $plugin_name The name of the plugin.
	 * @param string $version     The version of this plugin.
	 */

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}


	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since 1.0.0
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

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/hrv_mla-public.css', array(), $this->version, 'all' );
		wp_enqueue_style( 'flexslider', plugin_dir_url( __FILE__ ) . 'css/flexslider.css', array(), $this->version, 'all' );
		wp_enqueue_style( 'vanilla-datepicker', '//cdn.jsdelivr.net/npm/vanillajs-datepicker@1.2.0/dist/css/datepicker.min.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since 1.0.0
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
		$hrv_admin = new HRV_MLA_Admin( 'hrv_mla', '1.0.0' );
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/hrv_mla-public.js', array( 'jquery' ), $this->version, false );
		wp_enqueue_script( 'flexslider', plugin_dir_url( __FILE__ ) . 'js/jquery.flexslider-min.js', array( 'jquery' ), $this->version, false );
		wp_enqueue_script( 'vanilla-datepicker', '//cdn.jsdelivr.net/npm/vanillajs-datepicker@1.2.0/dist/js/datepicker.min.js', array( 'jquery' ), $this->version, false );
		wp_localize_script(
			$this->plugin_name,
			'HRV',
			array(
				'ajax_url'              => admin_url( 'admin-ajax.php' ),
				'nonce'                 => wp_create_nonce( 'hrv-nonce' ),
				'stripe'                => $this->get_stripe_publishable_key(),
				'postlink'              => get_the_permalink(),
				'userid'                => get_current_user_id(),
				'post_id'               => get_the_ID(),
				'root_url'              => get_site_url(),
				'properties_ids'        => $this->get_all_properties(),
				'properties_beds'       => $this->get_all_beds(),
				'properties_result_ids' => $this->get_result_properties(),
				'stripe_deposit'        => $hrv_admin->deposit,
			)
		);
	}


	/**
	 * Get all properties results ID's
	 */
	public function get_all_beds() {
		$query = new WP_Query(
			array(
				'post_type'      => 'properties',
				'posts_per_page' => -1,
			)
		);
		$ids   = array();
		while ( $query->have_posts() ) :
			$query->the_post();
			$ids[] = get_field( 'bedrooms' );
		endwhile;
		wp_reset_postdata();
		return $ids;
	}

	/**
	 * Get all properties results ID's
	 */
	public function get_result_properties() {
        $args = array(
            'post_type'      => 'properties',
            'posts_per_page' => -1,
            'meta_key'       => 'bedrooms',
            'meta_value'     => isset( $_GET['bedrooms'] ) ? $_GET['bedrooms'] : null,
        );

        if ( isset( $_GET['resort'] ) && !empty( $_GET['resort'] ) && $_GET['resort'] != 'all' ) {
            $args['tax_query'] = array(
                array(
                    'taxonomy'         => 'resort',
                    'terms'            => array( $_GET['resort'] ),
                    'field'            => 'slug',
                    'operator'         => 'IN',
                ),
            );
        }

        $query = new WP_Query( $args );

        $ids = array();

        while ( $query->have_posts() ) {
            $query->the_post();
            $ids[] = get_the_ID();
        }

        wp_reset_postdata();

        return $ids;
    }


	/**
	 * Get all properties ID's
	 */
	public function get_all_properties() {
		$query = new WP_Query(
			array(
				'post_type'      => 'properties',
				'posts_per_page' => -1,
			)
		);
		$ids   = array();
		while ( $query->have_posts() ) :
			$query->the_post();
			$ids[] = get_the_ID();
		endwhile;
		wp_reset_postdata();
		return $ids;
	}

	/**
	 * Stripe Mailchimp API Key
	 */
	public function get_mailchimp_api() {
		return get_field( 'mailchimp_api', 'option' );
	}

	/**
	 * Stripe Mailchimp API Key
	 */
	public function get_mailchimp_list_id() {
		return get_field( 'mailchimp_list_id', 'option' );
	}

	/**
	 * Stripe Publishable Key
	 */
	public function get_stripe_publishable_key() {
		return get_field( 'testing', 'option' ) ? get_field( 'test_publishable_key', 'option' ) : get_field( 'live_publishable_key', 'option' );
	}

	/**
	 * Stripe Secret Key
	 */
	public function get_stripe_secret_key() {
		return get_field( 'testing', 'option' ) ? get_field( 'test_secret_key', 'option' ) : get_field( 'live_secret_key', 'option' );
	}



	public function compute_age( $birthDate ) {
		$currentDate = date( 'd-m-Y' );
		$age         = date_diff( date_create( $birthDate ), date_create( $currentDate ) );
		return $age->format( '%y' );
	}

	public function percentage_tax_price( $price, $percent ) {
		return ( $percent / 100 ) * $price;
	}

	/**
	 * Property Available
	 */

	public function check_availability() {
		if ( ! wp_verify_nonce( $_POST['nonce'], 'hrv-nonce' ) ) {
			wp_send_json( 'Nonce Error' );
		}
		$hrv_admin              = new HRV_MLA_Admin( 'hrv_mla', '1.0.0' );
		$checkin                = date( 'd M Y', strtotime( $_REQUEST['checkin'] ) );
		$checkout               = date( 'd M Y', strtotime( $_REQUEST['checkout'] ) );
		$datediff               = strtotime( $checkout ) - strtotime( $checkin );
		$nights                 = round( $datediff / ( 60 * 60 * 24 ) );
		$id                     = $_REQUEST['ciirus_id'];
		$property_id            = $_REQUEST['property_id'];
		$api_get_price          = $hrv_admin->ciirus_get_property_rates( $id, $checkin, $nights );
		$cleaning_fees          = $hrv_admin->ciirus_get_cleaning_fee( $id, $nights );
		$propertyTaxRatesApi    = $hrv_admin->ciirus_get_tax_rates( $id );
		$propertyTaxRates       = $propertyTaxRatesApi['total_rates'];
		$api_total_rate         = $api_get_price['total_rates'];
		$bookingprice           = round( $api_total_rate + $this->percentage_tax_price( $api_total_rate, $propertyTaxRates ) + $cleaning_fees, 2 );
		$results['status']      = $hrv_admin->ciirus_is_property_available( $id, $checkin, $checkout );
		$results['nights']      = $nights;
		$results['checkin']     = $checkin;
		$results['checkout']    = $checkout;
		$results['property_id'] = $property_id;
		$results['ciirus_id']   = $id;
		$results['price']       = $bookingprice;
		wp_send_json( $results );
	}

	public function get_all_property_details() {
		if ( ! wp_verify_nonce( $_POST['nonce'], 'hrv-nonce' ) ) {
			wp_send_json( 'Nonce Error' );
		}
		$bedrooms = isset( $_POST['bedrooms'] ) && ! empty( $_POST['bedrooms'] ) ? $_POST['bedrooms'] : null;
       
        $resort = isset( $_POST['resort'] ) && ! empty( $_POST['resort'] ) ? $_POST['resort'] : null;


		$args     = array(
			'post_type'      => 'properties',
			'numberposts'    => -1,
			'posts_per_page' => -1,
		);

		if ( $bedrooms ) {
			$args['meta_key']   = 'bedrooms';
			$args['meta_value'] = $bedrooms;
		}
        
        if ( $resort && $resort !== 'all' ) {
            $args['tax_query'] = array(
                array(
                    'taxonomy'         => 'resort', 
                    'terms'            => array( $resort ), 
                    'field'            => 'slug',
                    'operator'         => 'IN', 
                )
            );
        }

		$query = new WP_Query( $args );

		$status = array();
		ob_start();

		if ( $query->have_posts() ) :
			while ( $query->have_posts() ) :
				$query->the_post();
				$amenities       = get_field( 'amenities_list' );
				$amenities_icons = get_field( 'amenities_icons' );
				?>
<div class="property-result-wrap">
    <div class="img-wrap-property">
        <?php echo get_the_post_thumbnail( get_the_ID(), 'full' ); ?>
    </div>
    <div class="property-results-content">
        <h3 class="property-result-title">
            <?php echo get_the_title( get_the_ID() ); ?>
        </h3>

        <?php the_content(); ?>

        <?php
				if ( count( $amenities ) > 0 || count( $amenities_icons ) > 0 ) :
					?>
        <div class="amenities-div">
            <?php echo do_shortcode( '[amenities]' ); ?>
        </div>
        <?php endif; ?>

        <div class="book-btns">
            <div style="display: flex; grid-gap: 20px; width: 100%; margin: auto;">
                <a href="<?php echo get_the_permalink( get_the_ID() ); ?>" class="book-btn ct-link-button">View
                    Property</a>
            </div>
        </div>
    </div>
</div>
<?php
			endwhile;
			$status['content'] = ob_get_clean();
		endif;
		$status['bedrooms']      = $_POST['bedrooms'];
		$status['numberOfPosts'] = $query->found_posts;
		wp_send_json( $status );
	}

	/**
	 * Property Available
	 */
	public function property_available() {
		if ( ! wp_verify_nonce( $_POST['nonce'], 'hrv-nonce' ) ) {
			wp_send_json( 'Nonce Error' );
		}
		$status                 = array();
		$hrv_admin              = new HRV_MLA_Admin( 'hrv_mla', '1.0.0' );
		$checkin                = date( 'd M Y', strtotime( $_REQUEST['checkin'] ) );
		$checkout               = date( 'd M Y', strtotime( $_REQUEST['checkout'] ) );
		$id                     = $_REQUEST['property_id'];
		$status['is_available'] = $hrv_admin->ciirus_is_property_available( get_field( 'ciirus_id', $id ), $checkin, $checkout );
		ob_start();
		$amenities       = get_field( 'amenities_list', $id );
		$amenities_icons = get_field( 'amenities_icons', $id );
        $ciirus_id = get_field( 'ciirus_id', $id );
		$datediff = strtotime( $checkout ) - strtotime( $checkin );
		$nights   = round( $datediff / ( 60 * 60 * 24 ) );

		$price         = $hrv_admin->ciirus_calculated_booking_price( $ciirus_id, $checkin, $nights );
		$cleaning_fees = $hrv_admin->ciirus_get_cleaning_fee( $ciirus_id, $nights );
       
		?>
<div class="property-result-wrap">
    <div class="img-wrap-property">
        <?php echo get_the_post_thumbnail( $id, 'full' ); ?>

    </div>
    <div class="property-results-content">
        <h3 class="property-result-title">
            <?php echo get_the_title( $id ); ?>
        </h3>

        <?php
				echo apply_filters( 'the_content', get_the_content( null, false, $id ) );
		?>

        <?php
		if ( $amenities || $amenities_icons ) :
			?>
        <div class="amenities-div">
            <?php echo do_shortcode( '[amenities id="' . $id . '"]' ); ?>
        </div>
        <?php endif; ?>

        <?php


		if ( $price && $price['total'] > 0 ) {
            print_r( $price );
			?>
        <div class="property-results-price">
            Price: <strong>&dollar;<?php echo round($price['total'], 2); ?></strong>
        </div>
        <?php } ?>





        <div class="book-btns">
            <?php
					$datediff = strtotime( $checkout ) - strtotime( $checkin );
					$nights   = round( $datediff / ( 60 * 60 * 24 ) );
			?>
            <div style="display: flex; grid-gap: 20px; width: 100%; margin: auto;">
                <?php if ( $datediff ) : ?>
                <a href="<?php echo get_the_permalink( $id ); ?>?id=<?php echo $id; ?>&date_checkin=<?php echo $checkin; ?>&date_checkout=<?php echo $checkout; ?>&nights=<?php echo $nights; ?>"
                    class="book-btn ct-link-button">View Property</a>
                <a href="/book-online?id=<?php echo $id; ?>&date_checkin=<?php echo $checkin; ?>&date_checkout=<?php echo $checkout; ?>&nights=<?php echo $nights; ?>"
                    class="book-btn blue-btn ct-link-button">Book Property</a>
                <?php else : ?>
                <a href="<?php echo get_the_permalink( $id ); ?>" class="book-btn ct-link-button">View Property</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php
		$status['content'] = ob_get_clean();
		wp_send_json( $status );
	}

	public function hrv_percentage_tax_price( $price, $percent ) {
		return ( $percent / 100 ) * $price;
	}

	public function hrv_get_property_rates( $id, $checkin, $checkout ) {
		$hrv_public  = new HRV_MLA_Public( 'hrv_mla', HRV_MLA_VERSION );
		$hrv_admin   = new HRV_MLA_Admin( 'hrv_mla', HRV_MLA_VERSION );
		$datediff    = strtotime( $checkout ) - strtotime( $checkin );
		$nights      = round( $datediff / ( 60 * 60 * 24 ) );
		$total_rates = 0;

		if ( get_field( 'api_price', $id ) ) {
			$api_get_price       = $hrv_admin->ciirus_get_property_rates( get_field( 'ciirus_id', $id ), $checkin, $nights );
			$cleaning_fees       = $hrv_admin->ciirus_get_cleaning_fee( get_field( 'ciirus_id', $id ), $nights );
			$propertyTaxRatesApi = $hrv_admin->ciirus_get_tax_rates( get_field( 'ciirus_id', $id ) );
			$propertyTaxRates    = $propertyTaxRatesApi['total_rates'];
			$bookingprice        = $api_get_price['total_rates'];
			$total_rates         = round( $api_get_price['total_rates'] + $this->hrv_percentage_tax_price( $api_get_price['total_rates'], $propertyTaxRates ) + $cleaning_fees, 2 );
		} else {
			$price_cat_ID = wp_get_post_terms( $id, 'price_categories' );
			$currentprice = $hrv_public->compute_price( $price_cat_ID[0]->term_id, $checkin );
			$total_rates  = $currentprice * $nights;
		}

		return round( $total_rates );
	}

	/**
	 * Book Property
	 */
	public function book_property() {
		if ( ! wp_verify_nonce( $_POST['nonce'], 'hrv-nonce' ) ) {
			wp_send_json( 'Nonce Error' );
		}
		$hrv_admin              = new HRV_MLA_Admin( $this->plugin_name, $this->version );
		$secret                 = get_field( 'testing', 'option' ) ? get_field( 'test_secret_key', 'option' ) : get_field( 'live_secret_key', 'option' );
		$stripe                 = new \Stripe\StripeClient( $secret );
		$startdate              = date( 'Y-m-d', strtotime( $_POST['startdate'] ) );
		$enddate                = date( 'Y-m-d', strtotime( $_POST['enddate'] ) );
		$firstname              = $_POST['firstname'];
		$surname                = $_POST['surname'];
		$email                  = $_POST['email'];
		$adults                 = $_POST['adults'];
		$address1               = $_POST['address1'];
		$bedrooms               = $_POST['bedrooms'];
		$children               = $_POST['children'];
		$city                   = $_POST['city'];
		$nights                 = $_POST['nights'];
		$birthDate              = $_POST['birthDate'];
		$phone                  = $_POST['phone'];
		$property               = $_POST['property'];
		$owner_id               = $_POST['owner_id'];
		$state                  = $_POST['state'];
		$zip                    = $_POST['zip'];
		$total_price            = $_POST['totalPrice'];
		$property_owner_email   = $_POST['property_owner_email'];
		$extracostname          = isset( $_POST['extracostname'] ) ? explode( ',', $_POST['extracostname'] ) : array();
		$extracostprice         = isset( $_POST['extracostprice'] ) ? explode( ',', $_POST['extracostprice'] ) : array();
		$extracostownerpercent  = isset( $_POST['extracostownerpercent'] ) ? explode( ',', $_POST['extracostownerpercent'] ) : array();
		$extracostoriginalprice = isset( $_POST['extracostoriginalprice'] ) ? explode( ',', $_POST['extracostoriginalprice'] ) : array();
		$ownerbookingpercent    = $_POST['ownerbookingpercent'];
		$total_room_rate        = $_POST['totalRoomRate'];
		$owner_price            = $_POST['ownerPrice'];
		$owner_name             = $_POST['ownerName'];
		$bookingprice           = $_POST['bookingprice'];
		$due_date               = $_POST['dueDate'];
		$deposit_compute        = $total_price * 0.10;
		$deposit_price          = $deposit_compute > $hrv_admin->deposit ? $hrv_admin->deposit : $deposit_compute;
		$rental_price           = ( $bookingprice * $nights ) + $owner_price;
		$api_price              = $_POST['apiPrice'] == 1 ? 1 : 0;
		$api_profit             = $_POST['apiProfit'];
		$total_extra            = $_POST['totalExtra'];

		// $charge = $stripe->charges->create(
		// array(
		// 'amount'      => $total_price * 100,
		// 'currency'    => 'gbp',
		// 'source'      => $_POST['token'],
		// 'description' => $nights . ' nights booking of ' . get_the_title( $property ),
		// )
		// );

		$payment_intents = $stripe->paymentIntents->create(
			array(
				'amount'               => round( $deposit_price, 2 ) * 100,
				'currency'             => 'gbp',
				'payment_method_types' => array( 'card' ),
				'description'          => $nights . ' nights booking of ' . get_the_title( $property ),
				'capture_method'       => 'manual',
				'payment_method'       => $_POST['token'],
				'confirm'              => true,
			)
		);

		if ( $payment_intents ) {
			// $payment_intents->capture();
			$return['params']  = $_POST;
			$args              = array(
				'post_type'   => 'bookings',
				'post_title'  => $firstname . ' ' . $surname . ' - (' . $startdate . ' - ' . $enddate . ')',
				'post_status' => 'publish',
			);
			$booking_id        = wp_insert_post( $args );
			$return['booking'] = $booking_id;
			$property_link     = array(
				'title'  => get_the_title( $property ),
				'url'    => get_the_permalink( $property ),
				'target' => '_blank',
			);

			if ( $booking_id ) {
				wp_update_post(
					array(
						'ID'         => $booking_id,
						'post_title' => 'HRV-' . $booking_id . ' - ' . $firstname . ' ' . $surname,
					)
				);
				update_field( 'first_name', $firstname, $booking_id );
				update_field( 'surname', $surname, $booking_id );
				update_field( 'email', $email, $booking_id );
				update_field( 'phone', $phone, $booking_id );
				update_field( 'arrival_date', str_replace( '-', '', $startdate ), $booking_id );
				update_field( 'end_date', str_replace( '-', '', $enddate ), $booking_id );
				update_field( 'no_of_nights', $nights, $booking_id );
				update_field( 'no_of_bedrooms', $bedrooms, $booking_id );
				update_field( 'children', $children, $booking_id );
				update_field( 'adult', $adults, $booking_id );
				update_field( 'address_line_1', $address1, $booking_id );
				update_field( 'state', $state, $booking_id );
				update_field( 'postalzipcode', $zip, $booking_id );
				update_field( 'city', $city, $booking_id );
				update_field( 'property', get_the_title( $property ), $booking_id );
				update_field( 'total_price', $total_price, $booking_id );
				update_field( 'property_link', $property_link, $booking_id );
				update_field( 'property_owner_percentage', $ownerbookingpercent, $booking_id );
				update_field( 'property_post', array( $property ), $booking_id );
				update_field( 'stripe_charge_id', $payment_intents->id, $booking_id );
				update_field( 'booking_season_price', $bookingprice, $booking_id );
				update_field( 'booking_property_owner', $owner_id, $booking_id );
				update_field( 'booking_property_owner', $owner_id, $booking_id );
				update_field( 'api_price', $api_price, $booking_id );
				update_field( 'payment_status', 'deposit', $booking_id );
				update_post_meta( $booking_id, 'payment_email_sent', 'no' );

				if ( $api_profit ) {
					update_field( 'api_profit', $api_profit, $booking_id );
				}

				if ( $api_price == 1 ) {
					update_field( 'ciirus_room_price', (int) $total_room_rate - (int) $api_profit, $booking_id );
					update_field( 'total_ciirus_price_with_comission', $total_room_rate, $booking_id );
					update_field( 'extra_price', $total_extra, $booking_id );
					update_field( 'total_price_and_extras', $total_price, $booking_id );
				}

				$rows = array();

				/**
				 * Other Addons
				 */
				$other_addons = '';
				if ( ! empty( $extracostname ) ) {
					foreach ( $extracostname as $key => $cost ) {
						$rows[]        = array(
							'extra_cost'       => $cost,
							'price'            => $extracostprice[ $key ],
							'owner_percentage' => $extracostownerpercent[ $key ],
						);
						$other_addons .= '<tr>
							<td>' . $cost . '</td>
							<td colspan="2">$' . $extracostprice[ $key ] . '</td>
							</tr>';
					}
				}

				update_field( 'field_61fbad0ce3c30', $rows, $booking_id );

				if ( $api_price == 1 ) {
                    $admin_email = get_option( 'admin_email' );
					$booking_api_details = '<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema"
    xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
    <soap:Body>
        <MakeBooking xmlns="http://xml.ciirus.com/">
            <APIUsername>' . $hrv_admin->ciirus_user . '</APIUsername>
            <APIPassword>' . $hrv_admin->ciirus_password . '</APIPassword>
            <BD>
                <ArrivalDate>' . date( 'd M Y', strtotime( $_POST['startdate'] ) ) . '</ArrivalDate>
                <DepartureDate>' . date( 'd M Y', strtotime( $_POST['enddate'] ) ) . '</DepartureDate>
                <PropertyID>' . get_field( 'ciirus_id', $property ) . '</PropertyID>
                <GuestName>' . $firstname . ' ' . $surname . '</GuestName>
                <GuestEmailAddress>' . $admin_email . '</GuestEmailAddress>
                <GuestTelephone>' . $phone . '</GuestTelephone>
                <GuestAddress>' . $address1 . '</GuestAddress>
                <GuestList>
                    <sGuests>
                        <Name>' . $firstname . ' ' . $surname . '</Name>
                        <Age>-1</Age>
                    </sGuests>
                </GuestList>
            </BD>
        </MakeBooking>
    </soap:Body>
</soap:Envelope>';

$booking_api = $hrv_admin->ciirus_make_booking( $booking_api_details );
if ( 'true' == $booking_api['BookingPlaced'] ) {
$return['BookingID'] = $booking_api['BookingID'];
$return['TotalAmountIncludingTax'] = $booking_api['TotalAmountIncludingTax'];
$return['BookingPlaced'] = $booking_api['BookingPlaced'];
update_field( 'ciirus_api_booking_id', $booking_api['BookingID'], $booking_id );
update_field( 'ciirus_total_amount_inc_tax', $booking_api['TotalAmountIncludingTax'], $booking_id );
}
$return['booking_api'] = $booking_api;

}

$arrival_date = strtotime( $startdate );
$today = time();
$diff = $arrival_date - $today;
$days_left = floor( $diff / ( 60 * 60 * 24 ) );
// $request_payment_email_content = $hrv_admin->to_customer_email_hrv_content_body();

/**
* Other Addons Owner
*/
$extra_owner_total = 0;
$other_addons_owner = '';
if ( ! empty( $extracostname ) ) {
foreach ( $extracostname as $key => $cost ) {
// $compute = ( 1 - ( (int) $extracostownerpercent[ $key ] / 100 ) ) * $extracostprice[ $key ];
$compute = $extracostoriginalprice[ $key ] * $nights;
$extra_owner_total = $extra_owner_total + $compute;

$other_addons_owner .= '<tr class="item">
    <td>' . $cost . '</td>
    <td>$' . $compute . '</td>
</tr>';
}
}

if ( $api_price == 0 ) {
$owner_total_price = $total_room_rate + $extra_owner_total;
$profit = $total_price - $owner_total_price;
}

if ( $days_left <= $hrv_admin->days_to_notify ) {
    $request_payment_email_content = $hrv_admin->get_request_payment_content();
    $request_payment_email_content = str_replace( 'BOOKING_ID', 'HRV-' . $booking_id, $request_payment_email_content );
    $request_payment_email_content = str_replace( 'INVOICE_DATE', date( 'd/M/Y' ), $request_payment_email_content );
    $directions_acf = get_field( 'directions_from_airport', $property );
    if ( $api_price == 1 ) {
    $request_payment_email_content = str_replace(
    '[BOOKING_DETAILS]',
    $hrv_admin->booking_details_content_api(),
    $request_payment_email_content
    );
    $request_payment_email_content = str_replace( 'TOTAL_ROOM_RATE', $total_room_rate, $request_payment_email_content );
    } else {
    $request_payment_email_content = str_replace(
    '[BOOKING_DETAILS]',
    $hrv_admin->booking_details_content(),
    $request_payment_email_content
    );
    }
    $request_payment_email_content = str_replace( 'NO_ADULTS', $adults, $request_payment_email_content );
    $request_payment_email_content = str_replace( 'NO_NIGHTS', $nights, $request_payment_email_content );
    $request_payment_email_content = str_replace( 'NO_CHILDREN', $children, $request_payment_email_content );
    $request_payment_email_content = str_replace(
    '[GUEST_NAME]',
    $firstname . ' ' . $surname,
    $request_payment_email_content
    );
    $request_payment_email_content = str_replace(
    'DEPARTURE_DATE',
    date( 'd/M/Y', strtotime( $enddate ) ),
    $request_payment_email_content
    );
    $request_payment_email_content = str_replace(
    'ARRIVAL_DATE',
    date( 'd/M/Y', strtotime( $startdate ) ),
    $request_payment_email_content
    );
    $request_payment_email_content = str_replace(
    'PROPERTY_NAME',
    get_the_title( $property ),
    $request_payment_email_content
    );
    $request_payment_email_content = str_replace( 'TOTAL_PRICE', $total_price, $request_payment_email_content );
    $request_payment_email_content = str_replace( 'RENT_PRICE', $bookingprice, $request_payment_email_content );
    $request_payment_email_content = str_replace( 'HOME_RENTAL_PRICE', $rental_price, $request_payment_email_content );
    $request_payment_email_content = str_replace( 'TOTAL_DEPOSIT', $deposit_price, $request_payment_email_content );
    $request_payment_email_content = str_replace(
    'TOTAL_BALANCE',
    $total_price - $deposit_price,
    $request_payment_email_content
    );
    $request_payment_email_content = str_replace( 'OTHER_ADDONS', $other_addons, $request_payment_email_content );
    $request_payment_email_content = str_replace(
    'DIRECTIONS_FROM_AIRPORT',
    $directions_acf,
    $request_payment_email_content
    );
    $request_payment_email_content = str_replace( 'DUE_DATE', $due_date, $request_payment_email_content );
    $hrv_admin->send_hrv_email( $email, 'Request for payment', $request_payment_email_content );
    update_post_meta( $booking_id, 'payment_email_sent', 'yes' );
    }

    /**
    * Send Email to Admin
    */
    $admin_email_subject = $hrv_admin->get_to_admin_email_subject() ? $hrv_admin->get_to_admin_email_subject() : 'New
    Booking';
    $admin_email_subject = str_replace( '[REF_#]', $booking_id, $admin_email_subject );
    $admin_email_subject = str_replace( '[GUEST_NAME]', $firstname . ' ' . $surname, $admin_email_subject );
    $admin_email_subject = str_replace( '[OWNER_NAME]', $owner_name, $admin_email_subject );
    $get_to_admin_email_content = $hrv_admin->get_to_admin_email_content();
    if ( $api_price == 1 ) {
    $get_to_admin_email_content = str_replace(
    '[BOOKING_DETAILS]',
    $hrv_admin->booking_admin_details_content_api(),
    $get_to_admin_email_content
    );
    $get_to_admin_email_content = str_replace( 'TOTAL_ROOM_RATE', $total_room_rate, $get_to_admin_email_content );
    $get_to_admin_email_content = str_replace( '[PROFIT]', $api_profit, $get_to_admin_email_content );
    } else {
    $get_to_admin_email_content = str_replace(
    '[BOOKING_DETAILS]',
    $hrv_admin->booking_admin_details_content(),
    $get_to_admin_email_content
    );
    }
    $get_to_admin_email_content = str_replace( 'BOOKING_ID', 'HRV-' . $booking_id, $get_to_admin_email_content );
    $get_to_admin_email_content = str_replace( 'NO_ADULTS', $adults, $get_to_admin_email_content );
    $get_to_admin_email_content = str_replace( 'NO_NIGHTS', $nights, $get_to_admin_email_content );
    $get_to_admin_email_content = str_replace( 'NO_CHILDREN', $children, $get_to_admin_email_content );
    $get_to_admin_email_content = str_replace(
    '[GUEST_NAME]',
    $firstname . ' ' . $surname,
    $get_to_admin_email_content
    );
    $get_to_admin_email_content = str_replace(
    'DEPARTURE_DATE',
    date( 'd/M/Y', strtotime( $enddate ) ),
    $get_to_admin_email_content
    );
    $get_to_admin_email_content = str_replace(
    'ARRIVAL_DATE',
    date( 'd/M/Y', strtotime( $startdate ) ),
    $get_to_admin_email_content
    );
    $get_to_admin_email_content = str_replace(
    'PROPERTY_NAME',
    get_the_title( $property ),
    $get_to_admin_email_content
    );
    $get_to_admin_email_content = str_replace( 'TOTAL_PRICE', $total_price, $get_to_admin_email_content );
    $get_to_admin_email_content = str_replace( 'TOTAL_OWNER_PRICE', $owner_total_price, $get_to_admin_email_content );
    $get_to_admin_email_content = str_replace( 'RENT_PRICE', $bookingprice, $get_to_admin_email_content );
    $get_to_admin_email_content = str_replace( 'HOME_RENTAL_PRICE', $rental_price, $get_to_admin_email_content );
    $get_to_admin_email_content = str_replace(
    'OWNER_RENTAL_PRICE',
    $bookingprice * $nights,
    $get_to_admin_email_content
    );
    $get_to_admin_email_content = str_replace( 'INVOICE_DATE', date( 'd/M/Y' ), $get_to_admin_email_content );
    $get_to_admin_email_content = str_replace( 'TOTAL_DEPOSIT', $deposit_price, $get_to_admin_email_content );
    $get_to_admin_email_content = str_replace(
    'TOTAL_BALANCE',
    $total_price - $deposit_price,
    $get_to_admin_email_content
    );
    $get_to_admin_email_content = str_replace( 'OTHER_ADDONS', $other_addons, $get_to_admin_email_content );
    $get_to_admin_email_content = str_replace( 'OWNER_ADDONS', $other_addons_owner, $get_to_admin_email_content );
    $get_to_admin_email_content = str_replace( 'OWNER_ADDONS', $other_addons_owner, $get_to_admin_email_content );
    $get_to_admin_email_content = str_replace( '[PROFIT]', $profit, $get_to_admin_email_content );
    $get_to_admin_email_content = str_replace( 'DUE_DATE', $due_date, $get_to_admin_email_content );

    $hrv_admin->send_hrv_email(
    get_field( 'admin_email', 'option' ),
    $admin_email_subject,
    $get_to_admin_email_content
    );

    /**
    * Send Email to Customer
    */
    $customer_email_subject = $hrv_admin->get_to_customer_email_subject() ? $hrv_admin->get_to_customer_email_subject()
    : 'Thanks for booking';
    $customer_email_subject = str_replace( '[REF_#]', $booking_id, $customer_email_subject );
    $customer_email_subject = str_replace( '[OWNER_NAME]', $owner_name, $customer_email_subject );
    $customer_email_subject = str_replace( '[GUEST_NAME]', $firstname . ' ' . $surname, $customer_email_subject );
    $email_to_customer = $hrv_admin->get_to_customer_email_content();
    if ( $api_price == 1 ) {
    $email_to_customer = str_replace(
    '[BOOKING_DETAILS]',
    $hrv_admin->booking_details_content_api(),
    $email_to_customer
    );
    $email_to_customer = str_replace( 'TOTAL_ROOM_RATE', $total_room_rate, $email_to_customer );
    } else {
    $email_to_customer = str_replace( '[BOOKING_DETAILS]', $hrv_admin->booking_details_content(), $email_to_customer );
    }
    $email_to_customer = str_replace( 'BOOKING_ID', 'HRV-' . $booking_id, $email_to_customer );
    $email_to_customer = str_replace( 'NO_ADULTS', $adults, $email_to_customer );
    $email_to_customer = str_replace( 'NO_NIGHTS', $nights, $email_to_customer );
    $email_to_customer = str_replace( 'NO_CHILDREN', $children, $email_to_customer );
    $email_to_customer = str_replace( '[GUEST_NAME]', $firstname . ' ' . $surname, $email_to_customer );
    $email_to_customer = str_replace( 'DEPARTURE_DATE', date( 'd/M/Y', strtotime( $enddate ) ), $email_to_customer );
    $email_to_customer = str_replace( 'ARRIVAL_DATE', date( 'd/M/Y', strtotime( $startdate ) ), $email_to_customer );
    $email_to_customer = str_replace( 'PROPERTY_NAME', get_the_title( $property ), $email_to_customer );
    $email_to_customer = str_replace( 'TOTAL_PRICE', $total_price, $email_to_customer );
    $email_to_customer = str_replace( 'RENT_PRICE', $bookingprice, $email_to_customer );
    $email_to_customer = str_replace( 'HOME_RENTAL_PRICE', $rental_price, $email_to_customer );
    $email_to_customer = str_replace( 'INVOICE_DATE', date( 'd/M/Y' ), $email_to_customer );
    $email_to_customer = str_replace( 'TOTAL_DEPOSIT', $deposit_price, $email_to_customer );
    $email_to_customer = str_replace( 'TOTAL_BALANCE', $total_price - $deposit_price, $email_to_customer );
    $email_to_customer = str_replace( 'OTHER_ADDONS', $other_addons, $email_to_customer );
    $email_to_customer = str_replace( 'DUE_DATE', $due_date, $email_to_customer );
    $email_to_customer = str_replace( '[GUEST_NAME]', $firstname . ' ' . $surname, $email_to_customer );
    $hrv_admin->send_hrv_email( $email, $customer_email_subject, $email_to_customer );

    /**
    * Send Email to Property Owner
    */
    $email_to_owner = $hrv_admin->get_to_owner_email_content();
    if ( $api_price == 1 ) {
    $email_to_owner = str_replace(
    '[OWNER_DETAILS]',
    $hrv_admin->booking_owner_details_content_api(),
    $email_to_owner
    );
    } else {
    $email_to_owner = str_replace( '[OWNER_DETAILS]', $hrv_admin->booking_owner_details_content(), $email_to_owner );
    }

    $email_to_owner = str_replace( 'BOOKING_ID', 'HRV-' . $booking_id, $email_to_owner );
    $email_to_owner = str_replace( 'NO_ADULTS', $adults, $email_to_owner );
    $email_to_owner = str_replace( 'NO_NIGHTS', $nights, $email_to_owner );
    $email_to_owner = str_replace( 'NO_CHILDREN', $children, $email_to_owner );
    $email_to_owner = str_replace( 'DUE_DATE', $due_date, $email_to_owner );
    $email_to_owner = str_replace( '[GUEST_NAME]', $firstname . ' ' . $surname, $email_to_owner );
    $email_to_owner = str_replace( 'DEPARTURE_DATE', date( 'd/M/Y', strtotime( $enddate ) ), $email_to_owner );
    $email_to_owner = str_replace( 'ARRIVAL_DATE', date( 'd/M/Y', strtotime( $startdate ) ), $email_to_owner );
    $email_to_owner = str_replace( 'PROPERTY_NAME', get_field( 'address', $property ), $email_to_owner );
    if ( $api_price == 1 ) {
    $email_to_owner = str_replace( 'TOTAL_ROOM_RATE', $total_room_rate, $email_to_owner );
    $email_to_owner = str_replace( 'TOTAL_PRICE', $total_price - $api_profit, $email_to_owner );
    } else {
    $email_to_owner = str_replace( 'TOTAL_PRICE', $owner_total_price, $email_to_owner );
    }
    $email_to_owner = str_replace( 'RENT_PRICE', $total_room_rate, $email_to_owner );
    $email_to_owner = str_replace( 'HOME_RENTAL_PRICE', $bookingprice * $nights, $email_to_owner );
    $email_to_owner = str_replace( 'OTHER_ADDONS', $other_addons_owner, $email_to_owner );
    $email_to_owner = str_replace( 'INVOICE_DATE', date( 'd/M/Y' ), $email_to_owner );
    $owner_email_subject = $hrv_admin->get_to_owner_email_subject() ? $hrv_admin->get_to_owner_email_subject() : 'Your
    property is booked.';
    $owner_email_subject = str_replace( '[REF_#]', $booking_id, $owner_email_subject );
    $owner_email_subject = str_replace( '[GUEST_NAME]', $firstname . ' ' . $surname, $owner_email_subject );
    $owner_email_subject = str_replace( '[OWNER_NAME]', $owner_name, $owner_email_subject );
    $email_to_owner_content = str_replace( '[OWNER_NAME]', $owner_name, $email_to_owner );

    if ( $property_owner_email ) {
    $hrv_admin->send_hrv_email( $property_owner_email, $owner_email_subject, $email_to_owner_content );
    }

    if ( $api_price == 0 ) {
    update_field( 'owner_total_price', $owner_total_price, $booking_id );
    update_field( 'total_profit', $profit, $booking_id );
    }

    if ( get_option( 'xero_access_token' ) && false ) {
    $contact_array = array(
    'name' => $firstname . ' ' . $surname,
    'email' => $email,
    'phone' => $phone,
    );

    $contact_id = $hrv_admin->xero_contact( $contact_array );

    $booking_details = array(
    'booking_id' => $booking_id,
    'property' => get_the_title( $property ),
    'total_price' => $total_price,
    'start_date' => $startdate,
    'end_date' => $enddate,
    );

    $contact = $hrv_admin->add_xero_invoice( $contact_id, $booking_details );
    $return['contact'] = $contact;
    }

    if ( $this->get_mailchimp_api() && $this->get_mailchimp_list_id() ) {
    $MailChimp = new MailChimp( $this->get_mailchimp_api() );
    $list_id = $this->get_mailchimp_list_id();

    $subscribed = $MailChimp->post(
    "lists/$list_id/members",
    array(
    'email_address' => $email,
    'merge_fields' => array(
    'FNAME' => $firstname,
    'LNAME' => $surname,
    ),
    'status' => 'subscribed',
    )
    );
    $return['mailchimp'] = $subscribed['status'];
    }
    }
    wp_send_json( $return );
    }
    wp_send_json( 'ERROR' );
    }



    /**
    * Compute season price Property
    */

    public function compute_season_price() {
    if ( ! wp_verify_nonce( $_POST['nonce'], 'hrv-nonce' ) ) {
    wp_send_json( 'Nonce Error' );
    }

    $checkin_date = date( 'm-d', strtotime( $_POST['checkin'] ) );
    $seasons = new WP_Query(
    array(
    'post_type' => 'seasons',
    )
    );

    $price = 0;
    if ( $seasons->have_posts() ) :
    while ( $seasons->have_posts() ) :
    $seasons->the_post();
    $start_date = date( get_field( 'date_from' ) );
    $end_date = date( get_field( 'date_end' ) );
    if ( $end_date >= $checkin_date && $start_date <= $checkin_date ) { if ( have_rows( 'category' ) ) : while (
        have_rows( 'category' ) ) : the_row(); $cat=get_sub_field( 'categories' ); if ( $cat==$price_category ) {
        $price=get_sub_field( 'price' ); } endwhile; endif; } endwhile; wp_reset_postdata(); endif; wp_send_json( $price
        ); } /** * Compute Price based on season. * * @param string $price_category The price category */ public
        function compute_price( $price_category, $checkin ) { $current_date=date( 'm-d' , strtotime( $checkin ) );
        $seasons=new WP_Query( array( 'post_type'=> 'seasons',
        )
        );
        $price = 0;
        if ( $seasons->have_posts() ) :
        while ( $seasons->have_posts() ) :
        $seasons->the_post();
        $start_date = date( get_field( 'date_from' ) );
        $end_date = date( get_field( 'date_end' ) );
        if ( $end_date >= $current_date && $start_date <= $current_date ) { if ( have_rows( 'category' ) ) : while (
            have_rows( 'category' ) ) : the_row(); $cat=get_sub_field( 'categories' ); if ( $cat==$price_category ) {
            $price=get_sub_field( 'price' ); } endwhile; endif; } endwhile; wp_reset_postdata(); endif; return $price; }
            public function add_shortcodes( $atts ) { add_shortcode( 'hrv_booking_form' , function () { ob_start();
            include_once 'partials/bookingform.php' ; $content=ob_get_clean(); return $content; } );
            add_shortcode( 'check_availability' , function () { ob_start();
            include_once 'partials/check-availability.php' ; $content=ob_get_clean(); return $content; } );
            add_shortcode( 'search_booking_form' , function () { ob_start(); include_once 'partials/searchform.php' ;
            $content=ob_get_clean(); return $content; } ); add_shortcode( 'amenities' , array( $this, 'amenities_output'
            , ) ); } public function amenities_output( $atts ) { $atts=shortcode_atts( array( 'id'=> get_the_ID(),
            ),
            $atts,
            'amenities'
            );

            ob_start();
            $amenities = get_field( 'amenities_list', $atts['id'] );
            $amenities_icons = get_field( 'amenities_icons', $atts['id'] );
            if ( $amenities || $amenities_icons ) :
            ?>
            <style>
            ul.aminities-list {
                margin: 0;
                padding: 0;
                display: flex;
                list-style: none;
                grid-gap: 1em;
                font-size: 12px;
                width: 100%;
            }

            ul.amenities-text {
                margin-top: 0;
                font-weight: 600;
                list-style: none;
                padding: 0;
            }


            ul.aminities-list img {
                height: 30px;
                object-fit: contain;
                margin-right: 1rem;
            }

            ul.aminities-list li {
                display: flex;
                align-items: center;
                line-height: 1.6;
                text-align: left;
            }
            </style>
            <?php if ( $amenities ) : ?>
            <ul class="amenities-text">
                <?php $amenities_text = wp_list_pluck( $amenities, 'label' ); ?>
                <?php foreach ( $amenities_text as $item ) : ?>
                <li><?php echo $item; ?></li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
            <?php if ( $amenities_icons ) : ?>
            <ul class="aminities-list">
                <?php foreach ( $amenities_icons as $item ) : ?>
                <?php if ( 'wifi' === $item['value'] ) { ?>
                <li><img src="<?php echo home_url(); ?>/wp-content/uploads/2023/01/wifi.png"
                        alt="<?php echo $item['label']; ?>" title="<?php echo $item['label']; ?>"></li>
                <?php } ?>
                <?php if ( 'fullaircon' === $item['value'] ) { ?>
                <li><img src="<?php echo home_url(); ?>/wp-content/uploads/2023/01/air-conditioner.png"
                        alt="<?php echo $item['label']; ?>" title="<?php echo $item['label']; ?>"></li>
                <?php } ?>
                <?php if ( 'gasbbq' === $item['value'] ) { ?>
                <li><img src="<?php echo home_url(); ?>/wp-content/uploads/2023/01/gas-bbq.png"
                        alt="<?php echo $item['label']; ?>" title="<?php echo $item['label']; ?>"></li>
                <?php } ?>
                <?php if ( 'soutwestpool' === $item['value'] ) { ?>
                <li><img src="<?php echo home_url(); ?>/wp-content/uploads/2023/01/swimming-pool.png"
                        alt="<?php echo $item['label']; ?>" title="<?php echo $item['label']; ?>">
                </li>
                <?php } ?>
                <?php if ( 'pool' === $item['value'] ) { ?>
                <li><img src="<?php echo home_url(); ?>/wp-content/uploads/2023/01/jacuzzi.png"
                        alt="<?php echo $item['label']; ?>" title="<?php echo $item['label']; ?>">
                </li>
                <?php } ?>
                <?php if ( 'hottub' === $item['value'] ) { ?>
                <li><img src="<?php echo home_url(); ?>/wp-content/uploads/2023/01/hot-bath.png"
                        alt="<?php echo $item['label']; ?>" title="<?php echo $item['label']; ?>">
                </li>
                <?php } ?>
                <?php if ( 'gamesroom' === $item['value'] ) { ?>
                <li><img src="<?php echo home_url(); ?>/wp-content/uploads/2023/04/table-tennis.png"
                        alt="<?php echo $item['label']; ?>" title="<?php echo $item['label']; ?>">
                </li>
                <?php } ?>
                <?php endforeach; ?>
            </ul>
            <?php
				endif;
			endif;
			return ob_get_clean();
			}

			public function has_no_search_dates() {
				 ob_start();
				?>

            <div class="villa-search-results-wrap searching" id="villaResults">
                <div id="loading">
                    <div class="loading-flex">
                        <h3>Searching properties <span id="percentStatus"></span></h3>
                        <div class="lds-spinner">
                            <div></div>
                            <div></div>
                            <div></div>
                            <div></div>
                            <div></div>
                            <div></div>
                            <div></div>
                            <div></div>
                            <div></div>
                            <div></div>
                            <div></div>
                            <div></div>
                        </div>
                    </div>
                </div>
            </div>
            <?php
					add_action(
						'wp_footer',
						function () {
							?>
            <script>
            const villaResults = document.getElementById('villaResults');
            const fx = [];
            const propertyIds = HRV.properties_result_ids;
            const percentStatus = document.getElementById('percentStatus');
            const loading = document.getElementById('loading');
            const form = new FormData();
            form.append('action', 'get_all_property_details');
            form.append('nonce', HRV.nonce);
            <?php if ( isset( $_REQUEST['bedrooms'] ) ) : ?>
            form.append(
                'bedrooms', <?php echo $_REQUEST['bedrooms']; ?>
            );
            <?php endif; ?>
            const params = new URLSearchParams(form);

            fetch(HRV.ajax_url, {
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
                    console.log(data);
                    loading.style.display = "none";
                    villaResults.classList.remove('searching');
                    villaResults.insertAdjacentHTML('beforeend', data.content);

                    if (data.numberOfPosts < 1) {
                        document.querySelector('.noresults').style.display = 'flex';
                        villaResults.style.display = 'none';
                    } else {}
                })
                .catch((error) => {
                    console.error(error);
                });
            </script>
            <?php
						},
						99
					);
				return ob_get_clean();
			}


			public function has_search_dates() {
				ob_start();
				?>
            <div class="vsr-container" style="position: relative;">
                <div id="loading">
                    <div class="loading-flex">
                        <h3>Searching properties <span id="percentStatus"></span></h3>
                        <div class="lds-spinner">
                            <div></div>
                            <div></div>
                            <div></div>
                            <div></div>
                            <div></div>
                            <div></div>
                            <div></div>
                            <div></div>
                            <div></div>
                            <div></div>
                            <div></div>
                            <div></div>
                        </div>
                    </div>
                </div>
                <div class="villa-search-results-wrap searching" id="villaResults">

                </div>
            </div>




            <?php
					add_action(
						'wp_footer',
						function () {
							?>
            <script>
            const villaResults = document.getElementById('villaResults');
            const propertyIds = HRV.properties_result_ids;
            const percentStatus = document.getElementById('percentStatus');
            const loading = document.getElementById('loading');
            const localResults = localStorage.getItem('propertyResults');
            const localResultsUrl = localStorage.getItem('propertyResultsUrl');

            async function is_available(id) {
                const form = new FormData();
                form.append('action', 'property_available');
                form.append('nonce', HRV.nonce);
                form.append('checkin', '<?php echo $_REQUEST['date_checkin']; ?>');
                form.append('checkout', '<?php echo $_REQUEST['date_checkout']; ?>');
                form.append('property_id', id);
                const params = new URLSearchParams(form);

                try {
                    console.log('starting...');
                    const response = await fetch(HRV.ajax_url, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                            'Cache-Control': 'no-cache',
                        },
                        body: params,
                    });

                    const data = await response.json();

                    console.log(data);

                    if (data.is_available === 'available') {
                        villaResults.insertAdjacentHTML('beforeend', data.content);
                        return {
                            id: id,
                            status: data.is_available
                        }
                    }
                } catch (error) {
                    console.error(error);
                }

                return {
                    id: id,
                    status: 'none'
                };
            }

            if (localResults && localResultsUrl === window.location.search) {

                villaResults.classList.remove('searching');

                villaResults.insertAdjacentHTML('beforeend', localResults);

                loading.style.display = 'none';

            } else {

                if (propertyIds.length) {
                    const fxPromises = propertyIds.map((id) => is_available(id));

                    Promise.all(fxPromises)
                        .then((values) => {
                            const filteredValues = values.filter((element) => element.status !== 'none');
                            console.log(filteredValues);

                            loading.style.display = 'none';
                            villaResults.classList.remove('searching');

                            if (!filteredValues.length) {
                                document.querySelector('.noresults').style.display = 'flex';
                                villaResults.style.display = 'none';
                            }

                            console.log('DONE');
                            const propertyResults = document.getElementById('villaResults').innerHTML;
                            localStorage.setItem('propertyResults', propertyResults);
                            localStorage.setItem('propertyResultsUrl', window.location.search);
                        })
                        .catch((error) => {
                            console.error(error);
                        });


                } else {
                    document.querySelector('.noresults').style.display = 'flex';
                    villaResults.style.display = 'none';
                    loading.style.display = 'none';
                }

            }
            </script>
            <?php
						},
						99
					);
				return ob_get_clean();
			}


			public function search_results() {
				add_shortcode(
					'search_results',
					function () {
						if ( isset( $_REQUEST['date_checkin'] ) && ! empty( $_REQUEST['date_checkin'] ) && isset( $_REQUEST['date_checkout'] ) && ! empty( $_REQUEST['date_checkout'] ) ) {
							$return = $this->has_search_dates();
						} else {
							$return = $this->has_no_search_dates();
						}

						return $return;
					}
				);
			}

			public function contact_date_picker() {
				if ( is_page( 'contact-us' ) ) {
					?>
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                const addBtn = document.querySelectorAll('.wpcf7-field-group-add');
                const dates = document.querySelectorAll(
                    'input[name$="-date"], .wpcf7-text.date, [data-name="date"] > input');
                dates.forEach((date, i) => {
                    new Datepicker(date, {
                        minDate: 'tomorrow',
                        autohide: true,
                        format: 'dd M yyyy',
                    });
                });

                jQuery('body').on('wpcf7-field-groups/added', function() {
                    document.querySelectorAll('[data-name="date"] > input').forEach((date, i) => {
                        new Datepicker(date, {
                            minDate: 'tomorrow',
                            autohide: true,
                            format: 'dd M yyyy',
                        });
                    });
                });
            });
            </script>
            <?php
				}
			}



			public function search_result_ids() {
				$checkin      = $_POST['date_checkin'];
				$bedrooms     = $_POST['bedrooms'];
				$checkout     = $_POST['date_checkout'];
				$checkinDate  = date( 'Y-m-d', strtotime( $checkin ) );
				$datediff     = strtotime( $checkout ) - strtotime( $checkin );
				$nights       = round( $datediff / ( 60 * 60 * 24 ) );
				$hrv_admin    = new HRV_MLA_Admin( 'hrv_mla', '1.0.0' );
				$hrv_public   = new HRV_MLA_Public( 'hrv_mla', '1.0.0' );
				$property_ids = array();
				$args         = array(
					'post_type'   => 'properties',
					'numberposts' => -1,
					'meta_key'    => 'bedrooms',
					'meta_value'  => $bedrooms,
				);

				$query = new WP_Query( $args );
				if ( $query->have_posts() ) :
					while ( $query->have_posts() ) :
						$query->the_post();
						$id = get_field( 'ciirus_id' );
						if ( isset( $_POST['date_checkin'] ) ) {
							if ( $hrv_admin->ciirus_is_property_available( $id, $checkin, $checkout ) === 'available' ) {
								$property_ids[] = get_the_ID();
							}
						}
						endwhile;
					wp_reset_postdata();
					endif;
				return $property_ids;
			}

			public function mailchimp_test() {
				$MailChimp = new MailChimp( $this->get_mailchimp_api() );
				$result    = $MailChimp->get( 'lists' );
				echo '<pre>';
				print_r( $result['lists'][0]['id'] );
				echo '</pre>';
				$list_id = '2061e80bec';
				$result  = $MailChimp->post(
					"lists/$list_id/members",
					array(
						'email_address' => 'nyhynipa@vomoto.com',
						'merge_fields'  => array(
							'FNAME' => 'THIS',
							'LNAME' => 'TEST',
						),
						'status'        => 'subscribed',
					)
				);
				print_r( $result['status'] );
			}

}

?>