<?php
/**
 * Tmt_Payment_Gateway class to extend WC_Payment_Gateway_CC.
 *
 * @package woocommerce-gateway-tmt.
 */

/**
 * Tmt_Payment_Gateway class.
 */
class Tmt_Payment_Gateway extends WC_Payment_Gateway_CC {

	/**
	 * Order meta.
	 * Used for storing order meta for older version of WooCommerce.
	 *
	 * @var array.
	 */
	private $order_meta = [];

	/**
	 * Sets $order_meta with post custom values.
	 *
	 * @param integer $order_id order id.
	 * @internal test_wordpress_post_custom.
	 */
	public function set_order_meta( $order_id ) {
		$this->order_meta = get_post_custom( $order_id );
	}

	/**
	 * Getter for $order_meta.
	 *
	 * @return array post custom values for the order.
	 */
	public function get_order_meta() {
		return $this->order_meta;
	}

	/**
	 * Constructor.
	 */
	function __construct() {

		// The global ID for this Payment method.
		$this->id = 'tmt';

		// The Title shown on the top of the Payment Gateways Page next to all the other Payment Gateways.
		$this->method_title = __( 'Trust My Travel', 'woocommerce-gateway-tmt' );

		// The description for this Payment Gateway, shown on the actual Payment options page on the backend.
		$this->method_description = __( 'Trust My Travel Payment Gateway Plug-in for WooCommerce', 'woocommerce-gateway-tmt' );

		// The title to be used for the vertical tabs that can be ordered top to bottom.
		$this->title = __( 'Trust My Travel', 'woocommerce-gateway-tmt' );

		// If you want to show an image next to the gateway's name on the frontend, enter a URL to an image.
		$this->icon = null;

		// Bool. Set to true if you want payment fields to show on the checkout.
		$this->has_fields = true;

		$this->supports = [
			'products',
			'refunds',
		];

		// Defines your settings which are then loaded with init_settings().
		$this->init_form_fields();

		// After init_settings() is called, load them into vars, e.g: $this->title = $this->get_option( 'title' ).
		$this->init_settings();

		// Turn these settings into variables we can use.
		foreach ( $this->settings as $setting_key => $value ) {
			$this->$setting_key = $value;
		}

		// Lets check for SSL.
		add_action( 'admin_notices', [ $this, 'do_ssl_check' ] );

		// Save settings.
		if ( is_admin() ) {
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, [ $this, 'process_admin_options' ] );
		}
	}

	/**
	 * Init our gateway settings.
	 */
	public function init_form_fields() {

		$this->form_fields 	= [
			'enabled'				=> [
				'title'			=> __( 'Enable / Disable', 'woocommerce-gateway-tmt' ),
				'label'			=> __( 'Enable this payment gateway', 'woocommerce-gateway-tmt' ),
				'type'			=> 'checkbox',
				'default'		=> 'no',
			],
			'title'					=> [
				'title'			=> __( 'Title', 'woocommerce-gateway-tmt' ),
				'type'			=> 'text',
				'desc_tip'	=> __( 'Payment title the customer will see during the checkout process.', 'woocommerce-gateway-tmt' ),
				'default'		=> __( 'Credit card payment with Trust My Travel', 'woocommerce-gateway-tmt' ),
			],
			'cc_css'				=> [
				'title'			=> __( 'Credit Card Input CSS', 'woocommerce-gateway-tmt' ),
				'type'			=> 'text',
				'desc_tip'	=> __( 'CSS to use for the Credit Card input.' ),
				'default'		=> __( 'width:300px;  height:32px; font-size: 14px; outline: none; background-color:#fff; border: 1px solid #bbb; border-radius: 3px; padding:5px;', 'woocommerce-gateway-tmt' ),
			],
			'cvv_css'				=> [
				'title'			=> __( 'CVV Input CSS', 'woocommerce-gateway-tmt' ),
				'type'			=> 'text',
				'desc_tip'	=> __( 'CSS to use for the CVV input.' ),
				'default'		=> __( 'width:100px;  height:32px; font-size: 14px; outline: none; background-color:#fff; border: 1px solid #bbb; border-radius: 3px; padding:5px;', 'woocommerce-gateway-tmt' ),
			],
			'spreedly_env'	=> [
				'title'			=> __( 'Spreedly Environment Key', 'woocommerce-gateway-tmt' ),
				'type'			=> 'text',
				'desc_tip'	=> __( 'This is the Spreedly Environment Key provided by Trust My Travel.', 'woocommerce-gateway-tmt' ),
			],
			'tmt_key'				=> [
				'title'			=> __( 'Trust My Travel Token', 'woocommerce-gateway-tmt' ),
				'type'			=> 'password',
				'desc_tip'	=> __( 'This is the API token provided by Trust My Travel.', 'woocommerce-gateway-tmt' ),
			],
			'environment'		=> [
				'title'			=> __( 'Live Mode', 'woocommerce-gateway-tmt' ),
				'label'			=> __( 'Enable Live Mode', 'woocommerce-gateway-tmt' ),
				'type'			=> 'checkbox',
				'desc_tip'	=> __( 'Place the payment gateway in live mode.', 'woocommerce-gateway-tmt' ),
				'default'		=> '',
			],
		];
	}

	/**
	 * UI - Payment page fields.
	 */
	public function payment_fields() {

		$forex_options = $this->forex_options();

		// Get month options for expiry month.
		$months = [];

		for ( $i = 1; $i <= 12; $i ++ ) {
			$timestamp = mktime( 0, 0, 0, $i, 1 );
			$months[ date( 'n', $timestamp ) ] = date( 'F', $timestamp );
		}

		// Get spreedly environment key and output to script.
		$spreedly_env = $this->spreedly_env;

		$spreedly_script = "<script>
			Spreedly.init( '{$spreedly_env}', {
					'numberEl': 'spreedly-number',
					'cvvEl': 'spreedly-cvv',
			});
			</script>";

		// Serve template.
		require( WC_TMT_PLUGIN_PATH . '/template/credit-card-form.php' );
	}

	/**
	 * Fetches TMT currencies available against the store base.
	 *
	 * @return string html select options as html string.
	 */
	private function forex_options() {

		global $woocommerce;

		// Get currency.
		$base_currency = get_woocommerce_currency();

		// Get amount.
		$amount = $woocommerce->cart->total;

		// Are we testing right now or is it a real transaction?
		$url = ( 'yes' === $this->environment ) ? 'https://trustmytravel.com' : 'https://staging.trustmytravel.com';
		$url .= "/currency/{$base_currency}/";

		// Remote get.
		$response = wp_remote_get( $url );

		if ( is_wp_error( $response ) ) {
			return;
		}

		$options = '<option value= "">' . $base_currency . ' ' . $amount . '</option>';

		// If we don't have the currency, we get a 404.
		if ( '404' === wp_remote_retrieve_response_code( $response ) ) {
			return $options;
		}

		// Otherwise parse.
		$json = wp_remote_retrieve_body( $response );
		$obj = json_decode( $json );

		// Just in case.
		if ( empty( $obj ) ) {
			return $options;
		}

		foreach ( $obj->rates as $currency => $rate ) {

			$value = sprintf( '%.2F', ( $rate * $amount ) );
			$key = "{$currency}|{$value}";
			$options .= "<option value=\"{$key}\">{$currency} {$value}</option>";
		}

		return $options;
	}

	/**
	 * Order object.
	 *
	 * @var object.
	 */
	private $order;

	/**
	 * Wrapper for wc_get_order for easier stubbing and mocking in tests.
	 *
	 * @param  integer $order_id post id.
	 */
	public function set_order( $order_id ) {
		$this->order = wc_get_order( $order_id );
	}

	/**
	 * Getter for $order.
	 *
	 * @return object order object.
	 */
	public function get_order() {
		return $this->order;
	}

	/**
	 * Fetches order data.
	 *
	 * @param  integer $order_id post id.
	 * @return array           order data.
	 * @internal test_that_get_data_method_is_called.
	 * @internal test_that_order_data_pre_woo_version_three_method_is_called.
	 */
	public function get_order_data( $order_id ) {
		$order = $this->get_order();
		return ( method_exists( $order, 'get_data' ) ) ? $order->get_data() : $this->order_data_pre_woo_version_three( $order_id );
	}

	/**
	 * For WooCommerce pre version 3, fetches and compiles order data.
	 *
	 * @param  integer $order_id post id.
	 * @return array           order data.
	 * @internal test_that_order_data_is_correctly_compiled.
	 */
	public function order_data_pre_woo_version_three( $order_id ) {

		// Set order meta.
		$this->set_order_meta( $order_id );

		$fields = [
			'_billing_first_name'			=> '',
			'_billing_last_name'			=> '',
			'_billing_email'					=> '',
			'_billing_address_1'			=> '',
			'_billing_address_2'			=> '',
			'_billing_city'						=> '',
			'_billing_state'					=> '',
			'_billing_country'				=> '',
			'_billing_postcode'				=> '',
			'_billing_phone'					=> '',
			'_order_currency'					=> '',
			'_order_total'						=> '',
		];

		array_walk( $fields, function( &$value, $key ) {
			$value = ( isset( $this->get_order_meta()[ $key ][0] ) ) ? $this->get_order_meta()[ $key ][0] : '';
		});

		return [
			'billing'		=> [
				'first_name'	=> $fields['_billing_first_name'],
				'last_name'		=> $fields['_billing_last_name'],
				'email'				=> $fields['_billing_email'],
				'address_1'		=> $fields['_billing_address_1'],
				'address_2'		=> $fields['_billing_address_2'],
				'city'				=> $fields['_billing_city'],
				'state'				=> $fields['_billing_state'],
				'country'			=> $fields['_billing_country'],
				'postcode'		=> $fields['_billing_postcode'],
				'phone'				=> $fields['_billing_phone'],
			],
			'currency'	=> $fields['_order_currency'],
			'total'			=> $fields['_order_total'],
		];
	}

	/**
	 * Submit payment.
	 *
	 * @param  integer $order_id Order ID.
	 * @throws Exception Errors contacting or processing.
	 */
	public function process_payment( $order_id ) {

		// Set order.
		$this->set_order( $order_id );

		// Get order data.
		$order_data = $this->get_order_data( $order_id );

		// Are we testing right now or is it a real transaction?
		$url = ( 'yes' === $this->environment ) ? 'https://trustmytravel.com' : 'https://staging.trustmytravel.com';
		$url .= '/xmlrpc.php';

		// Parse line item data.
		$line_item_data = $this->parse_tmt_line_item_data();

		// Declare booking data and assign order values.
		$booking_data = [
			'firstname'							=> $order_data['billing']['first_name'],
			'surname'								=> $order_data['billing']['last_name'],
			'email'									=> $order_data['billing']['email'],
			'address'								=> $order_data['billing']['address_1'],
			'address2'							=> $order_data['billing']['address_2'],
			'city'									=> $order_data['billing']['city'],
			'state'									=> $order_data['billing']['state'],
			'country'								=> $order_data['billing']['country'],
			'postcode'							=> $order_data['billing']['postcode'],
			'phone'									=> $order_data['billing']['phone'],
			'payment_method_token'	=> filter_input( INPUT_POST, 'payment_method_token' ),
			'date'									=> $line_item_data['start_date'],
			'date_end'							=> $line_item_data['end_date'],
			'currency'							=> $order_data['currency'],
			'total'									=> $order_data['total'],
			'line_items'						=> implode( ', ', $line_item_data['line_items'] ),
			'reference'							=> $order_id,
		];

		// Test for alternate payment values.
		$forex = filter_input( INPUT_POST, 'tmt-alternate_payment' );

		if ( '' !== (string) $forex ) {

			$alternate = explode( '|', $forex );
			$booking_data['alternate_payment_currency'] = $alternate[0];
			$booking_data['alternate_payment_amount'] = $alternate[1];
		}

		// Include IXR.
		include ABSPATH . WPINC . '/class-IXR.php';

		// Init IXR.
		$client = new IXR_Client( $url );

		// Query.
		$client->query( 'tmt.spreedly.addBooking', 0, 'token', $this->tmt_key, $booking_data );

		// Get the response.
		$response = $client->getResponse();

		// Nothing? Throw exception.
		if ( empty( $response ) ) {
			throw new Exception( __( 'We are currently experiencing problems trying to connect to this payment gateway. Sorry for the inconvenience.', 'woocommerce-gateway-tmt' ) );
		}

		// Error?
		if ( isset( $response['faultString'] ) ) {
			throw new Exception( $response['faultString'] );
		}

		// Shouldnt be possible not to have a success field, but just in case.
		if ( empty( $response['success'] ) ) {
			throw new Exception( __( 'We are currently experiencing problems trying to connect to this payment gateway. Sorry for the inconvenience.', 'woocommerce-gateway-tmt' ) );
		}

		// Test the code to know if the transaction went through or not.
		if ( 'true' === $response['success'] ) {

			$currency = isset( $alternate[0] ) ? $alternate[0] : '';
			$payment = isset( $alternate[1] ) ? $alternate[1] : '';

			// If this was a forex booking, log payment and currency.
			if ( '' !== (string) $forex ) {

				update_post_meta( $order_id, '_tmt_order_total', $payment );
				update_post_meta( $order_id, '_tmt_order_currency', $currency );
			}

			$order = $this->get_order();

			// Payment has been successful.
			$order->add_order_note( $response['response'] );

			// Mark order as Paid and pass the TMT booking ID.
			$order->payment_complete( $response['booking_id'] );

			// Empty the cart (Very important step).
			WC()->cart->empty_cart();

			do_action( 'woocommerce_tmt_after_successful_payment', $order_id, $currency, $payment );

			// Redirect to thank you page.
			return [
				'result'   => 'success',
				'redirect' => $this->get_return_url( $order ),
			];
		}

		// Transaction was not succesful, add notice to the cart.
		wc_add_notice( $response['response'], 'error' );

		// Add note to the order for your reference.
		$order->add_order_note( 'Error: ' . $response['response'] );

		do_action( 'woocommerce_tmt_after_failed_payment', $order_id );
	}

	/**
	 * Fetches line item data from pluggable function and validates.
	 *
	 * @return array line items, start date and end date.
	 * @throws Exception Details on all errors encountered.
	 */
	private function parse_tmt_line_item_data() {

		// Fetch line items from pluggable function tmt_line_item_data.
		$line_items = tmt_line_item_data();

		$errors = [];

		// Check line items are present and in array format.
		if ( ! isset( $line_items['line_items'] ) ) {
			$errors[] = 'Line items not defined';
		} elseif ( ! is_array( $line_items['line_items'] ) ) {
			$errors[] = 'Line items must be an array';
		}

		// Check dates.
		$date_errors = false;

		foreach ( [ 'start_date', 'end_date' ] as $date_type ) {

			$string = ucfirst( str_replace( '_', ' ', $date_type ) );

			if ( ! isset( $line_items[ $date_type ] ) ) {

				$errors[] = "{$string} not defined";
				$date_errors = true;

			} elseif ( false === $this->check_date( $line_items[ $date_type ] ) ) {

				$errors[] = "{$string} incorrectly formatted";
				$date_errors = true;

			} else {
				$$date_type = $line_items[ $date_type ];
			}
		}

		// Check start and end dates if valid.
		if ( false === $date_errors && false === $this->is_start_before_end( $start_date, $end_date ) ) {
			$errors[] = 'Start date is after end date';
		}

		if ( ! empty( $errors ) ) {

			$error_string = implode( ', ', $errors );

			throw new Exception( $error_string );
		}

		return $line_items;
	}

	/**
	 * Check that the date value passed is in the correct format (YYYY-MM-DD) and is a valid date.
	 *
	 * @param  string $date 	passed value for date.
	 * @return boolean.
	 */
	private function check_date( $date ) {

		// Is format correct yyyy-mm-dd?
		if ( ! preg_match( '/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $date ) ) {
			return false;
		}

		// Is it a real date eg not 2020-13-45 nb checkdate requires: m, d, y.
		return checkdate( substr( $date, 5, 2 ), substr( $date, 8, 2 ), substr( $date, 0, 4 ) );
	}

	/**
	 * Compare two dates to ensure that the end date is the same or greater than the start date.
	 *
	 * @param  date $start_date date in YYYY-MM-DD format.
	 * @param  date $end_date   date in YYYY-MM-DD format.
	 * @return boolean.
	 */
	private function is_start_before_end( $start_date, $end_date ) {
		return strtotime( $end_date ) >= strtotime( $start_date );
	}

	/**
	 * Check if we are forcing SSL on checkout pages.
	 *
	 * @return string null | error message.
	 */
	public function do_ssl_check() {

		// Are we ssl enabled?
		if ( 'yes' !== $this->enabled ) {
			return;
		}

		// Are we forcing SSL checkout?
		if ( 'no' !== get_option( 'woocommerce_force_ssl_checkout' ) ) {
			return;
		}

		// Return warning.
		echo '<div class="error"><p>' . sprintf( __( '<strong>Trust My Travel</strong> is enabled and WooCommerce is not forcing the SSL certificate on your checkout page. Please ensure that you have a valid SSL certificate and that you are <a href="%s">forcing the checkout pages to be secured.</a>' ), admin_url( 'admin.php?page=wc-settings&tab=checkout' ) ) . '</p></div>';
	}

	/**
	 * Process refund on booking.
	 *
	 * @param  integer $order_id order ID.
	 * @param  float   $amount   refund amount.
	 * @param  string  $reason   reason for refund.
	 * @return mixed             bool true | object WP_Error.
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {

		$booking_id = get_post_meta( $order_id, '_transaction_id', true );

		if ( '' === $booking_id ) {
			return new WP_Error( 'tmt_refund_error', 'No Booking ID stored' );
		}

		$customer_order = new WC_Order( $order_id );

		$refund_data = [
			'booking_id'	=> $booking_id,
			'currency'	=> $customer_order->get_currency(),
			'amount'		=> $amount,
		];

		// Are we testing right now or is it a real transaction?
		$url = ( 'yes' === $this->environment ) ? 'https://trustmytravel.com' : 'https://staging.trustmytravel.com';
		$url .= '/xmlrpc.php';

		// Include IXR.
		include ABSPATH . WPINC . '/class-IXR.php';

		// Init IXR.
		$client = new IXR_Client( $url );

		// Query.
		$client->query( 'tmt.spreedly.refundBooking', 0, 'token', $this->tmt_key, $refund_data );

		// Get the response.
		$response = $client->getResponse();

		if ( empty( $response ) ) {
			return new WP_Error( 'tmt_refund_error', 'No response from gateway' );
		}

		if ( isset( $response['faultString'] ) ) {
			return new WP_Error( 'tmt_refund_error', $response['faultString'] );
		}

		if ( 'false' === $response['success'] ) {
			return new WP_Error( 'tmt_refund_error', $response['response'] );
		}

		return true;
	}
}
