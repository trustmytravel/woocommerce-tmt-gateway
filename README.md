# Woocommerce Trust My Travel Gateway
[Trust My Travel](https://trustmytravel.com/) payment gateway extension for [WooCommerce](https://woocommerce.com/) on [WordPress](https://wordpress.org/).<br />Allows for placing, and refunding bookings via the Trust My Travel API.

## Requirements ##
* WordPress 4.8 or above.
* WooCommerce 2.6  or above.
* Trust My Travel account.
* The [WooCommerce Bookings Extension](https://woocommerce.com/products/woocommerce-bookings/) is also highly recommended.

## Installation ##
Install as you would install a standard WordPress plugin:
https://codex.wordpress.org/Managing_Plugins#Installing_Plugins

The plugin works out of the box with WooCommerce and the WooCommerce Bookings Extension.

As Trust My Travel require a start and end date for bookings placed via their API, the Bookings Extension is recommended as it allows for defining a start and end date.

If you do not use the Bookings Extension, and have an alternate means of defining booking start and end dates, you can create your own version of the pluggable function `tmt_line_item_data` (see code examples below).

Trust My Travel offer multiple currency payment (MCP) options to the traveller meaning that you can transact in multiple currencies. On successful completion of an MCP booking, the total and currency of the payment are logged to post meta fields and a message is displayed indicating what the customer paid, and in which currency. Should you wish to perform more complex actions than this, you can make use of the `woocommerce_tmt_after_successful_payment action` (see code examples below).

## Settings ##
You can find the settings for the TMT Gateway in the "Checkout" section of WooCommerce "Settings":
`yoursite/wp-admin/admin.php?page=wc-settings&tab=checkout&section=tmt`

**Enable / Disable**<br />
Check enable to enable the gateway.

**Title**<br />
The title to display on the payment form.

**Credit Card Input CSS**<br />
CSS to be passed to the Spreedly generated credit card input box on the payment form.

**CVV Input CSS**<br />
CSS to be passed to the Spreedly generated CVV input box on the payment form.

**Spreedly Environment Key**<br />
Trust My Travel Spreedly Environment Key. To be supplied by Trust My Travel.

**Trust My Travel Token**<br />
Trust My Travel API Token. To be supplied by Trust My Travel.

**Live Mode**<br />
Tick to process live bookings, leave unchecked to work in sandbox mode and work with test credit card data.

**Default to Base Currency**<br />
By default, your payment page will show prices in the currency of your WooCommerce store. Users can then pick from a dropdown list of other currencies if they wish to pay in a currency other than your store currency. If you want to force users to select a currency rather than giving them a default, tick this box.

## Code Examples ##

### TMT Action: `tmt_woo_credit_card_form_info` ###
By default, this custom action has a method called `tmt_woo_credit_card_form_info_output` hooked to it, which renders out some copy informing the user that the payment will be processed by Trust My Travel and that in paying the booking, they accept the Terms & Conditions of Trust My Travel. If you wish to add your own copy here, you can do so by removing the default hook and adding your own. Please note that your payment page must include a link to the Trust My Travel terms and conditions in order to be compliant.

**Example:**

```php
function remove_my_action() {

    remove_action( 'tmt_woo_credit_card_form_info', 'tmt_woo_credit_card_form_info_output', 1 );
    add_action( 'tmt_woo_credit_card_form_info', function() {
        echo '<p>Some other message with the <a href="https://www.trustmytravel.com/terms/" target="_blank">Trust My Travel terms and conditions</a> included</p>';
    });
}
add_action( 'init', 'remove_my_action' );
```

### TMT Action: `tmt_woo_forex_info` ###
By default, this custom action has a method called `tmt_woo_forex_info_output` hooked to it, which renders out some copy informing the user that they can select payment in an alternate currency and that, so long as their card is in the same currency, they won't incur any additional fees.

**Example:**

```php
function remove_my_action() {

    remove_action( 'tmt_woo_forex_info', 'tmt_woo_forex_info_output', 1 );
    add_action( 'tmt_woo_forex_info', function() {
        echo '<p>Some other message regarding the foreign currency payment options.</p>';
    });
}
add_action( 'init', 'remove_my_action' );
```

### Pluggable Function: `tmt_line_item_data filter` ###
If you are not using the WooCommerce Bookings Extension, you will need to add the function `tmt_line_item_data` to your theme's functions.php file. This function must return an array as follows:

* `line_items`: array of line items that make up the booking.
* `start_date`: valid start date of booking in 'Y-m-d' format.
* `end_date`: valid end date of booking in 'Y-m-d' format that does not occure before the start dates.

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
For full details on the validation applied to the line item array, refer to the `parse_tmt_line_item_data` method in the `Tmt_Payment_Gateway` class.

Alternatively you can use our [TMT WooCommerce Pluggables MU Plugin](https://github.com/trustmytravel/tmt-woocommerce-pluggables)

### Woo Action: `woocommerce_tmt_after_successful_payment` ###
Whenever an MCP booking occurs, the total is stored to the post_meta field `_tmt_order_total` and the currency is stored to the post_meta field `_tmt_order_currency`. The `woocommerce_thankyou_tmt` and `woocommerce_order_details_after_order_table` actions are then hooked to display to the user what currency and total they paid underneath the order details (see below for details on removing these).

As an alternative, you may wish to overwrite the WooCommerce `_order_currency` and `_order_total` fields in order to have the customer order reflect the payment currency and amount*.

Whenever an MCP booking occurs, the total is stored to the post_meta field `_tmt_order_total` and the currency is stored to the post_meta field `_tmt_order_currency`. You could use the data in these fields in conjunction with hooking the `woocommerce_tmt_after_successful_payment` action, which is fired after the order has been paid and processed, and the user's cart emptied and prior to redirecting the customer to the thankyou page.

**Example:**

```php
function override_woo( $order_id, $currency, $payment ) {

	// Store base currency = USD, no need to do anything if booking was paid in USD.
	if ( 'USD' === $currency ) {
		return;
	}

	update_post_meta( $order_id, '_order_currency', $currency );
	update_post_meta( $order_id, '_order_total', $payment );
}
add_action( 'woocommerce_tmt_after_successful_payment', 'override_woo', 10, 3 );
```
<sub>_*NB: There may be implications with other WooCommerce plugins or functionality if you do this. There are also implications to TMT Payment Gateway refund functionality as refunds must be processed in the base currency of the booking._</sub>

### Woo Action `woocommerce_thankyou_tmt` & `woocommerce_order_details_after_order_table` ###
As the TMT Payment Gateway does not overwrite any core WooCommerce payment data, we make use of the `woocommerce_thankyou_tmt` and `woocommerce_order_details_after_order_table` hooks in order to output what the user paid for a booking in the event of a currency other than the store base being used. If you have hooked the `woocommerce_tmt_after_successful_payment` action to overwrite the booking payment data, or have another means of outputing payment data to the customer, you may wish to remove these custom hooks.

**Example:**

```php
function remove_my_action() {

	// Thank you page output.
	remove_action( 'woocommerce_thankyou_tmt', 'tmt_woo_forex_paid', 1 );

	// Order page output.
	remove_action( 'woocommerce_order_details_after_order_table', 'tmt_woo_forex_paid' );
}
add_action( 'wp_head', 'remove_my_action' );
```

### Woo Action: `woocommerce_tmt_after_failed_payment` ###
Whenever a payment attempt fails, this action is available, and has one argument, the order ID, available.

**Example:**

```php
function do_something( $order_id ) {

    // Perform an action on fail - email customer, log attempt etc.
}
add_action( 'woocommerce_tmt_after_failed_payment', 'do_something', 10, 1 );
```
## Support ##
matt.bush@trustmytravel.com
