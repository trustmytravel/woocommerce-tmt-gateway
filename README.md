# woocommerce-tmt-gateway
[Trust My Travel](https://trustmytravel.com/) payment gateway extension for [WooCommerce](https://woocommerce.com/) on [WordPress](https://wordpress.org/). Allows for placing bookings via Trust My Travel, and refunding.

## Requirements ##
* WordPress installation.
* WooCommerce plugin.
* Trust My Travel account.
* The [WooCommerce Bookings Extension](https://woocommerce.com/products/woocommerce-bookings/) is also highly recommended.

## Installation ##
Install as you would install a standard WordPress plugin:
https://codex.wordpress.org/Managing_Plugins#Installing_Plugins

The plugin works out of the box with WooCommerce and the WooCommerce Bookings Extension.

As Trust My Travel require a start and end date for bookings placed via their API, the Bookings Extension is recommended as it allows for defining a start and end date.

If you do not use the Bookings Extension, and have an alternate means of defining booking start and end dates, you can create your own version of the pluggable function `tmt_line_item_data` (see code examples below).

Trust My Travel offer multiple currency payment (MCP) options to the traveller meaning that you can transact in multiple currencies. On successful completion of an MCP booking, the total and currency of the payment are logged to post meta fields and a message is displayed indicating what the customer paid, and in which currency. Should you wish to override the WooCommerce booking currency and amount with this, you can make use of the woocommerce_tmt_after_successful_payment action (see code examples below).

## Settings ##
You can find the settings for the TMT Gateway in the "Checkout" section of WooCommerce "Settings":
yoursite/wp-admin/admin.php?page=wc-settings&tab=checkout&section=tmt

**Enable / Disable**
Check enable to enable the gateway.

**Title**
The title to display on the payment form.

**Credit Card Input CSS**
CSS to be passed to the Spreedly generated credit card input box on the payment form.

**CVV Input CSS**
CSS to be passed to the Spreedly generated CVV input box on the payment form.

**Spreedly Environment Key**
Trust My Travel Spreedly Environment Key. To be supplied by Trust My Travel.

**Trust My Travel Token**
Trust My Travel API Token. To be supplied by Trust My Travel.

**Live Mode**
Tick to process live bookings, leave unchecked to work in sandbox mode and work with test credit card data.

## Code Examples ##

### Pluggable Function: tmt_line_item_data filter ###
If you are not using the WooCommerce Bookings Extension, you will need to add the function `tmt_line_item_data` to your theme's functions.php file. This function must return an array as follows:

'line_items': array of line items that make up the booking.
'start_date': valid start date of booking in 'Y-m-d' format.
'end_date': valid end date of booking in 'Y-m-d' format that does not occure before the start dates.

**Example:**

```php
function tmt_line_item_data() {

	return [
		'line_items'	=> [ 'My Company Package Tour', 'Hotel Transfer' ],
		'start_date'	=> date( 'Y-m-d' ),
		'end_date'	=> date( 'Y-m-d' ),
	];
}
```

If any of the required fields are missing from the returned array, an exception will be thrown.
For full details on the validation applied to the line item array, refer to the `parse_tmt_line_item_data` method in the Tmt_Payment_Gateway class.

### Action: woocommerce_tmt_after_successful_payment ###
Whenever an MCP booking occurs, the total is stored to the post_meta field `_tmt_order_total` and the currency is stored to the post_meta field `_tmt_order_currency`. The `woocommerce_thankyou_tmt` and `woocommerce_order_details_after_order_table` actions are then hooked to display to the user what currency and total they paid underneath the order details (see below for details on removing these).

As an alternative, you may wish to overwrite the WooCommerce `_order_currency` and `_order_total` fields in order to have the customer order reflect the payment currency and amount*.

Whenever an MCP booking occurs, the total is stored to the post_meta field `_tmt_order_total` and the currency is stored to the post_meta field `_tmt_order_currency`. You could use the data in these fields in conjunction with by hooking the `woocommerce_tmt_after_successful_payment` action, which is fired after the order has been paid and processed, and the user's cart emptied and prior to redirecting the customer to the thankyou page.

**Example:**

```php
function override_woo( $order_id, $currency, $payment ) {

	// Store base currency is USD, no need to do anything if booking was paid in USD.
	if ( 'USD' === $currency ) {
		return;
	}

	update_post_meta( $order_id, '_order_currency', $currency );
	update_post_meta( $order_id, '_order_total', $payment );
}
add_action( 'woocommerce_tmt_after_successful_payment', 'override_woo', 10, 3 );
```

_*There may be implications with other WooCommerce plugins or functionality if you do this. There are also implications to TMT Payment Gateway refund functionality as refunds must be processed in the base currency of the booking._

### Action: woocommerce_tmt_after_failed_payment ###
Whenever a payment attempt fails, this action is available, and has one argument, the order ID, available.

**Example:**

```php
function do_something( $order_id ) {

	// Perform an action on fail - email customer, log attempt etc.
}
add_action( 'woocommerce_tmt_after_failed_payment', 'do_something', 10, 1 );
```

### Custom Hooks ###
As the TMT Payment Gateway does not overwrite any core WooCommerce payment data, we make use of the `woocommerce_thankyou_tmt` and `woocommerce_order_details_after_order_table` hooks in order to output what the user paid for a booking in the event of a currency other than the store base being used. If you have made use of the `woocommerce_tmt_after_successful_payment` to overwrite the booking payment data, or have another means of outputing payment data to the customer, you may wish to remove these custom hooks.

**Example:**

```php
function remove_my_action() {

	$instance = Tmt_Payment_Gateway::this();

	// Thank you page output.
	$ty = remove_action( 'woocommerce_thankyou_tmt', [ $instance, 'forex_paid' ], 1 );

	// Order page output.
	$op = remove_action( 'woocommerce_order_details_after_order_table', [ $instance, 'forex_paid' ] );
}
add_action( 'wp_head', 'remove_my_action' );
```

## Support ##
matt.bush@trustmytravel.com
