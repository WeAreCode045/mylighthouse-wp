/**
 * Modal results JavaScript
 */

(function($) {
	'use strict';

	$(document).ready(function() {
		// Handle iframe loading
		var iframe = $('#mylighthouse-booking-iframe');
		if (iframe.length) {
			iframe.on('load', function() {
				// Iframe loaded successfully
				console.log('Booking iframe loaded');
			});
		}
	});

})(jQuery);
