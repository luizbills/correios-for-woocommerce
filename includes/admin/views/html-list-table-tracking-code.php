<?php
/**
 * List table - Tracking Code
 *
 * @package WooCommerce_Correios/Admin/Settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="correios-tracking-code">
	<small class="meta">
		<?php echo esc_html( _n( 'Tracking code:', 'Tracking codes:', count( $tracking_codes ), 'correios-for-woocommerce' ) ); ?>
		<?php echo implode( ' | ', $tracking_codes ); ?>
	</small>
</div>
