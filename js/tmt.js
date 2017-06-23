/**
 * TMT Payment Form Scripts.
 *
 * @package woocommerce-gateway-tmt.
 */

(function ( $ ) {

	Spreedly.on( 'ready', function( frame ) {

		Spreedly.setFieldType( 'text' );
		Spreedly.setNumberFormat( 'prettyFormat' );
		Spreedly.setStyle( 'number', tmt_data.ccCss );
		Spreedly.setStyle( 'cvv', tmt_data.cvvCss );
	});

	Spreedly.on( 'paymentMethod', function( token, pmData ) {

		// Set the token.
		$( '#payment_method_token' ).val( token );

		// Submit the form.
		var $form = $( 'form.checkout' );
		$form.submit();
	});

	Spreedly.on( 'errors', function( errors ) {

		var errorList = '';
		var n = errors.length;

		for ( var i = 0; i < n; i++ ) {
			var error = errors[i];
			errorList += '<li>' + error['message'] + '</li>';
		}

		// Show the errors on the form.
		var $form  = $( 'form.checkout' );

		// Remove old errors.
		$( '.woocommerce-error' ).remove();

		// Show the errors on the form.
		$form.unblock();

		$form.prepend( '<ul class="woocommerce-error">' + errorList + '</ul>' );

		$( 'html, body' ).animate({
			scrollTop: ( $form.offset().top - 100 )
		}, 2000);
	});

	// Form handler.
	function tmtFormHandler() {

		var $form = $( 'form.checkout' );

		if ( $( '#payment_method_tmt' ).is( ':checked' ) ) {

			if ( '' === $( 'input#payment_method_token' ).val() ) {

				$form.block({
					message: null,
					overlayCSS: {
						background: '#fff',
						opacity: 0.6
					}
				});

				var options = {
					first_name:	$form.find( '#billing_first_name' ).val(),
					last_name:	$form.find( '#billing_last_name' ).val(),
					month:		$form.find( '#expmonth' ).val(),
					year:		$form.find( '#expyear' ).val(),
					address1:		$form.find( '#billing_address_1' ).val(),
					address2:		$form.find( '#billing_address_2' ).val(),
					city:		$form.find( '#billing_city' ).val(),
					state:		$form.find( '#billing_state' ).val(),
					zip:			$form.find( '#billing_postcode' ).val(),
					country:		$form.find( '#billing_country' ).val(),
				}

				// Tokenise.
				Spreedly.tokenizeCreditCard( options );

				// Prevent the form from submitting.
				return false;
			}
		}

		return true;
	}

	$( function () {

		/**
		 * Listener for checkout_place_order_tmt event. Clear errors and return tmtFormHandler.
		 */
		$( 'form.checkout' ).on( 'checkout_place_order_tmt', function () {

			// Remove old errors.
			$( '.woocommerce-error' ).remove();

			return tmtFormHandler();
		});

		/**
		 * Listener for 'checkout_error' event. Clear any payment_method_token.
		 */
		$( document.body ).on( 'checkout_error', function() {
			$( 'input#payment_method_token' ).val( '' );
		});
	});
}( jQuery ) );
