<?php
/**
 * Plugin Name:          Luiz Bills - Correios for WooCommerce
 * Plugin URI:           https://github.com/luizbills/correios-for-woocommerce
 * GitHub Plugin URI:    https://github.com/luizbills/correios-for-woocommerce
 * Description:          Adds Correios shipping methods to your WooCommerce store. This plugin is based on <strong>Claudio Sanches - Correios for WooCommerce</strong> plugin.
 * Author:               Luiz Bills
 * Author URI:           https://luizpb.com/
 * Version:              4.0.1
 * License:              GPLv2 or later
 * Text Domain:          correios-for-woocommerce
 * Domain Path:          /languages
 *
 * @package WooCommerce_Correios
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WC_Correios' ) ) {
	define( 'WC_CORREIOS_VERSION', '4.0.1' );
	define( 'WC_CORREIOS_PLUGIN_FILE', __FILE__ );

	include_once dirname( __FILE__ ) . '/includes/class-wc-correios.php';
	add_action( 'plugins_loaded', array( 'WC_Correios', 'init' ) );
}
