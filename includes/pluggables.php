<?php
/**
 * Pluggable function to return line_items, start_date and end_date of order.
 *
 * @package  woocommerce-gateway-tmt.
 */

if ( ! function_exists( 'tmt_line_item_data' ) ) :

	/**
	 * Fetches all products in cart, calculates booking start and end dates and line items.
	 *
	 * @return array line_items: array, start_date: date, end_date: date.
	 */
	function tmt_line_item_data() {

		$line_items = [];
		$start_date = '';
		$end_date = '';

		foreach ( WC()->cart->get_cart() as $cart_item_key => $item_values ) {

			// Product details.
			$product_id = $item_values['product_id'];
			$product_name = get_the_title( $product_id );

			// Booking start date details.
			$line_start_date = $item_values['booking']['_start_date'];
			$line_start_date_filtered = date( 'Y-m-d', $line_start_date );

			// Booking end date details.
			$line_end_date = $item_values['booking']['_end_date'];
			$line_end_date_filtered = date( 'Y-m-d', $line_end_date );

			if ( '' === $start_date || $line_start_date < $start_date ) {
				$start_date = $line_start_date;
			}

			if ( '' === $end_date || $line_end_date > $end_date ) {
				$end_date = $line_end_date;
			}

			$line_items[] = "{$line_start_date_filtered} - {$line_end_date_filtered}: {$product_name}";
		}

		return [
			'line_items'	=> $line_items,
			'start_date'	=> date( 'Y-m-d', $start_date ),
			'end_date'	=> date( 'Y-m-d', $end_date ),
		];
	}
endif;
