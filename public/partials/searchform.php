<style>
form.newserachform {
	display: flex;
	border-radius: 10px;
	border-bottom: 1px solid #ddd;
}

form.newserachform .form-field {
	flex: 1;
	position: relative;
}

.form-field-submit {}

.form-field label {
	position: absolute;
	z-index: 1;
	bottom: 2.35em;
	left: 4em;
	line-height: 1;
	transition: .4s;
	color: #808384;
	pointer-events: none;
}



form.newserachform input,
form.newserachform select {
	width: 100%;
	position: relative;
	padding: 1em;
	border: 0;
	padding-left: 4em;
}

form.newserachform input {
	padding: 2.35em 2em 2.35em 4em;
}

form.newserachform select:not([value=""]):valid {
	padding-left: 4em;
}

form.newserachform input:focus ~ label,
form.newserachform input:active ~ label,
form.newserachform select:active ~ label,
form.newserachform select:focus ~ label,
form.newserachform select:not([value=""]):valid ~ label,
form.newserachform input:not(:placeholder-shown) ~ label,
.div-select-wrap.focus ~ label {
	bottom: 4em;
	left: 1.75em;
	transform: scale(.75);
}

.form-field .submit-search {
	position: absolute;
	width: 100%;
	height: 100%;
	left: 0;
	background: #59b202;
	border: 0;
	color: #fff;
	cursor: pointer;
	transition: .5s;
}

.form-field .submit-search:hover {
	background: #489200;
}

.form-field-select {
	display: flex;
	align-items: center;
}

form.newserachform .form-field:not(:last-child)::after {
	height: 80%;
	width: 1px;
	background: #ede8e8;
	position: absolute;
	top: 50%;
	right: 0;
	transform: translateY(-50%);
	content: '';
	display: inline-block;
}

.form-field svg {
	position: absolute;
	width: 1em;
	top: 50%;
	transform: translateY(-50%);
	z-index: 2;
	left: 40px;
	fill: #808384;
}

form.newserachform .datepicker-input.in-edit:active, form.newserachform .datepicker-input.in-edit:focus {
	box-shadow: none !important;
}

.brl-10 {
	border-top-left-radius: 10px;
	border-bottom-left-radius: 10px;
}

.brr-10 {
	border-top-right-radius: 10px;
	border-bottom-right-radius: 10px;
}

form.newserachform .form-field-submit {
	width: 270px;
	flex: inherit;
}

.div-select-wrap {
	position: relative;
}

.div-select {
	position: absolute;
	background-color: #fff;
	width: 100%;
	box-shadow: -1px 0 5px 0px rgb(0 0 0 / 50%);
	cursor: pointer;
	display: none;
	z-index: 99;
}

.div-select-wrap.focus .div-select {
	display: block;
}

.div-select-wrap.focus.has-value .div-select {
	display: none;
}

.div-select > div {
	padding: 5px;
	transition: .4s;
}

.div-select > div:hover {
	background: rgba(0,0,0,.1);
}

.div-select.hide {
	display: none;
}

</style>

<form action="<?php bloginfo( 'url' ); ?>/villa-list-result" method="GET" autocomplete="off" class="newserachform">
<?php
	$show = false;
if ( $show ) {
	?>
	<div class="form-field form-field-select">
	<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><path d="M215.7 499.2C267 435 384 279.4 384 192C384 86 298 0 192 0S0 86 0 192c0 87.4 117 243 168.3 307.2c12.3 15.3 35.1 15.3 47.4 0zM192 256c-35.3 0-64-28.7-64-64s28.7-64 64-64s64 28.7 64 64s-28.7 64-64 64z"/></svg>
		<select class="form-select" name="resort" id="resort" required>
			<option value=""></option>
		<?php
		$resorts = get_terms(
			array(
				'taxonomy'   => 'resort',
				'hide_empty' => true,
			)
		);
		?>
		<?php foreach ( $resorts as $resort ) { ?>

			<option value="<?php echo $resort->term_id; ?>" <?php echo ( isset( $_REQUEST['resort'] ) && $resort->term_id == $_REQUEST['resort'] ? ' selected' : '' ); ?>><?php echo $resort->name; ?></option>

			<?php } ?>

		</select>
		<label for="bedrooms">Resort</label>
	</div>
	<?php } ?>
	<div class="form-field">
	<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Pro 6.2.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2022 Fonticons, Inc. --><path d="M152 64H296V24C296 10.75 306.7 0 320 0C333.3 0 344 10.75 344 24V64H384C419.3 64 448 92.65 448 128V448C448 483.3 419.3 512 384 512H64C28.65 512 0 483.3 0 448V128C0 92.65 28.65 64 64 64H104V24C104 10.75 114.7 0 128 0C141.3 0 152 10.75 152 24V64zM48 448C48 456.8 55.16 464 64 464H384C392.8 464 400 456.8 400 448V192H48V448z"/></svg>
		<input type="text" name="date_checkin" id="check-in" value="<?php echo ( isset( $_REQUEST['date_checkin'] ) ? $_REQUEST['date_checkin'] : '' ); ?>" placeholder=" " class="brl-10">
		<label for="check-in">Check In</label>
	</div>
	<div class="form-field">
	<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Pro 6.2.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2022 Fonticons, Inc. --><path d="M152 64H296V24C296 10.75 306.7 0 320 0C333.3 0 344 10.75 344 24V64H384C419.3 64 448 92.65 448 128V448C448 483.3 419.3 512 384 512H64C28.65 512 0 483.3 0 448V128C0 92.65 28.65 64 64 64H104V24C104 10.75 114.7 0 128 0C141.3 0 152 10.75 152 24V64zM48 448C48 456.8 55.16 464 64 464H384C392.8 464 400 456.8 400 448V192H48V448z"/></svg>
		<input type="text" name="date_checkout" id="check-out" value="<?php echo ( isset( $_REQUEST['date_checkout'] ) ? $_REQUEST['date_checkout'] : '' ); ?>" placeholder=" ">
		<label for="check-out">Check Out</label>
	</div>
	<div class="form-field form-field-select">
	<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><!-- Font Awesome Pro 5.15.4 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) --><path d="M176 256c44.11 0 80-35.89 80-80s-35.89-80-80-80-80 35.89-80 80 35.89 80 80 80zm352-128H304c-8.84 0-16 7.16-16 16v144H64V80c0-8.84-7.16-16-16-16H16C7.16 64 0 71.16 0 80v352c0 8.84 7.16 16 16 16h32c8.84 0 16-7.16 16-16v-48h512v48c0 8.84 7.16 16 16 16h32c8.84 0 16-7.16 16-16V240c0-61.86-50.14-112-112-112z"/></svg>
		<div class="div-select-wrap <?php echo ( isset( $_REQUEST['bedrooms'] ) ? 'focus has-value' : '' ); ?>">
		<input type="text" id="bedrooms" value="<?php echo ( isset( $_REQUEST['bedrooms'] ) ? $_REQUEST['bedrooms'] . ' Bedrooms' : '' ); ?>" required>
		<input type="hidden" name="bedrooms" id="bedroomshidden" value="<?php echo ( isset( $_REQUEST['bedrooms'] ) ? $_REQUEST['bedrooms'] : '' ); ?>">
		<div class="div-select">
			<?php for ( $i = 3; $i < 10; $i++ ) { ?>
			<div data-room="<?php echo $i; ?>"><?php echo $i; ?> Bedrooms</div>
			<?php } ?>
			</div>
		</div>
		<label for="bedrooms">Bedrooms</label>
	</div>

	


	<div class="form-field form-field-submit">
		<button class="submit-search brr-10" type="submit">Search</button>
	</div>
</form>


<script>

	const date_checkin = document.getElementById('check-in');
	const date_checkout = document.getElementById('check-out');
	const nights = document.getElementById('nights');
	const bedrooms = document.getElementById('bedrooms');
	const bedroomshidden = document.getElementById('bedroomshidden');
	const bedroomsSelect = document.querySelectorAll('.div-select > div');


	bedrooms.addEventListener('focusin', (event) => {
		bedrooms.parentElement.classList.add('focus');
		bedrooms.parentElement.classList.remove('has-value');
	});


	bedrooms.addEventListener('input', (event) => {
		return;
	});

	bedrooms.onkeypress = function() {return false;};

	bedroomsSelect.forEach((bedroom) => {
		bedroom.addEventListener('click', (event) => {
			let val = bedroom.dataset.room;
			bedrooms.parentElement.classList.add('has-value');
			bedrooms.value = val + ' Bedrooms';
			bedroomshidden.value = val;
		});
	});

	const datepicker1 = new Datepicker(date_checkin, {
		minDate: 'tomorrow',
		autohide: true,
		format: 'dd M yyyy',

	}); 

	const datepicker2 = new Datepicker(date_checkout, {
		minDate: 'tomorrow',
		autohide: true,
		format: 'dd M yyyy',

	}); 

	date_checkin.addEventListener('changeDate', function (e, details) { 

		datepicker2.setOptions( {
			minDate: datepicker1.getDate(),
		} );
		date_checkout.focus();

	});
</script>

