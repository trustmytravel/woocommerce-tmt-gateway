<?php
/**
 * Main plugin class.
 *
 * @package woocommerce-gateway-tmt.
 */

/**
 * TMT Woo Commerce payment gateway plugin class.
 */
class Tmt_Woo_Commerce_Gateway {

	/**
	 * Constructor.
	 */
	public function run() {

		// Actions.
		add_action( 'plugins_loaded', [ $this, 'init' ], 0 );
		add_action( 'wp_enqueue_scripts', [ $this, 'add_tmt_scripts' ] );

		// Filters.
		add_filter( 'woocommerce_payment_gateways', [ $this, 'register_gateway' ] );
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), [ $this, 'plugin_action_links' ] );
	}

	/**
	 * Init the plugin by including the class.
	 */
	public function init() {

		// If the parent WC_Payment_Gateway class doesn't exist, WooCommerce is not installed on the site.
		if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
			return;
		}

		/**
		 * Tmt_Payment_Gateway class.
		 *
		 * @package woocommerce-gateway-tmt.
		 */
		include_once 'class-tmt-payment-gateway.php';
	}

	/**
	 * Register our script and enqueue on the checkout page.
	 */
	public function add_tmt_scripts() {

		// TMT Script.
		wp_register_script(
			'tmt-js',
			WC_TMT_PLUGIN_URL . '/js/tmt.min.js',
			[ 'jquery' ],
			WC_TMT_VERSION,
			true
		);

		// Spreedly iframe script.
		wp_register_script(
			'spreedly',
			'https://core.spreedly.com/iframe/iframe-v1.min.js',
			[],
			1,
			false
		);

		$options = get_option( 'woocommerce_tmt_settings' );

		// Localize TMT script with css settings.
		wp_localize_script(
			'tmt-js',
			'tmt_data',
			[
				'cvvCss'		=> isset( $options['cvv_css'] ) ? $options['cvv_css'] : '',
				'ccCss'			=> isset( $options['cvv_css'] ) ? $options['cc_css'] : '',
			]
		);

		// Queue if we are on checkout page.
		if ( is_checkout() ) {
			wp_enqueue_script( 'spreedly' );
			wp_enqueue_script( 'tmt-js' );
		}
	}

	/**
	 * Register TMT Payment Gateway method.
	 *
	 * @param  array $methods pre-existing WooCommerce methods.
	 * @return array          pre-existing WooCommerce methods with ours added.
	 */
	public function register_gateway( $methods ) {
		$methods[] = 'Tmt_Payment_Gateway';
		return $methods;
	}

	/**
	 * Add a link to our gateway to the gateway links in the relevant WooCommerce settings page.
	 *
	 * @param  array $links pre-existing WooCommerce links.
	 * @return array        pre-existing WooCommerce links with ours added.
	 */
	public function plugin_action_links( $links ) {
		$links[] = '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout' ) . '">' . __( 'Settings', 'woocommerce-gateway-tmt' ) . '</a>';
		return $links;
	}
}
