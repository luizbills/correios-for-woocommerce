<?php
/**
 * Admin notice template.
 *
 * @package WooCommerce_Correios/Admin/Settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @var string $message */
/** @var string $classes */
$classes = $classes ?? 'notice notice-error';
?>

<div class="<?php esc_attr_e( $classes ) ?>" id="message">
	<p><?php echo wp_kses_post( $message ); ?></p>
</div>
