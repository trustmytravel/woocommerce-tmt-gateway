<p>
In paying for this booking, you are accepting the <a href="https://www.trustmytravel.com/terms/" target="_blank">terms and conditions of Trust My Travel</a>, our credit card processors.
</p>

<div>

	<label for="tmt-alternate_payment">
		<?php echo __( 'Payment Total', 'woocommerce-gateway-tmt' ) ?> <span class="required">*</span>
	</label>

	<select name="tmt-alternate_payment" id="tmt-alternate_payment" class="woocommerce-select">
		<?php echo $forex_options; ?>
	</select>

</div>

<div>

	<label for="spreedly-number">
		<?php echo __( 'Credit Card number', 'woocommerce-gateway-tmt' ) ?> <span class="required">*</span>
	</label>

	<div id="spreedly-number" style="height: 45px;"></div>
	<div id="cc-error"></div>

</div>

<div>

	<label for="spreedly-cvv">
		<?php echo __( 'CVV', 'woocommerce-gateway-tmt' ) ?> <span class="required">*</span>
	</label>

	<div id="spreedly-cvv" style="height: 45px;"></div>
	<div id="cvv-error"></div>

</div>

<div>

	<label for="spreedly-cvv">
		<?php echo __( 'Expiry', 'woocommerce-gateway-tmt' ) ?> <span class="required">*</span>
	</label>

	<select name="expmonth" id="expmonth" class="woocommerce-select woocommerce-cc-month">
		<option value=""><?php _e( 'Month', 'woocommerce-gateway-tmt' ) ?></option>

		<?php foreach ( $months as $num => $name ) {
			printf( '<option value="%u">%s</option>', $num, $name );
		} ?>

	</select>

	<select name="expyear" id="expyear" class="woocommerce-select woocommerce-cc-year">
		<option value=""><?php _e( 'Year', 'woocommerce-gateway-tmt' ) ?></option>

		<?php for ( $i = date( 'y' ); $i <= date( 'y' ) + 15; $i ++ ) {
			printf( '<option value="20%u">20%u</option>', $i, $i );
		} ?>

	</select>
</div>

<!-- This field will get the payment method token value after the user submits the payment frame -->
<input type="hidden" name="payment_method_token" id="payment_method_token" value="" />
<?php echo $spreedly_script; ?>
