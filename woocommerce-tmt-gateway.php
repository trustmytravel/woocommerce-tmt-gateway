<?php
/*
Plugin Name: Trust My Travel - WooCommerce Gateway
Plugin URI: https://trustmytravel.com/
Description: Extends WooCommerce by Adding the Trust My Travel Gateway.
Version: 1.0.0
Author: Matt Bush
Author URI: https://trustmytravel.com/
*/

/**
 * TMT Woo Commerce payment gateway plugin class.
 */
class Tmt_Woo_Commerce_Gateway {

	/**
	 * Constructor.
	 */
	public function __construct() {

		// Constants.
		define( 'WC_TMT_VERSION', '1.0.0' );
		define( 'WC_TMT_PLUGIN_PATH', dirname( __FILE__ ) );
		define( 'WC_TMT_PLUGIN_URL', plugins_url( '', __FILE__ ) );

		// Activation hook.
		register_activation_hook( __FILE__, [ $this, 'plugin_activation' ] );

		// Actions.
		add_action( 'plugins_loaded', [ $this, 'init' ], 0 );
		add_action( 'wp_enqueue_scripts', [ $this, 'add_tmt_scripts' ] );
		add_action( 'admin_notices', [ $this, 'tmt_admin_notice' ] );
		add_action( 'wp', [ $this, 'pluggables' ], 10 );

		// Filters.
		add_filter( 'woocommerce_payment_gateways', [ $this, 'register_gateway' ] );
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), [ $this, 'plugin_action_links' ] );
	}

	/**
	 * Checks for WooCommerce and WooCommerce Bookings Extension on activation.
	 * Activation is still permitted if either is not present.
	 */
	function plugin_activation() {

		// If the parent WC_Payment_Gateway class doesn't exist, WooCommerce is not installed on the site.
		if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
			set_transient( 'tmt-woo-admin-notice', 'You need to install WooCommerce', 5 );
			return;
		}

		// If the Bookings extension isnt installed, return advisory message.
		if ( ! class_exists( 'WC_Bookings' ) ) {
			set_transient( 'tmt-woo-admin-notice', 'We highly recommend using this gateway with the WooCommerce Bookings Extension.', 5 );
		}
	}

	/**
	 * Init the plugin by including the class.
	 */
	function init() {

		// If the parent WC_Payment_Gateway class doesn't exist, WooCommerce is not installed on the site.
		if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
			return;
		}

		/**
		 * Tmt_Payment_Gateway class.
		 *
		 * @package woocommerce-gateway-tmt.
		 */
		include_once 'class/class-tmt-payment-gateway.php';

		// Localisation.
		load_plugin_textdomain( 'woocommerce-gateway-tmt', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	/**
	 * Register our script and enqueue on the checkout page.
	 */
	function add_tmt_scripts() {

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
				'cvvCss'			=> isset( $options['cvv_css'] ) ? $options['cvv_css'] : '',
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
	 * Display admin notice regarding lack of WooCommerce or WooCommerce Bookings Extension plugins if applicable.
	 */
	function tmt_admin_notice() {

		// Check transient, if available display notice.
		$admin_notice = esc_attr( get_transient( 'tmt-woo-admin-notice' ) );

		if ( '' === (string) $admin_notice ) {
			return;
		}

		echo '<div class="updated notice is-dismissible">';
		echo "<p>{$admin_notice}</p>";
		echo '</div>';

		// Delete transient, only display this notice once.
		delete_transient( 'tmt-woo-admin-notice' );
	}

	/**
	 * Include pluggables.
	 */
	function pluggables() {
		require_once 'includes/pluggables.php';
	}

	/**
	 * Register TMT Payment Gateway method.
	 *
	 * @param  array $methods pre-existing WooCommerce methods.
	 * @return array          pre-existing WooCommerce methods with ours added.
	 */
	function register_gateway( $methods ) {
		$methods[] = 'Tmt_Payment_Gateway';
		return $methods;
	}

	/**
	 * Add a link to our gateway to the gateway links in the relevant WooCommerce settings page.
	 *
	 * @param  array $links pre-existing WooCommerce links.
	 * @return array        pre-existing WooCommerce links with ours added.
	 */
	function plugin_action_links( $links ) {
		$links[] = '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout' ) . '">' . __( 'Settings', 'woocommerce-gateway-tmt' ) . '</a>';
		return $links;
	}
}

new Tmt_Woo_Commerce_Gateway();
