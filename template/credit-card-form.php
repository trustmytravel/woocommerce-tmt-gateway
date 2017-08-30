<?php
/**
 * Credit Card Payment Form Template.
 *
 * @package woocommerce-gateway-tmt.
 */

do_action( 'tmt_woo_credit_card_form_info' );
?>

<div class="tmt-credit-card-form-container">

	<label for="tmt-alternate_payment">
		<?php echo __( 'Payment Total', 'woocommerce-gateway-tmt' ) ?> <span class="required">*</span>
	</label>

	<?php do_action( 'tmt_woo_forex_info' ); ?>

	<select name="tmt-alternate_payment" id="tmt-alternate_payment" class="woocommerce-select">
		<?php echo $forex_options; ?>
	</select>

</div>

<div class="tmt-credit-card-form-container">

	<label for="spreedly-number">
		<?php echo __( 'Credit Card number', 'woocommerce-gateway-tmt' ) ?> <span class="required">*</span>
	</label>

	<div id="spreedly-number" style="height: 45px;"></div>

	<div id="cc-error"></div>

</div>

<div class="tmt-credit-card-form-container">

	<label for="spreedly-cvv">
		<?php echo __( 'CVV', 'woocommerce-gateway-tmt' ) ?> <span class="required">*</span>
	</label>

	<div id="spreedly-cvv" style="height: 45px;"></div>

	<div id="cvv-error"></div>

</div>

<div class="tmt-credit-card-form-container">

	<label for="spreedly-cvv">
		<?php echo __( 'Expiry', 'woocommerce-gateway-tmt' ) ?> <span class="required">*</span>
	</label>

	<select name="expmonth" id="expmonth" class="woocommerce-select woocommerce-cc-month">

		<option value=""><?php _e( 'Month', 'woocommerce-gateway-tmt' ) ?></option>

		<?php foreach ( $months as $num => $name ) : ?>
			<?php printf( '<option value="%u">%s</option>', $num, $name ); ?>
		<?php endforeach; ?>

	</select>

	<select name="expyear" id="expyear" class="woocommerce-select woocommerce-cc-year">

		<option value=""><?php _e( 'Year', 'woocommerce-gateway-tmt' ) ?></option>

		<?php $year = date( 'y' ); ?>

		<?php for ( $i = $year; $i <= $year + 15; $i ++ ) : ?>
			<?php printf( '<option value="20%u">20%u</option>', $i, $i ); ?>
		<?php endfor; ?>

	</select>

</div>

<!-- This field will get the payment method token value after the user submits the payment frame -->
<input type="hidden" name="payment_method_token" id="payment_method_token" value="" />
<?php echo $spreedly_script; ?>
