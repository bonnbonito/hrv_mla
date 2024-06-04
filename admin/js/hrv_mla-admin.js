(function( $ ) {
	'use strict';

	const xeroConnect = document.getElementById('xeroConnect');
	const xeroDisconnect = document.getElementById('xeroDisconnect');
	const xeroRefresh = document.getElementById('xeroRefresh');
	

	if ( xeroConnect ) {
		xeroConnect.addEventListener('click', function(e){
			e.preventDefault();

			fetch('https://identity.xero.com/connect/token', {
			method: 'POST',
			mode: 'no-cors',
			body: 'grant_type=client_credentials',
			headers: {
				'Authorization': "Basic " + btoa(XERO.client_id + ":" + XERO.client_secret),
				'Content-Type': 'application/json'
			}
			}).then(resp => {
				return resp.json()
			}).then(data => {
				console.log('token', data);
			});
		});
	}

	

	/**
	 * All of the code for your admin-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */

	

	 
	
})( jQuery );