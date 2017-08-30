<?php
/**
 * Hooks and Filters.
 *
 * @package woocommerce-gateway-tmt.
 */

/**
 * Default return for the tmt_woo_credit_card_form_info action.
 */
function tmt_woo_credit_card_form_info_output() {
	echo '<p>In paying for this booking, you are accepting the <a href="https://www.trustmytravel.com/terms/" target="_blank">terms and conditions of Trust My Travel</a>, our credit card processor.</p>';
}

/**
 * Default return for the tmt_woo_forex_info action.
 */
function tmt_woo_forex_info_output() {
	echo '<p>Please use the drop-down list below to choose your local currency. Please note: If the currency selected matches your card, the payment amount quoted will be reflected on your statement. If you do not choose your local currency, you may be liable to FX fees by your cardholder bank.</p>';
}

/**
 * Custom hook for the 'woocommerce_thankyou_tmt' and 'woocommerce_order_details_after_order_table' actions.
 * Tests if order was paid in non-base currency, and displays payment data if so.
 *
 * @param  mixes $order integer order_id | object order object.
 */
function tmt_woo_forex_paid( $order ) {

	$order_id = ( is_integer( $order ) ) ? $order : trim( str_replace( '#', '', $order->get_order_number() ) );

	$alternate_currency = get_post_meta( $order_id, '_tmt_order_currency', true );

	if ( '' === $alternate_currency ) {
		return;
	}

	$alternate_payment = get_post_meta( $order_id, '_tmt_order_total', true );

	echo '<p class="woocommerce-notice woocommerce-notice--success woocommerce-thankyou-order-received">' . sprintf( __( 'You paid %1$s %2$s for this order.' ), $alternate_currency, $alternate_payment ) . '</p>' ;
}
