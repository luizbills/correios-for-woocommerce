<?php
/**
 * Correios
 *
 * @package WooCommerce_Correios/Classes
 * @since   3.6.0
 * @version 4.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugins main class.
 */
class WC_Correios {

	/**
	 * Initialize the plugin public actions.
	 */
	public static function init() {
		if ( self::is_original_plugin_active() ) {
			return;
		}

		add_action( 'init', array( __CLASS__, 'load_plugin_textdomain' ), -1 );

		// Checks with WooCommerce is installed.
		if ( class_exists( 'WC_Integration' ) ) {
			self::includes();

			if ( is_admin() ) {
				self::admin_includes();
			}

			add_filter( 'woocommerce_integrations', array( __CLASS__, 'include_integrations' ) );
			add_filter( 'woocommerce_shipping_methods', array( __CLASS__, 'include_methods' ) );
			add_filter( 'woocommerce_email_classes', array( __CLASS__, 'include_emails' ) );
		} else {
			add_action( 'admin_notices', array( __CLASS__, 'woocommerce_missing_notice' ) );
		}
	}

	/**
	 * Load the plugin text domain for translation.
	 */
	public static function load_plugin_textdomain() {
		load_plugin_textdomain( 'correios-for-woocommerce', false, dirname( plugin_basename( WC_CORREIOS_PLUGIN_FILE ) ) . '/languages/' );
	}

	/**
	 * Includes.
	 */
	private static function includes() {
		include_once dirname( __FILE__ ) . '/wc-correios-functions.php';
		include_once dirname( __FILE__ ) . '/class-wc-correios-package.php';
		include_once dirname( __FILE__ ) . '/class-wc-correios-webservice.php';
		include_once dirname( __FILE__ ) . '/class-wc-correios-autofill-addresses.php';
		include_once dirname( __FILE__ ) . '/class-wc-correios-tracking-history.php';
		include_once dirname( __FILE__ ) . '/class-wc-correios-rest-api.php';
		include_once dirname( __FILE__ ) . '/class-wc-correios-orders.php';
		include_once dirname( __FILE__ ) . '/class-wc-correios-cart.php';

		// Integration.
		include_once dirname( __FILE__ ) . '/integrations/class-wc-correios-integration.php';

		include_once dirname( __FILE__ ) . '/abstracts/class-wc-correios-shipping.php';
		foreach ( glob( plugin_dir_path( __FILE__ ) . '/shipping/*.php' ) as $filename ) {
			include_once $filename;
		}
	}

	/**
	 * Admin includes.
	 */
	private static function admin_includes() {
		include_once dirname( __FILE__ ) . '/admin/class-wc-correios-admin-orders.php';
	}

	/**
	 * Include Correios integration to WooCommerce.
	 *
	 * @param  array $integrations Default integrations.
	 *
	 * @return array
	 */
	public static function include_integrations( $integrations ) {
		$integrations[] = 'WC_Correios_Integration';

		return $integrations;
	}

	/**
	 * Include Correios shipping methods to WooCommerce.
	 *
	 * @param  array $methods Default shipping methods.
	 *
	 * @return array
	 */
	public static function include_methods( $methods ) {
		$methods['correios-pac']   = 'WC_Correios_Shipping_PAC';
		$methods['correios-sedex'] = 'WC_Correios_Shipping_SEDEX';

		return $methods;
	}

	/**
	 * Include emails.
	 *
	 * @param  array $emails Default emails.
	 *
	 * @return array
	 */
	public static function include_emails( $emails ) {
		if ( ! isset( $emails['WC_Correios_Tracking_Email'] ) ) {
			$emails['WC_Correios_Tracking_Email'] = include dirname( __FILE__ ) . '/emails/class-wc-correios-tracking-email.php';
		}

		return $emails;
	}

	/**
	 * WooCommerce fallback notice.
	 */
	public static function woocommerce_missing_notice() {
		include_once dirname( __FILE__ ) . '/admin/views/html-admin-missing-dependencies.php';
	}

	/**
	 * Get main file.
	 *
	 * @return string
	 */
	public static function get_main_file() {
		return WC_CORREIOS_PLUGIN_FILE;
	}

	/**
	 * Get plugin path.
	 *
	 * @return string
	 */
	public static function get_plugin_path() {
		return plugin_dir_path( WC_CORREIOS_PLUGIN_FILE );
	}

	/**
	 * Get templates path.
	 *
	 * @return string
	 */
	public static function get_templates_path() {
		return self::get_plugin_path() . 'templates/';
	}

	/**
	 * Check if the 'Claudio Sanches - Correios for WooCommerce' is installed and active.
	 *
	 * @return bool
	 */
	protected static function is_original_plugin_active () {
		$wc_correios_original = 'woocommerce-correios/woocommerce-correios.php';
		$wc_correios_active = in_array( $wc_correios_original, (array) get_option( 'active_plugins', array() ), true );

		if ( ! $wc_correios_active ) return false;

		add_action( 'admin_notices', function () {
			?>
			<div class="notice notice-error">
				<p>Por favor, desative o plugin <strong>Claudio Sanches - Correios for WooCommerce</strong>.</p>
			</div>
			<?php
		} );
		return true;
	}
}
