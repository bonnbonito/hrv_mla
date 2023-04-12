<style>
.date-box input {
	padding: 1em;
	border: 1px solid #ababab;
	border-radius: 4px;
}

.check-dates {
	display: flex;
	flex-wrap: wrap;
	grid-gap: 1em;
}

.check-now {
	margin-top: 1em;
}

button#checkdates {
	height: 51px;
	width: 120px;
	border-radius: 4px;
	border: 0;
	background: #0f395f;
	color: #fff;
	width: 100%;
	cursor: pointer;
}

.check-dates-result {
	padding: 1em;
	border: 1px solid #ababab;
	background-color: #f4fef0;
	border-radius: 4px;
	margin-top: 1em;
	display: none;
}
.check-dates-result.show {
	display: block;
}

.check-dates-result .hide {
	display: none;
}

#status-result .booknow {
	padding: 1em 2em;
	margin-left: 1em;
	font-size: 14px;
	font-family: "Poppins";
	font-weight: 400;
	border-radius: 4px;
	background: green;
	color: #fff;
}
#status-result {
	display: flex;
	align-items: center;
	justify-content: space-between;
}
</style>
<form id="checkdateform" class="check-dates-wrap" method="get" action="/">
	<div class="check-dates">
	<div class="date-box">
		<input id="date-1" placeholder="Checkin Date" required value="">
	</div>
	<div class="date-box">
		<input id="date-2" placeholder="Checkout Date" required required value="">
	</div>
	</div>
	<div class="check-now">
		<button id="checkdates" type="submit">Check</button>
	</div>
</form>
<div class="check-dates-result">
	<div id="loading-2">
		<h3 id="check-loading-text">Please wait...</h3>
	</div>
	<div id="status-result">

	</div>
</div>

<script>
	const loadingText = document.getElementById("check-loading-text");
	const checkdateform = document.getElementById("checkdateform");
	const statusResult = document.getElementById("status-result");
	const date1 = document.getElementById('date-1');
	const date2 = document.getElementById('date-2');
	const checkdates = document.getElementById('checkdates');
	const result = document.querySelector('.check-dates-result');
	const datepicker1 = new Datepicker(date1, {
		minDate: 'tomorrow',
		autohide: true,
		format: 'dd M yyyy',
	}); 

	const datepicker2 = new Datepicker(date2, {
		minDate: 'tomorrow',
		autohide: true,
		format: 'dd M yyyy',
	}); 

	date1.addEventListener('changeDate', function (e, details) { 
		datepicker2.setOptions( {
			minDate: datepicker1.getDate(),
		} );
		date2.focus();
	});

	checkdateform.addEventListener('submit', e => {
		e.preventDefault();
		const form = new FormData();
		form.append('action', 'check_availability');
		form.append('nonce', HRV.nonce);
		form.append('checkin', date1.value);
		form.append('checkout', date2.value);
		form.append('ciirus_id', <?php echo get_field( 'ciirus_id' ); ?>);
		form.append('property_id', <?php echo get_the_ID(); ?>);
		const params = new URLSearchParams(form);	
		console.log(params);
		loadingText.classList.remove('hide');
		result.classList.add('show');
		statusResult.innerHTML = "";
		fetch( HRV.ajax_url, {
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
			loadingText.classList.add('hide');
			if (data) {
				console.log(data);
				if ( 'available' === data['status'] ) {
					statusResult.innerHTML = `<h3 style="color: green;">Available: $${data['price']}</h3> <a href="/book-online/?id=${data['property_id']}&date_checkin=${data['checkin']}&date_checkout=${data['checkout']}&nights=${data['nights']}" class="booknow">Book Now</a>`;
				} else {
					statusResult.innerHTML = `<h3 style="color: red;">Not Available</h3>`;
				}
			}
		})
		.catch((error) => {
			console.error(error);
		})
	});
</script>
