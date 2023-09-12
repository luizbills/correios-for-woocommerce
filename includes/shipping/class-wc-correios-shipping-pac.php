<?php
/**
 * Correios PAC shipping method.
 *
 * @package WooCommerce_Correios/Classes/Shipping
 * @since   3.0.0
 * @version 4.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PAC shipping method class.
 */
class WC_Correios_Shipping_PAC extends WC_Correios_Shipping {

	/**
	 * Service code.
	 *
	 * @var string
	 */
	protected $code = '03298';

	protected $declared_value_code = '064';

	/**
	 * Initialize PAC.
	 *
	 * @param int $instance_id Shipping zone instance.
	 */
	public function __construct( $instance_id = 0 ) {
		$this->id           = 'correios-pac';
		$this->method_title = __( 'PAC', 'correios-for-woocommerce' );
		$this->more_link    = 'https://www.correios.com.br/enviar/encomendas/nacional';

		parent::__construct( $instance_id );
	}

	/**
	 * Get the declared value from the package.
	 *
	 * @param  array $package Cart package.
	 * @return float
	 */
	protected function get_declared_value( $package ) {
		$value = $package['contents_cost'];
		if ( $value < 24.5  ) {
			$value = 0;
		}
		if ( $value > 3000 ) {
			$value = 3000;
		}
		return (float) $value;
	}
}
