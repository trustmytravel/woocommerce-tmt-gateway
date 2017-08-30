<?php
/**
 * Plugin bootstrap file.
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @wordpress-plugin
 * Plugin Name: Trust My Travel - WooCommerce Gateway
 * Plugin URI: https://trustmytravel.com/
 * Description: Extends WooCommerce by Adding the Trust My Travel Gateway.
 * Version: 1.1.0
 * Author: Matt Bush
 * Author URI: https://trustmytravel.com/
 *
 * Text Domain: woocommerce-tmt-gateway
 *
 * @package woocommerce-tmt-gateway
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Define Constants.
define( 'WC_TMT_VERSION', '1.1.1' );
define( 'WC_TMT_PLUGIN_PATH', dirname( __FILE__ ) );
define( 'WC_TMT_PLUGIN_URL', plugins_url( '', __FILE__ ) );

/**
 * Actions.
 */
// Pluggables.
add_action( 'wp', function() {
	require_once 'includes/pluggables.php';
}, 10 );

// Credit card form info.
add_action( 'tmt_woo_credit_card_form_info', 'tmt_woo_credit_card_form_info_output', 1 );

// Forex info.
add_action( 'tmt_woo_forex_info', 'tmt_woo_forex_info_output', 1 );

// Thank you page output.
add_action( 'woocommerce_thankyou_tmt', 'tmt_woo_forex_paid', 1 );

// Order page output.
add_action( 'woocommerce_order_details_after_order_table', 'tmt_woo_forex_paid' );

/**
 * File containing hook and filter methods.
 */
require WC_TMT_PLUGIN_PATH . '/includes/hooks-filters.php';

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require WC_TMT_PLUGIN_PATH . '/includes/class-tmt-woo-commerce-gateway.php';

/**
 * Begins execution of the plugin.
 */
function run_tmt_woo_commerce_gateway() {
	$tmt_woo_commerce_gateway = new Tmt_Woo_Commerce_Gateway();
	$tmt_woo_commerce_gateway->run();
}

run_tmt_woo_commerce_gateway();
