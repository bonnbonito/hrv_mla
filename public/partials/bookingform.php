<script src="https://js.stripe.com/v3/"></script>
<script>
var stripe = Stripe(HRV.stripe);

function registerElements(elements, className) {
    var formClass = '.' + className;
    var classSelector = document.querySelector(formClass);
    var form = classSelector.querySelector('form');
    var error = form.querySelector('.error');
    var errorMessage = error.querySelector('.message');

    function enableInputs() {
        Array.prototype.forEach.call(
            form.querySelectorAll(
                "input[type='text'], input[type='email'], input[type='tel']"
            ),
            function(input) {
                input.removeAttribute('disabled');
            }
        );
    }

    function disableInputs() {
        Array.prototype.forEach.call(
            form.querySelectorAll(
                "input[type='text'], input[type='email'], input[type='tel']"
            ),
            function(input) {
                input.setAttribute('disabled', 'true');
            }
        );
    }

    function triggerBrowserValidation() {
        // The only way to trigger HTML5 form validation UI is to fake a user submit
        // event.
        var submit = document.createElement('input');
        submit.type = 'submit';
        submit.style.display = 'none';
        form.appendChild(submit);
        submit.click();
        submit.remove();
    }

    // Listen for errors from each Element, and show error messages in the UI.
    var savedErrors = {};
    elements.forEach(function(element, idx) {
        element.on('change', function(event) {
            if (event.error) {
                error.classList.add('visible');
                savedErrors[idx] = event.error.message;
                errorMessage.innerText = event.error.message;
            } else {
                savedErrors[idx] = null;

                // Loop over the saved errors and find the first one, if any.
                var nextError = Object.keys(savedErrors)
                    .sort()
                    .reduce(function(maybeFoundError, key) {
                        return maybeFoundError || savedErrors[key];
                    }, null);

                if (nextError) {
                    // Now that they've fixed the current error, show another one.
                    errorMessage.innerText = nextError;
                } else {
                    // The user fixed the last error; no more errors.
                    error.classList.remove('visible');
                }
            }
        });
    });

    // Listen on the form's 'submit' handler...
    form.addEventListener('submit', function(e) {
        e.preventDefault();

        // Trigger HTML5 validation UI on the form if any of the inputs fail
        // validation.
        var plainInputsValid = true;
        Array.prototype.forEach.call(form.querySelectorAll('input'), function(
            input
        ) {
            if (input.checkValidity && !input.checkValidity()) {
                plainInputsValid = false;
                return;
            }
        });

        if (document.getElementById('no-nights').value < 1) {
            alert("Invalid dates");
            plainInputsValid = false;
            return;
        }

        if (!plainInputsValid) {
            triggerBrowserValidation();
            return;
        }

        // Show a loading screen...
        const paymentLoading = document.querySelector('#paymentLoading');
        paymentLoading.style.display = 'flex';

        classSelector.classList.add('submitting');

        // Disable all inputs.
        disableInputs();

        // Gather additional customer data we may have collected in our form.
        let firstname = form.querySelector('#firstname');
        let surname = form.querySelector('#surname');
        let name = firstname.value + ' ' + surname.value;
        let customerEmail = form.querySelector('#customerEmail');
        let address1 = form.querySelector('#address1');
        let city = form.querySelector('#formCity');
        let state = form.querySelector('#formState');
        let zip = form.querySelector('#formZip');
        let arrivalDate = form.querySelector('#arrival-date');
        let endDate = form.querySelector('#end-date');
        let noNights = form.querySelector('#no-nights');
        let phone = form.querySelector('#customerPhone');
        let bedrooms = form.querySelector('#noOfBedrooms');
        let children = form.querySelector('#noOfChildren');
        let adults = form.querySelector('#noOfAdult');
        let additionalData = {
            name: name ? name : undefined,
            address_line1: address1 ? address1.value : undefined,
            address_city: city ? city.value : undefined,
            address_state: state ? state.value : undefined,
            address_zip: zip ? zip.value : undefined,
        };


        // Use Stripe.js to create a token. We only need to pass in one Element
        // from the Element group in order to create a token. We can also pass
        // in the additional customer data we collected in our form.
        /*stripe.createToken(elements[0], additionalData).then(function(result) {
          // Stop loading!
          classSelector.classList.remove('submitting');

          if (result.token) {
        	// If we received a token, show the token ID.       
        	classSelector.classList.add('submitted');
        	
        	stripeBooking( result.token );
        	
          } else {
        	// Otherwise, un-disable inputs.
        	enableInputs();
          }
        });*/

        stripe.createPaymentMethod({
            type: 'card',
            card: elements[0],
            billing_details: {
                name: name,
                email: customerEmail,
            },
        }).then(function(result) {
            // Stop loading!
            classSelector.classList.remove('submitting');

            console.log(result.paymentMethod.id);

            if (result.paymentMethod.id) {
                // If we received a token, show the token ID.       
                classSelector.classList.add('submitted');

                stripeBooking(result.paymentMethod.id);

            } else {
                // Otherwise, un-disable inputs.
                enableInputs();
            }
        });



    });
}

function stripeBooking(token) {
    const form = new FormData();
    form.append('action', 'book_property');
    form.append('nonce', HRV.nonce);
    form.append('token', token);
    form.append('firstname', document.querySelector('#firstname').value);
    form.append('surname', document.querySelector('#surname').value);
    form.append('email', document.querySelector('#customerEmail').value);
    form.append('address1', document.querySelector('#address1').value);
    form.append('city', document.querySelector('#formCity').value);
    form.append('state', document.querySelector('#formState').value);
    form.append('zip', document.querySelector('#formZip').value);
    form.append('nights', document.querySelector('#no-nights').value);
    form.append('phone', document.querySelector('#customerPhone').value);
    form.append('children', document.querySelector('#noOfChildren').value);
    form.append('adults', document.querySelector('#noOfAdult').value);
    form.append('startdate', document.querySelector('#arrival-date').value);
    form.append('enddate', document.querySelector('#end-date').value);
    form.append('property', document.querySelector('#propertyId').value);
    form.append('totalPrice', document.querySelector('#totalPrice').value);
    form.append('totalRoomRate', document.querySelector('#totalRoomRate').value);
    form.append('ownerPrice', document.querySelector('#ownerPrice').value);
    form.append('bookingprice', document.querySelector('#bookingprice').value);
    form.append('owner_id', document.querySelector('#ownerID').value);
    form.append('ownerName', document.querySelector('#ownerName').value);
    form.append('ownerbookingpercent', document.querySelector('#ownerbookingpercent').value);
    form.append('property_owner_email', document.querySelector('#ownerEmail').value);
    form.append('dueDate', document.querySelector('#dueDate').value);
    form.append('deposit', document.querySelector('#depositPrice').value);
    <?php if ( get_field( 'api_price', $_GET['id'] ) ) : ?>
    form.append('apiPrice', document.querySelector('#apiPrice').value);
    form.append('apiProfit', document.querySelector('#apiProfit').value);
    form.append('roomPrice', document.querySelector('#roomPrice').value);
    form.append('totalExtra', document.querySelector('#totalExtra').value);
    form.append('cleaningFees', document.querySelector('#cleaningFees').value);
    form.append('additional', document.querySelector('#additional').value);
    form.append('taxRate', document.querySelector('#taxRate').value);
    form.append('extras', document.querySelector('#extras').value);
    <?php endif; ?>


    let extraCostName = [];
    let extraCostPrice = [];
    let extraCostPercentage = [];
    let extraCostOriginalPrice = [];
    let extracostSelector = document.querySelectorAll('input[name="extra-cost[]"]:checked');

    extracostSelector.forEach(function(extra) {
        extraCostName.push(extra.dataset.name);
        extraCostPrice.push(extra.value);
        extraCostPercentage.push(extra.dataset.percentage);
        extraCostOriginalPrice.push(extra.dataset.original)
    });

    if (extracostSelector.length > 0) {
        form.append('extracostname', extraCostName);
        form.append('extracostprice', extraCostPrice);
        form.append('extracostownerpercent', extraCostPercentage);
        form.append('extracostoriginalprice', extraCostOriginalPrice);
    }


    const params = new URLSearchParams(form);
    const loadingText = document.querySelector('#loadingText');



    return (
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
            if (data) {
                console.log(data);
                loadingText.innerText = "Successful. Redirecting...";

                setTimeout(function(e) {
                    window.location.href =
                        `<?php echo home_url( '/' ) . 'thank-you-for-booking?booking='; ?>${data.booking}`;
                }, 750);
            }
        })
        .catch((error) => {
            console.log('[STRIPE BOOKING ERROR]');
            console.log(error);
            console.error(error);
        })

    );

}
</script>
<?php $post_id = $_GET['id']; ?>


<?php

$hrv_public = new HRV_MLA_Public( 'hrv_mla', HRV_MLA_VERSION );
$hrv_admin  = new HRV_MLA_Admin( 'hrv_mla', HRV_MLA_VERSION );

$ciirus_id    = isset( $_GET['id'] ) && ! empty( $_GET['id'] ) ? get_field( 'ciirus_id', $_GET['id'] ) : '';
$price_cat_ID = wp_get_post_terms( $_GET['id'], 'price_categories' );

$date_checkin  = isset( $_GET['date_checkin'] ) ? $_GET['date_checkin'] : date( 'd M Y' );
$date_checkout = isset( $_GET['date_checkout'] ) ? $_GET['date_checkout'] : date( 'd M Y' );
$due_date_get  = new DateTime( $date_checkin );
$due_date_get->sub( new DateInterval( 'P30D' ) );
$due_date = $due_date_get->format( 'd M Y' );

$currentprice = round( $hrv_public->compute_price( $price_cat_ID[0]->term_id, $_GET['date_checkin'] ), 1 );

$total_room_rate = $currentprice ? $currentprice * $_GET['nights'] : 0;

$ownerbookingpercent = get_field( 'property_owner_booking_percentage', $_GET['id'] ) ? get_field( 'property_owner_booking_percentage', $_GET['id'] ) : get_field( 'additional_pricing', 'option' )['default_property_owner_booking_percentage'];
$owner_price         = 0;
$bookingprice        = round( $currentprice, 1 );

if ( $ownerbookingpercent ) {
	$owner_price = ( $ownerbookingpercent / 100 ) * $total_room_rate;
}
$total_price = round( $total_room_rate + $owner_price );
/* compute discount price */
$deposit_compute  = $total_price * .10;
$deposit_price    = $deposit_compute > $hrv_admin->deposit ? $hrv_admin->deposit : $deposit_compute;
$cleaning_fees    = 0;
$propertyTaxRates = 0;

function percentage_tax_price( $price, $percent ) {
	return ( $percent / 100 ) * $price;
}

function previous_page() {

	$previous = 'javascript:history.go(-1)';
	if ( isset( $_SERVER['HTTP_REFERER'] ) ) {
		$previous = $_SERVER['HTTP_REFERER'];
	}
	return $previous;

}

if ( get_field( 'api_price', $_GET['id'] ) ) {

	$getbookingprice = $hrv_admin->ciirus_calculated_booking_price( $ciirus_id, $_GET['date_checkin'], $_GET['nights'] );

	$bookingprice = round( $getbookingprice['total'] );
	$profit       = round( $getbookingprice['additional'] );
	$total_price  = $bookingprice;
	$owner_price = $bookingprice - $profit;

	$total_room_rate = $total_price;
	/* compute discount price */
	$deposit_compute = $total_price * .10;
	$deposit_price   = $deposit_compute > $hrv_admin->deposit ? $hrv_admin->deposit : $deposit_compute;
}


?>
<style>
.booking-property-summary {
    display: grid;
    grid-template-columns: 1fr 1fr;
    grid-gap: 20px;
    margin-bottom: 50px;
}

@media (max-width: 767px) {
    .booking-property-summary {
        grid-template-columns: 1fr;
    }
}

.booking-property-image img {
    width: 100%;
    height: auto;
    object-fit: contain;
    display: block;
}

.booking-property-content {
    padding: 20px;
    background: #315d76;
    border: 1px solid #ddd;
    border-radius: 4px;
    text-align: left;
    display: flex;
    align-items: center;
    color: #fff;
}

.booking-property-content * {
    color: #fff;
}

span.price-highlight {
    font-size: 130%;
    font-weight: 600;
    color: #fff;
}
</style>
<div class="booking-property-summary">
    <div class="booking-property-content">
        <div>
            <h2>Booking Summary</h2>
            <h3>Property: <?php echo get_the_title( $post_id ); ?></h3>
            <p>Bedrooms: <strong><?php the_field( 'bedrooms', $post_id ); ?></strong> | Sleeps:
                <strong><?php the_field( 'sleeps', $post_id ); ?></strong> | Bathrooms:
                <strong><?php the_field( 'bathrooms', $post_id ); ?></strong>
            </p>
            <div class="booking-summary-box">
                <p>Total Accomodation Price: <span class="price-highlight">$<span
                            id="summaryPrice"><?php echo number_format( $total_price ); ?></span></span>
                    <br><small>only <span class="price-highlight">$<span
                                id="summaryDeposit"><?php echo number_format( $deposit_price ); ?></span></span>
                        deposit</small>
                </p>
                <h4>Check In: <?php echo date( 'F j, Y', strtotime( $date_checkin ) ); ?></h4>
                <h4>Check Out: <?php echo date( 'F j, Y', strtotime( $date_checkout ) ); ?></h4>
            </div>
        </div>
    </div>
    <div class="booking-property-image">
        <?php echo get_the_post_thumbnail( $_GET['id'] ); ?>
    </div>
</div>
<div class="paymentform" style="position: relative;">
    <div id="paymentLoading" class="paymentLoading" style="display: none;">
        <h3 id="loadingText">Please wait...</h3>
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
    <form name="info-form" method="post" id="bookingForm" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
        <input type="hidden" id="submit" name="submit" value="true">
        <input type="hidden" id="id" name="id" value="<?php echo $_GET['id']; ?>">
        <input type="hidden" id="booking_name" name="booking_name" value="<?php echo get_the_title( $_GET['id'] ); ?>">
        <input type="hidden" id="auth" name="auth" value="<?php echo time(); ?>">
        <input type="hidden" id="ciirus_id" name="ciirus_id" value="<?php echo $ciirus_id; ?>">
        <div class="form-container">
            <h1 class="custom-form-header">BOOKING FORM</h1>
            <div class="custom-form-booking-input-wrapper">
                <div class="custom-book-form-row-1" style="display: none;">
                    <div class="arrival-wrapper">
                        <?php
						$datetime = new DateTime( 'tomorrow' );
						?>
                        <p class="custom-form"><label for="arrival">Arrival Date*</label></p>
                        <p class="custom-form-input-box"><input id="arrival-date" type="hidden" name="arrival"
                                value="<?php echo $date_checkin; ?>" readonly /></p>
                    </div>

                    <div class="nights-wrapper">
                        <p class="custom-form"><label for="end-date">Departure Date*</label></p>
                        <p class="custom-form-input-box"><input readonly id="end-date" type="hidden" name="departure"
                                required value="<?php echo $date_checkout; ?>" readonly /></p>
                    </div>
                    <div class="end-date-wrapper">
                        <p class="custom-form"><label for="nights">Nights</label></p>
                        <p class="custom-form-input-box"><input id="no-nights" type="hidden" name="nights" required
                                value="<?php echo $_GET['nights']; ?>" readonly /></p>
                    </div>
                </div>
                <div class="custom-book-form-row-2">
                    <div class="first-name-wrapper">
                        <p class="custom-form"><label for="Fname">First name*</label></p>
                        <p class="custom-form-input-box"><input id="firstname" type="text" name="Fname" required /></p>
                    </div>
                    <div class="surname-wrapper">
                        <p class="custom-form"><label for="surname">Surname*</label></p>
                        <p class="custom-form-input-box"><input id="surname" type="text" name="surname"
                                aria-required="true" aria-invalid="false" required /></p>
                    </div>
                    <div class="email-wrapper">
                        <p class="custom-form"><label for="customerEmail">E-mail*</label></p>
                        <p class="custom-form-input-box"><input id="customerEmail" type="email" name="email" required />
                        </p>
                    </div>
                </div>
                <div class="custom-book-form-row-3">
                    <div class="phone-wrapper">
                        <p class="custom-form"><label for="phone">Phone</label></p>
                        <p class="custom-form-input-box"><input id="customerPhone" type="tel" name="phone" required />
                        </p>
                    </div>

                    <div class="phone-wrapper">
                        <p class="custom-form"><label for="children">Children</label></p>
                        <p class="custom-form-input-box"><input id="noOfChildren" type="number" name="children"
                                required /></p>
                    </div>
                    <div class="phone-wrapper">
                        <p class="custom-form"><label for="adult">Adult</label></p>
                        <p class="custom-form-input-box"><input id="noOfAdult" type="number" name="adult" required />
                        </p>
                    </div>
                </div>
                <div class="custom-book-form-row-4">
                    <div class="address-line-wrapper">
                        <p class="custom-form"><label for="Address">Address line 1</label></p>
                        <p class="custom-form-input-box"><input id="address1" type="text" name="Address-line-1"
                                required /></p>
                    </div>
                    <div class="state-wrapper">
                        <p class="custom-form"><label for="formState">State/County</label></p>
                        <p class="custom-form-input-box"><input id="formState" type="text" name="State" required /></p>
                    </div>
                    <div class="country-wrapper">
                        <p class="custom-form"><label for="formCity">City</label></p>
                        <p class="custom-form-input-box"><input id="formCity" type="text" name="Country" required /></p>
                    </div>
                    <div class="zipcode-wrapper">
                        <p class="custom-form"><label for="Zipcode">Postal/Zipcode</label></p>
                        <p class="custom-form-input-box"><input id="formZip" type="text" name="Zipcode" required /></p>
                    </div>
                    <input type="hidden" name="property" id="propertyId" value="<?php echo $_GET['id']; ?>">
                    <input type="hidden" name="seasonprice" id="seasonprice" value="<?php echo (int) $currentprice; ?>">
                    <input type="hidden" name="bookingprice" id="bookingprice" value="<?php echo $bookingprice; ?>">
                    <input type="hidden" name="ownerbookingpercent" id="ownerbookingpercent"
                        value="<?php echo $ownerbookingpercent; ?>">


                    <input type="hidden" name="roomPrice" id="roomPrice" value="<?php echo $bookingprice; ?>">
                    <input type="hidden" name="totalPrice" id="totalPrice" value="<?php echo $total_price; ?>">
                    <input type="hidden" name="apiProfit" id="apiProfit" value="<?php echo $profit; ?>">
                    <input type="hidden" name="depositPrice" id="depositPrice" value="<?php echo $deposit_price; ?>">
                    <input type="hidden" name="ownerPrice" id="ownerPrice" value="<?php echo $owner_price; ?>">
                    <input type="hidden" name="totalRoomRate" id="totalRoomRate"
                        value="<?php echo $total_room_rate; ?>">
                    <input type="hidden" name="totalExtra" id="totalExtra" value="0">
                    <input type="hidden" name="dueDate" id="dueDate" value="<?php echo $due_date; ?>">
                    <input type="hidden" name="apiPrice" id="apiPrice"
                        value="<?php echo ( get_field( 'api_price', $_GET['id'] ) ? 1 : 0 ); ?>">
                    <?php
					$property_owner_id = get_field( 'property_owner', $_GET['id'] );
					$owner_email       = get_field( 'owner_email', $property_owner_id[0] );
					$owner_name        = get_the_title( $property_owner_id[0] );
					?>
                    <input type="hidden" name="ownerEmail" id="ownerEmail" value="<?php echo $owner_email; ?>">
                    <input type="hidden" name="owner_id" id="ownerID" value="<?php echo $property_owner_id[0]; ?>">
                    <input type="hidden" name="owner_name" id="ownerName" value="<?php echo $owner_name; ?>">

                    <?php if ( isset( $getbookingprice['tax_price'] ) ) : ?>
                    <input type="hidden" name="taxrate" id="taxRate"
                        value="<?php echo $getbookingprice['tax_price']; ?>">
                    <?php endif; ?>
                    <?php if ( isset( $getbookingprice['cleaning_price'] ) ) : ?>
                    <input type="hidden" name="cleaningfees" id="cleaningFees"
                        value="<?php echo $getbookingprice['cleaning_price']; ?>">
                    <?php endif; ?>
                    <?php if ( isset( $getbookingprice['cleaning_tax'] ) ) : ?>
                    <input type="hidden" name="cleaning_tax" id="cleaningTax"
                        value="<?php echo $getbookingprice['cleaning_tax']; ?>">
                    <?php endif; ?>
                    <?php if ( isset( $getbookingprice['extras'] ) ) : ?>
                    <input type="hidden" name="extras" id="extras" value="<?php echo $getbookingprice['extras']; ?>">
                    <?php endif; ?>
                    <?php if ( isset( $getbookingprice['additional'] ) ) : ?>
                    <input type="hidden" name="additional" id="additional"
                        value="<?php echo $getbookingprice['additional']; ?>">
                    <?php endif; ?>
                </div>




            </div>
            <div class="extra-cost-wrapper">
                <div class="extra-cost-header">
                    <p>The following items can be added at extra cost if required </p>
                </div>
                <div class="extra-cost-input">
                    <?php
					$extraCost = 0;
					if ( have_rows( 'extra_cost', $_GET['id'] ) ) :

						// Loop through rows.
						while ( have_rows( 'extra_cost', $_GET['id'] ) ) :
							the_row();

							// Load sub field value.
							$name        = get_sub_field( 'name' );
							$price_field = floatval(get_sub_field( 'price' ));
							$percentage  = get_sub_field( 'owner_percentage' ) ? get_sub_field( 'owner_percentage' ) : get_field( 'additional_pricing', 'option' )['default_additional_costs_property_owner_percentage'];
							$price       = $price_field + ( $price_field * floatval($percentage) / 100 );
							$price_total = number_format( round( $price * $_GET['nights'], 2 ), 2);
							$extraCost   = $extraCost + $price_total;
							// Do something...
							?>
                    <p class="extra-cost-checkbox">
                        <input id="<?php echo esc_attr( $name ); ?>" type="checkbox" name="extra-cost[]"
                            value="<?php echo $price_total; ?>" data-name="<?php echo $name; ?>"
                            data-percentage="<?php echo $percentage; ?>" data-original="<?php echo $price_field; ?>">
                        <label for="<?php echo esc_attr( $name ); ?>"><?php echo $name; ?>
                            $<?php echo $price_total; ?></label>
                    </p>
                    <?php

							// End loop.
						endwhile;

						// No value.
					else :
						// Do something...
					endif;

					?>


                </div>

            </div>
            <style>
            h1.custom-form-header.stripe-payment-title {
                display: flex;
                align-items: center;
            }

            h1.custom-form-header.stripe-payment-title img {
                height: 2em;
                padding-left: 2em;
            }
            </style>
            <h1 class="custom-form-header stripe-payment-title">PAYMENT <img
                    src="<?php echo bloginfo( 'url' ); ?>/wp-content/uploads/2022/10/stripe.png" alt="stripe payment">
            </h1>
            <div class="custom-form-booking-input-wrapper">

                <div class="custom-book-form-row-5">
                    <div class="cardNumberLine">
                        <p class="custom-form"><label for="Address">Card Number</label></p>
                        <p class="custom-form-input-box">
                        <div id="cardNumber" class="stripe-input"></div>
                        </p>
                    </div>
                    <div class="cardExpiryLine">
                        <p class="custom-form"><label for="email">Expiration</label></p>
                        <p class="custom-form-input-box">
                        <div id="cardExpiry" class="stripe-input"></div>
                        </p>
                    </div>
                    <div class="cardCvcLine">
                        <p class="custom-form"><label for="Zipcode">CVC</label></p>
                        <p class="custom-form-input-box">
                        <div id="cardCVC" class="stripe-input"></div>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="error" role="alert"><svg xmlns="http://www.w3.org/2000/svg" width="17" height="17"
                viewBox="0 0 17 17">
                <path class="base" fill="#000"
                    d="M8.5,17 C3.80557963,17 0,13.1944204 0,8.5 C0,3.80557963 3.80557963,0 8.5,0 C13.1944204,0 17,3.80557963 17,8.5 C17,13.1944204 13.1944204,17 8.5,17 Z">
                </path>
                <path class="glyph" fill="#FFF"
                    d="M8.5,7.29791847 L6.12604076,4.92395924 C5.79409512,4.59201359 5.25590488,4.59201359 4.92395924,4.92395924 C4.59201359,5.25590488 4.59201359,5.79409512 4.92395924,6.12604076 L7.29791847,8.5 L4.92395924,10.8739592 C4.59201359,11.2059049 4.59201359,11.7440951 4.92395924,12.0760408 C5.25590488,12.4079864 5.79409512,12.4079864 6.12604076,12.0760408 L8.5,9.70208153 L10.8739592,12.0760408 C11.2059049,12.4079864 11.7440951,12.4079864 12.0760408,12.0760408 C12.4079864,11.7440951 12.4079864,11.2059049 12.0760408,10.8739592 L9.70208153,8.5 L12.0760408,6.12604076 C12.4079864,5.79409512 12.4079864,5.25590488 12.0760408,4.92395924 C11.7440951,4.59201359 11.2059049,4.59201359 10.8739592,4.92395924 L8.5,7.29791847 L8.5,7.29791847 Z">
                </path>
            </svg>
            <span class="message"></span>
        </div>

        <div class="total-price-wrap" style="text-align: right;">
            <h3>Total: $<span id="pricetotalcompute"><?php echo number_format( $total_price ); ?></span></h3>
            <h4>Deposit: $<span id="depositpricecompute"><?php echo number_format( $deposit_price ); ?></span></h4>
        </div>

        <div class="buttons">
            <div class="go_back_link">
                <img src="/wp-content/uploads/2021/10/chelvron-left.png">
                <p><a href="<?php echo previous_page(); ?>">Go Back</a></p>
            </div>
            <div class="submit">
                <input type="submit">
            </div>

        </div>
        <input type="hidden" id="extra" name="extra" value="<?php echo $extraCost; ?>">
    </form>
</div>
<script>
(function() {
    'use strict';


    const bookingForm = document.getElementById('bookingForm');
    const arrivalDate = document.querySelector('#arrival-date');
    const endDate = document.querySelector('#end-date');
    const noNights = document.querySelector('#no-nights');
    const bookingprice = document.querySelector('#bookingprice');
    const pricetotalcompute = document.querySelector('#pricetotalcompute');
    const totalPrice = document.querySelector('#totalPrice');
    const ownerPrice = document.querySelector('#ownerPrice');
    const totalRoomRate = document.querySelector('#totalRoomRate');
    const taxrate = document.querySelector('#taxRate');
    const cleaningfees = document.querySelector('#cleaningFees');
    let extracosts = document.querySelectorAll('input[name="extra-cost[]"]');
    const depositPrice = document.querySelector('#depositPrice');
    const dueDate = document.querySelector('#dueDate');
    const apiProfit = document.getElementById('apiProfit');
    const extras = document.getElementById('extras');
    const additional = document.getElementById('additional');
    const totalExtra = document.getElementById('totalExtra');
    Date.prototype.addDays = function(days) {
        const date = new Date(this.valueOf())
        date.setDate(date.getDate() + days)
        return date
    }

    function compute_nights() {
        let nights = noNights.value;
        let seasonprice = bookingForm.querySelector('#seasonprice').value;
        let day1 = new Date(arrivalDate.value);
        let day2 = new Date(endDate.value);
        let difference = day2.getTime() - day1.getTime();
        let days = Math.ceil(difference / (1000 * 3600 * 24));
        noNights.value = days;
        bookingprice.value = parseInt(days) * parseInt(seasonprice);
        if (days) {
            getTotalPrice();
        }
    }

    //compute_nights();		
    <?php if ( get_field( 'api_price', $_GET['id'] ) ) { ?>

    function getTotalPrice() {
        const totalRoomRate = document.getElementById('totalRoomRate');
        const summaryPrice = document.getElementById('summaryPrice');
        const summaryDeposit = document.getElementById('summaryDeposit');
        let total = 0;
        let computedTotal = 0;
        extracosts.forEach(function(cost) {

            if (cost.checked) {
                total = Number(total) + Number(cost.value);
            }
        });

        computedTotal = Number(total) + Number(totalRoomRate.value);
        // if ( taxrate.value != 0 ) {
        // 	computedTotal = computedTotal + ((Number(taxrate.value)/100) * computedTotal) + Number(cleaningfees.value);
        // }

        console.log(total, Number(totalRoomRate.value), Number(total) + Number(totalRoomRate.value), computedTotal);
        let depositCompute = computedTotal * .10;
        let depositTotal = Number(depositCompute).toFixed(1) < HRV.stripe_deposit ? depositCompute
            .toFixed(1) : HRV.stripe_deposit;
        let computed = Number(computedTotal).toFixed(1);
        pricetotalcompute.innerText = Number(computed).toLocaleString();
        totalPrice.value = computed;
        summaryPrice.innerText = Number(computed).toLocaleString();
        depositPrice.value = depositTotal;
        document.getElementById('depositpricecompute').innerText = Number(depositTotal).toLocaleString();
        summaryDeposit.innerText = Number(depositTotal).toLocaleString();
        totalExtra.value = Number(total).toFixed(1);

    }
    <?php } else { ?>

    function getTotalPrice() {
        let total = 0;
        extracosts.forEach(function(cost) {

            if (cost.checked) {
                total += parseInt(cost.value);
            }
        });

        const summaryPrice = document.getElementById('summaryPrice');
        const summaryDeposit = document.getElementById('summaryDeposit');
        let computedPrice = bookingprice.value * noNights.value + parseInt(ownerPrice.value);

        let computedTotal = total + parseInt(computedPrice);

        let depositCompute = computedTotal * .10;
        let depositTotal = depositCompute.toFixed(1) < HRV.stripe_deposit ? depositCompute.toFixed(
            1) : HRV.stripe_deposit;

        pricetotalcompute.innerText = Number(computedTotal).toLocaleString();
        totalPrice.value = computedTotal;
        summaryPrice.innerText = Number(computedTotal).toLocaleString();
        depositPrice.value = depositTotal;
        document.getElementById('depositpricecompute').innerText = Number(depositTotal).toLocaleString();
        summaryDeposit.innerText = Number(depositTotal).toLocaleString();


    }
    <?php } ?>


    //getTotalPrice();	

    arrivalDate.addEventListener('change', function(e) {
        endDate.setAttribute("min", arrivalDate.value);
    });

    bookingForm.querySelectorAll('.extra-cost-checkbox input').forEach(function(input) {
        input.addEventListener('change', function(e) {
            getTotalPrice();
        });
    });

    var elements = stripe.elements({
        // Stripe's examples are localized to specific languages, but if
        // you wish to have Elements automatically detect your user's locale,
        // use `locale: 'auto'` instead.
        locale: window.__exampleLocale
    });

    var elementStyles = {
        base: {
            color: '#32325D',
            fontWeight: 500,
            fontSize: '16px',
            fontSmoothing: 'antialiased',

            '::placeholder': {
                color: '#CFD7DF',
            },
            ':-webkit-autofill': {
                color: '#e39f48',
            },
        },
        invalid: {
            color: '#E25950',

            '::placeholder': {
                color: '#FFCCA5',
            },
        },
    };

    var elementClasses = {
        focus: 'focused',
        empty: 'empty',
        invalid: 'invalid',
    };

    var cardNumber = elements.create('cardNumber', {
        style: elementStyles,
        classes: elementClasses,
    });
    cardNumber.mount('#cardNumber');

    var cardExpiry = elements.create('cardExpiry', {
        style: elementStyles,
        classes: elementClasses,
    });
    cardExpiry.mount('#cardExpiry');

    var cardCvc = elements.create('cardCvc', {
        style: elementStyles,
        classes: elementClasses,
    });
    cardCvc.mount('#cardCVC');

    registerElements([cardNumber, cardExpiry, cardCvc], 'paymentform');



})();
</script>