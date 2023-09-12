<?php
/**
 * Correios integration with the REST API.
 *
 * @package WooCommerce_Correios/Classes
 * @since   3.0.0
 * @version 4.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * WC_Correios_REST_API class.
 */
class WC_Correios_REST_API {

	/**
	 * Init REST API actions.
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_tracking_code' ), 100 );
	}

	/**
	 * Register tracking code field in WP REST API.
	 */
	public function register_tracking_code() {
		if ( ! function_exists( 'register_rest_field' ) ) {
			return;
		}

		register_rest_field( 'shop_order',
			'correios_tracking_code',
			array(
				'get_callback'    => array( $this, 'get_tracking_code_callback' ),
				'update_callback' => array( $this, 'update_tracking_code_callback' ),
				'schema'          => array(
					'description' => __( 'Correios tracking code.', 'correios-for-woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
			)
		);
	}

	/**
	 * Get tracking code callback.
	 *
	 * @param array           $data    Details of current response.
	 * @param string          $field   Name of field.
	 * @param WP_REST_Request $request Current request.
	 *
	 * @return string
	 */
	public function get_tracking_code_callback( $data, $field, $request ) {
		return implode( ',', wc_correios_get_tracking_codes( $data['id'] ) );
	}

	/**
	 * Update tracking code callback.
	 *
	 * @param string  $value  The value of the field.
	 * @param WP_Post $object The object from the response.
	 *
	 * @return bool
	 */
	public function update_tracking_code_callback( $value, $object ) {
		if ( ! $value || ! is_string( $value ) ) {
			return;
		}

		return wc_correios_update_tracking_code( $object->ID, $value );
	}
}

new WC_Correios_REST_API();
