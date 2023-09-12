<?php
/**
 * Correios Tracking History.
 *
 * @package WooCommerce_Correios/Classes/Tracking
 * @since   3.0.0
 * @version 4.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Correios tracking history class.
 */
class WC_Correios_Tracking_History {

	/**
	 * Initialize actions.
	 */
	public function __construct() {
		add_action( 'woocommerce_order_details_after_order_table', array( $this, 'view' ), 1 );
	}

	/**
	 * Get user data.
	 *
	 * @return array
	 */
	protected function get_user_data() {
		$user_data = apply_filters( 'woocommerce_correios_tracking_user_data', array(
			'login'    => 'ECT',
			'password' => 'SRO',
		) );

		return $user_data;
	}

	/**
	 * Logger.
	 *
	 * @param string $message Data to log.
	 * @param string $level
	 */
	protected function log( $message, $level = 'info' ) {
		if ( apply_filters( 'woocommerce_correios_enable_tracking_debug', false ) ) {
			$logger = wc_get_logger();
			$logger->log( $level, $message, array(
				'source'  => 'correios-tracking-history',
			) );
		}
	}

	/**
	 * Access API Correios.
	 *
	 * @throws Exception When username or password fails.
	 * @param  array $tracking_codes Tracking codes.
	 * @return array
	 */
	protected function get_tracking_history( $tracking_codes ) {
		$objects = [];

		try {
			$api = new WC_Correios_Webservice( 'correios-tracking-history' );
			$response = $api->request_tracking_history( $tracking_codes, 'T' );

			foreach ( $response['objetos'] as $object ) {
				if ( empty( $object['eventos'] ) ) {
					$object['erro'] = $object['mensagem'];
					unset( $object['mensagem'] );
				}
				$objects[] = $object;
			}
		} catch ( Exception $e ) {
			$this->log( sprintf( 'An error occurred while trying to fetch the tracking history for "%s": %s', implode( ', ', $tracking_codes ), $e->getMessage() ) );
		}

		if ( ! empty( $objects ) ) {
			$this->log( sprintf( 'Tracking history found successfully: %s', wc_print_r( $objects, true ) ) );
		}

		return apply_filters( 'woocommerce_correios_tracking_objects', $objects, $tracking_codes );
	}

	/**
	 * Display the order tracking code in order details and the tracking history.
	 *
	 * @param WC_Order $order Order data.
	 */
	public function view( $order ) {
		$objects = array();

		$tracking_codes = wc_correios_get_tracking_codes( $order );

		// Check if exist a tracking code for the order.
		if ( empty( $tracking_codes ) ) {
			return;
		}

		wc_get_template(
			'myaccount/tracking-title.php',
			array(),
			'',
			WC_Correios::get_templates_path()
		);

		wc_get_template(
			'myaccount/tracking-codes.php',
			array(
				'codes' => $tracking_codes,
			),
			'',
			WC_Correios::get_templates_path()
		);
	}
}

new WC_Correios_Tracking_History();
