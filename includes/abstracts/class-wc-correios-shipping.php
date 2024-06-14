<?php
/**
 * Abstract Correios shipping method.
 *
 * @package WooCommerce_Correios/Abstracts
 * @since   3.0.0
 * @version 4.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Default Correios shipping method abstract class.
 *
 * This is a abstract method with default options for all methods.
 */
abstract class WC_Correios_Shipping extends WC_Shipping_Method {

	/**
	 * @var string
	 */
	public $origin_postcode = '';

	/**
	 * @var int
	 */
	public $shipping_class_id = null;

	/**
	 * @var bool
	 */
	public $show_delivery_time = true;

	/**
	 * @var int
	 */
	public $additional_time = 0;

	/**
	 * @var bool
	 */
	public $receipt_notice = false;

	/**
	 * @var bool
	 */
	public $own_hands = false;

	/**
	 * @var bool
	 */
	public $declare_value = true;

	/**
	 * Declared value service code.
	 *
	 * @var string
	 */
	protected $declared_value_code = '';

	/**
	 * @var string
	 */
	public $more_link = '';

	/**
	 * Service code.
	 *
	 * @var string
	 */
	protected $code = '';

	/**
	 * Custom service code.
	 *
	 * @var string
	 */
	protected $custom_code = '';

	/**
	 * @var string
	 */
	protected $login = '';

	/**
	 * @var string
	 */
	protected $password = '';

	/**
	 * @var float
	 */
	protected $minimum_height = 0;

	/**
	 * @var float
	 */
	protected $minimum_width = 0;

	/**
	 * @var float
	 */
	protected $minimum_length = 0;

	/**
	 * @var float
	 */
	protected $extra_weight = 0;

	/**
	 * @var bool
	 */
	protected $debug = false;

	/**
	 * @var int
	 */
	protected $default_delivery_time = 15;

	/**
	 * Initialize the Correios shipping method.
	 *
	 * @param int $instance_id Shipping zone instance ID.
	 */
	public function __construct( $instance_id = 0 ) {
		$this->instance_id = absint( $instance_id );
		/* translators: %s: method title */
		$this->method_description = sprintf( __( '%s is a shipping method from Correios.', 'correios-for-woocommerce' ), $this->method_title );

		$this->supports = array(
			'shipping-zones',
			'instance-settings',
		);

		// Load the form fields.
		$this->init_form_fields();

		// Define user set variables.
		$this->enabled            = $this->get_option( 'enabled' );
		$this->title              = $this->get_option( 'title' );
		$this->origin_postcode    = $this->get_option( 'origin_postcode' );
		$this->shipping_class_id  = (int) $this->get_option( 'shipping_class_id', '-1' );
		$this->show_delivery_time = 'yes' === $this->get_option( 'show_delivery_time' );
		$this->additional_time    = (int) $this->get_option( 'additional_time' );
		$this->fee                = $this->get_option( 'fee' );
		$this->receipt_notice     = 'yes' === $this->get_option( 'receipt_notice' );
		$this->own_hands          = 'yes' === $this->get_option( 'own_hands' );
		$this->declare_value      = 'yes' === $this->get_option( 'declare_value' );
		$this->custom_code        = $this->get_option( 'custom_code' );
		$this->login              = $this->get_option( 'login' );
		$this->password           = $this->get_option( 'password' );
		$this->minimum_height     = (float) $this->get_option( 'minimum_height' );
		$this->minimum_width      = (float) $this->get_option( 'minimum_width' );
		$this->minimum_length     = (float) $this->get_option( 'minimum_length' );
		$this->extra_weight       = (float) $this->get_option( 'extra_weight', '0' );
		$this->debug              = 'yes' === $this->get_option( 'debug' );

		// Save admin options.
		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
	}

	/**
	 * Get log.
	 *
	 * @return string
	 */
	protected function get_log_link() {
		return ' <a href="' . esc_url( admin_url( 'admin.php?page=wc-status&tab=logs&log_file=' . esc_attr( $this->id ) . '-' . sanitize_file_name( wp_hash( $this->id ) ) . '.log' ) ) . '">' . __( 'View logs.', 'correios-for-woocommerce' ) . '</a>';
	}

	/**
	 * Get base postcode.
	 *
	 * @since  3.5.1
	 * @return string
	 */
	protected function get_base_postcode() {
		// WooCommerce 3.1.1+.
		if ( method_exists( WC()->countries, 'get_base_postcode' ) ) {
			return WC()->countries->get_base_postcode();
		}

		return '';
	}

	/**
	 * Get shipping classes options.
	 *
	 * @return array
	 */
	protected function get_shipping_classes_options() {
		$shipping_classes = WC()->shipping->get_shipping_classes();
		$options          = array(
			'-1' => __( 'Any Shipping Class', 'correios-for-woocommerce' ),
			'0'  => __( 'No Shipping Class', 'correios-for-woocommerce' ),
		);

		if ( ! empty( $shipping_classes ) ) {
			$options += wp_list_pluck( $shipping_classes, 'name', 'term_id' );
		}

		return $options;
	}

	/**
	 * Admin options fields.
	 */
	public function init_form_fields() {
		$this->instance_form_fields = array(
			'enabled'            => array(
				'title'   => __( 'Enable/Disable', 'correios-for-woocommerce' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable this shipping method', 'correios-for-woocommerce' ),
				'default' => 'yes',
			),
			'title'              => array(
				'title'       => __( 'Title', 'correios-for-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'correios-for-woocommerce' ),
				'desc_tip'    => true,
				'default'     => $this->method_title,
			),
			'behavior_options'   => array(
				'title'   => __( 'Behavior Options', 'correios-for-woocommerce' ),
				'type'    => 'title',
				'default' => '',
			),
			'origin_postcode'    => array(
				'title'       => __( 'Origin Postcode', 'correios-for-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'The postcode of the location your packages are delivered from.', 'correios-for-woocommerce' ),
				'desc_tip'    => true,
				'placeholder' => '00000-000',
				'default'     => $this->get_base_postcode(),
			),
			'shipping_class_id'  => array(
				'title'       => __( 'Shipping Class', 'correios-for-woocommerce' ),
				'type'        => 'select',
				'description' => __( 'If necessary, select a shipping class to apply this method.', 'correios-for-woocommerce' ),
				'desc_tip'    => true,
				'default'     => '',
				'class'       => 'wc-enhanced-select',
				'options'     => $this->get_shipping_classes_options(),
			),
			'show_delivery_time' => array(
				'title'       => __( 'Delivery Time', 'correios-for-woocommerce' ),
				'type'        => 'checkbox',
				'label'       => __( 'Show estimated delivery time', 'correios-for-woocommerce' ),
				'description' => __( 'Display the estimated delivery time in working days.', 'correios-for-woocommerce' ),
				'desc_tip'    => true,
				'default'     => 'no',
			),
			'additional_time'    => array(
				'title'       => __( 'Additional Days', 'correios-for-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Additional working days to the estimated delivery.', 'correios-for-woocommerce' ),
				'desc_tip'    => true,
				'default'     => '0',
				'placeholder' => '0',
			),
			'fee'                => array(
				'title'       => __( 'Handling Fee', 'correios-for-woocommerce' ),
				'type'        => 'price',
				'description' => __( 'Enter an amount, e.g. 2.50, or a percentage, e.g. 5%. Leave blank to disable.', 'correios-for-woocommerce' ),
				'desc_tip'    => true,
				'placeholder' => '0.00',
				'default'     => '',
			),
			'optional_services'  => array(
				'title'       => __( 'Optional Services', 'correios-for-woocommerce' ),
				'type'        => 'title',
				'description' => __( 'Use these options to add the value of each service provided by the Correios.', 'correios-for-woocommerce' ),
				'default'     => '',
			),
			'receipt_notice'     => array(
				'title'       => __( 'Receipt Notice', 'correios-for-woocommerce' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable receipt notice', 'correios-for-woocommerce' ),
				'description' => __( 'This controls whether to add costs of the receipt notice service.', 'correios-for-woocommerce' ),
				'desc_tip'    => true,
				'default'     => 'no',
			),
			'own_hands'          => array(
				'title'       => __( 'Own Hands', 'correios-for-woocommerce' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable own hands', 'correios-for-woocommerce' ),
				'description' => __( 'This controls whether to add costs of the own hands service', 'correios-for-woocommerce' ),
				'desc_tip'    => true,
				'default'     => 'no',
			),
			'declare_value'      => array(
				'title'       => __( 'Declare Value for Insurance', 'correios-for-woocommerce' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable declared value', 'correios-for-woocommerce' ),
				'description' => __( 'This controls if the price of the package must be declared for insurance purposes.', 'correios-for-woocommerce' ),
				'desc_tip'    => true,
				'default'     => 'yes',
			),
			'service_options'    => array(
				'title'   => __( 'Service Options', 'correios-for-woocommerce' ),
				'type'    => 'title',
				'default' => '',
			),
			'custom_code'        => array(
				'title'       => __( 'Service Code', 'correios-for-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Service code, use this for custom codes.', 'correios-for-woocommerce' ),
				'desc_tip'    => true,
				'placeholder' => $this->code,
				'default'     => '',
			),
			'package_standard'   => array(
				'title'       => __( 'Package Standard', 'correios-for-woocommerce' ),
				'type'        => 'title',
				'description' => __( 'Minimum measure for your shipping packages.', 'correios-for-woocommerce' ),
				'default'     => '',
			),
			'minimum_height'     => array(
				'title'       => __( 'Minimum Height (cm)', 'correios-for-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Minimum height of your shipping packages. Correios needs at least 2cm.', 'correios-for-woocommerce' ),
				'desc_tip'    => true,
				'default'     => '2',
			),
			'minimum_width'      => array(
				'title'       => __( 'Minimum Width (cm)', 'correios-for-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Minimum width of your shipping packages. Correios needs at least 11cm.', 'correios-for-woocommerce' ),
				'desc_tip'    => true,
				'default'     => '11',
			),
			'minimum_length'     => array(
				'title'       => __( 'Minimum Length (cm)', 'correios-for-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Minimum length of your shipping packages. Correios needs at least 16cm.', 'correios-for-woocommerce' ),
				'desc_tip'    => true,
				'default'     => '16',
			),
			'extra_weight'       => array(
				'title'       => __( 'Extra Weight (kg)', 'correios-for-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Extra weight in kilograms to add to the package total when quoting shipping costs.', 'correios-for-woocommerce' ),
				'desc_tip'    => true,
				'default'     => '0',
			),
			'testing'            => array(
				'title'   => __( 'Testing', 'correios-for-woocommerce' ),
				'type'    => 'title',
				'default' => '',
			),
			'debug'              => array(
				'title'       => __( 'Debug Log', 'correios-for-woocommerce' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable logging', 'correios-for-woocommerce' ),
				'default'     => 'no',
				/* translators: %s: method title */
				'description' => sprintf( __( 'Log %s events, such as WebServices requests.', 'correios-for-woocommerce' ), $this->method_title ) . $this->get_log_link(),
			),
		);
	}

	/**
	 * Correios options page.
	 */
	public function admin_options() {
		include WC_Correios::get_plugin_path() . 'includes/admin/views/html-admin-shipping-method-settings.php';
	}

	/**
	 * Get Correios service code.
	 *
	 * @return string
	 */
	public function get_code() {
		$code = ! empty( $this->custom_code ) ? $this->custom_code : $this->code;
		return apply_filters( 'woocommerce_correios_shipping_method_code', $code, $this->id, $this->instance_id );
	}

	/**
	 * Get additional time.
	 *
	 * @param  array $package Package data.
	 *
	 * @return array
	 */
	protected function get_additional_time( $package = array() ) {
		return apply_filters( 'woocommerce_correios_shipping_additional_time', $this->additional_time, $package );
	}

	/**
	 * Check if package uses only the selected shipping class.
	 *
	 * @param  array $package Cart package.
	 * @return bool
	 */
	protected function has_only_selected_shipping_class( $package ) {
		$only_selected = true;

		if ( -1 === $this->shipping_class_id ) {
			return $only_selected;
		}

		foreach ( $package['contents'] as $item_id => $values ) {
			$product = $values['data'];
			$qty     = $values['quantity'];

			if ( $qty > 0 && $product->needs_shipping() ) {
				if ( $this->shipping_class_id !== $product->get_shipping_class_id() ) {
					$only_selected = false;
					break;
				}
			}
		}

		return $only_selected;
	}

	/**
	 * Get the declared value from the package.
	 *
	 * @param  array $package Cart package.
	 *
	 * @return float
	 */
	protected function get_declared_value( $package ) {
		return $package['contents_cost'];
	}

	protected function get_delivery_time( $package ) {
		$api = new WC_Correios_Webservice( $this->id, $this->instance_id );

		$api->set_debug( $this->debug );
		$api->set_service( $this->get_code() );
		$api->set_origin_postcode( $this->origin_postcode );
		$api->set_destination_postcode( $package['destination']['postcode'] );

		$response = $api->request_shipping_delivery_time();

		return $response;
	}

	/**
	 * Get shipping rate.
	 *
	 * @param  array $package Cart package.
	 *
	 * @return array|null
	 */
	protected function get_rate( $package ) {
		$api = new WC_Correios_Webservice( $this->id, $this->instance_id );

		$api->set_debug( $this->debug );
		$api->set_service( $this->get_code() );
		$api->set_package( $package );
		$api->set_origin_postcode( $this->origin_postcode );
		$api->set_destination_postcode( $package['destination']['postcode'] );

		if ( $this->declare_value && $this->declared_value_code ) {
			$api->set_declared_value_code( $this->declared_value_code );
			$api->set_declared_value( $this->get_declared_value( $package ) );
		}

		$api->set_own_hands( $this->own_hands );
		$api->set_receipt_notice( $this->receipt_notice );

		$api->set_minimum_height( $this->minimum_height );
		$api->set_minimum_width( $this->minimum_width );
		$api->set_minimum_length( $this->minimum_length );
		$api->set_extra_weight( $this->extra_weight );

		$response = $api->request_shipping_cost();

		if ( is_wp_error( $response ) ) {
			/** @var WP_Error $response */
			$this->add_cart_notice( $response->get_error_message() );
			return null;
		}

		if ( $this->show_delivery_time && ! is_null( $response ) ) {
			$delivery_time = $this->get_delivery_time( $package );
			if ( ! is_null( $delivery_time ) ) {
				$response['prazoEntrega'] = $delivery_time['prazoEntrega'];
			}
		}

		return $response;
	}

	/**
	 * Calculates the shipping rate.
	 *
	 * @param array $package Order package.
	 */
	public function calculate_shipping( $package = array() ) {
		// Only available in Brazil
		if ( 'BR' !== $package['destination']['country'] || empty( 1 ) ) {
			return;
		}

		// Check for shipping classes.
		if ( ! $this->has_only_selected_shipping_class( $package ) ) {
			return;
		}

		// Check for valid postcode
		$destination_postcode = wc_correios_sanitize_postcode( $package['destination']['postcode'] ?? '' );
		if ( ! $destination_postcode ) {
			// $error_message = __( 'Invalid zip code.', 'correios-for-woocommerce' );
			// $this->add_cart_notice( $error_message );
			return;
		}

		$shipping = $this->get_rate( $package );
		if ( empty( $shipping ) ) {
			return;
		}

		// Set the shipping rates.
		$label = $this->title;
		$cost  = (float) wc_correios_normalize_price( esc_attr( (string) $shipping['pcFinal'] ) );

		// Exit if don't have price.
		if ( $cost <= 0 ) {
			return;
		}

		// Apply fees.
		$fee = $this->get_fee( $this->fee, $cost );

		// Display delivery.
		$meta_delivery = array();
		if ( $this->show_delivery_time ) {
			$delivery_time = absint( $shipping['prazoEntrega'] ?? $this->default_delivery_time );
			$meta_delivery = array(
				'_delivery_forecast' => $delivery_time + absint( $this->get_additional_time( $package ) ),
			);
		}

		// Create the rate and apply filters.
		$rate = apply_filters(
			'woocommerce_correios_' . $this->id . '_rate', array(
				'id'        => $this->id . $this->instance_id,
				'label'     => $label,
				'cost'      => (float) $cost + (float) $fee,
				'meta_data' => $meta_delivery,
			), $this->instance_id, $package
		);

		// Add rate to WooCommerce.
		$this->add_rate( $rate );
	}

	protected function add_cart_notice ( $message, $type = 'error' ) {
		$notice = '<strong>' . esc_html( $this->title ) . ':</strong> '. esc_html( $message );
		wc_add_notice( $notice, $type );
	}
}
