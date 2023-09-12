<?php
/**
 * Correios Autofill Addresses.
 *
 * @package WooCommerce_Correios/Classes/Autofill
 * @since   3.0.0
 * @version 4.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Correios autofill addresses class.
 */
class WC_Correios_Autofill_Addresses {

	/**
	 * Table name.
	 *
	 * @var string
	 */
	public static $table = 'correios_postcodes';

	/**
	 * Ajax endpoint.
	 *
	 * @var string
	 */
	protected $ajax_endpoint = 'correios_autofill_address';

	/**
	 * Initialize actions.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );
	}

	/**
	 * Init autofill.
	 */
	public function init() {
		if ( apply_filters( 'woocommerce_correios_enable_autofill_addresses', false ) ) {
			$this->maybe_install();

			add_action( 'wp_enqueue_scripts', array( $this, 'frontend_scripts' ) );
			add_action( 'wc_ajax_' . $this->ajax_endpoint, array( $this, 'ajax_autofill' ) );
		}
	}

	/**
	 * Logger.
	 *
	 * @param string $message data to log.
	 * @param string $level
	 */
	protected static function log( $message, $level = 'info' ) {
		if ( apply_filters( 'woocommerce_correios_enable_autofill_addresses_debug', false ) ) {
			$logger = wc_get_logger();
			$logger->log( $level, $message, array(
				'source'  => 'correios-autofill-addresses'
			) );
		}
	}

	/**
	 * Get validity.
	 *
	 * @return string
	 */
	protected static function get_validity() {
		return apply_filters( 'woocommerce_correios_autofill_addresses_validity', 'forever' );
	}

	/**
	 * Get address by postcode.
	 *
	 * @param string $postcode Address postcode.
	 *
	 * @return stdClass
	 */
	public static function get_address( $postcode ) {
		global $wpdb;

		$postcode = wc_correios_sanitize_postcode( $postcode );

		if ( empty( $postcode ) ) {
			return null;
		}

		$table   = $wpdb->prefix . self::$table;
		$address = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE postcode = %s;", $postcode ) ); // @codingStandardsIgnoreLine

		if ( is_wp_error( $address ) || is_null( $address ) ) {
			$address = self::fetch_address( $postcode );

			if ( ! is_null( $address ) ) {
				self::save_address( (array) $address );
			}
		} elseif ( self::check_if_expired( $address->last_query ) ) {
			$_address = self::fetch_address( $postcode );

			if ( ! is_null( $_address ) ) {
				$address = $_address;
				self::update_address( (array) $address );
			}
		}

		return $address;
	}

	/**
	 * Check if postcode is expired.
	 *
	 * @param string $last_query Date of the last query.
	 * @return bool
	 */
	protected static function check_if_expired( $last_query ) {
		$validity = self::get_validity();

		if ( 'forever' !== $validity && strtotime( '+' . $validity . ' months', strtotime( $last_query ) ) < current_time( 'timestamp' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Insert an address.
	 *
	 * @param array $address Address data to save.
	 *
	 * @return bool
	 */
	protected static function save_address( $address ) {
		global $wpdb;

		$default = array(
			'postcode'     => '',
			'address'      => '',
			'city'         => '',
			'neighborhood' => '',
			'state'        => '',
			'last_query'   => current_time( 'mysql' ),
		);

		$address = wp_parse_args( $address, $default );

		$result = $wpdb->insert(
			$wpdb->prefix . self::$table,
			$address,
			array( '%s', '%s', '%s', '%s', '%s', '%s' )
		); // WPCS: db call ok, cache ok.

		return false !== $result;
	}

	/**
	 * Delete an address from database.
	 *
	 * @param string $postcode Address postcode.
	 */
	protected static function delete_address( $postcode ) {
		global $wpdb;

		$wpdb->delete( $wpdb->prefix . self::$table, array( 'postcode' => $postcode ), array( '%s' ) ); // WPCS: db call ok, cache ok.
	}

	/**
	 * Update an address.
	 *
	 * @param array $address Address data.
	 *
	 * @return bool
	 */
	protected static function update_address( $address ) {
		self::delete_address( $address['postcode'] );

		return self::save_address( $address );
	}

	/**
	 * Fetch an address from Correios Webservices.
	 *
	 * @param string $postcode Address postcode.
	 * @return stdClass
	 */
	protected static function fetch_address( $postcode ) {
		$address = null;

		try {
			$api = new WC_Correios_Webservice( 'correios-autofill-addresses' );
			$response = $api->request_address( $postcode );

			if ( is_wp_error( $response ) ) {
				throw new Exception( $response->get_error_message() );
			}

			if ( $response ) {
				$address = new stdClass();
				$address->postcode     = $response['cep'];
				$address->address      = $response['logradouro'];
				$address->city         = $response['localidade'];
				$address->neighborhood = $response['bairro'];
				$address->state        = $response['uf'];
				$address->last_query   = current_time( 'mysql' );
			}
		} catch ( Exception $e ) {
			self::log( sprintf( 'An error occurred while trying to fetch address for "%s": %s', $postcode, $e->getMessage() ), 'error' );
		}

		if ( ! is_null( $address ) ) {
			self::log( sprintf( 'Address for "%s" found successfully: %s', $postcode, print_r( $address, true ) ) ); // @codingStandardsIgnoreLine
		}

		return $address;
	}

	/**
	 * Frontend scripts.
	 */
	public function frontend_scripts() {
		if ( is_checkout() || is_account_page() ) {
			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			wp_enqueue_script( 'woocommerce-correios-autofill-addresses', plugins_url( 'assets/js/frontend/autofill-address' . $suffix . '.js', WC_Correios::get_main_file() ), array( 'jquery', 'jquery-blockui' ), WC_CORREIOS_VERSION, true );

			wp_localize_script(
				'woocommerce-correios-autofill-addresses',
				'WCCorreiosAutofillAddressParams',
				array(
					'url'   => WC_AJAX::get_endpoint( $this->ajax_endpoint ),
					'force' => apply_filters( 'woocommerce_correios_autofill_addresses_force_autofill', 'no' ),
				)
			);
		}
	}

	/**
	 * Ajax autofill endpoint.
	 */
	public function ajax_autofill() {
		if ( empty( $_GET['postcode'] ) ) { // WPCS: input var okay, CSRF ok.
			wp_send_json_error( array( 'message' => __( 'Missing postcode paramater.', 'correios-for-woocommerce' ) ) );
			exit;
		}

		$postcode = wc_correios_sanitize_postcode( wp_unslash( $_GET['postcode'] ) ); // WPCS: input var okay, CSRF ok.

		if ( empty( $postcode ) || 8 !== strlen( $postcode ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid postcode.', 'correios-for-woocommerce' ) ) );
			exit;
		}

		$address = self::get_address( $postcode );

		if ( is_null( $address ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid postcode.', 'correios-for-woocommerce' ) ) );
			exit;
		}

		// Unset ID and last_query.
		unset( $address->ID );
		unset( $address->last_query );

		wp_send_json_success( $address );
	}

	/**
	 * Maybe install database.
	 */
	public function maybe_install() {
		$version = get_option( 'woocommerce_correios_autofill_addresses_db_version' );

		if ( empty( $version ) ) {
			self::create_database();

			update_option( 'woocommerce_correios_autofill_addresses_db_version', '1.0.0' );
		}
	}

	/**
	 * Create database.
	 */
	public static function create_database() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$table_name      = $wpdb->prefix . self::$table;

		$sql = "CREATE TABLE $table_name (
			ID bigint(20) NOT NULL auto_increment,
			postcode char(8) NOT NULL,
			address longtext NULL,
			city longtext NULL,
			neighborhood longtext NULL,
			state char(2) NULL,
			last_query datetime NULL,
			PRIMARY KEY  (ID),
			KEY postcode (postcode)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		dbDelta( $sql );
	}
}

new WC_Correios_Autofill_Addresses();
